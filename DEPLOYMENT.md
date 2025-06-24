# 🚀 Guía de Deployment - Vive Hidalgo

Esta guía te ayudará a desplegar la API de Vive Hidalgo en diferentes entornos de producción.

## 📋 Tabla de Contenidos

- [Requisitos del Servidor](#-requisitos-del-servidor)
- [Preparación del Proyecto](#-preparación-del-proyecto)
- [Deployment en VPS](#-deployment-en-vps)
- [Deployment en Heroku](#-deployment-en-heroku)
- [Deployment en DigitalOcean](#-deployment-en-digitalocean)
- [Deployment con Docker](#-deployment-con-docker)
- [Configuración de SSL](#-configuración-de-ssl)
- [Monitoreo y Logs](#-monitoreo-y-logs)
- [Backup y Recuperación](#-backup-y-recuperación)

## 🖥️ Requisitos del Servidor

### Mínimos
- **PHP**: 8.2+
- **MySQL**: 8.0+ o PostgreSQL 13+
- **Redis**: 6.0+ (opcional, para cache)
- **Composer**: 2.0+
- **Node.js**: 18+ (para compilación de assets)

### Recomendados
- **CPU**: 2 cores mínimo, 4+ recomendado
- **RAM**: 4GB mínimo, 8GB+ recomendado
- **Storage**: 20GB SSD mínimo
- **Bandwidth**: 1TB/mes mínimo

## 🔧 Preparación del Proyecto

### 1. Optimizar para Producción

```bash
# Instalar dependencias optimizadas
composer install --optimize-autoloader --no-dev

# Compilar assets
npm run build

# Cache de configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Crear enlace de storage
php artisan storage:link
```

### 2. Configurar Variables de Entorno

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vive_hidalgo
DB_USERNAME=usuario_db
DB_PASSWORD=password_seguro

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_usuario
MAIL_PASSWORD=tu_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@vivehidalgo.com"
MAIL_FROM_NAME="${APP_NAME}"

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=tu_api_key
```

## 🖥️ Deployment en VPS

### 1. Configurar Servidor Ubuntu/Debian

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependencias
sudo apt install nginx mysql-server php8.2-fpm php8.2-mysql php8.2-redis php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd php8.2-bcmath composer redis-server git unzip -y

# Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### 2. Configurar MySQL

```bash
sudo mysql_secure_installation

# Crear base de datos
sudo mysql -u root -p
CREATE DATABASE vive_hidalgo;
CREATE USER 'vive_user'@'localhost' IDENTIFIED BY 'password_seguro';
GRANT ALL PRIVILEGES ON vive_hidalgo.* TO 'vive_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Configurar Nginx

```nginx
# /etc/nginx/sites-available/vive-hidalgo
server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/vive-hidalgo/public;

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
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 4. Desplegar Aplicación

```bash
# Clonar proyecto
sudo git clone https://github.com/tu-usuario/vive-hidalgo-backend.git /var/www/vive-hidalgo
cd /var/www/vive-hidalgo

# Configurar permisos
sudo chown -R www-data:www-data /var/www/vive-hidalgo
sudo chmod -R 755 /var/www/vive-hidalgo
sudo chmod -R 775 storage bootstrap/cache

# Instalar dependencias
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Configurar aplicación
cp .env.example .env
php artisan key:generate
# Editar .env con credenciales de producción

# Ejecutar migraciones
php artisan migrate --force
php artisan db:seed --force

# Optimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link

# Habilitar sitio
sudo ln -s /etc/nginx/sites-available/vive-hidalgo /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. Configurar Supervisor para Colas

```ini
# /etc/supervisor/conf.d/vive-hidalgo.conf
[program:vive-hidalgo-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vive-hidalgo/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/vive-hidalgo/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start vive-hidalgo-worker:*
```

### 6. Configurar Cron

```bash
# Agregar al crontab
crontab -e

# Agregar esta línea:
* * * * * cd /var/www/vive-hidalgo && php artisan schedule:run >> /dev/null 2>&1
```

## ☁️ Deployment en Heroku

### 1. Configurar Heroku

```bash
# Instalar Heroku CLI
curl https://cli-assets.heroku.com/install.sh | sh

# Login
heroku login

# Crear app
heroku create vive-hidalgo-api

# Agregar add-ons
heroku addons:create heroku-postgresql:mini
heroku addons:create heroku-redis:mini
heroku addons:create sendgrid:starter
```

### 2. Configurar Variables

```bash
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
heroku config:set APP_KEY=$(php artisan key:generate --show)
heroku config:set CACHE_DRIVER=redis
heroku config:set QUEUE_CONNECTION=redis
heroku config:set SESSION_DRIVER=redis
```

### 3. Desplegar

```bash
# Agregar Procfile
echo "web: vendor/bin/heroku-php-apache2 public/" > Procfile

# Commit y push
git add .
git commit -m "Heroku deployment"
git push heroku main

# Ejecutar migraciones
heroku run php artisan migrate --force
heroku run php artisan db:seed --force
```

## 🐳 Deployment con Docker

### 1. Dockerfile

```dockerfile
FROM php:8.2-fpm

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Instalar extensiones PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar archivos del proyecto
COPY . /var/www

# Instalar dependencias
RUN composer install --optimize-autoloader --no-dev

# Configurar permisos
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage

# Exponer puerto
EXPOSE 9000

CMD ["php-fpm"]
```

### 2. Docker Compose

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: vive-hidalgo-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - vive-hidalgo

  webserver:
    image: nginx:alpine
    container_name: vive-hidalgo-nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/conf.d/:/etc/nginx/conf.d/
    networks:
      - vive-hidalgo

  db:
    image: mysql:8.0
    container_name: vive-hidalgo-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: vive_hidalgo
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_PASSWORD: password
      MYSQL_USER: vive_user
    volumes:
      - dbdata:/var/lib/mysql
    networks:
      - vive-hidalgo

  redis:
    image: redis:alpine
    container_name: vive-hidalgo-redis
    restart: unless-stopped
    networks:
      - vive-hidalgo

networks:
  vive-hidalgo:
    driver: bridge

volumes:
  dbdata:
    driver: local
```

### 3. Desplegar con Docker

```bash
# Construir y ejecutar
docker-compose up -d

# Ejecutar migraciones
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --force
```

## 🔒 Configuración de SSL

### Con Let's Encrypt

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d tu-dominio.com

# Renovar automáticamente
sudo crontab -e
# Agregar: 0 12 * * * /usr/bin/certbot renew --quiet
```

## 📊 Monitoreo y Logs

### 1. Configurar Logs

```bash
# Configurar logrotate
sudo nano /etc/logrotate.d/vive-hidalgo

/var/www/vive-hidalgo/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    notifempty
    create 644 www-data www-data
}
```

### 2. Monitoreo con New Relic (opcional)

```bash
# Instalar agente New Relic
curl -L https://download.newrelic.com/php_agent/release/newrelic-php5-{VERSION}.tar.gz | tar -C /tmp -zx
cd /tmp/newrelic-php5-{VERSION}
sudo ./newrelic-install install
```

## 💾 Backup y Recuperación

### 1. Script de Backup

```bash
#!/bin/bash
# /var/www/vive-hidalgo/backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/vive-hidalgo"
DB_NAME="vive_hidalgo"

# Crear directorio de backup
mkdir -p $BACKUP_DIR

# Backup de base de datos
mysqldump -u root -p $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# Backup de archivos
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/vive-hidalgo

# Limpiar backups antiguos (mantener últimos 7 días)
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completado: $DATE"
```

### 2. Configurar Backup Automático

```bash
# Agregar al crontab
0 2 * * * /var/www/vive-hidalgo/backup.sh >> /var/log/backup.log 2>&1
```

## 🔧 Comandos de Mantenimiento

```bash
# Optimizar base de datos
php artisan app:optimize-database

# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Verificar estado
php artisan about
php artisan route:list

# Monitorear colas
php artisan queue:monitor
```

## 📞 Soporte

Para problemas de deployment:
- Revisar logs: `tail -f storage/logs/laravel.log`
- Verificar permisos: `ls -la storage/`
- Comprobar servicios: `sudo systemctl status nginx php8.2-fpm mysql redis`

---

**¡Tu API de Vive Hidalgo está lista para producción! 🚀** 