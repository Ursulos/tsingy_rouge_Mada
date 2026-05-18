<?php
// ============================================================
// controllers/VendeurController.php
// ============================================================

class VendeurController {

    private VendeurModel  $model;
    private SecteurModel  $secteurModel;

    public function __construct() {
        auth_check();
        $this->model        = new VendeurModel();
        $this->secteurModel = new SecteurModel();
    }

    // GET /index.php?page=vendeurs
    public function index(): void {
        $data = [
            'page_title' => 'Vendeurs',
            'vendeurs'   => $this->model->getAll(),
            'classement' => $this->model->classement(),
            'secteurs'   => $this->secteurModel->getAll(),
        ];
        $this->render('vendeurs/index', $data);
    }

    // POST — créer un vendeur
    public function store(): void {
        csrf_verify();

        $photo = null;
        if (!empty($_FILES['photo']['name'])) {
            $photo = upload_image($_FILES['photo'], 'vendeurs');
            if (!$photo) {
                set_flash('danger', 'Image invalide (JPG/PNG/WEBP, max 2 Mo).');
                $this->redirectTo('vendeurs');
            }
        }

        $errors = $this->validate([
            'nom'        => ['required', 'max:100'],
            'prenom'     => ['required', 'max:100'],
            'secteur_id' => ['required', 'numeric'],
        ], $_POST);

        if ($errors) {
            set_flash('danger', implode(' ', $errors));
            $this->redirectTo('vendeurs');
        }

        $this->model->create([
            'nom'        => sanitize($_POST['nom']),
            'prenom'     => sanitize($_POST['prenom']),
            'telephone'  => sanitize($_POST['telephone'] ?? ''),
            'email'      => sanitize($_POST['email']     ?? ''),
            'secteur_id' => (int)$_POST['secteur_id'],
            'statut'     => in_array($_POST['statut'] ?? '', ['actif','inactif']) ? $_POST['statut'] : 'actif',
            'photo'      => $photo,
        ]);

        set_flash('success', 'Vendeur ajouté avec succès.');
        $this->redirectTo('vendeurs');
    }

    // POST — mettre à jour un vendeur
    public function update(): void {
        csrf_verify();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { set_flash('danger', 'Identifiant manquant.'); $this->redirectTo('vendeurs'); }

        $data = [
            'nom'        => sanitize($_POST['nom']),
            'prenom'     => sanitize($_POST['prenom']),
            'telephone'  => sanitize($_POST['telephone'] ?? ''),
            'email'      => sanitize($_POST['email']     ?? ''),
            'secteur_id' => (int)$_POST['secteur_id'],
            'statut'     => in_array($_POST['statut'] ?? '', ['actif','inactif']) ? $_POST['statut'] : 'actif',
        ];

        if (!empty($_FILES['photo']['name'])) {
            $photo = upload_image($_FILES['photo'], 'vendeurs');
            if ($photo) $data['photo'] = $photo;
        }

        $this->model->update($id, $data);
        set_flash('success', 'Vendeur mis à jour.');
        $this->redirectTo('vendeurs');
    }

    // POST — supprimer un vendeur
    public function delete(): void {
        csrf_verify();
        auth_admin();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { set_flash('danger', 'Identifiant manquant.'); $this->redirectTo('vendeurs'); }

        $this->model->delete($id);
        set_flash('success', 'Vendeur supprimé.');
        $this->redirectTo('vendeurs');
    }

    // ---- Helpers ----

    private function validate(array $rules, array $data): array {
        $errors = [];
        foreach ($rules as $field => $ruleList) {
            $value = trim($data[$field] ?? '');
            foreach ($ruleList as $rule) {
                if ($rule === 'required' && $value === '') {
                    $errors[] = "Le champ « $field » est obligatoire.";
                }
                if (str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                    if (mb_strlen($value) > $max) $errors[] = "« $field » dépasse $max caractères.";
                }
                if ($rule === 'numeric' && $value !== '' && !is_numeric($value)) {
                    $errors[] = "« $field » doit être numérique.";
                }
            }
        }
        return $errors;
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
