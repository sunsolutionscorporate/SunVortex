<?php

/**
 * Application Middleware Handler
 * 
 * Extends BaseMw - inherit $request attribute
 * Setiap method harus follow pattern: {middleware_name}_handle($request, $next)
 */
class Middleware extends BaseMw
{
   public function Cors_handle($context, $next)
   {
      $allowedOrigins = [
         'http://localhost:3000',
         'http://localhost:8080',
         'https://yourdomain.com',
         'https://sunsolutions.local',
         'http://sunsolutions.local',
         'http://127.0.0.1',
      ];
      $allowedMethods = 'GET, POST, PUT, DELETE, PATCH, OPTIONS';
      $allowedHeaders = 'Content-Type, Authorization, X-CSRF-TOKEN, X-Requested-With';
      $context
         ->origin($allowedOrigins, true)
         ->credential()->allowMethod($allowedMethods)
         ->allowedHeaders($allowedHeaders)
         ->cache(600); // cache preflight selama 10 menit


      return $next($context);
   }

   public function PageCache_handle($context, $next)
   {
      // try {
      //    $key = $this->request->fullUrl();
      //    return $context->remember($key, $next);
      // } catch (\Throwable $e) {
      //    Logger::warning('[PageCache]', 'Cache middleware error: ' . $e->getMessage());
      //    // Fallback: continue pipeline
      //    return $next($context);
      // }
      return $next($context);
   }

   public function route_handle($context, $next)
   {
      $request = $context->request->getUri();

      if ($this->request->isBrowser()) {
         if (empty($request['controller'])) {
            $context->changeController('home');
         }
         // slog('Controller:', $request['controller']);
         // slog('Method:', $request['method']);
         // slog('Params:', $request['params']);
         // slog('OKE');
      };
      return $next($context);
   }

   public function auth_handle($context, $next)
   {
      $uri = $this->request->getUri();
      // $token = token_encode([
      //    'name' => 'widodo'
      // ]);
      // slog($token);
      // setcookie('Authorization', $token, [
      //    'expires' => time() + 86400,
      //    'path' => '/',
      //    'secure' => false,         // ✅ aktif di HTTP
      //    'httponly' => true,        // ✅ tetap aman dari JS
      //    'samesite' => 'Lax'        // ✅ cukup aman untuk localhost
      // ]);

      // if ($this->request->isAjax() || $this->request->isApi()) {
      //    $token = $this->request->getBearerToken();
      //    $decode = $context->decode($token);

      //    if ($decode->status !== 'ok') {
      //       Response::json([
      //          'links' => [
      //             'view' => base_url(),
      //             'verify' => 'xxxxxx',
      //          ]
      //       ])->status(401, $decode->status)->send();
      //    }
      // } elseif ($this->request->isBrowser()) {
      //    // 
      // }

      $uri = Request::init()->getUri();
      // slog($uri);
      if ($this->request->isAjax() || $this->request->isApi()) {
         if ($uri['controller'] !== 'auth') {

            Response::json($uri)
               ->status(401, 'gembel')
               ->links('login', 'gembel')
               ->links('home', 'gembel')
               ->send();
         }
      };
      if ($this->request->isBrowser()) {
         // slog('OKE', $this->request->getCookie('Authorization'));
         if (($uri['controller'] !== 'home') &&
            ($uri['controller'] !== 'auth')
         ) {
            // slog('aaa');
         }

         $token = $this->request->getCookie('Authorization') ?? '';
      };


      // $decode = $context->decode($token);
      // if ($decode->status !== 'ok' && $uri['controller'] !== 'auth') {
      //    return redirect('auth/index');
      // }


      return $next($context);
   }

   public function throttle_handle($context, $next)
   {
      $context->attempt('widodo');
      if (!$context->isAllowed()) {
         abort(
            429,
            'Request temporarily blocked due to rate limiting',
            'Request temporarily blocked due to rate limiting. wait ' . $context->getRetryAfter() . ' seconds.',
            [
               'retry_after' => $context->getRetryAfter(),
               'reset_at' => $context->getResetAt(),
               'identifier' => $context->getIdentifier(),
            ]
         );
      };

      return $next($context);
   }

   public function csrf_handle($context, $next)
   {
      // slog('------------- > jalankan => csrf');
      return $next($context);
   }

   public function file_handle($context, $next)
   {
      // slog('------------- > jalankan => file');
      return $next($context);
   }
};
