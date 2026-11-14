
<?php

class Missao
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }


    // Calcula o período atual das missões
    private function obterPeriodo(string $tipo): array
    {
        $fuso = new DateTimeZone('America/Sao_Paulo');

        $agora = new DateTimeImmutable('now', $fuso);


        // MISSÕES DIÁRIAS
        if ($tipo === 'diaria') {

            // Define hoje às 22h como referência
            $inicio = $agora->setTime(22, 0, 0);

            // Se ainda não chegou às 22h,
            // considera o período iniciado ontem
            if ($agora < $inicio) {
                $inicio = $inicio->modify('-1 day');
            }

            // O período termina 24 horas depois
            $fim = $inicio->modify('+1 day');


        // MISSÕES SEMANAIS
        } elseif ($tipo === 'semanal') {

            // Descobre o dia da semana
            // Domingo = 0, segunda = 1, etc.
            $diaSemana = (int) $agora->format('w');

            // Calcula o último domingo às 22h
            $inicio = $agora
                ->modify("-{$diaSemana} days")
                ->setTime(22, 0, 0);

            // Se ainda não chegou ao domingo às 22h,
            // utiliza o domingo anterior
            if ($agora < $inicio) {
                $inicio = $inicio->modify('-7 days');
            }

            // O período termina sete dias depois
            $fim = $inicio->modify('+7 days');


        } else {

            throw new InvalidArgumentException(
                'Tipo de missão inválido.'
            );

        }


        return [
            'inicio' => $inicio->format('Y-m-d H:i:s'),
            'fim' => $fim->format('Y-m-d H:i:s')
        ];
    }


    // Sorteia e atribui missões ao jogador
    private function gerarMissoes(
        int $usuarioId,
        string $tipo,
        int $quantidade,
        array $periodo
    ): void {

        // Verifica se o jogador já recebeu missões neste período
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM usuarios_missoes um

            INNER JOIN missoes m
                ON m.id = um.missao_id

            WHERE um.usuario_id = ?
              AND m.tipo = ?
              AND um.periodo_inicio = ?
              AND um.periodo_fim = ?
        ");

        $stmt->execute([
            $usuarioId,
            $tipo,
            $periodo['inicio'],
            $periodo['fim']
        ]);

        $totalExistente = (int) $stmt->fetchColumn();


        // Se já existem missões, não sorteia novamente
        if ($totalExistente > 0) {
            return;
        }


        // Busca as missões ativas do tipo solicitado
        $stmt = $this->pdo->prepare("
            SELECT id
            FROM missoes
            WHERE tipo = ?
              AND ativa = TRUE
        ");

        $stmt->execute([$tipo]);

        $missoesDisponiveis = array_map(
            'intval',
            $stmt->fetchAll(PDO::FETCH_COLUMN)
        );


        // Verifica se existe alguma missão cadastrada
        if (empty($missoesDisponiveis)) {
            return;
        }


        // Prepara a inserção das missões
        $stmtInserir = $this->pdo->prepare("
            INSERT INTO usuarios_missoes
            (
                usuario_id,
                missao_id,
                periodo_inicio,
                periodo_fim
            )
            VALUES (?, ?, ?, ?)
        ");


        // Inicia uma transação
        $this->pdo->beginTransaction();

        try {

            // Repete o sorteio conforme a quantidade desejada
            for ($i = 0; $i < $quantidade; $i++) {

                // Escolhe uma posição aleatória do array
                $indice = random_int(
                    0,
                    count($missoesDisponiveis) - 1
                );

                // Obtém o ID da missão sorteada
                $missaoId = $missoesDisponiveis[$indice];


                // Registra a missão para o jogador
                $stmtInserir->execute([
                    $usuarioId,
                    $missaoId,
                    $periodo['inicio'],
                    $periodo['fim']
                ]);

            }

            // Confirma todas as inserções
            $this->pdo->commit();

        } catch (Throwable $e) {

            // Desfaz as inserções se ocorrer algum erro
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;

        }
    }


    // Busca as missões de um determinado tipo
    private function listarMissoes(
        int $usuarioId,
        string $tipo,
        int $quantidade
    ): array {

        // Descobre o período atual
        $periodo = $this->obterPeriodo($tipo);


        // Gera as missões caso ainda não existam
        $this->gerarMissoes(
            $usuarioId,
            $tipo,
            $quantidade,
            $periodo
        );


        // Consulta as missões atribuídas
        $stmt = $this->pdo->prepare("
            SELECT
                um.id AS usuario_missao_id,
                m.id AS missao_id,
                m.nome,
                m.descricao,
                m.icone,
                m.quantidade_necessaria,
                m.recompensa_moedas,
                m.recompensa_pontos,
                m.recompensa_xp,
                um.progresso,
                um.periodo_inicio,
                um.periodo_fim,
                um.concluida_em,
                um.resgatada_em

            FROM usuarios_missoes um

            INNER JOIN missoes m
                ON m.id = um.missao_id

            WHERE um.usuario_id = ?
              AND m.tipo = ?
              AND um.periodo_inicio = ?
              AND um.periodo_fim = ?

            ORDER BY um.id ASC
        ");

        $stmt->execute([
            $usuarioId,
            $tipo,
            $periodo['inicio'],
            $periodo['fim']
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Retorna duas missões diárias
    public function listarDiarias(int $usuarioId): array
    {
        return $this->listarMissoes(
            $usuarioId,
            'diaria',
            3
        );
    }


    // Retorna quatro missões semanais
    public function listarSemanais(int $usuarioId): array
    {
        return $this->listarMissoes(
            $usuarioId,
            'semanal',
            4
        );
    }



    // Registra o progresso das missões do jogador
    public function registrarProgresso(
        int $usuarioId,
        string $criterio,
        int $quantidade = 1
    ): void {

        if ($usuarioId <= 0 || $quantidade <= 0) {
            return;
        }

        // Obtém o horário atual de São Paulo
        $fuso = new DateTimeZone('America/Sao_Paulo');

        $agora = new DateTimeImmutable('now', $fuso);

        $dataHora = $agora->format('Y-m-d H:i:s');


        // Verifica se já existe uma transação em andamento
        $transacaoPropria = !$this->pdo->inTransaction();

        if ($transacaoPropria) {
            $this->pdo->beginTransaction();
        }

        try {

            // Atualiza o progresso de todas as missões
            // correspondentes ao critério informado
            $stmt = $this->pdo->prepare("
                UPDATE usuarios_missoes um

                INNER JOIN missoes m
                    ON m.id = um.missao_id

                SET um.progresso = LEAST(
                    um.progresso + ?,
                    m.quantidade_necessaria
                )

                WHERE um.usuario_id = ?
                AND m.criterio = ?
                AND um.periodo_inicio <= ?
                AND um.periodo_fim > ?
                AND um.progresso < m.quantidade_necessaria
                AND um.concluida_em IS NULL
            ");

            $stmt->execute([
                $quantidade,
                $usuarioId,
                $criterio,
                $dataHora,
                $dataHora
            ]);


            // Identifica as missões que acabaram de ser concluídas
            $stmt = $this->pdo->prepare("
                UPDATE usuarios_missoes um

                INNER JOIN missoes m
                    ON m.id = um.missao_id

                SET um.concluida_em = ?

                WHERE um.usuario_id = ?
                AND m.criterio = ?
                AND um.periodo_inicio <= ?
                AND um.periodo_fim > ?
                AND um.progresso >= m.quantidade_necessaria
                AND um.concluida_em IS NULL
            ");

            $stmt->execute([
                $dataHora,
                $usuarioId,
                $criterio,
                $dataHora,
                $dataHora
            ]);


            if ($transacaoPropria) {
                $this->pdo->commit();
            }

        } catch (Throwable $e) {

            if ($transacaoPropria && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }


    // Resgata a recompensa de uma missão concluída
    public function resgatarRecompensa(
        int $usuarioId,
        int $usuarioMissaoId
    ): array {

        if ($usuarioId <= 0 || $usuarioMissaoId <= 0) {
            throw new InvalidArgumentException(
                'Dados da missão inválidos.'
            );
        }

        // Verifica se já existe uma transação em andamento
        $transacaoPropria = !$this->pdo->inTransaction();

        if ($transacaoPropria) {
            $this->pdo->beginTransaction();
        }

        try {

            // Busca a missão e bloqueia seu registro durante o resgate
            $stmt = $this->pdo->prepare("
                SELECT
                    um.id,
                    um.progresso,
                    um.concluida_em,
                    um.resgatada_em,
                    m.quantidade_necessaria,
                    m.recompensa_moedas,
                    m.recompensa_pontos,
                    m.recompensa_xp

                FROM usuarios_missoes um

                INNER JOIN missoes m
                    ON m.id = um.missao_id

                WHERE um.id = ?
                  AND um.usuario_id = ?

                FOR UPDATE
            ");

            $stmt->execute([
                $usuarioMissaoId,
                $usuarioId
            ]);

            $missao = $stmt->fetch(PDO::FETCH_ASSOC);


            // Verifica se a missão pertence ao jogador
            if (!$missao) {
                throw new DomainException(
                    'Missão não encontrada.'
                );
            }


            // Verifica se a missão foi concluída
            if (
                empty($missao['concluida_em']) ||
                (int) $missao['progresso'] <
                (int) $missao['quantidade_necessaria']
            ) {
                throw new DomainException(
                    'Você ainda não concluiu esta missão.'
                );
            }


            // Impede o resgate duplicado
            if (!empty($missao['resgatada_em'])) {
                throw new DomainException(
                    'Você já resgatou a recompensa desta missão.'
                );
            }


            // Recupera os valores das recompensas
            $moedas = (int) $missao['recompensa_moedas'];
            $pontos = (int) $missao['recompensa_pontos'];
            $xp = (int) $missao['recompensa_xp'];


            // Adiciona as recompensas à conta do jogador
            $stmt = $this->pdo->prepare("
                UPDATE usuarios
                SET
                    moedas = moedas + ?,
                    pontos = pontos + ?,
                    experiencia = experiencia + ?
                WHERE id = ?
            ");

            $stmt->execute([
                $moedas,
                $pontos,
                $xp,
                $usuarioId
            ]);


            // Registra quando a recompensa foi resgatada
            $fuso = new DateTimeZone('America/Sao_Paulo');

            $agora = new DateTimeImmutable('now', $fuso);

            $dataResgate = $agora->format('Y-m-d H:i:s');

            $stmt = $this->pdo->prepare("
                UPDATE usuarios_missoes
                SET resgatada_em = ?
                WHERE id = ?
                  AND usuario_id = ?
                  AND resgatada_em IS NULL
            ");

            $stmt->execute([
                $dataResgate,
                $usuarioMissaoId,
                $usuarioId
            ]);


            if ($stmt->rowCount() !== 1) {
                throw new DomainException(
                    'Esta recompensa já foi resgatada.'
                );
            }


            // Confirma a entrega das recompensas
            if ($transacaoPropria) {
                $this->pdo->commit();
            }


            return [
                'moedas' => $moedas,
                'pontos' => $pontos,
                'xp' => $xp
            ];

        } catch (Throwable $e) {

            // Desfaz as alterações caso ocorra algum erro
            if (
                $transacaoPropria &&
                $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }


}