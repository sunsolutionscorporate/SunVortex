<?php

/**
 * Response Handler — Flexible, Chainable & High Performance
 * 
 * Fitur:
 * - Multiple content types (HTML, JSON, XML, CSV, Plain, Custom)
 * - File streaming dengan HTTP Range support (video/audio seeking)
 * - Smart caching dengan ETag & Last-Modified
 * - Compression support (gzip, deflate)
 * - Middleware pipeline
 * - Error handling & JSON error format
 * - Security headers (CORS, CSP, HSTS, etc)
 * - Lazy loading & performance optimized
 * 
 * Usage:
 *   Response::html(view('home'))->send();
 *   Response::json(['status' => 'ok'])->cacheFor(3600)->send();
 *   Response::download('/path/file.pdf')->send();
 *   Response::status(404)->error('Not found')->send();
 *   Response::success(['data' => $data])->send();
 */
class Response
{
   private static $instance = null;
   private static $mimeTypes = null; // Lazy load MIME types

   private $headers = [];
   private $cookies = [];
   private $content; // Any type - string, object, array
   private $info = [];
   private $meta = [];
   private $errors = [];
   private $httpCode = 200;
   private $responseType = 'html';
   private $filePath = '';
   private $mimeType = '';
   private $jsonOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
   private $middleware = [];
   private $compression = null; // null = auto detect, false = disable, 'gzip' or 'deflate'
   private $cacheControl = null;
   private $etag = null;
   private $lastModified = null;
   private $chunkSize = 8192; // For streaming
   private $headersSent = false;
   private $securityHeaders = [];

   private function __construct() {}

   /**
    * Reset singleton (berguna untuk CLI/testing)
    */
   public static function reset(): void
   {
      self::$instance = null;
   }

   /**
    * Create atau get singleton instance
    */
   public static function getInstance()
   {
      if (self::$instance === null) {
         self::$instance = new self();
         self::$instance->headers['X-Powered-By'] = 'SunVortex';
         // Add security headers by default
         self::$instance->secureHeaders();
      }
      return self::$instance;
   }

   /**
    * Add security headers (CORS, CSP, HSTS, X-Frame-Options, etc)
    */
   public function secureHeaders()
   {
      // Prevent clickjacking
      $this->header('X-Frame-Options', 'SAMEORIGIN');

      // Prevent MIME sniffing
      $this->header('X-Content-Type-Options', 'nosniff');

      // Enable XSS protection (legacy, mostly ignored by modern browsers)
      $this->header('X-XSS-Protection', '1; mode=block');

      // Referrer policy
      $this->header('Referrer-Policy', 'strict-origin-when-cross-origin');

      // Feature policy / Permissions policy
      $this->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

      return $this;
   }

   /**
    * Set HTTP response code with optional message
    */
   public static function status(int $code, string $message = '')
   {
      $instance = self::getInstance();
      $instance->httpCode = $code;
      $instance->info = httpInfo($code);

      if (class_exists('Request')) {
         try {
            $request = Request::init();
            if ($request && method_exists($request, 'getId')) {
               $instance->info['request_id'] = $request->getId();
            }
         } catch (Exception $e) {
            // Ignore request initialization errors
         }
      }

      $instance->info['code'] = $code;

      if (!empty($message)) {
         $instance->info['message'] = $message;
      }

      http_response_code($code);
      return $instance;
   }

   /**
    * Quick success response dengan data
    */
   public static function success($data, int $code = 200, string $message = 'Success')
   {
      return self::status($code, $message)->json($data);
   }

   /**
    * Quick error response
    */
   public static function error(string $message, int $code = 400, $errors = null)
   {
      $instance = self::status($code, $message);
      $response = ['message' => $message];

      if ($errors !== null) {
         $response['errors'] = $errors;
      }

      return $instance->json($response);
   }

   /**
    * Validation error response
    */
   public static function validationError($errors, string $message = 'Validation failed')
   {
      return self::status(422, $message)
         ->json(['errors' => $errors]);
   }

   /**
    * Not found response
    */
   public static function notFound(string $message = 'Resource not found')
   {
      return self::status(404, $message)->json(['message' => $message]);
   }

   /**
    * Unauthorized response
    */
   public static function unauthorized(string $message = 'Unauthorized')
   {
      return self::status(401, $message)->json(['message' => $message]);
   }

