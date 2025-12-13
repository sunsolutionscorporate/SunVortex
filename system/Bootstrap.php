<?php
$root = substr(__DIR__, 0, strripos(__DIR__, DIRECTORY_SEPARATOR . "system") + 1);
define('undefined', '__undefined__');
define("ROOT_PATH", $root);
define("APP_PATH", ROOT_PATH . "app" . DIRECTORY_SEPARATOR);
define("CORE_PATH", ROOT_PATH . "system" . DIRECTORY_SEPARATOR);
define("CRITICAL", '__CRITICAL__');
define("ERROR", '__ERROR__');
define("WARNING", '__WARNING__');
define("INFO", '__INFO__');
define("DEBUG", '__DEBUG__');
class Kernel
{
   private $request;
   private static $reflectionCache = [];
   private static $http_codes;

   private static function insertEnv($key, $value)
   {
      if ($key === 'DISK') {
         $path_disk = $_ENV['ROOT'] . $value . DIRECTORY_SEPARATOR;
         // self::intermedia('PATH_DISK', $path_disk);
         define("DISK_PATH", $path_disk);
      }
      if ($key === 'APP_VERSION' && $value === '') {
         $value = 'v0';
      }
      $value = trim($value, "'\"");
      if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
         putenv("$key=$value");
         $_ENV[$key] = $value;
         $_SERVER[$key] = $value;
      };
   }

   private static function loadEnv()
   {
      $path = __DIR__ . '/../.env';
      if (!file_exists($path)) return;
      function parseValue(string $value)
      {
         $value = trim($value);

         // Hilangkan kutip luar jika ada
         if ((str_starts_with($value, "'") && str_ends_with($value, "'")) ||
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
         ) {
            $value = substr($value, 1, -1);
         }

         // Ubah nilai boolean/string/JSON otomatis
         if (strtolower($value) === 'true') return true;
         if (strtolower($value) === 'false') return false;
         if (is_numeric($value)) return $value + 0;

         // Jika mendeteksi JSON
         if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
            $json = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
               return $json;
            }
         }

         return $value;
      };
      function getVersionDirAPP(string $path): array
      {
         $versions = [];
         foreach (scandir($path) as $item) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $item;

            // Ambil hanya folder dengan nama sesuai pola v + angka(.angka)
            if (is_dir($fullPath) && preg_match('/^v\d+(\.\d+)*$/', $item)) {
               $versions[] = $item;
            }
         }
         // Urutkan versi dengan version_compare
         usort($versions, function ($a, $b) {
            return version_compare(substr($a, 1), substr($b, 1));
         });

         return $versions;
      };


      // 
      $root = substr(__DIR__, 0, strripos(__DIR__, DIRECTORY_SEPARATOR . "system") + 1);
      $path_app = $root . "app" . DIRECTORY_SEPARATOR;
      self::insertEnv('ROOT', $root);
      self::insertEnv('PATH_APP',  $path_app);

      $content = file_get_contents($path);
      $lines = preg_split('/\r\n|\n|\r/', $content);
      $buffer = '';
      $currentKey = null;
      foreach ($lines as $line) {
         $trimmed = trim($line);

         if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
         }

         if (preg_match('/^([A-Z0-9_]+)\s*=\s*(.*)$/i', $trimmed, $matches)) {
            // Simpan buffer sebelumnya jika ada
            if ($currentKey !== null && $buffer !== '') {
               self::insertEnv($currentKey, trim($buffer));
            }

            $currentKey = $matches[1];
            $value = trim($matches[2]);

            // Jika value diakhiri kutip tunggal belum tertutup, aktifkan buffer
            if (preg_match("/^'(.*)$/", $value) && !str_ends_with($value, "'")) {
               $buffer = $value . "\n";
               continue;
            }

            self::insertEnv($currentKey, parseValue($value));
            $buffer = '';
            $currentKey = null;
         } else {
            // Lanjutan dari value multiline
            if ($currentKey !== null) {
               $buffer .= $trimmed . "\n";
            }
         }
      };

      // Simpan buffer terakhir
      if ($currentKey !== null && $buffer !== '') {
         self::insertEnv($currentKey, trim($buffer));
      };
      // ambil versi dalam folder app
      $cok = getVersionDirAPP(APP_PATH);
      self::insertEnv('VERSIONS', json_encode($cok));

      // LANGUAGE
      if (self::$http_codes === null) {
         $json_file = 'http_codes.json';
         $real_path = realpath(__DIR__ . '/language/');
         $file_path = $real_path . DIRECTORY_SEPARATOR . $json_file;
         if (!file_exists($file_path)) {
            Logger::warning("File '{$json_file}' tidak ditemukan pada folder '{$real_path}'");
            return 0;
         };
         $json_data = file_get_contents($file_path);
         self::$http_codes = json_decode($json_data, true);
         Logger::debug("File '{$json_file}' berhasil di-load");
      };
      $bahasa = [];
      foreach (self::$http_codes as $code => $data) {
         $bahasa["http_{$code}_status"] = $data['en']['status'];
         $bahasa["http_{$code}_message"] = $data['en']['message'];
         // $bahasa
      }
      self::insertEnv('LANGUAGE_DICT', json_encode($bahasa));
   }

   public function __construct()
   {
      if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
         require_once __DIR__ . '/../vendor/autoload.php';
      }
      // Load autoload
      require_once __DIR__ . '/Autoload.php';
      self::loadEnv();

      switch (config('MODE')) {
         case 'development':
            if (config('APP_LOG')) {
               ini_set('display_errors', 0);
            } else {
               error_reporting(-1);
               ini_set('display_errors', 1);
            }
            break;
         case 'testing':
         case 'production':
            ini_set('display_errors', 0);
            if (version_compare(PHP_VERSION, '5.3', '>=')) {
               error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
            } else {
               error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
            }
            break;
         default:
            header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
            echo 'The application environment is not set correctly.';
            exit(1);
      };
      IOLog::init();
      $this->request =  Request::init();
   }

   public function boot()
   {
      ///////////////////////////////////////////
      ///// MIDDLEWARE PIPELINE /////////////////
      ///////////////////////////////////////////

      // Initialize pipeline dengan request
      $pipeline = new Pipeline();
      // Set middleware queue dengan default order
      $pipeline->through([
         'cors',      // Handle CORS & preflight
         'pageCache', // Page caching
         'throttle',  // Rate limiting
         'auth',      // Authentication
         'route',     // Route matching
         'csrf',      // CSRF token validation
      ]);

      // Skip CSRF untuk API requests
      if ($this->request->isApi()) {
         $pipeline->skip('csrf');
      } else {
         // Tambahkan CSRF hanya untuk non-API, non-GET/HEAD/OPTIONS requests
         if (!$this->request->isMethod('GET', 'HEAD', 'OPTIONS')) {
            // CSRF akan dijalankan
         } else {
            $pipeline->skip('csrf');
         }
      };
      // Register event listener untuk track middleware execution
      $pipeline->listener(function ($event) {
         $type = $event['type'];
         $middleware = $event['middleware'];


         if ($middleware === 'cors' && $type === 'after') {
            // Middleware throttle sudah selesai, stop middleware berikutnya
            if ($this->request->getOrigin() && $this->request->isOptions()) {
               Response::status(204)->send();
               return 'stop';
            }
         };
         return null;  // Default: lanjut
      });
      // 
      // Execute pipeline - final callback adalah routing ke controller
      $response = $pipeline->then(function () {
         return $this->routeToController($this->request);
      });

      // Pastikan response adalah Response instance
      if (!($response instanceof Response)) {
         $response = Response::content($response);
      }

      // Send response
      return $response->send();
   }


   /**
    * Route request ke controller yang sesuai
    * Ini adalah destination callback dalam pipeline
    */
   private function routeToController($request)
   {

      [$controller, $method, $params] = destructure($request->getUri(), ['controller', 'method', 'params']);
      $end_point = ($request->isApi() ? 'api/' : '') . $controller . '/' . $method . '/' . implode('/', $params);

      $controller = ucfirst($controller);
      $controller_dir = 'controllers';
      if ($request->isApi()) {
         $controller_dir .= DIRECTORY_SEPARATOR . 'api';
      }
      $controller_dir .= DIRECTORY_SEPARATOR . $controller . '.php';


      // Cek akses browser ke API
      // if ($request->type() === 'browser' && $request->isApi()) {
      //    abort(
      //       403,
      //       "API endpoint accessed via browser: '/{$end_point}' (Direct access blocked)",
      //       'This endpoint cannot be accessed directly through a browser. It is intended for API requests only.',
      //    );
      // }

      $controller_path = realpath($request->getSource() . $controller_dir);
      if (!$controller_path) {
         abort(
            404,
            "Controller file '{$controller}.php' not found, expected a valid app path.:'{$controller_dir}'",
         );
      }

      Logger::info("Dispatching request: '" . $end_point . "'");
      /** @var mixed $Controller - Dynamically loaded at runtime */
      Controller::setAttribute('request', $request);

      require_once $controller_path;
      ///////////////////////////////////////////
      ///// RESOLVE & INVOKE HANDLER ////////////
      ///////////////////////////////////////////
      $metaData = $this->getReflectionMeta($controller, $method);
      $args = $this->buildArgs($metaData, $params);

      if (!$metaData) {
         abort(404, "Controller method '{$controller}::{$method}()' not found");
      }


      return $this->invokeControllerMethod($metaData['ref'], $args);
   }

   /**
    * Get or build reflection metadata for controller::method and cache it.
    */
   private function getReflectionMeta(string $controller, string $method): array
   {
      $refKey = $controller . '::' . $method;

      if (!isset(self::$reflectionCache[$refKey])) {
         try {
            //code...
            $ref = new ReflectionMethod($controller, $method);
            $paramsMeta = [];
            foreach ($ref->getParameters() as $p) {
               $type = $p->getType();
               $meta = [
                  'name' => $p->getName(),
                  'isVariadic' => $p->isVariadic(),

                  'hasDefault' => $p->isDefaultValueAvailable(),
                  'default' => $p->isDefaultValueAvailable() ? $p->getDefaultValue() : null,
                  'type' => null,
                  'isUnion' => false,
                  'types' => [],
                  'allowsNull' => $type ? (method_exists($type, 'allowsNull') ? $type->allowsNull() : true) : true
               ];

               if ($type) {
                  if (class_exists('\\ReflectionNamedType') && $type instanceof \ReflectionNamedType) {
                     $meta['type'] = $type->getName();
                  } elseif (class_exists('\\ReflectionUnionType') && $type instanceof \ReflectionUnionType) {
                     $meta['isUnion'] = true;
                     foreach ($type->getTypes() as $t) {
                        if ($t instanceof \ReflectionNamedType) {
                           $meta['types'][] = $t->getName();
                        }
                     }
                  }
               }

               $paramsMeta[] = $meta;
            }

            self::$reflectionCache[$refKey] = ['ref' => $ref, 'meta' => $paramsMeta];
         } catch (\Throwable $th) {
            // slog('REF:', $th);
            // abort(
            //    500,
            //    "Call to undefined method '{$controller}::{$method}()'",
            //    CRITICAL
            // );
         }
      };

      return self::$reflectionCache[$refKey] ?? [];
   }

   /**
    * Build argument list for invokeArgs using metadata and route params
    */
   private function buildArgs(array $metaData, array $routeParams): array
   {
      if (empty($metaData)) return [];
      // slog($metaData['ref']->getName());
      $declaringName = $metaData['ref']->getDeclaringClass();

      $controller = $declaringName->getName();
      $method = $metaData['ref']->getName();
      $paramsMeta = $metaData['meta'];


      $args = [];
      $paramCount = count($paramsMeta);

      for ($i = 0; $i < $paramCount; $i++) {
         $meta = $paramsMeta[$i];

         if ($meta['isVariadic']) {
            $remaining = array_slice($routeParams, $i);
            foreach ($remaining as $r) {
               $args[] = $r;
            }
            break;
         }

         $hasValue = array_key_exists($i, $routeParams);

         if (!$hasValue) {
            if ($meta['hasDefault']) {
               $args[] = $meta['default'];
            } else {
               abort(
                  404,
                  "Missing required parameter '{$meta['name']}' in method '{$controller}::{$method}'",
                  $meta
               );
            }
            continue;
         }

         $value = $routeParams[$i];

         if ($meta['isUnion']) {
            $passed = false;
            foreach ($meta['types'] as $t) {
               if (in_array($t, ['int', 'integer', 'float', 'string', 'bool', 'array'])) {
                  $validated = $this->validateScalarType($value, $t);
                  if ($validated !== null) {
                     $args[] = $validated;
                     $passed = true;
                     break;
                  }
               } else {
                  if (is_object($value) && $value instanceof $t) {
                     $args[] = $value;
                     $passed = true;
                     break;
                  }
               }
            }
            if (!$passed) {
               abort(404, "Invalid type for parameter '{$meta['name']}'. Expected one of: " . implode('|', $meta['types']));
            }
            continue;
         }

         if ($meta['type']) {
            $t = $meta['type'];
            if (in_array($t, ['int', 'integer', 'float', 'string', 'bool', 'array'])) {
               $validated = $this->validateScalarType($value, $t);
               if ($validated === null) {
                  abort(
                     404,
                     "Invalid type for parameter '{$meta['name']}'. Expected '{$t}' type.",
                     [
                        'parameter' => $meta['name'],
                        'given' => $value
                     ]
                  );
               }
               $args[] = $validated;
               continue;
            }

            if (!is_object($value) || !($value instanceof $t)) {
               abort(404, "Parameter '{$meta['name']}' must be instance of {$t}", ['parameter' => $meta['name'], 'given_type' => gettype($value)]);
            }
            $args[] = $value;
            continue;
         }

         $args[] = $value;
      }

      $consumed = count($args);
      if (count($routeParams) > $consumed && !($paramCount > 0 && $paramsMeta[$paramCount - 1]['isVariadic'])) {
         abort(400, "Too many parameters provided to {$controller}::{$method}");
      }

      return $args;
   }

   /**
    * Invoke controller method safely and return the result
    */
   private function invokeControllerMethod(ReflectionMethod $ref, array $args)
   {
      try {
         $declaringName = $ref->getDeclaringClass();
         $controller = $declaringName->getName();
         $controllerInstance = new $controller();

         return $ref->invokeArgs($controllerInstance, $args);
      } catch (\Throwable $e) {
         if (function_exists('config') && config('MODE') === 'development') {
            throw $e;
         }
         abort(500, 'Internal server error');
      }
   }

   private function validateScalarType($value, $expected)
   {
      switch ($expected) {

         case 'int':
         case 'integer':
            return is_numeric($value) ? (int)$value : null;

         case 'float':
            return is_numeric($value) ? (float)$value : null;

         case 'string':
            return is_scalar($value) ? (string)$value : null;

         case 'bool':
            if (is_bool($value)) return $value;

            $map = ['1' => true, '0' => false, 'true' => true, 'false' => false];
            $lower = strtolower((string)$value);
            return $map[$lower] ?? null;

         case 'array':
            return is_array($value) ? $value : null;
      }

      return null;
   }
};
