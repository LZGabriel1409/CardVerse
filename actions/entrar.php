<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php'); exit;
}
validarCsrf();
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$senha = (string)($_POST['senha'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
    mensagem('danger', 'E-mail ou senha inválidos.');
    header('Location: ../login.php'); exit;
}

$stmt = $pdo->prepare('SELECT id, nome, senha FROM usuarios WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$usuario = $stmt->fetch();
if (!$usuario || !password_verify($senha, $usuario['senha'])) {
    mensagem('danger', 'E-mail ou senha inválidos.');
    header('Location: ../login.php'); exit;
}
session_regenerate_id(true);
$_SESSION['usuario_id'] = (int)$usuario['id'];
$_SESSION['usuario_nome'] = $usuario['nome'];
header('Location: ../dashboard.php');
exit;
