
<?php
// ==========================================
// CONFIGURAÇÕES E AUTENTICAÇÃO
// ==========================================

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

// Impede o acesso de usuários que não são administradores.
exigirAdmin($pdo, '../login.php');

// ==========================================
// BUSCA DAS CARTAS
// ==========================================

try {
    $sql = "SELECT id, nome, descricao, imagem, raridade, ataque, defesa
            FROM cartas
            ORDER BY id DESC";

    $stmt = $pdo->query($sql);
    $cartas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $erro = "Não foi possível carregar as cartas.";
    $cartas = [];
}

// ==========================================
// MENSAGENS DO SISTEMA
// ==========================================

$flash = pegarMensagem();

function escapar($valor): string {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>

<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Gerenciar Cartas | CardVerse</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- CSS principal do CardVerse -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>

<div class="container-fluid admin-layout">
    <div class="row">

        <!-- ==================================
             MENU LATERAL
        =================================== -->

        <aside class="col-md-3 col-lg-2 admin-sidebar">

            <div class="brand mb-5">
                <span class="brand-mark">
                    <i class="bi bi-stars"></i>
                </span>
                CardVerse
            </div>

            <div class="eyebrow mb-3">Administração</div>

            <nav class="nav flex-column">

                <a href="index.php" class="nav-link">
                    <i class="bi bi-grid-1x2"></i> Dashboard
                </a>

                <a href="cartas.php" class="nav-link active">
                    <i class="bi bi-card-list"></i> Cartas
                </a>

                <a href="pacotes.php" class="nav-link">
                    <i class="bi bi-box-seam"></i> Pacotes
                </a>

                <hr class="border-secondary my-4">

                <a href="../dashboard.php" class="nav-link">
                    <i class="bi bi-arrow-left-circle"></i> Voltar ao jogo
                </a>

                <form action="../logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="nav-link border-0 bg-transparent text-start w-100"><i class="bi bi-box-arrow-right"></i> Sair</button>
                </form>

            </nav>
        </aside>

        <!-- ==================================
             CONTEÚDO PRINCIPAL
        =================================== -->

        <main class="col-md-9 col-lg-10 admin-content">

            <header class="admin-header">

                <div>
                    <div class="eyebrow mb-2">Gerenciamento</div>
                    <h1 class="section-title">Cartas</h1>
                    <p class="muted mb-0">
                        Consulte e gerencie as cartas do CardVerse.
                    </p>
                </div>

                <a href="carta-form.php" class="btn btn-magic">
                    <i class="bi bi-plus-lg me-2"></i>
                    Nova carta
                </a>

            </header>

            <!-- MENSAGENS -->

            <?php if ($flash): ?>
                <div class="alert alert-<?= escapar($flash['tipo']) ?>">
                    <?= escapar($flash['texto']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($erro)): ?>
                <div class="alert alert-danger">
                    <?= escapar($erro) ?>
                </div>
            <?php endif; ?>

            <!-- CONTADOR -->

            <div class="admin-info mb-4">
                <div>
                    <span class="eyebrow">Cartas cadastradas</span>
                    <h2><?= count($cartas) ?></h2>
                </div>

                <i class="bi bi-collection"></i>
            </div>

            <!-- TABELA DE CARTAS -->

            <section class="feature-card admin-table-card">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                    <h2 class="h5 mb-0">Todas as cartas</h2>
                    <span class="tag">Biblioteca CardVerse</span>
                </div>

                <?php if (empty($cartas)): ?>

                    <div class="text-center py-5">
                        <i class="bi bi-card-list admin-empty-icon"></i>

                        <h3 class="h5 mt-3">Nenhuma carta encontrada</h3>

                        <p class="muted">
                            Cadastre sua primeira carta para começar.
                        </p>
                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table admin-table align-middle">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Carta</th>
                                    <th>Raridade</th>
                                    <th>Ataque</th>
                                    <th>Defesa</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($cartas as $carta): ?>

                                    <tr>
                                        <td><?= (int) $carta['id'] ?></td>

                                        <td>
                                            <div class="d-flex align-items-center gap-3">

                                                <?php if (!empty($carta['imagem'])): ?>
                                                    <img
                                                        src="../<?= escapar($carta['imagem']) ?>"
                                                        alt=""
                                                        class="admin-card-image"
                                                    >
                                                <?php else: ?>
                                                    <div class="admin-card-placeholder">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                <?php endif; ?>

                                                <div>
                                                    <strong><?= escapar($carta['nome']) ?></strong>
                                                    <small class="muted d-block">
                                                        <?= escapar($carta['descricao']) ?>
                                                    </small>
                                                </div>

                                            </div>
                                        </td>

                                        <td>
                                            <span class="tag">
                                                <?= ucfirst(escapar($carta['raridade'])) ?>
                                            </span>
                                        </td>

                                        <td><?= (int) $carta['ataque'] ?></td>

                                        <td><?= (int) $carta['defesa'] ?></td>

                                        <!-- ==================================
                                             AÇÕES: EDITAR E EXCLUIR
                                        =================================== -->

                                        <td class="text-end">

                                            <!-- Botão de edição -->
                                            <a
                                                href="carta-form.php?id=<?= (int) $carta['id'] ?>"
                                                class="btn btn-sm btn-outline-game"
                                                title="Editar carta"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <!-- Formulário de exclusão -->
                                            <form
                                                action="actions/excluir-carta.php"
                                                method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Tem certeza que deseja excluir esta carta?');"
                                            >

                                                <!-- Proteção CSRF -->
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= escapar(tokenCsrf()) ?>"
                                                >

                                                <!-- ID da carta -->
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $carta['id'] ?>"
                                                >

                                                <!-- Botão de exclusão -->
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Excluir carta"
                                                >
                                                    <i class="bi bi-trash"></i>
                                                </button>

                                            </form>

                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </section>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
