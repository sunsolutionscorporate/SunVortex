<?php

class IOLog
{
   private static $instance = null;
   public static $content = [];
   public  $context = [];
   private static $flag_render = false;
   private static $time = 0;
   private static $performance = 0;
   private function __construct()
   {
      if (config('APP_LOG')) {
         set_exception_handler(function ($e) {
            // slog($e);
            if ($e instanceof ArgumentCountError) {
               slog('ERROR', $e);
               $this->writeBuffer(CRITICAL, [$e->getMessage()]);
            };
            $code = $e->getCode();
            $type = $e->getErrorType() ?? CRITICAL;
            if ($code >= 300 && ($type === CRITICAL || $type === ERROR || $type === WARNING)) {

               $info = (string)Response::getHttpInfo();
               self::$instance->writeBuffer($type, [$info ? ($info . ":") : "", $e->getMessage()])
                  ->addContext($e->getContext());
            }
         });


         set_error_handler(function ($severity, $message, $file, $line) {});

         register_shutdown_function(function () {
            $error = error_get_last();
            self::flush();
         });
      };
   }
   public static function init()
   {
      if (self::$instance === null) {
         self::$instance = new self();

         self::$time = microtime(true);
         self::$performance = self::$time;
      }
      return self::$instance;
   }

   private static function flush()
   {
      function indentJson(string $json, int $spaces): string
      {
         $pad = str_repeat(' ', $spaces);     // indent untuk setiap baris
         $lines = explode("\n", $json);
         return implode("\n" . $pad, $lines);
      }


      $mem_current = format_bytes(memory_get_usage(true));
      $performance = "0.00";
      if (!empty(self::$performance)) {
         $ms = microtime(true) - self::$performance;
         $performance = number_format($ms * 1000, 2);
      };

      $request = Request::init();

      $log = "\n== START [{$request->getId()}] ";
      $log .= "TYPE={$request->type()} METHOD={$request->getMethod()} PATH={$request->path()} ";

      $agent = $request->getUserAgent();
      $os = str_replace(' ', '', $agent['os']);
      $browser = str_replace(' ', '', $agent['browser']);
      $log .= "IP={$request->getClientIp()} UA={$os}/{$browser}\n";

      foreach (self::$content as $entry) {
         $tb = $entry['level'] === CRITICAL || $entry['level'] === WARNING ? "\t" : "\t\t";
         $level = trim($entry["level"], '__');
         $log .= "[{$level}]{$tb}--> [{$entry['performance']}ms] {$entry['message']}\n";

         if (!empty($entry["context"])) {
            $json = json_encode($entry["context"], JSON_PRETTY_PRINT);
            $indented = indentJson($json, 16); // 20 spasi atau sesuai kebutuhan
            $log .= "\t\t\t\t" . $indented . "\n";
         }
      };
      $log .= "== END [{$request->getId()}] ";
      $log .= "DURATION={$performance}ms MEMORY={$mem_current}";
      if (!self::$flag_render) {
         $response = Response::getHttpInfo()->toArray();
         $log .= " HTTP={$response['code']} CONTENT={$response['type']}";
      }
      $date = date('Y-m-d');
      $log_dir = DISK_PATH . '.logs';
      $filename = $log_dir . '/' . $date . '.log';
      if (!is_dir($log_dir)) {
         mkdir($log_dir, 0755, true);
      };

      file_put_contents($filename, $log . "\n", FILE_APPEND);

      self::$content = [];
   }

   public static function renderError($http_code, string $publicMessage = '')
   {
      if (config('APP_LOG')) {
         if (self::$flag_render) return;
         self::$flag_render = true;
         $request = Request::init();
         if ($request->type() === 'browser') {
            $view = view('error');
            Response::html($view)->status($http_code, $publicMessage)->send();
         } elseif ($request->type() === 'ajax' || $request->isApi()) {
            Response::json([])->status($http_code, $publicMessage)->send();
            // 
         }
      }
   }

   public function writeBuffer($level, array $messages)
   {
      if ($level === CRITICAL) {
         self::renderError(500);
      }
      $out = "";
      foreach ($messages as  $value) {
         if (is_string($value)) {
            $out .= trim($value);
         } elseif (is_array($value)) {
            $out .= json_encode($value);
         } elseif (is_object($value)) {
            $out .= json_encode($value);
         } else {
            $str = strval($value);
            $out .= trim($str);
         }

         $out .= " ";
      };

      $duration_ms = "0.00";
      if (!empty(self::$time)) {
         $duration = microtime(true) - self::$time;
         $duration_ms = number_format($duration * 1000, 2);
         self::$time = microtime(true);
      };

      $level = strtoupper($level);
      self::$content[] = [
         'level' => $level,
         'message' => $out,
         'performance' => $duration_ms,
         'context'     => null
      ];

      $lastIndex = array_key_last(self::$content);

      return new class($lastIndex) {

         private $index;

         public function __construct($index)
         {
            $this->index = $index;
         }

         public function addContext(array $ctx)
         {
            // Langsung ubah static property milik Tulis
            IOLog::$content[$this->index]['context'] = $ctx;

            return $this; // opsional, untuk chaining
         }
      };
   }

