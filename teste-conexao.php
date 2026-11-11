
<?php
require_once 'config/conexao.php';

$sql = "SELECT COUNT(*) AS total FROM cartas";
$resultado = $pdo->query($sql);
$dados = $resultado->fetch();

echo "Conexão realizada com sucesso!<br>";
echo "Cartas cadastradas: " . $dados['total'];
?>
