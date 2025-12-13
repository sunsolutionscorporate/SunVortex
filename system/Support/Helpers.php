<?php


if (!function_exists('token_encode')) {
   function token_encode($payload)
   {
      $data = [
         "iss" => "http://localhost", // issuer
         "aud" => "http://localhost", // audience
         "iat" => time(),             // issued at
         "exp" => time() + config('TOKEN_EXP', 86400),      // defaul expired in 1 hari
         "data" => $payload
      ];
      $jwt = JWT::encode($data, config('SECRET_KEY'), config('ALGORITHM'));
      return $jwt;
   }
};

if (!function_exists('token_decode')) {
   function token_decode($credential)
   {
      $decoded = new stdClass();
      if (!is_string($credential)) {
         Logger::warning("Argument passed to method 'token()' is not a valid token: string expected.");
         $decoded->status = "invalid token";
         return $decoded;
      }
      Logger::debug('Decoding Authorization token.');
      try {
         $decoded = JWT::decode($credential, new Key(config('SECRET_KEY'), config('ALGORITHM')));
         Logger::info('Token decode succeeded — payload: ', $decoded->data);
         $decoded->status = 'ok';
         return $decoded;
      } catch (Exception $e) {
         $err_msg = $e->getMessage();
         Logger::warning('Authentication: ', $e->getMessage());
         $decoded->status = $err_msg === "Expired token" ? 'expired token' : 'invalid token';
         return $decoded;
      }
   }
};

if (!function_exists('format_bytes')) {
   function format_bytes($bytes, $decimal = 2)
   {
      $units = ['B', 'KB', 'MB', 'GB', 'TB'];
      $i = 0;

      while ($bytes >= 1024 && $i < count($units) - 1) {
         $bytes /= 1024;
         $i++;
      }
      $val = round($bytes, 2);

      return number_format($val, $decimal) . $units[$i];
   }
};

// Fallback untuk PHP < 8
if (!function_exists('str_starts_with')) {
   function str_starts_with($haystack, $needle)
   {
      return substr($haystack, 0, strlen($needle)) === $needle;
   }
};

// Fallback untuk PHP < 8
if (!function_exists('str_ends_with')) {
   function str_ends_with($haystack, $needle)
   {
      if ($needle === '') return true;
      return substr($haystack, -strlen($needle)) === $needle;
   }
};

if (!function_exists('is_assoc')) {
   function is_assoc(array $arr): bool
   {
      return array_keys($arr) !== range(0, count($arr) - 1);
   }
};

if (!function_exists('str_contains')) {
   function str_contains(string $haystack, string $needle): bool
   {
      return $needle !== '' && strpos($haystack, $needle) !== false;
   }
}

if (!function_exists('include_class')) {
   function include_class($namaFile, $target, $params = [], $alias = "")
   {
      $cleanPath = rtrim($namaFile, '/');
      $info = pathinfo($cleanPath);
      $dir  = ($info['dirname'] ?? '') . '/';
      $name = ucfirst($info['filename']);
      $ext  = isset($info['extension']) ? '.' . $info['extension'] : '';
      if ($ext === '') {
         $ext = '.php';
      }
      $file = $dir . $name . $ext;
      if (!file_exists($file)) return false;
      require_once $file;
      if (!class_exists($name, false)) return false;
      $prop = empty($alias) ? lcfirst($name) : $alias;
      $instance = !empty($params) ? new $name($params) : new $name();
      // 🔹 Kalau target adalah string (misal __CLASS__ atau self)
      if (is_string($target)) {
         $target::$$prop = $instance;
         return $target::$$prop;
      }

      // 🔹 Kalau target adalah object instance
      if (is_object($target)) {
         $target->$prop = $instance;
         return $target->$prop;
      };
      return false;
   }
};

