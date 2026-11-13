
<?php
require_once __DIR__ . '/config/auth.php';
exigirLogin();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/classes/autoload.php';


$usuarios = new Usuario($pdo);
$cartasModel = new Carta($pdo);
$conquistasModel = new Conquista($pdo);
$rankingModel = new Ranking($pdo);

$usuario = $usuarios->buscarPorId((int)$_SESSION['usuario_id']);
$usuarioId = (int)$_SESSION['usuario_id'];

if (!$usuario) {
    header('Location: logout.php');
    exit;
}

// Foto de perfil: usa a padrão caso o usuário ainda não tenha uma.
$avatar = !empty($usuario['avatar'])
    ? $usuario['avatar']
    : 'assets/img/avatares/default.png';


//
//  Nivel e experiencia
//

    $xp = (int)$usuario['experiencia'];
    $pontosAtuais = (int)$usuario['pontos'];

    $stmt = $pdo->query("
    SELECT nivel, experiencia_necessaria FROM niveis
    ORDER BY experiencia_necessaria ASC");

    $niveis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nivelAtual = $niveis[0];
    $proximoNivel = null; //null é pq ele ainda n tem valor

    foreach ($niveis as $nivelInfo) {

        if ($xp >= (int) $nivelInfo['experiencia_necessaria']) {
            $nivelAtual = $nivelInfo;
        } else {
            $proximoNivel = $nivelInfo;
            $expNecessaria = (int) $nivelInfo['experiencia_necessaria'];
            break;
        }

    }

    $nivel = (int) $nivelAtual['nivel'];
    $proxNivel = (int)$proximoNivel["experiencia_necessaria"] ?? $nivelAtual['experiencia_necessaria'];
    $progressoXP =(int) (($xp - $nivelAtual["experiencia_necessaria"]) / ($proxNivel - $nivelAtual["experiencia_necessaria"]) * 100);


    //
    // Estastisticas: cartas
    //

    $raridade = (string)($_GET['raridade'] ?? '');
    if (!isset($raridades[$raridade])) {
        $raridade = '';
    }


    $cartas = $cartasModel->listarColecao($usuarioId, '', $raridade);
    $qtdCartas = count($cartas);
    $totalCartas = 0;
    $cartasExistentes = $cartasModel->qtdCartasExistentes();

    $rarasOuSuperiores = 0;
    
    foreach ($cartas as $carta) {

    $totalCartas += (int)$carta['quantidade'];
    $rc = $carta['raridade']; // rc = raridadeCarta, so para abreviar

    if ($rc == 'rara' || $rc == 'epica' || $rc == 'lendaria') {
        $rarasOuSuperiores += (int) $carta['quantidade'];
    }

    }


    
    //
    // Estastisticas/geral: Conquistas
    //

    $totalConquistas = $conquistasModel->contarConquistas($usuarioId);
    $numExistenteConquistas = $conquistasModel->listarConquistas();
    $conquistasPreview = $conquistasModel->conquistasPreview($usuarioId);


    //
    // progressão da cole~çao
    //

    $progressoColecao =(int) (($qtdCartas / $cartasExistentes) * 100);
    $cartasRestantes =(int) ($cartasExistentes - $qtdCartas);


    //
    // Ranking
    //

    $posicaoAtual = $rankingModel->buscarPosicao($usuarioId);

?>

<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Meu perfil | CardVerse</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container py-5 profile-page">

    <!-- Cabeçalho do perfil -->
    <section class="profile-hero mb-4">
        <div class="profile-main">

            <div class="profile-avatar-wrapper">
                <img
                    src="<?= htmlspecialchars($avatar) ?>"
                    alt="Foto de perfil"
                    class="profile-avatar"
                >
                <span class="profile-online" title="Jogador"></span>
            </div>

            <div class="profile-identity">
                <div class="eyebrow mb-2">
                    <i class="bi bi-stars me-1"></i>
                    Perfil do colecionador
                </div>

                <h1><?= htmlspecialchars($usuario['nome']) ?></h1>

                <span class="profile-title">
                    <i class="bi bi-lightning-charge-fill me-1"></i>
                    Colecionador iniciante
                </span>

                <p class="muted mt-3 mb-0">
                    Explorando o universo do CardVerse.
                </p>
            </div>

        </div>

        <div class="profile-member">
            <i class="bi bi-calendar3"></i>
            <span>
                Membro desde
                <strong><?= date('d/m/Y', strtotime($usuario['criado_em'])) ?></strong>
            </span>
        </div>
    </section>


    <!-- Nível e experiência -->
    <section class="profile-level-panel mb-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <div class="eyebrow mb-2">Progresso do jogador</div>
                <h2 class="section-title mb-0">Seu nível</h2>
            </div>

            <div class="level-number">
                <span>LVL</span>
                <strong><?= $nivel ?></strong>
            </div>
        </div>

        <div class="level-progress-info">
            <span><?=$nivel?></span>
            <span><?= $xp; echo '/ ' . $proxNivel?></span>
        </div>

        <div class="progress profile-progress" role="progressbar"
             aria-label="Progresso de experiência"
             aria-valuenow="<?=$progressoXP?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: <?=$progressoXP?>%"></div>
        </div>

        <div class="level-footer">
            <span><i class="bi bi-stars me-1"></i> <?=$xp?> XP acumulados</span>
            <span><?=$proxNivel?> XP para o próximo nível</span>
        </div>

    </section>


    <!-- Estatísticas -->
    <section class="mb-5">

        <h2 class="section-title h4 mb-3">Estatísticas do jogador</h2>

        <div class="row g-3">

            <div class="col-6 col-lg-3">
                <div class="stat profile-stat">
                    <span class="stat-icon">
                        <i class="bi bi-collection"></i>
                    </span>
                    <div class="muted mb-1">Cartas diferentes</div>
                    <div class="stat-number"><?=$qtdCartas?></div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat profile-stat">
                    <span class="stat-icon gold">
                        <i class="bi bi-layers"></i>
                    </span>
                    <div class="muted mb-1">Total de cartas</div>
                    <div class="stat-number"><?=$totalCartas?></div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat profile-stat">
                    <span class="stat-icon">
                        <i class="bi bi-gem"></i>
                    </span>
                    <div class="muted mb-1">Cartas raras ou superiores</div>
                    <div class="stat-number"><?=$rarasOuSuperiores?></div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat profile-stat">
                    <span class="stat-icon gold">
                        <i class="bi bi-trophy"></i>
                    </span>
                    <div class="muted mb-1">Conquistas</div>
                    <div class="stat-number"><?=$totalConquistas?></div>
                </div>
            </div>

        </div>
    </section>


    <!-- Coleção e ranking -->
    <section class="row g-3 mb-5">

        <div class="col-lg-7">
            <div class="profile-panel h-100">

                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="section-title h5 mb-0">Progresso da coleção</h2>
                    <i class="bi bi-grid-3x3-gap profile-panel-icon"></i>
                </div>

                <div class="collection-progress-number">
                    <strong><?=$qtdCartas?></strong>
                    <span class="muted">de <?=$cartasExistentes?> cartas descobertas</span>
                </div>

                <div class="progress profile-progress mt-3">
                    <div class="progress-bar collection-progress-fill" style="width: <?=$progressoColecao?>%"></div>
                </div>

                <div class="d-flex justify-content-between mt-2">
                    <span class="muted small"><?=$progressoColecao?>% concluído</span>
                    <span class="gold small fw-bold"><?=$cartasRestantes?> cartas restantes</span>
                </div>

                <a href="colecao.php" class="btn btn-outline-game mt-4">
                    <i class="bi bi-grid me-1"></i>
                    Ver minha coleção
                </a>

            </div>
        </div>


        <div class="col-lg-5">
            <div class="profile-panel ranking-panel h-100">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="section-title h5 mb-0">Ranking</h2>
                    <i class="bi bi-bar-chart-line profile-panel-icon"></i>
                </div>

                <div class="ranking-position">
                    <i class="bi bi-trophy-fill"></i>
                    <div>
                        <span class="muted small">Sua posição atual</span>
                        <strong><?=$posicaoAtual?>º lugar</strong>
                    </div>
                </div>

                <div class="ranking-points mt-3">
                    <span class="muted">Pontos no ranking</span>
                    <strong><?=$pontosAtuais?></strong>
                </div>

                <p class="muted small mt-3 mb-0">
                    Continue colecionando e desbloqueando conquistas para subir no ranking!
                </p>

            </div>
        </div>

    </section>


        
    <!-- Conquistas -->
    <section class="mb-5">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <div class="eyebrow mb-1">Suas recompensas</div>
                <h2 class="section-title h4 mb-0">Conquistas</h2>
            </div>

            <span class="tag">
                <?= $totalConquistas ?> de <?= $numExistenteConquistas ?> desbloqueadas
            </span>
        </div>

        <div class="row g-3">

            <?php foreach ($conquistasPreview as $conquista): ?>

                <?php
                    $desbloqueada = $conquista['usuario_conquista_id'] !== null;

                    $icone = !empty($conquista['icone'])
                        ? $conquista['icone']
                        : 'bi-award-fill';
                ?>

                <div class="col-md-6 col-xl-3">

                    <div class="achievement-card <?= $desbloqueada ? 'unlocked' : 'locked' ?>">

                        <div class="achievement-icon <?= $desbloqueada ? 'gold' : '' ?>">
                            <i class="bi <?= htmlspecialchars($icone) ?>"></i>
                        </div>

                        <span class="achievement-status">
                            <?= $desbloqueada ? 'Desbloqueada' : 'Bloqueada' ?>
                        </span>

                        <h3>
                            <?= htmlspecialchars($conquista['nome']) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($conquista['descricao']) ?>
                        </p>

                        <div class="achievement-reward">

                            <?php if ($desbloqueada): ?>

                                <span>
                                    <i class="bi bi-star-fill"></i>
                                    +<?= (int)$conquista['experiencia'] ?> XP
                                </span>

                                <span>
                                    <i class="bi bi-coin"></i>
                                    +<?= (int)$conquista['pontos'] ?> pontos
                                </span>

                            <?php else: ?>

                                <span>
                                    <i class="bi bi-lock-fill"></i>
                                    Recompensa bloqueada
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

            <?php if (empty($conquistasPreview)): ?>

                <div class="col-12">
                    <div class="profile-panel text-center py-4">
                        <i class="bi bi-trophy fs-1 muted"></i>
                        <h3 class="h5 mt-3">Nenhuma conquista cadastrada</h3>
                        <p class="muted mb-0">
                            As conquistas aparecerão aqui quando forem adicionadas ao jogo.
                        </p>
                    </div>
                </div>

            <?php endif; ?>

        </div>

        <div class="text-center mt-4">
            <a href="conquistas.php" class="btn btn-outline-game">
                <i class="bi bi-trophy me-2"></i>
                Ver todas as conquistas
                <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>

    </section>



        </div>
    </section>


    <!-- Missões -->
    <!-- <section class="mb-4">

        <h2 class="section-title h4 mb-3">Missões Diarias</h2>

        <div class="profile-panel activity-list">

            <div class="activity-item">
                <span class="activity-icon gold">
                    <i class="bi bi-gem"></i>
                </span>
                <div class="activity-content">
                    <strong>Nova carta rara descoberta</strong>
                    <span>Você adicionou uma carta à sua coleção.</span>
                </div>
                <span class="activity-date">Hoje</span>
            </div>

            <div class="activity-item">
                <span class="activity-icon purple">
                    <i class="bi bi-box-seam"></i>
                </span>
                <div class="activity-content">
                    <strong>Pacote aberto</strong>
                    <span>Você realizou uma nova abertura.</span>
                </div>
                <span class="activity-date">Ontem</span>
            </div>

            <div class="activity-item">
                <span class="activity-icon gold">
                    <i class="bi bi-trophy"></i>
                </span>
                <div class="activity-content">
                    <strong>Conquista desbloqueada</strong>
                    <span>Você concluiu uma nova conquista.</span>
                </div>
                <span class="activity-date">Há 2 dias</span>
            </div>

        </div>

    </section> -->

</main>

</body>
</html>
