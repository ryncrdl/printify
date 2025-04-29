import os
import requests
from flask import Flask
from bluetooth import BluetoothSocket, RFCOMM

app = Flask(__name__)

# CONFIG
UPLOAD_FOLDER = "received_files"
LARAVEL_UPLOAD_URL = "https://printify.icu/api/upload_files" 

if not os.path.exists(UPLOAD_FOLDER):
    os.makedirs(UPLOAD_FOLDER)

@app.route('/')
def home():
    return "Bluetooth File Receiver is running!"

def receive_bluetooth_file():
    server_sock = BluetoothSocket(RFCOMM)
    server_sock.bind(("", 3))  # Channel 3 usually works
    server_sock.listen(1)

    print("Waiting for Bluetooth connection...")

    client_sock, client_info = server_sock.accept()
    print("Accepted connection from ", client_info)

    filename = os.path.join(UPLOAD_FOLDER, "received_file")
    with open(filename, 'wb') as f:
        while True:
            data = client_sock.recv(1024)
            if not data:
                break
            f.write(data)

    client_sock.close()
    server_sock.close()

    print("File received successfully.")

    return filename

def send_to_laravel(filepath):
    with open(filepath, 'rb') as f:
        files = {'files[]': f}
        response = requests.post(LARAVEL_UPLOAD_URL, files=files)
    
    print("Uploaded to Laravel:", response.text)

if __name__ == '__main__':
    from threading import Thread

    # Thread 1: Flask server
    Thread(target=lambda: app.run(port=5000)).start()

    # Thread 2: Bluetooth receiver
    while True:
        filepath = receive_bluetooth_file()
        send_to_laravel(filepath)
