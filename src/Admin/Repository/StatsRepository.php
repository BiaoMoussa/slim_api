<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use PDO;

/** */
class StatsRepository  extends BaseRepository
{
    public function getAll($critere = '', $page = 1, $perPage = 10)
    {
        /*******************************
         * Stats sur les recherches
         ******************************/
        // 1. Nombre total de recherches
        $nb_search = $this->database->query("SELECT * FROM recherches")->rowCount();

        // 2. Nombre total de recherches avec resultat
        $search_with_result = $this->database->query(
            "SELECT recherches.id_recherche 
            FROM recherches, resultats_recherche
            WHERE recherches.id_recherche = resultats_recherche.id_recherche 
            AND resultats_recherche.nombre_resultat > 0"
        )->rowCount();

        // 3. Nombre total de recherches sans resultat
        $search_without_result = $this->database->query(
            "SELECT recherches.id_recherche 
            FROM recherches, resultats_recherche
            WHERE recherches.id_recherche = resultats_recherche.id_recherche 
            AND resultats_recherche.nombre_resultat = 0"
        )->rowCount();

        /************************************
         * Fin stats sur les recherches
         ************************************/




        /*********************************
         * Stats sur les produits
         ********************************/
        //1. Le nombre total de produit
        $nb_produit = $this->database->query("SELECT * FROM produits")->rowCount();

        //2. Le nombre total de produit disponibles
        $nb_produit_disponible = $this->database->query(
            "SELECT produits.id_produit 
          FROM produits 
          WHERE produits.id_produit IN (
          SELECT id_produit 
          FROM pharmacie_has_produits 
          WHERE produits.id_produit AND pharmacie_has_produits.statut=1
          )
          "
        )->rowCount();

        //3. Le nombre total de produit non disponibles
        $nb_produit_indisponible = $this->database->query(
            "SELECT produits.id_produit 
          FROM produits 
          WHERE produits.id_produit IN (
          SELECT id_produit 
          FROM pharmacie_has_produits 
          WHERE produits.id_produit AND pharmacie_has_produits.statut=0
          )
          "
        )->rowCount();
        // 4. Le nombre de produits par catégorie
        $nb_produit_par_categorie = $this->database->query(
            "SELECT categories.libelle_categorie as categorie, COUNT(produits.id_produit) as nb_produit , couleur, (COUNT(produits.id_produit)/$nb_produit)*100 as ratio
          FROM produits , categories
          WHERE produits.id_categorie = categories.id_categorie
          GROUP BY categories.libelle_categorie
          "
        )->fetchAll(PDO::FETCH_ASSOC);

        //5.Les 20 premiers produits les plus recherchés avec ou sans resulats
        $produits_plus_recherches = $this->database->query(
            "SELECT produits.designation,
            COUNT(recherches.id_produit) as rechereches,
            COUNT(CASE WHEN resultats_recherche.nombre_resultat > 0 THEN 1 END) as avec_resulat,
            COUNT(CASE WHEN resultats_recherche.nombre_resultat = 0 THEN 1 END) as sans_resulat
            FROM produits, recherches, resultats_recherche
            WHERE produits.id_produit = recherches.id_produit
            AND resultats_recherche.id_recherche = recherches.id_recherche
            GROUP BY produits.designation
            ORDER BY rechereches DESC
            LIMIT 0,20
            "
        )->fetchAll(PDO::FETCH_ASSOC);
        /*********************************
         * Fin Stats sur les produits
         ********************************/


        /********************************** 
         * Stats sur les pharmacies
         ************************************/
        //1. Le nombre total de pharmacie
        $nb_pharmacie = $this->database->query("SELECT * FROM pharmacies")->rowCount();

        //2. Le nombre de pharmacie de pharmacies de garde
        $nb_pharmacie_garde = $this->database->query(
            "SELECT pharmacies.id_pharmacie 
          FROM pharmacies 
          WHERE garde=1
          "
        )->rowCount();

        // 3.Le nombre de pharmacie active
        $nb_pharmacie_active = $this->database->query(
            "SELECT pharmacies.id_pharmacie 
          FROM pharmacies 
          WHERE statut=1
          "
        )->rowCount();

        //4. Le nombre de produit disponible et non disponible par pharmacie
        $nb_produit_par_pharmacie = $this->database->query(
            "SELECT pharmacies.nom_pharmacie as pharmacie,
        COUNT(CASE WHEN pharmacie_has_produits.statut = '1' THEN 1 END) as nb_disponible,
        COUNT(CASE WHEN pharmacie_has_produits.statut = '0' THEN 1 END) as nb_indisponible
        FROM produits, pharmacies, pharmacie_has_produits
        WHERE produits.id_produit = pharmacie_has_produits.id_produit
        AND pharmacie_has_produits.id_pharmacie = pharmacies.id_pharmacie
        GROUP BY pharmacies.nom_pharmacie
          "
        )->fetchAll(PDO::FETCH_ASSOC);


        //5. Les 10  premières pharmacies dans les resultats de recherche
        $tens_pharmacies = $this->database->query(
            "SELECT pharmacies.nom_pharmacie, COUNT(lignes_resultat_recherche.id_pharmacie) as nb_resultat
            FROM pharmacies, lignes_resultat_recherche
            WHERE pharmacies.id_pharmacie=lignes_resultat_recherche.id_pharmacie
            GROUP BY pharmacies.nom_pharmacie
            ORDER BY nb_resultat DESC
            LIMIT 0,10
          "
        )->fetchAll(PDO::FETCH_ASSOC);
        /** *************************
         * Fin stats pharmacie 
         ************************/






        return  [
            "searchs" => [
                "total" => $nb_search,
                "with_result" => $search_with_result,
                "without_result" => $search_without_result,
                "with_ratio" => round($search_with_result / $nb_search) * 100,
                "without_ratio" => round($search_without_result / $nb_search) * 100,
            ],
            "produits" => [
                "total" => $nb_produit,
                "disponible" => $nb_produit_disponible,
                "indisponible" => $nb_produit_indisponible,
                "disponible_ratio" => round($nb_produit_disponible / $nb_produit) * 100,
                "indisponible_ratio" => round($nb_produit_indisponible / $nb_produit) * 100,
                "produit_per_categorie" => $nb_produit_par_categorie,
                "produit_plus_recherches" => $produits_plus_recherches
            ],
            "pharmacies" => [
                "total" => $nb_pharmacie,
                "garde" => $nb_pharmacie_garde,
                "active" => $nb_pharmacie_active,
                "disponibilite" => $nb_produit_par_pharmacie,
                "pharmacies_plus_resultats"=> $tens_pharmacies
            ]
        ];
    }


    public function getOne($id, $critere = "true")
    {
    }
    public function insert($params = [])
    {
    }
    public function update($id, $params, $critere = "true")
    {
    }
    public function delete($id, $critere = "true")
    {
    }
    public function exists($critere = "true"): bool
    {
        return true;
    }
}
