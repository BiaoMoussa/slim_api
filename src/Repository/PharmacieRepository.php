<?php

declare(strict_types=1);

namespace App\Repository;

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

    public function getAll( $crietre= '') {}

    public function getOne($id, $crietre = ''){
    }
}
