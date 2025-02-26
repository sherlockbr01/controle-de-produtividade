<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro</title>
</head>
<body>
<h1>Erro</h1>
<p><?= htmlspecialchars($_SESSION['error_message'] ?? 'Ocorreu um erro.') ?></p>
<a href="/sistema_produtividade/public/">Voltar para a página inicial</a>
</body>
</html>