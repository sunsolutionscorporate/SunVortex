<?php

// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;



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
      // require 'PHPMailer/src/PHPMailer.php';
      // require 'PHPMailer/src/SMTP.php';
      // require 'PHPMailer/src/Exception.php';


      $mail = new PHPMailer(true);

      try {
         // Mode SMTP
         $mail->isSMTP();
         $mail->Host       = 'smtp.gmail.com';
         $mail->SMTPAuth   = true;
         $mail->Username   = 'sunsolutioncorporate@gmail.com';  // email akun SMTP mu
         $mail->Password   = 'uncj ldag hard jzbz';         // password akun SMTP
         $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
         $mail->Port       = 587;


         // Pengirim
         $mail->setFrom('sunsolutioncorporate@gmail.com');

         // Penerima
         $mail->addAddress('sugengwahyuwidodo9@gmail.com');

         $mail->addReplyTo('no-reply@domainmu.com', 'No Reply');


         // Tembusan (CC)
         $mail->addCC('kampungcabang6@gmail.com', 'Kepala Kampung');
         // Tembusan tersembunyi (BCC)
         $mail->addBCC('bos@example.com', 'Bos');

         // Isi
         $mail->isHTML(true);
         // $mail->Subject = 'Judul Email';
         $mail->Body    = '<p>Halo, ini email dari PHPMailer!</p>';

         // Kirim
         $mail->send();
         echo "Email terkirim!";
      } catch (Exception $e) {
         echo "Gagal: {$mail->ErrorInfo}";
      }
   }
}
