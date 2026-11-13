
<?php

$saldo = (int)$usuario['moedas'];

// Identifica qual página está aberta
$paginaAtual = basename($_SERVER['PHP_SELF']);

?>

<header>

    <nav class="navbar navbar-expand navbar-dark nav-game py-3">
        <div class="container gap-2">

            <a class="navbar-brand brand mb-0" href="dashboard.php">
                <span class="brand-mark">
                    <i class="bi bi-stars logo-star"></i>
                </span>
                CardVerse
            </a>

            <div class="navbar-links d-none d-md-flex align-items-center gap-4 ms-2">

                <a class="nav-link <?= $paginaAtual === 'dashboard.php' ? 'active' : '' ?>"
                   href="dashboard.php"
                   <?= $paginaAtual === 'dashboard.php' ? 'aria-current="page"' : '' ?>>
                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                </a>

                <a class="nav-link <?= $paginaAtual === 'colecao.php' ? 'active' : '' ?>"
                   href="colecao.php"
                   <?= $paginaAtual === 'colecao.php' ? 'aria-current="page"' : '' ?>>
                    <i class="bi bi-grid-3x3-gap me-1"></i>Minha coleção
                </a>

                <a class="nav-link <?= $paginaAtual === 'loja.php' ? 'active' : '' ?>"
                   href="loja.php"
                   <?= $paginaAtual === 'loja.php' ? 'aria-current="page"' : '' ?>>
                    <i class="bi bi-shop me-1"></i>Loja
                </a>

                <a class="nav-link <?= $paginaAtual === 'abrir-pacote.php' ? 'active' : '' ?>"
                   href="abrir-pacote.php"
                   <?= $paginaAtual === 'abrir-pacote.php' ? 'aria-current="page"' : '' ?>>
                    <i class="bi bi-envelope-open me-1"></i>Abrir pacotes
                </a>

                <a class="nav-link <?= $paginaAtual === 'perfil.php' ? 'active' : '' ?>"
                   href="perfil.php"
                   <?= $paginaAtual === 'perfil.php' ? 'aria-current="page"' : '' ?>>
                    <i class="bi bi-person me-1"></i>Perfil
                </a>

            </div>

            <div class="ms-auto d-flex align-items-center gap-2">

                <span class="tag d-none d-sm-inline-block">
                    <i class="bi bi-coin me-1"></i><?= number_format($saldo, 0, ',', '.') ?>
                </span>

                <form action="logout.php" method="post" class="mb-0">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()) ?>">

                    <button class="btn btn-outline-game btn-sm px-3" type="submit">
                        <i class="bi bi-box-arrow-right me-1"></i>Sair
                    </button>
                </form>

            </div>

        </div>
    </nav>

</header>
