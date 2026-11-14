
<?php
require_once __DIR__ . '/config/auth.php';
exigirLogin();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/classes/autoload.php';


$usuarios = new Usuario($pdo);
$cartasModel = new Carta($pdo);
$conquistasModel = new Conquista($pdo);
$rankingModel = new Ranking($pdo);
$missaoModel = new Missao($pdo);

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

    $usuarios->verificarNivel($xp);

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


    //
    // Missoes
    //

    $missoesDiarias = $missaoModel->listarDiarias($usuarioId);
    $missoesSemanais = $missaoModel->listarSemanais($usuarioId);

    // $missaoModel->registrarProgresso($usuarioId, 'comprar_pacotes');
    // $missaoModel->registrarProgresso($usuarioId, 'abrir_pacotes');

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
    

<!-- ========================================= -->
<!-- MISSÕES DIÁRIAS -->
<!-- ========================================= -->

<section class="mb-4" id="missoes">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">

        <h2 class="section-title h4 mb-0">
            <i class="bi bi-calendar-check me-2"></i>
            Missões Diárias
        </h2>

        <span class="badge bg-success">
            <i class="bi bi-arrow-repeat me-1"></i>
            Renovam diariamente
        </span>

    </div>


    <div class="profile-panel activity-list">

        <?php if (empty($missoesDiarias)): ?>

            <div class="text-center py-4">

                <i class="bi bi-calendar-x fs-2 text-secondary"></i>

                <p class="mb-0 mt-2 text-secondary">
                    Nenhuma missão diária disponível no momento.
                </p>

            </div>

        <?php else: ?>

            <?php foreach ($missoesDiarias as $missao): ?>

                <?php

                    // Quantidade necessária para completar a missão
                    $necessaria = max(
                        1,
                        (int)$missao['quantidade_necessaria']
                    );

                    // Progresso atual, limitado ao objetivo
                    $progresso = min(
                        (int)$missao['progresso'],
                        $necessaria
                    );

                    // Calcula a porcentagem de conclusão
                    $porcentagem = (int) round(
                        ($progresso / $necessaria) * 100
                    );

                    // Verifica se a missão foi concluída
                    $concluida = !empty($missao['concluida_em']);

                    // Verifica se a recompensa já foi resgatada
                    $resgatada = !empty($missao['resgatada_em']);

                    // Ícone padrão caso não exista um cadastrado
                    $icone = !empty($missao['icone'])
                        ? $missao['icone']
                        : 'bi bi-flag';

                ?>

                <div class="activity-item align-items-start">

                    <!-- Ícone da missão -->
                    <span class="activity-icon purple">

                        <i class="<?= htmlspecialchars($icone, ENT_QUOTES, 'UTF-8') ?>"></i>

                    </span>


                    <!-- Conteúdo da missão -->
                    <div class="activity-content flex-grow-1">

                        <!-- Nome e status -->
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">

                            <strong>
                                <?= htmlspecialchars($missao['nome'], ENT_QUOTES, 'UTF-8') ?>
                            </strong>


                            <?php if ($resgatada): ?>

                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Resgatada
                                </span>

                            <?php elseif ($concluida): ?>

                                <span class="badge bg-warning text-dark">
                                    Concluída
                                </span>

                            <?php else: ?>

                                <span class="badge bg-secondary">
                                    Em andamento
                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- Descrição -->
                        <span>
                            <?= htmlspecialchars($missao['descricao'], ENT_QUOTES, 'UTF-8') ?>
                        </span>


                        <!-- Progresso -->
                        <div class="d-flex justify-content-between mt-3 mb-1">

                            <small class="text-secondary">
                                Progresso
                            </small>

                            <small class="fw-semibold">
                                <?= $progresso ?>/<?= $necessaria ?>
                                (<?= $porcentagem ?>%)
                            </small>

                        </div>


                        <!-- Barra de progresso -->
                        <div
                            class="progress"
                            style="height: 8px;"
                            role="progressbar"
                            aria-valuenow="<?= $porcentagem ?>"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >

                            <div
                                class="progress-bar <?= $concluida ? 'bg-success' : '' ?>"
                                style="width: <?= $porcentagem ?>%;"
                            ></div>

                        </div>


                        <!-- Recompensas -->
                        <div class="d-flex flex-wrap gap-2 mt-3">

                            <?php if ((int)$missao['recompensa_moedas'] > 0): ?>

                                <span class="badge bg-warning text-dark">

                                    <i class="bi bi-coin me-1"></i>

                                    +<?= (int)$missao['recompensa_moedas'] ?> moedas

                                </span>

                            <?php endif; ?>


                            <?php if ((int)$missao['recompensa_pontos'] > 0): ?>

                                <span class="badge bg-primary">

                                    <i class="bi bi-star me-1"></i>

                                    +<?= (int)$missao['recompensa_pontos'] ?> pontos

                                </span>

                            <?php endif; ?>


                            <?php if ((int)$missao['recompensa_xp'] > 0): ?>

                                <span class="badge bg-info text-dark">

                                    <i class="bi bi-lightning-charge me-1"></i>

                                    +<?= (int)$missao['recompensa_xp'] ?> XP

                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- Botão de resgate -->
                        <?php if ($resgatada): ?>

                            <div class="mt-3">

                                <span class="badge bg-success">

                                    <i class="bi bi-gift me-1"></i>

                                    Recompensa recebida!

                                </span>

                            </div>

                        <?php elseif ($concluida): ?>

                            <form
                                action="actions/resgatar-missao.php"
                                method="POST"
                                class="mt-3"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <input
                                    type="hidden"
                                    name="usuario_missao_id"
                                    value="<?= (int)$missao['usuario_missao_id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-success btn-sm"
                                >

                                    <i class="bi bi-gift me-1"></i>

                                    Resgatar recompensa

                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</section>



