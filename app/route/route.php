<?php

use app\controller\User;
use app\controller\Home;
use app\controller\cliente;
use app\controller\Empresas;
use app\controller\Fornecedor;
use app\controller\Login;
use app\middleware\Middleware;
use Slim\Routing\RouteCollectorProxy;

$app->get('/', Home::class . ':home')->add(Middleware::authentication());
$app->get('/home', Home::class . ':home')->add(Middleware::authentication());
$app->get('/login', Login::class . ':login');

$app->group('/login', function (RouteCollectorProxy $group) {
    $group->post('/precadastro', Login::class . ':precadastro');
    $group->post('/autenticar', Login::class . ':autenticar');
});

$app->group('/usuario', function (RouteCollectorProxy $group) {
    $group->get('/lista', User::class . ':lista')->add(Middleware::authentication());
    $group->get('/cadastro', User::class . ':cadastro')->add(Middleware::authentication());
    $group->post('/listuser', User::class . ':listuser');
    $group->post('/insert', User::class . ':insert');
    $group->get('/alterar/{id}', User::class . ':alterar')->add(Middleware::authentication());
    $group->post('/update', User::class . ':update');
    $group->post('/delete', User::class . ':delete');
});
$app->group('/cliente', function (RouteCollectorProxy $group) {
    $group->get('/lista', cliente::class . ':lista')->add(Middleware::authentication());
    $group->get('/cadastro', cliente::class . ':cadastro')->add(Middleware::authentication());
    $group->post('/listcliente', cliente::class . ':listcliente');
    $group->post('/insert', cliente::class . ':insert');
    $group->get('/alterar/{id}', User::class . ':alterar')->add(Middleware::authentication());
    $group->post('/update', User::class . ':update');
    $group->post('/delete', User::class . ':delete');
});
$app->group('/empresas', function (RouteCollectorProxy $group) {
    $group->get('/lista', Empresas::class . ':lista')->add(Middleware::authentication());
    $group->get('/cadastro', Empresas::class . ':cadastro')->add(Middleware::authentication());
    $group->post('/listempresas', Empresas::class . ':listempresas');
    $group->post('/insert', Empresas::class . ':insert');
});
$app->group('/fornecedor', function (RouteCollectorProxy $group) {
    $group->get('/lista', Fornecedor::class . ':lista')->add(Middleware::authentication());
    $group->get('/cadastro', Fornecedor::class . ':cadastro')->add(Middleware::authentication());
    $group->get('/delete', Fornecedor::class . ':delete')->add(Middleware::authentication());
    $group->post('/listfornecedor', Fornecedor::class . ':listfornecedor');
    $group->post('/insert', Fornecedor::class . ':insert');
});


