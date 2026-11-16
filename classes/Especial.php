<?php
/** Regras de cartas especiais; exige migração SQL instalada. */
class Especial {
 public function __construct(private PDO $pdo) {}
 public function tipo(int $id): string {
  $s=$this->pdo->prepare('SELECT tipo_especial FROM cartas WHERE id=?');$s->execute([$id]);return (string)$s->fetchColumn();
 }
 public function descobrir(int $usuario,int $carta): void {
  if ($this->tipo($carta)!=='secreta') return;
  $s=$this->pdo->prepare('INSERT IGNORE INTO descobertas_secretas(usuario_id,carta_id) VALUES(?,?)');$s->execute([$usuario,$carta]);
  $s=$this->pdo->prepare("SELECT COUNT(*) FROM descobertas_secretas d JOIN cartas c ON c.id=d.carta_id WHERE d.usuario_id=? AND c.tipo_especial='secreta'");$s->execute([$usuario]);
  $total=(int)$this->pdo->query("SELECT COUNT(*) FROM cartas WHERE tipo_especial='secreta'")->fetchColumn();
  if ($total!==3 || (int)$s->fetchColumn()!==3) return;
  $s=$this->pdo->prepare('SELECT usuario_id FROM recompensas_secretas WHERE usuario_id=? FOR UPDATE');$s->execute([$usuario]);
  if ($s->fetchColumn()) return;
  $recompensas=$this->pdo->query("SELECT id FROM cartas WHERE tipo_especial='exclusiva' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
  if(count($recompensas)!==2) throw new RuntimeException('Cadastre as duas recompensas exclusivas.');
  $premio=(int)$recompensas[random_int(0,1)];
  $s=$this->pdo->prepare('INSERT INTO recompensas_secretas(usuario_id,carta_id) VALUES(?,?)');$s->execute([$usuario,$premio]);
  $s=$this->pdo->prepare('INSERT INTO colecao(usuario_id,carta_id,quantidade) VALUES(?,?,1) ON DUPLICATE KEY UPDATE quantidade=quantidade+1');$s->execute([$usuario,$premio]);
 }
 public function sorteada(): ?array {
  if(random_int(1,1000)!==1) return null; // 0,10% por pacote
  $cartas=$this->pdo->query("SELECT id,nome,imagem,raridade,ataque,defesa,tipo_especial FROM cartas WHERE tipo_especial='secreta' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
  if(count($cartas)!==3) throw new RuntimeException('As três cartas secretas precisam estar cadastradas.');
  return $cartas[random_int(0,2)];
 }
}
