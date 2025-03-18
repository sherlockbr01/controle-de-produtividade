<?php

// Substitua '127.0.0.1' pelo IP local da máquina
$host = '0.0.0.0'; // Isso faz com que o servidor escute em todas as interfaces de rede
$port = '9865'; // Porta que você deseja usar

// Define o comando para iniciar o servidor embutido do PHP
$command = sprintf('php -S %s:%s -t public', $host, $port);

// Executa o comando
echo "Iniciando o servidor em http://$host:$port\n";
echo "Pressione Ctrl+C para parar o servidor\n";
passthru($command);