   /**
    * Forbidden response
    */
   public static function forbidden(string $message = 'Forbidden')
   {
      return self::status(403, $message)->json(['message' => $message]);
   }

   /**
    * Server error response
    */
   public static function serverError(string $message = 'Internal server error', $errors = null)
   {
      $response = ['message' => $message];
      if ($errors !== null) {
         $response['errors'] = $errors;
      }
      return self::status(500)->json($response);
   }

   /**
    * Set custom header
    */
   public function header(string $key, string $value)
   {
      $this->headers[$key] = $value;
      return $this;
   }

   /**
    * Add multiple headers at once
    */
   public function headers(array $headers)
   {
      foreach ($headers as $key => $value) {
         $this->headers[$key] = $value;
      }
      return $this;
   }

   /**
    * Set cache control header
    * @param int|null $seconds TTL dalam detik, null untuk no-cache
    */
   public function cacheFor(?int $seconds = 3600)
   {
      if ($seconds === null) {
         $this->cacheControl = 'no-cache, no-store, must-revalidate';
      } else {
         $this->cacheControl = "public, max-age={$seconds}";
      }
      return $this;
   }

   /**
    * Disable cache
    */
   public function noCache()
   {
      $this->cacheControl = 'no-cache, no-store, must-revalidate, max-age=0';
      return $this;
   }

   /**
    * Set cache untuk private (browser only, bukan proxy)
    */
   public function privateCacheFor(?int $seconds = 3600)
   {
      if ($seconds === null) {
         $this->cacheControl = 'private, no-cache, no-store, must-revalidate';
      } else {
         $this->cacheControl = "private, max-age={$seconds}";
      }
      return $this;
   }

   /**
    * Set ETag untuk cache validation
    */
   public function withETag(string $etag)
   {
      $this->etag = $etag;
      return $this;
   }

   /**
    * Set Last-Modified header
    * @param int|string $timestamp Unix timestamp atau date string
    */
   public function withLastModified($timestamp)
   {
      if (is_string($timestamp)) {
         $timestamp = strtotime($timestamp);
      }
      $this->lastModified = gmdate('D, d M Y H:i:s T', $timestamp);
      return $this;
   }

   /**
    * Set cookie
    * @param string $name Nama cookie
    * @param string $value Nilai cookie
    * @param int $expire TTL dalam detik (0 = session)
    * @param array $options Path, Domain, Secure, HttpOnly, SameSite
    */
   public function cookie(string $name, string $value, int $expire = 0, array $options = [])
   {
      $this->cookies[$name] = [
         'value' => $value,
         'expire' => $expire,
         'path' => $options['path'] ?? '/',
         'domain' => $options['domain'] ?? '',
         'secure' => $options['secure'] ?? false,
         'httponly' => $options['httponly'] ?? true,
         'samesite' => $options['samesite'] ?? 'Lax'
      ];
      return $this;
   }

   /**
    * Set compression method (gzip, deflate, atau auto)
    */
   public function compress(?string $method = 'gzip')
   {
      $this->compression = $method; // null = disable, 'gzip', 'deflate', atau 'auto'
      return $this;
   }

   /**
    * Disable compression
    */
   public function noCompress()
   {
      $this->compression = false;
      return $this;
   }

   /**
    * Set chunk size untuk streaming (default 8192)
    */
   public function setChunkSize(int $bytes)
   {
      $this->chunkSize = max(1024, min($bytes, 1048576)); // Min 1KB, Max 1MB
      return $this;
   }

   /**
    * Add middleware untuk process response
    * @param callable $callback function($response) { ... return $response; }
    */
   public function middleware(callable $callback)
   {
      $this->middleware[] = $callback;
      return $this;
   }

   /**
    * Get HTTP status code
    */
   public function getHttpCode(): int
   {
      return $this->httpCode;
   }

   /**
    * Get response type
    */
   public function getResponseType(): string
   {
      return $this->responseType;
   }

   /**
    * Get response content
    */
   public function getContent()
   {
      return $this->content;
   }

   /**
    * Get response metadata
    */
   public function getMeta(): array
   {
      return $this->meta;
   }

   /**
    * Get response errors
    */
   public function getErrors(): array
   {
      return $this->errors;
   }

