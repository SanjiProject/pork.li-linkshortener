# 🐷 Pork.li

**Smart URL shortener with intelligent link rotation — shorten once, rotate forever!**

Share clean links that intelligently distribute traffic across multiple destinations. Perfect for A/B testing, load balancing, and marketing campaigns! 🚀🐷

[![Website](https://img.shields.io/badge/Website-pork.li-FF1493?style=for-the-badge)](https://pork.li)
[![Demo](https://img.shields.io/badge/Live_Demo-pork.li-FF1493?style=for-the-badge&logo=globe)](https://pork.li)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php)](https://php.net)

![Pork.li Platform Screenshot](https://pork.li/img/screenshot.png)

## ✨ Features
- 🔗 **Smart URL shortening** with custom codes
- 🔄 **Intelligent link rotation** (Round Robin & Random)
- 📊 **Real-time analytics** with click tracking
- 👤 **User management** with guest & registered modes  
- 🛡️ **Security-first** with CSRF protection & CAPTCHA
- 🎨 **Modern responsive UI** with smooth animations
- ⚡ **Lightning fast** with optimized caching

## 🛠️ Tech Stack
- **Backend**: PHP 8.3+ with PDO
- **Database**: MySQL 8.0+ / MariaDB 10.4+
- **Frontend**: Vanilla JavaScript ES6+ & Modern CSS3
- **Architecture**: Clean MVC with security-first approach

## 📁 Project Structure
```
pork.li/
api/                   # REST endpoints
  create-link.php      # Link creation
  link-analytics.php   # Analytics data
  login.php           # Authentication
config/
  database.php        # Database config
dashboard/            # User interface
admin/               # Admin panel
includes/
  auth.php           # Authentication
  functions.php      # Core functionality
public/              # Assets (CSS, JS)
index.php           # Homepage & router
redirect.php        # URL redirection handler
```

## 🚀 Quick Start

### Requirements
- PHP 8.3+ with PDO MySQL extension
- MySQL/MariaDB 8.0+
- Web server (Nginx/Apache)

### Installation
1. **Clone the repository**
   ```bash
   git clone https://github.com/SanjiProject/pork.li.git
   cd pork.li
   ```

2. **Import the database schema**
   ```bash
   mysql -u username -p porkli < porkli.sql
   ```

3. **Configure database**
   ```php
   // Edit config/database.php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'porkli');
   ```


## ⚙️ Nginx Configuration

```nginx

location /img/ {
    expires 1M;
    add_header Cache-Control "public, immutable";
    add_header Content-Type "image/webp";
    try_files $uri =404;
}

location /public/ {
    expires 1M;
    add_header Cache-Control "public, immutable";
    try_files $uri =404;
}

# Main location block - prioritize index.php
location / {
    index index.php index.html index.htm;
    try_files $uri $uri/ @rewrite;
}

location @rewrite {
    # Handle clean URLs for pages
    rewrite ^/login/?$ /login.php last;
    rewrite ^/register/?$ /register.php last;
    rewrite ^/dashboard/?$ /dashboard/index.php last;
    rewrite ^/admin/?$ /admin/index.php last;
    rewrite ^/settings/?$ /settings/index.php last;
    
    # Handle short links (3-50 characters, alphanumeric, hyphens, underscores)
    rewrite ^/([a-zA-Z0-9_-]{3,50})/?$ /redirect.php?code=$1 last;
    
    # Fallback to 404
    rewrite ^ /404.php last;
}

location ~ ^/(api|public|dashboard|admin|settings|img)/ {
    try_files $uri $uri/ /404.php;
}

location ~ ^/(config|includes)/ {
    deny all;
    return 404;
}

location ~ /\.(env|htaccess|gitignore|ini|log|conf|sql)$ {
    deny all;
    return 404;
}

location ~ \.php$ {
    try_files $uri =404;
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_pass unix:/tmp/php-cgi-74.sock;  # Adjust PHP version as needed
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}

location ~* \.(css)$ {
    expires 1M;
    add_header Cache-Control "public, immutable";
    add_header Content-Type "text/css";
    try_files $uri =404;
}

location ~* \.(js)$ {
    expires 1M;
    add_header Cache-Control "public, immutable";
    add_header Content-Type "application/javascript";
    try_files $uri =404;
}

location ~* \.(webp)$ {
    expires 1M;
    add_header Cache-Control "public, immutable";
    add_header Content-Type "image/webp";
    try_files $uri =404;
}

location ~* \.(png|jpg|jpeg|gif|ico|svg)$ {
    expires 1M;
    add_header Cache-Control "public, immutable";
    try_files $uri =404;
}

add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

```

## 🛡️ Security Features

- **CSRF protection** on all POST routes
- **Password hashing** with PHP's `password_hash()`
- **Input validation** and sanitization
- **Math captcha** for public forms
- **Prepared statements** (SQL injection prevention)

## 📈 SEO & Analytics

- **Auto-generated sitemap** at `/sitemap.xml`
- **Privacy-friendly analytics** (no external tracking)
- **Open Graph meta tags** for social sharing
- **Real-time click tracking** with detailed insights

Try it out: **[pork.li](https://pork.li)**

---

Made with ❤️ by [SanjiProject](https://github.com/SanjiProject)
