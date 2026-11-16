
<?php
/**
 * Exclui uma carta do catálogo.
 */

// Dependências.
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/conexao.php';

// Somente administradores podem executar esta ação.
exigirAdmin($pdo, '../../login.php');

// Aceita somente requisições POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../cartas.php');
    exit;
}

// Valida o token CSRF.
validarCsrf();

// Recebe e valida o ID.
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id <= 0) {
    mensagem('danger', 'Identificador da carta inválido.');
    header('Location: ../cartas.php');
    exit;
}

try {

    // Verifica se a carta existe.
    $stmt = $pdo->prepare(
        'SELECT id FROM cartas WHERE id = ? LIMIT 1'
    );

    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        mensagem('danger', 'Carta não encontrada.');
        header('Location: ../cartas.php');
        exit;
    }

    // Tenta excluir a carta.
    $stmt = $pdo->prepare(
        'DELETE FROM cartas WHERE id = ?'
    );

    $stmt->execute([$id]);

    mensagem('success', 'Carta excluída com sucesso!');

} catch (PDOException $e) {

    // SQLSTATE 23000: violação de integridade referencial.
    if ($e->getCode() === '23000') {

        mensagem(
            'warning',
            'Não é possível excluir esta carta porque ela está vinculada a uma coleção, pacote ou troca.'
        );

    } else {

        error_log($e->getMessage());

        mensagem(
            'danger',
            'Ocorreu um erro ao tentar excluir a carta.'
        );
    }
}

header('Location: ../cartas.php');
exit;