if (!function_exists('loadFiles')) {
   /**
    * Load semua file PHP dalam folder tertentu
    *
    * @param string $folder Path folder target
    * @param string $ext ekstensi *default 'php'
    * @param bool $recursive Apakah scan subfolder juga
    * @return void
    */
   function loadFiles(string $folder, string $ext = 'php', bool $recursive = false): void
   {
      if (!is_dir($folder)) {
         throw new InvalidArgumentException("Folder tidak ditemukan: $folder");
      }

      $iterator = $recursive
         ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder))
         : new DirectoryIterator($folder);

      foreach ($iterator as $file) {
         if ($file->isFile() && $file->getExtension() === $ext) {
            require_once $file->getPathname();
         }
      }
   }
};

if (!function_exists('destructure')) {
   function destructure(array $arr, array $keys): array
   {
      $result = [];
      foreach ($keys as $key) {
         $result[] = $arr[$key] ?? null; // pakai numerik agar bisa di-list
      }
      return $result;
   }
};

if (!function_exists('slog')) {
   /**
    * Print dan return (untuk chaining)
    */
   function slog(...$args)
   {
      $pesan = '';
      foreach ($args as $arg) {
         if (is_array($arg) || is_object($arg)) {
            $pesan .= '<pre style="margin:0;padding:0;padding-left:30px;">' . print_r($arg, true) . '</pre>';
         } else {
            $pesan .= $arg . " ";
         }
      }
      echo '<p style="margin:10px 0;padding:0;">' . $pesan . "</p>";
   }
};

if (!function_exists('base_url')) {
   /**
    * Get base URL
    */
   function base_url($param = "")
   {
      $request = Request::init();
      $url = trim($request->getProtocol() . '://' . $request->getHost(), '/') . '/';
      return $url . (empty($param) ? '' : $param . "/");
   };
};

if (!function_exists('config')) {
   function config($key = null, $default = null)
   {
      $key = strtoupper($key);
      if (empty($key)) {
         return $_ENV;
      }

      // Cek di $_ENV
      if (isset($_ENV[$key])) {
         $val = $_ENV[$key];
         return isJson($val) ? json_decode($val, true) : $val;
      }

      // Cek di $_SERVER
      if (isset($_SERVER[$key])) {
         $val = $_SERVER[$key];
         return isJson($val) ? json_decode($val, true) : $val;
      }

      // Cek via getenv()
      $value = getenv($key);
      if ($value !== false) {
         return isJson($value) ? json_decode($value, true) : $value;
      }

      return $default;
   }
}

if (!function_exists('httpInfo')) {
   function httpInfo($code): array
   {
      $langs = config('LANGUAGE_DICT');
      $status_key  = "http_{$code}_status";
      $message_key = "http_{$code}_message";

      if (isset($langs[$status_key]) && isset($langs[$message_key])) {
         return [
            'status'  => $langs[$status_key],
            'message' => $langs[$message_key]
         ];
      }
      return [];
   }
};

if (!function_exists('isVersionString')) {
   function isVersionString(string $str): bool
   {
      // ^v  => harus diawali huruf v
      // \d+ => diikuti satu atau lebih angka
      // (\.\d+)* => boleh ada titik diikuti angka (opsional, berulang)
      // $   => sampai akhir string
      return preg_match('/^v\d+(\.\d+)*$/', $str) === 1;
   }
};

if (!function_exists('isRegex')) {
   function isRegex(string $str): bool
   {
      $delimiters = ['/', '#', '~', '%', '!'];

      $first = substr($str, 0, 1);

      if (!in_array($first, $delimiters)) {
         return false;
      }

      // Cari posisi delimiter penutup (sama dengan delimiter pembuka)
      // tapi tidak pada posisi terakhir karena bisa ada modifier
      $len = strlen($str);
      $pos = strrpos($str, $first);

      if ($pos === 0) {
         return false; // tidak ditemukan penutup
      }

      // Ambil bagian setelah delimiter penutup
      $modifiers = substr($str, $pos + 1);

      // Validasi modifier regex PHP
      if ($modifiers !== '' && !preg_match('/^[imsxuADSUXJ]*$/', $modifiers)) {
         return false;
      }

      // Pastikan preg_match tidak error
      return @preg_match($str, '') !== false;
   }
};

