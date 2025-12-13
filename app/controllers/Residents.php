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

   public function email()
   {
      email([
         'content' => view('email_otp', []),
         'subject' => 'Contoh Subject Email',
         // 'altBody' => 'Halo, ini email dari PHPMailer!',
      ])
         ->notify('kampungcabang6@gmail.com', 'Kepala Kampung', Email::TYPE_PUBLIC)
         ->send('sugengwahyuwidodo9@gmail.com');
   }

   public function whatsapp()
   {
      $wa = new WhatsApp('890719127460004', 'EAALQZAP9p9xMBQOGeEoD4vsI0p53rYYgM5PujFKHo8fXNt8AnwAG5a8iX65b2hMgow52N1x1ajKOlDg4JIGyVIDK0kGmHuWti5mQRcKvl0xB5ZB5CqkdH5fEs3oUakPRRlaz8ZBiUNq6oWvsDm2SWsi3T9JPmIOWUNZA3XgPkd4ycE6s20OUjc40Oiqis7xjL1wzypvMAuFo2GpEk8ZAqUDGZB0dQYYhMgkNwKWsaSPpdvv4Fa1G6H8m09olXMNMjYvgvH3V1R1pBnTd9ZABFQ1iEi7X3DVvYVQK4FL6AAUjAZDZD');

      $response = $wa->sendText('6282279905665', "Halo sayang ini pesan dari suamimu *Sugeng Wahyu Widodo*");
      print_r($response);
   }
};
