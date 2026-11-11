<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../cadastro.php');
    exit;
}
validarCsrf();

$nome = trim((string)($_POST['nome'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$senha = (string)($_POST['senha'] ?? '');
$confirmacao = (string)($_POST['confirmacao'] ?? '');

if (mb_strlen($nome) < 3 || mb_strlen($nome) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    mensagem('danger', 'Informe um nome (3 a 100 caracteres) e um e-mail válido.');
    header('Location: ../cadastro.php'); exit;
}
if (strlen($senha) < 8 || $senha !== $confirmacao) {
    mensagem('danger', 'A senha deve ter pelo menos 8 caracteres e coincidir com a confirmação.');
    header('Location: ../cadastro.php'); exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, moedas, tipo, criado_em) VALUES (?, ?, ?, 500, 'usuario', NOW())");
    $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);
    mensagem('success', 'Conta criada! Entre com seu e-mail e senha.');
    header('Location: ../login.php'); exit;
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        mensagem('danger', 'Este e-mail já está cadastrado.');
    } else {
        error_log($e->getMessage());
        mensagem('danger', 'Não foi possível concluir o cadastro.');
    }
    header('Location: ../cadastro.php'); exit;
}
