
<?php
// ==========================================
// CONFIGURAÇÕES E AUTENTICAÇÃO
// ==========================================

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/conexao.php';

// Impede o acesso de usuários que não são administradores
exigirAdmin($pdo, '../../login.php');

// ==========================================
// VALIDAÇÃO DA REQUISIÇÃO
// ==========================================

// Aceita somente requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pacotes.php');
    exit;
}

// Valida o token CSRF
validarCsrf();

// ==========================================
// RECEBIMENTO DOS DADOS
// ==========================================

$pacoteId = filter_input(INPUT_POST, 'pacote_id', FILTER_VALIDATE_INT);

$pesos = [
    'comum' => filter_input(INPUT_POST, 'peso_comum', FILTER_VALIDATE_INT),
    'rara' => filter_input(INPUT_POST, 'peso_rara', FILTER_VALIDATE_INT),
    'epica' => filter_input(INPUT_POST, 'peso_epica', FILTER_VALIDATE_INT)
];

// ==========================================
// VALIDAÇÃO DOS VALORES
// ==========================================

if (!$pacoteId || $pacoteId <= 0) {
    mensagem('danger', 'Pacote inválido.');
    header('Location: ../pacotes.php');
    exit;
}

foreach ($pesos as $raridade => $peso) {
    if ($peso === false || $peso === null || $peso < 0 || $peso > 100) {
        mensagem('danger', 'Informe valores válidos entre 0 e 100.');
        header('Location: ../pacote-chances.php?id=' . $pacoteId);
        exit;
    }
}

// Verifica se a soma das chances é exatamente 100
$total = array_sum($pesos);

if ($total !== 100) {
    mensagem('danger', 'A soma das chances precisa ser exatamente 100%.');
    header('Location: ../pacote-chances.php?id=' . $pacoteId);
    exit;
}

// ==========================================
// VERIFICA SE O PACOTE EXISTE
// ==========================================

try {

    $stmt = $pdo->prepare(
        "SELECT id FROM pacotes WHERE id = :id"
    );

    $stmt->execute([':id' => $pacoteId]);

    if (!$stmt->fetch()) {
        mensagem('danger', 'Pacote não encontrado.');
        header('Location: ../pacotes.php');
        exit;
    }

    // ==========================================
    // SALVAMENTO DAS CHANCES
    // ==========================================

    $pdo->beginTransaction();

    $sql = "INSERT INTO pacote_chances (pacote_id, raridade, peso)
            VALUES (:pacote_id, :raridade, :peso)
            ON DUPLICATE KEY UPDATE peso = VALUES(peso)";

    $stmt = $pdo->prepare($sql);

    // Salva as três raridades configuráveis
    foreach ($pesos as $raridade => $peso) {

        $stmt->execute([
            ':pacote_id' => $pacoteId,
            ':raridade' => $raridade,
            ':peso' => $peso
        ]);
    }

    // A chance lendária é configurada separadamente
    // pelo campo chance_lendaria da tabela pacotes.
    // Mantemos o peso lendário em zero para evitar duplicidade.

    $stmt->execute([
        ':pacote_id' => $pacoteId,
        ':raridade' => 'lendaria',
        ':peso' => 0
    ]);

    $pdo->commit();

    mensagem('success', 'Chances do pacote atualizadas com sucesso.');

    header('Location: ../pacote-chances.php?id=' . $pacoteId);
    exit;

} catch (PDOException $e) {

    // Desfaz alterações caso ocorra algum erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    mensagem('danger', 'Erro ao salvar as chances do pacote.');

    header('Location: ../pacote-chances.php?id=' . $pacoteId);
    exit;
}
