<?php

class Home extends Controller
{
   public function __construct()
   {
      parent::__construct();
   }

   public function index()
   {
      $html = view('header');
      $html .= view('main');
      $html .= view('footer');

      // return $html;
   }
   public function dashboard()
   {
      $outp = view('dashboard');
      return $outp;
   }
}
