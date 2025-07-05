
# GDP Laravel Start – Template base per progetti Laravel gestionali

This repository is a starting template for Laravel projects created by **Generazione Digitale**.  
It is pre-configured with:

- Laravel 11 + Breeze (basic authentication)
- Bootstrap 5 + FontAwesome (via Vite)
- Vite for asset bundling and SCSS
- Spatie Laravel Permissions for role management
- Authenticated area routing under `/admin`
- Optional deployment to a subfolder with `Route::prefix()`

---

## 📁 Project Architecture

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/                 ← Breeze Controllers
│   │   └── Admin/               ← Authenticated area Controllers
│
resources/
└── views/
    ├── admin/                   ← Views for logged-in users
    │   └── dashboard.blade.php
    └── auth/                    ← Breeze Views (login, register, etc.)
```

---

## 🧩 Adding a new Entity (e.g. `Example`)

### 1. Create the model, migration, seeder and factory

```bash
php artisan make:model Example -a
```

### 2. Replace the controller

```bash
php artisan make:controller Admin/ExampleController -r --model=Example
```

> You may delete the automatically generated controller in the main Controllers folder.

### 3. Register the routes in `routes/web.php`

Under the `/admin` group:

```php
use App\Http\Controllers\Admin\ExampleController;

Route::resource('examples', ExampleController::class);
```

> Ensure the controller is imported at the top.

### 4. Edit the migration and run:

```bash
php artisan migrate
```

### 5. Fill the seeder (optional)

```php
use Faker\Generator as Faker;

public function run(Faker $faker): void {
    \App\Models\Example::factory()->count(10)->create();
}
```

```bash
php artisan db:seed --class=ExampleSeeder
```

### 6. Create views

Add: `resources/views/admin/examples/index.blade.php`

### 7. Modify the controller

```php
public function index()
{
    $examples = Example::all();
    return view('admin.examples.index', compact('examples'));
}
```

---

## 🔐 Role Management (Spatie)

Install (if not yet done):

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --tag="permission-config"
php artisan vendor:publish --tag="permission-migrations"
php artisan migrate
```

In `User.php`:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

Usage:

```php
$user->assignRole('admin');
$user->hasRole('staff');
```

---

## ⚙️ Project Configuration Notes

- `APP_NAME` is read from `.env`
- `favicon.ico` must be in `public/`
- Navbar logo is located at `public/img/nav-logo.png`
- All post-login pages live under `/admin`

---

## 🌍 Subfolder Hosting – Route Prefixing

If deploying under a subfolder like:

```
https://generazionedigitaleprogrammi.com/gdp-template/
```

You must wrap all routes in `routes/web.php` inside:

```php
Route::prefix('gdp-template')->group(function () {
    // your routes here (including /admin group, auth, etc.)
});
```

This ensures paths are correctly resolved when Laravel is hosted under a non-root folder.

---

## 📦 First Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
npm run dev
php artisan serve
```

---

## 🚀 Deployment Guide (NGINX + VPS)

### 1. SSH into your server

```bash
ssh root@your-server-ip
```

### 2. Prepare project folder

```bash
cd /var/www/
mkdir -p generazionedigitaleprogrammi/gdp-template
```

Deploy your Laravel app inside this directory.

### 3. NGINX Config

Create `/etc/nginx/sites-available/gdp-template.com` with:

```nginx
server {
    listen 443 ssl;
    server_name gdp-template.generazionedigitaleprogrammi.com;

    root /var/www/generazionedigitaleprogrammi/gdp-template/public;
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/gdp-template.generazionedigitaleprogrammi.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/gdp-template.generazionedigitaleprogrammi.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    access_log /var/log/nginx/gdp-template_access.log;
    error_log  /var/log/nginx/gdp-template_error.log;
}

server {
    listen 80;
    server_name gdp-template.generazionedigitaleprogrammi.com;
    return 301 https://$host$request_uri;
}
```

Enable the site:

```bash
ln -s /etc/nginx/sites-available/gdp-template.com /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

Generate SSL:

```bash
apt install certbot python3-certbot-nginx
certbot --nginx -d gdp-template.generazionedigitaleprogrammi.com
```

### 4. Laravel Post Deploy

```bash
cd /var/www/generazionedigitaleprogrammi/gdp-template
cp .env.example .env
nano .env  # update DB, APP_URL, VITE_APP_BASE
php artisan key:generate
composer install
npm install && npm run build
php artisan migrate --seed
php artisan config:cache
php artisan route:cache
```

---

## 📄 Final Notes

- Always set `APP_URL` and `VITE_APP_BASE` properly for subfolder hosting.
- `public/` is the correct root for NGINX.
- Laravel logs: `storage/logs/`
- Set ownership: `chown -R www-data:www-data .`
