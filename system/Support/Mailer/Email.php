<?php
require_once __DIR__ . "/Mailer.php";

/**
 * Class Email
 *
 * A lightweight wrapper around PHPMailer, providing a simplified and
 * framework-integrated API for sending emails within the SunVortex ecosystem.
 *
 * This class automatically configures the underlying mail transport based on
 * environment settings (.env), supporting multiple drivers such as SMTP,
 * Sendmail, Qmail, and PHP's native mail().
 *
 * It also streamlines common tasks such as:
 * - Setting sender and reply-to information
 * - Handling HTML and plain-text email bodies
 * - Applying encryption and timeout settings
 * - Managing CC/BCC recipients via a simplified `notify()` method
 * - Automatically resetting the mail instance after sending
 *
 * Typical usage:
 *
 *    $mail = new Email([
 *        'subject' => 'Welcome!',
 *        'content' => '<h1>Hello World</h1>',
 *        'alt'     => 'Hello World'
 *    ]);
 *
 *    $mail->notify('cc@example.com', 'John Doe', Email::TYPE_PUBLIC);
 *    $mail->send('destination@example.com');
 *
 * This class is intended to provide a clean, developer-friendly interface for
 * sending emails while keeping compatibility with advanced PHPMailer features.
 *
 * @package SunVortex\Mail
 */
class Email extends Mailer
{
   /**
    * Tipe notifikasi penerima untuk CC (Carbon Copy).
    * Penerima akan terlihat oleh semua penerima lain.
    */
   const TYPE_PUBLIC = "cc";

   /**
    * Tipe notifikasi penerima untuk BCC (Blind Carbon Copy).
    * Penerima tersembunyi dan tidak akan terlihat oleh penerima lain.
    */
   const TYPE_HIDE = "bcc";

   /**
    * Membuat instance Email baru dan mengonfigurasi PHPMailer berdasarkan
    * pengaturan yang ada pada file .env framework SunVortex.
    *
    * Parameter $content dapat berupa:
    * - string  → isi email (HTML atau teks biasa)
    * - array   → [
    *       'subject' => 'Judul Email',
    *       'content' => '<h1>Isi Email</h1>',
    *       'alt'     => 'Isi alternatif'
    *   ]
    *
    * Konstruktor ini secara otomatis:
    * - Memilih driver (SMTP, sendmail, qmail, atau mail)
    * - Mengatur enkripsi, port, dan otentikasi SMTP
    * - Mengatur pengirim dan alamat balasan (reply-to)
    * - Mendeteksi apakah isi email berupa HTML
    *
    * @param string|array $content Isi email atau konfigurasi email.
    */
   public function __construct($content)
   {
      parent::__construct(true);
      switch (config('MAIL_DRIVER')) {
         case 'smtp':
            $this->isSMTP();
            $this->Host       = config('SMTP_HOST');
            $this->SMTPAuth   = true;
            $this->Username   = config('SMTP_USERNAME');
            $this->Password   = config('SMTP_PASSWORD');
            $this->Port       = config('SMTP_PORT');

            // ENCRYPTION HANDLER
            $this->SMTPSecure = strtolower(config('SMTP_ENCRYPTION'));
            break;

         case 'sendmail':
            $this->isSendmail();
            break;

         case 'qmail':
            $this->isQmail();
            break;

         default:
            $this->isMail();
            break;
      }

      $this->Timeout = config('MAIL_TIMEOUT', 30);
      $this->setFrom(config('MAIL_ADDRESS'), config('MAIL_NAME'));

      $this->addReplyTo(
         config('MAIL_REPLYTO_ADDRESS', 'no-reply@example.com'),
         config('MAIL_REPLYTO_NAME', 'No Reply')
      );

      $this->Sender = config('MAIL_SENDER', 'no-reply@example.com');

      // BODY SETUP
      $body = "";
      if (is_array($content)) {
         $body = $content['content'] ?? '';
         $this->Subject = $content['subject'] ?? '';
         $this->AltBody = $content['alt'] ?? '';
      } else {
         $body = (string)$content;
      }

      if (isHtml($body)) {
         $this->isHTML(true);
      }

      $this->Body = $body;
   }

   /**
    * Menambahkan penerima CC atau BCC pada email.
    *
    * @param string $address  Alamat email tujuan.
    * @param string $name     Nama penerima (opsional).
    * @param string $type     Jenis notifikasi: 
    *                         - Email::TYPE_PUBLIC → CC
    *                         - Email::TYPE_HIDE   → BCC
    *
    * Contoh:
    *    $mail->notify('example@test.com', 'John Doe', Email::TYPE_PUBLIC);
    *
    * @return static Mengembalikan instance Email agar dapat digunakan secara chaining.
    */
   public function notify(string $address, string $name = '', string $type = 'cc')
   {
      if ($type === self::TYPE_PUBLIC) {
         $this->addCC($address, $name);
      } elseif ($type === self::TYPE_HIDE) {
         $this->addBCC($address, $name);
      } else throw new \InvalidArgumentException("Invalid notify type: $type");

      return $this;
   }

   /**
    * Mengirim email ke alamat tujuan.
    *
    * Metode ini:
    * - Menambahkan alamat tujuan utama (To)
    * - Menjalankan proses pengiriman melalui PHPMailer
    * - Membersihkan seluruh penerima setelah pengiriman agar instance dapat dipakai kembali
    *
    * Jika terjadi error, metode ini menanganinya secara aman dan mengembalikan false.
    *
    * @param string $destination Alamat email tujuan utama.
    *
    * @return bool True jika pengiriman berhasil, False jika gagal.
    */
   public function send(string $destination): bool
   {
      try {
         $this->addAddress($destination);
         $out = parent::send('');

         $this->clearAllRecipients();
         $this->clearAttachments();
         $this->clearCustomHeaders();

         return $out;
      } catch (\Throwable $e) {
         return false;
      }
   }
}
