<?php

class Conquista
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // para pegar a quantidade de conquistas existentes no jogo
    public function listarConquistas()
    {
        $stmt = $this->pdo->query("
            SELECT count(*) FROM `conquistas`;
        ");

        return (int)$stmt->fetchColumn();
    }

    // para mostrar as conquistas no perfil
    public function conquistasPreview(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                c.id,
                c.nome,
                c.descricao,
                c.icone,
                c.pontos,
                c.experiencia,
                uc.id AS usuario_conquista_id,
                uc.data_desbloqueio
            FROM conquistas c
            LEFT JOIN usuarios_conquistas uc
                ON uc.id_conquista = c.id
                AND uc.id_usuario = ?
            ORDER BY
                (uc.id IS NOT NULL) DESC,
                uc.data_desbloqueio DESC,
                c.id ASC
            LIMIT 4
        ");

        $stmt->execute([$usuarioId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    // Buscar conquistas desbloqueadas pelo usuário
    public function contarConquistas(int $usuarioId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM usuarios_conquistas
            WHERE id_usuario = ?
        ");

        $stmt->execute([$usuarioId]);

        return (int)$stmt->fetchColumn();
    }

    public function desbloquear(int $usuarioId, int $conquistaId): void
    {
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO usuarios_conquistas
            (id_usuario, id_conquista)
            VALUES (?, ?)
        ");

        $stmt->execute([$usuarioId, $conquistaId]);
    }

    
    public function verificarCompras(int $usuarioId): void
    {
        // 1. Descobre quantos pacotes o jogador comprou
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM compras
            WHERE usuario_id = ?
        ");

        $stmt->execute([$usuarioId]);

        $totalCompras = (int)$stmt->fetchColumn();


        // 2. Busca todas as conquistas relacionadas a compras
        $stmt = $this->pdo->prepare("
            SELECT id, valor_criterio, pontos, experiencia
            FROM conquistas
            WHERE criterio = 'comprar_pacotes'
        ");

        $stmt->execute();

        $conquistas = $stmt->fetchAll(PDO::FETCH_ASSOC);


        // 3. Verifica cada conquista
        foreach ($conquistas as $conquista) {

            if ($totalCompras >= (int)$conquista['valor_criterio']) {

                // 4. Tenta desbloquear a conquista
                $stmt = $this->pdo->prepare("
                    INSERT IGNORE INTO usuarios_conquistas
                    (id_usuario, id_conquista)
                    VALUES (?, ?)
                ");

                $stmt->execute([
                    $usuarioId,
                    $conquista['id']
                ]);

                // 5. Entrega as recompensas somente se foi desbloqueada agora
                if ($stmt->rowCount() > 0) {

                    $stmt = $this->pdo->prepare("
                        UPDATE usuarios
                        SET pontos = pontos + ?,
                            experiencia = experiencia + ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $conquista['pontos'],
                        $conquista['experiencia'],
                        $usuarioId
                    ]);
                }
            }
        }
    }

}