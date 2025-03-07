<?php

// Definir o diretório base do projeto
define('BASE_PATH', __DIR__);

// Verificar se o arquivo index.php existe na pasta public
if (file_exists(BASE_PATH . '/public/index.php')) {
    // Redirecionar todas as requisições para o index.php na pasta public
    require BASE_PATH . '/public/index.php';
} else {
    // Se o arquivo não existir, exibir uma mensagem de erro
    http_response_code(500);
    echo "Erro interno do servidor: arquivo index.php não encontrado na pasta public.";
    exit;
}