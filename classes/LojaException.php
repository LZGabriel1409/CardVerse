<?php
/**
 * Exceção de domínio da loja.
 * O código identifica a regra violada para a camada de apresentação.
 */
class LojaException extends RuntimeException
{
    public const PACOTE_INDISPONIVEL = 1;
    public const SALDO_INSUFICIENTE = 2;
    public const COMPRA_DUPLICADA = 3;
    public const USUARIO_INEXISTENTE = 4;
    public const ERRO_GENERICO = 5;
}
