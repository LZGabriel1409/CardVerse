<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../classes/autoload.php';

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

$usuarios = new Usuario($pdo);

try {
    if ($usuarios->emailExiste($email)) {
        mensagem('danger', 'Este e-mail já está cadastrado.');
        header('Location: ../cadastro.php'); exit;
    }

    $usuarios->criar($nome, $email, $senha);
    mensagem('success', 'Conta criada! Entre com seu e-mail e senha.');
    header('Location: ../login.php'); exit;
} catch (InvalidArgumentException $e) {
    mensagem('danger', $e->getMessage());
    header('Location: ../cadastro.php'); exit;
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        mensagem('danger', 'Este e-mail já está cadastrado.');
    } else {
        error_log($e->getMessage());
        mensagem('danger', 'Não foi possível concluir o cadastro.');
    }
    header('Location: ../cadastro.php'); exit;
}
