<?php

/**
 * Model Resident_Marital_Model
 * Merepresentasikan status pernikahan dari tabel 'residents_marital'
 * Relasi:
 *   - HasMany Resident_Model (residents yang memiliki status pernikahan ini)
 */
class Resident_Marital_Model extends BaseModel
{
   protected $table = 'residents_marital';
   protected $primaryKey = 'id';
   protected $fillable = ['id', 'name'];
   protected $casts = [
      'id' => 'int',
   ];

   /**
    * Relasi: ResidentMarital_model HasMany Resident_model
    * Mengambil semua penduduk yang memiliki status pernikahan ini
    */
   public function relationResidents()
   {
      return (new HasMany($this, Residents_Model::class, 'id_marital'))->getResults();
   }
}
