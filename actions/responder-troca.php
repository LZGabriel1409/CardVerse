<?php
require_once __DIR__.'/../config/auth.php';exigirLogin('../login.php');
require_once __DIR__.'/../config/conexao.php';require_once __DIR__.'/../classes/autoload.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') {header('Location: ../trocas.php');exit;}
validarCsrf();
try {
    $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
    if (!$id) throw new InvalidArgumentException('Troca inválida.');
    (new Troca($pdo))->responder((int)$id,(int)$_SESSION['usuario_id'],(string)($_POST['acao']??''));
    mensagem('success','Troca atualizada com sucesso.');
} catch (InvalidArgumentException|RuntimeException $e) {mensagem('danger',$e->getMessage());}
catch (Throwable $e) {error_log($e->getMessage());mensagem('danger','Não foi possível concluir a troca.');}
header('Location: ../trocas.php');exit;
