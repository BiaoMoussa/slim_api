<?php

declare(strict_types=1);

namespace App\Admin\Repository;


use App\Admin\Exception\ActionException;
use App\Admin\Exception\PharmacieHasProduitException;
use PDO;
use PDOException;

class PharmacieHasProduitRepository extends BaseRepository
{

    public function __construct()
    {
        $this->database = $GLOBALS["pdo"];
    }

    public function insert($params = [])
    {
        try {
        } catch (ActionException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function update($id, $params, $critere = 'true')
    {

        try {
            $produit = $this->getOne($id, $critere);

            $QUERY = "UPDATE pharmacie_has_produits 
                    SET prix=:prix,
                    statut=:statut,
                    modified_by=:modified_by,
                    modified_at=:modified_at
                    WHERE id_pharmacie_has_produit=$id";
            $this->database->prepare($QUERY)->execute($params);
            return $this->getOne($id);
        } catch (ActionException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function delete($id, $critere = "true")
    {
        $QUERY = "DELETE FROM pharmacie_has_produits  WHERE id_pharmacie_has_produit=$id AND $critere";
        try {
            $this->getOne($id);
            $this->database->query($QUERY)->execute();
        } catch (ActionException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function getAll($critere = 'true', $page = 1, $perPage = 10)
    {
        $QUERY = "SELECT php.*, pr.*, ph.nom_pharmacie, php.statut as statut_produit FROM pharmacie_has_produits php ,produits pr, pharmacies ph 
                     WHERE php.id_produit=pr.id_produit AND php.id_pharmacie=ph.id_pharmacie AND $critere";
        return $this->getResultsWithPagination($QUERY, $page, $perPage);
    }

    public function getOne($id_pharmacie_has_produit, $critere = 'true')
    {
        $QUERY = "SELECT php.*, pr.*, ph.nom_pharmacie, php.statut as statut_produit FROM pharmacie_has_produits php ,produits pr, pharmacies ph 
                     WHERE php.id_produit=pr.id_produit AND php.id_pharmacie=ph.id_pharmacie AND php.id_pharmacie_has_produit=$id_pharmacie_has_produit AND $critere";
        $resultat = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($resultat)) {
            throw new ActionException("Produit non trouvé", 404);
        }
        return $resultat;
    }

    public function exists($critere = 'true'): bool
    {
        $QUERY = "SELECT * FROM groupe_gardes WHERE  $critere";
        return $this->database->query($QUERY)->rowCount() > 0;
    }

    public function getPharmacieHasProduits($idPharmacie, $critere = "true", $page = 1, $perPage = 10)
    {
        $this->pharmacieExists($idPharmacie);
        $QUERY = "SELECT pr.*,php.*,cat.* FROM pharmacie_has_produits php ,produits pr, pharmacies ph , categories cat
                 WHERE php.id_produit=pr.id_produit 
                 AND php.id_pharmacie=ph.id_pharmacie 
                 AND pr.id_categorie = cat.id_categorie
                 AND ph.id_pharmacie=$idPharmacie AND $critere";
        return $this->getResultsWithPagination($QUERY, $page, $perPage);
    }

    public function addPharmacieHasProduit($id_pharmacie, $produits = [], $created_by, $created_at)
    {

        try {
            $this->database->beginTransaction();
            $this->pharmacieExists($id_pharmacie);
            if (!empty($produits)) {

                // $i=0;
                foreach ($produits as $value) {
                    $data = [];
                    $this->produitExists($value['id_produit']);
                    if ($this->relationExists($value['id_produit'], $id_pharmacie)) continue;
                    $data['id_produit'] = $value['id_produit'];
                    $data['prix'] = $value['prix'];
                    $data['id_pharmacie'] = $id_pharmacie;
                    $data['created_at'] = $created_at;
                    $data['created_by'] = $created_by;
                    $QUERY = "INSERT INTO pharmacie_has_produits (id_produit, id_pharmacie, prix, created_by, created_at) VALUES( :id_produit, :id_pharmacie, :prix, :created_by, :created_at)";

                    $this->database->prepare($QUERY)->execute($data);
                    $this->setIADataSets($id_pharmacie, $value["id_produit"], date("Y-m-d H:i:s"), 1);
                }
            }

            $this->database->commit();
            return $this->getPharmacieHasProduits($id_pharmacie);
        } catch (PDOException $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }

    public function ChangerStatutPharmacieHasProduit($pharmacie_has_produit, $status, $modified_by, $modified_at, $critere = "true")
    {
        try {
            $this->database->beginTransaction();

            $pharmacie_has_produit = $this->database
                ->query("SELECT id_pharmacie, id_produit FROM pharmacie_has_produits WHERE id_pharmacie_has_produit=$pharmacie_has_produit")
                ->fetch(PDO::FETCH_COLUMN);

            $data = [];
            $data['modified_at'] = $modified_at;
            $data['modified_by'] = $modified_by;
            $data['statut'] = $status;

            $QUERY = "UPDATE  pharmacie_has_produits php SET statut=:statut, modified_by=:modified_by, modified_at=:modified_at WHERE id_pharmacie_has_produit=$pharmacie_has_produit  AND $critere";
            $this->database->prepare($QUERY)->execute($data);
            $this->setIADataSets($pharmacie_has_produit["id_pharmacie"], $pharmacie_has_produit["id_produit"], date("Y-m-d H:i:s"), $status);
            $this->database->commit();
            return $this->getOne($pharmacie_has_produit);;
        } catch (PharmacieHasProduitException $exception) {
            $this->database->rollBack();
            throw $exception;
        } catch (PDOException $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }


    public function loadXlsxFile($idPharmacie, $idProduitsIndisponibles = [], $modified_by)
    {
        try {
            $this->database->beginTransaction();
            // Prendre tous les produits
            $produits = $this->database->query("SELECT id_produit FROM produits")->fetchAll(PDO::FETCH_COLUMN);
            $dataDispo = [];
            $dataDispo['modified_at'] = date('Y-m-d H:i:s');
            $dataDispo['modified_by'] = $modified_by;
            $dataDispo['id_pharmacie'] = $idPharmacie;
            $QUERY_DISPO = "UPDATE  pharmacie_has_produits php SET statut=1, modified_by=:modified_by,
                                       modified_at=:modified_at WHERE id_pharmacie=:id_pharmacie";
            $this->database->prepare($QUERY_DISPO)->execute($dataDispo);
           
            // Écriture dans les datasets
            foreach ($produits as $produit) {
                if (!in_array($produit, $idProduitsIndisponibles)) {
                    $this->setIADataSets($idPharmacie, $produit, date("Y-m-d H:i:s"), 1);
                }
            }

            if (!empty($idProduitsIndisponibles)) {
                $QUERY = "UPDATE  pharmacie_has_produits php SET statut=0, modified_by=:modified_by,
                                       modified_at=:modified_at WHERE id_pharmacie=:id_pharmacie  AND id_produit=:id_produit";
                $data = [];
                $data['modified_at'] = date('Y-m-d H:i:s');
                $data['modified_by'] = $modified_by;
                $data['id_pharmacie'] = $idPharmacie;

                foreach ($idProduitsIndisponibles as $produit) {
                    $data['id_produit'] = $produit;
                    $this->database->prepare($QUERY)->execute($data);
                    $this->setIADataSets($idPharmacie, $produit, date("Y-m-d H:i:s"), 0);
                }
            } else {
                throw new PharmacieHasProduitException("Tous les produits sont disponibles.", 400);
            }

            $this->database->commit();
            return true;
        } catch (PharmacieHasProduitException $exception) {
            $this->database->rollBack();
            throw $exception;
        } catch (PDOException $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }

    public function relationExists($idProduit, $pharmacie)
    {
        return $this->database
            ->query("SELECT * FROM pharmacie_has_produits WHERE id_pharmacie='$pharmacie' AND id_produit='$idProduit'")
            ->rowCount() > 0;
    }

    public function findPharmacieByCode($codePharmacie)
    {
        $pharmacie = $this->database
            ->query("SELECT id_pharmacie,code_pharmacie FROM pharmacies WHERE code_pharmacie='$codePharmacie'")
            ->fetch();
        if (!$pharmacie) {
            throw new PharmacieHasProduitException("Pharmacie introuvale", 404);
        }
        return $pharmacie;
    }

    private function produitExists($produit)
    {
        $control_existence_produit = $this->database
            ->query("SELECT * FROM produits WHERE id_produit='$produit'")
            ->rowCount() > 0;
        if (!$control_existence_produit) {
            throw new PharmacieHasProduitException("Le produit $produit n'existe pas.");
        }
    }

    private function pharmacieExists($pharmacie)
    {
        $control_existence_pharmacie = $this->database
            ->query("SELECT * FROM pharmacies WHERE id_pharmacie='$pharmacie'")
            ->rowCount() > 0;
        if (!$control_existence_pharmacie) {
            throw new PharmacieHasProduitException("La pharmacie $pharmacie n'existe pas.");
        }
    }


    /**
     * Écrire une ligne dans les dataSets
     * @param mixed $idPharmacie
     * @param mixed $idProduit
     * @param mixed $dateDisponibilite
     * @param mixed $disponibilite
     * @return bool
     */
    private function setIADataSets($idPharmacie, $idProduit, $dateDisponibilite, $disponibilite)
    {

        try {
            $dataSet = [
                "id_pharmacie" => $idPharmacie,
                "id_produit" => $idProduit,
                "date_disponibilite" => $dateDisponibilite,
                "disponibilite" => $disponibilite
            ];
            $QUERY = "INSERT INTO ia_datasets (id_pharmacie, id_produit, date_disponibilite, disponiblite)
             VALUES(:id_pharmacie, :id_produit, :date_disponibilite, :disponibilite)";
            $this->database->prepare($QUERY)->execute($dataSet);
            return true;
        } catch (PDOException $exception) {
           
            throw $exception;
        }
    }
}
