<?php
require_once __DIR__.'/config/auth.php';exigirLogin();
require_once __DIR__.'/config/conexao.php';require_once __DIR__.'/classes/autoload.php';
$uid=(int)$_SESSION['usuario_id'];$usuario=(new Usuario($pdo))->buscarPorId($uid);exigirSessaoValida($usuario);
$model=new Troca($pdo);$jogadores=$model->jogadores($uid);$trocas=$model->listar($uid);
$cartas=(new Carta($pdo))->listarColecao($uid);$catalogo=(new Carta($pdo))->listarCatalogo();
// Cartas especiais só são exibidas para quem já possui uma carta especial.
$minhasEspeciais = array_values(array_filter($cartas, function($c) use ($pdo) {
    static $stmt = null;
    if ($stmt === null) $stmt = $pdo->prepare("SELECT tipo_especial FROM cartas WHERE id=?");
    $stmt->execute([(int)$c['id']]);
    return $stmt->fetchColumn() !== 'normal';
}));
if ($minhasEspeciais) {
    $s = $pdo->prepare("SELECT c.id,c.nome FROM cartas c JOIN colecao co ON co.carta_id=c.id WHERE co.usuario_id=? AND c.tipo_especial IN ('secreta','exclusiva')");
    // Only reveal the target player's special cards when selecting that player.
    $especiaisPorJogador = [];
} else { $especiaisPorJogador=[]; }

$flash=pegarMensagem();function e($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Trocas | CardVerse</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"><link href="assets/css/style.css" rel="stylesheet"></head><body>
<?php include __DIR__.'/includes/header.php'; ?>
<main class="container py-5"><h1><i class="bi bi-arrow-left-right me-2"></i>Trocas de cartas</h1><p class="muted">Proponha a troca de uma carta sua por uma carta de outro jogador. Cada troca movimenta uma unidade de cada carta somente após a aceitação.</p>
<?php if($flash):?><div class="alert alert-<?=e($flash['tipo'])?>"><?=e($flash['texto'])?></div><?php endif;?>
<section class="card bg-dark text-light border-secondary p-4 mb-4"><h2 class="h4">Nova proposta</h2>
<?php if(!$jogadores || !$cartas):?><p>Você precisa possuir pelo menos uma carta e ter outro jogador cadastrado para propor trocas.</p><?php else:?>
<form method="post" action="actions/propor-troca.php" class="row g-3"><input type="hidden" name="csrf_token" value="<?=e(tokenCsrf())?>">
<div class="col-md-4"><label class="form-label" for="dest">Jogador destinatário</label><select class="form-select" id="dest" name="destinatario_id" required><option value="">Selecione</option><?php foreach($jogadores as $j):?><option value="<?= (int)$j['id']?>"><?=e($j['nome'])?></option><?php endforeach;?></select></div>
<div class="col-md-4"><label class="form-label" for="of">Sua carta oferecida</label><select class="form-select" id="of" name="carta_oferecida_id" required><option value="">Selecione</option><?php foreach($cartas as $c):?><option data-tipo="<?=in_array((int)$c['id'],array_map('intval',array_column($minhasEspeciais,'id')),true)?'especial':'normal'?>" value="<?=(int)$c['id']?>"><?=e($c['nome'])?> (<?= (int)$c['quantidade']?>)</option><?php endforeach;?></select></div>
<div class="col-md-4"><label class="form-label" for="sol">Carta desejada</label><select class="form-select" id="sol" name="carta_solicitada_id" required><option value="">Selecione</option><?php foreach($catalogo as $c):?><option data-tipo="normal" value="<?=(int)$c['id']?>"><?=e($c['nome'])?></option><?php endforeach;?></select><small class="muted">A proposta só será enviada se o jogador possuir essa carta.</small></div>
<div class="col-12"><button class="btn btn-warning" type="submit">Enviar proposta</button></div></form><?php endif;?></section>
<h2 class="h4">Propostas enviadas e recebidas</h2><?php if(!$trocas):?><p class="muted">Nenhuma troca registrada.</p><?php endif;?>
<div class="row g-3"><?php foreach($trocas as $t):$recebida=(int)$t['destinatario_id']===$uid;?><div class="col-lg-6"><div class="card bg-dark text-light border-secondary p-3 h-100"><div class="d-flex justify-content-between"><strong><?= $recebida?'Recebida de '.e($t['remetente']):'Enviada para '.e($t['destinatario'])?></strong><span class="badge bg-secondary"><?=e(ucfirst($t['status']))?></span></div><p class="my-3"><?=e($t['oferecida'])?> <i class="bi bi-arrow-left-right"></i> <?=e($t['solicitada'])?></p>
<?php if($t['status']==='pendente'):?><div class="d-flex gap-2"><?php foreach(($recebida?['aceitar'=>'Aceitar','recusar'=>'Recusar']:['cancelar'=>'Cancelar']) as $acao=>$rotulo):?><form action="actions/responder-troca.php" method="post"><input type="hidden" name="csrf_token" value="<?=e(tokenCsrf())?>"><input type="hidden" name="id" value="<?=(int)$t['id']?>"><input type="hidden" name="acao" value="<?=e($acao)?>"><button class="btn btn-sm <?= $acao==='aceitar'?'btn-success':'btn-outline-light' ?>" type="submit"><?=e($rotulo)?></button></form><?php endforeach;?></div><?php endif;?></div></div><?php endforeach;?></div></main><script>
const destino=document.getElementById('dest'),oferta=document.getElementById('of'),pedido=document.getElementById('sol');
async function atualizarCartas(){
 if(!destino||!oferta||!pedido)return;
 const id=destino.value, tipo=oferta.selectedOptions[0]?.dataset.tipo||'normal';
 pedido.replaceChildren(new Option('Selecione',''));
 if(!id)return;
 try{const r=await fetch('actions/cartas-troca.php?destinatario_id='+encodeURIComponent(id)+'&tipo='+tipo,{credentials:'same-origin'});
 if(!r.ok)return;const cartas=await r.json();for(const c of cartas)pedido.add(new Option(c.nome,c.id));}catch(e){}
}
destino?.addEventListener('change',atualizarCartas);oferta?.addEventListener('change',atualizarCartas);
</script></body></html>
