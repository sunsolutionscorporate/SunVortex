<?php
class DBException extends Logger
{
   protected $query;
   private function traceError(array $trace)
   {
      foreach ($trace as $frame) {
         if (!isset($frame['file'])) continue;

         // jika TIDAK mengandung file DB berikut
         if (!preg_match('/QueryBuilder\.php|Database\.php|QueryResult\.php/i', $frame['file'])) {
            return $frame;
         }
      }
      return null;
   }
   public static function toQueryStr($sql, $params = null)
   {
      $str = $sql;
      // Ganti placeholder dengan nilai
      foreach ($params as $key => $value) {
         $str = str_replace(":$key", $value, $str);
      }
      return $str;
   }

   public function __construct($message,  $previous = null, $sql = null, $params = null)
   {


      if (!empty($sql)) {
         $this->query = self::toQueryStr($sql, $params);
         $message .= ' "' . $this->query . '" ';
      }
      if (!empty($previous)) {
         $info = isset($previous->errorInfo) ? $previous->errorInfo : null;
         $trace = $this->traceError($previous->getTrace());
         $message .= isset($info[2]) ? $info[2] : $previous->getMessage();
         if (!empty($trace)) {
            $message .= " in " .  $trace['file'] . " " . $trace['line'];
         }
      };


      // parent::__construct($message);
      parent::__construct(
         500,
         CRITICAL,
         $message
      );
   }

   public function getQuery()
   {
      return $this->query;
   }
}
