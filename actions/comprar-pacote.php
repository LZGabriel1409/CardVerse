<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../loja.php');
    exit;
}

validarCsrf();

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if ($usuarioId <= 0) {
    header('Location: ../login.php');
    exit;
}

$pacoteId = filter_var($_POST['pacote_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

$tokenRecebido = (string)($_POST['compra_token'] ?? '');
$tokenSessao = (string)($_SESSION['compra_token'] ?? '');

if ($pacoteId === false) {
    mensagem('danger', 'Pacote inválido. Tente novamente.');
    header('Location: ../loja.php');
    exit;
}

// Impede reenvio do mesmo formulário (duplo clique, refresh ou token reutilizado)
if ($tokenSessao === '' || $tokenRecebido === '' || !hash_equals($tokenSessao, $tokenRecebido)) {
    mensagem('warning', 'Esta compra já foi processada ou expirou. Recarregue a loja e tente novamente.');
    header('Location: ../loja.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Bloqueia o pacote para validar disponibilidade e preço
    $stmt = $pdo->prepare(
        'SELECT id, nome, preco FROM pacotes WHERE id = ? AND ativo = 1 FOR UPDATE'
    );
    $stmt->execute([$pacoteId]);
    $pacote = $stmt->fetch();

    if (!$pacote) {
        $pdo->rollBack();
        mensagem('danger', 'Pacote indisponível ou inexistente.');
        header('Location: ../loja.php');
        exit;
    }

    $preco = (int)$pacote['preco'];

    if ($preco <= 0) {
        $pdo->rollBack();
        mensagem('danger', 'Este pacote não está disponível para compra.');
        header('Location: ../loja.php');
        exit;
    }

    // Bloqueia o saldo do jogador durante a compra
    $stmt = $pdo->prepare('SELECT moedas FROM usuarios WHERE id = ? FOR UPDATE');
    $stmt->execute([$usuarioId]);
    $saldo = $stmt->fetchColumn();

    if ($saldo === false) {
        $pdo->rollBack();
        mensagem('danger', 'Conta não encontrada. Entre novamente.');
        header('Location: ../logout.php');
        exit;
    }

    if ((int)$saldo < $preco) {
        $pdo->rollBack();
        mensagem('danger', 'Saldo insuficiente para comprar o pacote "' . $pacote['nome'] . '".');
        header('Location: ../loja.php');
        exit;
    }

    $stmt = $pdo->prepare('UPDATE usuarios SET moedas = moedas - ? WHERE id = ?');
    $stmt->execute([$preco, $usuarioId]);

    $stmt = $pdo->prepare(
        'INSERT INTO compras (usuario_id, pacote_id, valor_pago, token_compra) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$usuarioId, $pacoteId, $preco, $tokenRecebido]);

    $pdo->commit();

    // Consome o token: o formulário não pode ser enviado novamente
    unset($_SESSION['compra_token']);

    mensagem(
        'success',
        'Compra do pacote "' . $pacote['nome'] . '" realizada com sucesso! '
        . 'Foram debitadas ' . number_format($preco, 0, ',', '.') . ' moedas.'
    );
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Violação de chave única: token de compra já utilizado
    if ($e->getCode() === '23000') {
        mensagem('warning', 'Esta compra já foi processada anteriormente.');
    } else {
        error_log('Erro ao comprar pacote: ' . $e->getMessage());
        mensagem('danger', 'Não foi possível concluir a compra. Tente novamente.');
    }
}

header('Location: ../loja.php');
exit;
