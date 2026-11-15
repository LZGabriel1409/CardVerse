<?php
require_once __DIR__ . '/config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    ?>
    <!doctype html>
    <html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sair | CardVerse</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="assets/css/style.css" rel="stylesheet">
    </head>
    <body>
    <main class="container py-5">
        <div class="alert alert-warning" role="alert">
            <h1 class="h4">Sessão não encerrada</h1>
            <p class="mb-0">Para sair da sua conta, use o botão <strong>Sair</strong> no menu do jogo.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-magic" href="login.php">Ir para o login</a>
            <a class="btn btn-outline-game" href="dashboard.php">Voltar ao dashboard</a>
        </div>
    </main>
    </body>
    </html>
    <?php
    exit;
}

validarCsrf();
encerrarSessao();
header('Location: login.php');
exit;

