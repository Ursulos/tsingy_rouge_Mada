<?php
// ============================================================
// controllers/DashboardController.php
// Charge les données et délègue le rendu à la vue
// ============================================================

class DashboardController {

    private DashboardModel $model;

    public function __construct() {
        auth_check();
        $this->model = new DashboardModel();
    }

    public function index(): void {
        $data = [
            'page_title'       => 'Dashboard',
            'kpis'             => $this->model->getKpis(),
            'meilleur_vendeur' => $this->model->getMeilleurVendeur(),
            'meilleur_secteur' => $this->model->getMeilleurSecteur(),
            'top_clients'      => $this->model->getTopClients(8),
            'ca_evolution'     => $this->model->getCaEvolution(),
            'ventes_vendeur'   => $this->model->getVentesParVendeur(),
            'ventes_secteur'   => $this->model->getVentesParSecteur(),
            'top_produits'     => $this->model->getTopProduits(),
            'ventes_recentes'  => $this->model->getVentesRecentes(8),
            'stock_faible'     => $this->model->getStockFaible(),
        ];

        $this->render('dashboard/index', $data);
    }

    // ---- Rendu : extrait les variables du tableau et inclut la vue ----
    private function render(string $view, array $data = []): void {
        extract($data, EXTR_SKIP);
        include __DIR__ . '/../views/' . $view . '.php';
    }
}
