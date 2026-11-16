<?php
require_once __DIR__.'/../config/auth.php';exigirLogin('../login.php');
require_once __DIR__.'/../config/conexao.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') {header('Location: ../perfil.php');exit;}
validarCsrf();
$nome=(string)($_POST['avatar']??'');
if (!preg_match('/\A[a-zA-Z0-9_-]+\.(?:png|jpg|jpeg|webp)\z/i',$nome) || !is_file(__DIR__.'/../assets/img/avatares/'.$nome)) {
    mensagem('danger','Avatar inválido.');
} else {
    $caminho='assets/img/avatares/'.$nome;
    $s=$pdo->prepare('UPDATE usuarios SET avatar=? WHERE id=?');$s->execute([$caminho,(int)$_SESSION['usuario_id']]);
    mensagem('success','Avatar atualizado!');
}
header('Location: ../perfil.php');exit;
