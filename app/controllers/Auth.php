<?php

class Auth extends Controller
{
   private $client;
   public function __construct()
   {
      parent::__construct();
      $this->client = new Google_Client();
      $this->client->setClientId(config('GOOGLE_CLIENT_ID'));
      $this->client->setClientSecret(config('GOOGLE_CLIENT_SECRET'));
      $this->client->setRedirectUri("http://localhost/Auth/google");
      $this->client->addScope('email');
      $this->client->addScope('profile');
   }

   public function google()
   {
      if (!isset($_GET['code'])) {
         die('Kode OAuth tidak ditemukan.');
      };

      $token = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
      if (isset($token['error'])) {
         die('Google OAuth error: ' . $token['error_description']);
      }
      $this->client->setAccessToken($token['access_token']);

      $oauth = new Google_Service_Oauth2($this->client);
      $userInfo = $oauth->userinfo->get();

      // 
      // Buat JWT
      $payload = [
         'sub' => $userInfo->id,
         'name' => $userInfo->name,
         'email' => $userInfo->email,
         'iat' => time(),
         'exp' => time() + config('TOKEN_EXP', 86400),
      ];

      $jwt = JWT::encode($payload, config('SECRET_KEY'), config('ALGORITHM'));
      // Simpan di cookie
      // setcookie('Authorization', $jwt, time() + config('TOKEN_EXP', 86400), '/', '', true, true);

      slog('AKUN:', $payload);
   }
   public function index()
   {
      // slog('ID:', config('GOOGLE_CLIENT_ID'));
      // slog('SECRET:', config('GOOGLE_CLIENT_SECRET'));
      return view('auth/login');


      // $loginUrl = $this->client->createAuthUrl();
      // header("Location: $loginUrl");
      // exit;
   }
   public function login_google()
   {
      // slog('ID:', config('GOOGLE_CLIENT_ID'));
      // slog('SECRET:', config('GOOGLE_CLIENT_SECRET'));
      // return view('auth/login');


      $loginUrl = $this->client->createAuthUrl();
      header("Location: $loginUrl");
      exit;
   }
}
