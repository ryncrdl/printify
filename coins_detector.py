import serial
import requests

ser = serial.Serial('COM4', 9600)  # Replace COM4 with your port
api_url = 'http://192.168.0.102:8000/api/coin_inserted'  # Laravel API endpoint

while True:
    line = ser.readline().decode('utf-8').strip()
    if "Value:" in line:
        pesos = int(line.split("Value:")[1].strip().split()[0])
        headers = {'Content-Type': 'application/json'}
        # Send the coin value to Laravel
        response = requests.post(api_url, json={'amount': pesos}, headers=headers)
        print(f"Sent {pesos} pesos to Laravel, Response: {response.status_code}")
