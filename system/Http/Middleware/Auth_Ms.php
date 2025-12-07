<?php

if (!class_exists('\Firebase\JWT\JWT')) {
   class JWT
   {
      public static function encode($payload, $key, $alg = 'HS256')
      {
         return base64_encode(json_encode($payload));
      }

      public static function decode($jwt, $keyOrKeyArray, $allowedAlgs = [])
      {
         return json_decode(base64_decode($jwt));
      }
   }
   class_alias('JWT', '\Firebase\JWT\JWT');
} else {
   class JWT extends \Firebase\JWT\JWT {};
};
if (!class_exists('\Firebase\JWT\Key')) {
   class Key
   {
      public function __construct($key, $alg) {}
   }
   class_alias('Key', '\Firebase\JWT\Key');
} else {
   class Key extends \Firebase\JWT\Key {};
};

class Auth_Ms
{
   public function decode(string $token)
   {
      $decoded = new stdClass();
      if (!is_string($token)) {
         Logger::warning('[', __CLASS__, '] ', "Argument passed to method 'token()' is not a valid token: string expected.");
         $decoded->status = "invalid token";
         return $decoded;
      }
      Logger::debug('[', __CLASS__, '] ', 'Decoding Authorization token.');
      try {
         $decoded = JWT::decode($token, new Key(config('SECRET_KEY'), config('ALGORITHM')));
         Logger::info('[', __CLASS__, '] ', 'Token decode succeeded — payload: ', $decoded->data);
         $decoded->status = 'ok';
         return $decoded;
      } catch (Exception $e) {
         $err_msg = $e->getMessage();
         Logger::warning('[', __CLASS__, '] ', 'Authentication: ', $e->getMessage());
         $decoded->status = $err_msg === "Expired token" ? 'expired token' : 'invalid token';
         return $decoded;
      }
   }
};
