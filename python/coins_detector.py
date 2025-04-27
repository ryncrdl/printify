import serial
import serial.tools.list_ports
import requests
import time

api_url = 'http://192.168.0.102:8000/api/coin_inserted'  
serial_port = 'COM4'  
baud_rate = 9600

def connect_serial(port, baudrate):
    while True:
        try:
            ser = serial.Serial(port, baudrate, timeout=1)
            print(f"Connected to {port}")
            return ser
        except serial.SerialException as e:
            print(f"Serial {port} not available. Retrying in 5 seconds...")
            time.sleep(5)

ser = connect_serial(serial_port, baud_rate)

while True:
    try:
        line = ser.readline().decode('utf-8').strip()
        if "Value:" in line:
            pesos = int(line.split("Value:")[1].strip().split()[0])
            headers = {'Content-Type': 'application/json'}
            
            # Send the coin value to Laravel
            response = requests.post(api_url, json={'amount': pesos}, headers=headers)
            print(f"Sent {pesos} pesos to Laravel, Response: {response.status_code}")
    except serial.SerialException:
        print("Serial disconnected! Reconnecting...")
        ser = connect_serial(serial_port, baud_rate)
    except Exception as e:
        print(f"Error: {e}")
