<?php
class View
{
   protected $path;
   protected $data;

   public function __construct(string $path, array $data = [])
   {
      $this->path = $path;
      $this->data = $data;
   }

   public function render(): string
   {
      $file = APP_PATH . '/Views/' . str_replace('.', '/', $this->path) . '.php';

      if (!file_exists($file)) {
         throw new Exception("View not found: {$this->path}");
      }

      extract($this->data);

      ob_start();
      include $file;
      return ob_get_clean();
   }

   // Magic convert object to string
   public function __toString(): string
   {
      return $this->render();
   }

   // Add or merge data after construction (chainable)
   public function with($key, $value = null): self
   {
      if (is_array($key)) {
         $this->data = array_merge($this->data, $key);
      } else {
         $this->data[$key] = $value;
      }
      return $this;
   }

   // Replace entire data array
   public function setData(array $data): self
   {
      $this->data = $data;
      return $this;
   }

   // Merge additional data into existing data
   public function addData(array $data): self
   {
      $this->data = array_merge($this->data, $data);
      return $this;
   }

   // Get current data array
   public function getData(): array
   {
      return $this->data;
   }
}
