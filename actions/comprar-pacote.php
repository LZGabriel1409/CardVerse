<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../classes/autoload.php';

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

$pacotesModel = new Pacote($pdo);

try {
    $resultado = $pacotesModel->comprar($usuarioId, $pacoteId, $tokenRecebido);

    // Consome o token: o formulário não pode ser enviado novamente
    unset($_SESSION['compra_token']);

    mensagem(
        'success',
        'Compra do pacote "' . $resultado['pacote']['nome'] . '" realizada com sucesso! '
        . 'Foram debitadas ' . number_format($resultado['preco'], 0, ',', '.') . ' moedas. '
        . 'O pacote está esperando em "Abrir pacotes".'
    );
} catch (LojaException $e) {
    switch ($e->getCode()) {
        case LojaException::PACOTE_INDISPONIVEL:
        case LojaException::SALDO_INSUFICIENTE:
            mensagem('danger', $e->getMessage());
            break;
        case LojaException::COMPRA_DUPLICADA:
            mensagem('warning', $e->getMessage());
            break;
        case LojaException::USUARIO_INEXISTENTE:
            mensagem('danger', $e->getMessage());
            header('Location: ../logout.php');
            exit;
        default:
            error_log(
                'Erro ao comprar pacote: ' . $e->getMessage()
                . ' | ' . ($e->getPrevious()?->getMessage() ?? '')
            );
            mensagem('danger', 'Não foi possível concluir a compra. Tente novamente.');
    }
} catch (InvalidArgumentException $e) {
    mensagem('danger', $e->getMessage());
}

header('Location: ../loja.php');
exit;
