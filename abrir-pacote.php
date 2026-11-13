<?php
require_once __DIR__ . '/config/auth.php';
exigirLogin();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/classes/autoload.php';

$usuarioId = (int)$_SESSION['usuario_id'];
$usuarios = new Usuario($pdo);
$aberturasModel = new Abertura($pdo);

$usuario = $usuarios->buscarPorId($usuarioId);

if (!$usuario) {
    header('Location: logout.php');
    exit;
}

$saldo = (int)$usuario['moedas'];

$idDaUrl = static function ($valor): ?int {
    $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $id === false ? null : (int)$id;
};

$aberturaId = $idDaUrl($_GET['abertura'] ?? null);
$compraId = $idDaUrl($_GET['compra'] ?? null);

$abertura = null;
$compra = null;
$pendentes = [];
$recentes = [];
$chances = [];
$totalPeso = 0;
$aberturaToken = '';

if ($aberturaId !== null) {
    $abertura = $aberturasModel->buscar($aberturaId, $usuarioId);

    if (!$abertura) {
        mensagem('danger', 'Abertura não encontrada na sua conta.');
        header('Location: abrir-pacote.php');
        exit;
    }
} elseif ($compraId !== null) {
    $compra = $aberturasModel->buscarCompra($usuarioId, $compraId);

    if (!$compra) {
        mensagem('danger', 'Pacote não encontrado na sua conta.');
        header('Location: abrir-pacote.php');
        exit;
    }

    // Compra já aberta: leva direto para a revelação registrada.
    if (!empty($compra['abertura_id'])) {
        header('Location: abrir-pacote.php?abertura=' . (int)$compra['abertura_id']);
        exit;
    }

    $chances = $aberturasModel->chancesDoPacote((int)$compra['pacote_id']);
    $totalPeso = array_sum($chances);

    if (!isset($_SESSION['abertura_tokens']) || !is_array($_SESSION['abertura_tokens'])) {
        $_SESSION['abertura_tokens'] = [];
    }

    if (empty($_SESSION['abertura_tokens'][$compraId])) {
        $_SESSION['abertura_tokens'][$compraId] = bin2hex(random_bytes(32));
    }

    $aberturaToken = (string)$_SESSION['abertura_tokens'][$compraId];
} else {
    $pendentes = $aberturasModel->listarPendentes($usuarioId);
    $recentes = $aberturasModel->listarRecentes($usuarioId);
}

$flash = pegarMensagem();

function imagemCartaAbertura(string $imagem): ?string
{
    $arquivo = basename($imagem);

    if ($arquivo === '') {
        return null;
    }

    $relativo = 'assets/img/cartas/' . $arquivo;

    return is_file(__DIR__ . '/' . $relativo) ? $relativo : null;
}

function formatarDataAbertura(?string $data): string
{
    if ($data === null || trim($data) === '') {
        return '';
    }

    $timestamp = strtotime($data);

    return $timestamp === false ? '' : date('d/m/Y \à\s H:i', $timestamp);
}

function raridadesOrdenadas(array $resumo): array
{
    $ordenadas = [];

    foreach (array_reverse(Carta::RARIDADES, true) as $chave => $dados) {
        if (!empty($resumo[$chave])) {
            $ordenadas[$chave] = (int)$resumo[$chave];
        }
    }

    return $ordenadas;
}

