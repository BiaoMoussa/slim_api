<?php

declare(strict_types=1);

namespace App\Admin\Repository;



use App\Admin\Exception\ProduitException;
use PDO;
use PDOException;

/** */
class ProduitRepository extends BaseRepository
{

   
    public function insert($params = [])
    {
     
           
        try {
      
            $id_categorie = $params["id_categorie"];
            $QUERY = "SELECT *
            FROM categories WHERE id_categorie='$id_categorie'";
            $categorie = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
            if (empty($categorie)) {
                throw new ProduitException("Categorie non trouvé.", 404);
            }
      
            $QUERY = "INSERT INTO produits
                            (designation, description, id_categorie,  created_at, created_by)
                            VALUES(:designation, :description, :id_categorie, :created_at, :created_by)";
            $this->database->prepare($QUERY)->execute($params);
            return $this->getOne($this->database->lastInsertId());
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function update($id, $params, $critere = 'true')
    {   
        
        $produit = $this->getOne($id);
        $designation = $params["designation"];
        $id_categorie = $params["id_categorie"];
        $QUERY = "SELECT *
        FROM categories WHERE id_categorie='$id_categorie'";
        $categorie = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($categorie)) {
            throw new ProduitException("Categorie non trouvé.", 404);
        }
        if ($this->exists("p.designation='$designation' AND p.id_categorie=$id_categorie AND p.id_produit <> $id")) {
            throw new ProduitException("Ce produit existe existe déjà");
        }

        
      
        $params["designation"] = $params["designation"] ?? $produit["designation"];
        $params["description"] = $params["description"] ?? $produit["description"];
        $params["id_categorie"] = $params["id_categorie"] ?? $produit["id_categorie"];
        try {
            $QUERY = "UPDATE produits 
                        SET designation=:designation,description=:description, id_categorie=:id_categorie,
                        modified_at=:modified_at,modified_by=:modified_by
                        WHERE id_produit = $id AND $critere";
            $this->database->prepare($QUERY)->execute($params);
            return $this->getOne($id);
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function delete($id, $critere = "true")
    {
        $QUERY = "DELETE FROM produits WHERE id_produit=$id AND $critere";
        try {
            $this->getOne($id);
            $this->database->query($QUERY)->execute();
        } catch (ProduitException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function getAll($critere = 'true', $page = 1, $perPage = 10)
    {
        $QUERY = "SELECT *
        FROM produits p , categories c WHERE p.id_categorie=c.id_categorie AND $critere";
        return  $this->getResultsWithPagination($QUERY, $page, $perPage);
    }

    public function getOne($id, $critere = 'true')
    {
        $QUERY = "SELECT *
        FROM produits p , categories c WHERE p.id_categorie=c.id_categorie AND id_produit='$id' AND $critere";
        $produit = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($produit)) {
            throw new ProduitException("Produit non trouvé.", 404);
        }
        return $produit;
    }



    public function exists($critere = 'true'): bool
    {
        return $this->database->query("SELECT * FROM produits p , categories c WHERE p.id_categorie=c.id_categorie AND $critere")->rowCount() > 0;
    }

}
