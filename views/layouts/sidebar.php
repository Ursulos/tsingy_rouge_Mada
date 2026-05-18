<!-- views/layouts/sidebar.php -->
<?php
$current_page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'dashboard';
$user         = current_user();
$initiales    = strtoupper(substr($user['nom'], 0, 1));
$is_admin     = ($_SESSION['user_role'] ?? '') === 'admin';

$nav_items = [
    ['page' => 'dashboard',   'icon' => 'tachometer-alt', 'label' => 'Dashboard'],

    ['section' => 'Ventes'],
    ['page' => 'ventes',      'icon' => 'shopping-cart',  'label' => 'Ventes'],
    ['page' => 'suivi',       'icon' => 'table',          'label' => 'Suivi hebdo / mois'],
    ['page' => 'comparaison', 'icon' => 'balance-scale',  'label' => 'Comparaison'],

    ['section' => 'Gestion'],
    ['page' => 'clients',     'icon' => 'users',          'label' => 'Clients'],
    ['page' => 'produits',    'icon' => 'tshirt',         'label' => 'Produits'],

    ['section' => 'Équipe'],
    ['page' => 'vendeurs',    'icon' => 'user-tie',       'label' => 'Vendeurs'],
    ['page' => 'secteurs',    'icon' => 'map',            'label' => 'Secteurs & Villes'],

    ['section' => 'Objectifs'],
    ['page' => 'pdr',         'icon' => 'bullseye',       'label' => 'Objectifs PDR'],

    ['section' => 'Analyses'],
    ['page' => 'analyses',    'icon' => 'chart-bar',      'label' => 'Performances'],
];
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="sidebar-brand-icon">
      <i class="fas fa-tshirt"></i>
    </div>
    <div class="sidebar-brand-text">
      <div class="sidebar-brand-name">Tsingy Rouge</div>
      <div class="sidebar-brand-sub">Madagascar</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?php foreach ($nav_items as $item): ?>

      <?php if (isset($item['section'])): ?>
        <div class="nav-section-label"><?= e($item['section']) ?></div>

      <?php elseif (($item['admin_only'] ?? false) && !$is_admin): ?>
        <?php // masquer les liens admin aux vendeurs ?>

      <?php else: ?>
        <div class="nav-item">
          <a href="<?= APP_URL ?>/index.php?page=<?= $item['page'] ?>"
             class="nav-link <?= $current_page === $item['page'] ? 'active' : '' ?>">
            <i class="fas fa-<?= $item['icon'] ?> nav-icon"></i>
            <?= e($item['label']) ?>
            <?php if (($item['page'] === 'pdr') && $is_admin): ?>
              <span class="nav-badge">Admin</span>
            <?php endif; ?>
          </a>
        </div>
      <?php endif; ?>

    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= $initiales ?></div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= e($user['nom']) ?></div>
        <div class="sidebar-user-role"><?= e($user['role']) ?></div>
      </div>
      <a href="<?= APP_URL ?>/index.php?page=logout"
         style="color:rgba(255,255,255,.4); font-size:.85rem;" title="Déconnexion">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </div>
</aside>
