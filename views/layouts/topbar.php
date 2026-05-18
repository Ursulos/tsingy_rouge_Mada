<?php
// views/layouts/topbar.php
$db_model = new DashboardModel();
$stock_faible_count = count($db_model->getStockFaible());
?>
<header class="topbar">
  <!-- Toggle sidebar mobile -->
  <button class="topbar-btn d-lg-none" id="sidebarToggle" aria-label="Menu">
    <i class="fas fa-bars"></i>
  </button>

  <div class="topbar-title">
    <?= isset($page_title) ? '<span>' . e($page_title) . '</span>' : APP_NAME ?>
  </div>

  <div class="topbar-actions">
    <!-- Alerte stock -->
    <?php if ($stock_faible_count > 0): ?>
    <a href="<?= APP_URL ?>/index.php?page=produits" class="topbar-btn topbar-badge-btn" title="<?= $stock_faible_count ?> produit(s) en stock faible" style="color:var(--warning);">
      <i class="fas fa-boxes"></i>
      <span class="topbar-badge"><?= $stock_faible_count ?></span>
    </a>
    <?php endif; ?>

    <!-- Date courante -->
    <div style="font-size:.75rem; color:var(--text-muted); display:flex; align-items:center; gap:6px; padding:0 8px;">
      <i class="fas fa-calendar-alt" style="color:var(--primary);"></i>
      <?= date('d/m/Y') ?>
    </div>

    <!-- Déconnexion -->
    <a href="<?= APP_URL ?>/index.php?page=logout" class="topbar-btn" title="Déconnexion">
      <i class="fas fa-sign-out-alt"></i>
    </a>
  </div>
</header>
