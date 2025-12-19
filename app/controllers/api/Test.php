<?php

class Test extends Controller
{
   public function __construct()
   {
      parent::__construct();
      model('resident/residents_model', 'penduduk');
   }

   public function index_get()
   {
      return Response::status(200, 'WOKE');
   }
}
