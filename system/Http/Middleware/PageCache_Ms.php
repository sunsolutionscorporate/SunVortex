<?php

/**
 * PageCache middleware (system-level helper)
 *
 * Responsibilities:
 * - Compute stable cache key for full request (method + URL + query)
 * - Read/write cached response payload (status, headers, body, responseType)
 * - Provide a handle() method called by the app-level middleware
 */
class PageCache_Ms
{
   protected $request;
   protected $cacheTtl = 300; // seconds, default 5 minutes

   public function __construct(array $options = [])
   {
      if (isset($options['ttl'])) {
         $this->cacheTtl = (int)$options['ttl'];
      }

      $this->request = Request::init();
   }



   /**
    * Main handler called from app middleware
    * @param string $key
    * @param callable $next
    * @return mixed Response|View|string|array
    */
   public function remember(string $key, $next)
   {
      $cache = new Cache('render');

      // Try hit
      $cached = $cache->get($key);
      if ($cached !== false && $cached !== null) {
         // Reconstruct Response from cached payload
         if (is_array($cached) && isset($cached['responseType'])) {
            return Response::buildFromCache($cached);
         }
      };
      $method = strtoupper($this->request->getMethod());

      $result = $next($this->request);
      if ($method !== 'GET') {
         return $result;
      };

      // Normalize result into cacheable payload
      $payload = null;

      if ($result instanceof Response) {
         $payload = $result->exportForCache();
      } elseif ($result instanceof View) {
         $body = $result->render();
         $payload = [
            'status' => 200,
            'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
            'content' => $body,
            'responseType' => 'html',
         ];
      } elseif (is_array($result) || is_object($result)) {
         $payload = [
            'status' => 200,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'content' => $result,
            'responseType' => 'json',
         ];
      } else {
         $payload = [
            'status' => 200,
            'headers' => ['Content-Type' => 'text/plain; charset=utf-8'],
            'content' => (string)$result,
            'responseType' => 'plain',
         ];
      };

      // Avoid caching non-200 or non-cacheable response types
      $status = $payload['status'] ?? 200;
      $responseType = $payload['responseType'] ?? 'html';
      $nonCacheableTypes = ['redirect', 'download', 'stream'];
      if ($status !== 200 || in_array($responseType, $nonCacheableTypes, true)) {
         return $result;
      };

      // Store payload in cache (best-effort)
      try {
         $cache->set($key, $payload, $this->cacheTtl);
      } catch (\Throwable $e) {
         // ignore cache write failures
      }

      // Return original result so downstream handling remains same
      return $result;
   }
}
