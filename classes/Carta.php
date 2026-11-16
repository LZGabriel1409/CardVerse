<?php
/**
 * Catálogo de cartas e coleção do jogador.
 */
class Carta
{
    public const RARIDADES = [
        'comum' => [
            'rotulo' => 'Comum',
            'classe' => 'rarity-comum',
            'icone' => 'bi-circle-fill',
        ],
        'rara' => [
            'rotulo' => 'Rara',
            'classe' => 'rarity-rara',
            'icone' => 'bi-diamond-fill',
        ],
        'epica' => [
            'rotulo' => 'Épica',
            'classe' => 'rarity-epica',
            'icone' => 'bi-gem',
        ],
        'lendaria' => [
            'rotulo' => 'Lendária',
            'classe' => 'rarity-lendaria',
            'icone' => 'bi-stars',
        ],
    ];

    public static function dadosExibicao(array $carta): array
    {
        $tipo = (string)($carta['tipo_especial'] ?? 'normal');
        if ($tipo === 'secreta') {
            return ['rotulo' => 'Secreta', 'classe' => 'rarity-secreta', 'icone' => 'bi-incognito'];
        }
        if ($tipo === 'exclusiva') {
            return ['rotulo' => 'Exclusiva', 'classe' => 'rarity-exclusiva', 'icone' => 'bi-award-fill'];
        }
        return self::RARIDADES[$carta['raridade'] ?? 'comum'] ?? self::RARIDADES['comum'];
    }

    public function __construct(private PDO $pdo)
    {
    }

    public function listarCatalogo(): array
    {
        return $this->pdo->query(
            'SELECT id, nome, descricao, imagem, raridade, ataque, defesa
             FROM cartas
             WHERE tipo_especial = \'normal\'
             ORDER BY nome'
        )->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, nome, descricao, imagem, raridade, ataque, defesa
             FROM cartas
             WHERE id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $carta = $stmt->fetch();

        return $carta ?: null;
    }

    /**
     * Lista as cartas da coleção do jogador, com busca por nome e filtro de raridade.
     */
    public function listarColecao(int $usuarioId, string $busca = '', string $raridade = ''): array
    {
        $busca = trim($busca);

        if (!isset(self::RARIDADES[$raridade])) {
            $raridade = '';
        }

        $condicoes = ['co.usuario_id = ?'];
        $parametros = [$usuarioId];

        if ($busca !== '') {
            $condicoes[] = 'ca.nome LIKE ?';
            $parametros[] = '%' . $busca . '%';
        }

        if ($raridade !== '') {
            $condicoes[] = 'ca.raridade = ?';
            $parametros[] = $raridade;
        }

        $sql = 'SELECT ca.id, ca.nome, ca.descricao, ca.imagem, ca.raridade,
                       ca.ataque, ca.defesa, ca.tipo_especial, SUM(co.quantidade) AS quantidade
                FROM colecao co
                INNER JOIN cartas ca ON ca.id = co.carta_id
                WHERE ' . implode(' AND ', $condicoes) . '
                GROUP BY ca.id
                ORDER BY FIELD(ca.raridade, \'lendaria\', \'epica\', \'rara\', \'comum\'), ca.nome';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Retorna a quantidade de tipos e o total de cartas da coleção.
     */
    public function resumoColecao(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS tipos, COALESCE(SUM(quantidade), 0) AS total
             FROM colecao
             WHERE usuario_id = ?'
        );
        $stmt->execute([$usuarioId]);
        $resumo = $stmt->fetch();

        return [
            'tipos' => (int)($resumo['tipos'] ?? 0),
            'total' => (int)($resumo['total'] ?? 0),
        ];
    }

    public function quantidadeNaColecao(int $usuarioId, int $cartaId): int
    {
        if ($usuarioId <= 0 || $cartaId <= 0) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT quantidade FROM colecao WHERE usuario_id = ? AND carta_id = ? LIMIT 1'
        );
        $stmt->execute([$usuarioId, $cartaId]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Adiciona cartas à coleção, somando a quantidade quando a carta já existe.
     */
    public function adicionarAoColecao(int $usuarioId, int $cartaId, int $quantidade = 1): void
    {
        if ($usuarioId <= 0 || $cartaId <= 0) {
            throw new InvalidArgumentException('Jogador ou carta inválidos.');
        }

        if ($quantidade <= 0) {
            throw new InvalidArgumentException('A quantidade deve ser maior que zero.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO colecao (usuario_id, carta_id, quantidade)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantidade = quantidade + ?'
        );
        $stmt->execute([$usuarioId, $cartaId, $quantidade, $quantidade]);
    }

    public function qtdCartasExistentes() {
        $stmt = $this->pdo->query("
            SELECT count(*) FROM `cartas`;
        ");

        return (int)$stmt->fetchColumn();
    }
}
