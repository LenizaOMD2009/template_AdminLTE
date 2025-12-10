<?php

namespace app\middleware;

class Middleware
{
    public static function authentication()
    {
        #Retorna um closure (função anônima)
        $middleware = function ($request, $handler) {
            $response = $handler->handle($request);
            #Capturamos o metodo de requisição (GET, POST, PUT, DELETE, ETC).
            $method = $request->getMethod();
            #Capturamos a pagina que o usuário esta tentando acessar.
            $pagina = $request->getRequestTarget();
            if ($method === 'GET') {
                #Verificando se o usuário está autenticado, caso não esteja ja direcionamos para o login.
                $usuarioLogado = empty($_SESSION['usuario']) || empty($_SESSION['usuario']['logado']);
                if ($usuarioLogado and $pagina !== '/login') {
                    #Destrui a sessão. 
                    session_destroy();
                    #E depois direcionar o usuário para a pagina de autenticação.
                    return $response->withHeader('Location', '/login')->withStatus(302);
                }
                if ($pagina === '/login') {
                    if (!$usuarioLogado) {
                        return $response->withHeader('Location', '/')->withStatus(302);
                    }
                }
                if (empty($_SESSION['usuario']['ativo']) or !$_SESSION['usuario']['ativo']) {
                    session_destroy();
                    return $response->withHeader('Location', '/login')->withStatus(302);
                }
            }
            return $handler->handle($request);
        };
        return $middleware;
    }
}