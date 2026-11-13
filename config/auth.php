<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function usuarioLogado(): bool {
    return isset($_SESSION['usuario_id']);
}

function exigirLogin(): void {
    if (!usuarioLogado()) {
        header("Location: $destino");
        exit;
    }
}

function usuarioAdmin(PDO $pdo): bool {
    if (!usuarioLogado()) {
        return false;
    }

    $stmt = $pdo->prepare(
        "SELECT tipo
        FROM usuarios
        WHERE id = ?
        LIMIT 1"
    );

        $stmt->execute([(int) $_SESSION['usuario_id']]);

        $tipo = $stmt->fetchColumn();

        return $tipo === 'admin';
}

function exigirAdmin(
    PDO $pdo,
    string $destinoLogin = '../login.php'
) : void {

    exigirLogin($destinoLogin);

    if(!usuarioAdmin($pdo)) {

    http_response_code(403);

    exit('
    <h1>Acesso Negado!</h1>
    <p>Você não possui permissão para acessar está área.</p>
    <a href="../dashboard.php">Voltar ao Cardverse</a>
    ');
    }
}

function tokenCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarCsrf(): void {
    if (!isset($_POST['csrf_token']) || !hash_equals(tokenCsrf(), (string)$_POST['csrf_token'])) {
        http_response_code(403);
        exit('Solicitação inválida. Atualize a página e tente novamente.');
    }
}

function mensagem(string $tipo, string $texto): void {
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

function pegarMensagem(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
