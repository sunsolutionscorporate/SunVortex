<?php

class Residents extends Controller
{

   public function __construct()
   {
      parent::__construct();

      model('resident/residents_model', 'penduduk');
   }

   public function index()
   {
      $data = $this->penduduk->paginate();

      return view('resident/page', $data);
   }
   public function form($id)
   {
      $data = $this->penduduk->find($id);
      return view('resident/form', $data);
   }
   public function update()
   {
      $data = $this->penduduk->entry();
      redirect('residents');
   }
   public function delete($id)
   {
      $data = $this->penduduk->delete($id);
      redirect('residents');
   }
}
