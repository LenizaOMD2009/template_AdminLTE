<?php

namespace app\database\builder;

use app\database\Connection;

class SelectQuery
{
    private string $fields;
    private string $table;
    private array $where = [];
    private array $binds = [];
    private string $order;
    private int $limit;
    private int $offset;
    private string $limits;
    public static function select(string $fields = '*'): self
    {
        $self = new self;
        $self->fields = $fields;
        return $self;
    }
    public function from(string $table): self
    {
        $this->table = $table;
        return $this;
    }
    public function where(string $field, string $operator, string | int $value, ?string $logic = null): self
    {
        $placeholder = '';
        $placeholder = $field;

        if (str_contains($placeholder, '.')) {
            $placeholder = substr($field, strpos($field, '.') + 1);
        }
        if ($logic) {
    $this->where[] = "{$field} {$operator} :{$placeholder} {$logic}";
} else {
    $this->where[] = "{$field} {$operator} :{$placeholder}";
}

        $this->binds[$placeholder] = $value;
        return $this;
    }
    public function order(string $field, string $typeOrder = 'asc'): self
    {
        $this->order = " order by {$field}  {$typeOrder}";
        return $this;
    }
    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->limits = " limit {$this->limit} offset {$this->offset} ";
        return $this;
    }
    public function whereRaw(string $raw, ?string $logic = null): self
{
    if ($logic) {
        $this->where[] = "{$raw} {$logic}";
    } else {
        $this->where[] = "{$raw}";
    }
    return $this;
}

    private function createQuery(): string
    {
        if (!$this->fields) {
            throw new \Exception("Para realizar uma consulta SQL é necessário informa os campos da consulta");
        }
        if (!$this->table) {
            throw new \Exception("Para realizar a consulta SQL é necessário informa a nome da tabela.");
        }
        $query = '';
        $query = 'select ';
        $query .= $this->fields . ' from ';
        $query .= $this->table;
        $query .= (isset($this->where) and (count($this->where) > 0)) ? ' where ' . implode(' ', $this->where) : '';
        $query .= $this->order ?? '';
        $query .= $this->limits ?? '';
        return $query;
    }
    public function fetch()
    {
        $query = '';
        $query = $this->createQuery();
        try {
            $connection = Connection::connection();
            $prepare = $connection->prepare($query);
            $prepare->execute($this->binds ?? []);
            return $prepare->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new \Exception("Restrição: " . $e->getMessage());
        }
    }
    public function fetchAll()
    {
        $query = '';
        $query = $this->createQuery();
        try {
            $connection = Connection::connection();
            $prepare = $connection->prepare($query);
            $prepare->execute($this->binds ?? []);
            return $prepare->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new \Exception("Restrição: " . $e->getMessage());
        }
    }
}