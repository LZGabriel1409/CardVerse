<?php
/**
 * Abertura de pacotes: sorteio das cartas, persistência da abertura
 * e entrega das cartas na coleção do jogador.
 *
 * Regras:
 * - Cada compra pode ser aberta uma única vez (garantido por compra_id UNIQUE).
 * - Os pesos de raridade vêm da tabela pacote_chances.
 * - chance_lendaria é a chance de o pacote conter ao menos uma lendária.
 * - raridade_garantida promove um slot quando o sorteio não alcança a raridade.
 */
class Abertura
{
    private const ORDEM_RARIDADE = [
        'comum' => 1,
        'rara' => 2,
        'epica' => 3,
        'lendaria' => 4,
    ];

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Compras do jogador que ainda não foram abertas.
     */
    public function listarPendentes(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT c.id AS compra_id, c.data_compra, c.valor_pago,
                    p.id AS pacote_id, p.nome, p.descricao, p.selo, p.destaque,
                    p.raridade_garantida, p.chance_lendaria, p.quantidade_cartas
             FROM compras c
             INNER JOIN pacotes p ON p.id = c.pacote_id
             LEFT JOIN aberturas a ON a.compra_id = c.id
             WHERE c.usuario_id = ? AND a.id IS NULL
             ORDER BY c.data_compra ASC, c.id ASC'
        );
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll();
    }

    public function contarPendentes(int $usuarioId): int
    {
        if ($usuarioId <= 0) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM compras c
             LEFT JOIN aberturas a ON a.compra_id = c.id
             WHERE c.usuario_id = ? AND a.id IS NULL'
        );
        $stmt->execute([$usuarioId]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Busca uma compra do jogador com os dados do pacote.
     * Retorna também abertura_id quando a compra já foi aberta.
     */
    public function buscarCompra(int $usuarioId, int $compraId): ?array
    {
        if ($usuarioId <= 0 || $compraId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT c.id AS compra_id, c.data_compra, c.valor_pago,
                    p.id AS pacote_id, p.nome, p.descricao, p.selo, p.destaque,
                    p.raridade_garantida, p.chance_lendaria, p.quantidade_cartas,
                    a.id AS abertura_id
             FROM compras c
             INNER JOIN pacotes p ON p.id = c.pacote_id
             LEFT JOIN aberturas a ON a.compra_id = c.id
             WHERE c.id = ? AND c.usuario_id = ?
             LIMIT 1'
        );
        $stmt->execute([$compraId, $usuarioId]);
        $compra = $stmt->fetch();

        return $compra ?: null;
    }

    public function aberturaDaCompra(int $usuarioId, int $compraId): ?int
    {
        if ($usuarioId <= 0 || $compraId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id FROM aberturas WHERE compra_id = ? AND usuario_id = ? LIMIT 1'
        );
        $stmt->execute([$compraId, $usuarioId]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int)$id;
    }

    /**
     * Retorna uma abertura do jogador com as cartas já decodificadas.
     */
    public function buscar(int $aberturaId, int $usuarioId): ?array
    {
        if ($aberturaId <= 0 || $usuarioId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT a.id, a.compra_id, a.data_abertura, a.cartas_recebidas,
                    p.nome, p.descricao, p.selo, p.destaque
             FROM aberturas a
             INNER JOIN pacotes p ON p.id = a.pacote_id
             WHERE a.id = ? AND a.usuario_id = ?
             LIMIT 1'
        );
        $stmt->execute([$aberturaId, $usuarioId]);
        $abertura = $stmt->fetch();

        if (!$abertura) {
            return null;
        }

        $abertura['cartas'] = $this->decodificarCartas($abertura['cartas_recebidas']);
        $abertura['raridades'] = $this->resumirRaridades($abertura['cartas']);

        return $abertura;
    }

    /**
     * Aberturas mais recentes do jogador, para o histórico.
     */
    public function listarRecentes(int $usuarioId, int $limite = 6): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        $limite = max(1, min(30, $limite));

        $stmt = $this->pdo->prepare(
            'SELECT a.id, a.compra_id, a.data_abertura, a.cartas_recebidas,
                    p.nome, p.selo, p.destaque
             FROM aberturas a
             INNER JOIN pacotes p ON p.id = a.pacote_id
             WHERE a.usuario_id = ?
             ORDER BY a.data_abertura DESC, a.id DESC
             LIMIT ' . $limite
        );
        $stmt->execute([$usuarioId]);

        $aberturas = [];

        foreach ($stmt->fetchAll() as $abertura) {
            $abertura['cartas'] = $this->decodificarCartas($abertura['cartas_recebidas']);
            $abertura['raridades'] = $this->resumirRaridades($abertura['cartas']);
            $aberturas[] = $abertura;
        }

        return $aberturas;
    }

    /**
     * Pesos de raridade configurados para o pacote.
     *
     * @return array<string,int> raridade => peso
     */
    public function chancesDoPacote(int $pacoteId): array
    {
        if ($pacoteId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT raridade, peso FROM pacote_chances WHERE pacote_id = ? ORDER BY peso DESC'
        );
        $stmt->execute([$pacoteId]);

        $chances = [];

        foreach ($stmt->fetchAll() as $linha) {
            $chances[$linha['raridade']] = (int)$linha['peso'];
        }

        return $chances;
    }

    /**
     * Sorteia as cartas de um pacote conforme os pesos e as regras especiais.
     * Repetidas são permitidas: a mesma carta pode sair mais de uma vez.
     *
     * @param array $pacote  Dados do pacote (quantidade_cartas, raridade_garantida, chance_lendaria)
     * @param array $pool    Cartas permitidas no pacote
     * @param array $chances raridade => peso
     *
     * @return array<int,array> Cartas sorteadas
     */
    public function sortearCartas(array $pacote, array $pool, array $chances): array
    {
        $quantidade = max(1, (int)($pacote['quantidade_cartas'] ?? 5));

        /** @var array<string,array<int,array>> $porRaridade */
        $porRaridade = [];

        foreach ($pool as $carta) {
            $raridade = (string)($carta['raridade'] ?? '');

            if (isset(self::ORDEM_RARIDADE[$raridade])) {
                $porRaridade[$raridade][] = $carta;
            }
        }

        if ($porRaridade === []) {
            return [];
        }

        // Só entram no sorteio raridades com peso positivo e cartas disponíveis.
        $pesos = [];

        foreach (Carta::RARIDADES as $raridade => $dados) {
            $peso = (int)($chances[$raridade] ?? 0);

            if ($peso > 0 && !empty($porRaridade[$raridade])) {
                $pesos[$raridade] = $peso;
            }
        }

        // Fallback: sem pesos configurados, distribui igualmente pelas raridades do pool.
        if ($pesos === []) {
            foreach (array_keys($porRaridade) as $raridade) {
                $pesos[$raridade] = 1;
            }
        }

        $slots = [];

        for ($i = 0; $i < $quantidade; $i++) {
            $slots[] = $this->sortearRaridade($pesos);
        }

        // Chance de ao menos uma lendária no pacote.
        $chanceLendaria = max(0, min(100, (int)($pacote['chance_lendaria'] ?? 0)));

        if ($chanceLendaria > 0 && !empty($porRaridade['lendaria'])) {
            if (random_int(1, 100) <= $chanceLendaria) {
                $slots[random_int(0, count($slots) - 1)] = 'lendaria';
            }
        }

        // Garantia de raridade mínima.
        $garantia = (string)($pacote['raridade_garantida'] ?? '');

        if (isset(self::ORDEM_RARIDADE[$garantia]) && !empty($porRaridade[$garantia])) {
            $atingiu = false;

            foreach ($slots as $raridade) {
                if (self::ORDEM_RARIDADE[$raridade] >= self::ORDEM_RARIDADE[$garantia]) {
                    $atingiu = true;
                    break;
                }
            }

            if (!$atingiu) {
                $slots[random_int(0, count($slots) - 1)] = $garantia;
            }
        }

        $cartas = [];

        foreach ($slots as $raridade) {
            if (empty($porRaridade[$raridade])) {
                $raridade = $this->raridadeMaisProxima($porRaridade, $raridade);
            }

            $candidatas = $porRaridade[$raridade] ?? [];

            if ($candidatas === []) {
                continue;
            }

            $carta = $candidatas[random_int(0, count($candidatas) - 1)];

            $cartas[] = [
                'id' => (int)$carta['id'],
                'nome' => (string)$carta['nome'],
                'raridade' => (string)$carta['raridade'],
                'imagem' => (string)($carta['imagem'] ?? ''),
                'ataque' => (int)($carta['ataque'] ?? 0),
                'defesa' => (int)($carta['defesa'] ?? 0),
            ];
        }

        return $cartas;
    }

    /**
     * Abre uma compra em uma única transação: registra a abertura e
     * adiciona as cartas sorteadas à coleção.
     *
     * @return array{abertura_id:int, pacote:array, cartas:array}
     *
     * @throws AberturaException
     */
    public function abrir(int $usuarioId, int $compraId, string $token): array
    {
        if ($usuarioId <= 0 || $compraId <= 0) {
            throw new InvalidArgumentException('Dados da abertura inválidos.');
        }

        if (trim($token) === '') {
            throw new InvalidArgumentException('Token de abertura inválido.');
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'SELECT c.id AS compra_id, c.pacote_id,
                        p.nome, p.descricao, p.selo, p.destaque, p.quantidade_cartas,
                        p.raridade_garantida, p.chance_lendaria,
                        a.id AS abertura_id
                 FROM compras c
                 INNER JOIN pacotes p ON p.id = c.pacote_id
                 LEFT JOIN aberturas a ON a.compra_id = c.id
                 WHERE c.id = ? AND c.usuario_id = ?
                 FOR UPDATE'
            );
            $stmt->execute([$compraId, $usuarioId]);
            $compra = $stmt->fetch();

            if (!$compra) {
                throw new AberturaException(
                    'Compra não encontrada para este jogador.',
                    AberturaException::COMPRA_NAO_ENCONTRADA
                );
            }

            if (!empty($compra['abertura_id'])) {
                throw new AberturaException(
                    'Este pacote já foi aberto.',
                    AberturaException::COMPRA_JA_ABERTA,
                    null,
                    (int)$compra['abertura_id']
                );
            }

            $stmt = $this->pdo->prepare(
                'SELECT ca.id, ca.nome, ca.descricao, ca.imagem, ca.raridade, ca.ataque, ca.defesa
                 FROM pacote_cartas pc
                 INNER JOIN cartas ca ON ca.id = pc.carta_id
                 WHERE pc.pacote_id = ?'
            );
            $stmt->execute([(int)$compra['pacote_id']]);
            $pool = $stmt->fetchAll();

            $cartas = $this->sortearCartas(
                $compra,
                $pool,
                $this->chancesDoPacote((int)$compra['pacote_id'])
            );

            if ($cartas === []) {
                throw new AberturaException(
                    'Este pacote não possui cartas disponíveis para sorteio.',
                    AberturaException::SEM_CARTAS
                );
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO aberturas (usuario_id, pacote_id, compra_id, cartas_recebidas)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $usuarioId,
                (int)$compra['pacote_id'],
                $compraId,
                json_encode($cartas, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ]);
            $aberturaId = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
                'INSERT INTO colecao (usuario_id, carta_id, quantidade)
                 VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE quantidade = quantidade + 1'
            );

            foreach ($cartas as $carta) {
                $stmt->execute([$usuarioId, (int)$carta['id']]);
            }

            $this->pdo->commit();

            return [
                'abertura_id' => $aberturaId,
                'pacote' => [
                    'id' => (int)$compra['pacote_id'],
                    'nome' => (string)$compra['nome'],
                ],
                'cartas' => $cartas,
            ];
        } catch (AberturaException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // Compra já aberta por outra requisição concorrente.
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new AberturaException(
                    'Este pacote já foi aberto.',
                    AberturaException::COMPRA_JA_ABERTA,
                    $e,
                    $this->aberturaDaCompra($usuarioId, $compraId)
                );
            }

            throw new AberturaException(
                'Não foi possível abrir o pacote. Tente novamente.',
                AberturaException::ERRO_GENERICO,
                $e
            );
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw new AberturaException(
                'Não foi possível abrir o pacote. Tente novamente.',
                AberturaException::ERRO_GENERICO,
                $e
            );
        }
    }

    /**
     * @param array<string,int> $pesos
     */
    private function sortearRaridade(array $pesos): string
    {
        $total = array_sum($pesos);
        $sorteio = random_int(1, max(1, $total));
        $acumulado = 0;

        foreach ($pesos as $raridade => $peso) {
            $acumulado += $peso;

            if ($sorteio <= $acumulado) {
                return $raridade;
            }
        }

        $raridades = array_keys($pesos);

        return $raridades[count($raridades) - 1];
    }

    /**
     * Raridade disponível mais próxima (prefere a inferior em caso de empate).
     *
     * @param array<string,array> $porRaridade
     */
    private function raridadeMaisProxima(array $porRaridade, string $desejada): string
    {
        $alvo = self::ORDEM_RARIDADE[$desejada] ?? 1;
        $melhor = null;
        $melhorDistancia = PHP_INT_MAX;

        foreach (array_keys($porRaridade) as $raridade) {
            $distancia = abs(self::ORDEM_RARIDADE[$raridade] - $alvo);

            if ($distancia < $melhorDistancia
                || ($distancia === $melhorDistancia
                    && self::ORDEM_RARIDADE[$raridade] < self::ORDEM_RARIDADE[$melhor])) {
                $melhor = $raridade;
                $melhorDistancia = $distancia;
            }
        }

        return $melhor ?? array_key_first($porRaridade);
    }

    private function decodificarCartas(mixed $json): array
    {
        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $cartas = json_decode($json, true);

        return is_array($cartas) ? $cartas : [];
    }

    /**
     * @param array<int,array> $cartas
     *
     * @return array<string,int>
     */
    private function resumirRaridades(array $cartas): array
    {
        $resumo = [];

        foreach ($cartas as $carta) {
            $raridade = (string)($carta['raridade'] ?? 'comum');

            if (!isset(self::ORDEM_RARIDADE[$raridade])) {
                continue;
            }

            $resumo[$raridade] = ($resumo[$raridade] ?? 0) + 1;
        }

        return $resumo;
    }
}
