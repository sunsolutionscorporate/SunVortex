<?php

class WhatsApp
{
   protected $accessToken;
   protected $phoneNumberId;

   public function __construct(string $phoneNumberId, string $accessToken)
   {
      $this->phoneNumberId = $phoneNumberId;
      $this->accessToken = $accessToken;
   }

   /**
    * Kirim pesan teks ke nomor WhatsApp
    *
    * @param string $to Nomor penerima dengan kode negara, misal "6281234567890"
    * @param string $message Isi pesan
    * @return array Response dari WhatsApp API
    */
   public function sendText(string $to, string $message): array
   {
      $url = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages";

      $payload = [
         'messaging_product' => 'whatsapp',
         'to' => $to,
         'type' => 'text',
         'text' => [
            'body' => $message
         ]
      ];

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
         "Authorization: Bearer {$this->accessToken}",
         "Content-Type: application/json"
      ]);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $response = curl_exec($ch);
      curl_close($ch);

      return json_decode($response, true);
   }
};