if (!function_exists('isJson')) {
   function isJson(string $string): bool
   {
      // JSON harus diawali { ... } atau [ ... ]
      $trim = trim($string);
      if ($trim === '' || !in_array($trim[0], ['{', '['])) {
         return false;
      }

      json_decode($trim);

      return json_last_error() === JSON_ERROR_NONE;
   }
};

if (!function_exists('changeClassProperty')) {
   /**
    * Set value property (attribute) pada object/class
    *
    * @param object $obj    Object instance
    * @param string $property  Nama property
    * @param mixed $value      Nilai baru
    */
   function changeClassProperty(object $obj, string $property, $value): object
   {
      $ref = new ReflectionClass($obj);

      if (!$ref->hasProperty($property)) {
         throw new InvalidArgumentException("Property '$property' tidak ditemukan pada class " . $ref->getName());
      }

      $prop = $ref->getProperty($property);
      // slog($prop->getValue($obj));

      // Buka akses jika property tidak public
      if (!$prop->isPublic() && method_exists($prop, 'setAccessible')) {
         $prop->setAccessible(true);
      };

      $ori = $prop->getValue($obj);
      if (is_object($ori) || is_array($ori)) {
         $value = spread($ori, $value);
      }
      $prop->setValue($obj, $value);
      return $ref;
   }
};

if (!function_exists('spread')) {
   /**
    * Spread-like helper untuk PHP
    * - Input: array atau object
    * - Output: tipe sama dengan input
    */

   function spread($data, array $extra)
   {
      // Jika array → langsung merge
      if (is_array($data)) {
         return array_merge($data, $extra);
      }

      // Jika object → ambil property publik saja
      if (is_object($data)) {
         // ambil properti publik
         $publicProps = get_object_vars($data);

         // merge dengan extra
         $merged = array_merge($publicProps, $extra);

         // jika stdClass atau object lain → tetap kembalikan object
         $result = clone $data;
         foreach ($merged as $key => $value) {
            $result->$key = $value;
         }

         return $result;
      }

      // Jika bukan array/object → tolak
      throw new InvalidArgumentException(
         "spread() hanya menerima array atau object."
      );
   }
};

if (!function_exists('object_merge')) {
   function object_merge(object $a, object $b): object
   {
      foreach (get_object_vars($b) as $key => $value) {
         $a->$key = $value;
      }
      return $a;
   }
};

if (!function_exists('getContentType')) {
   function getContentType($headers = null): string
   {
      // If no headers passed, delegate to Request helper
      if ($headers === null) {
         if (class_exists('Request')) {
            try {
               $req = Request::init();
               return $req->getContentType() ?: 'unknown';
            } catch (\Throwable $e) {
               // fallback to unknown
               return 'unknown';
            }
         }
         return 'unknown';
      }

      $contentType = null;

      foreach ($headers as $key => $value) {

         // Pattern 1: "Content-Type: application/json"
         if (is_string($value) && stripos($value, 'content-type:') === 0) {
            $contentType = trim(substr($value, 13));
            break;
         }

         // Pattern 2: ["Content-Type" => "application/json"]
         if (strcasecmp($key, 'content-type') === 0) {
            $contentType = trim($value);
            break;
         }
      }

      if (!$contentType) return 'unknown';

      $mime = strtolower($contentType);

      // ---- CATEGORY DETECTION (universal) ----
      if (str_starts_with($mime, 'application/json')) return 'json';
      if (str_contains($mime, '+json')) return 'json';  // e.g. application/ld+json

      if (str_starts_with($mime, 'text/html')) return 'html';
      if (str_starts_with($mime, 'text/')) return 'text';

      if (str_ends_with($mime, '/xml')) return 'xml';
      if (str_contains($mime, 'xml')) return 'xml';

      if (str_starts_with($mime, 'multipart/form-data')) return 'multipart';
      if (str_starts_with($mime, 'application/x-www-form-urlencoded')) return 'form';

      if (str_starts_with($mime, 'image/')) return 'image';
      if (str_starts_with($mime, 'video/')) return 'video';
      if (str_starts_with($mime, 'audio/')) return 'audio';

      if (str_starts_with($mime, 'application/pdf')) return 'pdf';
      if (str_starts_with($mime, 'application/javascript')) return 'javascript';

      if (str_starts_with($mime, 'application/octet-stream')) return 'binary';
      if (str_contains($mime, 'zip')) return 'binary';

      return 'unknown';
   }
};

