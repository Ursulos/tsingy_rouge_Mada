<?php
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

    private function formData(): array {
        return [
            'vendeurs' => $this->vendeurModel->getAll(),
            'clients'  => $this->clientModel->getAll(1000,0),
            'secteurs' => $this->secteurModel->getAll(),
            'villes'   => $this->villeModel->getAll(),
            'produits' => $this->produitModel->getAll(true),
        ];
    }

    public function index(): void {
        $f=['vendeur_id'=>!empty($_GET['vendeur_id'])?(int)$_GET['vendeur_id']:null,
            'secteur_id'=>!empty($_GET['secteur_id'])?(int)$_GET['secteur_id']:null,
            'ville_id'  =>!empty($_GET['ville_id'])  ?(int)$_GET['ville_id']  :null,
            'date_debut'=>sanitize($_GET['date_debut']??''),
            'date_fin'  =>sanitize($_GET['date_fin']  ??'')];
        $total=$this->model->count($f);
        $pagination=paginate($total,max(1,(int)($_GET['p']??1)),30);
        $this->render('ventes/index',array_merge($this->formData(),[
            'page_title'=>'Ventes','ventes'=>$this->model->getAll($f,30,$pagination['offset']),
            'f'=>$f,'total'=>$total,'pagination'=>$pagination,
            'show_create'=>false,'edit_vente'=>null,'edit_lignes'=>[],
        ]));
    }

    public function create(): void {
        $this->render('ventes/index',array_merge($this->formData(),[
            'page_title'=>'Nouvelle vente','show_create'=>true,
            'edit_vente'=>null,'edit_lignes'=>[],
            'ventes'=>[],'f'=>[],'total'=>0,'pagination'=>[],
        ]));
    }

    public function edit(): void {
        $id=$this->getId();
        $vente=$this->model->getById($id);
        if (!$vente) { set_flash('danger','Vente introuvable.'); $this->redirectTo('ventes'); }
        $this->render('ventes/index',array_merge($this->formData(),[
            'page_title'=>'Modifier la vente','show_create'=>true,
            'edit_vente'=>$vente,'edit_lignes'=>$this->model->getLignes($id),
            'ventes'=>[],'f'=>[],'total'=>0,'pagination'=>[],
        ]));
    }

    public function store(): void {
        csrf_verify();
        $lignes=$this->parseLignes();
        if (empty($lignes)) { set_flash('danger','Ajoutez au moins une ligne.'); $this->redirectTo('ventes','create'); }
        try {
            $this->model->create($this->parseData(),$lignes);
            set_flash('success','Vente enregistrée avec succès.');
            $this->redirectTo('ventes');
        } catch (Exception $e) {
            set_flash('danger','Erreur : '.$e->getMessage());
            $this->redirectTo('ventes','create');
        }
    }

    public function update(): void {
        csrf_verify();
        $id=(int)($_POST['vente_id']??0);
        $lignes=$this->parseLignes();
        if (empty($lignes)) { set_flash('danger','Ajoutez au moins une ligne.'); $this->redirectTo('ventes','edit&id='.$id); }
        try {
            $this->model->update($id,$this->parseData(),$lignes);
            set_flash('success','Vente modifiée avec succès.');
            $this->redirectTo('ventes');
        } catch (Exception $e) {
            set_flash('danger','Erreur : '.$e->getMessage());
            $this->redirectTo('ventes');
        }
    }

    public function delete(): void {
        csrf_verify();
        $id=(int)($_POST['id']??0);
        try {
            $this->model->delete($id);
            set_flash('success','Vente supprimée. Stock remis à jour.');
        } catch (Exception $e) {
            set_flash('danger','Erreur : '.$e->getMessage());
        }
        $this->redirectTo('ventes');
    }

    public function show(): void { $this->index(); }

    private function parseData(): array {
        return ['vendeur_id'=>(int)($_POST['vendeur_id']??0),'client_id'=>(int)($_POST['client_id']??0),
                'secteur_id'=>(int)($_POST['secteur_id']??0),'ville_id'=>(int)($_POST['ville_id']??0),
                'date_vente'=>sanitize($_POST['date_vente']??date('Y-m-d')),'note'=>sanitize($_POST['note']??'')];
    }

    private function parseLignes(): array {
        $pids=$_POST['produit_id']??[]; $qtys=$_POST['quantite']??[]; $pus=$_POST['prix_unitaire']??[];
        $lignes=[];
        foreach ($pids as $k=>$pid) {
            $pid=(int)$pid; $qty=(int)($qtys[$k]??0); $pu=floatval($pus[$k]??0);
            if ($pid>0&&$qty>0) $lignes[]=['produit_id'=>$pid,'quantite'=>$qty,'prix'=>$pu];
        }
        return $lignes;
    }

    private function getId(): int { return (int)($_GET['id']??$_POST['id']??0); }

    private function render(string $view, array $data=[]): void {
        extract($data,EXTR_SKIP);
        include __DIR__.'/../views/'.$view.'.php';
    }

    private function redirectTo(string $page, string $action=''): void {
        $url=APP_URL.'/index.php?page='.$page;
        if ($action) $url.='&action='.$action;
        header('Location:'.$url); exit;
    }
}