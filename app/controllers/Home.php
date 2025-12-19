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
      return view('spa');
   }
   public function tes_verify()
   {
      return view('auth/verify');
   }
   public function xxx()
   {

      slog('CREATE:', $this->user->find('107203577704318296848'));
   }
   public function web()
   {
      $outp = view('web');
      return $outp;
   }
   public function dashboard()
   {
      $outp = view('dashboard');
      return $outp;
   }
}
