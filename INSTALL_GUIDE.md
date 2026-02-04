# System Setup Guide for Windows

Since you only have VS Code installed, you will need to install a few tools to run this PHP application. The easiest way is to use a package like **XAMPP**, which installs PHP, MySQL, and Apache all at once.

## 1. Install XAMPP (The Easiest Way)
XAMPP gives you PHP and MySQL in one easy installer.

1.  **Download XAMPP**:
    *   Go to [apachefriends.org](https://www.apachefriends.org/download.html).
    *   Download the latest version for **Windows** (with PHP 8.0 or higher).
2.  **Install**:
    *   Run the installer.
    *   Keep the default settings (ensure **MySQL** and **PHP** are checked).
    *   It usually installs to `C:\xampp`.
3.  **Start Services**:
    *   Open "XAMPP Control Panel" from your Start menu.
    *   Click **Start** next to **Apache** (Web Server).
    *   Click **Start** next to **MySQL** (Database).

## 2. Install Composer (For Libraries)
We need Composer to install the AWS SDK (used for uploading images).

1.  **Download**:
    *   Go to [getcomposer.org/download](https://getcomposer.org/download/).
    *   Download and run **Composer-Setup.exe**.
2.  **Install**:
    *   During installation, it will ask for your "PHP Command Line". 
    *   Browse and select: `C:\xampp\php\php.exe`.
    *   Finish the installation.

## 3. Install LocalStack (Optional - For Image Uploads)
Since the app uses S3 for images, we use "LocalStack" to fake this locally. 
*If you find this too complex, you can skip it for now and the app will just use local folders seamlessly (I added fallback logic).*

1.  **Requirement**: You need **Docker** installed first (docker.com).
2.  **Install**: Open your terminal (PowerShell or VS Code) and run:
    ```powershell
    pip install localstack
    ```

---

## How to Run Your Project

Once you have installed the above:

1.  **Open VS Code Terminal** (`Ctrl` + `~`).
2.  **Install Project Libraries**:
    ```powershell
    composer install
    ```
3.  **Setup Database**:
    *   Go to `http://localhost/phpmyadmin` in your browser.
    *   Click "New" -> Name it `watch_store` -> Click "Create".
    *   Click on the new database, go to the "Import" tab, and upload the `database.sql` file from your project folder.
4.  **Run the App**:
    In your VS Code terminal, run:
    ```powershell
    php -S localhost:8000 -t public
    ```
5.  **View Site**: Open `http://localhost:8000`.
