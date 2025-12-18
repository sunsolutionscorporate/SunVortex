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
      // Buat JWT
      $payload = [
         'sub' => $userInfo->id,
         'name' => $userInfo->name,
         'email' => $userInfo->email,
         'iat' => time(),
         'exp' => time() + config('TOKEN_EXP', 86400),
      ];

      $jwt = JWT::encode($payload, config('SECRET_KEY'), config('ALGORITHM'));

      // BALIK KE SPA
      header("Location: http://localhost/#/oauth#token=$jwt");
      exit;
   }
   public function index()
   {
      return view('auth/login');
   }
   public function login_google()
   {
      $loginUrl = $this->client->createAuthUrl();
      header("Location: $loginUrl");
      exit;
   }
}
