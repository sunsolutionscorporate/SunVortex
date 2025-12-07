<?php

/**
 * Summary of Results
 */
class Results
{
   protected $results = [];
   private $pagination = [];

   /**
    * Summary of __construct
    * @param array $data
    * @param mixed $total
    * @param mixed $limit
    * @param mixed $currentPage
    */
   public function __construct(array $data)
   {
      $this->results = $data;
   }

   /**
    * Summary of results
    * @return array{data: array, pagination: array|array{from: float|int, last_page: int, limit: int, offset: int, page: int, to: float|int, total: int}}
    */
   public function results()
   {
      return [
         'data' => $this->results,
         'pagination' => $this->pagination,
      ];
   }

   public function getData()
   {
      return $this->results;
   }

   public function getPagination()
   {
      if ($this->pagination['total'] !== null && $this->pagination['limit'] !== null && $this->pagination['page'] !== null) {
         $lastPage = ($this->pagination['limit'] > 0) ? (int)ceil($this->pagination['total'] / $this->pagination['limit']) : 0;
         $offset = ($this->pagination['page'] - 1) * $this->pagination['limit'];
         $from = ($this->pagination['total'] > 0) ? ($offset + 1) : 0;
         $to = ($this->pagination['total'] > 0) ? ($offset + count($this->results)) : 0;

         $this->pagination['offset'] = (int)$offset;
         $this->pagination['last_page'] = $lastPage;
         $this->pagination['from'] = $from;
         $this->pagination['to'] = $to;
      }
      return $this->pagination;
   }

   public function setTotal(int $total)
   {
      $this->pagination['total'] = $total;
      return $this;
   }

   /**
    * Summary of setLimit
    * @param int $limit
    * @return static
    */
   public function setLimit(int $limit)
   {
      $this->pagination['limit'] = $limit;
      return $this;
   }

   /**
    * Summary of setPage
    * @param int $page
    * @return static
    */
   public function setPage(int $page)
   {
      $this->pagination['page'] = $page;
      return $this;
   }
};