if (!function_exists('getLastSegment')) {
   function getLastSegment(string $path): string
   {
      // Ganti backslash (\) dengan slash (/) agar konsisten
      $normalized = str_replace('\\', '/', $path);
      // Hilangkan slash di akhir
      $trimmed = rtrim($normalized, '/');
      // Ambil segmen terakhir
      $last = basename($trimmed);
      // Hilangkan ekstensi jika ada
      return pathinfo($last, PATHINFO_FILENAME);
   }
};

if (!function_exists('errorMapLevelPhp')) {
   function errorMapLevelPhp($err_code)
   {
      // 
      $map_error = [
         E_ERROR => CRITICAL,
         E_CORE_ERROR => CRITICAL,
         E_COMPILE_ERROR => CRITICAL,
         E_PARSE => CRITICAL,
         E_WARNING => ERROR,
         E_CORE_WARNING => ERROR,
         E_COMPILE_WARNING => ERROR,
         E_NOTICE => WARNING,
         E_USER_ERROR => CRITICAL,
         E_USER_WARNING => ERROR,
         E_USER_NOTICE => WARNING,
         E_DEPRECATED => WARNING,
         E_USER_DEPRECATED => WARNING,
      ];
      return isset($map_error[$err_code]) ? $map_error[$err_code] : INFO;
   }
};

if (!function_exists('allowOrigin')) {
   function allowOrigin($domains, $subdomainWildcard = true)
   {
      $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
      if (!$origin) return false;

      // Parse origin
      $parsed = parse_url($origin);
      if (!$parsed || !isset($parsed['host'])) return false;

      $originScheme = $parsed['scheme'] ?? 'http';
      $originHost   = $parsed['host'];

      // Jika DEV mode → scheme http/https longgar
      $isDev = (function_exists('config') && config('MODE') === 'development');

      if (!is_array($domains)) {
         $domains = [$domains];
      }

      foreach ($domains as $pattern) {

         // Buang spasi
         $pattern = trim($pattern);

         // Extract scheme jika ada di pola
         $patternScheme = null;
         if (strpos($pattern, '://') !== false) {
            $parsePattern = parse_url($pattern);
            $patternScheme = $parsePattern['scheme'] ?? null;
            $patternHost   = $parsePattern['host'] ?? $pattern;
         } else {
            $patternHost = $pattern;
         }

         // Jika DEV → abaikan scheme mismatch
         if (!$isDev && $patternScheme && $originScheme !== $patternScheme) {
            continue; // scheme tidak cocok
         }

         // Tangani wildcard: "*.example.com"
         if (strpos($patternHost, '*.') === 0) {
            $baseDomain = substr($patternHost, 2); // buang "*."

            // Mode subdomain wildcard
            if ($subdomainWildcard) {
               if (preg_match('#(^|\.)' . preg_quote($baseDomain) . '$#i', $originHost)) {
                  return true;
               }
            } else {
               // Non-wildcard: hanya hostname exact tanpa '*.'
               if ($originHost === $baseDomain) {
                  return true;
               }
            }

            continue;
         }

         // Pola exact tanpa wildcard
         if ($originHost === $patternHost) {
            return true;
         }
      }

      return false;
   }
};

