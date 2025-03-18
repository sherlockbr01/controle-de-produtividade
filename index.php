<?php

// Definir constantes do projeto
define('BASE_PATH', __DIR__);
define('PUBLIC_PATH', BASE_PATH . '/public');
define('SRC_PATH', BASE_PATH . '/src');
define('VIEWS_PATH', SRC_PATH . '/views');

// Determinar BASE_URL dinamicamente sem incluir '/public'
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;
define('BASE_URL', $baseUrl); // Sem adicionar '/public'

// Verificar se o arquivo index.php existe na pasta public
if (file_exists(PUBLIC_PATH . '/index.php')) {
    // Redirecionar todas as requisições para o index.php na pasta public
    require PUBLIC_PATH . '/index.php';
} else {
    // Se o arquivo não existir, exibir uma mensagem de erro
    http_response_code(500);
    echo "Erro interno do servidor: arquivo index.php não encontrado na pasta public.";
    exit;
}