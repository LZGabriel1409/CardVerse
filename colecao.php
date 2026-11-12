<?php
require_once __DIR__ . '/config/auth.php';
exigirLogin();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/classes/autoload.php';

$usuarioId = (int)$_SESSION['usuario_id'];
$usuarios = new Usuario($pdo);
$cartasModel = new Carta($pdo);

// Dados do jogador autenticado
$usuario = $usuarios->buscarPorId($usuarioId);

if (!$usuario) {
    header('Location: logout.php');
    exit;
}

// Raridades disponíveis no jogo
$raridades = Carta::RARIDADES;

// Filtros vindos da URL
$busca = trim((string)($_GET['busca'] ?? ''));
if (mb_strlen($busca) > 100) {
    $busca = mb_substr($busca, 0, 100);
}

$raridade = (string)($_GET['raridade'] ?? '');
if (!isset($raridades[$raridade])) {
    $raridade = '';
}

// Totais gerais da coleção (para diferenciar "vazia" de "sem resultados")
$resumo = $cartasModel->resumoColecao($usuarioId);

// Cartas com os filtros aplicados
$cartas = $cartasModel->listarColecao($usuarioId, $busca, $raridade);

$totalFiltrado = 0;
$raridadesEncontradas = [];
foreach ($cartas as $carta) {
    $totalFiltrado += (int)$carta['quantidade'];
    $raridadesEncontradas[$carta['raridade']] = true;
}

/**
 * Retorna o caminho relativo da imagem da carta ou null quando o arquivo ainda não existe.
 */
function imagemCarta(?string $arquivo): ?string
{
    $arquivo = basename(str_replace('\\', '/', trim((string)$arquivo)));

    if ($arquivo === '') {
        return null;
    }

    $relativo = 'assets/img/cartas/' . $arquivo;

    return is_file(__DIR__ . '/' . $relativo) ? $relativo : null;
}

