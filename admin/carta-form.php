<?php
/**
 * Formulário de cadastro e edição de cartas.
 */

// Dependências do sistema.
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../classes/autoload.php';

// Somente administradores podem acessar esta página.
exigirAdmin($pdo, '../login.php');

// Instância da classe Carta.
$cartas = new Carta($pdo);

// Verifica se estamos editando uma carta.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$editando = $id !== false && $id !== null && $id > 0;

// Busca os dados da carta, caso seja edição.
$carta = null;

if ($editando) {
    $carta = $cartas->buscarPorId($id);

    // Se o ID não existir, retorna erro 404.
    if (!$carta) {
        http_response_code(404);
        exit('Carta não encontrada.');
    }
}

// Mensagem temporária do sistema.
$flash = pegarMensagem();

// Dados padrão para um cadastro novo.
$nome = $carta['nome'] ?? '';
$descricao = $carta['descricao'] ?? '';
$raridade = $carta['raridade'] ?? 'comum';
$ataque = $carta['ataque'] ?? 0;
$defesa = $carta['defesa'] ?? 0;
$imagemAtual = $carta['imagem'] ?? '';

// Monta o caminho da imagem atual.
// No banco, as imagens existentes são armazenadas apenas pelo nome do arquivo.
$imagemUrl = '';

if ($imagemAtual !== '') {
    $imagemUrl = '../' . str_replace(' ', '%20', $imagemAtual);
}
?>

<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= $editando ? 'Editar carta' : 'Nova carta' ?> | CardVerse
    </title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- CSS principal do projeto -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <div class="container py-5">

        <!-- Cabeçalho -->
        <div class="admin-header">

            <div>
                <span class="eyebrow">GERENCIAMENTO DE CARTAS</span>

                <h1 class="mt-2">
                    <?= $editando ? 'Editar carta' : 'Nova carta' ?>
                </h1>

                <p class="muted mb-0">
                    <?= $editando
                        ? 'Atualize as informações desta carta.'
                        : 'Adicione uma nova carta ao catálogo do CardVerse.' ?>
                </p>
            </div>

            <a href="cartas.php" class="btn btn-outline-game">
                <i class="bi bi-arrow-left me-2"></i>
                Voltar
            </a>

        </div>

        <!-- Mensagem do sistema -->
        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?> alert-dismissible fade show" role="alert">

                <?= htmlspecialchars($flash['texto']) ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <div class="feature-card admin-form-card">

            <form action="actions/salvar-carta.php." method="POST" enctype="multipart/form-data">

                <!-- Token de segurança -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()) ?>">

                <!-- ID necessário durante a edição -->
                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= (int) $carta['id'] ?>">
                <?php endif; ?>

                <div class="row g-4">

                    <!-- Coluna dos campos -->
                    <div class="col-lg-8">

                        <div class="mb-4">
                            <label for="nome" class="form-label">Nome da carta</label>

                            <input
                                type="text"
                                class="form-control admin-input"
                                id="nome"
                                name="nome"
                                maxlength="100"
                                value="<?= htmlspecialchars($nome) ?>"
                                placeholder="Ex: Cavaleiro de Ferro"
                                required
                            >
                        </div>

                        <div class="mb-4">
                            <label for="descricao" class="form-label">Descrição</label>

                            <textarea
                                class="form-control admin-input"
                                id="descricao"
                                name="descricao"
                                rows="5"
                                placeholder="Descreva as características da carta..."
                            ><?= htmlspecialchars($descricao) ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="raridade" class="form-label">Raridade</label>

                            <select
                                class="form-select admin-input"
                                id="raridade"
                                name="raridade"
                                required
                            >
                                <?php foreach (Carta::RARIDADES as $valor => $dados): ?>
                                    <option
                                        value="<?= htmlspecialchars($valor) ?>"
                                        <?= $raridade === $valor ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($dados['rotulo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label for="ataque" class="form-label">
                                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
                                    Ataque
                                </label>

                                <input
                                    type="number"
                                    class="form-control admin-input"
                                    id="ataque"
                                    name="ataque"
                                    min="0"
                                    value="<?= (int) $ataque ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label for="defesa" class="form-label">
                                    <i class="bi bi-shield-fill text-info me-1"></i>
                                    Defesa
                                </label>

                                <input
                                    type="number"
                                    class="form-control admin-input"
                                    id="defesa"
                                    name="defesa"
                                    min="0"
                                    value="<?= (int) $defesa ?>"
                                    required
                                >
                            </div>

                        </div>

                    </div>

                    <!-- Coluna da imagem -->
                    <div class="col-lg-4">

                        <div class="admin-image-upload">

                            <label class="form-label">Imagem da carta</label>

                            <div class="admin-image-preview">

                                <?php if ($imagemUrl !== ''): ?>

                                    <img
                                        src="<?= htmlspecialchars($imagemUrl) ?>"
                                        alt="Imagem atual da carta"
                                        id="previewImagem"
                                    >

                                <?php else: ?>

                                    <div id="placeholderImagem">
                                        <i class="bi bi-image"></i>
                                        <span>Nenhuma imagem selecionada</span>
                                    </div>

                                    <img
                                        src=""
                                        alt="Pré-visualização da carta"
                                        id="previewImagem"
                                        class="d-none"
                                    >

                                <?php endif; ?>

                            </div>

                            <input
                                type="file"
                                class="form-control admin-input mt-3"
                                id="imagem"
                                name="imagem"
                                accept="image/png,image/jpeg,image/webp"
                                <?= $editando ? '' : 'required' ?>
                            >

                            <small class="muted d-block mt-2">
                                Formatos aceitos: PNG, JPG, WEBP e SVG.
                            </small>

                            <?php if ($editando): ?>
                                <small class="muted d-block mt-1">
                                    Se não selecionar outra imagem, a atual será mantida.
                                </small>
                            <?php endif; ?>

                        </div>

                    </div>

                </div>

                <!-- Botões -->
                <div class="admin-form-actions mt-5 pt-4">

                    <a href="cartas.php" class="btn btn-outline-game">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-magic">
                        <i class="bi bi-check-circle me-2"></i>

                        <?= $editando ? 'Salvar alterações' : 'Cadastrar carta' ?>
                    </button>

                </div>

            </form>

        </div>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Pré-visualização da imagem selecionada -->
    <script>
        const inputImagem = document.getElementById('imagem');
        const previewImagem = document.getElementById('previewImagem');
        const placeholderImagem = document.getElementById('placeholderImagem');

        inputImagem.addEventListener('change', function () {

            // Verifica se algum arquivo foi selecionado.
            const arquivo = this.files[0];

            if (!arquivo) {
                return;
            }

            // Cria uma URL temporária para mostrar a imagem.
            const urlImagem = URL.createObjectURL(arquivo);

            previewImagem.src = urlImagem;
            previewImagem.classList.remove('d-none');

            // Esconde o espaço reservado, se existir.
            if (placeholderImagem) {
                placeholderImagem.classList.add('d-none');
            }
        });
    </script>

</body>
</html>