<?php

/**
 * CORS Manager - Non-intrusive CORS configuration
 * 
 * Generates CORS headers without calling header() directly.
 * Headers are returned as array and applied by middleware/Response.
 * 
 * Usage:
 *   $cors = new Cors(['origins' => ['*'], 'methods' => 'GET,POST']);
 *   $isAllowed = $cors->isOriginAllowed('https://example.com');
 *   $headers = $cors->toHeaders();
 *   Response::headers($headers);
 */
class Cors_Ms
{
   private $request;
   private $response;
   public function __construct()
   {
      $this->request = Request::init();
      $this->response = Response::getInstance();
   }
   public  function origin(array $origin, bool $subdomain = true)
   {
      if (allowOrigin($origin, $subdomain)) {
         $this->response->header("Access-Control-Allow-Origin", $_SERVER['HTTP_ORIGIN'] ?? "null");
      } else {
         $this->response->header("Access-Control-Allow-Origin", "null");
      };
      $this->response->header("Vary", "Origin"); // penting untuk caching proxy/CDN
      return $this;
   }

   public function credential()
   {
      $this->response->header("Access-Control-Allow-Credentials", "true");
      return $this;
   }
   public function allowMethod(string $allowedMethods)
   {
      if ($this->request->getOrigin() && $this->request->isOptions()) {
         $this->response->header("Access-Control-Allow-Methods", "$allowedMethods");
      }
      return $this;
   }
   public function allowedHeaders(string $allowedHeaders)
   {
      if ($this->request->getOrigin() && $this->request->isOptions()) {
         $this->response->header("Access-Control-Allow-Headers", "$allowedHeaders");
      }
      return $this;
   }
   public function cache(int $maxAge)
   {
      if ($this->request->getOrigin() && $this->request->isOptions()) {
         $this->response->header("Access-Control-Max-Age", "$maxAge");
      }
      return $this;
   }
};
