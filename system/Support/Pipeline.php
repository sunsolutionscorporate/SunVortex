<?php

/**
 * HTTP Pipeline - Middleware Queue Handler
 * 
 * Modern, elegant middleware pipeline inspired by Laravel.
 * 
 * Features:
 * - Clean middleware queue management
 * - Conditional middleware (skip, prepend, append)
 * - Early termination (preflight handling)
 * - Middleware aliases & dynamic resolution
 * - Event listeners untuk track middleware execution
 * - Easy to debug & test
 * 
 * Usage:
 *   $pipeline = new Pipeline($request);
 *   $pipeline
 *       ->through(['cors', 'throttle', 'auth'])
 *       ->skip(['csrf']) // for API
 *       ->listener(function($event) {
 *           if ($event['type'] === 'after' && $event['middleware'] === 'auth') {
 *               // Do something after auth middleware
 *           }
 *       })
 *       ->then(function($req) { 
 *           return routeToController($req);
 *       });
 */
class Pipeline
{
   private $request;
   private $queue = [];
   private $skipped = [];
   private $middleware_instances = [];
   private $aliases = [];
   private $middleware_handle = null;
   private $listeners = [];  // Event listeners

   public function __construct($request = null)
   {
      $this->request = $request;

      $middleware_path = realpath(config('PATH_APP') . 'middleware' . DIRECTORY_SEPARATOR . 'middleware.php');
      if ($middleware_path) {
         require_once $middleware_path;
         $middleware_name = getLastSegment($middleware_path);
         if (class_exists($middleware_name, false)) {
            $instance = new $middleware_name();

            // Check apakah instance extends BaseMw
            if (!($instance instanceof BaseMw)) {
               throw new \Exception(
                  "Middleware class '{$middleware_name}' must extend BaseMw class. " .
                     "Please add 'extends BaseMw' to class declaration."
               );
            }

            // Assign request ke public property

            $this->middleware_handle = $instance;
         } else {
            // class Middleware tidak ditemukan pada file 'middleware.php' app path;
            Logger::warning('[', __CLASS__, ']', "mismatch: class is not valid middleware.");
         };
      }
   }

   /**
    * Resolve alias menjadi class name
    */
   private function resolveAlias(string $middleware): string
   {
      // Jika sudah format class (PascalCase), gunakan apa adanya
      if (preg_match('/^[A-Z]/', $middleware)) {
         return $middleware;
      };
      return ucfirst($middleware);
   }

   /**
    * Set the entire middleware queue
    */
   public function through(array $middlewares): self
   {
      $this->queue = array_values($middlewares);
      return $this;
   }

   /**
    * Add middleware(s) ke queue (append di akhir)
    */
   public function append($middleware): self
   {
      $middlewares = is_array($middleware) ? $middleware : [$middleware];
      array_push($this->queue, ...$middlewares);
      return $this;
   }

   /**
    * Add middleware(s) ke depan queue (prioritas tinggi)
    */
   public function prepend($middleware): self
   {
      $middlewares = is_array($middleware) ? $middleware : [$middleware];
      $this->queue = array_merge($middlewares, $this->queue);
      return $this;
   }

   /**
    * Skip specific middleware(s) dari execution
    */
   public function skip($middleware): self
   {
      $middlewares = is_array($middleware) ? $middleware : [$middleware];
      $this->skipped = array_merge($this->skipped, $middlewares);
      return $this;
   }

   /**
    * Register event listener untuk middleware execution
    * 
    * Listener akan dipanggil dengan event array:
    * [
    *    'type' => 'before|after|failed',
    *    'middleware' => 'cors',
    *    'request' => Request object,
    *    'result' => Response or Request,
    *    'error' => Exception (jika failed)
    * ]
    * 
    * Listener bisa return:
    * - void/null: lanjut ke middleware berikutnya
    * - 'stop': hentikan pipeline, jangan lanjut
    * - 'continue': lanjut ke middleware berikutnya
    */
   public function listener(callable $callback): self
   {
      $this->listeners[] = $callback;
      return $this;
   }

   /**
    * Dispatch event ke semua registered listeners
    * 
    * @return string 'stop', 'continue', atau null
    */
   private function dispatchEvent(array $event): ?string
   {
      foreach ($this->listeners as $listener) {
         try {
            $result = $listener($event);

            // Jika listener return 'stop', hentikan pipeline
            if ($result === 'stop') {
               return 'stop';
            }

            // Jika listener return 'continue', lanjut ke listener berikutnya
            if ($result === 'continue') {
               continue;
            }
         } catch (\Throwable $e) {
            Logger::warning('[Pipeline]', 'Listener error: ' . $e->getMessage());
         }
      }

      return null;  // Default: lanjut ke middleware berikutnya
   }

