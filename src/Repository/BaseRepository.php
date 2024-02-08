<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

abstract class BaseRepository
{
    protected PDO $database;
    
    public function __construct()
    {
        global $GLOBALS;
        $this->database = $GLOBALS["pdo"];
    }

    protected function getDb(): PDO
    {
        return $this->database;
    }

    protected function getResultsWithPagination(
        string $query,
        int $page,
        int $perPage
    ): array {
        $total  = $this->database->query($query)->rowCount();
        
        return [
            'pagination' => [
                'totalRows' => $total,
                'totalPages' => ceil($total / $perPage),
                'currentPage' => $page,
                'perPage' => $perPage,
            ],
            'content' => $this->getResultByPage($query, $page, $perPage),
        ];
    }

    protected function getResultByPage(
        string $query,
        int $page,
        int $perPage
    ): array {
        $offset = ($page - 1) * $perPage;
        $query .= " LIMIT $perPage OFFSET $offset";
        return  $this->database->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    abstract protected function getAll($critere="true", $page=1, $perPage= 10);
    abstract protected function getOne($id,$critere="true");
    abstract protected function insert($params=[]);
    abstract protected function update($id, $params, $critere="true");
    abstract protected function delete($id, $critere="true");
    abstract protected function exists($critere="true"):bool;
}
