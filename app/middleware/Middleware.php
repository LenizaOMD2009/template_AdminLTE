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
                if (isset($_SESSION['usuario']) && boolval($_SESSION['usuario']['logado'])) {
                    session_regenerate_id(true);
                }
                if ($pagina == '/login' && isset($_SESSION['usuario']) && boolval($_SESSION['usuario']['logado'])) {
                    return $response->withHeader('Location', HOME)->withStatus(302);
                }
                if ((empty($_SESSION['usuario']) || !boolval($_SESSION['usuario']['logado'])) && ($pagina !== '/login')) {
                    session_destroy();
                    return $response->withHeader('Location', HOME . '/login')->withStatus(302);
                }
            }
            return $response;
        };
        return $middleware;
    }
}