   /**
    * Set request untuk pipeline
    */
   public function setRequest($request): self
   {
      $this->request = $request;
      return $this;
   }

   /**
    * Get current queue (useful for debugging)
    */
   public function getQueue(): array
   {
      return $this->queue;
   }

   /**
    * Get skipped middlewares
    */
   public function getSkipped(): array
   {
      return $this->skipped;
   }

   /**
    * Execute pipeline - run through middlewares then call destination
    */
   public function then(callable $destination)
   {
      // Buat closure untuk memanggil next middleware
      $next = $this->buildNextClosure($destination);
      // Jalankan pipeline dengan request
      return $next($this->request);
   }

   /**
    * Build recursive closure untuk middleware chaining
    */
   private function buildNextClosure(callable $destination): \Closure
   {
      // Reverse queue agar urutan execute sesuai dengan order di array
      $queue = array_reverse($this->queue);
      $currentMiddleware = null;
      $currentResult = null;

      $next = function ($request) use (&$queue, &$next, $destination, &$currentMiddleware, &$currentResult) {
         // Jika queue kosong, jalankan destination callback
         if (empty($queue)) {
            return $destination($request);
         }

         // Pop middleware dari queue
         $middleware = array_pop($queue);

         // Skip jika dalam daftar skip
         if (in_array($middleware, $this->skipped)) {
            // Dispatch 'skipped' event
            $event = [
               'type' => 'skipped',
               'middleware' => $middleware,
               'request' => $request,
            ];
            $this->dispatchEvent($event);

            return $next($request);
         }

         // Resolve class name dari alias/string
         $class = $this->resolveAlias($middleware);
         $method_handle = $class . '_handle';
         $class = $class . '_Ms';

         // Cek apakah class exists
         if (!class_exists($class)) {
            Logger::warning("Middleware '{$class}' not found, skipping...");

            // Dispatch 'failed' event
            $event = [
               'type' => 'failed',
               'middleware' => $middleware,
               'request' => $request,
               'error' => new \Exception("Middleware class '{$class}' not found"),
            ];
            $this->dispatchEvent($event);

            return $next($request);
         }

         // Dispatch 'before' event - sebelum middleware dijalankan
         $beforeEvent = [
            'type' => 'before',
            'middleware' => $middleware,
            'request' => $request,
         ];
         $listenerResult = $this->dispatchEvent($beforeEvent);

         // Jika listener return 'stop', hentikan pipeline
         if ($listenerResult === 'stop') {
            Logger::info('[Pipeline]', "Pipeline stopped by listener at middleware '{$middleware}'");
            return $request;  // Return request tanpa lanjut
         }

         // Create instance jika belum ada (cache untuk reuse)
         if (!isset($this->middleware_instances[$class])) {
            $this->middleware_instances[$class] = new $class();
         }
         $instance = $this->middleware_instances[$class];
         // slog($instance);



         try {
            // Simpan middleware dan result untuk after event
            $previousMiddleware = $currentMiddleware;
            $previousResult = $currentResult;
            $currentMiddleware = $middleware;

            // Buat wrapper untuk $next yang akan dispatch 'after' event
            // sebelum benar-benar lanjut ke middleware berikutnya
            $nextWrapper = function ($req) use (&$next, &$currentMiddleware, &$currentResult) {
               // Dispatch 'after' event untuk middleware saat ini
               $afterEvent = [
                  'type' => 'after',
                  'middleware' => $currentMiddleware,
                  'request' => $req,
                  'result' => $currentResult,
               ];
               $listenerResult = $this->dispatchEvent($afterEvent);

               // Jika listener return 'stop', hentikan pipeline
               if ($listenerResult === 'stop') {
                  Logger::info('[Pipeline]', "Pipeline stopped by listener after middleware '{$currentMiddleware}'");
                  return $currentResult instanceof Response ? $currentResult : $req;
               }

               // Lanjut ke middleware berikutnya
               return $next($req);
            };

            // Panggil middleware handle, pass wrapper closure
            $result = $this->middleware_handle->$method_handle($instance, $nextWrapper);
            $currentResult = $result;

            // Return result middleware
            return $result;
         } catch (\Throwable $e) {
            // Logger::warning('[Pipeline]', "Middleware '{$middleware}' error: " . $e->getMessage());

            // Dispatch 'failed' event
            $failedEvent = [
               'type' => 'failed',
               'middleware' => $middleware,
               'request' => $request,
               'error' => $e,
            ];
            $this->dispatchEvent($failedEvent);

            throw $e;
         }
      };

      return $next;
   }

   /**
    * Debug: tampilkan pipeline state
    */
   public function debug(): array
   {
      return [
         'queue' => $this->queue,
         'skipped' => $this->skipped,
         'request_type' => get_class($this->request),
      ];
   }
}
