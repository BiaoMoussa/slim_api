<?php

declare(strict_types=1);

namespace App\Admin\Repository;



use App\Admin\Exception\CategorieException;
use PDO;
use PDOException;

/** */
class CategorieRepository extends BaseRepository
{

   
    public function insert($params = [])
    {
     
           
        try {
         
            $QUERY = "INSERT INTO categories
                            (code_categorie, libelle_categorie, created_at, created_by)
                            VALUES(:code_categorie, :libelle_categorie, :created_at, :created_by)";
            $this->database->prepare($QUERY)->execute($params);
            return $this->getOne($this->database->lastInsertId());
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function update($id, $params, $critere = 'true')
    {   
        
        $categorie = $this->getOne($id);
        $code_categorie = $params["code_categorie"];
        if ($this->exists("code_categorie='$code_categorie' AND id_categorie <> $id")) {
            throw new CategorieException("Ce code existe existe déjà");
        }

        
      
        $params["code_categorie"] = $params["code_categorie"] ?? $categorie["code_categorie"];
        $params["libelle_categorie"] = $params["libelle_categorie"] ?? $categorie["libelle_categorie"];
        try {
            $QUERY = "UPDATE categories 
                        SET code_categorie=:code_categorie,libelle_categorie=:libelle_categorie,
                        modified_at=:modified_at,modified_by=:modified_by
                        WHERE id_categorie = $id AND $critere";
            $this->database->prepare($QUERY)->execute($params);
            return $this->getOne($id);
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function delete($id, $critere = "true")
    {
        $QUERY = "DELETE FROM categories WHERE id_categorie=$id AND $critere";
        try {
            $this->getOne($id);
            $this->database->query($QUERY)->execute();
        } catch (CategorieException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function getAll($critere = 'true', $page = 1, $perPage = 10)
    {
        $QUERY = "SELECT *
        FROM categories WHERE  $critere";
        return  $this->getResultsWithPagination($QUERY, $page, $perPage);
    }

    public function getOne($id, $critere = 'true')
    {
        $QUERY = "SELECT *
        FROM categories WHERE id_categorie='$id' AND $critere";
        $categorie = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($categorie)) {
            throw new CategorieException("Categorie non trouvé.", 404);
        }
        return $categorie;
    }



    public function exists($critere = 'true'): bool
    {
        return $this->database->query("SELECT * FROM categories WHERE $critere")->rowCount() > 0;
    }

}
