
<?php
// ==========================================
// CONFIGURAÇÕES E AUTENTICAÇÃO
// ==========================================

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

// Impede o acesso de usuários que não são administradores
exigirAdmin($pdo, '../login.php');

// ==========================================
// VALIDAÇÃO DO PACOTE
// ==========================================

$pacoteId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$pacoteId || $pacoteId <= 0) {
    mensagem('danger', 'Pacote inválido.');
    header('Location: pacotes.php');
    exit;
}

// ==========================================
// BUSCA OS DADOS DO PACOTE
// ==========================================

try {
    $sql = "SELECT id, nome, chance_lendaria, raridade_garantida
            FROM pacotes
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $pacoteId]);

    $pacote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pacote) {
        mensagem('danger', 'Pacote não encontrado.');
        header('Location: pacotes.php');
        exit;
    }

    // ==========================================
    // BUSCA AS CHANCES CADASTRADAS
    // ==========================================

    $sql = "SELECT raridade, peso
            FROM pacote_chances
            WHERE pacote_id = :pacote_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pacote_id' => $pacoteId]);

    $chancesCadastradas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Valores padrão caso ainda não existam chances
    $chances = [
        'comum' => 100,
        'rara' => 0,
        'epica' => 0
    ];

    // Substitui os valores padrão pelos já cadastrados
    foreach ($chances as $raridade => $peso) {
        if (isset($chancesCadastradas[$raridade])) {
            $chances[$raridade] = (int) $chancesCadastradas[$raridade];
        }
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    mensagem('danger', 'Erro ao carregar as chances do pacote.');
    header('Location: pacotes.php');
    exit;
}

// ==========================================
// CONFIGURAÇÕES VISUAIS
// ==========================================

$raridades = [
    'comum' => 'Comum',
    'rara' => 'Rara',
    'epica' => 'Épica'
];

$flash = pegarMensagem();

?>

