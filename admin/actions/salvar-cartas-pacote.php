
<?php
/**
 * Salva as cartas disponíveis em determinado pacote.
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

$pacoteId = filter_var(
    $_POST['pacote_id'] ?? null,
    FILTER_VALIDATE_INT
);

$cartas = $_POST['cartas'] ?? [];

// ==========================================
// VALIDAÇÃO DO PACOTE
// ==========================================

if ($pacoteId === false || $pacoteId === null || $pacoteId <= 0) {
    mensagem('danger', 'Identificador do pacote inválido.');
    header('Location: ../pacotes.php');
    exit;
}

$destino = '../pacote-cartas.php?id=' . $pacoteId;

// Confirma se o pacote existe.
$stmt = $pdo->prepare(
    'SELECT id FROM pacotes WHERE id = ? LIMIT 1'
);

$stmt->execute([$pacoteId]);

if (!$stmt->fetch()) {
    mensagem('danger', 'Pacote não encontrado.');
    header('Location: ../pacotes.php');
    exit;
}

// ==========================================
// VALIDAÇÃO DAS CARTAS SELECIONADAS
// ==========================================

// Impede o envio de dados que não sejam uma lista.
if (!is_array($cartas)) {
    mensagem('danger', 'A seleção de cartas é inválida.');
    header("Location: $destino");
    exit;
}

// Exige pelo menos uma carta selecionada.
if (empty($cartas)) {
    mensagem('warning', 'Selecione pelo menos uma carta para o pacote.');
    header("Location: $destino");
    exit;
}

// Valida cada identificador recebido.
$cartasIds = [];

foreach ($cartas as $cartaId) {

    $cartaId = filter_var($cartaId, FILTER_VALIDATE_INT);

    if ($cartaId === false || $cartaId <= 0) {
        mensagem('danger', 'Uma das cartas selecionadas possui ID inválido.');
        header("Location: $destino");
        exit;
    }

    $cartasIds[] = $cartaId;
}

// Remove possíveis IDs repetidos.
$cartasIds = array_values(array_unique($cartasIds));

// ==========================================
// CONFIRMAÇÃO DAS CARTAS NO BANCO
// ==========================================

try {

    // Cria os marcadores necessários para a consulta.
    $marcadores = implode(
        ',',
        array_fill(0, count($cartasIds), '?')
    );

    $sql = "
        SELECT id
        FROM cartas
        WHERE id IN ($marcadores)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($cartasIds);

    $cartasExistentes = array_map(
        'intval',
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    );

    // Verifica se todos os IDs enviados existem.
    if (count($cartasExistentes) !== count($cartasIds)) {
        mensagem('danger', 'Uma ou mais cartas selecionadas não existem.');
        header("Location: $destino");
        exit;
    }

    // ======================================
    // ATUALIZAÇÃO DAS RELAÇÕES
    // ======================================

    $pdo->beginTransaction();

    // Remove as relações anteriores do pacote.
    $stmt = $pdo->prepare(
        'DELETE FROM pacote_cartas WHERE pacote_id = ?'
    );

    $stmt->execute([$pacoteId]);

    // Insere novamente as cartas selecionadas.
    $stmt = $pdo->prepare(
        'INSERT INTO pacote_cartas (pacote_id, carta_id)
         VALUES (?, ?)'
    );

    foreach ($cartasIds as $cartaId) {
        $stmt->execute([
            $pacoteId,
            $cartaId
        ]);
    }

    // Confirma todas as alterações.
    $pdo->commit();

    mensagem('success', 'Cartas do pacote atualizadas com sucesso!');

   header("Location: $destino");
    exit;

} catch (PDOException $e) {

    // Desfaz as alterações caso algo dê errado.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    mensagem('danger', 'Ocorreu um erro ao salvar as cartas do pacote.');

    header("Location: $destino");
    exit;
}
