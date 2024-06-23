<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\SearchException;
use Exception;
use PDO;
use PDOException;

/** */
class SearchRepository extends BaseRepository
{

    private function search(array $params, $page = 1, $perPage = 10)
    {

        $produit = $params["produit"];
        if (is_numeric($produit)) {
            $produit = (int)$produit;
        } else {
            $produit = strtolower($produit);
        }
        $params["position"] = $params["position"] ?? null;

        if (!isset($params["position"])) {
            $QUERY_SEARCH = "SELECT ph.*, com.libelle_commune,
             (( (TIME(NOW()) BETWEEN '08:00:00' AND '20:59:59') AND DAYOFWEEK(NOW()) NOT IN (1, 7)) OR ph.garde=1) as etat_ouverture
            FROM pharmacie_has_produits  php,
             pharmacies  ph, produits pr,
              categories cat,communes com
              WHERE php.id_pharmacie=ph.id_pharmacie 
              AND php.id_produit=pr.id_produit 
              AND pr.id_categorie=cat.id_categorie
              AND ph.id_commune = com.id_commune
              AND php.statut=1
              AND ph.statut=1
              AND (pr.id_produit='$produit' OR LOWER(pr.designation)='$produit')
              ORDER BY ph.garde, etat_ouverture DESC
               ";
        } else {
            $position = $params["position"];
            $QUERY_SEARCH = "SELECT ph.* , com.libelle_commune,
            (( (TIME(NOW()) BETWEEN '08:00:00' AND '20:59:59') AND DAYOFWEEK(NOW()) NOT IN (1, 7)) OR ph.garde=1) as etat_ouverture,
            ROUND(haversine(extract_latitude('$position'),extract_longitude('$position'),
            ph.latitude,ph.longitude)) as distance_km, CONCAT(ph.latitude, ', ', ph.longitude) as coordinates
            FROM pharmacie_has_produits  php,
             pharmacies  ph, produits pr,
              categories cat, communes com
              WHERE php.id_pharmacie=ph.id_pharmacie 
              AND php.id_produit=pr.id_produit 
              AND pr.id_categorie=cat.id_categorie
              AND ph.id_commune = com.id_commune
              AND php.statut=1
              AND ph.statut=1
              AND (pr.id_produit='$produit' OR LOWER(pr.designation)='$produit') 
              ORDER BY  etat_ouverture,ph.garde DESC, distance_km ASC
              
              ";
        }

        return  $this->getResultsWithPagination($QUERY_SEARCH, $page, $perPage);
    }

