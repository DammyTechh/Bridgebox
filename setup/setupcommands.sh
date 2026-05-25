# Step 1. Install only what you need
sudo apt update
sudo apt -y install nginx php-fpm php-sqlite3 php-xml php-mbstring php-curl php-zip php-gd unzip php-opcache
sudo systemctl enable nginx

# Step 2. Move your project to the Pi
sudo mkdir -p /var/www/bridgebox
sudo chown -R $USER:$USER /var/www/bridgebox
git clone https://github.com/Cyberghost5/bridgebox.git /var/www/bridgebox

# Step 3. Prepare SQLite
cd /var/www/bridgebox/database
touch database.sqlite
sudo chown -R www-data:www-data /var/www/bridgebox/storage /var/www/bridgebox/bootstrap/cache /var/www/bridgebox/database
sudo chmod -R 775 /var/www/bridgebox/storage /var/www/bridgebox/bootstrap/cache /var/www/bridgebox/database

# Step 4. Configure Laravel for SQLite
nano /var/www/bridgebox/.env

APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.4.1

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/bridgebox/database/database.sqlite

cd /var/www/bridgebox

# Install composer if not present
if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# Run composer install
composer install --no-dev --optimize-autoloader

php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 5. Nginx config for bridgebox
sudo nano /etc/nginx/sites-available/bridgebox

server {
    listen 80;
    server_name _;

    root /var/www/bridgebox/public;
    index index.php index.html;

    client_max_body_size 200M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~* \.(jpg|jpeg|png|gif|webp|svg|css|js|pdf)$ {
        expires 30d;
        access_log off;
        try_files $uri =404;
    }
}

sudo rm -f /etc/nginx/sites-enabled/default
sudo ln -s /etc/nginx/sites-available/bridgebox /etc/nginx/sites-enabled/bridgebox
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php8.4-fpm

php artisan config:cache

sudo reboot