   /**
    * Get HTTP info (status, message, code)
    */
   public static function getHttpInfo()
   {
      $instance = self::getInstance();

      return new class($instance->info, $instance->responseType) {
         private $data;

         public function __construct(array $data, $responseType)
         {
            $this->data = $data;
            $this->data['type'] = $responseType;
         }

         public function toArray(): array
         {
            return $this->data;
         }

         public function __get($key)
         {
            return $this->data[$key] ?? null;
         }

         public function __toString(): string
         {
            return "{$this->data['code']} {$this->data['status']}";
         }
      };
   }

   /**
    * === RESPONSE TYPE METHODS ===
    */

   /**
    * HTML response (View atau string)
    */
   public static function html($content)
   {
      $instance = self::getInstance();
      $instance->responseType = 'html';
      $instance->content = $content;

      if (!isset($instance->headers['Content-Type'])) {
         $instance->headers['Content-Type'] = 'text/html; charset=utf-8';
      }

      return $instance;
   }

   /**
    * JSON response (auto-encode array/object, auto-detect if already encoded)
    * 
    * Smart handling:
    * - array/object → encode to JSON
    * - JSON string → use as-is (no double encoding)
    * - non-JSON string → escape & return as string
    */
   public static function json($data, int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
   {
      $instance = self::getInstance();
      $instance->responseType = 'json';
      $instance->jsonOptions = $options;  // Store options untuk sendContent

      // Smart detection: if already JSON string, decode first then store raw
      if (is_string($data)) {
         if (self::isJson($data)) {
            // Already JSON encoded string → decode to array, will re-encode in sendContent
            $instance->content = json_decode($data, true);
         } else {
            // Plain string → wrap in array and encode
            $instance->content = ['message' => $data];
         }
      } else {

         // Array/Object → store as-is, will encode in sendContent
         $instance->content = $data;
      }

      $instance->headers['Content-Type'] = 'application/json; charset=utf-8';

      return $instance;
   }
   /**
    * Plain text response
    */
   public static function plain(string $content)
   {
      $instance = self::getInstance();
      $instance->responseType = 'plain';
      $instance->content = $content;
      $instance->headers['Content-Type'] = 'text/plain; charset=utf-8';

      return $instance;
   }

   /**
    * XML response
    */
   public static function xml(string $content)
   {
      $instance = self::getInstance();
      $instance->responseType = 'xml';
      $instance->content = $content;
      $instance->headers['Content-Type'] = 'application/xml; charset=utf-8';

      return $instance;
   }

   /**
    * CSV response
    */
   public static function csv(string $content, string $filename = 'export.csv')
   {
      $instance = self::getInstance();
      $instance->responseType = 'csv';
      $instance->content = $content;
      $instance->headers['Content-Type'] = 'text/csv; charset=utf-8';
      $instance->headers['Content-Disposition'] = "attachment; filename=\"{$filename}\"";

      return $instance;
   }

   /**
    * Download file
    */
   public static function download(string $filePath, string $filename = '')
   {
      $instance = self::getInstance();

      if (!file_exists($filePath)) {
         abort(404, "File tidak ditemukan: {$filePath}");
      }

      $instance->responseType = 'download';
      $instance->filePath = $filePath;
      $instance->content = null;

      if (empty($filename)) {
         $filename = basename($filePath);
      }

      $instance->headers['Content-Type'] = self::getMimeType($filePath);
      $instance->headers['Content-Disposition'] = "attachment; filename=\"{$filename}\"";
      $instance->headers['Content-Length'] = filesize($filePath);

      return $instance;
   }

   /**
    * Stream file (inline, bukan download)
    * Support HTTP Range requests untuk video/audio seeking
    */
   public static function stream(string $filePath, string $mimeType = '')
   {
      $instance = self::getInstance();

      if (!file_exists($filePath)) {
         abort(404, "File tidak ditemukan: {$filePath}");
      }

      $instance->responseType = 'stream';
      $instance->filePath = $filePath;
      $instance->content = null;
      $instance->mimeType = $mimeType ?: self::getMimeType($filePath);
      $fileSize = filesize($filePath);

      // Set headers untuk streaming
      $instance->headers['Content-Type'] = $instance->mimeType;
      $instance->headers['Accept-Ranges'] = 'bytes';
      $instance->headers['Content-Length'] = $fileSize;

      return $instance;
   }

   /**
    * Redirect response — compatible dengan helper `redirect()`
    *
    * Signature (minus exit param):
    *   Response::redirect(string $uri, $refresh = false, $code = null)
    *
    * Behavior:
    * - Jika URI relatif → ubah ke absolute via `base_url()`
    * - Menangani IIS dengan header Refresh jika diperlukan
    * - Menentukan status code pintar jika $code null (HTTP/1.1: non-GET => 303, GET => 307, fallback 302)
    * - Menambahkan header cache-control / pragma
    * - Bersihkan output buffer jika ada
    * - Caller harus memanggil ->send() untuk mengirim headers dan exit
    *
    * Usage:
    *   Response::redirect('/login')->send();
    *   Response::redirect('/home', true)->send();
    */
   public static function redirect(string $uri, $refresh = false, $code = null)
   {
      $instance = self::getInstance();
      $instance->responseType = 'redirect';
      $instance->content = null;  // Redirect tidak punya content body

      // Jika URI relatif, buat absolute
      if (!preg_match('#^(\w+:)?//#i', $uri)) {
         $uri = base_url($uri);
      }
      // Bersihkan output buffer jika ada
      if (function_exists('ob_get_length') && ob_get_length()) {
         @ob_clean();
      }

      // Tentukan behavior IIS / Refresh
      if ($refresh === false && isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false) {
         $refresh = true;
      } elseif ($refresh !== true && (empty($code) || !is_numeric($code))) {
         if (
            isset($_SERVER['SERVER_PROTOCOL'], $_SERVER['REQUEST_METHOD']) &&
            $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1'
         ) {
            $code = ($_SERVER['REQUEST_METHOD'] !== 'GET') ? 303 : 307;
         } else {
            $code = 302;
         }
      };

      self::status($code);



      if ($refresh) {
         // Gunakan header Refresh untuk server yang memerlukannya
         $instance->headers['Refresh'] = '0;url=' . $uri;
         $instance->httpCode = 200;  // Jangan ubah status code untuk refresh
      } else {
         $instance->headers['Location'] = $uri;
         $instance->httpCode = (int)$code;
      }
      // Set header cache-control / pragma untuk amankan caching browser
      $instance->headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
      $instance->headers['Pragma'] = 'no-cache';

      return $instance;
   }

   /**
    * Custom response type with custom mime type
    */
   public static function custom($content, string $mimeType)
   {
      $instance = self::getInstance();
      $instance->responseType = 'custom';
      $instance->content = $content;
      $instance->headers['Content-Type'] = $mimeType;

      return $instance;
   }

   /**
    * Smart content detection
    * Automatically detects content type dan respond accordingly
    */
   public static function content($content)
   {
      $instance = self::getInstance();

      if ($content instanceof View) {
         return self::html($content);
      } elseif (is_array($content) || is_object($content)) {
         return self::json($content);
      } elseif (is_string($content)) {
         // Detect jika JSON string
         if (self::isJson($content)) {
            return self::json(json_decode($content, true));
         }
         return self::plain($content);
      }

      return self::html($content);
   }

   /**
    * === HELPER METHODS ===
    */

   /**
    * Get MIME type dari file path
    */
   private static function getMimeType(string $filePath): string
   {
      $mimeTypes = [
         'pdf' => 'application/pdf',
         'doc' => 'application/msword',
         'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
         'xls' => 'application/vnd.ms-excel',
         'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
         'jpg' => 'image/jpeg',
         'jpeg' => 'image/jpeg',
         'png' => 'image/png',
         'gif' => 'image/gif',
         'svg' => 'image/svg+xml',
         'webp' => 'image/webp',
         'mp4' => 'video/mp4',
         'webm' => 'video/webm',
         'mp3' => 'audio/mpeg',
         'wav' => 'audio/wav',
         'zip' => 'application/zip',
         'txt' => 'text/plain',
         'csv' => 'text/csv',
         'xml' => 'application/xml',
         'json' => 'application/json',
      ];

      $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

      return $mimeTypes[$ext] ?? 'application/octet-stream';
   }

   /**
    * Check if string is valid JSON
    * Strict check: must start with { or [ to be considered JSON
    */
   private static function isJson(string $data): bool
   {
      if (empty($data) || !is_string($data)) {
         return false;
      }

      $trim = trim($data);

      // JSON harus diawali { atau [
      if (!in_array($trim[0] ?? '', ['{', '['])) {
         return false;
      }

      json_decode($trim);
      return (json_last_error() === JSON_ERROR_NONE);
   }

   /**
    * Set meta data
    * @example $response->meta(['title' => 'My Title',
    *                           'description' => 'My Description']);
    * @example $response->meta('title', 'My Title');
    * @example $response->meta('description', 'My Description');
    * @example $response->meta('description', 'My Description');
    */
   public function meta($key, $value = null)
   {
      if (is_array($key)) {
         $this->meta = array_merge($this->meta, $key);
      } elseif (is_string($key)) {
         $this->meta[$key] = $value;
      }
      return $this;
   }

   /**
    * === SENDING RESPONSE ===
    */

   /**
    * Send headers
    */
   private function sendHeaders(): void
   {
      if (headers_sent()) {
         return;
      }

      // Set HTTP status
      http_response_code($this->httpCode);

      // Set Content-Type header
      $contentType = $this->mimeTypes[$this->responseType] ?? 'text/html';
      if ($contentType === 'application/json' && strpos($contentType, 'charset') === false) {
         $contentType .= '; charset=utf-8';
      }
      header('Content-Type: ' . $contentType);

      // Apply cache control headers
      if (!empty($this->cacheControl)) {
         header('Cache-Control: ' . $this->cacheControl);
      } else {
         header('Cache-Control: no-cache, must-revalidate');
      }

      // Apply ETag if set
      if (!empty($this->etag)) {
         header('ETag: "' . $this->etag . '"');
      }

      // Apply Last-Modified if set
      if (!empty($this->lastModified)) {
         header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', $this->lastModified));
      }

      // Apply security headers
      if (!empty($this->securityHeaders)) {
         foreach ($this->securityHeaders as $header => $value) {
            if ($value !== false) {
               header($header . ': ' . $value);
            }
         }
      }

      // Apply custom headers
      foreach ($this->headers as $key => $value) {
         if (is_array($value)) {
            foreach ($value as $v) {
               header($key . ': ' . $v, false);
            }
         } else {
            header("{$key}: {$value}");
         }
      }

      // Apply cookies
      foreach ($this->cookies as $name => $cookie) {
         $options = $cookie['options'] ?? [];
         $expires = $cookie['expire'] ?? 0;
         $path = $options['path'] ?? '/';
         $domain = $options['domain'] ?? '';
         $secure = $options['secure'] ?? false;
         $httponly = $options['httponly'] ?? true;
         $samesite = $options['samesite'] ?? 'Lax';

         // PHP 7.3+ setcookie dengan array options
         if (PHP_VERSION_ID >= 70300) {
            setcookie($name, $cookie['value'], [
               'expires' => $expires,
               'path' => $path,
               'domain' => $domain,
               'secure' => $secure,
               'httponly' => $httponly,
               'samesite' => $samesite
            ]);
         } else {
            // Fallback untuk PHP < 7.3
            setcookie(
               $name,
               $cookie['value'],
               $expires,
               $path,
               $domain,
               $secure,
               $httponly
            );
         }
      }

      // Apply compression headers jika enabled
      if ($this->compression === 'gzip' && !ini_get('zlib.output_compression')) {
         if (!ob_get_contents()) {
            header('Content-Encoding: gzip');
         }
      } elseif ($this->compression === 'deflate' && !ini_get('zlib.output_compression')) {
         if (!ob_get_contents()) {
            header('Content-Encoding: deflate');
         }
      }

      // Redirect dengan Location header
      if ($this->responseType === 'redirect' && isset($this->headers['Location'])) {
         header('Location: ' . $this->headers['Location'], TRUE, $this->httpCode);
      }

      $this->headersSent = true;
   }
   /**
    * Send content based on response type
    */
   private function sendContent(): void
   {
      if (empty($this->info)) {
         self::status(200);
      }

      // Handle Results object with pagination
      if ($this->content instanceof Results) {
         $this->meta('pagination', $this->content->getPagination());
         $this->content = $this->content->getData();
      }

      $this->meta([
         'timestamp' => time(),
         'request_id' => class_exists('Request') ? Request::init()->getId() : null
      ]);

      // Start compression output buffer jika enabled
      if ($this->compression === 'gzip') {
         ob_start('ob_gzhandler');
      } elseif ($this->compression === 'deflate') {
         ob_start('ob_deflatehandler');
      } elseif ($this->compression === 'auto') {
         // Auto-detect from Accept-Encoding header
         $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
         if (strpos($acceptEncoding, 'gzip') !== false) {
            ob_start('ob_gzhandler');
         } elseif (strpos($acceptEncoding, 'deflate') !== false) {
            ob_start('ob_deflatehandler');
         }
      }

      switch ($this->responseType) {
         case 'html':
            // Handle View object rendering
            if (is_object($this->content)) {
               // Cast to object explicitly for static analysis
               /** @var object $viewObj */
               $viewObj = $this->content;

               // Try to call render() on object (duck typing)
               if (method_exists($viewObj, 'with') && !empty($this->info)) {
                  $viewObj->with($this->info);
               }

               if (method_exists($viewObj, 'render')) {
                  echo $viewObj->render();
               } else {
                  echo (string)$this->content;
               }
            } else {
               echo (string)$this->content;
            }
            break;
         case 'json':
            $output = [
               'status' => $this->info['status'] ?? 'success',
               'message' => $this->info['message'] ?? '',
               'data' => $this->content,
               'meta' => $this->meta,
            ];

            // Tambahin errors jika ada
            if (!empty($this->errors)) {
               $output['errors'] = $this->errors;
            }

            echo json_encode($output, $this->jsonOptions);
            break;

         case 'plain':
         case 'xml':
         case 'csv':
         case 'custom':
            echo (string)$this->content;
            break;

         case 'download':
            readfile($this->filePath);
            break;

         case 'stream':
            $this->streamWithRange();
            break;

         case 'redirect':
            // Redirect handled via headers, no body
            break;

         default:
            if ($this->content !== null) {
               echo (string)$this->content;
            }
      }

      // Flush compression buffer jika active
      if (ob_get_level() > 0 && ($this->compression === 'gzip' || $this->compression === 'deflate' || $this->compression === 'auto')) {
         ob_end_flush();
      }
   }

   /**
    * Stream file dengan support HTTP Range requests
    * Memungkinkan user untuk seek/skip di video/audio
    */
   private function streamWithRange(): void
   {
      $fileSize = filesize($this->filePath);
      $fp = fopen($this->filePath, 'rb');

      if (!$fp) {
         http_response_code(500);
         echo 'Error opening file';
         return;
      }

      // Check jika ada HTTP Range request
      if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
         $start = intval($matches[1]);
         $end = $matches[2] !== '' ? intval($matches[2]) : $fileSize - 1;

         // Validate range
         if ($start < 0 || $end >= $fileSize || $start > $end) {
            http_response_code(416); // Range Not Satisfiable
            header('Content-Range: bytes */' . $fileSize);
            fclose($fp);
            return;
         }

         // Send 206 Partial Content
         http_response_code(206);
         header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
         header('Content-Length: ' . ($end - $start + 1));

         // Seek dan stream only requested range
         fseek($fp, $start);
         echo fread($fp, $end - $start + 1);
      } else {
         // Stream entire file
         http_response_code(200);
         header('Content-Length: ' . $fileSize);
         fpassthru($fp);
      }

      fclose($fp);
   }

