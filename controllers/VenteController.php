<?php
// ============================================================
// controllers/VenteController.php
// ============================================================

class VenteController {

    private VenteModel   $model;
    private VendeurModel $vendeurModel;
    private ClientModel  $clientModel;
    private SecteurModel $secteurModel;
    private VilleModel   $villeModel;
    private ProduitModel $produitModel;

    public function __construct() {
        auth_check();
        $this->model        = new VenteModel();
        $this->vendeurModel = new VendeurModel();
        $this->clientModel  = new ClientModel();
        $this->secteurModel = new SecteurModel();
        $this->villeModel   = new VilleModel();
        $this->produitModel = new ProduitModel();
    }

    // Liste des ventes avec filtres
    public function index(): void {
        $f = [
            'vendeur_id'  => !empty($_GET['vendeur_id'])  ? (int)$_GET['vendeur_id']  : null,
            'secteur_id'  => !empty($_GET['secteur_id'])  ? (int)$_GET['secteur_id']  : null,
            'ville_id'    => !empty($_GET['ville_id'])    ? (int)$_GET['ville_id']    : null,
            'date_debut'  => sanitize($_GET['date_debut'] ?? ''),
            'date_fin'    => sanitize($_GET['date_fin']   ?? ''),
        ];

        $page_num   = max(1, (int)($_GET['p'] ?? 1));
        $per_page   = 30;
        $total      = $this->model->count($f);
        $pagination = paginate($total, $page_num, $per_page);

        $data = [
            'page_title'  => 'Ventes',
            'ventes'      => $this->model->getAll($f, $per_page, $pagination['offset']),
            'vendeurs'    => $this->vendeurModel->getAll(),
            'secteurs'    => $this->secteurModel->getAll(),
            'villes'      => $this->villeModel->getAll(),
            'f'           => $f,
            'total'       => $total,
            'pagination'  => $pagination,
            'show_create' => false,
        ];

        $this->render('ventes/index', $data);
    }

    // Formulaire création
    public function create(): void {
        $data = [
            'page_title'  => 'Nouvelle vente',
            'vendeurs'    => $this->vendeurModel->getAll(),
            'clients'     => $this->clientModel->getAll(500, 0),
            'secteurs'    => $this->secteurModel->getAll(),
            'villes'      => $this->villeModel->getAll(),
            'produits'    => $this->produitModel->getAll(true),
            'show_create' => true,
            // Valeurs vides pour éviter les undefined dans la vue
            'ventes' => [], 'f' => [], 'total' => 0, 'pagination' => [],
        ];
        $this->render('ventes/index', $data);
    }

    // Enregistrer une nouvelle vente
    public function store(): void {
        csrf_verify();

        // Construire les lignes
        $produit_ids = $_POST['produit_id']    ?? [];
        $quantites   = $_POST['quantite']      ?? [];
        $prix_units  = $_POST['prix_unitaire'] ?? [];

        $lignes = [];
        foreach ($produit_ids as $k => $pid) {
            $pid = (int)$pid;
            $qty = (int)($quantites[$k]  ?? 0);
            $pu  = floatval($prix_units[$k] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $lignes[] = ['produit_id' => $pid, 'quantite' => $qty, 'prix' => $pu];
            }
        }

        if (empty($lignes)) {
            set_flash('danger', 'Ajoutez au moins une ligne de produit valide.');
            $this->redirectTo('ventes', 'create');
        }

        // Valider les champs obligatoires
        $required = ['vendeur_id', 'client_id', 'secteur_id', 'ville_id', 'date_vente'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                set_flash('danger', "Le champ « $field » est obligatoire.");
                $this->redirectTo('ventes', 'create');
            }
        }

        try {
            $id = $this->model->create([
                'vendeur_id' => (int)$_POST['vendeur_id'],
                'client_id'  => (int)$_POST['client_id'],
                'secteur_id' => (int)$_POST['secteur_id'],
                'ville_id'   => (int)$_POST['ville_id'],
                'date_vente' => sanitize($_POST['date_vente']),
                'note'       => sanitize($_POST['note'] ?? ''),
            ], $lignes);

            set_flash('success', 'Vente #' . $id . ' enregistrée avec succès.');
            $this->redirectTo('ventes');

        } catch (Exception $e) {
            set_flash('danger', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
            $this->redirectTo('ventes', 'create');
        }
    }

    // Détail d'une vente
    public function show(): void {
        $id    = (int)($_GET['id'] ?? 0);
        $vente = $this->model->getById($id);

        if (!$vente) {
            set_flash('danger', 'Vente introuvable.');
            $this->redirectTo('ventes');
        }

        $data = [
            'page_title' => 'Vente ' . e($vente['reference']),
            'vente'      => $vente,
            'lignes'     => $this->model->getLignes($id),
        ];
        $this->render('ventes/detail', $data);
    }

    private function render(string $view, array $data = []): void {
        extract($data, EXTR_SKIP);
        include __DIR__ . '/../views/' . $view . '.php';
    }

    private function redirectTo(string $page, string $action = ''): void {
        $url = APP_URL . '/index.php?page=' . $page;
        if ($action) $url .= '&action=' . $action;
        header('Location: ' . $url);
        exit;
    }
}
