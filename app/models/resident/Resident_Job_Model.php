<?php

/**
 * Model Resident_Job_Model
 * Merepresentasikan data pekerjaan dari tabel 'residents_job'
 * Relasi:
 *   - HasMany Resident_Model (residents yang memiliki pekerjaan ini)
 */
class Resident_Job_Model extends BaseModel
{
   protected $table = 'residents_job';
   protected $primaryKey = 'id';
   protected $fillable = ['id', 'name'];
   protected $casts = [
      'id' => 'int',
   ];

   /**
    * Relasi: ResidentJob_model HasMany Resident_model
    * Mengambil semua penduduk yang memiliki pekerjaan ini
    */
   public function relationResidents()
   {

      return (new HasMany($this, Residents_Model::class, 'id_job'))->getResults();
   }
}
