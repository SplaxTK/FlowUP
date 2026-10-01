<?php
require_once 'config/config.php';
require_once 'functions.php';
require_once 'includes/helpers.php';

validarCSRF();

$secao = $_GET['secao'] ?? 'cadastro';
$acao = $_GET['acao'] ?? '';
$id = $_GET['id'] ?? '';
$email = $_GET['email'] ?? '';
$busca = trim($_GET['busca'] ?? '');

$mensagem = '';
$tipo_mensagem = '';
$erro_email = '';
$manter_form = false;
$registro = null;
$todos = array();

switch ($secao) {
    case 'tarefas':
        require_once 'controllers/tarefas_controller.php';
        break;
    case 'recompensa':
        require_once 'controllers/recompensa_controller.php';
        break;
    case 'cadastro':
    default:
        require_once 'controllers/cadastro_controller.php';
        break;
}

require_once 'includes/header.php';

switch ($secao) {
    case 'tarefas':
        require_once 'views/tarefas_view.php';
        break;
    case 'recompensa':
        require_once 'views/recompensa_view.php';
        break;
    case 'cadastro':
    default:
        require_once 'views/cadastro_view.php';
        break;
}

require_once 'includes/footer.php';