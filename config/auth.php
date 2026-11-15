<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function usuarioLogado(): bool {
    return isset($_SESSION['usuario_id']) && (int)$_SESSION['usuario_id'] > 0;
}

function exigirLogin(string $destino = 'login.php'): void {
    if (!usuarioLogado()) {
        header('Location: ' . $destino);
        exit;
    }
}

/**
 * Descarta os dados de autenticação da sessão, preservando o restante
 * (flash, CSRF e tokens de uso único). Usado quando o usuário da sessão
 * não existe mais no banco.
 */
function limparSessaoUsuario(): void {
    unset($_SESSION['usuario_id'], $_SESSION['usuario_nome']);

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Encerra a sessão por completo, incluindo o cookie do navegador.
 */
function encerrarSessao(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Garante que a sessão aponta para um usuário existente.
 * Sessões órfãs (usuário removido do banco) são encerradas aqui,
 * sem passar por logout.php.
 */
function exigirSessaoValida(?array $usuario, string $destino = 'login.php'): void {
    if ($usuario !== null) {
        return;
    }

    limparSessaoUsuario();
    mensagem('warning', 'Sua sessão expirou. Entre novamente para continuar.');
    header('Location: ' . $destino);
    exit;
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
