<?php

declare(strict_types=1);

namespace App\Repository;


use App\Exception\UserException;
use PDO;
use PDOException;

/** */
class UserRepository extends BaseRepository
{

    function login($params = [])
    {

        $login = trim(strtolower($params["login"]));
        $password = $params["password"];
        $query = "SELECT id_user as id, nom_user as nom, prenom_user as prenom, 
        login ,id_profil as profil, password, id_pharmacie as pharmacie, statut FROM users WHERE LOWER(login)=:login";
        $statement = $this->database->prepare($query);
        $statement->bindParam('login', $login);
        $statement->execute();
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        if (empty($user)) {
            throw new UserException(
                'Login echoué: login ou password incorrect.',
                400
            );
        }

        if (!password_verify($password, $user['password'])) {
            throw new UserException(
                'Login echoué: login ou password incorrect.',
                400
            );
        }
        unset($user['password']);
        $idProfil = $user['profil'];
        $pharmacie = $user['pharmacie'];
        $profilStatus = $this->database->query("SELECT statut FROM profils WHERE id_profil='$idProfil'")->fetchColumn();
        if ($profilStatus == 0  || $user["statut"] == 0) {
            throw new UserException("Vous avez été désactivé.");
        }
        $actions = $this->database->query("SELECT url_action as url, methode 
                                    FROM actions WHERE id_action IN 
                                    (SELECT id_action FROM profil_has_actions, profils
                                         WHERE profil_has_actions.id_profil='$idProfil' )")
            ->fetchAll(PDO::FETCH_ASSOC);
           
         return array_merge($user, ["actions" => $actions]);
         
    }

    public function insert($params = [])
    {
        $params["type"] = "admin";
        $login = $params["login"] = strtolower($params["login"]);
        $params["password"] = password_hash($params["password"], PASSWORD_BCRYPT);
        $profil = $params["profil"];
        $pharmacie = $params["pharmacie"];
        try {
            if ($this->exists("login='$login'")) {
                throw new UserException("Cet utilisateur existe déjà");
            }
            if (!$this->profilExists($profil, "id_societe='$pharmacie'")) {
                throw new UserException("Le profil $profil n'existe pas.");
            }
            $QUERY = "INSERT INTO users(nom_user,prenom_user,login,password,id_profil,id_pharmacie, type_user,created_by,modified_by)
                VALUES (:nom,:prenom,:login,:password,:profil,:pharmacie,:type,:createdBy,:updatedBy)";

            $this->database->prepare($QUERY)->execute($params);
            return $this->getOne($this->database->lastInsertId());
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function update($id, $params, $critere = 'true')
    {
        $user = $this->getOne($id);
        if (isset($params["login"]) && !is_null($params["login"]) && $user["login"] != $params["login"]) {
            $login = $params["login"];
            if ($this->exists("login='$login'")) {
                throw new UserException("Cet utilisateur existe déjà");
            }
        }
        $pharmacie = $params["pharmacie"];
        if (isset($params["profil"]) && !is_null($params["profil"]) && $user["profil"] != $params["profil"]) {
            $profil = $params["profil"];
            if (!$this->profilExists($profil, "id_societe='$pharmacie'")) {
                throw new UserException("Le profil $profil n'existe pas.");
            }
        }
        $params["id"] = $id;
        $params["type"] = "admin";
        $params["nom"] = $params["nom"] ?? $user["nom"];
        $params["prenom"] = $params["prenom"] ?? $user["prenom"];
        $params["prenom"] = $params["prenom"] ?? $user["prenom"];
        $params["login"] = $params["login"] ?? $user["login"];
        $params["profil"] = $params["profil"] ?? $user["profil"];
        try {
            $QUERY = "UPDATE users 
                        SET nom_user=:nom,prenom_user=:prenom,id_pharmacie=:pharmacie,
                        login=:login,id_profil=:profil,type_user=:type,modified_by=:updatedBy,modified_at=:updatedAt
                        WHERE id_user = :id AND $critere";
            $this->database->prepare($QUERY)->execute($params);
            return $this->getOne($id);
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function delete($id, $crietre = "true")
    {
        $QUERY = "DELETE FROM users WHERE id_user=$id AND $crietre";
        try {
            $this->getOne($id);
            $this->database->query($QUERY)->execute();
        } catch (UserException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
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
            throw new UserException("User non trouvé.", 404);
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

    public function changePassowrd($id, $oldPassword, $newPassowrd)
    {
        try {
            $this->getOne($id);
            $user = $this->database->query("SELECT password FROM users WHERE id_user='$id'")->fetch(PDO::FETCH_ASSOC);
            if (!password_verify($oldPassword, $user["password"])) {
                throw new UserException("Mot de passe incorrect", 403);
            }
            $QUERY = "UPDATE users SET password=:password WHERE id_user=:id";
            $newPassowrd = password_hash($newPassowrd, PASSWORD_BCRYPT);
            $this->database->prepare($QUERY)->execute(["password" => "$newPassowrd", "id" => $id]);
            return true;
        } catch (UserException $exception) {
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
        } catch (UserException $exception) {
            throw $exception;
        }
    }

    public function exists($critere = 'true'): bool
    {
        return $this->database->query("SELECT * FROM users WHERE $critere")->rowCount() > 0;
    }

    private function profilExists($id, $crietre = "true"): bool
    {
        return $this->database->query("SELECT * FROM profils WHERE id_profil='$id' AND $crietre")->rowCount() > 0;
    }
}
