<?php

class Residents_api extends Controller
{
   public function __construct()
   {
      parent::__construct();
      model('resident/residents_model', 'penduduk');
   }

   public function index_get()
   {
      // slog(strtoupper(__CLASS__ . " " . __METHOD__));
      return $this->penduduk->paginate();
      // return [
      //    'data' => [],
      //    'pagination' => $this->request->getAccept(),
      //    'content' => $this->request->getContentType(),
      // ];
   }
   public function post()
   {
      // 
   }
   public function put()
   {
      // 
   }
}
