<?php

class Home extends Controller
{
   public function __construct()
   {
      parent::__construct();
      model('user/user_model', 'user');
   }

   public function index()
   {
      $html = view('header');
      $html .= view('main');
      $html .= view('footer');

      return $html;
   }
   public function tes_verify()
   {
      $payload = [
         'name' => 'sugeng wahyu widodod',
         'email' => 'sugengwahyuwidodo9@gmail.com',
         'id' => '107203577704318296848',
         'avatar' => 'https://lh3.googleusercontent.com/a/ACg8ocJW02KeuCR3zmPRm6w0uKGEha9I2Wl-yDP3H45hgDkeFJ_9zuwo=s96-c',
         'givenName' => 'sugeng',
         'familyName' => 'wahyu widodo',
      ];

      slog('CREATE:', $this->user->create($payload));
   }
   public function xxx()
   {

      slog('CREATE:', $this->user->find('107203577704318296848'));
   }
   public function dashboard()
   {
      $outp = view('dashboard');
      return $outp;
   }
}
