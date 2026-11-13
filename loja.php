<?php
require_once __DIR__ . '/config/auth.php';
exigirLogin();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/classes/autoload.php';

$usuarioId = (int)$_SESSION['usuario_id'];
$usuarios = new Usuario($pdo);
$pacotesModel = new Pacote($pdo);
$aberturasModel = new Abertura($pdo);

// Dados do jogador autenticado
$usuario = $usuarios->buscarPorId($usuarioId);

if (!$usuario) {
    header('Location: logout.php');
    exit;
}

$saldo = (int)$usuario['moedas'];

// Pacotes disponíveis na loja
$pacotes = $pacotesModel->listarAtivos();

// Pacotes comprados e ainda não abertos
$pacotesPendentes = $aberturasModel->contarPendentes($usuarioId);

// Token de uso único para evitar envio duplicado da compra
if (empty($_SESSION['compra_token'])) {
    $_SESSION['compra_token'] = bin2hex(random_bytes(32));
}
$compraToken = $_SESSION['compra_token'];

$flash = pegarMensagem();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loja | CardVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand navbar-dark nav-game py-3">
    <div class="container gap-2">
        <a class="navbar-brand brand mb-0" href="dashboard.php">
            <span class="brand-mark"><i class="bi bi-stars logo-star"></i></span>CardVerse
        </a>
        <div class="navbar-links d-none d-md-flex align-items-center gap-1 ms-2">
            <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
            <a class="nav-link" href="colecao.php"><i class="bi bi-grid-3x3-gap me-1"></i>Minha coleção</a>
            <a class="nav-link active" href="loja.php" aria-current="page"><i class="bi bi-shop me-1"></i>Loja</a>
            <a class="nav-link" href="abrir-pacote.php"><i class="bi bi-envelope-open me-1"></i>Abrir pacotes</a>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span class="tag d-none d-sm-inline-block">
                <i class="bi bi-coin me-1"></i><?= number_format($saldo, 0, ',', '.') ?>
            </span>
            <form action="logout.php" method="post" class="mb-0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()) ?>">
                <button class="btn btn-outline-game btn-sm px-3" type="submit">
                    <i class="bi bi-box-arrow-right me-1"></i>Sair
                </button>
            </form>
        </div>
    </div>
</nav>