$modo = $abertura !== null ? 'abertura' : ($compra !== null ? 'compra' : 'lista');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Abrir pacotes | CardVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <noscript>
        <style>
            .reveal-card__inner { transform: rotateY(180deg) !important; }
        </style>
    </noscript>
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
            <a class="nav-link" href="loja.php"><i class="bi bi-shop me-1"></i>Loja</a>
            <a class="nav-link active" href="abrir-pacote.php" aria-current="page"><i class="bi bi-envelope-open me-1"></i>Abrir pacotes</a>
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
    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?>" role="alert">
            <?= htmlspecialchars($flash['texto']) ?>
        </div>
    <?php endif; ?>

    <?php if ($modo === 'abertura'): ?>
        <?php
        $cartas = $abertura['cartas'];
        $resumo = raridadesOrdenadas($abertura['raridades']);
        $dataAbertura = formatarDataAbertura($abertura['data_abertura']);
        $quantidade = count($cartas);
        ?>
        <section class="hero mb-4">
            <div class="eyebrow mb-2"><i class="bi bi-stars me-2"></i>Abertura registrada</div>
            <h1 class="display-6 mb-2">Pacote <?= htmlspecialchars($abertura['nome']) ?></h1>
            <p class="muted mb-3">
                Revelação de <?= $dataAbertura !== '' ? htmlspecialchars($dataAbertura) : 'hoje' ?>.
                As cartas abaixo já estão na sua coleção.
            </p>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="tag"><i class="bi bi-layers me-1"></i><?= $quantidade ?> <?= $quantidade === 1 ? 'carta' : 'cartas' ?></span>
                <?php foreach ($resumo as $raridade => $total): ?>
                    <span class="rarity-badge <?= htmlspecialchars(Carta::RARIDADES[$raridade]['classe']) ?>">
                        <i class="bi <?= htmlspecialchars(Carta::RARIDADES[$raridade]['icone']) ?>"></i>
                        <?= $total ?> <?= htmlspecialchars(Carta::RARIDADES[$raridade]['rotulo']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="reveal-stage" data-reveal-stage aria-label="Cartas reveladas">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 class="section-title h4 mb-0">Suas cartas</h2>
                <span class="muted small" id="revealContador" aria-live="polite">
                    0 de <?= $quantidade ?> <?= $quantidade === 1 ? 'carta revelada' : 'cartas reveladas' ?>
                </span>
            </div>

            <div class="reveal-grid">
                <?php foreach ($cartas as $indice => $carta): ?>
                    <?php
                    $raridade = isset(Carta::RARIDADES[$carta['raridade']])
                        ? $carta['raridade']
                        : 'comum';
                    $dadosRaridade = Carta::RARIDADES[$raridade];
                    $imagem = imagemCartaAbertura((string)($carta['imagem'] ?? ''));
                    ?>
                    <div class="reveal-card" data-rarity="<?= htmlspecialchars($raridade) ?>"
                         style="--reveal-delay: <?= (int)$indice ?>;">
                        <div class="reveal-card__inner">
                            <div class="reveal-card__face reveal-card__back" aria-hidden="true">
                                <span class="reveal-back-ring"></span>
                                <i class="bi bi-stars"></i>
                                <span class="reveal-back-label">CardVerse</span>
                            </div>
                            <div class="reveal-card__face reveal-card__front <?= htmlspecialchars($dadosRaridade['classe']) ?>">
                                <div class="reveal-card__art">
                                    <?php if ($imagem !== null): ?>
                                        <img src="<?= htmlspecialchars($imagem) ?>"
                                             alt="Carta <?= htmlspecialchars((string)$carta['nome']) ?>">
                                    <?php else: ?>
                                        <div class="card-art-placeholder">
                                            <i class="bi <?= htmlspecialchars($dadosRaridade['icone']) ?>"></i>
                                            <span class="placeholder-label">Arte em breve</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="reveal-card__body">
                                    <span class="rarity-badge <?= htmlspecialchars($dadosRaridade['classe']) ?>">
                                        <i class="bi <?= htmlspecialchars($dadosRaridade['icone']) ?>"></i>
                                        <?= htmlspecialchars($dadosRaridade['rotulo']) ?>
                                    </span>
                                    <h3 class="reveal-card__name"><?= htmlspecialchars((string)$carta['nome']) ?></h3>
                                    <div class="reveal-card__stats">
                                        <span><i class="bi bi-lightning-charge-fill"></i><?= (int)($carta['ataque'] ?? 0) ?></span>
                                        <span><i class="bi bi-shield-fill"></i><?= (int)($carta['defesa'] ?? 0) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <button class="btn btn-magic" type="button" id="revelarTodas">
                <i class="bi bi-lightning-charge me-1"></i>Revelar todas
            </button>
            <a class="btn btn-outline-game" href="abrir-pacote.php">
                <i class="bi bi-envelope-open me-1"></i>Meus pacotes
            </a>
            <a class="btn btn-outline-game" href="colecao.php">
                <i class="bi bi-grid-3x3-gap me-1"></i>Ver coleção
            </a>
            <a class="btn btn-outline-game" href="loja.php">
                <i class="bi bi-shop me-1"></i>Comprar mais pacotes
            </a>
        </div>

    <?php elseif ($modo === 'compra'): ?>
        <?php
        $precoPago = (int)$compra['valor_pago'];
        $quantidadeCartas = (int)$compra['quantidade_cartas'];
        $selo = trim((string)($compra['selo'] ?? ''));
        $premium = (int)($compra['destaque'] ?? 0) === 1;
        $garantia = (string)($compra['raridade_garantida'] ?? '');
        $garantiaRotulo = isset(Carta::RARIDADES[$garantia]) ? Carta::RARIDADES[$garantia]['rotulo'] : '';
        $chanceLendaria = (int)($compra['chance_lendaria'] ?? 0);
        $dataCompra = formatarDataAbertura($compra['data_compra']);
        ?>
        <section class="hero mb-4">
            <div class="eyebrow mb-2"><i class="bi bi-envelope-open me-2"></i>Pacote fechado</div>
            <h1 class="display-6 mb-2"><?= htmlspecialchars($compra['nome']) ?></h1>
            <p class="muted mb-3"><?= htmlspecialchars(trim((string)$compra['descricao'])) ?></p>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="tag"><i class="bi bi-coin me-1"></i><?= number_format($precoPago, 0, ',', '.') ?> pagos</span>
                <span class="tag"><i class="bi bi-layers me-1"></i><?= $quantidadeCartas ?> cartas</span>
                <?php if ($dataCompra !== ''): ?>
                    <span class="tag"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($dataCompra) ?></span>
                <?php endif; ?>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-5">
                <article class="pack-card<?= $premium ? ' pack-card--premium' : '' ?>">
                    <div class="pack-art" aria-hidden="true">
                        <?php if ($selo !== ''): ?>
                            <span class="pack-badge"><?= htmlspecialchars($selo) ?></span>
                        <?php endif; ?>
                        <i class="bi <?= $premium ? 'bi-gem' : 'bi-box2-heart' ?>"></i>
                    </div>
                    <div class="pack-body">
                        <h2 class="pack-name h5 mb-0">Pacote pronto para abrir</h2>
                        <p class="pack-desc mb-0">
                            A abertura é definitiva: ao confirmar, as <?= $quantidadeCartas ?> cartas
                            são sorteadas e adicionadas à sua coleção na mesma hora.
                        </p>
                        <form action="actions/abrir-pacote.php" method="post" class="mt-auto">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()) ?>">
                            <input type="hidden" name="abertura_token" value="<?= htmlspecialchars($aberturaToken) ?>">
                            <input type="hidden" name="compra_id" value="<?= (int)$compraId ?>">
                            <button class="btn btn-magic w-100" type="submit">
                                <i class="bi bi-magic me-1"></i>Abrir pacote
                            </button>
                        </form>
                    </div>
                </article>
            </div>

            <div class="col-lg-7">
                <section class="odds-panel" aria-labelledby="chances-titulo">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h2 class="section-title h5 mb-0" id="chances-titulo">Chances desta abertura</h2>
                        <?php if ($garantiaRotulo !== ''): ?>
                            <span class="tag tag-guarantee">
                                <i class="bi bi-patch-check me-1"></i><?= htmlspecialchars($garantiaRotulo) ?> garantida
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($totalPeso > 0): ?>
                        <ul class="odds-list">
                            <?php foreach (Carta::RARIDADES as $raridade => $dados): ?>
                                <?php
                                $peso = (int)($chances[$raridade] ?? 0);
                                $percentual = $totalPeso > 0 ? (int)round($peso / $totalPeso * 100) : 0;
                                ?>
                                <?php if ($peso > 0): ?>
                                    <li class="odds-item">
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <span class="rarity-badge <?= htmlspecialchars($dados['classe']) ?>">
                                                <i class="bi <?= htmlspecialchars($dados['icone']) ?>"></i>
                                                <?= htmlspecialchars($dados['rotulo']) ?>
                                            </span>
                                            <strong class="odds-value"><?= $percentual ?>%</strong>
                                        </div>
                                        <div class="odds-bar" role="presentation">
                                            <span class="odds-bar__fill <?= htmlspecialchars($dados['classe']) ?>"
                                                  style="width: <?= $percentual ?>%"></span>
                                        </div>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                        <p class="muted small mb-0">Cada uma das <?= $quantidadeCartas ?> cartas sorteia uma raridade conforme os pesos acima.</p>
                    <?php else: ?>
                        <p class="muted mb-0">Este pacote não possui chances configuradas; o sorteio usará o pool de cartas disponível.</p>
                    <?php endif; ?>

                    <?php if ($chanceLendaria > 0): ?>
                        <div class="odds-extra mt-3">
                            <i class="bi bi-stars"></i>
                            <span>Chance de ao menos uma carta lendária no pacote: <strong><?= $chanceLendaria ?>%</strong></span>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <a class="btn btn-outline-game" href="abrir-pacote.php">
                <i class="bi bi-arrow-left me-1"></i>Meus pacotes
            </a>
            <a class="btn btn-outline-game" href="loja.php">
                <i class="bi bi-shop me-1"></i>Voltar à loja
            </a>
        </div>

    <?php else: ?>
        <?php $totalPendentes = count($pendentes); ?>
        <section class="hero mb-4">
            <div class="eyebrow mb-2"><i class="bi bi-envelope-open me-2"></i>Central de aberturas</div>
            <h1 class="display-6 mb-2">Abrir pacotes</h1>
            <p class="muted mb-3">Seus pacotes comprados esperam aqui. Abra quando quiser e descubra as cartas.</p>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="tag"><i class="bi bi-envelope-paper me-1"></i><?= $totalPendentes ?> <?= $totalPendentes === 1 ? 'pacote fechado' : 'pacotes fechados' ?></span>
                <span class="tag"><i class="bi bi-clock-history me-1"></i><?= count($recentes) ?> <?= count($recentes) === 1 ? 'abertura recente' : 'aberturas recentes' ?></span>
            </div>
        </section>

        <?php if ($totalPendentes > 0): ?>
            <section aria-labelledby="pendentes-titulo" class="mb-5">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h2 class="section-title h4 mb-0" id="pendentes-titulo">Pacotes fechados</h2>
                    <a class="btn btn-outline-game btn-sm" href="loja.php">
                        <i class="bi bi-bag-plus me-1"></i>Comprar pacotes
                    </a>
                </div>
                <div class="row g-4">
                    <?php foreach ($pendentes as $pacotePendente): ?>
                        <?php
                        $selo = trim((string)($pacotePendente['selo'] ?? ''));
                        $premium = (int)($pacotePendente['destaque'] ?? 0) === 1;
                        $quantidadeCartas = (int)$pacotePendente['quantidade_cartas'];
                        $dataCompra = formatarDataAbertura($pacotePendente['data_compra']);
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
                                        <h3 class="pack-name"><?= htmlspecialchars($pacotePendente['nome']) ?></h3>
                                        <span class="pack-price">
                                            <i class="bi bi-coin"></i><?= number_format((int)$pacotePendente['valor_pago'], 0, ',', '.') ?>
                                        </span>
                                    </div>
                                    <p class="pack-desc"><?= htmlspecialchars(trim((string)$pacotePendente['descricao'])) ?></p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="tag">
                                            <i class="bi bi-layers me-1"></i><?= $quantidadeCartas ?> <?= $quantidadeCartas === 1 ? 'carta' : 'cartas' ?>
                                        </span>
                                        <?php if ($dataCompra !== ''): ?>
                                            <span class="tag"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($dataCompra) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <a class="btn btn-magic w-100 mt-auto"
                                       href="abrir-pacote.php?compra=<?= (int)$pacotePendente['compra_id'] ?>">
                                        <i class="bi bi-envelope-open me-1"></i>Abrir pacote
                                    </a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else: ?>
            <section class="empty-state mb-5" aria-live="polite">
                <i class="bi bi-envelope-check d-block mb-3"></i>
                <h2 class="h4 mb-2">Nenhum pacote fechado</h2>
                <p class="muted mb-4">Compre um pacote na loja e ele aparecerá aqui, pronto para abrir quando você quiser.</p>
                <a class="btn btn-magic" href="loja.php">
                    <i class="bi bi-shop me-1"></i>Ir para a loja
                </a>
            </section>
        <?php endif; ?>

        <section aria-labelledby="recentes-titulo">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 class="section-title h4 mb-0" id="recentes-titulo">Aberturas recentes</h2>
                <span class="muted small">Últimas <?= count($recentes) ?> aberturas</span>
            </div>

            <?php if ($recentes === []): ?>
                <p class="muted">Você ainda não abriu nenhum pacote. A primeira revelação está esperando por você.</p>
            <?php else: ?>
                <div class="history-list">
                    <?php foreach ($recentes as $aberturaRecente): ?>
                        <?php $dataRecente = formatarDataAbertura($aberturaRecente['data_abertura']); ?>
                        <a class="history-item" href="abrir-pacote.php?abertura=<?= (int)$aberturaRecente['id'] ?>">
                            <div class="history-main">
                                <span class="history-pack"><?= htmlspecialchars($aberturaRecente['nome']) ?></span>
                                <span class="muted small"><?= htmlspecialchars($dataRecente) ?></span>
                            </div>
                            <div class="history-cards" aria-hidden="true">
                                <?php foreach ($aberturaRecente['cartas'] as $cartaRecente): ?>
                                    <?php
                                    $raridadeRecente = isset(Carta::RARIDADES[$cartaRecente['raridade']])
                                        ? $cartaRecente['raridade']
                                        : 'comum';
                                    ?>
                                    <span class="mini-card <?= htmlspecialchars(Carta::RARIDADES[$raridadeRecente]['classe']) ?>"
                                          title="<?= htmlspecialchars((string)$cartaRecente['nome']) ?>">
                                        <i class="bi <?= htmlspecialchars(Carta::RARIDADES[$raridadeRecente]['icone']) ?>"></i>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <span class="history-link">
                                Ver revelação<i class="bi bi-chevron-right ms-1"></i>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<?php if ($modo === 'abertura'): ?>
    <script>
        (function () {
            var stage = document.querySelector('[data-reveal-stage]');
            var botao = document.getElementById('revelarTodas');
            var contador = document.getElementById('revealContador');

            if (!stage) {
                return;
            }

            var cartas = Array.prototype.slice.call(stage.querySelectorAll('.reveal-card'));
            var reveladas = 0;
            var timers = [];
            var reduzido = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function atualizarContador() {
                if (!contador) {
                    return;
                }
                contador.textContent = reveladas + ' de ' + cartas.length
                    + (cartas.length === 1 ? ' carta revelada' : ' cartas reveladas');
            }

            function revelar(carta) {
                if (carta.classList.contains('is-revealed')) {
                    return;
                }
                carta.classList.add('is-revealed');
                reveladas++;
                atualizarContador();
            }

            function revelarTodas() {
                timers.forEach(window.clearTimeout);
                timers = [];
                cartas.forEach(revelar);
                if (botao) {
                    botao.disabled = true;
                    botao.innerHTML = '<i class="bi bi-check2-all me-1"></i>Todas reveladas';
                }
            }

            if (reduzido) {
                revelarTodas();
            } else {
                cartas.forEach(function (carta, indice) {
                    timers.push(window.setTimeout(function () {
                        revelar(carta);
                    }, 500 + indice * 520));
                });
            }

            if (botao) {
                botao.addEventListener('click', revelarTodas);
            }
        })();
    </script>
<?php endif; ?>
</body>
</html>
