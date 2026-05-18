<?php
// ============================================================
// controllers/ClientController.php
// ============================================================

class ClientController {

    private ClientModel  $model;
    private SecteurModel $secteurModel;
    private VilleModel   $villeModel;

    public function __construct() {
        auth_check();
        $this->model        = new ClientModel();
        $this->secteurModel = new SecteurModel();
        $this->villeModel   = new VilleModel();
    }

    public function index(): void {
        $f = [
            'search'     => sanitize($_GET['search']     ?? ''),
            'secteur_id' => !empty($_GET['secteur_id']) ? (int)$_GET['secteur_id'] : null,
            'ville_id'   => !empty($_GET['ville_id'])   ? (int)$_GET['ville_id']   : null,
        ];
        $page_num   = max(1, (int)($_GET['p'] ?? 1));
        $per_page   = 25;
        $total      = count($this->model->getAll(9999, 0, $f));
        $pagination = paginate($total, $page_num, $per_page);

        $data = [
            'page_title'   => 'Clients',
            'clients'      => $this->model->getAll($per_page, $pagination['offset'], $f),
            'top_clients'  => $this->model->topClients(date('n'), date('Y'), CLIENT_TOP_SEUIL),
            'secteurs'     => $this->secteurModel->getAll(),
            'villes'       => $this->villeModel->getAll(),
            'f'            => $f,
            'pagination'   => $pagination,
            'total_clients'=> $total,
        ];

        $this->render('clients/index', $data);
    }

    public function store(): void {
        csrf_verify();

        $this->model->create([
            'nom'        => sanitize($_POST['nom']),
            'prenom'     => sanitize($_POST['prenom']),
            'telephone'  => sanitize($_POST['telephone'] ?? ''),
            'email'      => sanitize($_POST['email']     ?? ''),
            'ville_id'   => !empty($_POST['ville_id'])   ? (int)$_POST['ville_id']   : null,
            'secteur_id' => !empty($_POST['secteur_id']) ? (int)$_POST['secteur_id'] : null,
        ]);

        set_flash('success', 'Client ajouté avec succès.');
        $this->redirectTo('clients');
    }

    public function update(): void {
        csrf_verify();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { set_flash('danger', 'ID manquant.'); $this->redirectTo('clients'); }

        $this->model->update($id, [
            'nom'        => sanitize($_POST['nom']),
            'prenom'     => sanitize($_POST['prenom']),
            'telephone'  => sanitize($_POST['telephone'] ?? ''),
            'email'      => sanitize($_POST['email']     ?? ''),
            'ville_id'   => !empty($_POST['ville_id'])   ? (int)$_POST['ville_id']   : null,
            'secteur_id' => !empty($_POST['secteur_id']) ? (int)$_POST['secteur_id'] : null,
        ]);

        set_flash('success', 'Client mis à jour.');
        $this->redirectTo('clients');
    }

    public function delete(): void {
        csrf_verify();
        auth_admin();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { set_flash('danger', 'ID manquant.'); $this->redirectTo('clients'); }

        $this->model->delete($id);
        set_flash('success', 'Client supprimé.');
        $this->redirectTo('clients');
    }

    private function render(string $view, array $data = []): void {
        extract($data, EXTR_SKIP);
        include __DIR__ . '/../views/' . $view . '.php';
    }

    private function redirectTo(string $page): void {
        header('Location: ' . APP_URL . '/index.php?page=' . $page);
        exit;
    }
}
