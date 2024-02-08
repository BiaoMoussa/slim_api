<?php

declare(strict_types=1);

namespace App\Admin\Repository;


use App\Admin\Exception\UserException;
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
        login ,id_profil as profil, password, id_pharmacie as pharmacie FROM users WHERE LOWER(login)=:login";
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
        $profilStatus = $this->database->query("SELECT statut FROM profils WHERE id_profil='$idProfil'")->fetchColumn();
        if($profilStatus==0){
            throw new UserException("Votre profil a été désactivé.");
        }
        $actions = $this->database->query("SELECT url_action as url, methode 
                                    FROM actions WHERE id_action IN (SELECT id_action FROM profil_has_actions WHERE id_profil='$idProfil')")
                                    ->fetchAll(PDO::FETCH_ASSOC);
        return array_merge($user, ["actions"=>$actions]);
    }

    public function insert($params = [])
    {
        $params["type"] = "super";
        $login = $params["login"] = strtolower($params["login"]);
        $params["password"] = password_hash($params["password"], PASSWORD_BCRYPT);
        $profil = $params["profil"];
        try {
            if ($this->exists("login='$login'")) {
                throw new UserException("Cet utilisateur existe déjà");
            }
            if (!$this->profilExists($profil)) {
                throw new UserException("Le profil $profil n'existe pas.");
            }
           
            $QUERY = "INSERT INTO users(nom_user,prenom_user,login,password,id_profil,type_user,created_by,modified_by)
                VALUES (:nom,:prenom,:login,:password,:profil,:type,:createdBy,:updatedBy)";
                 
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

        if (isset($params["profil"]) && !is_null($params["profil"]) && $user["profil"] != $params["profil"]) {
            $profil = $params["profil"];
            if (!$this->profilExists($profil)) {
                throw new UserException("Le profil $profil n'existe pas.");
            }
        }
        $params["id"] = $id;
        $params["type"] = "super";
        $params["nom"] = $params["nom"] ?? $user["nom"];
        $params["prenom"] = $params["prenom"] ?? $user["prenom"];
        $params["prenom"] = $params["prenom"] ?? $user["prenom"];
        $params["login"] = $params["login"] ?? $user["login"];
        $params["profil"] = $params["profil"] ?? $user["profil"];
        try {
            $QUERY = "UPDATE users 
                        SET nom_user=:nom,prenom_user=:prenom,
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
        login ,id_profil as profil
        FROM users WHERE 1 AND $critere";
        return  $this->getResultsWithPagination($QUERY, $page, $perPage);;
    }

    public function getOne($id, $critere = 'true')
    {
        $QUERY = "SELECT id_user as id, nom_user as nom, prenom_user as prenom, 
        login ,id_profil as profil
        FROM users WHERE id_user='$id' AND $critere";
        $user = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($user)) {
            throw new UserException("User non trouvé.", 404);
        }
        $idProfil = $user['profil'];
        $actions = $this->database->query("SELECT url_action as url, methode 
                                    FROM actions WHERE id_action IN (SELECT id_action FROM profil_has_actions WHERE id_profil='$idProfil')")
                                    ->fetchAll(PDO::FETCH_ASSOC);
        return array_merge($user, ["actions"=>$actions]);
    }

    public function changePassowrd($id,$oldPassword, $newPassowrd){
        try {
            $this->getOne($id);
            $user = $this->database->query("SELECT password FROM users WHERE id_user='$id'")->fetch(PDO::FETCH_ASSOC); 
            if(!password_verify($oldPassword,$user["password"])){
                throw new UserException("Mot de passe incorrect",403);
            }  
            $QUERY = "UPDATE users SET password=:password WHERE id_user=:id";
            $newPassowrd = password_hash($newPassowrd,PASSWORD_BCRYPT); 
            $this->database->prepare($QUERY)->execute(["password"=>"$newPassowrd","id"=>$id]);
            return true;
          } catch (UserException $exception) {
            throw $exception;
        }
    }

    public function resetPassword($id){
        try {
            $this->getOne($id);
            $newPassowrd = password_hash("Deafult2024",PASSWORD_BCRYPT); 
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

    private function profilExists($id): bool{
        return $this->database->query("SELECT * FROM profils WHERE id_profil='$id'")->rowCount() > 0;
    }
}
