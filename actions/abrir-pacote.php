<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../classes/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../abrir-pacote.php');
    exit;
}

validarCsrf();

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if ($usuarioId <= 0) {
    header('Location: ../login.php');
    exit;
}

$compraId = filter_var($_POST['compra_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

$tokenRecebido = (string)($_POST['abertura_token'] ?? '');
$tokens = $_SESSION['abertura_tokens'] ?? [];
$tokens = is_array($tokens) ? $tokens : [];

if ($compraId === false) {
    mensagem('danger', 'Pacote inválido. Tente novamente.');
    header('Location: ../abrir-pacote.php');
    exit;
}

// Token de uso único: bloqueia duplo clique, refresh e reenvio do formulário.
$tokenSessao = (string)($tokens[$compraId] ?? '');

if ($tokenSessao === '' || $tokenRecebido === '' || !hash_equals($tokenSessao, $tokenRecebido)) {
    mensagem('warning', 'Esta abertura já foi processada ou expirou. Abra o pacote novamente pelo painel.');
    header('Location: ../abrir-pacote.php');
    exit;
}

$aberturasModel = new Abertura($pdo);

try {
    $resultado = $aberturasModel->abrir($usuarioId, $compraId, $tokenRecebido);

    unset($tokens[$compraId]);
    $_SESSION['abertura_tokens'] = $tokens;

    $total = count($resultado['cartas']);

    mensagem(
        'success',
        'Pacote "' . $resultado['pacote']['nome'] . '" aberto! '
        . 'Você recebeu ' . $total . ' ' . ($total === 1 ? 'carta' : 'cartas') . ' e elas já estão na sua coleção.'
    );

    header('Location: ../abrir-pacote.php?abertura=' . $resultado['abertura_id']);
    exit;
} catch (AberturaException $e) {
    switch ($e->getCode()) {
        case AberturaException::COMPRA_JA_ABERTA:
            unset($tokens[$compraId]);
            $_SESSION['abertura_tokens'] = $tokens;

            $aberturaId = $e->aberturaId() ?? $aberturasModel->aberturaDaCompra($usuarioId, $compraId);

            mensagem('warning', 'Este pacote já foi aberto. Confira as cartas recebidas abaixo.');

            if ($aberturaId !== null) {
                header('Location: ../abrir-pacote.php?abertura=' . $aberturaId);
                exit;
            }
            break;

        case AberturaException::COMPRA_NAO_ENCONTRADA:
            unset($tokens[$compraId]);
            $_SESSION['abertura_tokens'] = $tokens;
            mensagem('danger', $e->getMessage());
            break;

        case AberturaException::SEM_CARTAS:
            mensagem('danger', $e->getMessage());
            break;

        default:
            error_log(
                'Erro ao abrir pacote: ' . $e->getMessage()
                . ' | ' . ($e->getPrevious()?->getMessage() ?? '')
            );
            mensagem('danger', 'Não foi possível abrir o pacote. Tente novamente.');
    }
} catch (InvalidArgumentException $e) {
    mensagem('danger', $e->getMessage());
}

header('Location: ../abrir-pacote.php');
exit;
