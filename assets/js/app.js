/* ============================================================
   TSINGY ROUGE MADAGASCAR — app.js
   ============================================================ */

'use strict';

// ---- Sidebar mobile ----
(function initSidebar() {
  const sidebar  = document.getElementById('sidebar');
  const toggle   = document.getElementById('sidebarToggle');
  const overlay  = document.getElementById('sidebarOverlay');
  if (!sidebar) return;

  const open  = () => { sidebar.classList.add('open'); overlay && (overlay.style.display = 'block'); };
  const close = () => { sidebar.classList.remove('open'); overlay && (overlay.style.display = 'none'); };

  toggle  && toggle.addEventListener('click', open);
  overlay && overlay.addEventListener('click', close);

  document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
})();

// ---- Auto-dismiss alerts après 5 secondes ----
document.querySelectorAll('.alert-dismissible').forEach(alert => {
  setTimeout(() => {
    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
    bsAlert && bsAlert.close();
  }, 5000);
});

// ---- Animations au scroll (fade-up) ----
(function initFadeObserver() {
  if (!window.IntersectionObserver) return;
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity   = '1';
        entry.target.style.transform = 'translateY(0)';
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.08 });

  document.querySelectorAll('.fade-up').forEach(el => {
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(16px)';
    el.style.transition = 'opacity .4s ease, transform .4s ease';
    observer.observe(el);
  });
})();

// ---- Confirmation suppression globale ----
document.querySelectorAll('[data-confirm]').forEach(btn => {
  btn.addEventListener('click', e => {
    if (!confirm(btn.dataset.confirm || 'Confirmer cette action ?')) {
      e.preventDefault();
    }
  });
});

// ---- Filtre de tableau côté client ----
window.filterTable = function(tableId, query) {
  const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
  const q = query.toLowerCase().trim();
  rows.forEach(row => {
    row.style.display = !q || row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
};

// ---- Chart.js defaults globaux ----
if (window.Chart) {
  Chart.defaults.font.family  = "'Plus Jakarta Sans', sans-serif";
  Chart.defaults.font.size    = 12;
  Chart.defaults.color        = '#5A6176';
  Chart.defaults.plugins.legend.labels.boxWidth = 10;
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.tooltip.backgroundColor = '#1A1D2E';
  Chart.defaults.plugins.tooltip.titleColor      = '#fff';
  Chart.defaults.plugins.tooltip.bodyColor       = 'rgba(255,255,255,.75)';
  Chart.defaults.plugins.tooltip.padding         = 10;
  Chart.defaults.plugins.tooltip.cornerRadius    = 8;
}

// ---- Utilitaires format ----
window.formatMoney = (v) =>
  new Intl.NumberFormat('fr-MG', { minimumFractionDigits: 0 }).format(v) + ' Ar';

window.formatNumber = (v) =>
  new Intl.NumberFormat('fr-FR').format(v);

// ---- Topbar : date/heure dynamique ----
(function initClock() {
  const el = document.getElementById('liveClock');
  if (!el) return;
  const update = () => {
    el.textContent = new Date().toLocaleTimeString('fr-MG', { hour: '2-digit', minute: '2-digit' });
  };
  update();
  setInterval(update, 60000);
})();

// ---- Pré-remplissage date max = aujourd'hui ----
// document.querySelectorAll('input[type="date"]').forEach(inp => {
//   if (!inp.getAttribute('max')) inp.max = new Date().toISOString().split('T')[0];
// });
// Execption pour les champs pdr
// document.querySelectorAll('.date-passee-only').forEach(inp => {
//   if (!inp.getAttribute('max')) {
//     inp.max = new Date().toISOString().split('T')[0];
//   }
// });
// ---- Sidebar overlay style ----
(function injectOverlayStyle() {
  const style = document.createElement('style');
  style.textContent = `
    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.4);
      z-index: 999;
      backdrop-filter: blur(2px);
    }
  `;
  document.head.appendChild(style);
})();
