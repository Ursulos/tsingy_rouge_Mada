<?php
// ============================================================
// controllers/PdrController.php
// ============================================================

class PdrController
{

    private PdrModel    $model;
    private VendeurModel $vendeurModel;
    private SecteurModel $secteurModel;
    private VilleModel   $villeModel;

    public function __construct()
    {
        auth_check();
        $this->model        = new PdrModel();
        $this->vendeurModel = new VendeurModel();
        $this->secteurModel = new SecteurModel();
        $this->villeModel   = new VilleModel();
    }

    // Liste de tous les PDR
    public function index(): void
    {
        $filters = [
            'type_cible'   => $_GET['type_cible']   ?? '',
            'statut'       => $_GET['statut']        ?? '',
            'type_periode' => $_GET['type_periode']  ?? '',
        ];

        $data = [
            'page_title'    => 'Objectifs PDR',
            'pdrs'          => $this->model->getAll($filters),
            'vendeurs'      => $this->vendeurModel->getAll(),
            'secteurs'      => $this->secteurModel->getAll(),
            'villes'        => $this->villeModel->getAll(),
            'filters'       => $filters,
        ];
        $this->render('pdr/index', $data);
    }

    // Créer un PDR (admin seulement)
    public function store(): void
    {
        auth_admin();
        csrf_verify();

        // Dates
        $debut = $_POST['date_debut'] ?? date('Y-m-d');
        $fin   = $_POST['date_fin']   ?? '';

        // Génération auto de la date fin
        if (empty($fin)) {

            $fin = match ($_POST['type_periode'] ?? 'mois') {

                'jour' =>
                $debut,

                'semaine' =>
                date('Y-m-d', strtotime($debut . ' +6 days')),

                'mois' =>
                date('Y-m-t', strtotime($debut)),

                'annee' =>
                date('Y-12-31', strtotime($debut)),

                default =>
                date('Y-m-t', strtotime($debut)),
            };
        }

        // Validation cohérence dates
        if ($debut > $fin) {

            set_flash(
                'danger',
                'La date de début doit être antérieure ou égale à la date de fin.'
            );

            $this->redirectTo('pdr');
        }

        // Création
        $this->model->create([

            'libelle' =>
            sanitize($_POST['libelle']),

            'type_cible' =>
            $_POST['type_cible'] ?? 'global',

            'cible_id' =>
            !empty($_POST['cible_id'])
                ? (int)$_POST['cible_id']
                : null,

            'type_periode' =>
            $_POST['type_periode'] ?? 'mois',

            'date_debut' =>
            $debut,

            'date_fin' =>
            $fin,

            'objectif_ca' =>
            $_POST['objectif_ca'] ?? 0,

            'objectif_pieces' =>
            $_POST['objectif_pieces'] ?? 0,

            'objectif_ventes' =>
            $_POST['objectif_ventes'] ?? 0,

            'note' =>
            sanitize($_POST['note'] ?? ''),
        ]);

        set_flash(
            'success',
            'Objectif PDR créé avec succès.'
        );

        $this->redirectTo('pdr');
    }

    // Modifier un PDR
    public function update(): void
    {

        auth_admin();
        csrf_verify();

        $id    = (int)($_POST['id'] ?? 0);

        $debut = $_POST['date_debut'] ?? date('Y-m-d');

        $fin   = $_POST['date_fin'] ?? $debut;

        // Validation
        if ($debut > $fin) {

            set_flash(
                'danger',
                'La date de début doit être antérieure ou égale à la date de fin.'
            );

            $this->redirectTo('pdr');
        }

        $this->model->update($id, [

            'libelle' =>
            sanitize($_POST['libelle']),

            'type_cible' =>
            $_POST['type_cible'] ?? 'global',

            'cible_id' =>
            !empty($_POST['cible_id'])
                ? (int)$_POST['cible_id']
                : null,

            'type_periode' =>
            $_POST['type_periode'] ?? 'mois',

            'date_debut' =>
            $debut,

            'date_fin' =>
            $fin,

            'objectif_ca' =>
            $_POST['objectif_ca'] ?? 0,

            'objectif_pieces' =>
            $_POST['objectif_pieces'] ?? 0,

            'objectif_ventes' =>
            $_POST['objectif_ventes'] ?? 0,

            'note' =>
            sanitize($_POST['note'] ?? ''),
        ]);

        set_flash(
            'success',
            'Objectif PDR mis à jour.'
        );

        $this->redirectTo('pdr');
    }

    // Supprimer
    public function delete(): void
    {
        auth_admin();
        csrf_verify();
        $this->model->delete((int)($_POST['id'] ?? 0));
        set_flash('success', 'PDR supprimé.');
        $this->redirectTo('pdr');
    }

    private function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        include __DIR__ . '/../views/' . $view . '.php';
    }

    private function redirectTo(string $page): void
    {
        header('Location: ' . APP_URL . '/index.php?page=' . $page);
        exit;
    }
}
