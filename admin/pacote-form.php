
<?php
/**
 * Formulário de cadastro e edição de pacotes.
 */

// ==========================================
// CONFIGURAÇÕES E AUTENTICAÇÃO
// ==========================================

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

exigirAdmin($pdo, '../login.php');

// ==========================================
// FUNÇÃO DE PROTEÇÃO
// ==========================================

function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// ==========================================
// VALORES PADRÃO
// ==========================================

$pacote = [
    'id' => null,
    'nome' => '',
    'descricao' => '',
    'selo' => '',
    'destaque' => 0,
    'raridade_garantida' => '',
    'chance_lendaria' => 0,
    'preco' => 100,
    'quantidade_cartas' => 5,
    'ativo' => 1
];

$editando = false;

// ==========================================
// BUSCA DO PACOTE PARA EDIÇÃO
// ==========================================

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id !== null && $id !== false) {

    if ($id <= 0) {
        mensagem('danger', 'Identificador inválido.');
        header('Location: pacotes.php');
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT * FROM pacotes WHERE id = ? LIMIT 1'
    );

    $stmt->execute([$id]);

    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resultado) {
        mensagem('danger', 'Pacote não encontrado.');
        header('Location: pacotes.php');
        exit;
    }

    $pacote = $resultado;
    $editando = true;
}

$flash = pegarMensagem();

?>

<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= $editando ? 'Editar pacote' : 'Novo pacote' ?> | CardVerse
    </title>

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

                    <h1>
                        <?= $editando ? 'Editar pacote' : 'Novo pacote' ?>
                    </h1>

                    <p class="muted mb-0">
                        Configure as informações principais do pacote.
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
                 FORMULÁRIO
            =================================== -->

            <section class="feature-card admin-form-card">

                <form action="actions/salvar-pacote.php"
                      method="POST">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= escapar(tokenCsrf()) ?>">

                    <?php if ($editando): ?>

                        <input type="hidden"
                               name="id"
                               value="<?= (int) $pacote['id'] ?>">

                    <?php endif; ?>

                    <!-- INFORMAÇÕES BÁSICAS -->

                    <div class="mb-4">

                        <h2 class="h5 mb-3">Informações básicas</h2>

                        <div class="row g-3">

                            <div class="col-md-8">

                                <label for="nome" class="form-label">
                                    Nome do pacote
                                </label>

                                <input type="text"
                                       class="form-control admin-input"
                                       id="nome"
                                       name="nome"
                                       maxlength="100"
                                       value="<?= escapar($pacote['nome']) ?>"
                                       placeholder="Ex: Pacote Místico"
                                       required>

                            </div>

                            <div class="col-md-4">

                                <label for="selo" class="form-label">
                                    Selo
                                </label>

                                <input type="text"
                                       class="form-control admin-input"
                                       id="selo"
                                       name="selo"
                                       maxlength="30"
                                       value="<?= escapar($pacote['selo']) ?>"
                                       placeholder="Ex: Popular">

                            </div>

                            <div class="col-12">

                                <label for="descricao" class="form-label">
                                    Descrição
                                </label>

                                <textarea class="form-control admin-input"
                                          id="descricao"
                                          name="descricao"
                                          rows="3"
                                          placeholder="Descreva o conteúdo do pacote..."><?= escapar($pacote['descricao']) ?></textarea>

                            </div>

                        </div>

                    </div>

                    <hr class="border-secondary my-4">

                    <!-- CONFIGURAÇÕES DE COMPRA -->

                    <div class="mb-4">

                        <h2 class="h5 mb-3">Configurações de compra</h2>

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label for="preco" class="form-label">
                                    Preço em moedas
                                </label>

                                <input type="number"
                                       class="form-control admin-input"
                                       id="preco"
                                       name="preco"
                                       min="1"
                                       max="4294967295"
                                       value="<?= (int) $pacote['preco'] ?>"
                                       required>

                            </div>

                            <div class="col-md-6">

                                <label for="quantidade_cartas" class="form-label">
                                    Cartas por abertura
                                </label>

                                <input type="number"
                                       class="form-control admin-input"
                                       id="quantidade_cartas"
                                       name="quantidade_cartas"
                                       min="1"
                                       max="255"
                                       value="<?= (int) $pacote['quantidade_cartas'] ?>"
                                       required>

                            </div>

                        </div>

                    </div>

                    <hr class="border-secondary my-4">

                    <!-- PROBABILIDADES E RARIDADES -->

                    <div class="mb-4">

                        <h2 class="h5 mb-3">Configurações de raridade</h2>

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label for="raridade_garantida" class="form-label">
                                    Raridade garantida
                                </label>

                                <select class="form-select admin-input"
                                        id="raridade_garantida"
                                        name="raridade_garantida">

                                    <option value="">
                                        Nenhuma garantia
                                    </option>

                                    <option value="comum"
                                        <?= $pacote['raridade_garantida'] === 'comum' ? 'selected' : '' ?>>
                                        Comum
                                    </option>

                                    <option value="rara"
                                        <?= $pacote['raridade_garantida'] === 'rara' ? 'selected' : '' ?>>
                                        Rara
                                    </option>

                                    <option value="epica"
                                        <?= $pacote['raridade_garantida'] === 'epica' ? 'selected' : '' ?>>
                                        Épica
                                    </option>

                                    <option value="lendaria"
                                        <?= $pacote['raridade_garantida'] === 'lendaria' ? 'selected' : '' ?>>
                                        Lendária
                                    </option>

                                </select>

                            </div>

                            <div class="col-md-6">

                                <label for="chance_lendaria" class="form-label">
                                    Chance lendária (%)
                                </label>

                                <input type="number"
                                       class="form-control admin-input"
                                       id="chance_lendaria"
                                       name="chance_lendaria"
                                       min="0"
                                       max="100"
                                       value="<?= (int) $pacote['chance_lendaria'] ?>"
                                       required>

                            </div>

                        </div>

                        <small class="muted d-block mt-2">
                            A distribuição completa das chances por raridade
                            será configurada em uma etapa posterior.
                        </small>

                    </div>

                    <hr class="border-secondary my-4">

                    <!-- OPÇÕES DO PACOTE -->

                    <div class="mb-4">

                        <h2 class="h5 mb-3">Opções do pacote</h2>

                        <div class="row g-3">

                            <div class="col-md-6">

                                <div class="form-check form-switch">

                                    <input class="form-check-input"
                                           type="checkbox"
                                           role="switch"
                                           id="destaque"
                                           name="destaque"
                                           value="1"
                                           <?= (int) $pacote['destaque'] === 1 ? 'checked' : '' ?>>

                                    <label class="form-check-label"
                                           for="destaque">
                                        Pacote em destaque
                                    </label>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="form-check form-switch">

                                    <input class="form-check-input"
                                           type="checkbox"
                                           role="switch"
                                           id="ativo"
                                           name="ativo"
                                           value="1"
                                           <?= (int) $pacote['ativo'] === 1 ? 'checked' : '' ?>>

                                    <label class="form-check-label"
                                           for="ativo">
                                        Pacote ativo
                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- BOTÕES -->

                    <div class="admin-form-actions pt-4">

                        <a href="pacotes.php"
                           class="btn btn-outline-game">
                            Cancelar
                        </a>

                        <button type="submit" class="btn btn-magic">
                            <i class="bi bi-check-lg me-2"></i>

                            <?= $editando ? 'Salvar alterações' : 'Cadastrar pacote' ?>
                        </button>

                    </div>

                </form>

            </section>

        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