<main class="container py-5">
    <!-- Boas-vindas -->
    <section class="hero mb-4">
        <div class="eyebrow mb-2"><i class="bi bi-shop me-2"></i>Mercado de pacotes</div>
        <h1 class="display-6 mb-2">Loja</h1>
        <p class="muted mb-3">Invista suas moedas em pacotes misteriosos e amplie sua coleção.</p>
        <span class="tag"><i class="bi bi-coin me-1"></i>Saldo: <?= number_format($saldo, 0, ',', '.') ?> moedas</span>
    </section>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?>" role="alert">
            <?= htmlspecialchars($flash['texto']) ?>
        </div>
    <?php endif; ?>

    <?php if ($pacotesPendentes > 0): ?>
        <a class="pending-banner" href="abrir-pacote.php">
            <i class="bi bi-envelope-open"></i>
            <span class="pending-text">
                <strong>
                    Você tem <?= $pacotesPendentes ?>
                    <?= $pacotesPendentes === 1 ? 'pacote fechado' : 'pacotes fechados' ?> esperando!
                </strong>
                <span>Abra quando quiser e descubra as cartas que vão para a sua coleção.</span>
            </span>
            <span class="pending-cta">Abrir agora <i class="bi bi-arrow-right"></i></span>
        </a>
    <?php endif; ?>

    <?php if (count($pacotes) === 0): ?>
        <!-- Nenhum pacote ativo -->
        <section class="empty-state" aria-live="polite">
            <i class="bi bi-box2 d-block mb-3"></i>
            <h2 class="h4 mb-2">Nenhum pacote disponível</h2>
            <p class="muted mb-4">A loja está sem pacotes ativos no momento. Volte mais tarde para conferir as novidades.</p>
            <a class="btn btn-magic" href="dashboard.php">
                <i class="bi bi-arrow-left me-1"></i>Voltar ao dashboard
            </a>
        </section>
    <?php else: ?>
        <div class="shop-note mb-4">
            <i class="bi bi-info-circle"></i>
            <span>A compra debita as moedas na hora e o pacote fica guardado em "Abrir pacotes". A revelação e a entrega das cartas acontecem quando você abrir.</span>
        </div>

        <!-- Pacotes -->
        <section aria-labelledby="pacotes-titulo">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 class="section-title h4 mb-0" id="pacotes-titulo">Pacotes disponíveis</h2>
                <span class="muted small">
                    <?= count($pacotes) ?> <?= count($pacotes) === 1 ? 'pacote' : 'pacotes' ?>
                </span>
            </div>

            <div class="row g-4">
                <?php foreach ($pacotes as $pacote): ?>
                    <?php
                    $preco = (int)$pacote['preco'];
                    $quantidadeCartas = (int)$pacote['quantidade_cartas'];
                    $descricao = trim((string)$pacote['descricao']);
                    if ($descricao === '') {
                        $descricao = 'Pacote com ' . $quantidadeCartas . ' cartas misteriosas para a sua coleção.';
                    }
                    $saldoSuficiente = $saldo >= $preco;
                    $faltam = max(0, $preco - $saldo);
                    $selo = trim((string)($pacote['selo'] ?? ''));
                    $premium = (int)($pacote['destaque'] ?? 0) === 1;
                    $garantia = (string)($pacote['raridade_garantida'] ?? '');
                    $garantiaRotulo = isset(Carta::RARIDADES[$garantia])
                        ? Carta::RARIDADES[$garantia]['rotulo']
                        : '';
                    $chanceLendaria = (int)($pacote['chance_lendaria'] ?? 0);
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <article class="pack-card<?= $premium ? ' pack-card--premium' : '' ?>">
                            <div class="pack-art" aria-hidden="true">
                                <?php if ($selo !== ''): ?>
                                    <span class="pack-badge"><?= htmlspecialchars($selo) ?></span>
                                <?php endif; ?>
                                <i class="bi <?= $premium ? 'bi-gem' : 'bi-box2-heart' ?>"></i>
                            </div>
                            <div class="pack-body">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <h3 class="pack-name"><?= htmlspecialchars($pacote['nome']) ?></h3>
                                    <span class="pack-price">
                                        <i class="bi bi-coin"></i><?= number_format($preco, 0, ',', '.') ?>
                                    </span>
                                </div>
                                <p class="pack-desc"><?= htmlspecialchars($descricao) ?></p>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="tag">
                                        <i class="bi bi-layers me-1"></i><?= $quantidadeCartas ?> <?= $quantidadeCartas === 1 ? 'carta' : 'cartas' ?>
                                    </span>
                                    <?php if ($garantiaRotulo !== ''): ?>
                                        <span class="tag tag-guarantee">
                                            <i class="bi bi-patch-check me-1"></i><?= htmlspecialchars($garantiaRotulo) ?> garantida
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($chanceLendaria > 0): ?>
                                    <p class="pack-odds mb-0">
                                        <i class="bi bi-stars me-1"></i>Chance de ao menos uma lendária: <?= $chanceLendaria ?>%
                                    </p>
                                <?php endif; ?>

                                <?php if ($saldoSuficiente): ?>
                                    <form action="actions/comprar-pacote.php" method="post" class="mt-auto">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()) ?>">
                                        <input type="hidden" name="compra_token" value="<?= htmlspecialchars($compraToken) ?>">
                                        <input type="hidden" name="pacote_id" value="<?= (int)$pacote['id'] ?>">
                                        <button class="btn btn-magic w-100" type="submit">
                                            <i class="bi bi-bag-check me-1"></i>Comprar por <?= number_format($preco, 0, ',', '.') ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="mt-auto">
                                        <button class="btn btn-outline-game w-100" type="button" disabled>
                                            <i class="bi bi-lock me-1"></i>Saldo insuficiente
                                        </button>
                                        <p class="small muted text-center mt-2 mb-0">
                                            Faltam <?= number_format($faltam, 0, ',', '.') ?> moedas
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
