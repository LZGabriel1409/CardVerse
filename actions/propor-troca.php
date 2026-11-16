<?php
require_once __DIR__.'/../config/auth.php';exigirLogin('../login.php');
require_once __DIR__.'/../config/conexao.php';require_once __DIR__.'/../classes/autoload.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') {header('Location: ../trocas.php');exit;}
validarCsrf();
try {
    $ids=[];foreach(['destinatario_id','carta_oferecida_id','carta_solicitada_id'] as $campo){$n=filter_var($_POST[$campo]??null,FILTER_VALIDATE_INT);if (!$n || $n<=0) throw new InvalidArgumentException('Selecione jogador e cartas válidos.');$ids[]=(int)$n;}
    (new Troca($pdo))->propor((int)$_SESSION['usuario_id'],...$ids);
    mensagem('success','Proposta enviada!');
} catch (InvalidArgumentException|RuntimeException $e) {mensagem('danger',$e->getMessage());}
catch (Throwable $e) {error_log($e->getMessage());mensagem('danger','Não foi possível enviar a proposta.');}
header('Location: ../trocas.php');exit;
