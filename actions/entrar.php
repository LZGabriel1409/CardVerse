<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../classes/autoload.php';

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

$usuarios = new Usuario($pdo);
$usuario = $usuarios->autenticar($email, $senha);
if (!$usuario) {
    mensagem('danger', 'E-mail ou senha inválidos.');
    header('Location: ../login.php'); exit;
}
session_regenerate_id(true);
$_SESSION['usuario_id'] = (int)$usuario['id'];
$_SESSION['usuario_nome'] = $usuario['nome'];
header('Location: ../dashboard.php');
exit;
