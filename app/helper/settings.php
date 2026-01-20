<?php
session_start();
#Diretorio raiz da applicação WEB
define('ROOT', dirname(__FILE__, 3));
#Extensão padrão da camada de interação com usuário front-end.
define('EXT_VIEW', '.html');
#Diretorio do arquivos de template da view.
define('DIR_VIEW', ROOT . '/app/view');
#$_SERVER['HTTP_HOST'] : Indica o domínio (host) que foi chamado na URL pelo navegador. Domínio principal meusite.com ou localhost
#$_SERVER['REQUEST_SCHEME'] : Indica o protocolo usado na requisição atual. podendo ser http ou https
#Criamos uma constante chamada HOME que guarda automaticamente o endereço principal do site.
$scheme = isset($_SERVER['HTTP_CF_VISITOR']) ? $_SERVER['HTTP_CF_VISITOR'] : (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http');
define('HOME', $scheme . '://' . $_SERVER['HTTP_HOST']);
#Configurações E-mail
define('CONFIG_SMTP_EMAIL',[
    'host' => 'smtp.titan.email',
    'port' => 587,
    'user' => 'noreply@mkt.fanorte.edu.br',
    'passwd' => '@w906083W@',
    'from_name' => 'Mercantor',
    'from_email' => 'noreply@mkt.fanorte.edu.br',
    ]);