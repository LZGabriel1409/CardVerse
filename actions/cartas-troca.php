<?php
require_once __DIR__.'/../config/auth.php';
exigirLogin();
require_once __DIR__.'/../config/conexao.php';
header('Content-Type: application/json; charset=utf-8');
$uid=(int)$_SESSION['usuario_id'];
$dest=filter_input(INPUT_GET,'destinatario_id',FILTER_VALIDATE_INT);
$tipo=$_GET['tipo']??'normal';
if(!$dest || $dest===$uid || !in_array($tipo,['normal','especial'],true)){http_response_code(400);echo '[]';exit;}
if($tipo==='especial'){
 $s=$pdo->prepare("SELECT COUNT(*) FROM colecao co JOIN cartas c ON c.id=co.carta_id WHERE co.usuario_id=? AND c.tipo_especial IN ('secreta','exclusiva')");
 $s->execute([$uid]);if(!(int)$s->fetchColumn()){http_response_code(403);echo '[]';exit;}
}
$sql="SELECT c.id,c.nome FROM colecao co JOIN cartas c ON c.id=co.carta_id WHERE co.usuario_id=? AND co.quantidade>0 AND c.tipo_especial ".($tipo==='especial'?"IN ('secreta','exclusiva')":"= 'normal'")." ORDER BY c.nome";
$s=$pdo->prepare($sql);$s->execute([$dest]);
echo json_encode($s->fetchAll(PDO::FETCH_ASSOC),JSON_UNESCAPED_UNICODE);
