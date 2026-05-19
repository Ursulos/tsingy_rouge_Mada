<?php
session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';

require_once __DIR__ . '/models/DashboardModel.php';
require_once __DIR__ . '/models/VenteModel.php';
require_once __DIR__ . '/models/Models.php';
require_once __DIR__ . '/models/PdrModel.php';

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/VendeurController.php';
require_once __DIR__ . '/controllers/ClientController.php';
require_once __DIR__ . '/controllers/VenteController.php';
require_once __DIR__ . '/controllers/PdrController.php';

$page   = filter_input(INPUT_GET, 'page',   FILTER_SANITIZE_SPECIAL_CHARS) ?? 'dashboard';
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'index';
$method = $_SERVER['REQUEST_METHOD'];

if ($page === 'login')  { (new AuthController())->login();  exit; }
if ($page === 'logout') { (new AuthController())->logout(); exit; }

auth_check();

switch ($page) {

    case 'dashboard':
        (new DashboardController())->index();
        break;

    case 'ventes':
        $ctrl = new VenteController();
        if ($method === 'POST') {
            $af = $_POST['action_form'] ?? '';
            if ($af === 'create_vente') $ctrl->store();
            elseif ($af === 'update_vente') $ctrl->update();
            elseif ($af === 'delete_vente') $ctrl->delete();
            else $ctrl->index();
        } elseif ($action === 'create') $ctrl->create();
        elseif ($action === 'edit')   $ctrl->edit();
        else $ctrl->index();
        break;

    case 'clients':
        $ctrl = new ClientController();
        if ($method === 'POST') {
            $af = $_POST['action_form'] ?? '';
            if ($af === 'create') $ctrl->store();
            elseif ($af === 'update') $ctrl->update();
            elseif ($af === 'delete') $ctrl->delete();
            else $ctrl->index();
        } else $ctrl->index();
        break;

    case 'vendeurs':
        $ctrl = new VendeurController();
        if ($method === 'POST') {
            $af = $_POST['action_form'] ?? '';
            if ($af === 'create') $ctrl->store();
            elseif ($af === 'update') $ctrl->update();
            elseif ($af === 'delete') $ctrl->delete();
            else $ctrl->index();
        } else $ctrl->index();
        break;

    case 'pdr':
        $ctrl = new PdrController();
        if ($method === 'POST') {
            $af = $_POST['action_form'] ?? '';
            if ($af === 'create') $ctrl->store();
            elseif ($af === 'update') $ctrl->update();
            elseif ($af === 'delete') $ctrl->delete();
            else $ctrl->index();
        } else $ctrl->index();
        break;

    case 'produits':
        require_once __DIR__ . '/views/produits/index.php';
        break;

    case 'secteurs':
    case 'villes':
        require_once __DIR__ . '/views/secteurs/index.php';
        break;

    case 'suivi':
        require_once __DIR__ . '/views/suivi/index.php';
        break;

    case 'analyses':
        require_once __DIR__ . '/views/analyses/index.php';
        break;

    case 'comparaison':
        require_once __DIR__ . '/views/comparaison/index.php';
        break;

    default:
        header('Location: ' . APP_URL . '/index.php?page=dashboard');
        exit;
}