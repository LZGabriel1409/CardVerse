<?php
/**
 * Pacotes da loja: consulta e compra com validação de saldo.
 */
class Pacote
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listarAtivos(): array
    {
        return $this->pdo->query(
            'SELECT id, nome, descricao, selo, destaque, raridade_garantida, chance_lendaria,
                    preco, quantidade_cartas
             FROM pacotes
             WHERE ativo = 1
             ORDER BY preco ASC, nome ASC'
        )->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, nome, descricao, selo, destaque, raridade_garantida, chance_lendaria,
                    preco, quantidade_cartas, ativo
             FROM pacotes
             WHERE id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $pacote = $stmt->fetch();

        return $pacote ?: null;
    }

    public function buscarAtivo(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, nome, descricao, selo, destaque, raridade_garantida, chance_lendaria,
                    preco, quantidade_cartas
             FROM pacotes
             WHERE id = ? AND ativo = 1
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $pacote = $stmt->fetch();

        return $pacote ?: null;
    }

    /**
     * Cartas que podem ser sorteadas em um pacote.
     */
    public function cartasDoPacote(int $pacoteId): array
    {
        if ($pacoteId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT ca.id, ca.nome, ca.descricao, ca.imagem, ca.raridade, ca.ataque, ca.defesa
             FROM pacote_cartas pc
             INNER JOIN cartas ca ON ca.id = pc.carta_id
             WHERE pc.pacote_id = ?
             ORDER BY ca.nome'
        );
        $stmt->execute([$pacoteId]);

        return $stmt->fetchAll();
    }

    /**
     * Realiza a compra de um pacote em uma única transação.
     *
     * @return array{pacote: array{id: int, nome: string, preco: int}, preco: int, saldo_restante: int}
     *
     * @throws LojaException Quando o pacote está indisponível, o saldo é insuficiente
     *                       ou a compra já foi processada.
     * @throws InvalidArgumentException Quando os dados recebidos são inválidos.
     */
    public function comprar(int $usuarioId, int $pacoteId, string $tokenCompra): array
    {
        if ($usuarioId <= 0 || $pacoteId <= 0) {
            throw new InvalidArgumentException('Dados da compra inválidos.');
        }

        $tokenCompra = trim($tokenCompra);

        if ($tokenCompra === '') {
            throw new InvalidArgumentException('Token de compra inválido.');
        }

        try {
            $this->pdo->beginTransaction();

            // Bloqueia o pacote para validar disponibilidade e preço
            $stmt = $this->pdo->prepare(
                'SELECT id, nome, preco, ativo FROM pacotes WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$pacoteId]);
            $pacote = $stmt->fetch();

            if (!$pacote || (int)$pacote['ativo'] !== 1) {
                throw new LojaException(
                    'Pacote indisponível ou inexistente.',
                    LojaException::PACOTE_INDISPONIVEL
                );
            }

            $preco = (int)$pacote['preco'];

            if ($preco <= 0) {
                throw new LojaException(
                    'Este pacote não está disponível para compra.',
                    LojaException::PACOTE_INDISPONIVEL
                );
            }

            // Bloqueia o saldo do jogador durante a compra
            $stmt = $this->pdo->prepare('SELECT moedas FROM usuarios WHERE id = ? FOR UPDATE');
            $stmt->execute([$usuarioId]);
            $saldo = $stmt->fetchColumn();

            if ($saldo === false) {
                throw new LojaException(
                    'Conta não encontrada. Entre novamente.',
                    LojaException::USUARIO_INEXISTENTE
                );
            }

            if ((int)$saldo < $preco) {
                throw new LojaException(
                    'Saldo insuficiente para comprar o pacote "' . $pacote['nome'] . '".',
                    LojaException::SALDO_INSUFICIENTE
                );
            }

            $stmt = $this->pdo->prepare('UPDATE usuarios SET moedas = moedas - ? WHERE id = ?');
            $stmt->execute([$preco, $usuarioId]);

            $stmt = $this->pdo->prepare(
                'INSERT INTO compras (usuario_id, pacote_id, valor_pago, token_compra)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$usuarioId, $pacoteId, $preco, $tokenCompra]);

            $this->pdo->commit();

            return [
                'pacote' => [
                    'id' => (int)$pacote['id'],
                    'nome' => $pacote['nome'],
                    'preco' => $preco,
                ],
                'preco' => $preco,
                'saldo_restante' => (int)$saldo - $preco,
            ];
        } catch (LojaException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // Violação de chave única: token de compra já utilizado
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new LojaException(
                    'Esta compra já foi processada anteriormente.',
                    LojaException::COMPRA_DUPLICADA,
                    $e
                );
            }

            throw new LojaException(
                'Não foi possível concluir a compra. Tente novamente.',
                LojaException::ERRO_GENERICO,
                $e
            );
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
