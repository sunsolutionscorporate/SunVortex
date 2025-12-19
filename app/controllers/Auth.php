<?php

class Auth extends Controller
{
   /**
    * Generate OTP beserta token terenkripsi
    *
    * @param int $length Panjang OTP (default 6 digit)
    * @param int $ttl Masa berlaku dalam detik (default 900 = 15 menit)
    * @return array ['otp' => '123456', 'token' => 'xxxxx']
    */
   private function generate_otp(int $length = 6, int $ttl = 900): array
   {
      $otp = '';
      for ($i = 0; $i < $length; $i++) {
         $otp .= random_int(0, 9);
      }

      $expires = time() + $ttl;

      // Simpan hash OTP + waktu kadaluarsa
      $payload = json_encode([
         'otp' => hash('sha256', $otp),
         'exp' => $expires
      ]);

      // Enkripsi payload menjadi token (AES-256)
      $iv = random_bytes(16);
      $encrypted = openssl_encrypt($payload, 'AES-256-CBC', config('SECRET_KEY'), 0, $iv);

      // Token base64: IV + encrypted
      $token = base64_encode($iv . $encrypted);

      return [
         'otp' => $otp,
         'token' => $token,
         'expires' => $expires
      ];
   }

   /**
    * Verifikasi OTP dari client berdasarkan token terenkripsi
    *
    * @param string $otp_input OTP dari user
    * @param string $token Token hasil generate_otp_token()
    * @return array ['valid' => bool, 'message' => string]
    */
   function verify_otp(string $otp_input, string $token): array
   {
      $decoded = base64_decode($token);
      if ($decoded === false || strlen($decoded) <= 16) {
         return ['valid' => false, 'message' => 'Token tidak valid', 'code' => 401];
      }

      $iv = substr($decoded, 0, 16);
      $encrypted = substr($decoded, 16);

      $payload = openssl_decrypt($encrypted, 'AES-256-CBC', config('SECRET_KEY'), 0, $iv);
      if ($payload === false) {
         return ['valid' => false, 'message' => 'Token gagal didekripsi', 'code' => 400];
      }

      $data = json_decode($payload, true);
      if (!$data || !isset($data['otp'], $data['exp'])) {
         return ['valid' => false, 'message' => 'Token rusak', 'code' => 400];
      }

      // Cek kedaluwarsa
      if (time() > $data['exp']) {
         return ['valid' => false, 'message' => 'Kode OTP telah kedaluwarsa', 'code' => 410];
      }

      // Cocokkan OTP (dihash agar aman)
      if (hash('sha256', $otp_input) !== $data['otp']) {
         return ['valid' => false, 'message' => 'Kode OTP salah', 'code' => 401];
      }

      return ['valid' => true, 'message' => 'Verifikasi berhasil', 'code' => 200];
   }

   // private $client;
   public function __construct()
   {
      parent::__construct();
      model('user/user_model', 'user');
      //    $this->client = new Google_Client();
      //    $this->client->setClientId(config('GOOGLE_CLIENT_ID'));
      //    $this->client->setClientSecret(config('GOOGLE_CLIENT_SECRET'));
      //    $this->client->setRedirectUri("http://localhost/Auth/google");
      //    $this->client->addScope('email');
      //    $this->client->addScope('profile');
   }

   public function google()
   {
      $accessToken = $this->request->post('access_token');

      $googlClient = new Google_Client();
      $googlClient->setClientId(config('GOOGLE_CLIENT_ID'));
      // $googlClient->setClientSecret(config('GOOGLE_CLIENT_SECRET'));
      $googlClient->setAccessToken($accessToken);
      $oauth = new Google_Service_Oauth2($googlClient);
      $userInfo = $oauth->userinfo->get();

      // cek id user di table users database
      $user = $this->user->find($userInfo->id);
      if ($user) {
         return [];
      } else {
         // buat user baru
         // $this->user->create([
         //    'name' => $userInfo->name,
         //    'email' => $userInfo->email,
         //    'uid' => $userInfo->id,
         //    'avatar' => $userInfo->picture,
         //    'givenName' => $userInfo->givenName,
         //    'familyName' => $userInfo->familyName,
         // ]);

         $otp = $this->generate_otp(6);
         $payload = [
            'name' => $userInfo->name,
            'email' => $userInfo->email,
            'uid' => $userInfo->id,
            'avatar' => $userInfo->picture,
            'givenName' => $userInfo->givenName,
            'familyName' => $userInfo->familyName,
            'otp' => $otp['otp'],
            'token' => $otp['token'],
            'expires' => $otp['expires']
         ];

         email([
            'content' => view('auth/otp', $payload),
            'subject' => 'OTP Email - CBNLink',
            // 'altBody' => 'Halo, ini email dari PHPMailer!',
         ])
            ->notify('kampungcabang6@gmail.com', 'Kepala Kampung', Email::TYPE_PUBLIC)
            ->send($userInfo->email);
         return view('auth/verify', $payload);
      }

      // // Buat JWT
      // $payload = [
      //    'name' => $userInfo->name,
      //    'email' => $userInfo->email,
      //    'iat' => time(),
      //    'exp' => time() + config('TOKEN_EXP', 86400),
      // ];

      // $jwt = JWT::encode($payload, config('SECRET_KEY'), config('ALGORITHM'));
      return $userInfo;
   }

   public function verify()
   {
      $otp = $this->verify_otp($this->request->post('otp'), $this->request->post('token'));

      return $otp;
   }

   public function index()
   {
      return view('auth/login');
   }
   public function jajal()
   {
      $outp = $this->user->find(1);

      slog($outp);
   }
}
