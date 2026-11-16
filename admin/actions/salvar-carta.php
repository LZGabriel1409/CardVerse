<?php
/**
 * Salva uma carta nova ou atualiza uma carta existente.
 */

// Dependências.
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../classes/autoload.php';

// Protege a ação: somente administradores podem salvar cartas.
exigirAdmin($pdo, '../../login.php');

// Esta ação aceita somente requisições POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../admin/cartas.php');
    exit;
}

// Valida o token CSRF.
validarCsrf();

// Endereço para retornar ao formulário.
$id = null;
$editando = isset($_POST['id']);

if ($editando) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);

    if ($id === false || $id <= 0) {
        mensagem('danger', 'Identificador da carta inválido.');
        header('Location: ../../admin/cartas.php');
        exit;
    }
}

$destinoFormulario = '../../admin/carta-form.php';

if ($editando) {
    $destinoFormulario .= '?id=' . $id;
}

// Busca a carta atual quando estamos editando.
$cartaAtual = null;

if ($editando) {
    $cartaAtual = (new Carta($pdo))->buscarPorId($id);

    if (!$cartaAtual) {
        mensagem('danger', 'A carta que você tentou editar não existe.');
        header('Location: ../../admin/cartas.php');
        exit;
    }
}

// Recebe e limpa os campos de texto.
$nome = trim((string) ($_POST['nome'] ?? ''));
$descricao = trim((string) ($_POST['descricao'] ?? ''));
$raridade = (string) ($_POST['raridade'] ?? '');
$ataque = filter_var($_POST['ataque'] ?? null, FILTER_VALIDATE_INT);
$defesa = filter_var($_POST['defesa'] ?? null, FILTER_VALIDATE_INT);

// Validação dos campos obrigatórios.
if (
    $nome === '' ||
    mb_strlen($nome) > 100 ||
    !array_key_exists($raridade, Carta::RARIDADES) ||
    $ataque === false ||
    $defesa === false ||
    $ataque === null ||
    $defesa === null ||
    $ataque < 0 ||
    $defesa < 0 ||
    $ataque > 4294967295 ||
    $defesa > 4294967295
) {
    mensagem('danger', 'Verifique os campos. Existem informações inválidas.');
    header("Location: $destinoFormulario");
    exit;
}

// A imagem atual é mantida durante uma edição sem novo upload.
$caminhoImagem = $cartaAtual['imagem'] ?? '';

// Verifica se foi enviado um arquivo.
$arquivo = $_FILES['imagem'] ?? null;
$temNovaImagem = $arquivo && $arquivo['error'] !== UPLOAD_ERR_NO_FILE;

if (!$editando && !$temNovaImagem) {
    mensagem('danger', 'Selecione uma imagem para a carta.');
    header("Location: $destinoFormulario");
    exit;
}

// Processamento do upload.
if ($temNovaImagem) {

    // Verifica possíveis erros no envio.
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        mensagem('danger', 'Ocorreu um erro durante o envio da imagem.');
        header("Location: $destinoFormulario");
        exit;
    }

    // Limite de 5 MB por imagem.
    $limiteImagem = 5 * 1024 * 1024;

    if ($arquivo['size'] > $limiteImagem) {
        mensagem('danger', 'A imagem deve ter no máximo 5 MB.');
        header("Location: $destinoFormulario");
        exit;
    }

    // Confirma que o arquivo foi enviado pelo formulário.
    if (!is_uploaded_file($arquivo['tmp_name'])) {
        mensagem('danger', 'Arquivo de imagem inválido.');
        header("Location: $destinoFormulario");
        exit;
    }

    // Identifica o tipo real do arquivo.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipoImagem = $finfo->file($arquivo['tmp_name']);

    // Define extensões permitidas de acordo com o MIME.
    $tiposPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($tiposPermitidos[$tipoImagem])) {
        mensagem('danger', 'Formato inválido. Utilize PNG, JPG ou WEBP.');
        header("Location: $destinoFormulario");
        exit;
    }

    // Confirma que o arquivo é uma imagem válida.
    if (@getimagesize($arquivo['tmp_name']) === false) {
        mensagem('danger', 'O arquivo enviado não é uma imagem válida.');
        header("Location: $destinoFormulario");
        exit;
    }

    // Pasta de destino das imagens.
   $pastaImagens = __DIR__ . '/../../assets/img/cartas/';

    // Cria a pasta caso ainda não exista.
    if (
        !is_dir($pastaImagens) &&
        !mkdir($pastaImagens, 0755, true) &&
        !is_dir($pastaImagens)
    ) {
        mensagem('danger', 'Não foi possível acessar a pasta de imagens.');
        header("Location: $destinoFormulario");
        exit;
    }

    // Gera um nome aleatório para evitar conflitos.
    $nomeArquivo = bin2hex(random_bytes(16))
        . '.' . $tiposPermitidos[$tipoImagem];

    $caminhoDestino = $pastaImagens . $nomeArquivo;

    // Move a imagem para a pasta definitiva.
    if (!move_uploaded_file($arquivo['tmp_name'], $caminhoDestino)) {
        mensagem('danger', 'Não foi possível salvar a imagem enviada.');
        header("Location: $destinoFormulario");
        exit;
    }

    // Caminho relativo armazenado no banco de dados.
    $caminhoImagem = 'assets/img/cartas/' . $nomeArquivo;
}

// Salva os dados no banco.
try {

    if ($editando) {

        // Atualiza uma carta existente.
        $stmt = $pdo->prepare(
            'UPDATE cartas
             SET nome = ?,
                 descricao = ?,
                 imagem = ?,
                 raridade = ?,
                 ataque = ?,
                 defesa = ?
             WHERE id = ?'
        );

        $stmt->execute([
            $nome,
            $descricao,
            $caminhoImagem,
            $raridade,
            $ataque,
            $defesa,
            $id
        ]);

        mensagem('success', 'Carta atualizada com sucesso!');

    } else {

        // Insere uma nova carta.
        $stmt = $pdo->prepare(
            'INSERT INTO cartas
                (nome, descricao, imagem, raridade, ataque, defesa)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $nome,
            $descricao,
            $caminhoImagem,
            $raridade,
            $ataque,
            $defesa
        ]);

        mensagem('success', 'Carta cadastrada com sucesso!');
    }

    header('Location: ../../admin/cartas.php');
    exit;

} catch (PDOException $e) {

    // Se o banco rejeitar o cadastro, remove apenas o novo arquivo enviado.
    if ($temNovaImagem && isset($caminhoDestino) && is_file($caminhoDestino)) {
        unlink($caminhoDestino);
    }

    // Código 23000: violação de integridade, como nome duplicado.
    if ($e->getCode() === '23000') {
        mensagem('danger', 'Já existe uma carta cadastrada com esse nome.');
    } else {
        error_log($e->getMessage());
        mensagem('danger', 'Não foi possível salvar a carta. Tente novamente.');
    }

    header("Location: $destinoFormulario");
    exit;
}