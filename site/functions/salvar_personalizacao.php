<?php

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../personalizar.php");
    exit;
}


/*
 * Recebe os dados enviados pelo formulário.
 */

$tema = $_POST["tema"] ?? "claro";
$cor = $_POST["cor"] ?? "azul";
$densidade = $_POST["densidade"] ?? "normal";

$calendario = isset($_POST["calendario"]) ? 1 : 0;
$tarefas = isset($_POST["tarefas"]) ? 1 : 0;
$resumo = isset($_POST["resumo"]) ? 1 : 0;


/*
 * Aqui vocês poderão conectar ao banco.
 *
 * Exemplo futuro:
 *
 * A conexão é carregada por config/config.php quando necessário.
 *
 * $sql = "INSERT/UPDATE ...";
 *
 * Por enquanto os dados são apenas recebidos.
 */


/*
 * Redireciona o usuário de volta
 * para a página de personalização.
 */

header(
    "Location: ../personalizar.php?sucesso=1"
);

exit;

?>
