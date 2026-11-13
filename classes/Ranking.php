
<?php

class Ranking
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Lista os melhores jogadores
    public function listarRanking(int $limite = 10): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nome,
                avatar,
                pontos,
                experiencia
            FROM usuarios
            WHERE tipo = 'usuario'
            ORDER BY
                pontos DESC,
                experiencia DESC,
                criado_em ASC,
                id ASC
            LIMIT ?
        ");

        $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Descobre a posição de um jogador
    public function buscarPosicao(int $usuarioId): ?int
    {
        $stmt = $this->pdo->prepare("
            SELECT
                (
                    SELECT COUNT(*)
                    FROM usuarios outro
                    WHERE outro.tipo = 'usuario'
                    AND (
                        outro.pontos > jogador.pontos

                        OR (
                            outro.pontos = jogador.pontos
                            AND outro.experiencia > jogador.experiencia
                        )

                        OR (
                            outro.pontos = jogador.pontos
                            AND outro.experiencia = jogador.experiencia
                            AND outro.criado_em < jogador.criado_em
                        )

                        OR (
                            outro.pontos = jogador.pontos
                            AND outro.experiencia = jogador.experiencia
                            AND outro.criado_em = jogador.criado_em
                            AND outro.id < jogador.id
                        )
                    )
                ) + 1 AS posicao

            FROM usuarios jogador
            WHERE jogador.id = ?
            AND jogador.tipo = 'usuario'
        ");

        $stmt->execute([$usuarioId]);

        $posicao = $stmt->fetchColumn();

        return (int)$posicao !== false ? (int) $posicao : null;
    }
}
