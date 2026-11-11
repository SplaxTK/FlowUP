<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/helpers.php';

if (!flowup_user_is_admin(['email' => $_SESSION['user_email'] ?? ''])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCSRF();
}

$secao = is_string($_GET['secao'] ?? null) ? $_GET['secao'] : 'cadastro';
$acao = is_string($_GET['acao'] ?? null) ? $_GET['acao'] : '';
$id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
$email = is_string($_GET['email'] ?? null) ? $_GET['email'] : '';
$busca = is_string($_GET['busca'] ?? null) ? trim($_GET['busca']) : '';

$mensagem = '';
$tipo_mensagem = '';
$erro_email = '';
$manter_form = false;
$registro = null;
$todos = array();

switch ($secao) {
    case 'tarefas':
        require_once __DIR__ . '/controllers/tarefas_controller.php';
        break;
    case 'recompensa':
        require_once __DIR__ . '/controllers/recompensa_controller.php';
        break;
    case 'cadastro':
    default:
        require_once __DIR__ . '/controllers/cadastro_controller.php';
        break;
}

require_once __DIR__ . '/includes/header.php';

switch ($secao) {
    case 'tarefas':
        require_once __DIR__ . '/views/tarefas_view.php';
        break;
    case 'recompensa':
        require_once __DIR__ . '/views/recompensa_view.php';
        break;
    case 'cadastro':
    default:
        require_once __DIR__ . '/views/cadastro_view.php';
        break;
}

require_once __DIR__ . '/includes/footer.php';