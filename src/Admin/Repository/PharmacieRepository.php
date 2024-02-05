<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use App\Exception\PharmacieException;

 class PharmacieRepository  extends BaseRepository
{
   
    public function __construct() {
        $this->database = $GLOBALS["pdo"];
    }
    public function insert($params =[])  {
        $QUERY = "INSERT INTO pharmacies (nom_pharmacie, teleph)";
    }

    public function update($id, $params, $crietre = ''){
    }

    public function delete($id,$crietre="") {
    }

    public function getAll($crietre= '',$page=1, $perPage= 10) {}

    public function getOne($id, $crietre = ''){
    }
    public function exists($critere = 'true') : bool{
        return false;
    }
}
