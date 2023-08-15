<?php

namespace App;

class QueryBuilder {

    private $fields = ["*"];

    private $from;

    private $params;

    private $orderBy = [];

    private $limit;

    private $offset;

    private $where;

    public function select(...$fields): self
    {
        if($this->fields === ["*"]) {
            $this->fields = [];
        }
        $this->fields = array_merge($this->fields, $this->flatArray($fields));
        return $this;
    }

    public function from(string $dbName, ?string $dbAlias = null): self
    {
        $this->from = "FROM $dbName";
        if($dbAlias) {
            $this->from .= " $dbAlias";
        }
        return $this;
    }

    public function where(string $condition)
    {
        $this->where = "WHERE $condition";
        return $this;
    }

    public function setParam(string $field, $value): self
    {
        $this->params[$field] = $value;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function orderBy(string $key, string $direction)
    {
        if(!in_array($direction, ['ASC', 'DESC'])) {
            $this->orderBy[] = $key;
        } else {
            $this->orderBy[] = "$key $direction";
        }
        return $this;
    }

    public function page(int $page): self
    {
        if($this->limit !== null) {
            $this->offset(($page - 1) * $this->limit);
        }
        return $this;
    }

    
    public function fetch(\PDO $pdo, string $field): ?string
    {
        $query = $pdo->prepare($this->toSql());
        $query->execute($this->params);
        $data = $query->fetch(\PDO::FETCH_ASSOC);
        return $data ? $data[$field] : null;
    }
    
    public function count(\PDO $pdo): int
    {
        $query = $pdo->prepare($this->toSql());
        $query->execute($this->params);
        return count($query->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function toSql(): string
    {
        $fields = implode(', ', $this->fields);
        $sql = "SELECT $fields $this->from";
        if($this->where) {
            $sql .= " $this->where";
        }
        if($this->orderBy) {
            $sql .= " ORDER BY " . implode(", ", $this->orderBy);
        }
        if($this->limit) {
            $sql .= " LIMIT $this->limit";
        }
        if($this->offset !== null) {
            $sql .= " OFFSET $this->offset";
        }
        return $sql;
    }

    private function flatArray(Array $arr)
    {
        $flatFields = [];
        array_walk_recursive($arr, function($a) use (&$flatFields) {
            $flatFields[] = $a;
        });

        return $flatFields;
    }

}