<?php

namespace app\database;

use PDO;

class Connection
{
    #Variável de conexão com banco de dados.
    private static $pdo = null;
    #Método de conexão com banco de dados.
    public static function connection(): PDO
    {
        #Tentativa de estabelecer uma conexão com o banco de dados com tratamento de exceções.
        try {
            #Caso já exista a conexão com banco de dados retornamos a conexão.
            if (static::$pdo) {
                return static::$pdo;
            }
            # Definindo as opções para a conexão PDO.
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, # Lança exceções em caso de erros.
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, # Define o modo de fetch padrão como array associativo.
                PDO::ATTR_EMULATE_PREPARES => false, # Desativa a emulação de prepared stratements.
                PDO::ATTR_PERSISTENT => true, # Conexão persistente para melhorar performance.
                PDO::ATTR_STRINGIFY_FETCHES => false, # Desativa a conversão de valores numéricos para strings.
            ];

            # Criação da nova conexão PDO com os parâmetros do banco de dados.
            static::$pdo = new PDO(
                'pgsql:host=localhost;port=5432;dbname=template_adiminlte', # DSN (Data Source Name) para PostgreSQL.
                'senac', # Nome de usuário do banco de dados.
                'senac', # Senha do banco de dados.
                $options # Opções para a conexão PDO.
            );
            static::$pdo->exec("SET NAMES 'utf8'");
            #Caso seja bem-sucedida a conexão retornamos a variável $pdo;
            return static::$pdo;
        } catch (\PDOException $e) {
            throw new \PDOException("Erro: " . $e->getMessage(), 1);
        }
    }
}
