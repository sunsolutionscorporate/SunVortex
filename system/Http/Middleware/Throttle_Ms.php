<?php

class Throttle_Ms
{
   protected $cache = null;

   /**
    * Structured response produced by attempt()
    *
    * @var array
    */
   private $response = [
      'allowed'     => true,
      'remaining'   => 0,
      'retry_after' => 0,
      'reset_at'    => 0,
      'limiter'     => '',
      'identifier'  => null,
   ];

   /**
    * Rate limit defaults. Can be overridden via constructor.
    */
   protected $maxAttempts;
   protected $decaySeconds;
   protected $keyPrefix = 'throttle_';

   public function __construct()
   {
      $this->cache = new Cache();
      $this->maxAttempts = (int) config('RATE_ATT');
      $this->decaySeconds = (int) config('RATE_TIME');
   }

   /**
    * Attempt an action identified by $identifier for the current client.
    *
    * @param string $identifier  Unique identifier for the action (e.g. route name)
    * @return $this
    */
   public function attempt(string $identifier)
   {
      $request = Request::init();

      $client = method_exists($request, 'getClientIp') ? $request->getClientIp() : 'anon';
      $key = $this->keyPrefix . $client . '_' . $identifier;
      $now = time();
      $data = $this->cache->get($key);

      $maxAttempts = $this->maxAttempts;
      $decaySeconds = $this->decaySeconds;

      // No record: fresh window
      if (!$data) {
         $data = [
            'attempts'   => 1,
            'expires_at' => $now + $decaySeconds,
            'limited'    => false,
         ];
         $this->cache->set($key, $data, $decaySeconds);

         $this->setResponse(true, $maxAttempts - 1, $decaySeconds, $data['expires_at'], 'none', $identifier);
         return $this;
      }

      // Window expired: reset
      if ($now > $data['expires_at']) {
         $wasLimited = !empty($data['limited']);
         $data = [
            'attempts'   => 1,
            'expires_at' => $now + $decaySeconds,
            'limited'    => false,
         ];
         $this->cache->set($key, $data, $decaySeconds);

         $this->setResponse(true, $maxAttempts - 1, $decaySeconds, $data['expires_at'], $wasLimited ? 'finish' : 'none', $identifier);
         return $this;
      }

      // Increment attempts inside window
      $data['attempts']++;

      // Exceeded limit
      if ($data['attempts'] > $maxAttempts) {
         $firstExceeded = ($data['attempts'] === $maxAttempts + 1);
         $data['limited'] = true;
         $this->cache->set($key, $data, max(0, $data['expires_at'] - $now));

         $retry = max(0, $data['expires_at'] - $now);
         $limiter = $firstExceeded ? 'start' : 'remain';

         $this->setResponse(false, 0, $retry, $data['expires_at'], $limiter, $identifier);
         return $this;
      }

      // Still allowed
      $remaining = max(0, $maxAttempts - $data['attempts']);
      $this->cache->set($key, $data, max(0, $data['expires_at'] - $now));

      $this->setResponse(true, $remaining, max(0, $data['expires_at'] - $now), $data['expires_at'], 'none', $identifier);
      return $this;
   }

   /**
    * Set internal response structure
    */
   private function setResponse(bool $allowed, int $remaining, int $retryAfter, int $resetAt, string $limiter, ?string $identifier = null)
   {
      $this->response = [
         'allowed'     => $allowed,
         'remaining'   => $remaining,
         'retry_after' => $retryAfter,
         'reset_at'    => $resetAt,
         'limiter'     => $limiter,
         'identifier'  => $identifier,
      ];

      if (!$allowed) {
         $send_response = Response::getInstance();
         $send_response->headers([
            'X-RateLimit-Limit' => (string) $this->maxAttempts,
            'X-RateLimit-Remaining' => (string) ($this->response['remaining'] ?? 0),
            'X-RateLimit-Reset' => (string) ($this->response['reset_at'] ?? time()),
            'Retry-After' => (string) ($this->response['retry_after'] ?? 0),
         ]);
      }
      return $this;
   }

   /**
    * Boolean helper
    */
   public function isAllowed(): bool
   {
      return (bool) ($this->response['allowed'] ?? false);
   }

   /**
    * Reset throttle for a given identifier and client (useful for tests)
    */
   public function reset(string $identifier): void
   {
      $request = Request::init();
      $client = method_exists($request, 'getClientIp') ? $request->getClientIp() : 'anon';
      $key = $this->keyPrefix . $client . '_' . $identifier;
      $this->cache->delete($key);
   }

   public function getRemaining(): int
   {
      return (int) ($this->response['remaining'] ?? 0);
   }

   public function getRetryAfter(): int
   {
      return (int) ($this->response['retry_after'] ?? 0);
   }

   public function getResetAt(): int
   {
      return (int) ($this->response['reset_at'] ?? 0);
   }

   public function getLimiter(): string
   {
      return (string) ($this->response['limiter'] ?? '');
   }

   public function getIdentifier(): ?string
   {
      return $this->response['identifier'] ?? null;
   }
}
