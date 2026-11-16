
<?php
/**
 * Gerenciamento das cartas disponíveis em um pacote.
 */

// ==========================================
// CONFIGURAÇÕES E AUTENTICAÇÃO
// ==========================================

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

exigirAdmin($pdo, '../login.php');

function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// ==========================================
// IDENTIFICAÇÃO DO PACOTE
// ==========================================

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    mensagem('danger', 'Pacote inválido.');
    header('Location: pacotes.php');
    exit;
}

// Busca os dados do pacote.
$stmt = $pdo->prepare(
    'SELECT id, nome FROM pacotes WHERE id = ? LIMIT 1'
);

$stmt->execute([$id]);

$pacote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pacote) {
    mensagem('danger', 'Pacote não encontrado.');
    header('Location: pacotes.php');
    exit;
}

// ==========================================
// BUSCA DAS CARTAS CADASTRADAS
// ==========================================

$stmt = $pdo->query(
    'SELECT id, nome, raridade, ataque, defesa
     FROM cartas
     ORDER BY nome ASC'
);

$cartas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// CARTAS JÁ VINCULADAS AO PACOTE
// ==========================================

$stmt = $pdo->prepare(
    'SELECT carta_id
     FROM pacote_cartas
     WHERE pacote_id = ?'
);

$stmt->execute([$id]);

$cartasSelecionadas = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Converte os IDs para inteiros.
$cartasSelecionadas = array_map('intval', $cartasSelecionadas);

$flash = pegarMensagem();

?>

<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Cartas do pacote | CardVerse</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
          rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container-fluid admin-layout">
    <div class="row">

        <!-- ==================================
             MENU LATERAL
        =================================== -->

        <aside class="col-md-3 col-lg-2 admin-sidebar">

            <a href="index.php" class="brand text-decoration-none d-block mb-5">
                <span class="brand-mark">
                    <i class="bi bi-stars"></i>
                </span>
                CardVerse
            </a>

            <nav class="nav flex-column">

                <a href="index.php" class="nav-link">
                    <i class="bi bi-grid"></i>
                    Visão geral
                </a>

                <a href="cartas.php" class="nav-link">
                    <i class="bi bi-collection"></i>
                    Cartas
                </a>

                <a href="pacotes.php" class="nav-link active">
                    <i class="bi bi-box-seam"></i>
                    Pacotes
                </a>

                <hr class="border-secondary">

                <a href="../dashboard.php" class="nav-link">
                    <i class="bi bi-controller"></i>
                    Área do jogador
                </a>

                <a href="../actions/logout.php" class="nav-link">
                    <i class="bi bi-box-arrow-left"></i>
                    Sair
                </a>

            </nav>

        </aside>

        <!-- ==================================
             CONTEÚDO PRINCIPAL
        =================================== -->

        <main class="col-md-9 col-lg-10 admin-content">

            <header class="admin-header">

                <div>
                    <span class="eyebrow">CONFIGURAÇÃO DE PACOTES</span>

                    <h1>Cartas disponíveis</h1>

                    <p class="muted mb-0">
                        Escolha quais cartas podem aparecer em
                        <strong><?= escapar($pacote['nome']) ?></strong>.
                    </p>
                </div>

                <a href="pacotes.php" class="btn btn-outline-game">
                    <i class="bi bi-arrow-left me-2"></i>
                    Voltar
                </a>

            </header>

            <?php if ($flash): ?>

                <div class="alert alert-<?= escapar($flash['tipo']) ?> alert-dismissible fade show">
                    <?= escapar($flash['texto']) ?>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert">
                    </button>
                </div>

            <?php endif; ?>

            <!-- ==================================
                 SELEÇÃO DE CARTAS
            =================================== -->

            <section class="feature-card admin-form-card">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                    <div>
                        <h2 class="h5 mb-1">Catálogo de cartas</h2>

                        <p class="muted mb-0">
                            Selecione todas as cartas que poderão sair neste pacote.
                        </p>
                    </div>

                    <span class="tag">
                        <?= count($cartasSelecionadas) ?> selecionadas
                    </span>

                </div>

                <?php if (empty($cartas)): ?>

                    <div class="text-center py-5">
                        <i class="bi bi-collection admin-empty-icon"></i>

                        <h3 class="h5 mt-3">Nenhuma carta cadastrada</h3>

                        <p class="muted">
                            Cadastre cartas antes de configurar o pacote.
                        </p>

                        <a href="carta-form.php" class="btn btn-magic">
                            Cadastrar carta
                        </a>
                    </div>

                <?php else: ?>

                    <form action="actions/salvar-cartas-pacote.php"
                          method="POST">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= escapar(tokenCsrf()) ?>">

                        <input type="hidden"
                               name="pacote_id"
                               value="<?= (int) $pacote['id'] ?>">

                        <div class="row g-3">

                            <?php foreach ($cartas as $carta): ?>

                                <div class="col-12 col-md-6 col-xl-4">

                                    <label class="package-card-option w-100">

                                        <input
                                            type="checkbox"
                                            name="cartas[]"
                                            value="<?= (int) $carta['id'] ?>"
                                            <?= in_array((int) $carta['id'], $cartasSelecionadas, true) ? 'checked' : '' ?>
                                        >

                                        <div class="package-card-option-content">

                                            <div class="d-flex justify-content-between align-items-start gap-2">

                                                <strong>
                                                    <?= escapar($carta['nome']) ?>
                                                </strong>

                                                <i class="bi bi-check-circle-fill package-card-check"></i>

                                            </div>

                                            <span class="tag mt-2 d-inline-block">
                                                <?= escapar(ucfirst($carta['raridade'])) ?>
                                            </span>

                                            <div class="d-flex gap-3 mt-3">

                                                <small class="muted">
                                                    <i class="bi bi-sword me-1"></i>
                                                    Ataque: <?= (int) $carta['ataque'] ?>
                                                </small>

                                                <small class="muted">
                                                    <i class="bi bi-shield me-1"></i>
                                                    Defesa: <?= (int) $carta['defesa'] ?>
                                                </small>

                                            </div>

                                        </div>

                                    </label>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <div class="admin-form-actions pt-4 mt-4">

                            <a href="pacotes.php" class="btn btn-outline-game">
                                Cancelar
                            </a>

                            <button type="submit" class="btn btn-magic">
                                <i class="bi bi-check-lg me-2"></i>
                                Salvar cartas selecionadas
                            </button>

                        </div>

                    </form>

                <?php endif; ?>

            </section>

        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