   /**
    * Send response (headers + content)
    * Untuk redirect: kirim headers lalu exit
    */
   public function send(): void
   {
      if (Request::init()->isCli()) {
         echo 'hello';
         exit;
      }

      // Execute middleware pipeline if any
      if (!empty($this->middleware)) {
         $this->executeMiddleware();
      }

      $this->sendHeaders();
      $this->sendContent();

      // Jika redirect, exit setelah headers dikirim
      if ($this->responseType === 'redirect') {
         exit;
      }
      exit;
   }

   /**
    * Execute middleware pipeline callbacks
    */
   private function executeMiddleware(): void
   {
      foreach ($this->middleware as $callback) {
         if (is_callable($callback)) {
            call_user_func($callback, $this);
         }
      }
   }

   /**
    * Export response snapshot for caching (non-destructive)
    * Returns array payload containing status, headers, content and responseType
    */
   public function exportForCache(): array
   {
      return [
         'status' => $this->httpCode,
         'headers' => $this->headers,
         'content' => $this->content,
         'responseType' => $this->responseType,
      ];
   }

   /**
    * Build a Response instance from cached payload
    * Returned instance is ready to be sent by Kernel
    */
   public static function buildFromCache(array $data): Response
   {
      $instance = self::getInstance();
      $instance->httpCode = $data['status'] ?? 200;
      $instance->headers = $data['headers'] ?? [];
      $instance->content = $data['content'] ?? null;
      $instance->responseType = $data['responseType'] ?? 'html';
      return $instance;
   }
}