<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Chances do Pacote | CardVerse</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>

    <main class="container py-5">

        <!-- ======================================
             CABEÇALHO
        ======================================= -->

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <div>
                <span class="eyebrow">ADMINISTRAÇÃO</span>

                <h1 class="section-title mb-1">
                    Chances do pacote
                </h1>

                <p class="muted mb-0">
                    Configure a distribuição das raridades de
                    <strong><?= htmlspecialchars($pacote['nome']) ?></strong>.
                </p>
            </div>

            <a href="pacotes.php" class="btn btn-outline-game">
                <i class="bi bi-arrow-left me-2"></i>
                Voltar
            </a>

        </div>

        <!-- ======================================
             MENSAGENS
        ======================================= -->

        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?> alert-dismissible fade show">
                <?= htmlspecialchars($flash['texto']) ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ======================================
             INFORMAÇÕES SOBRE AS PROBABILIDADES
        ======================================= -->

        <div class="feature-card p-4 mb-4">

            <h5 class="mb-3">
                <i class="bi bi-info-circle me-2"></i>
                Como funcionam as chances?
            </h5>

            <p class="muted mb-2">
                Os pesos abaixo devem somar exatamente <strong>100%</strong>.
                Eles determinam a distribuição entre cartas comuns, raras e épicas.
            </p>

            <p class="muted mb-0">
                A chance de obter uma carta lendária é independente e está
                configurada em outro campo do pacote.
            </p>

        </div>

        <!-- ======================================
             CHANCE LENDÁRIA
        ======================================= -->

        <div class="feature-card p-4 mb-4">

            <span class="eyebrow">CHANCE INDEPENDENTE</span>

            <h5 class="mb-2">Raridade lendária</h5>

            <h2 class="mb-0">
                <?= (int) $pacote['chance_lendaria'] ?>%
            </h2>

            <p class="muted mt-2 mb-0">
                Para alterar esse valor, utilize a edição do pacote.
            </p>

        </div>

        <!-- ======================================
             FORMULÁRIO DE CHANCES
        ======================================= -->

        <div class="feature-card p-4">

            <form action="actions/salvar-chances-pacote.php" method="POST">

                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars(tokenCsrf()) ?>">

                <input type="hidden" name="pacote_id"
                       value="<?= (int) $pacote['id'] ?>">

                <h5 class="mb-4">Distribuição das raridades</h5>

                <div class="row g-3">

                    <?php foreach ($raridades as $raridade => $rotulo): ?>

                        <div class="col-md-4">

                            <label for="peso_<?= $raridade ?>" class="form-label">
                                <?= $rotulo ?> (%)
                            </label>

                            <input
                                type="number"
                                class="form-control admin-input"
                                id="peso_<?= $raridade ?>"
                                name="peso_<?= $raridade ?>"
                                min="0"
                                max="100"
                                step="1"
                                value="<?= $chances[$raridade] ?>"
                                required
                            >

                        </div>

                    <?php endforeach; ?>

                </div>

                <!-- ======================================
                     TOTAL DAS CHANCES
                ======================================= -->

                <div class="mt-4 p-3 rounded" style="background: var(--surface-raised);">

                    <div class="d-flex justify-content-between align-items-center">

                        <span class="muted">Total das probabilidades:</span>

                        <strong id="totalChances">100%</strong>

                    </div>

                    <div class="progress mt-2" style="height: 8px;">
                        <div
                            id="barraChances"
                            class="progress-bar"
                            role="progressbar"
                            style="width: 100%;"
                        ></div>
                    </div>

                    <small id="mensagemChances" class="muted mt-2 d-block">
                        A distribuição está correta.
                    </small>

                </div>

                <!-- ======================================
                     RARIDADE GARANTIDA
                ======================================= -->

                <div class="mt-4">

                    <label class="form-label">Raridade garantida</label>

                    <div class="form-control admin-input">
                        <?= $pacote['raridade_garantida']
                            ? ucfirst($pacote['raridade_garantida'])
                            : 'Nenhuma' ?>
                    </div>

                    <small class="muted">
                        Essa configuração é definida na edição do pacote.
                    </small>

                </div>

                <!-- ======================================
                     BOTÕES
                ======================================= -->

                <div class="d-flex justify-content-end gap-2 mt-4">

                    <a href="pacotes.php" class="btn btn-outline-game">
                        Cancelar
                    </a>

                    <button type="submit" id="btnSalvar" class="btn btn-magic">
                        <i class="bi bi-save me-2"></i>
                        Salvar chances
                    </button>

                </div>

            </form>

        </div>

    </main>

    <!-- ======================================
         JAVASCRIPT: SOMA DAS CHANCES
    ======================================= -->

    <script>
        const campos = [
            document.getElementById('peso_comum'),
            document.getElementById('peso_rara'),
            document.getElementById('peso_epica')
        ];

        const totalElemento = document.getElementById('totalChances');
        const barra = document.getElementById('barraChances');
        const mensagem = document.getElementById('mensagemChances');
        const botaoSalvar = document.getElementById('btnSalvar');

        function atualizarTotal() {

            // Soma os valores dos três campos
            const total = campos.reduce((soma, campo) => {
                return soma + (Number(campo.value) || 0);
            }, 0);

            totalElemento.textContent = total + '%';

            // Atualiza a barra visual
            barra.style.width = Math.min(total, 100) + '%';

            // Verifica se a soma está correta
            if (total === 100) {

                totalElemento.style.color = 'var(--gold)';
                mensagem.textContent = 'A distribuição está correta.';
                botaoSalvar.disabled = false;

            } else {

                totalElemento.style.color = '#dc3545';
                mensagem.textContent = 'A soma precisa ser exatamente 100%.';
                botaoSalvar.disabled = true;

            }
        }

        // Atualiza a soma sempre que um campo mudar
        campos.forEach(campo => {
            campo.addEventListener('input', atualizarTotal);
        });

        // Verifica a distribuição inicial
        atualizarTotal();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
