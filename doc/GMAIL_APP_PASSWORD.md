# Panduan Lengkap: Cara Mendapatkan **App Password Gmail**

App Password (Sandi Aplikasi) adalah password 16-digit yang diberikan oleh Google untuk mengizinkan aplikasi pihak ketiga (seperti PHPMailer, SunVortex Mailer, atau aplikasi SMTP lain) mengakses akun Gmail Anda secara aman **tanpa harus menggunakan password akun utama**.

Dokumen ini menjelaskan langkah-langkah lengkap, rinci, dan profesional untuk mendapatkan App Password Gmail.

---

## 1. Persyaratan Sebelum Membuat App Password

Sebelum Anda dapat membuat App Password, pastikan:

1. **Akun Gmail Anda menggunakan verifikasi 2 langkah (2-Step Verification)**.
2. Anda **sudah login** ke akun Google.
3. Anda **tidak menggunakan akun Google Workspace** yang membatasi fitur tertentu (tanyakan admin jika perlu).

Jika 2FA belum aktif, Anda **wajib** mengaktifkannya terlebih dahulu.

---

## 2. Mengaktifkan Verifikasi 2 Langkah (Jika Belum Aktif)

1. Buka halaman keamanan Google:

   - [https://myaccount.google.com/security](https://myaccount.google.com/security)

2. Temukan bagian **"Signing in to Google" / "Cara Anda login ke Google"**.

3. Klik **"2-Step Verification" / "Verifikasi 2 Langkah"**.

4. Tekan **"Get Started" / "Mulai"**.

5. Login ulang jika diminta.

6. Pilih metode 2FA:

   - Google Prompt (Rekomendasi)
   - SMS / Telepon
   - Security Key (Opsional)

7. Ikuti instruksi sampai 2FA berhasil diaktifkan.

Setelah 2FA aktif, Anda dapat membuat App Password.

---

## 3. Membuat App Password

1. Buka halaman **App Passwords** Google:

   - [https://myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)

2. Login jika diminta.

3. Pada halaman App Password, Anda akan melihat formulir:

   - **"Select app" / Pilih aplikasi**
   - **"Select device" / Pilih perangkat**

4. Klik **Select app → Other (Custom name)**.

5. Masukkan nama aplikasi atau sistem Anda, misalnya:

   - `SunVortex Mailer`
   - `PHPMailer SMTP`
   - `My PHP App`

6. Klik **Generate / Buat**.

7. Google akan menampilkan **App Password 16 digit**, contoh:

   `abcd efgh ijkl mnop`

8. **Copy App Password tersebut** dan simpan pada file .env Anda, misalnya:

```
SMTP_PASSWORD=abcd efgh ijkl mnop
```

> **Catatan penting:** Anda hanya bisa melihat password ini **sekali** saat pertama kali dibuat. Jika hilang, hapus dan buat yang baru.

---

## 4. Tips Keamanan

- Jangan pernah membagikan App Password ke siapa pun.
- Simpan password hanya di file konfigurasi yang tidak dipublikasi, seperti `.env`.
- Jika Anda curiga kredensial bocor, **hapus App Password** dari halaman App Passwords, lalu buat yang baru.
- Jangan gunakan App Password yang sama untuk beberapa aplikasi.

---

## 5. Menghapus atau Mengelola App Password

Jika Anda ingin mencabut akses aplikasi:

1. Buka kembali halaman App Passwords:

   - [https://myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)

2. Temukan App Password yang ingin dihapus pada daftar.

3. Klik ikon **Delete (✖)** untuk mencabut akses.

Aplikasi yang menggunakan password tersebut tidak akan bisa login lagi.

---

## 6. Referensi dan Dukungan

- Dokumentasi resmi Google App Passwords: [https://support.google.com/accounts/answer/185833](https://support.google.com/accounts/answer/185833)
- Dokumentasi keamanan akun Google: [https://myaccount.google.com/security](https://myaccount.google.com/security)

---

## 7. Kesimpulan

App Password adalah cara yang aman dan terkontrol untuk memberikan akses SMTP kepada aplikasi pihak ketiga tanpa mengekspos password utama Gmail Anda. Dengan App Password, koneksi mailer Anda menjadi lebih stabil, aman, dan sesuai standar Google.

letakkan App Password Anda di bagian `.env` seperti:

```
SMTP_PASSWORD=abcd efgh ijkl mnop
```

Framework akan menggunakan kredensial tersebut secara otomatis saat melakukan autentikasi SMTP.

---

Jika Anda ingin, saya bisa buatkan versi PDF, HTML, atau menyesuaikan gaya penulisan sesuai branding framework SunVortex Anda.
