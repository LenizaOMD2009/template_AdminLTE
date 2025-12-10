<?php

namespace app\database\builder;

use app\database\Connection;

class DeleteQuery
{
    private string $table;
    private array $where = [];
    private array $binds = [];
    public static function table(string $table): self
    {
        $self = new self;
        $self->table = $table;
        return $self;
    }
    #field Campo (coluna) que será filtrado.
    #operator Operador lógico (=, >, <, etc.).
    #string|int $value Valor a ser comparado.
    #logic Operador lógico adicional (AND, OR). Pode ser nulo.
    public function where(string $field, string $operator, string|int $value, ?string $logic = null): ?self
    {
        # Define um placeholder baseado no nome do campo
        $placeHolder = '';
        $placeHolder = $field;

        # Caso o campo venha com um alias (ex: "u.id"), extrai apenas o nome da coluna (ex: "id")
        if (str_contains($placeHolder, '.')) {
            $placeHolder = substr($field, strpos($field, '.') + 1);
        }

        # Monta a expressão da cláusula WHERE com o placeholder e operador lógico
        $this->where[] = "{$field} {$operator} :{$placeHolder} {$logic}";

        # Associa o valor ao placeholder no array de binds
        $this->binds[$placeHolder] = $value;

        # Retorna a própria instância para encadeamento
        return $this;
    }
    #Método privado que gera a query DELETE em forma de string.
    private function createQuery()
    {
        # Se a tabela não foi definida, lança uma exceção
        if (!$this->table) {
            throw new \Exception("A consulta precisa invocar o método delete.");
        }

        # Inicia a construção da query
        $query = '';
        $query = "delete from {$this->table} ";

        # Se houver condições WHERE, adiciona-as à query 
        $query .= (isset($this->where) && (count($this->where) > 0)) ? ' where ' . implode(' ', $this->where) : '';

        # Retorna a string da query montada
        return $query;
    }
    # $query A SQL gerada para execução.
    # Retorna true se a execução foi bem-sucedida.
    public function executeQuery($query)
    {
        # Obtém a conexão com o banco de dados via PDO
        $connection = Connection::connection();

        # Prepara a query para evitar SQL Injection
        $prepare = $connection->prepare($query);

        # Executa a query com os valores vinculados (binds)
        return $prepare->execute($this->binds ?? []);
    }
    #Método principal que monta e executa a query DELETE.
    #true em caso de sucesso, ou lança exceção se falhar.
    public function delete()
    {
        # Cria a query completa
        $query = $this->createQuery();
        try {
            # Tenta executar a query
            return $this->executeQuery($query);
        } catch (\PDOException $e) {
            # Captura exceções do PDO e lança uma nova exceção personalizada
            throw new \Exception("Restrição: {$e->getMessage()}");
        }
    }
}