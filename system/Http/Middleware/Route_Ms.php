<?php

class Route_Ms extends BaseMw
{

   public function changeController(string $controller)
   {
      changeClassProperty($this->request, 'elementUri', [
         'controller' => $controller,
      ]);
   }
   public function changeMethod(string $method)
   {
      changeClassProperty($this->request, 'elementUri', [
         'method' => $method,
      ]);
   }
   public function changeParams($param)
   {
      if (is_string($param)) {
         $param = explode('/', $param);
      } elseif (is_array($param)) {
         // 
      } else {
         abort(
            500,
            'changeParams invalid param must be string or array'
         );
      }

      changeClassProperty($this->request, 'elementUri', [
         'params' => $param,
      ]);
   }
};
