# BridgeBox Offline LMS on Raspberry Pi  
Complete Project Readme  

## Project Overview  
This project runs a fully offline Learning Management System on a Raspberry Pi.  
The system behaves like a portable local server. Users connect through Wi-Fi and access the LMS in a browser without internet.

The Pi hosts a Laravel application named **bridgebox**.  
The database uses **SQLite**, which is a file inside the project.  
The system serves PDFs and images locally and supports many users on the same local network.

---

## Core Idea  
We turned a Raspberry Pi into a self-contained learning server.  
It works like a Laragon/XAMPP setup, but on real Linux hardware.  
The Pi creates its own Wi-Fi network. Any phone or laptop connects and opens the LMS through a browser.

---

## System Architecture  

### Hardware  
- Raspberry Pi 5  
- 4GB RAM  
- microSD for storage  

### Operating System  
- Raspberry Pi OS  

### Server Stack  
- Nginx web server  
- PHP-FPM  
- Laravel application named **bridgebox**  
- SQLite database file  

### Network Mode  
- Pi acts as a Wi-Fi hotspot  
- Users connect directly to the Pi  
- Access address is `http://10.42.0.1`  

---

## How the System Works  

1. Power on the Pi  
2. Linux starts  
3. Nginx and PHP-FPM start automatically  
4. The Pi broadcasts a Wi-Fi network  
5. A user connects to the Wi-Fi  
6. The user opens a browser and enters `10.42.0.1`  
7. Nginx serves the Laravel app  
8. Laravel reads and writes to the SQLite file  
9. Course PDFs and images load from local storage  

No internet is required after setup.

---

## Project Folder Structure  

**Main project path**  
``` /var/www/bridgebox ```


**Important folders**

| Folder | Purpose |
|--------|---------|
| public | Web root served by Nginx |
| storage | Laravel storage, logs, cache, sessions |
| bootstrap/cache | Laravel cached config and routes |
| database/database.sqlite | SQLite database file |

### Course Content Storage  
Store all PDFs and images on disk inside the project.  
Nginx serves these files directly for better performance.

---

## Laravel Configuration  

**.env important values**  
```
APP_ENV=production  
APP_DEBUG=false  
APP_URL=http://10.42.0.1 

DB_CONNECTION=sqlite  
DB_DATABASE=database/database.sqlite 
```


**Run these for optimization**
```
php artisan key:generate  
php artisan config:cache  
php artisan route:cache  
php artisan view:cache  
```


---

## Web Server Configuration  

Nginx root points to:  
``` /var/www/bridgebox/public ```


Static files like pdf, jpg, png, css, js are served directly by Nginx.  
Dynamic requests go to PHP-FPM which runs Laravel.

---

## Database Design  

Database engine is **SQLite**.  
It is a single-file database. No MySQL or MariaDB server runs.  
This makes the system simpler and fully offline.

SQLite works best when most activity is reading content, which fits PDFs and images.

---

## Performance Design  

To support many users:

- Static files served by Nginx, not Laravel  
- Laravel caching enabled  
- PHP OPcache enabled  
- SSD used for storage  
- Minimal background jobs  

SQLite can slow down if many users write at the same time. Heavy logging or frequent writes should be reduced.

---

## Hotspot Network  
```
Wi-Fi name: Bridgbox  
Password: 12345678  
IP of the Pi: 10.42.0.1  
```


DHCP assigns IPs to users automatically. All traffic stays local.

---

## Deployment Workflow  

1. Install Raspberry Pi OS Lite  
2. Install Nginx and PHP packages  
3. Copy **bridgebox** project to `/var/www/bridgebox`  
4. Set permissions for storage, cache, and database folders  
5. Configure `.env` for SQLite  
6. Configure Nginx site  
7. Set up hotspot with hostapd and dnsmasq  
8. Reboot Pi  
9. Connect device to Wi-Fi and open the LMS  

---

## Offline Content Policy  

All course materials are be stored locally. It do not rely on:

- External CDNs  
- YouTube or Vimeo embeds  
- Cloud file storage  
- External login providers  
- Email-based password reset  

All CSS, JS, fonts, PDFs, and images exist on the Pi.

---

## Use Cases  

- Rural schools without internet  
- Training centers  
- Field education  
- Portable classroom servers  
- Emergency or remote learning  

---

## Advantages  

- Fully offline  
- Low power usage  
- Portable  
- Low-cost hardware  
- Real Linux server environment  
- No dependency on external services  

---

## Limitations  

- Hardware is small, not designed for heavy enterprise workloads  
- SQLite can slow with high simultaneous write operations  
- Large video streaming for many users at once may stress the system  

---

## Maintenance  

### To update content  
- Connect to temporary internet  
- Copy new files to content folder from this GitHub repo  
- Update database if needed  

### To update application  
- Replace code in `/var/www/bridgebox`  
- Re-run Laravel cache commands  

### To back up  
- Copy entire **bridgebox** folder  
- Copy `database.sqlite` file  

---

## Summary  

This project converts a Raspberry Pi into a self-contained offline LMS server.  
It runs Linux, Nginx, PHP-FPM, Laravel, and SQLite.  
Users connect over Wi-Fi and learn without internet.  
The system is optimized for serving PDFs and images efficiently to many local users.

