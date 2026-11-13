<?php
/**
 * Exceção de domínio da abertura de pacotes.
 * O código identifica a regra violada; o id da abertura permite
 * redirecionar o jogador para uma revelação já registrada.
 */
class AberturaException extends RuntimeException
{
    public const COMPRA_NAO_ENCONTRADA = 1;
    public const COMPRA_JA_ABERTA = 2;
    public const SEM_CARTAS = 3;
    public const ERRO_GENERICO = 4;

    public function __construct(
        string $message,
        int $code,
        ?Throwable $previous = null,
        private ?int $aberturaId = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function aberturaId(): ?int
    {
        return $this->aberturaId;
    }
}
