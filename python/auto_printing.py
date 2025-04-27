import os
import time
import shutil
import logging
import win32print
import win32api

# Setup logging
logging.basicConfig(filename="printer.log", level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

# Config
FOLDER_PATH = r'C:\Users\kaye\Desktop\printifty-ready-for-print'
PRINTED_FOLDER = r'C:\Users\kaye\Desktop\printed-files'
PAPER_SIZE = "Long"  # "Long" = A4, "Short" = Letter
COLOR_MODE = "Colored"  # "Colored" or "BlackAndWhite"

PAPER_SIZES = {
    "Long": 9,
    "Short": 1
}

COLOR_MODES = {
    "Colored": 2,
    "BlackAndWhite": 1
}

def ensure_folder_exists(path):
    if not os.path.exists(path):
        os.makedirs(path)

def check_printer_available(printer_name):
    try:
        hprinter = win32print.OpenPrinter(printer_name)
        properties = win32print.GetPrinter(hprinter, 2)
        status = properties['Status']
        win32print.ClosePrinter(hprinter)

        if status == 0:
            return True
        else:
            logging.warning(f"Printer {printer_name} has status code: {status}")
            return False

    except Exception as e:
        logging.error(f"Error checking printer availability: {e}")
        return False

def print_file(file_path, printer_name):
    try:
        hprinter = win32print.OpenPrinter(printer_name)
        properties = win32print.GetPrinter(hprinter, 2)
        devmode = properties["pDevMode"]

        # Set paper and color settings
        devmode.PaperSize = PAPER_SIZES.get(PAPER_SIZE, 9)
        devmode.Color = COLOR_MODES.get(COLOR_MODE, 2)

        job = win32print.StartDocPrinter(hprinter, 1, ("Print Job", None, "RAW"))
        win32print.StartPagePrinter(hprinter)

        # Print via ShellExecute
        win32api.ShellExecute(0, "print", file_path, None, ".", 0)

        win32print.EndPagePrinter(hprinter)
        win32print.EndDocPrinter(hprinter)
        win32print.ClosePrinter(hprinter)

        logging.info(f"Printed: {file_path}")

    except Exception as e:
        logging.error(f"Failed to print {file_path}: {e}")

def move_to_printed_folder(file_path):
    file_name = os.path.basename(file_path)
    destination = os.path.join(PRINTED_FOLDER, file_name)
    shutil.move(file_path, destination)
    logging.info(f"Moved printed file to: {destination}")

def main():
    ensure_folder_exists(PRINTED_FOLDER)
    printer_name = win32print.GetDefaultPrinter()

    if not os.path.exists(FOLDER_PATH):
        logging.error(f"Folder {FOLDER_PATH} does not exist.")
        return

    while True:
        try:
            if not check_printer_available(printer_name):
                logging.warning("Printer not available. Retrying in 10 seconds...")
                time.sleep(10)
                continue

            files = [f for f in os.listdir(FOLDER_PATH) if os.path.isfile(os.path.join(FOLDER_PATH, f))]
            if not files:
                logging.info("No files to print. Waiting...")
                time.sleep(5)
                continue

            for file_name in files:
                file_path = os.path.join(FOLDER_PATH, file_name)
                _, ext = os.path.splitext(file_name)
                ext = ext.lower()

                if ext in ['.pdf', '.doc', '.docx', '.txt']:
                    print_file(file_path, printer_name)
                    move_to_printed_folder(file_path)
                else:
                    logging.warning(f"Unsupported file type {file_name}. Deleting...")
                    os.remove(file_path)

            time.sleep(5)

        except Exception as e:
            logging.error(f"Error in main loop: {e}")
            time.sleep(5)

if __name__ == "__main__":
    main()
