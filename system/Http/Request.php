<?php
class Request
{
   private static $instance = null;
   private $prop = [];
   public $elementUri = [];
   private $inputCache = [];
   private $parsedBody = null;
   private $parsedQuery = null;
   private $headers = [];
   private $parsedJson = null;
   private $sanitize = true;

   // Middleware properties - accessible via magic __get
   public $cors = null;
   public $auth = null;
   public $throttle = null;
   public $csrf = null;
   public $file = null;

   // Timing tracking
   private $requestStartTime = null;
   private $deviceInfo = null;

   private function __construct() {}
   public static function init(array $options = []): Request
   {
      if (self::$instance === null) {
         self::$instance = new self();
         ////////////////////////////
         // Request initialization //
         ////////////////////////////

         function versioning($version)
         {
            if ($version === 'v0') {
               return APP_PATH;
            }
            // slog('XXX', config('VERSIONS'));
            try {
               // $versions = json_decode(config('VERSIONS'));
               $versions = config('VERSIONS');
               if (in_array($version, $versions)) {
                  return APP_PATH . $version . DIRECTORY_SEPARATOR;
               };
               //code...
            } catch (\Throwable $th) {
               //throw $th;
            }
         }


         self::attr('uid', date('Ymd-His-') . substr(md5(uniqid()), 0, 5));

         // Timing: capture request start time
         self::$instance->requestStartTime = microtime(true);

         // REQ-TYPE
         $headers = function_exists('getallheaders') ? getallheaders() : [];
         if (php_sapi_name() === 'cli' || defined('STDIN')) {
            // Deteksi browser cli
            self::attr('request_type', 'cli');
         } elseif (strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'curl') !== false) {
            // Deteksi browser curl
            self::attr('request_type', 'curl');
         } elseif ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($headers['Sec-Fetch-Mode']) && strtolower($headers['Sec-Fetch-Mode']) === 'cors')
         ) {
            // Deteksi AJAX
            self::attr('request_type', 'ajax');
         } elseif (!empty($_SERVER['HTTP_USER_AGENT'])) {
            // Deteksi browser biasa
            self::attr('request_type', 'browser');
         } else {
            self::attr('request_type', 'unknown');
         };

         self::attr('request_method', $_SERVER['REQUEST_METHOD'] ?? 'unknown');
         self::attr('url', self::captureUrlAccess());
         self::attr('agent', self::detectAgent());

         // deteksi PORT
         // Jika tidak ada, tentukan berdasarkan scheme
         $url_parse = parse_url(self::attr('url'));
         $defaultPorts = ['http'  => 80, 'https' => 443, 'ftp'   => 21, 'ftps'  => 990, 'ssh'   => 22, 'ws'    => 80, 'wss'   => 443,];
         self::attr('PORT', $defaultPorts[$url_parse['scheme'] ?? null] ?? null);
         self::attr('PROTOCOL', $url_parse['scheme'] ?? 'unknown');

         $instance = self::$instance;

         // http://localhost/sun/
         // http://localhost/sun/home/index
         // http://localhost/sun/home/index/1/2/3
         // http://localhost/sun/api/v0/residents/person
         // http://localhost/sun/file/images/gambar.jpg
         $path = $instance->path();

         $explode = explode('/', $path);
         $seg_0 = array_shift($explode); // array -> 0
         if ($seg_0 === 'api') {
            $seg_1 = array_shift($explode); // array -> 1
            $seg_2 = array_shift($explode); // array -> 2
            $api_version = isVersionString($seg_1 ?? "");
            $seg_3 = "";
            if ($api_version) {
               $seg_3 =  array_shift($explode); // array -> 3
            };
            $api_ctrl = $api_version ? $seg_2 : $seg_1;
            $api_mtd = $api_version ? ($api_ctrl ? $seg_3 : $seg_2) : $seg_2;
            $api_version = $api_version ? $seg_1 : 'v0';
            $path_src = versioning($api_version);
            self::attr('SOURCE', $path_src);
            self::attr('API', $api_version);
            self::$instance->elementUri = array(
               'controller' => $api_ctrl,
               'method' => $api_mtd ?? 'index',
               'params' => array_slice($explode, 0)
            );
         } elseif ($seg_0 === 'file') {
            $path = implode(DIRECTORY_SEPARATOR, $explode);
            $candidate = DISK_PATH  . $path;
            // sanitasi dasar
            $candidate = str_replace("\0", '', $candidate);
            $candidate = preg_replace('#\.\.(\\\\|/)#', '', $candidate);
            $foundFile = null;
            if (is_file($candidate)) {
               $foundFile = $candidate;
            } else {

               // coba cari dengan ekstensi yang diperbolehkan
               $baseDir = dirname($candidate);
               $fileName = basename($candidate);
               // EXPANDED: support lebih banyak file types
               $allowedExts = [
                  // Images
                  'jpg',
                  'jpeg',
                  'png',
                  'gif',
                  'webp',
                  'svg',
                  'bmp',
                  'ico',
                  // Documents
                  'pdf',
                  'txt',
                  'doc',
                  'docx',
                  'xls',
                  'xlsx',
                  'ppt',
                  'pptx',
                  // Archives
                  'zip',
                  'rar',
                  '7z',
                  'tar',
                  'gz',
                  // Audio
                  'mp3',
                  'wav',
                  'flac',
                  'aac',
                  'ogg',
                  'm4a',
                  // Video
                  'mp4',
                  'avi',
                  'mkv',
                  'mov',
                  'wmv',
                  'webm',
                  'flv',
                  '3gp'
               ];

               foreach ($allowedExts as $ext) {
                  $cand = $baseDir . DIRECTORY_SEPARATOR . $fileName . '.' . $ext;
                  if (is_file($cand)) {
                     $foundFile = $cand;
                     break;
                  }
               }
            }
            $file_path = dirname($foundFile) . DIRECTORY_SEPARATOR;
            self::attr('SOURCE', $file_path);
            self::attr('file_server', array(
               'file_path' => $file_path,
               'file_name' => basename($foundFile)
            ));
         } else {
            $seg_1 = array_shift($explode); // array -> 1
            $appVersion = function_exists('config') ? config('APP_VERSION') : 'v0';
            self::attr('source', versioning($appVersion));
            self::$instance->elementUri = array(
               'controller' => $seg_0 ?? '',
               'method' => $seg_1 ?? 'index',
               'params' => array_slice($explode, 0)
            );
         };
      }

      // slog('PROP', $instance->prop);
      // Apply options (backwards compatible)
      if (!empty($options)) {
         if (isset($options['sanitize'])) {
            self::$instance->setSanitize((bool)$options['sanitize']);
         }
      }

      return self::$instance;
   }

   /**
    * Enable or disable automatic sanitization of input values
    */
   public function setSanitize(bool $enabled = true)
   {
      $this->sanitize = $enabled;
      // Clear cache so next all() call honors new behavior
      $this->inputCache = [];
   }

   private static function captureUrlAccess()
   {
      if (php_sapi_name() === 'cli') {
         return 'CLI_COMMAND';
      };

      // 1. Protocol
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         ? 'https'
         : 'http';

      // 2. Host (domain)
      $host = $_SERVER['HTTP_HOST'];

      // ------------------------------------------
      // 3. Detect BASE PATH (tanpa folder public)
      // ------------------------------------------

      // script path: /sun/public/index.php
      $scriptPath = $_SERVER['SCRIPT_NAME'];

      // folder project: /sun/public
      $scriptDir = rtrim(dirname($scriptPath), '/');

      // Hapus folder public jika ada di ujung
      // menjadi: /sun
      $basePath = preg_replace('#/(public|www|htdocs)$#i', '', $scriptDir);

      // REQUEST_URI misalnya:
      // /sun/DefaultApp/index


      $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
      parse_str($_SERVER['QUERY_STRING'], $params);
      self::attr('query', $params);

      // Hitung URI dengan menghilangkan basePath
      if ($basePath !== '') {
         $uri = trim(substr($requestPath, strlen($basePath)), '/');
      } else {
         $uri = trim($requestPath, '/');
      }
      // Format final HOST (tanpa trailing slash)
      $fullHost = trim($host . $basePath, '/');
      self::attr('host', $fullHost);
      self::attr('path', $uri);

      return $protocol . '://' . $fullHost . '/' . $uri;
   }

   private static function attr($key, $value = null)
   {
      static $UNDEFINED = null;
      if ($UNDEFINED === null) {
         $UNDEFINED = new \stdClass();
      }

      $key = strtoupper($key);
      if (func_num_args() === 1) {
         return self::$instance->prop[$key] ?? null;
      }
      self::$instance->prop[$key] = $value;
      return true;
   }

   private static function detectAgent(): array
   {
      $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
      // Deteksi OS
      $os = 'Unknown OS';
      $osArray = [
         '/windows nt 10/i'      => 'Windows 10',
         '/windows nt 6.3/i'     => 'Windows 8.1',
         '/windows nt 6.2/i'     => 'Windows 8',
         '/windows nt 6.1/i'     => 'Windows 7',
         '/windows nt 6.0/i'     => 'Windows Vista',
         '/windows nt 5.1/i'     => 'Windows XP',
         '/macintosh|mac os x/i' => 'Mac OS X',
         '/mac_powerpc/i'        => 'Mac OS 9',
         '/linux/i'              => 'Linux',
         '/ubuntu/i'             => 'Ubuntu',
         '/iphone/i'             => 'iOS (iPhone)',
         '/ipad/i'               => 'iOS (iPad)',
         '/android/i'            => 'Android',
      ];
      foreach ($osArray as $regex => $value) {
         if (preg_match($regex, $userAgent)) {
            $os = $value;
            break;
         }
      }

      // Deteksi Browser
      $browser = 'Unknown Browser';
      $browserArray = [
         '/msie/i'       => 'Internet Explorer',
         '/trident/i'    => 'Internet Explorer',
         '/firefox/i'    => 'Firefox',
         '/edg/i'        => 'Microsoft Edge',
         '/chrome/i'     => 'Google Chrome',
         '/safari/i'     => 'Safari',
         '/opera/i'      => 'Opera',
         '/opr/i'        => 'Opera',
      ];
      foreach ($browserArray as $regex => $value) {
         if (preg_match($regex, $userAgent)) {
            $browser = $value;
            break;
         }
      }

      return [
         'browser' => $browser,
         'os'      => $os
      ];
   }

   ////////////////////////////
   /////// public method //////
   ////////////////////////////



   /**
    * Summary of getClientIp
    * @return string - IP address of the client
    */
   public function getClientIp()
   {
      if (php_sapi_name() === 'cli') {
         return 'CLI';
      }

      $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

      foreach ($keys as $key) {
         if (!empty($_SERVER[$key])) {
            $ipList = explode(',', $_SERVER[$key]);
            foreach ($ipList as $ip) {
               $ip = trim($ip);
               // Validasi IP versi 4 atau 6
               if (filter_var($ip, FILTER_VALIDATE_IP)) {
                  return $ip;
               }
            }
         }
      }
      // Fallback: tetap kembalikan REMOTE_ADDR meskipun tidak lolos validasi
      return $_SERVER['REMOTE_ADDR'] ?? 'IP tidak terdeteksi';
   }
   public function getId()
   {
      return self::attr('uid') ?? '';
   }
   public function getProtocol()
   {
      return self::attr('PROTOCOL') ?? '';
   }
   public function getHost()
   {
      return self::attr('host') ?? '';
   }
   public function getUserAgent()
   {
      return self::attr('agent') ?? [];
   }

   ////////////////////////////
   // INPUT HANDLING /////////
   ////////////////////////////

   /**
    * Get input value (GET, POST, or parsed body)
    * @param string $key - input key, can use dot notation (e.g. 'user.name')
    * @param mixed $default - default value if not found
    * @return mixed
    */
   public function input(string $key = '', $default = null)
   {
      if (empty($key)) {
         return $this->all();
      }

      $all = $this->all();

      // Support dot notation (user.name => user['name'])
      if (strpos($key, '.') !== false) {
         return $this->getDotNotation($all, $key, $default);
      }

      return $all[$key] ?? $default;
   }

   /**
    * Get all input data (GET + POST + parsed body)
    * @return array
    */
   public function all(): array
   {
      if (!empty($this->inputCache)) {
         return $this->inputCache;
      }

      $data = [];

      // GET parameters
      $data = array_merge($data, $_GET ?? []);

      // POST parameters
      $data = array_merge($data, $_POST ?? []);

      // Parsed JSON body (merge when present and not already in POST)
      if (empty($_POST) && $this->isJson()) {
         $parsed = $this->json(true);
         if (is_array($parsed)) {
            $data = array_merge($data, $parsed);
         }
      }

      // Sanitize all inputs recursively and cache if enabled
      if ($this->sanitize) {
         $sanitized = $this->sanitizeArrayRecursive($data);
         $this->inputCache = $sanitized;
      } else {
         $this->inputCache = $data;
      }
      return $this->inputCache;
   }

   /**
    * Get GET parameters
    * @param string $key - key to retrieve, empty = all
    * @param mixed $default - default value
    * @return mixed
    */
   public function get(string $key = '', $default = null)
   {
      if (empty($key)) {
         return $_GET ?? [];
      }
      return $_GET[$key] ?? $default;
   }

   /**
    * Get POST parameters
    * @param string $key - key to retrieve, empty = all
    * @param mixed $default - default value
    * @return mixed
    */
   public function post(string $key = '', $default = null)
   {
      if (empty($key)) {
         return $_POST ?? [];
      }
      return $_POST[$key] ?? $default;
   }

   /**
    * Get query string (parsed URL query)
    * @param string $key - key to retrieve, empty = all
    * @param mixed $default - default value
    * @return mixed
    */
   public function query(string $key = '', $default = null)
   {
      $query = self::attr('query');
      if (empty($key)) {
         return $query;
      }

      return $query[$key] ?? $default;
   }

   /**
    * Get parsed request body (for PUT, PATCH, DELETE)
    * @param string $key - key to retrieve, empty = all
    * @param mixed $default - default value
    * @return mixed
    */
   public function body(string $key = '', $default = null)
   {
      if ($this->parsedBody === null) {
         $input = file_get_contents('php://input');
         // If JSON, decode into parsedBody as array
         if ($this->isJson()) {
            $decoded = $this->json(true);
            $this->parsedBody = is_array($decoded) ? $decoded : [];
         } else {
            parse_str($input, $this->parsedBody);
            if (empty($this->parsedBody)) {
               $this->parsedBody = [];
            }
         }
      }

      if (empty($key)) {
         return $this->parsedBody;
      }

      return $this->parsedBody[$key] ?? $default;
   }

   /**
    * Get JSON parsed body
    * @param bool $assoc - return as associative array (true) or object (false)
    * @return mixed
    */
   public function json(bool $assoc = true)
   {
      if ($this->parsedJson !== null) {
         return $this->parsedJson;
      }

      $input = file_get_contents('php://input');
      if (empty($input)) {
         $this->parsedJson = $assoc ? [] : new \stdClass();
         return $this->parsedJson;
      }

      $decoded = json_decode($input, $assoc);
      $this->parsedJson = $decoded === null ? ($assoc ? [] : new \stdClass()) : $decoded;
      return $this->parsedJson;
   }

   ////////////////////////////
   // REQUEST METHODS ////////
   ////////////////////////////

   public function getMethod()
   {
      return self::attr('request_method') ?? 'GET';
   }

   /**
    * Check if request method is GET
    */
   public function isGet(): bool
   {
      return $this->getMethod() === 'GET';
   }

   /**
    * Check if request method is POST
    */
   public function isPost(): bool
   {
      return $this->getMethod() === 'POST';
   }

   /**
    * Check if request method is PUT
    */
   public function isPut(): bool
   {
      return $this->getMethod() === 'PUT';
   }

   /**
    * Check if request method is DELETE
    */
   public function isDelete(): bool
   {
      return $this->getMethod() === 'DELETE';
   }

   /**
    * Check if request method is PATCH
    */
   public function isPatch(): bool
   {
      return $this->getMethod() === 'PATCH';
   }

   /**
    * Check if request method is HEAD
    */
   public function isHead(): bool
   {
      return $this->getMethod() === 'HEAD';
   }

   /**
    * Check if request method is OPTIONS
    */
   public function isOptions(): bool
   {
      return $this->getMethod() === 'OPTIONS';
   }

   /**
    * Check if request method matches given methods
    */
   public function isMethod(string ...$methods): bool
   {
      $currentMethod = $this->getMethod();
      foreach ($methods as $method) {
         if (strtoupper($method) === $currentMethod) {
            return true;
         }
      }
      return false;
   }

   ////////////////////////////
   // CONTENT TYPE //////////
   ////////////////////////////

   /**
    * Get content type from request headers
    */
   public function getContentType(): string
   {
      $headers = getallheaders();
      if (isset($headers['Content-Type'])) {
         $type = $headers['Content-Type'];
         // Extract type without charset (e.g. "application/json; charset=utf-8" => "application/json")
         return strtolower(trim(explode(';', $type)[0]));
      }
      return '';
   }

   /**
    * Check if request is JSON
    */
   public function isJson(): bool
   {
      $type = $this->getContentType();
      return strpos($type, 'application/json') !== false ||
         strpos($type, 'application/ld+json') !== false;
   }

   /**
    * Check if request is form data
    */
   public function isFormData(): bool
   {
      $type = $this->getContentType();
      return strpos($type, 'application/x-www-form-urlencoded') !== false ||
         strpos($type, 'multipart/form-data') !== false;
   }

   ////////////////////////////
   // REQUEST TYPE //////////
   ////////////////////////////

   /**
    * Check if request is AJAX
    */
   public function isAjax(): bool
   {
      return $this->type() === 'ajax';
   }

   /**
    * Check if request is from browser
    */
   public function isBrowser(): bool
   {
      return $this->type() === 'browser';
   }

   /**
    * Check if request is HTTPS/SSL
    */
   public function isSecure(): bool
   {
      return $this->getProtocol() === 'https';
   }

   public function isApi()
   {
      return self::attr('api');
   }
   public function isCli()
   {
      return $this->type() === 'cli';
   }

   public function isFileServer()
   {
      return self::attr('file_server') ?? false;
   }

   public function type()
   {
      return self::attr('request_type');
   }
   ////////////////////////////
   // HEADERS ///////////////
   ////////////////////////////

   /**
    * Get specific header value
    */
   public function header(string $key, $default = null)
   {
      if (empty($this->headers)) {
         $this->headers = function_exists('getallheaders') ? getallheaders() : [];
      }

      if (isset($this->headers[$key])) {
         return $this->headers[$key];
      }

      // Try case-insensitive search
      foreach ($this->headers as $headerKey => $value) {
         if (strcasecmp($headerKey, $key) === 0) {
            return $value;
         }
      }

      return $default;
   }

   /**
    * Get all headers
    */
   public function headers(): array
   {
      if (empty($this->headers)) {
         $this->headers = function_exists('getallheaders') ? getallheaders() : [];
      }
      return $this->headers;
   }

   /**
    * Check if header exists
    */
   public function hasHeader(string $key): bool
   {
      return $this->header($key) !== null;
   }

   /**
    * Get Accept header
    */
   public function getAccept(): string
   {
      return $this->header('Accept', 'text/html');
   }

   /**
    * Get Authorization header
    */
   public function getAuthorization(): ?string
   {
      return $this->header('Authorization');
   }

   /**
    * Get Bearer token from Authorization header
    */
   public function getBearerToken(): ?string
   {
      $auth = $this->getAuthorization();
      if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
         return $matches[1];
      }
      return null;
   }

   /**
    * Get request Origin header (if present)
    */
   public function getOrigin(): ?string
   {
      // Prefer canonical header retrieval, fallback to SERVER key
      $origin = $this->header('Origin', null);
      return $origin ?? ($_SERVER['HTTP_ORIGIN'] ?? null);
   }

   /**
    * Detect whether this request is a CORS preflight request
    */
   public function isPreflight(): bool
   {
      // Preflight is OPTIONS request with Access-Control-Request-Method header
      return $this->isOptions() && $this->hasHeader('Access-Control-Request-Method');
   }

   ////////////////////////////
   // URL & PATH UTILITIES //
   ////////////////////////////

   /**
    * Get full URL
    */
   public function fullUrl(): string
   {
      return self::attr('url') ?? '';
   }

   /**
    * Get URL with given path
    */
   public function url(string $path = ''): string
   {
      $base = base_url();
      if (empty($path)) {
         return $base;
      }
      return $base . '/' . ltrim($path, '/');
   }

   /**
    * Get URL with given path
    */
   public function getUri(): array
   {
      // return $this->elementUri;
      return [
         'controller' => $this->elementUri['controller'],
         'method' => $this->elementUri['method'] . ($this->isApi() ? '_' . strtolower($this->getMethod()) : ''),
         'params' => $this->elementUri['params'],
      ];
   }

   /**
    * Get current path
    */
   public function path($index = null, $default = null)
   {
      $path = self::attr('path') ?? '';
      if (is_null($index)) {
         return $path;
      }
      $path = explode('/', $path);
      return $path[$index] ?? $default;
   }

   /**
    * Get all URI segments
    */
   public function segments(): array
   {
      $path = $this->path();
      if (empty($path)) {
         return [];
      }
      return array_filter(explode('/', $path), function ($v) {
         return !empty($v);
      });
   }

   /**
    * Get request source
    * @return string
    */
   public function getSource()
   {
      return self::attr('source') ?? '';
   }

   ////////////////////////////
   // FILE UPLOADS //////////
   ////////////////////////////

   /**
    * Get uploaded file
    */
   public function file(string $name): ?array
   {
      if (!isset($_FILES[$name])) {
         return null;
      }

      return $_FILES[$name];
   }

   /**
    * Check if file was uploaded
    */
   public function hasFile(string $name): bool
   {
      return isset($_FILES[$name]) && $_FILES[$name]['error'] === UPLOAD_ERR_OK;
   }

   /**
    * Get all uploaded files
    */
   public function getFiles(): array
   {
      return $_FILES ?? [];
   }

   /**
    * Get uploaded file size (in bytes)
    */
   public function getFileSize(string $name): ?int
   {
      $file = $this->file($name);
      return $file ? $file['size'] ?? null : null;
   }

   /**
    * Get uploaded file type (MIME)
    */
   public function getFileMimeType(string $name): ?string
   {
      $file = $this->file($name);
      return $file ? $file['type'] ?? null : null;
   }

   /**
    * Validate uploaded file against rules.
    * Rules supported: 'max' (bytes), 'mimes' (array of mime types), 'extensions' (array of allowed extensions)
    * Returns true when valid, or an array of error messages when invalid.
    */
   public function validateUpload(string $name, array $rules = [])
   {
      $errors = [];

      if (!isset($_FILES[$name])) {
         $errors[] = 'No file uploaded.';
         return $errors;
      }

      $file = $_FILES[$name];

      // Check PHP upload error
      $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
      if ($err !== UPLOAD_ERR_OK) {
         $errors[] = $this->fileError($name) ?? 'Upload error';
         return $errors;
      }

      // Validate tmp file
      if (!$this->isValidUploadedFile($file)) {
         $errors[] = 'Temporary uploaded file invalid.';
         return $errors;
      }

      // Size checks
      if (isset($rules['max'])) {
         $max = (int)$rules['max'];
         if (($file['size'] ?? 0) > $max) {
            $errors[] = 'File exceeds maximum allowed size of ' . $max . ' bytes.';
         }
      }

      // Check global upload_max_filesize
      $globalMax = $this->getUploadMaxSizeBytes();
      if ($globalMax > 0 && ($file['size'] ?? 0) > $globalMax) {
         $errors[] = 'File exceeds server upload_max_filesize (' . $globalMax . ' bytes).';
      }

      // MIME type
      if (!empty($rules['mimes']) && is_array($rules['mimes'])) {
         $allowed = array_map('strtolower', $rules['mimes']);
         $mime = strtolower($file['type'] ?? '');
         if (!in_array($mime, $allowed)) {
            $errors[] = 'Invalid file MIME type: ' . $mime;
         }
      }

      // Extension check
      if (!empty($rules['extensions']) && is_array($rules['extensions'])) {
         $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
         $allowedExts = array_map('strtolower', $rules['extensions']);
         if (!in_array($ext, $allowedExts)) {
            $errors[] = 'Invalid file extension: ' . $ext;
         }
      }

      return empty($errors) ? true : $errors;
   }

   /**
    * Return human readable file upload error or null if OK
    */
   public function fileError(string $name): ?string
   {
      if (!isset($_FILES[$name])) {
         return 'No file uploaded.';
      }
      $code = $_FILES[$name]['error'] ?? UPLOAD_ERR_NO_FILE;
      $map = [
         UPLOAD_ERR_OK => null,
         UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
         UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.',
         UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
         UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
         UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
         UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
         UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
      ];
      return $map[$code] ?? 'Unknown upload error.';
   }

   /**
    * Internal: check whether uploaded file is valid (handles CLI testing fallback)
    */
   private function isValidUploadedFile(array $file): bool
   {
      if (php_sapi_name() === 'cli') {
         // In CLI tests we allow synthetic uploads (tmp_name present)
         return !empty($file['tmp_name']);
      }
      return isset($file['tmp_name']) && is_uploaded_file($file['tmp_name']);
   }

   /**
    * Get php.ini upload_max_filesize in bytes
    */
   public function getUploadMaxSizeBytes(): int
   {
      $val = ini_get('upload_max_filesize');
      if (!$val) {
         return 0;
      }
      $val = trim($val);
      $last = strtolower($val[strlen($val) - 1]);
      $num = (int)$val;
      switch ($last) {
         case 'g':
            $num *= 1024;
            // no break
         case 'm':
            $num *= 1024;
            // no break
         case 'k':
            $num *= 1024;
      }
      return $num;
   }

   ////////////////////////////
   // VALIDATION HELPERS ////
   ////////////////////////////

   /**
    * Check if key exists and not empty
    */
   public function filled(string $key): bool
   {
      $value = $this->input($key);
      return !empty($value) && $value !== '';
   }

   /**
    * Check if key exists
    */
   public function has(string $key): bool
   {
      return isset($this->all()[$key]);
   }

   /**
    * Check if key does NOT exist
    */
   public function missing(string $key): bool
   {
      return !$this->has($key);
   }

   /**
    * Check if any of the keys exist and are filled
    */
   public function anyFilled(string ...$keys): bool
   {
      foreach ($keys as $key) {
         if ($this->filled($key)) {
            return true;
         }
      }
      return false;
   }

   /**
    * Check if all keys exist and are filled
    */
   public function allFilled(string ...$keys): bool
   {
      foreach ($keys as $key) {
         if (!$this->filled($key)) {
            return false;
         }
      }
      return true;
   }

   /**
    * Collect specific keys from input
    */
   public function collect(string ...$keys): array
   {
      $all = $this->all();
      $result = [];
      foreach ($keys as $key) {
         if (isset($all[$key])) {
            $result[$key] = $all[$key];
         }
      }
      return $result;
   }

   /**
    * Collect all except specific keys
    */
   public function except(string ...$keys): array
   {
      $all = $this->all();
      $exclude = array_flip($keys);
      return array_diff_key($all, $exclude);
   }

   /**
    * Only get specific keys
    */
   public function only(string ...$keys): array
   {
      return $this->collect(...$keys);
   }

   ////////////////////////////
   // HELPERS ///////////////
   ////////////////////////////

   /**
    * Get value using dot notation (e.g. user.profile.name)
    */
   private function getDotNotation(array $array, string $key, $default = null)
   {
      $keys = explode('.', $key);
      $value = $array;

      foreach ($keys as $k) {
         if (is_array($value) && isset($value[$k])) {
            $value = $value[$k];
         } else {
            return $default;
         }
      }

      return $value;
   }

   /**
    * Recursively sanitize array values (strings trimmed, tags removed)
    */
   private function sanitizeArrayRecursive(array $data): array
   {
      $result = [];
      foreach ($data as $k => $v) {
         $key = is_string($k) ? $this->sanitizeString($k) : $k;
         if (is_array($v)) {
            $result[$key] = $this->sanitizeArrayRecursive($v);
         } else {
            $result[$key] = $this->sanitizeValue($v);
         }
      }
      return $result;
   }

   /**
    * Sanitize scalar value
    */
   private function sanitizeValue($value)
   {
      if (is_string($value)) {
         // Remove control characters and null bytes, strip tags, trim
         $v = str_replace("\0", '', $value);
         $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v);
         $v = strip_tags($v);
         return trim($v);
      }
      if (is_numeric($value)) {
         // Keep numeric as-is (preserve string numeric)
         return $value;
      }
      return $value;
   }

   /**
    * Sanitize string keys
    */
   private function sanitizeString(string $s): string
   {
      $s = str_replace("\0", '', $s);
      return trim(strip_tags($s));
   }

   /**
    * Convert request to JSON array
    */
   public function toJson(): string
   {
      return json_encode($this->all());
   }

   /**
    * Convert request to array
    */
   public function toArray(): array
   {
      return $this->all();
   }

   ////////////////////////////
   // HEADER UTILITIES ENHANCEMENT //
   ////////////////////////////

   /**
    * Get Referer header
    */
   public function getReferer(): ?string
   {
      return $this->header('Referer', null);
   }

   /**
    * Get cookie value
    */
   public function getCookie(string $name, $default = null)
   {
      return $_COOKIE[$name] ?? $default;
   }

   /**
    * Get all cookies
    */
   public function getAllCookies(): array
   {
      return $_COOKIE ?? [];
   }

   /**
    * Check if cookie exists
    */
   public function hasCookie(string $name): bool
   {
      return isset($_COOKIE[$name]);
   }

   /**
    * Get Accept-Language header
    */
   public function getAcceptLanguage(): ?string
   {
      return $this->header('Accept-Language', null);
   }

   /**
    * Get User-Agent header
    */
   public function getUserAgentString(): ?string
   {
      return $this->header('User-Agent', null);
   }

   /**
    * Get X-Forwarded-For header (useful with proxies)
    */
   public function getXForwardedFor(): ?string
   {
      return $this->header('X-Forwarded-For', null);
   }

   ////////////////////////////
   // REQUEST TIMING/PERFORMANCE //
   ////////////////////////////

   /**
    * Get request start time (microtime)
    */
   public function startTime(): float
   {
      return $this->requestStartTime ?? microtime(true);
   }

   /**
    * Get duration in milliseconds since request started
    */
   public function duration(bool $asMs = true): float
   {
      $elapsed = microtime(true) - $this->startTime();
      return $asMs ? ($elapsed * 1000) : $elapsed;
   }

   /**
    * Get duration in seconds
    */
   public function durationSeconds(): float
   {
      return $this->duration(false);
   }

   ////////////////////////////
   // DEVICE DETECTION ENHANCEMENT //
   ////////////////////////////

   /**
    * Check if request is from mobile device
    */
   public function isMobile(): bool
   {
      $userAgent = $this->getUserAgentString() ?? '';
      $mobilePatterns = [
         '/mobile/i',
         '/android/i',
         '/iphone/i',
         '/ipod/i',
         '/ipad/i',
         '/windows phone/i',
         '/blackberry/i',
         '/opera mini/i',
         '/webos/i',
      ];

      foreach ($mobilePatterns as $pattern) {
         if (preg_match($pattern, $userAgent)) {
            return true;
         }
      }
      return false;
   }

   /**
    * Check if request is from tablet device
    */
   public function isTablet(): bool
   {
      $userAgent = $this->getUserAgentString() ?? '';
      $tabletPatterns = [
         '/ipad/i',
         '/tablet/i',
         '/kindle/i',
         '/playbook/i',
         '/windows ce/i',
         '/android/i', // Some android devices are tablets
      ];

      foreach ($tabletPatterns as $pattern) {
         if (preg_match($pattern, $userAgent)) {
            return true;
         }
      }
      return false;
   }

   /**
    * Check if request is from bot/crawler
    */
   public function isBot(): bool
   {
      $userAgent = $this->getUserAgentString() ?? '';
      $botPatterns = [
         '/bot/i',
         '/crawler/i',
         '/spider/i',
         '/scraper/i',
         '/curl/i',
         '/wget/i',
         '/python/i',
         '/java(?!\s)/i',
         '/perl/i',
         '/php/i',
         '/ruby/i',
         '/node/i',
         '/googlebot/i',
         '/bingbot/i',
         '/yandexbot/i',
         '/baiduspider/i',
         '/facebookexternalhit/i',
         '/twitterbot/i',
      ];

      foreach ($botPatterns as $pattern) {
         if (preg_match($pattern, $userAgent)) {
            return true;
         }
      }
      return false;
   }

   /**
    * Get device type (mobile, tablet, desktop, bot, or unknown)
    */
   public function getDeviceType(): string
   {
      if ($this->isBot()) {
         return 'bot';
      }
      if ($this->isMobile()) {
         return 'mobile';
      }
      if ($this->isTablet()) {
         return 'tablet';
      }
      if ($this->isBrowser()) {
         return 'desktop';
      }
      return 'unknown';
   }
};