    public function insert($params = [],  $page = 1, $perPage = 10)
    {
        $produit = (int)$params["produit"];
        $params["position"] = $params["position"] ?? null;

        $connectedAccount = (array)$params["connectedAccount"];

        $numero_telephone = $connectedAccount["numero_telephone"];

        if ($numero_telephone)
            $solde_recherche = $this->database->query("SELECT solde_recherche FROM comptes WHERE numero_telephone='$numero_telephone'")->fetchColumn();
        else $solde_recherche = -1;


        // vérification du solde du compte
        if (!$solde_recherche || $solde_recherche = 0) {
            throw new SearchException("Votre solde de recherche est insuffisant", 400);
        }



        $results = $this->search($params, $page, $perPage)["content"];

        $nombre_resultat = count($results);

        $searchCount = $this->database->query("SELECT id_recherche FROM recherches")->rowCount();

        $code_recherche = "SEARCH-" . $searchCount + 1;
        $QUERY_INSERT_SEARCH = "INSERT INTO recherches (id_produit,position_recherche, code_recherche,numero_compte)VALUES (:id_produit,:position, :code,:numero_compte)";
        $insert_sarch_params = ["id_produit" => $produit, "position" => $params["position"], "code" => $code_recherche, "numero_compte" => $numero_telephone];

        $QUERY_INSERT_RESULT = "INSERT INTO resultats_recherche (id_recherche,nombre_resultat) VALUES (:id_recherche,:nombre_resultat)";
        $insert_result_params = ["nombre_resultat" => $nombre_resultat];

        $QUERY_INSERT_LIGNE_RESULT = "INSERT INTO lignes_resultat_recherche(id_resultat, id_pharmacie) VALUES (:id_resultat,:id_pharmacie)";
        $insert_lignes_resultat_params = [];

        $QUERY_UPDATE_SOLDE_RECHERCHE = "UPDATE 
        comptes SET solde_recherche=:solde_recherche 
        WHERE numero_telephone = :numero_telephone";

        $faturation_params = ["numero_telephone" => $numero_telephone];
        // Si la recherche existe on fait une simple consultation
        if ($this->exists("LOWER(code_recherche) = '$code_recherche'")) {
            return  $this->getResultsWithPagination($QUERY_INSERT_SEARCH, $page, $perPage);
        }

        try {


            // Début de la transaction
            $this->database->beginTransaction();

            // Insertion de la recherche
            $insert_search_ok = $this->database->prepare($QUERY_INSERT_SEARCH)->execute($insert_sarch_params);
            if (!$insert_search_ok)
                $this->database->rollBack();

            $id_recherche = $this->database->lastInsertId();
            $insert_result_params["id_recherche"] = $id_recherche;

            // Insertion du nombre de resultat
            $insert_result_ok = $this->database->prepare($QUERY_INSERT_RESULT)->execute($insert_result_params);

            if (!$insert_result_ok)
                $this->database->rollBack();
            $id_resultat = $this->database->lastInsertId();
            $insert_lignes_resultat_params["id_resultat"] = $id_resultat;

            // Insertion des lignes de resultat
            if ($nombre_resultat > 0) {

                $faturation_params['solde_recherche'] =  $solde_recherche - 1;
                // Facturation de la recherche
                $update_ok = $this->database->prepare($QUERY_UPDATE_SOLDE_RECHERCHE)->execute($faturation_params);

                foreach ($results as $key => $result) {
                    $insert_lignes_resultat_params["id_pharmacie"] = $result->id_pharmacie;
                    $insert_ligne_result_ok = $this->database->prepare($QUERY_INSERT_LIGNE_RESULT)->execute($insert_lignes_resultat_params);

                    if (!$insert_ligne_result_ok)
                        $this->database->rollBack();
                }
            }
            $this->database->commit();
            return  $this->search($params, $page, $perPage);
        } catch (Exception $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }



    function siginin($params = [])
    {

        $numero = $params["numero"];
        $password = $params["password"];
        $query = "SELECT * FROM comptes WHERE numero_telephone=:numero";
        $statement = $this->database->prepare($query);
        $statement->bindParam('numero', $numero);
        $statement->execute();
        $compte = $statement->fetch(PDO::FETCH_ASSOC);

        if (empty($compte)) {
            throw new SearchException(
                'Login echoué: login ou password incorrect.',
                400
            );
        }

        if (!password_verify($password, $compte["password"])) {
            throw new SearchException(
                'Login echoué: login ou password incorrect.',
                400
            );
        }
        unset($compte['password']);

        if ($compte["statut"] == 0) {
            throw new SearchException("Vous avez été désactivé.", 400);
        }

        return $compte;
    }

    public function signup($params = [])
    {
        // Ajustement des paramètres facltatifs
        $params["nom"] = $params["nom"] ?? null;
        $params["prenom"] = $params["prenom"] ?? null;
        $params["pays"] = $params["pays"] ?? "NE";
        $params["password"] = password_hash($params["password"], PASSWORD_BCRYPT, ['cost' => 12]);
        $numero = $params["numero"];
        try {
            if ($this->accountExists("numero_telephone='$numero'")) {
                throw new SearchException("Ce compte est déjà créé.");
            }

            $QUERY = "INSERT INTO comptes(numero_telephone,nom,prenom,password,pays)
                VALUES (:numero,:nom,:prenom,:password,:pays)";

            $this->database->prepare($QUERY)->execute($params);
            return $this->getOneAccount($this->database->lastInsertId());
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function updateAccount($params = [])
    {
        // Ajoustement des paramètres facltatifs
        $nom = $params["nom"] ?? null;
        $prenom = $params["prenom"] ?? null;
        $pays = $params["pays"] ?? "NE";
        $numero = $params["numero"];
        $query_params = ["nom" => $nom, "prenom" => $prenom, "pays" => $pays, "numero" => $numero];
        try {
            $account = $this->accountExists("numero_telephone='$numero'");

            if (!$account) {
                throw new SearchException("Ce compte n'existe pas");
            }
            $QUERY = "UPDATE comptes
             SET nom=:nom,
              prenom=:prenom, 
              pays=:pays
               WHERE numero_telephone=:numero";

            $this->database->prepare($QUERY)->execute($query_params);
            return $this->getOneAccount($account->numero_telephone);
        } catch (PDOException $exception) {
            throw $exception;
        }
    }
    public function update($id, $params, $critere = 'true')
    {
    }

    public function delete($id, $crietre = "true")
    {
    }

    public function getAll($critere = '', $page = 1, $perPage = 10)
    {
        $QUERY = "SELECT id_user as id, nom_user as nom, prenom_user as prenom, 
        login ,users.id_profil as profil,libelle_profil, id_pharmacie as pharmacie, users.statut
        FROM users, profils WHERE users.id_profil=profils.id_profil AND profils.level=2
          AND $critere";
        return  $this->getResultsWithPagination($QUERY, $page, $perPage);
    }

    public function getOne($id, $critere = 'true')
    {
        $QUERY = "SELECT id_user as id, nom_user as nom, prenom_user as prenom, 
        login ,users.id_profil as profil, libelle_profil, id_pharmacie as pharmacie, users.statut
        FROM users, profils WHERE users.id_profil=profils.id_profil AND profils.level=2
         AND users.id_user='$id'  AND $critere";
        $user = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($user)) {
            throw new SearchException("Search non trouvé.", 404);
        }

        $idProfil = $user['profil'];
        $subCritera = "id_action IN (SELECT id_action FROM profil_has_actions WHERE id_profil='$idProfil') AND level=2";

        $actions = $this->database->query("SELECT url_action as url, methode 
                                    FROM actions WHERE id_action IN (SELECT id_action FROM profil_has_actions WHERE id_profil='$idProfil')")
            ->fetchAll(PDO::FETCH_ASSOC);
        $menu = $this->database->query("SELECT id_action as id, 
                                    url_action as url, icon, libelle_action as label, description_action as description 
                                FROM actions WHERE (is_menu=1  AND $subCritera) ORDER BY ordre")
            ->fetchAll(PDO::FETCH_ASSOC);

        $dashboardMenu  = $this->database->query("SELECT id_action as id, 
                                    url_action as url, icon, libelle_action as label, description_action as description 
                                FROM actions WHERE url_action='/v1/admin/dashboard'")
            ->fetch(PDO::FETCH_ASSOC);

        $menu = array_merge(array($dashboardMenu), $menu);
        return array_merge($user, ["actions" => $actions, "menu" => $menu]);
    }

    public function getOneAccount($id, $critere = 'true')
    {
        $QUERY = "SELECT * FROM comptes WHERE id_compte ='$id' OR numero_telephone ='$id' AND $critere";
        $compte = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($compte)) {
            throw new SearchException("Compte introuvable.", 404);
        }
        unset($compte["password"]);
        return $compte;
    }

    public function getSearchHistories(string $numero)
    {
        $QUERY = "SELECT * FROM recherches WHERE numero_compte = '$numero'";

        $recherches = $this->database->query($QUERY)->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($recherches)) {
            foreach ($recherches as $index => $recherche) {

                $id_recherche = $recherche["id_recherche"];

                $QUERY_GET_SEARCH_RESULTS = "SELECT * FROM  resultats_recherche WHERE id_recherche='$id_recherche'";

                $resulat = $this->database->query($QUERY_GET_SEARCH_RESULTS)->fetch(PDO::FETCH_ASSOC);

                $recherches[$index]["resultat"]["nombre"] = $resulat["nombre_resultat"];

                if ($resulat["nombre_resultat"] > 0) {

                    $id_resultat = $resulat["id_resultat"];

                    $QUERY_GET_LINE_SEARCH_RESULTS =
                        "SELECT pharmacies.* 
                        FROM  lignes_resultat_recherche, pharmacies
                         WHERE lignes_resultat_recherche.id_pharmacie=pharmacies.id_pharmacie 
                         AND  id_resultat='$id_resultat' ";
                    $lignes_resultats = $this->database->query($QUERY_GET_LINE_SEARCH_RESULTS)->fetchAll(PDO::FETCH_ASSOC);

                    $recherches[$index]["resultat"]["lignes"] = $lignes_resultats;
                }
            }
        }
        return array_merge(["nombre_recherches" => count($recherches), "recherches" => $recherches]);;
    }

    public function changePassowrd($id, $oldPassword, $newPassowrd)
    {
        try {
            $this->getOneAccount($id);
            $compte = $this->database->query("SELECT password FROM comptes WHERE numero_telephone='$id'")->fetch(PDO::FETCH_ASSOC);
            if (!password_verify($oldPassword, $compte["password"])) {
                throw new SearchException("Mot de passe incorrect", 403);
            }
            $QUERY = "UPDATE comptes SET password=:password WHERE numero_telephone=:id";
            $newPassowrd = password_hash($newPassowrd, PASSWORD_BCRYPT);
            $this->database->prepare($QUERY)->execute(["password" => "$newPassowrd", "id" => $id]);
            return true;
        } catch (SearchException $exception) {
            throw $exception;
        }
    }

    public function resetPassword($id)
    {
        try {
            $this->getOne($id);
            $newPassowrd = password_hash("Deafult2024", PASSWORD_BCRYPT);
            $QUERY = "UPDATE users SET password='$newPassowrd' WHERE id_user='$id'";
            $this->database->query($QUERY)->execute();
            return "Le nouveau mot de passe est 'Deafult2024'";
        } catch (SearchException $exception) {
            throw $exception;
        }
    }

    public function exists($critere = 'true'): bool
    {
        return $this->database->query("SELECT id_recherche FROM recherches WHERE $critere")->rowCount() > 0;
    }

    public function accountExists($critere = 'true'): bool|object
    {

        return $this->database->query("SELECT id_compte, numero_telephone FROM comptes WHERE $critere")->fetchObject();
    }

    private function profilExists($id, $crietre = "true"): bool
    {
        return $this->database->query("SELECT * FROM profils WHERE id_profil='$id' AND $crietre")->rowCount() > 0;
    }

    public function getStatistiquePublic()
    {
        /*******************************
         * Stats sur les recherches
         ******************************/
        // 1. Nombre total de recherches
        $nb_search = $this->database->query("SELECT * FROM recherches")->rowCount();


        /************************************
         * Fin stats sur les recherches
         ************************************/


        /*********************************
         * Stats sur les produits
         ********************************/


        //1. Le nombre total de produit disponibles
        $nb_produit = $this->database->query(
            "SELECT produits.id_produit 
          FROM produits 
          "
        )->rowCount();



        /*********************************
         * Fin Stats sur les produits
         ********************************/


        /********************************** 
         * Stats sur les pharmacies
         ************************************/
        //1. Le nombre total de pharmacie
        $nb_pharmacie = $this->database->query("SELECT * FROM pharmacies")->rowCount();

        //2. Les pharmacies de garde
        $pharmacie_garde = $this->database->query(
            "SELECT * 
          FROM pharmacies , communes
          WHERE pharmacies.id_commune= communes.id_commune AND  garde=1
          "
        )->fetchAll(PDO::FETCH_ASSOC);

        // 3.Le nombre de pharmacie active
        $nb_pharmacie_active = $this->database->query(
            "SELECT pharmacies.id_pharmacie 
          FROM pharmacies 
          WHERE statut=1
          "
        )->rowCount();


        // 3.Le nombre de pharmacie de garde
        $nb_pharmacie_garde = $this->database->query(
            "SELECT pharmacies.id_pharmacie 
          FROM pharmacies 
          WHERE garde=1
          "
        )->rowCount();





        return  [
            "searchs" => [
                "total" => $nb_search
            ],
            "produits" => [
                "total" => $nb_produit
            ],
            "pharmacies" => [
                "nombre_garde" => $nb_pharmacie_garde,
                "total" => $nb_pharmacie,
                "garde" => $pharmacie_garde,
                "active" => $nb_pharmacie_active
            ]
        ];
    }


    public function pharmacieGarde(array $params)
    {


        $params["position"] = $params["position"] ?? null;

        if (!isset($params["position"])) {
            $QUERY_PHARAMCIE = "SELECT ph.*, com.libelle_commune
            FROM pharmacies  ph, communes com
              WHERE ph.id_commune = com.id_commune
              AND ph.garde=1
              ORDER BY ph.garde DESC
               ";
        } else {
            $position = $params["position"];
            $QUERY_PHARAMCIE = "SELECT ph.* , com.libelle_commune,
            ROUND(haversine(extract_latitude('$position'),extract_longitude('$position'),
            ph.latitude,ph.longitude)) as distance_km, CONCAT(ph.latitude, ', ', ph.longitude) as coordinates
            FROM 
             pharmacies  ph, communes com
              WHERE ph.id_commune = com.id_commune
              AND ph.garde=1
              ORDER BY ph.garde DESC, distance_km ASC
              
              ";
        }

        $pharmacie_garde = $this->database->query($QUERY_PHARAMCIE)->fetchAll(PDO::FETCH_ASSOC);


        return  $pharmacie_garde;
    }
}
