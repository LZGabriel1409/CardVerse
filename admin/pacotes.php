
<?php
/**
 * Lista os pacotes cadastrados no CardVerse.
 */

// ==========================================
// CONFIGURAÇÕES E AUTENTICAÇÃO
// ==========================================

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

// Somente administradores podem acessar.
exigirAdmin($pdo, '../login.php');

// Função para proteger textos exibidos no HTML.
function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// ==========================================
// BUSCA DOS PACOTES
// ==========================================

try {
    $sql = "
        SELECT
            p.id,
            p.nome,
            p.descricao,
            p.selo,
            p.destaque,
            p.preco,
            p.quantidade_cartas,
            p.ativo,
            COUNT(pc.carta_id) AS total_cartas
        FROM pacotes p
        LEFT JOIN pacote_cartas pc
            ON pc.pacote_id = p.id
        GROUP BY
            p.id,
            p.nome,
            p.descricao,
            p.selo,
            p.destaque,
            p.preco,
            p.quantidade_cartas,
            p.ativo
        ORDER BY p.id ASC
    ";

    $stmt = $pdo->query($sql);
    $pacotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Não foi possível carregar os pacotes.');
}

// ==========================================
// MENSAGENS DO SISTEMA
// ==========================================

$flash = pegarMensagem();

$totalPacotes = count($pacotes);

$totalAtivos = count(array_filter(
    $pacotes,
    fn($pacote) => (int) $pacote['ativo'] === 1
));

?>

<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Gerenciar Pacotes | CardVerse</title>

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
                    <span class="eyebrow">ADMINISTRAÇÃO</span>
                    <h1>Gerenciar pacotes</h1>

                    <p class="muted mb-0">
                        Cadastre, edite e configure os pacotes do CardVerse.
                    </p>
                </div>

                <a href="pacote-form.php"
                   class="btn btn-magic">
                    <i class="bi bi-plus-lg me-2"></i>
                    Novo pacote
                </a>

            </header>

            <!-- ==================================
                 MENSAGEM DE RETORNO
            =================================== -->

            <?php if ($flash): ?>

                <div class="alert alert-<?= escapar($flash['tipo']) ?> alert-dismissible fade show"
                     role="alert">

                    <?= escapar($flash['texto']) ?>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert">
                    </button>

                </div>

            <?php endif; ?>

            <!-- ==================================
                 INDICADORES
            =================================== -->

            <div class="row g-3 mb-4">

                <div class="col-md-6 col-xl-3">

                    <div class="admin-info w-100">

                        <div>
                            <span class="muted">Total de pacotes</span>
                            <h2><?= $totalPacotes ?></h2>
                        </div>

                        <i class="bi bi-box-seam"></i>

                    </div>

                </div>

                <div class="col-md-6 col-xl-3">

                    <div class="admin-info w-100">

                        <div>
                            <span class="muted">Pacotes ativos</span>
                            <h2><?= $totalAtivos ?></h2>
                        </div>

                        <i class="bi bi-check-circle"></i>

                    </div>

                </div>

            </div>

            <!-- ==================================
                 TABELA DE PACOTES
            =================================== -->

            <section class="feature-card admin-table-card">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                    <div>
                        <h2 class="h4 mb-1">Pacotes cadastrados</h2>

                        <p class="muted mb-0">
                            Gerencie os pacotes disponíveis no jogo.
                        </p>
                    </div>

                </div>

                <?php if (empty($pacotes)): ?>

                    <div class="text-center py-5">

                        <i class="bi bi-box-seam admin-empty-icon"></i>

                        <h3 class="h5 mt-3">Nenhum pacote encontrado</h3>

                        <p class="muted">
                            Cadastre seu primeiro pacote para começar.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table admin-table align-middle">

                            <thead>
                                <tr>
                                    <th>Pacote</th>
                                    <th>Preço</th>
                                    <th>Cartas</th>
                                    <th>Status</th>
                                    <th>Destaque</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($pacotes as $pacote): ?>

                                    <tr>

                                        <!-- Nome e selo -->

                                        <td>
                                            <div>
                                                <strong>
                                                    <?= escapar($pacote['nome']) ?>
                                                </strong>

                                                <?php if (!empty($pacote['selo'])): ?>
                                                    <br>

                                                    <small class="muted">
                                                        <?= escapar($pacote['selo']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- Preço -->

                                        <td>
                                            <strong class="gold">
                                                <?= number_format(
                                                    (int) $pacote['preco'],
                                                    0,
                                                    ',',
                                                    '.'
                                                ) ?>
                                                moedas
                                            </strong>
                                        </td>

                                        <!-- Quantidade de cartas -->

                                        <td>
                                            <?= (int) $pacote['total_cartas'] ?>
                                            disponíveis

                                            <br>

                                            <small class="muted">
                                                <?= (int) $pacote['quantidade_cartas'] ?>
                                                por abertura
                                            </small>
                                        </td>

                                        <!-- Status -->

                                        <td>
                                            <?php if ((int) $pacote['ativo'] === 1): ?>

                                                <span class="badge bg-success">
                                                    Ativo
                                                </span>

                                            <?php else: ?>

                                                <span class="badge bg-secondary">
                                                    Inativo
                                                </span>

                                            <?php endif; ?>
                                        </td>

                                        <!-- Destaque -->

                                        <td>
                                            <?php if ((int) $pacote['destaque'] === 1): ?>

                                                <i class="bi bi-star-fill gold"
                                                   title="Pacote em destaque"></i>

                                            <?php else: ?>

                                                <span class="muted">—</span>

                                            <?php endif; ?>
                                        </td>

                                        <!-- Ações -->

                                        <td class="text-end">

                                            <!-- Configurar cartas -->

                                            <a href="pacote-cartas.php?id=<?= (int) $pacote['id'] ?>"
                                               class="btn btn-sm btn-outline-game"
                                               title="Configurar cartas">

                                                <i class="bi bi-collection"></i>

                                            </a>

                                            <!-- Configurar chances -->

                                            <a href="pacote-chances.php?id=<?= (int) $pacote['id'] ?>"
                                               class="btn btn-sm btn-outline-game"
                                               title="Configurar chances">

                                                <i class="bi bi-percent"></i>

                                            </a>

                                            <!-- Editar pacote -->

                                            <a href="pacote-form.php?id=<?= (int) $pacote['id'] ?>"
                                               class="btn btn-sm btn-outline-game"
                                               title="Editar pacote">

                                                <i class="bi bi-pencil"></i>

                                            </a>

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
