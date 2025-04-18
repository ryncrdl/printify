# Define log file path for Laravel log
$laravelLog = "$env:USERPROFILE\Desktop\printify\storage\logs\laravel.log"

# Define upload folder
$upload_files = "$env:USERPROFILE\Desktop\printify\public\storage\received_files"

# Logging helper
function LogMessage($message) {
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Add-Content -Path $laravelLog -Value "$timestamp - [BluetoothReceiver] $message"
}

# Start the Bluetooth File Transfer wizard
LogMessage "Launching Bluetooth wizard..."
Start-Process "fsquirt.exe"
Start-Sleep -Seconds 1

# Load Windows Forms to send keys
Add-Type -AssemblyName System.Windows.Forms

# Load user32.dll for window title detection and UI parsing
Add-Type @"
using System;
using System.Runtime.InteropServices;
using System.Text;
public class User32 {
    [DllImport("user32.dll")]
    public static extern IntPtr GetForegroundWindow();

    [DllImport("user32.dll", CharSet = CharSet.Auto, SetLastError = true)]
    public static extern int GetWindowText(IntPtr hWnd, StringBuilder lpString, int nMaxCount);

    [DllImport("user32.dll")]
    public static extern bool EnumChildWindows(IntPtr hWndParent, EnumChildProc lpEnumFunc, IntPtr lParam);

    public delegate bool EnumChildProc(IntPtr hWnd, IntPtr lParam);

    [DllImport("user32.dll")]
    public static extern int GetWindowTextLength(IntPtr hWnd);

    [DllImport("user32.dll")]
    public static extern IntPtr GetClassName(IntPtr hWnd, StringBuilder lpClassName, int nMaxCount);
}
"@

# Get the active window title
function Get-ActiveWindowTitle {
    $buff = New-Object System.Text.StringBuilder 500
    $handle = [User32]::GetForegroundWindow()
    [User32]::GetWindowText($handle, $buff, $buff.Capacity) | Out-Null
    return $buff.ToString()
}

# Get buttons from current window
function Get-ButtonsInWindow {
    $handle = [User32]::GetForegroundWindow()
    $buttons = @()

    $callback = [User32+EnumChildProc]{
        param([IntPtr]$hWnd, [IntPtr]$lParam)

        $className = New-Object System.Text.StringBuilder 256
        [User32]::GetClassName($hWnd, $className, $className.Capacity) | Out-Null

        if ($className.ToString() -eq "Button") {
            $buttonText = New-Object System.Text.StringBuilder 256
            [User32]::GetWindowText($hWnd, $buttonText, $buttonText.Capacity) | Out-Null
            $btn = $buttonText.ToString()
            if ($btn) { $buttons += $btn }
        }
        return $true
    }

    [User32]::EnumChildWindows($handle, $callback, [IntPtr]::Zero) | Out-Null
    return $buttons
}

# Wait for Bluetooth window
LogMessage "Waiting for Bluetooth window..."
$fsquirtTimeout = [datetime]::Now.AddSeconds(10)
do {
    $activeWindow = Get-ActiveWindowTitle
    Start-Sleep -Milliseconds 500
} while ($activeWindow -notlike "*Bluetooth*" -and [datetime]::Now -lt $fsquirtTimeout)

if ($activeWindow -like "*Bluetooth*") {
    LogMessage "Bluetooth window detected. Simulating TAB and ENTER..."
    [System.Windows.Forms.SendKeys]::SendWait("{TAB}")
    Start-Sleep -Milliseconds 500
    [System.Windows.Forms.SendKeys]::SendWait("{ENTER}")
} else {
    LogMessage "Bluetooth window not found in time. Exiting."
    exit
}

LogMessage "Waiting for incoming file..."

# Wait for file
$timeout = [datetime]::Now.AddMinutes(1)
do {
    $files = Get-ChildItem -Path $upload_files -File -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 1

    # Check buttons
    $buttons = Get-ButtonsInWindow
    LogMessage "Active window has the following buttons: $($buttons -join ', ')"
    if ($buttons.Count -gt 0) {
        LogMessage "Buttons found in the active window:"
        $buttons | ForEach-Object { LogMessage $_ }
    } else {
        LogMessage "No buttons found in the active window."
    }

    # Handle file received
    if ($files.Count -gt 0) {
        $receivedFile = $files[0].FullName
        LogMessage "File received: $receivedFile"

        LogMessage "Confirming transfer in Milliseconds 700"
        [System.Windows.Forms.SendKeys]::SendWait("{ENTER}")
        Start-Sleep -Milliseconds 700

        # Handle overwrite prompt
        $replaceTimeout = [datetime]::Now.AddSeconds(10)
        do {
            $title = Get-ActiveWindowTitle
            if ($title -like "*Replace or Skip Files*") {
                LogMessage "Overwrite dialog found. Pressing ENTER..."
                [System.Windows.Forms.SendKeys]::SendWait("{ENTER}")
                break
            }
            Start-Sleep -Milliseconds 500
        } while ([datetime]::Now -lt $replaceTimeout)

        LogMessage "Bluetooth file transfer completed successfully."
        break
    }

    # Handle save dialog
    $activeWindow = Get-ActiveWindowTitle
    if ($activeWindow -like "*Save the received file*") {
        LogMessage "Save dialog found. Pressing ENTER to save..."
        [System.Windows.Forms.SendKeys]::SendWait("{ENTER}")
        Start-Sleep -Seconds 1

        # Close the Bluetooth window
        Get-Process | Where-Object { $_.MainWindowTitle -like "*Bluetooth*" } | ForEach-Object {
            $_.CloseMainWindow()
            LogMessage "Closed Bluetooth window"
        }
        break
    }

} while ([datetime]::Now -lt $timeout)

# Handle timeout
if ($files.Count -eq 0) {
    LogMessage "Timeout waiting for file. No file received."
}
