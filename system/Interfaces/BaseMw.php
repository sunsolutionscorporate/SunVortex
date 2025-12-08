<?php

/**
 * BaseMw Abstract Class
 * 
 * Base class untuk semua middleware handler.
 * Setiap middleware class WAJIB extends class ini.
 * 
 * Menyediakan:
 * - $request attribute untuk akses Request object
 * - Contract untuk middleware handler methods
 * 
 * Usage:
 *   class Middleware extends BaseMw {
 *       public function cors_handle($request, $next) {
 *           // Bisa akses $this->request
 *       }
 *   }
 */
abstract class BaseMw
{
   /**
    * Request object - otomatis tersedia di semua middleware
    */
   protected $request;

   /**
    * Constructor - optional untuk custom initialization
    */
   public function __construct()
   {
      $this->request = Request::init();
   }
}
