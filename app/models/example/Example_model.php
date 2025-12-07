<?php
class Example_model extends BaseModel
{
   protected $table = 'examples';

   public function getAll()
   {
      return $this->db->table($this->table)->get();
   }

   public function find($id)
   {
      return $this->db->table($this->table)->where('id', $id)->first();
   }

   public function insert(array $data)
   {
      return $this->db->table($this->table)->insert($data);
   }

   public function update($id, array $data)
   {
      return $this->db->table($this->table)->where('id', $id)->update($data);
   }

   public function delete($id)
   {
      return $this->db->table($this->table)->where('id', $id)->delete();
   }
}
