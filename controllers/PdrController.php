<?php
class PdrController {
    private PdrModel     $model;
    private VendeurModel $vendeurModel;
    private SecteurModel $secteurModel;
    private VilleModel   $villeModel;

    public function __construct() {
        auth_check();
        $this->model        = new PdrModel();
        $this->vendeurModel = new VendeurModel();
        $this->secteurModel = new SecteurModel();
        $this->villeModel   = new VilleModel();
    }

    public function index(): void {
        $filters=['type_cible'=>$_GET['type_cible']??'','statut'=>$_GET['statut']??'','type_periode'=>$_GET['type_periode']??''];
        $this->render('pdr/index',[
            'page_title'=>'Objectifs PDR',
            'pdrs'      =>$this->model->getAll($filters),
            'vendeurs'  =>$this->vendeurModel->getAll(),
            'secteurs'  =>$this->secteurModel->getAll(),
            'villes'    =>$this->villeModel->getAll(),
            'filters'   =>$filters,
        ]);
    }

    public function store(): void {
        auth_admin(); csrf_verify();
        $debut = $_POST['date_debut'] ?? date('Y-m-d');
        $fin   = $_POST['date_fin']   ?? '';
        if (empty($fin)) $fin = $this->autoFin($debut, $_POST['type_periode']??'mois');

        $type_cible = $_POST['type_cible'] ?? 'global';

        // Multi-cible : si plusieurs cibles cochées, créer un PDR par cible
        $cibles = $_POST['cibles'] ?? [];

        if ($type_cible !== 'global' && !empty($cibles)) {
            foreach ($cibles as $cible_id) {
                $this->model->create([
                    'libelle'         => sanitize($_POST['libelle']),
                    'type_cible'      => $type_cible,
                    'cible_id'        => (int)$cible_id,
                    'type_periode'    => $_POST['type_periode']??'mois',
                    'date_debut'      => $debut,
                    'date_fin'        => $fin,
                    'objectif_ca'     => $_POST['objectif_ca']    ??0,
                    'objectif_pieces' => $_POST['objectif_pieces'] ??0,
                    'objectif_ventes' => $_POST['objectif_ventes'] ??0,
                    'note'            => sanitize($_POST['note']??''),
                ]);
            }
            $nb = count($cibles);
            set_flash('success', "$nb objectif(s) PDR créé(s) avec succès.");
        } else {
            // PDR global ou cible unique
            $this->model->create([
                'libelle'         => sanitize($_POST['libelle']),
                'type_cible'      => $type_cible,
                'cible_id'        => !empty($_POST['cible_id']) ? (int)$_POST['cible_id'] : null,
                'type_periode'    => $_POST['type_periode']??'mois',
                'date_debut'      => $debut,
                'date_fin'        => $fin,
                'objectif_ca'     => $_POST['objectif_ca']    ??0,
                'objectif_pieces' => $_POST['objectif_pieces'] ??0,
                'objectif_ventes' => $_POST['objectif_ventes'] ??0,
                'note'            => sanitize($_POST['note']??''),
            ]);
            set_flash('success','Objectif PDR créé.');
        }
        $this->redirectTo('pdr');
    }

    public function update(): void {
        auth_admin(); csrf_verify();
        $id    = (int)($_POST['id']??0);
        $debut = $_POST['date_debut']??date('Y-m-d');
        $fin   = $_POST['date_fin']  ?? $this->autoFin($debut,$_POST['type_periode']??'mois');
        $this->model->update($id,[
            'libelle'         => sanitize($_POST['libelle']),
            'type_cible'      => $_POST['type_cible']??'global',
            'cible_id'        => !empty($_POST['cible_id'])?(int)$_POST['cible_id']:null,
            'type_periode'    => $_POST['type_periode']??'mois',
            'date_debut'      => $debut,
            'date_fin'        => $fin,
            'objectif_ca'     => $_POST['objectif_ca']    ??0,
            'objectif_pieces' => $_POST['objectif_pieces'] ??0,
            'objectif_ventes' => $_POST['objectif_ventes'] ??0,
            'note'            => sanitize($_POST['note']??''),
        ]);
        set_flash('success','PDR mis à jour.');
        $this->redirectTo('pdr');
    }

    public function delete(): void {
        auth_admin(); csrf_verify();
        $this->model->delete((int)($_POST['id']??0));
        set_flash('success','PDR supprimé.');
        $this->redirectTo('pdr');
    }

    private function autoFin(string $debut, string $type): string {
        return match($type) {
            'jour'    => $debut,
            'semaine' => date('Y-m-d', strtotime($debut.' +6 days')),
            'mois'    => date('Y-m-t', strtotime($debut)),
            'annee'   => date('Y-12-31', strtotime($debut)),
            default   => date('Y-m-t', strtotime($debut)),
        };
    }

    private function render(string $view, array $data=[]): void {
        extract($data,EXTR_SKIP);
        include __DIR__.'/../views/'.$view.'.php';
    }

    private function redirectTo(string $page): void {
        header('Location:'.APP_URL.'/index.php?page='.$page); exit;
    }
}