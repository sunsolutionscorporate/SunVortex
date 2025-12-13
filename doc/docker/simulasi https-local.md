Penggunaan cara dan fitur ini hanya di ujicoba pada docker-container, jika menggunakan xampp dan sejenisnya belum dilakukan test lebih lanjut.

# Membuat Simulasi HTTPS lokal

## LANGKAH 1 — INSTALL mkcert (WAJIB)

`mkcert` = tool kecil buat bikin SSL lokal.

**download**
`mkcert-v1.4.4-windows-amd64.exe` pada link [https://github.com/FiloSottile/mkcert/releases](https://github.com/FiloSottile/mkcert/releases) rename menjadi: `mkcert.exe` dan letakkan hasil download ke folder (contoh):

```makefile
C:\mkcert\
```

Tambahkan ke PATH Windows

dan coba cek dengan perintah pada powerSher/cmd

```bash
mkcert --version
```

jika berhasil mendapatkan versinya. lanjut ke langkah selanjutnya.

## LANGKAH 2 — AKTIFKAN LOCAL CA

Buka PowerShell (Run as Administrator)

```bash
mkcert -install
```

## LANGKAH 3 — BUAT SERTIFIKAT UNTUK cbn.local

masuk ke folder project:

```makefile
E:\project-web\sunvortex-docker\docker\apache
```

dan jalankan perintah:

```bash
mkcert cbn.local
```

jika terdapat pesan konfirmasi pilih `Yes`

dan mendapatkan hasil file:

```lua
cbn.local.pem
cbn.local-key.pem
```

## LANGKAH 4 — UPDATE VHOST APACHE (HTTPS)

Edit file `docker/apache/sunvortex.conf` dan ubah isinya:

```apache
<VirtualHost *:80>
    ServerName cbn.local
    Redirect permanent / https://cbn.local/
</VirtualHost>

<VirtualHost *:443>
    ServerName cbn.local
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /etc/apache2/ssl/cbn.local.pem
    SSLCertificateKeyFile /etc/apache2/ssl/cbn.local-key.pem
</VirtualHost>
```

## LANGKAH 5 — UPDATE docker-compose.yml

Tambahkan:

1. port 443
2. volume SSL
3. enable module ssl

Service **web** menjadi seperti ini:

```yaml
services:
  web:
    image: php:8.2-apache
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./sun:/var/www/html
      - ./docker/apache/sunvortex.conf:/etc/apache2/sites-available/sunvortex.conf
      - ./docker/apache:/etc/apache2/ssl
    command: >
      bash -c "
      a2enmod rewrite ssl &&
      a2ensite sunvortex.conf &&
      a2dissite 000-default.conf &&
      docker-php-ext-install pdo pdo_mysql &&
      apache2-foreground
      "
```

## LANGKAH 6 — RESTART DOCKER

```bash
docker compose down
docker compose up -d --build
```

# LANGKAH 7 — TEST

buka browser dan buka link:

```
https://cbn.local
```
