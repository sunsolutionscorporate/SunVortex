<?php

/**
 * Model Resident
 * 
 * Representasi data penduduk dari tabel 'residents'
 * Demonstrasi implementasi BaseModel dengan:
 * - Mass-assignment (fillable)
 * - Casting tipe data
 * - Mutator & Accessor
 * - Cache invalidation otomatis (getCacheKeys)
 * - Named scopes
 * - Event listeners
 * 
 * Struktur tabel:
 * - id (auto increment, primary key)
 * - name (varchar) - nama penduduk
 * - nik (varchar, unique) - nomor induk kependudukan
 * - placebirth (varchar) - tempat lahir
 * - datebirth (date) - tanggal lahir
 * - created_at (timestamp)
 * - updated_at (timestamp)
 */
class User_model extends BaseModel
{
   protected $table = "users";
   protected $primaryKey = 'id';
   protected $softDelete = true;
   protected $fillable = [
      'uid',
      'name',
      'email',
      'avatar',
      'givenName',
      'familyName',
      'password',
   ];


   /**
    * Constructor dengan setup event listeners
    * 
    * @param array $attributes
    * @param Database $db
    */
   public function __construct($attributes = [], $db = null)
   {
      parent::__construct($attributes, $db);
   }

   public function find($field)
   {
      $user = $this->db->table('users');
      if (is_array($field)) {
         foreach ($field as $k => $v) {
            $user->where($k, $v);
         }
         $row = $user->first();
      } else {
         $row = $user->where('id', $field)
            ->first();
      };

      return $row;
   }
   public function insert($data)
   {
      try {
         $id = $this->db->table('users')->insert($data);
         if ($id === false || $id === null) return false;
         return $id ?? true;
      } catch (Exception $e) {
         Logger::warning($e->getMessage());
         return false;
      };
   }

   public function update($data = [])
   {
      try {
         $id = $this->db->table('users')
            ->where('id', $data['id'])
            ->update($data);
         return $id !== false;
      } catch (Exception $e) {
         Logger::warning($e->getMessage());
         return false;
      };
   }

   public function create($data)
   {
      $user = $this->find($data['id']);
      try {
         if ($user) {
            $id = $this->db->table('users')
               ->where('id', $data['id'])
               ->update($data);
            return $id !== false;
         }
         $id = $this->db->table('users')->insert($data);
         if ($id === false || $id === null) return false;
         return $id ?? true;
      } catch (Exception $e) {
         Logger::warning($e->getMessage());
         return false;
      };
   }
};
