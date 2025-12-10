<?php

#Importa a classe AppFactory do Slim Framework, responsável por criar a instância principal da aplicação.
use Slim\Factory\AppFactory;

#Carrega automaticamente todas as dependências instaladas via Composer (incluindo Slim e outras bibliotecas).
#Sem esse autoload, o framework e as classes utilizadas no projeto não poderiam ser encontradas.
require __DIR__ . '/../vendor/autoload.php';

#Cria a aplicação Slim, retornando um objeto que representa o servidor HTTP e gerenciador de rotas.
$app = AppFactory::create();

#Adiciona o middleware responsável por interpretar as rotas e direcionar cada requisição HTTP para a rota correta.
#Sem este middleware, o Slim não saberia como "ler" ou processar as rotas definidas.
$app->addRoutingMiddleware();

#Adiciona o middleware de tratamento de erros, permitindo exibir ou registrar exceções que ocorrerem durante a execução.
#Os três parâmetros (true, true, true) habilitam:
#1º - Mostrar detalhes de erro na tela (útil no modo de desenvolvimento)
#2º - Registrar erros
#3º - Registrar exceções fatales
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

require __DIR__ . '/../app/helper/settings.php';
require __DIR__ . '/../app/route/route.php';

$app->run();