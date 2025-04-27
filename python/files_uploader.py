import requests
import os
import time

api_url = 'http://192.168.0.102:8000/api/upload_files'
folder_path = r'C:\Users\kaye\Desktop\printify-bluetooth-files'

allowed_extensions = {'.pdf', '.doc', '.docx'}  # Only allowed file types

def upload_files(file_paths):
    try:
        files = {}
        for idx, path in enumerate(file_paths):
            files[f'file_{idx}'] = (os.path.basename(path), open(path, 'rb'))

        response = requests.post(api_url, files=files)
        
        # Close all opened files
        for file in files.values():
            file[1].close()

        print(f"Upload attempt for {len(file_paths)} files. Status code: {response.status_code}")
        return response.status_code
    except requests.exceptions.RequestException as e:
        print(f"Network error uploading files: {e}")
        return None
    except Exception as e:
        print(f"Unexpected error: {e}")
        return None

def main():
    if not os.path.exists(folder_path):
        print(f"Folder {folder_path} does not exist. Please create it and add files to upload.")
        return

    while True:
        try:
            files = [f for f in os.listdir(folder_path) if os.path.isfile(os.path.join(folder_path, f))]
            if not files:
                print("No files to upload. Waiting...")
                time.sleep(5)
                continue

            valid_files = []
            for file_name in files:
                file_path = os.path.join(folder_path, file_name)
                _, ext = os.path.splitext(file_name)
                ext = ext.lower()

                if ext not in allowed_extensions:
                    print(f"Deleted {file_name}: not an allowed file type.")
                    os.remove(file_path)
                else:
                    valid_files.append(file_path)

            if valid_files:
                status_code = upload_files(valid_files)
                
                if status_code == 200 or status_code == 500:
                    for file_path in valid_files:
                        os.remove(file_path)
                        print(f"Deleted {file_path} after upload (status {status_code}).")
                else:
                    print(f"Upload failed with status {status_code}. Will retry later.")

            time.sleep(5)  # Check again after 5 seconds
        except Exception as e:
            print(f"Error in main loop: {e}")
            time.sleep(5)

if __name__ == "__main__":
    main()
