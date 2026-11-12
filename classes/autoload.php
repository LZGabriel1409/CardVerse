<?php
/**
 * Autoload simples das classes de domínio do CardVerse.
 * Sem Composer: cada classe é carregada do arquivo com o mesmo nome.
 */
spl_autoload_register(static function (string $classe): void {
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $classe)) {
        return;
    }

    $arquivo = __DIR__ . '/' . $classe . '.php';

    if (is_file($arquivo)) {
        require_once $arquivo;
    }
});
