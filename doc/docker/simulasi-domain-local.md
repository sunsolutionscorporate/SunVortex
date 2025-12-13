Penggunaan cara dan fitur ini hanya di ujicoba pada docker-container, jika menggunakan xampp dan sejenisnya belum dilakukan test lebih lanjut.

# Membuat Simulasi lokal Domain

## Langkah 1 — EDIT FILE hosts (WAJIB)

buka dan edit file pada `C:\Windows\System32\drivers\etc\hosts`
tambahkan

```
127.0.0.1   sunvortex.local
```

dibaris paling bawah dan **simpan**.
ini artinya ketika ada akses ke `sunvortex.local`, maka arahkan ke komputer ini.

## Langkah 2 — BUAT FILE VHOST APACHE

buka folder `sunvortex-docker/docker/apache` lalu buat file `sunvortex.conf` dengan isi:

```apache
<VirtualHost *:80>
    ServerName sunvortex.local
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sunvortex_error.log
    CustomLog ${APACHE_LOG_DIR}/sunvortex_access.log combined
</VirtualHost>

```

## Langkah 3 — UBAH docker-compose.yml

ganti/ubah service **web** menjadi seperti ini:

```yaml
services:
  web:
    image: php:8.2-apache
    ports:
      - "80:80"
    volumes:
      - ./sun:/var/www/html
      - ./docker/apache/sunvortex.conf:/etc/apache2/sites-available/sunvortex.conf
    command: >
      bash -c "
      a2enmod rewrite &&
      a2ensite sunvortex.conf &&
      a2dissite 000-default.conf &&
      docker-php-ext-install pdo pdo_mysql &&
      apache2-foreground
      "
```

## LANGKAH 4 — RESTART DOCKER (WAJIB)

```bash
docker compose down
docker compose up -d --build
```

## LANGKAH 5 — TES

buka browser dan jalankan url:

```bash
http://sunvortex.local
```

---