<!-- ========================================= -->
<!-- MISSÕES SEMANAIS -->
<!-- ========================================= -->

<section class="mb-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">

        <h2 class="section-title h4 mb-0">

            <i class="bi bi-calendar-week me-2"></i>

            Missões Semanais

        </h2>

        <span class="badge bg-primary">

            <i class="bi bi-arrow-repeat me-1"></i>

            Renovam semanalmente

        </span>

    </div>


    <div class="profile-panel activity-list">

        <?php if (empty($missoesSemanais)): ?>

            <div class="text-center py-4">

                <i class="bi bi-calendar-x fs-2 text-secondary"></i>

                <p class="mb-0 mt-2 text-secondary">

                    Nenhuma missão semanal disponível no momento.

                </p>

            </div>

        <?php else: ?>

            <?php foreach ($missoesSemanais as $missao): ?>

                <?php

                    // Quantidade necessária para completar a missão
                    $necessaria = max(
                        1,
                        (int)$missao['quantidade_necessaria']
                    );

                    // Progresso atual, limitado ao objetivo
                    $progresso = min(
                        (int)$missao['progresso'],
                        $necessaria
                    );

                    // Calcula a porcentagem de conclusão
                    $porcentagem = (int) round(
                        ($progresso / $necessaria) * 100
                    );

                    // Verifica se a missão foi concluída
                    $concluida = !empty($missao['concluida_em']);

                    // Verifica se a recompensa já foi resgatada
                    $resgatada = !empty($missao['resgatada_em']);

                    // Ícone padrão caso não exista um cadastrado
                    $icone = !empty($missao['icone'])
                        ? $missao['icone']
                        : 'bi bi-flag';

                ?>

                <div class="activity-item align-items-start">

                    <!-- Ícone da missão -->
                    <span class="activity-icon gold">

                        <i class="<?= htmlspecialchars($icone, ENT_QUOTES, 'UTF-8') ?>"></i>

                    </span>


                    <!-- Conteúdo da missão -->
                    <div class="activity-content flex-grow-1">

                        <!-- Nome e status -->
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">

                            <strong>
                                <?= htmlspecialchars($missao['nome'], ENT_QUOTES, 'UTF-8') ?>
                            </strong>


                            <?php if ($resgatada): ?>

                                <span class="badge bg-success">

                                    <i class="bi bi-check-circle me-1"></i>

                                    Resgatada

                                </span>

                            <?php elseif ($concluida): ?>

                                <span class="badge bg-warning text-dark">

                                    Concluída

                                </span>

                            <?php else: ?>

                                <span class="badge bg-secondary">

                                    Em andamento

                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- Descrição -->
                        <span>
                            <?= htmlspecialchars($missao['descricao'], ENT_QUOTES, 'UTF-8') ?>
                        </span>


                        <!-- Progresso -->
                        <div class="d-flex justify-content-between mt-3 mb-1">

                            <small class="text-secondary">
                                Progresso
                            </small>

                            <small class="fw-semibold">

                                <?= $progresso ?>/<?= $necessaria ?>

                                (<?= $porcentagem ?>%)

                            </small>

                        </div>


                        <!-- Barra de progresso -->
                        <div
                            class="progress"
                            style="height: 8px;"
                            role="progressbar"
                            aria-valuenow="<?= $porcentagem ?>"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >

                            <div
                                class="progress-bar <?= $concluida ? 'bg-success' : '' ?>"
                                style="width: <?= $porcentagem ?>%;"
                            ></div>

                        </div>


                        <!-- Recompensas -->
                        <div class="d-flex flex-wrap gap-2 mt-3">

                            <?php if ((int)$missao['recompensa_moedas'] > 0): ?>

                                <span class="badge bg-warning text-dark">

                                    <i class="bi bi-coin me-1"></i>

                                    +<?= (int)$missao['recompensa_moedas'] ?> moedas

                                </span>

                            <?php endif; ?>


                            <?php if ((int)$missao['recompensa_pontos'] > 0): ?>

                                <span class="badge bg-primary">

                                    <i class="bi bi-star me-1"></i>

                                    +<?= (int)$missao['recompensa_pontos'] ?> pontos

                                </span>

                            <?php endif; ?>


                            <?php if ((int)$missao['recompensa_xp'] > 0): ?>

                                <span class="badge bg-info text-dark">

                                    <i class="bi bi-lightning-charge me-1"></i>

                                    +<?= (int)$missao['recompensa_xp'] ?> XP

                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- Botão de resgate -->
                        <?php if ($resgatada): ?>

                            <div class="mt-3">

                                <span class="badge bg-success">

                                    <i class="bi bi-gift me-1"></i>

                                    Recompensa recebida!

                                </span>

                            </div>

                        <?php elseif ($concluida): ?>

                            <form
                                action="actions/resgatar-missao.php"
                                method="POST"
                                class="mt-3"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <input
                                    type="hidden"
                                    name="usuario_missao_id"
                                    value="<?= (int)$missao['usuario_missao_id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-success btn-sm"
                                >

                                    <i class="bi bi-gift me-1"></i>

                                    Resgatar recompensa

                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</section>



</main>

</body>
</html>
