<?php

// CSRF = Cross-Site Request Forgery
// (Pemalsuan Permintaan Lintas Situs)
// CSRF adalah jenis serangan di mana orang lain membuat browser korban mengirim request ke server tanpa sepengetahuan korban,
// menggunakan cookie / session korban yang masih aktif.
// Dengan kata lain:
// Penyerang memancing user membuka link / gambar / script, lalu browser user melakukan request ke situsmu tanpa izin.


class Csrf_old
{
   private static $token = null;
   private static $instance = null;
   private function __construct() {}
   private static function refresh_cookie()
   {
      self::$token = self::token_encode();
      setcookie('csrf_token', self::$token, [
         'path' => '/',
         'httponly' => false, // harus false agar JS bisa baca untuk AJAX
         'samesite' => 'Strict',
         'secure' => isset($_SERVER['HTTPS']),
         'expires' => time() + 1800 // 30 menit
      ]);
   }
   public static function init()
   {
      if (self::$instance === null) {
         self::$instance = new Csrf();
      }
      if (empty($_COOKIE['csrf_token'])) {
         self::refresh_cookie();
      } else {
         self::$token = $_COOKIE['csrf_token'];
      }
      return self::$instance;
   }


   private static function token_encode()
   {
      $secret = config('SECRET_KEY');
      $rand   = bin2hex(random_bytes(16));
      $time   = time() + (60 * 30); // expired 30 menit
      $payload = json_encode([
         'r' => $rand,
         'e' => $time
      ]);

      $payload_b64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
      $sign = hash_hmac('sha256', $payload_b64, $secret);

      return $payload_b64 . '.' . $sign;
   }

   private static function token_decode($token)
   {
      $secret = config('SECRET_KEY');

      if (!str_contains($token, '.')) {
         return false;
      }

      list($payload_b64, $sign) = explode('.', $token, 2);
      $expected = hash_hmac('sha256', $payload_b64, $secret);

      if (!hash_equals($expected, $sign)) {
         return false;
      }

      $payload_json = base64_decode(strtr($payload_b64, '-_', '+/'));
      $payload = json_decode($payload_json, true);

      if (!$payload) return false;

      // cek expired
      if (time() > $payload['e']) {
         // slog('kadaluarsa');
         return false;
      }

      $remaining = $payload['e'] - time();
      $refreshThreshold = 300; // 5 menit
      if ($remaining < $refreshThreshold) {
         self::refresh_cookie();
      }
      // slog('csrf:', $remaining);
      return true;
   }


   public static function decode()
   {
      // ambil token dari cookie
      $cookieToken = $_COOKIE['csrf_token'] ?? null;

      // ambil token dari header atau form
      $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
      $formToken = $_POST['csrf'] ?? null;
      $sent = $headerToken ?: $formToken;
      if (!$cookieToken) {
         return "Missing CSRF key: expected csrf_token cookie.";
      }

      if (!$sent) {
         return "Missing CSRF token (header/form)";
      }

      // validasi token (signed stateless)
      if (!self::token_decode($sent)) {
         return "Invalid CSRF token";
      }

      // validasi: token harus sama dengan cookie (Double Submit)
      if ($cookieToken !== $sent) {
         return "CSRF mismatch: submitted token does not match session token.";
      }
      // self::refresh_cookie();
      return 'ok';
   }

   public static function token()
   {
      return self::$token;
   }
};

class Csrf_Ms {};
