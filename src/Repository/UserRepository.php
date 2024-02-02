<?php
declare(strict_types=1);

namespace App\Repository;


use App\Exception\User as ExceptionUser;
use App\Service\BaseService;

class UserRepository extends BaseRepository {

    function login($params=[]){
        
        $email = $params["email"];
        $password = $params["password"];
        $query = "SELECT * FROM users WHERE email=:email";
        $statement = $this->database->prepare($query);
        $statement->bindParam('email', $email);
        $statement->execute();
        $user = $statement->fetchObject();
       
        if (!$user) {
            throw new ExceptionUser(
                'Login failed: Email or password incorrect.',
                400
            );
            
        }

        if (sha1($password)!=$user->password) {
            throw new ExceptionUser(
                'Login failed: Email or password incorrect.',
                400
            );
        }

        return $user;
    }

    function getUsers($params=[]){
        if(!empty($params)){
            $critere = "";
            if(isset($params["id"]) && is_numeric($params["id"])){
                $critere.=" id=:id AND";
            }
            if(isset($params["name"]) && is_string($params["name"])){
                $critere.=" LOWER(name)  LIKE '%:name%' AND";
            }

            if(isset($params["email"]) && is_string($params["email"])){
                $critere.=" email=:email AND";
            }

            
            $page =  isset($params["page"])? (int)$params["page"]:1;
            $perPage =  isset($params["perPage"])? (int)$params["perPage"]:BaseService::$DEFAULT_PER_PAGE_PAGINATION;
            $result = (array)$this->database->query("SELECT * FROM users")->fetchAll();
           
            return $this->getResultsWithPagination("SELECT * FROM users WHERE 1 AND $critere 1",$page,$perPage,$params,count($result));
        }else{
            return (array)$this->database->query("SELECT * FROM users")->fetchAll();
        }
        
    }
}