
<?php
/**
 * Salva um pacote novo ou atualiza um pacote existente.
 */

// ==========================================
// CONFIGURAÇÕES E AUTENTICAÇÃO
// ==========================================

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/conexao.php';

exigirAdmin($pdo, '../../login.php');

// ==========================================
// VERIFICAÇÃO DO MÉTODO E CSRF
// ==========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pacotes.php');
    exit;
}

validarCsrf();

// ==========================================
// RECEBIMENTO DOS DADOS
// ==========================================

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$selo = trim($_POST['selo'] ?? '');

$preco = filter_var(
    $_POST['preco'] ?? null,
    FILTER_VALIDATE_INT
);

$quantidadeCartas = filter_var(
    $_POST['quantidade_cartas'] ?? null,
    FILTER_VALIDATE_INT
);

$chanceLendaria = filter_var(
    $_POST['chance_lendaria'] ?? null,
    FILTER_VALIDATE_INT
);

$raridadeGarantida = $_POST['raridade_garantida'] ?? '';

$destaque = isset($_POST['destaque']) ? 1 : 0;
$ativo = isset($_POST['ativo']) ? 1 : 0;

// ==========================================
// VALIDAÇÃO DOS DADOS
// ==========================================

$raridadesPermitidas = [
    '',
    'comum',
    'rara',
    'epica',
    'lendaria'
];

if ($id === false || ($id !== null && $id <= 0)) {
    mensagem('danger', 'Identificador do pacote inválido.');
    header('Location: ../pacotes.php');
    exit;
}

if ($nome === '' || strlen($nome) > 100) {
    mensagem('danger', 'Informe um nome válido para o pacote.');
    header('Location: ../pacote-form.php' . ($id ? '?id=' . $id : ''));
    exit;
}

if (strlen($selo) > 30) {
    mensagem('danger', 'O selo deve ter no máximo 30 caracteres.');
    header('Location: ../pacote-form.php' . ($id ? '?id=' . $id : ''));
    exit;
}

if (
    $preco === false ||
    $preco < 1 ||
    $preco > 4294967295
) {
    mensagem('danger', 'Informe um preço válido.');
    header('Location: ../pacote-form.php' . ($id ? '?id=' . $id : ''));
    exit;
}

if (
    $quantidadeCartas === false ||
    $quantidadeCartas < 1 ||
    $quantidadeCartas > 255
) {
    mensagem('danger', 'A quantidade de cartas deve estar entre 1 e 255.');
    header('Location: ../pacote-form.php' . ($id ? '?id=' . $id : ''));
    exit;
}

if (
    $chanceLendaria === false ||
    $chanceLendaria < 0 ||
    $chanceLendaria > 100
) {
    mensagem('danger', 'A chance lendária deve estar entre 0 e 100.');
    header('Location: ../pacote-form.php' . ($id ? '?id=' . $id : ''));
    exit;
}

if (!in_array($raridadeGarantida, $raridadesPermitidas, true)) {
    mensagem('danger', 'A raridade selecionada é inválida.');
    header('Location: ../pacote-form.php' . ($id ? '?id=' . $id : ''));
    exit;
}

// ==========================================
// PREPARAÇÃO DOS DADOS
// ==========================================

$descricao = $descricao !== '' ? $descricao : null;
$selo = $selo !== '' ? $selo : null;

$raridadeGarantida = $raridadeGarantida !== ''
    ? $raridadeGarantida
    : null;

// ==========================================
// CADASTRO OU ATUALIZAÇÃO
// ==========================================

try {

    // Verifica se o nome já está sendo utilizado.
    if ($id !== null) {

        $stmt = $pdo->prepare(
            'SELECT id FROM pacotes
             WHERE nome = ? AND id <> ?
             LIMIT 1'
        );

        $stmt->execute([$nome, $id]);

    } else {

        $stmt = $pdo->prepare(
            'SELECT id FROM pacotes
             WHERE nome = ?
             LIMIT 1'
        );

        $stmt->execute([$nome]);
    }

    if ($stmt->fetch()) {
        mensagem('warning', 'Já existe um pacote com esse nome.');
        header('Location: ../pacote-form.php' . ($id ? '?id=' . $id : ''));
        exit;
    }

    // ======================================
    // ATUALIZAÇÃO
    // ======================================

    if ($id !== null) {

        // Confirma que o pacote realmente existe.
        $stmt = $pdo->prepare(
            'SELECT id FROM pacotes WHERE id = ? LIMIT 1'
        );

        $stmt->execute([$id]);

        if (!$stmt->fetch()) {
            mensagem('danger', 'Pacote não encontrado.');
            header('Location: ../pacotes.php');
            exit;
        }

        $sql = "
            UPDATE pacotes
            SET
                nome = ?,
                descricao = ?,
                selo = ?,
                destaque = ?,
                raridade_garantida = ?,
                chance_lendaria = ?,
                preco = ?,
                quantidade_cartas = ?,
                ativo = ?
            WHERE id = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $nome,
            $descricao,
            $selo,
            $destaque,
            $raridadeGarantida,
            $chanceLendaria,
            $preco,
            $quantidadeCartas,
            $ativo,
            $id
        ]);

        mensagem('success', 'Pacote atualizado com sucesso!');

    } else {

        // ==================================
        // NOVO CADASTRO
        // ==================================

        $sql = "
            INSERT INTO pacotes (
                nome,
                descricao,
                selo,
                destaque,
                raridade_garantida,
                chance_lendaria,
                preco,
                quantidade_cartas,
                ativo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $nome,
            $descricao,
            $selo,
            $destaque,
            $raridadeGarantida,
            $chanceLendaria,
            $preco,
            $quantidadeCartas,
            $ativo
        ]);

        mensagem('success', 'Pacote cadastrado com sucesso!');
    }

    header('Location: ../pacotes.php');
    exit;

} catch (PDOException $e) {

    // Erro de chave duplicada no banco.
    if (
        isset($e->errorInfo[1]) &&
        (int) $e->errorInfo[1] === 1062
    ) {
        mensagem('warning', 'Já existe um pacote com esse nome.');

    } else {

        error_log($e->getMessage());

        mensagem('danger', 'Ocorreu um erro ao salvar o pacote.');
    }

    header('Location: ../pacote-form.php' . ($id ? '?id=' . $id : ''));
    exit;
}
