<?php
/**
 * Conta de jogador: consulta, cadastro e autenticação.
 */
class Usuario
{
    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, moedas, tipo, criado_em
             FROM usuarios
             WHERE id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function buscarPorEmail(string $email): ?array
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, senha, moedas, tipo, criado_em
             FROM usuarios
             WHERE email = ?
             LIMIT 1'
        );
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function emailExiste(string $email): bool
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Cria um jogador com senha protegida por hash e 500 moedas iniciais.
     *
     * @throws InvalidArgumentException Quando os dados são inválidos.
     */
    public function criar(string $nome, string $email, string $senha): int
    {
        $nome = trim($nome);
        $email = strtolower(trim($email));

        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 100) {
            throw new InvalidArgumentException('O nome deve ter entre 3 e 100 caracteres.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            throw new InvalidArgumentException('Informe um e-mail válido.');
        }

        if (strlen($senha) < 8) {
            throw new InvalidArgumentException('A senha deve ter pelo menos 8 caracteres.');
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios (nome, email, senha, moedas, tipo, criado_em)
             VALUES (?, ?, ?, 500, 'usuario', NOW())"
        );
        $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Valida e-mail e senha. Retorna os dados do jogador sem a senha, ou null.
     */
    public function autenticar(string $email, string $senha): ?array
    {
        if ($senha === '') {
            return null;
        }

        $usuario = $this->buscarPorEmail($email);

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            return null;
        }

        unset($usuario['senha']);

        return $usuario;
    }
}