if (!function_exists('model')) {
   function model_old(string $name, string $alias = "")
   {
      $CI = &get_instance();

      ////////////////////////////////
      //// Normalize Nama Model //////
      ////////////////////////////////
      // Normalisasi ke lower agar mudah deteksi
      $lower = strtolower($name);

      // Cek apakah ada suffix _model
      $hasModel = substr($lower, -6) === '_model';

      // Hilangkan _model kalau ada
      if ($hasModel) {
         $name = substr($name, 0, -6);
      }

      // NORMALISASI BAGIAN NAMA
      // Kalau original mengandung huruf besar → anggap camelCase → pertahankan
      if (preg_match('/[A-Z]/', $name)) {
         // Ubah huruf pertama jadi kapital (PascalCase)
         $name = ucfirst($name);
      } else {
         // Tidak ada uppercase → anggap satu kata → hanya kapital posisi pertama
         $name = ucfirst(strtolower($name));
      }

      // Gabungkan dengan suffix _Model
      $name = $name . '_Model';

      $path = realpath($CI->request->getSource() . '/models/' . $name . '.php');
      if (!include_class($path, $CI, [], $alias)) {
         abort(
            500,
            "File Model '{$name}.php' tidak ditemukan!"
         );
      }
   }
   function model(string $name, string $alias = "")
   {
      $CI = &get_instance();
      if (!($CI instanceof Controller)) {
         slog('ERROR karena bukan exend dari class Controller');
         exit;
      };
      $name = $CI->request->getSource() .
         'models' . DIRECTORY_SEPARATOR .
         (strcasecmp(substr($name, -6), '_model') === 0 ? $name : $name . "_model") . ".php";
      $path = realpath($name);
      if (!$path) abort(500, "File Model '{$name}.php' tidak ditemukan!");
      require_once $path;

      $class = getLastSegment($path);
      $name = substr($class, 0, stripos($class, '_model'));
      if (!class_exists($class, false)) abort(500, "class '{$class}' tidak ditemukan!");

      $instance =  new $class();
      $name = empty($alias) ? $name : $alias;
      $CI->$name = $instance;
      // slog('DIR:', $class);
      // slog('DIR:', $name);
   }
};

if (!function_exists('view')) {
   function view(string $path, $data = [])
   {
      if ($data instanceof Results) {
         $data = $data->results();
      }
      // unset($data['csrf_token']);
      // $data['csrf_token'] = Csrf::token();
      $view = new View($path, $data);
      return $view;
   }
};

if (!function_exists('abort')) {

   function abort(...$args)
   {
      throw new Logger(...$args);
   }
};

if (!function_exists('redirect')) {
   function redirect($uri, $refresh = false, $code = null)
   {
      return Response::redirect($uri, $refresh, $code);
   }
};

if (!function_exists('isHtml')) {
   /**
    * Fungsi untuk memeriksa apakah sebuah string mengandung tag HTML atau karakter entitas HTML.
    * @param string $str String yang akan diperiksa.
    * @return bool Mengembalikan true jika string mengandung HTML, false jika tidak atau kosong.
    */
   function isHtml($str)
   {
      // Jika kosong, dianggap bukan HTML
      if (trim($str) === '') return false;
      // Cek apakah ada tag HTML
      if ($str != strip_tags($str)) {
         return true; // ada tag HTML seperti <b>, <div>, dll
      }
      // Cek apakah ada karakter entitas HTML (misal &lt; atau &nbsp;)
      if (preg_match('/&[a-zA-Z0-9#]+;/', $str)) {
         return true;
      }
      return false; // bukan HTML
   }
}

if (!function_exists('email')) {
   /**
    * Fungsi untuk membuat objek Email
    * Parameter $payload dapat berupa:
    * - string  → isi email (HTML atau teks biasa)
    * - array   → [
    *       'subject' => 'Judul Email',
    *       'content' => '<h1>Isi Email</h1>',
    *       'alt'     => 'Isi alternatif'
    *   ]
    *
    * @param string|array $payload Isi email atau konfigurasi email.
    * @return Email Objek Email
    */
   function email($payload): Email
   {
      return new Email($payload);
   }
};

function &get_instance()
{
   return Controller::get_instance();
};
