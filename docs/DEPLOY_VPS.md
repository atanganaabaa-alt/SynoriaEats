# Déploiement VPS — SynoriaEats

Guide pour mettre SynoriaEats en production sur un VPS Linux (Ubuntu 22.04+).

## Prérequis serveur

- Ubuntu 22.04 / 24.04
- Domaine pointant vers l’IP du VPS
- Accès SSH root ou sudo

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx postgresql postgresql-contrib redis-server \
  php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl \
  unzip git curl
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## Base de données

```bash
sudo -u postgres createuser -P synoria   # mot de passe fort
sudo -u postgres createdb -O synoria synoriaeats
```

## Application

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone git@github.com:atanganaabaa-alt/SynoriaEats.git synoriaeats
sudo chown -R $USER:www-data synoriaeats
cd synoriaeats

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Éditer `.env` :

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ton-domaine.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=synoriaeats
DB_USERNAME=synoria
DB_PASSWORD=...

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

SYNORIA_PAYMENTS_SANDBOX=false
SYNORIA_COMMISSION_RATE=0.10
SYNORIA_NOTIFICATION_CHANNELS=log
# Puis sms,whatsapp ou orange_sms en prod
```

```bash
php artisan migrate --force
php artisan storage:link
npm ci && npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Compte admin (hors inscription publique)
php artisan synoria:admin admin@ton-domaine.com 'MotDePasseFort!' --name="Admin"

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

## Nginx

`/etc/nginx/sites-available/synoriaeats` :

```nginx
server {
    listen 80;
    server_name ton-domaine.com;
    root /var/www/synoriaeats/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/synoriaeats /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d ton-domaine.com
```

## Queue & scheduler (optionnel mais recommandé)

```bash
# /etc/systemd/system/synoriaeats-worker.service
# puis: sudo systemctl enable --now synoriaeats-worker
```

Cron :

```cron
* * * * * www-data cd /var/www/synoriaeats && php artisan schedule:run >> /dev/null 2>&1
```

## Mises à jour

```bash
cd /var/www/synoriaeats
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl reload php8.3-fpm
```

## Checklist prod

- [ ] `APP_DEBUG=false`
- [ ] PostgreSQL + backups
- [ ] HTTPS (Let’s Encrypt)
- [ ] Google OAuth redirect URI prod
- [ ] Paiements sandbox `false` + clés Orange/MTN
- [ ] Notifications Twilio / Orange SMS configurées
- [ ] Compte admin créé via `synoria:admin`
