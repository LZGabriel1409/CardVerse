
<?php

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../classes/autoload.php';


// Aceita somente requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../perfil.php');
    exit;
}


// Valida o token de segurança
validarCsrf();


// Identifica o jogador
$usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);

if ($usuarioId <= 0) {
    header('Location: ../login.php');
    exit;
}


// Recebe o ID da missão atribuída ao jogador
$usuarioMissaoId = filter_var(
    $_POST['usuario_missao_id'] ?? null,
    FILTER_VALIDATE_INT,
    [
        'options' => ['min_range' => 1]
    ]
);


// Verifica se o ID é válido
if ($usuarioMissaoId === false) {
    mensagem('danger', 'Missão inválida.');
    header('Location: ../perfil.php');
    exit;
}


// Inicializa o modelo
$missaoModel = new Missao($pdo);


try {

    // Entrega a recompensa da missão
    $recompensa = $missaoModel->resgatarRecompensa(
        $usuarioId,
        $usuarioMissaoId
    );


    // Monta a mensagem de sucesso
    $mensagem = 'Recompensa resgatada com sucesso! ';

    $mensagem .= number_format(
        $recompensa['moedas'],
        0,
        ',',
        '.'
    ) . ' moedas, ';

    $mensagem .= number_format(
        $recompensa['pontos'],
        0,
        ',',
        '.'
    ) . ' pontos e ';

    $mensagem .= number_format(
        $recompensa['xp'],
        0,
        ',',
        '.'
    ) . ' XP recebidos.';


    mensagem('success', $mensagem);


} catch (DomainException $e) {

    mensagem('warning', $e->getMessage());

} catch (Throwable $e) {

    error_log(
        'Erro ao resgatar missão: ' . $e->getMessage()
    );

    mensagem(
        'danger',
        'Não foi possível resgatar a recompensa. Tente novamente.'
    );

}


header('Location: ../perfil.php#missoes');
exit;
