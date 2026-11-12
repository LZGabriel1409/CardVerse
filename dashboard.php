<?php
require_once __DIR__ . '/config/auth.php';
exigirLogin();

require_once __DIR__ . '/config/conexao.php';

// Dados do jogador autenticado
$stmt = $pdo->prepare('SELECT nome, moedas FROM usuarios WHERE id = ?');
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: logout.php');
    exit;
}

// Estatísticas da coleção
$stmt = $pdo->prepare(
    'SELECT COUNT(*) AS tipos, COALESCE(SUM(quantidade), 0) AS total
     FROM colecao WHERE usuario_id = ?'
);
$stmt->execute([$_SESSION['usuario_id']]);
$colecao = $stmt->fetch();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | CardVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark nav-game py-3">
    <div class="container">
        <span class="navbar-brand brand mb-0">
            <span class="brand-mark"><i class="bi bi-stars logo-star"></i></span>CardVerse
        </span>
        <form action="logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()) ?>">
            <button class="btn btn-outline-game btn-sm px-3" type="submit">
                <i class="bi bi-box-arrow-right me-1"></i>Sair
            </button>
        </form>
    </div>
</nav>

<main class="container py-5">
    <!-- Boas-vindas -->
    <section class="hero mb-4">
        <div class="eyebrow mb-2"><i class="bi bi-stars me-2"></i>Central do colecionador</div>
        <h1 class="display-6 mb-2">Olá, <?= htmlspecialchars($usuario['nome']) ?>!</h1>
        <p class="muted mb-3">Sua jornada está apenas começando. Grandes descobertas esperam por você.</p>
        <span class="tag"><i class="bi bi-lightning-charge-fill me-1"></i>Jogador do CardVerse</span>
    </section>

    <!-- Estatísticas reais do banco de dados -->
    <section aria-label="Suas estatísticas" class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="stat">
                <span class="stat-icon gold"><i class="bi bi-coin"></i></span>
                <div class="muted mb-1">Moedas disponíveis</div>
                <div class="stat-number gold"><?= number_format((int)$usuario['moedas'], 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat">
                <span class="stat-icon"><i class="bi bi-collection"></i></span>
                <div class="muted mb-1">Cartas diferentes</div>
                <div class="stat-number"><?= (int)$colecao['tipos'] ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat">
                <span class="stat-icon"><i class="bi bi-layers"></i></span>
                <div class="muted mb-1">Total de cartas</div>
                <div class="stat-number"><?= (int)$colecao['total'] ?></div>
            </div>
        </div>
    </section>

    <!-- Prévia das próximas funcionalidades; sem links quebrados -->
    <section aria-labelledby="explorar-titulo">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 class="section-title h4 mb-0" id="explorar-titulo">Explore o CardVerse</h2>
            
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="feature-card is-locked">
                    <i class="bi bi-box2-heart"></i>
                    <h3 class="h5 mt-3">Pacotes misteriosos</h3>
                    <p class="muted mb-0">Abra pacotes e encontre cartas raras. Em breve!</p>
                </div>
            </div>
            <div class="col-md-4">
                <a class="feature-card" href="colecao.php">
                    <i class="bi bi-grid-3x3-gap"></i>
                    <h3 class="h5 mt-3">Minha coleção</h3>
                    <p class="muted mb-0">Organize suas cartas, filtre por raridade e veja os detalhes.</p>
                </a>
            </div>
            <div class="col-md-4">
                <div class="feature-card is-locked">
                    <i class="bi bi-arrow-left-right"></i>
                    <h3 class="h5 mt-3">Trocas</h3>
                    <p class="muted mb-0">Negocie cartas com outros jogadores. Em breve!</p>
                </div>
            </div>
        </div>
    </section>
</main>
</body>
</html>
