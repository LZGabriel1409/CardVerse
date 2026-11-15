<?php
/**
 * Ponto de entrada do CardVerse.
 * Encaminha para o dashboard quando a sessão é válida e para o login caso contrário.
 */
require_once __DIR__ . '/config/auth.php';

exigirLogin();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/classes/autoload.php';

$usuarios = new Usuario($pdo);

exigirSessaoValida($usuarios->buscarPorId((int)$_SESSION['usuario_id']));

header('Location: dashboard.php');
exit;
