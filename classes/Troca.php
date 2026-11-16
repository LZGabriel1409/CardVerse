<?php
/** Trocas de uma carta por outra, com movimentação atômica no banco. */
class Troca {
    public function __construct(private PDO $pdo) {}

    public function listar(int $usuarioId): array {
        $sql = "SELECT t.*, r.nome AS remetente, d.nome AS destinatario,
                       co.nome AS oferecida, cs.nome AS solicitada,
                       co.raridade AS raridade_oferecida, cs.raridade AS raridade_solicitada
                FROM trocas t JOIN usuarios r ON r.id=t.remetente_id
                JOIN usuarios d ON d.id=t.destinatario_id
                JOIN cartas co ON co.id=t.carta_oferecida_id
                JOIN cartas cs ON cs.id=t.carta_solicitada_id
                WHERE t.remetente_id=? OR t.destinatario_id=? ORDER BY t.criada_em DESC,t.id DESC";
        $s=$this->pdo->prepare($sql);$s->execute([$usuarioId,$usuarioId]);return $s->fetchAll(PDO::FETCH_ASSOC);
    }
    public function jogadores(int $id): array {
        $s=$this->pdo->prepare('SELECT id,nome FROM usuarios WHERE id<>? ORDER BY nome');
        $s->execute([$id]);return $s->fetchAll(PDO::FETCH_ASSOC);
    }
    public function propor(int $origem,int $destino,int $oferecida,int $solicitada): void {
        if ($origem<=0 || $destino<=0 || $origem===$destino || $oferecida<=0 || $solicitada<=0) throw new InvalidArgumentException('Dados da troca inválidos.');
        $this->pdo->beginTransaction();
        try {
            $s=$this->pdo->prepare('SELECT id FROM usuarios WHERE id=?');$s->execute([$destino]);
            if (!$s->fetchColumn()) throw new RuntimeException('Jogador não encontrado.');
            if ($this->quantidade($origem,$oferecida)<1) throw new RuntimeException('Você não possui a carta oferecida.');
            if ($this->quantidade($destino,$solicitada)<1) throw new RuntimeException('O destinatário não possui a carta solicitada.');
            $especial = new Especial($this->pdo);
            $tipoOf = $especial->tipo($oferecida);
            $tipoSol = $especial->tipo($solicitada);
            if (($tipoOf === 'normal') !== ($tipoSol === 'normal')) {
                throw new RuntimeException('Cartas especiais só podem ser trocadas por outras especiais.');
            }
            $s=$this->pdo->prepare('INSERT INTO trocas (remetente_id,destinatario_id,carta_oferecida_id,carta_solicitada_id) VALUES (?,?,?,?)');
            $s->execute([$origem,$destino,$oferecida,$solicitada]);$this->pdo->commit();
        } catch(Throwable $e) {$this->pdo->rollBack();throw $e;}
    }
    private function quantidade(int $usuario,int $carta): int {
        $s=$this->pdo->prepare('SELECT quantidade FROM colecao WHERE usuario_id=? AND carta_id=?');
        $s->execute([$usuario,$carta]);return (int)$s->fetchColumn();
    }
    private function retirar(int $usuario,int $carta): void {
        // A restrição CHECK do banco exige quantidade > 0.
        // Quando há somente uma unidade, excluímos o registro diretamente.
        $s=$this->pdo->prepare('DELETE FROM colecao WHERE usuario_id=? AND carta_id=? AND quantidade=1');
        $s->execute([$usuario,$carta]);
        if ($s->rowCount()===1) return;

        $s=$this->pdo->prepare('UPDATE colecao SET quantidade=quantidade-1 WHERE usuario_id=? AND carta_id=? AND quantidade>1');
        $s->execute([$usuario,$carta]);
        if ($s->rowCount()!==1) throw new RuntimeException('Uma das cartas não está mais disponível.');
    }
    private function adicionar(int $usuario,int $carta): void {
        $s=$this->pdo->prepare('INSERT INTO colecao(usuario_id,carta_id,quantidade) VALUES(?,?,1) ON DUPLICATE KEY UPDATE quantidade=quantidade+1');
        $s->execute([$usuario,$carta]);
    }
    public function responder(int $id,int $usuario,string $acao): void {
        if (!in_array($acao,['aceitar','recusar','cancelar'],true) || $id<=0) throw new InvalidArgumentException('Ação inválida.');
        $this->pdo->beginTransaction();
        try {
            $s=$this->pdo->prepare('SELECT * FROM trocas WHERE id=? FOR UPDATE');$s->execute([$id]);$t=$s->fetch(PDO::FETCH_ASSOC);
            if (!$t || $t['status']!=='pendente') throw new RuntimeException('Troca não encontrada ou já respondida.');
            $rem=(int)$t['remetente_id'];$dest=(int)$t['destinatario_id'];
            if (($acao==='cancelar' && $usuario!==$rem) || ($acao!=='cancelar' && $usuario!==$dest)) throw new RuntimeException('Você não pode realizar esta ação.');
            if ($acao==='aceitar') {
                // Bloqueia os dois inventários em ordem fixa, impedindo aceitação simultânea com saldo insuficiente.
                $s=$this->pdo->prepare('SELECT usuario_id,carta_id,quantidade FROM colecao WHERE (usuario_id=? AND carta_id=?) OR (usuario_id=? AND carta_id=?) ORDER BY usuario_id,carta_id FOR UPDATE');
                $s->execute([$rem,$t['carta_oferecida_id'],$dest,$t['carta_solicitada_id']]);
                $disponiveis=[];foreach($s->fetchAll(PDO::FETCH_ASSOC) as $c){$disponiveis[$c['usuario_id'].':'.$c['carta_id']]=(int)$c['quantidade'];}
                if (($disponiveis[$rem.':'.$t['carta_oferecida_id']]??0)<1 || ($disponiveis[$dest.':'.$t['carta_solicitada_id']]??0)<1) throw new RuntimeException('Uma das cartas não está mais disponível.');
                $especial = new Especial($this->pdo);
                $tipoOf = $especial->tipo((int)$t['carta_oferecida_id']);
                $tipoSol = $especial->tipo((int)$t['carta_solicitada_id']);
                if (($tipoOf === 'normal') !== ($tipoSol === 'normal')) throw new RuntimeException('Troca especial inválida.');
                $this->retirar($rem,(int)$t['carta_oferecida_id']);$this->retirar($dest,(int)$t['carta_solicitada_id']);
                $this->adicionar($rem,(int)$t['carta_solicitada_id']);$this->adicionar($dest,(int)$t['carta_oferecida_id']);
            }
            $status=['aceitar'=>'aceita','recusar'=>'recusada','cancelar'=>'cancelada'][$acao];
            $s=$this->pdo->prepare('UPDATE trocas SET status=? WHERE id=?');$s->execute([$status,$id]);
            $this->pdo->commit();
        } catch(Throwable $e) {$this->pdo->rollBack();throw $e;}
    }
}