$colecaoVazia = (int)$resumo['total'] === 0;
$semResultados = !$colecaoVazia && count($cartas) === 0;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Minha coleção | CardVerse</title>
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
            <a class="nav-link active" href="colecao.php" aria-current="page"><i class="bi bi-grid-3x3-gap me-1"></i>Minha coleção</a>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span class="tag d-none d-sm-inline-block">
                <i class="bi bi-coin me-1"></i><?= number_format((int)$usuario['moedas'], 0, ',', '.') ?>
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
        <div class="eyebrow mb-2"><i class="bi bi-grid-3x3-gap me-2"></i>Central do colecionador</div>
        <h1 class="display-6 mb-2">Minha coleção</h1>
        <p class="muted mb-3">Organize suas cartas, filtre por raridade e encontre suas lendas.</p>
        <span class="tag"><i class="bi bi-person me-1"></i><?= htmlspecialchars($usuario['nome']) ?></span>
    </section>

    <?php if (!$colecaoVazia): ?>
        <!-- Resumo da coleção filtrada -->
        <section aria-label="Resumo da coleção" class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat">
                    <span class="stat-icon"><i class="bi bi-collection"></i></span>
                    <div class="muted mb-1">Cartas diferentes</div>
                    <div class="stat-number"><?= count($cartas) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat">
                    <span class="stat-icon"><i class="bi bi-layers"></i></span>
                    <div class="muted mb-1">Total de cartas</div>
                    <div class="stat-number"><?= $totalFiltrado ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat">
                    <span class="stat-icon gold"><i class="bi bi-gem"></i></span>
                    <div class="muted mb-1">Raridades encontradas</div>
                    <div class="stat-number gold"><?= count($raridadesEncontradas) ?></div>
                </div>
            </div>
        </section>

        <!-- Filtros -->
        <form action="colecao.php" method="get" class="collection-toolbar mb-4" role="search">
            <div class="row g-3 align-items-end">
                <div class="col-lg-6">
                    <label class="form-label" for="busca">Buscar por nome</label>
                    <input class="form-control" type="search" id="busca" name="busca"
                           value="<?= htmlspecialchars($busca) ?>" maxlength="100"
                           placeholder="Ex.: Dragão das Sombras">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label" for="raridade">Raridade</label>
                    <select class="form-select" id="raridade" name="raridade">
                        <option value="">Todas as raridades</option>
                        <?php foreach ($raridades as $chave => $dados): ?>
                            <option value="<?= htmlspecialchars($chave) ?>" <?= $raridade === $chave ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dados['rotulo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3 d-flex gap-2">
                    <button class="btn btn-magic flex-grow-1" type="submit">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <?php if ($busca !== '' || $raridade !== ''): ?>
                        <a class="btn btn-outline-game" href="colecao.php" aria-label="Limpar filtros">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    <?php endif; ?>

    <?php if ($colecaoVazia): ?>
        <!-- Coleção vazia -->
        <section class="empty-state" aria-live="polite">
            <i class="bi bi-box2-heart d-block mb-3"></i>
            <h2 class="h4 mb-2">Sua coleção está vazia</h2>
            <p class="muted mb-4">Você ainda não possui cartas. Abra pacotes misteriosos para começar sua coleção e descobrir novas raridades.</p>
            <a class="btn btn-magic" href="dashboard.php">
                <i class="bi bi-arrow-left me-1"></i>Voltar ao dashboard
            </a>
        </section>
    <?php elseif ($semResultados): ?>
        <!-- Nenhum resultado para os filtros -->
        <section class="empty-state" aria-live="polite">
            <i class="bi bi-search d-block mb-3"></i>
            <h2 class="h4 mb-2">Nenhuma carta encontrada</h2>
            <p class="muted mb-4">Não há cartas na sua coleção que correspondam à busca ou à raridade selecionada.</p>
            <a class="btn btn-magic" href="colecao.php">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Limpar filtros
            </a>
        </section>
    <?php else: ?>
        <!-- Grade de cartas -->
        <section aria-labelledby="cartas-titulo">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 class="section-title h4 mb-0" id="cartas-titulo">Suas cartas</h2>
                <span class="muted small">
                    <?= count($cartas) ?> <?= count($cartas) === 1 ? 'carta diferente' : 'cartas diferentes' ?>
                </span>
            </div>

            <div class="row g-4">
                <?php foreach ($cartas as $carta): ?>
                    <?php
                    $quantidade = (int)$carta['quantidade'];
                    $dadosRaridade = $raridades[$carta['raridade']] ?? $raridades['comum'];
                    $imagem = imagemCarta($carta['imagem']);
                    $descricao = trim((string)$carta['descricao']);
                    ?>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <article class="card-tile">
                            <div class="card-art">
                                <?php if ($imagem !== null): ?>
                                    <img src="<?= htmlspecialchars($imagem) ?>"
                                         alt="Carta <?= htmlspecialchars($carta['nome']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="card-art-placeholder" aria-hidden="true">
                                        <i class="bi bi-stars"></i>
                                        <span class="placeholder-label">Arte em breve</span>
                                    </div>
                                <?php endif; ?>
                                <span class="quantity-pill" title="Quantidade na coleção">
                                    <i class="bi bi-layers"></i>&times;<?= $quantidade ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="rarity-row">
                                    <span class="rarity-badge <?= htmlspecialchars($dadosRaridade['classe']) ?>">
                                        <i class="bi <?= htmlspecialchars($dadosRaridade['icone']) ?>"></i>
                                        <?= htmlspecialchars($dadosRaridade['rotulo']) ?>
                                    </span>
                                </div>
                                <h3 class="card-name"><?= htmlspecialchars($carta['nome']) ?></h3>
                                <p class="card-desc"><?= htmlspecialchars($descricao !== '' ? $descricao : 'Sem descrição disponível.') ?></p>
                                <button class="btn btn-outline-game btn-sm w-100 mt-2" type="button"
                                        data-bs-toggle="modal" data-bs-target="#detalhes-<?= (int)$carta['id'] ?>">
                                    <i class="bi bi-eye me-1"></i>Ver detalhes
                                </button>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Modais de detalhes -->
        <?php foreach ($cartas as $carta): ?>
            <?php
            $quantidade = (int)$carta['quantidade'];
            $dadosRaridade = $raridades[$carta['raridade']] ?? $raridades['comum'];
            $imagem = imagemCarta($carta['imagem']);
            $descricao = trim((string)$carta['descricao']);
            ?>
            <div class="modal fade game-modal" id="detalhes-<?= (int)$carta['id'] ?>" tabindex="-1"
                 aria-labelledby="detalhes-titulo-<?= (int)$carta['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <h2 class="modal-title h5" id="detalhes-titulo-<?= (int)$carta['id'] ?>">
                                <?= htmlspecialchars($carta['nome']) ?>
                            </h2>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body pt-0">
                            <div class="row g-4">
                                <div class="col-md-5">
                                    <div class="modal-art">
                                        <?php if ($imagem !== null): ?>
                                            <img src="<?= htmlspecialchars($imagem) ?>"
                                                 alt="Carta <?= htmlspecialchars($carta['nome']) ?>">
                                        <?php else: ?>
                                            <div class="card-art-placeholder" aria-hidden="true">
                                                <i class="bi bi-stars"></i>
                                                <span class="placeholder-label">Arte em breve</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                        <span class="rarity-badge <?= htmlspecialchars($dadosRaridade['classe']) ?>">
                                            <i class="bi <?= htmlspecialchars($dadosRaridade['icone']) ?>"></i>
                                            <?= htmlspecialchars($dadosRaridade['rotulo']) ?>
                                        </span>
                                        <span class="tag"><i class="bi bi-layers me-1"></i><?= $quantidade ?> na coleção</span>
                                    </div>
                                    <p class="<?= $descricao !== '' ? '' : 'muted' ?>">
                                        <?= $descricao !== '' ? nl2br(htmlspecialchars($descricao)) : 'Sem descrição disponível.' ?>
                                    </p>
                                    <div class="detail-grid">
                                        <div class="detail-box">
                                            <span class="muted d-block small">Ataque</span>
                                            <strong class="gold"><i class="bi bi-lightning-charge-fill me-1"></i><?= (int)$carta['ataque'] ?></strong>
                                        </div>
                                        <div class="detail-box">
                                            <span class="muted d-block small">Defesa</span>
                                            <strong><i class="bi bi-shield-fill me-1"></i><?= (int)$carta['defesa'] ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button class="btn btn-outline-game" type="button" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