   public static function  parseArgs(...$args): array
   {
      $http_code       = null;
      $context         = null;
      $userFriendly    = null;
      $level           = null;
      $internalMessage = null;
      $publicMessage   = null;

      // Daftar level valid
      $validLevels = [CRITICAL, ERROR, WARNING, INFO, DEBUG];

      foreach ($args as $arg) {

         // 1. Integer → $http_code
         if (is_int($arg)) {
            $http_code = $arg;
            continue;
         }

         // 2. Array → $context
         if (is_array($arg)) {
            $context = $arg;
            continue;
         }

         // 3. Boolean → $userFriendly
         if (is_bool($arg)) {
            $userFriendly = $arg;
            continue;
         }

         // 4. String untuk LEVEL
         if (is_string($arg) && in_array($arg, $validLevels, true)) {
            $level = $arg;
            continue;
         }

         // 5. String biasa → internalMessage (pertama), publicMessage (kedua)
         if (is_string($arg)) {
            if ($internalMessage === null) {
               $internalMessage = $arg;
            } elseif ($publicMessage === null) {
               $publicMessage = $arg;
            }
            continue;
         }

         // 6. Tipe lain: abaikan
      }

      return [
         'http_code'       => $http_code,
         'internalMessage' => $internalMessage ?? '',
         'publicMessage'   => $publicMessage ?? '',
         'level'           => $level ?? ERROR,
         'context'         => $context ?? [],
         'userFriendly'    => $userFriendly ?? true,
      ];
   }
};


/**
 * Logger
 * 
 * Exception dengan pisahan pesan:
 * - $publicMessage   : pesan yang aman ditampilkan ke user (halaman error)
 * - $internalMessage : pesan detail untuk log/debugging (tidak boleh di-expose)
 * - $userFriendly    : apakah exception ini boleh ditampilkan ke user
 * 
 * Contoh penggunaan:
 * 
 *   throw new Logger(
 *       404,
 *       publicMessage: "Halaman tidak ditemukan",
 *       internalMessage: "File '/path/to/view.php' tidak ada atau akses ditolak"
 *   );
 * 
 *   throw new Logger(
 *       500,
 *       publicMessage: "Terjadi kesalahan pada sistem. Mohon coba beberapa saat lagi.",
 *       internalMessage: "Database connection failed: timeout after 30s on host 192.168.1.100:3306",
 *       userFriendly: false  // jangan expose di UI, hanya di log
 *   );
 */
class Logger extends Exception
{
   protected $errorType = '__ERROR__';
   protected $publicMessage = '';
   protected $internalMessage = '';
   protected $userFriendly = true;
   protected $context = [];

   public function __construct(...$args)
   {
      $arg = IOLog::parseArgs(...$args);

      // Gunakan publicMessage sebagai message utama jika internalMessage kosong
      $mainMessage = !empty($arg['internalMessage']) ? $arg['internalMessage'] : $arg['publicMessage'];
      parent::__construct($mainMessage, $arg['http_code']);
      $this->publicMessage = $arg['publicMessage'] ?: 'Terjadi kesalahan pada sistem';
      $this->internalMessage = $arg['internalMessage'] ?: $arg['publicMessage'];
      $this->userFriendly = $arg['userFriendly'];
      $this->context = $arg['context'];
      $this->errorType = $arg['level'];
      IOLog::renderError($arg['http_code'], $arg['publicMessage']);
   }

   /**
    * Pesan yang aman untuk ditampilkan ke pengguna
    */
   public function getPublicMessage(): string
   {
      return $this->publicMessage;
   }

   /**
    * Apakah exception boleh di-expose ke user?
    */
   public function isUserFriendly(): bool
   {
      return $this->userFriendly;
   }

   /**
    * Data konteks tambahan (debugging)
    */
   public function getContext(): array
   {
      return $this->context;
   }

   public function getErrorType(): string
   {
      return $this->errorType;
   }

   public static function debug(...$args)
   {
      return IOLog::init()->writeBuffer(DEBUG, $args);
   }
   public static function info(...$args)
   {
      return IOLog::init()->writeBuffer(INFO, $args);
   }
   public static function warning(...$args)
   {
      return IOLog::init()->writeBuffer(WARNING, $args);
   }
}
