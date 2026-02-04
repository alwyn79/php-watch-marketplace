# Quick Start Guide (No Installation Required)

Follow these 4 simple steps to run your Watch Store without installing XAMPP or MySQL.

### Step 1: Download Portable PHP
1. Click this link: [Download PHP 8.2 (VS16 x64 Thread Safe)](https://windows.php.net/downloads/releases/php-8.2.14-Win32-vs16-x64.zip)
   *(If the link doesn't work, go to windows.php.net/download and get the "VS16 x64 Thread Safe" Zip)*
2. Right-click the downloaded Zip file and select **Extract All**.
3. **Important**: Extract it to a simple folder name like: `C:\php`

### Step 2: Configure PHP (One-time)
1. Go to your new `C:\php` folder.
2. Find the file `php.ini-development`.
3. Rename it to `php.ini`.
4. Open `php.ini` with Notepad or VS Code.
5. Find these lines (Ctrl+F) and **remove the ; symbol** at the start to enable them:
   *   `;extension=pdo_sqlite`  ->  `extension=pdo_sqlite`
   *   `;extension=sqlite3`     ->  `extension=sqlite3`
   *   `;extension=fileinfo`    ->  `extension=fileinfo`
   *   `;extension=openssl`     ->  `extension=openssl`

### Step 3: Setup the Database
In your VS Code Terminal (at the bottom of your screen), copy and paste this command:
```powershell
C:\php\php.exe setup_sqlite.php
```
You should see: *"Success! Your database is ready."*

### Step 4: Run the Store
Copy and paste this command to start the website:
```powershell
C:\php\php.exe -S localhost:8000 -t public
```

### Step 5: View It!
Open your web browser and go to: **[http://localhost:8000](http://localhost:8000)**

---
**Troubleshooting**:
*   **"Images not working?"** -> In this simple mode, images upload to your local folder `public/uploads`. No S3 needed!
*   **"Command not found?"** -> Make sure you typed the path to php correctly (`C:\php\php.exe`).
