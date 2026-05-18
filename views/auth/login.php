<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — Tsingy Rouge Madagascar</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
  <style>
    .login-page {
      min-height: 100vh;
      display: flex;
      background: #F5F6FA;
    }

    .login-left {
      width: 480px;
      background: linear-gradient(160deg, #1A1D2E 0%, #0E1018 100%);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px 52px;
      position: relative;
      overflow: hidden;
      flex-shrink: 0;
    }

    .login-left::before {
      content: '';
      position: absolute;
      width: 400px; height: 400px;
      background: var(--primary);
      border-radius: 50%;
      top: -180px; right: -180px;
      opacity: .12;
    }

    .login-left::after {
      content: '';
      position: absolute;
      width: 300px; height: 300px;
      background: var(--accent);
      border-radius: 50%;
      bottom: -120px; left: -80px;
      opacity: .07;
    }

    .login-brand {
      display: flex; align-items: center; gap: 14px;
      margin-bottom: 48px;
      position: relative; z-index: 1;
    }

    .login-brand-icon {
      width: 50px; height: 50px;
      background: var(--primary);
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem; color: #fff;
      box-shadow: 0 8px 24px rgba(192,57,43,.4);
    }

    .login-brand-name {
      font-family: var(--font-display);
      font-size: 1.15rem;
      color: #fff;
      line-height: 1.2;
    }

    .login-brand-sub {
      font-size: .68rem;
      color: rgba(255,255,255,.35);
      text-transform: uppercase;
      letter-spacing: .1em;
    }

    .login-tagline {
      position: relative; z-index: 1;
    }

    .login-tagline h2 {
      font-family: var(--font-display);
      font-size: 2rem;
      color: #fff;
      line-height: 1.25;
      margin-bottom: 12px;
    }

    .login-tagline h2 span { color: var(--primary-light); }

    .login-tagline p {
      font-size: .84rem;
      color: rgba(255,255,255,.45);
      line-height: 1.7;
    }

    .login-features {
      margin-top: 40px;
      position: relative; z-index: 1;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .login-feature {
      display: flex; align-items: center; gap: 12px;
      font-size: .8rem;
      color: rgba(255,255,255,.55);
    }

    .login-feature i {
      width: 28px; height: 28px;
      background: rgba(255,255,255,.07);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      color: var(--primary-light);
      font-size: .75rem;
      flex-shrink: 0;
    }

    .login-right {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px;
    }

    .login-form-box {
      width: 100%;
      max-width: 420px;
    }

    .login-form-box h1 {
      font-size: 1.55rem;
      font-weight: 800;
      color: var(--text-primary);
      margin-bottom: 6px;
    }

    .login-form-box p {
      font-size: .83rem;
      color: var(--text-muted);
      margin-bottom: 32px;
    }

    .login-input-group {
      position: relative;
      margin-bottom: 16px;
    }

    .login-input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: .85rem;
      pointer-events: none;
    }

    .login-input-group .form-control {
      padding-left: 40px;
      height: 46px;
      border-radius: 10px;
    }

    .login-btn {
      width: 100%;
      height: 46px;
      background: var(--primary);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: .9rem;
      font-weight: 700;
      cursor: pointer;
      transition: var(--transition);
      box-shadow: 0 4px 16px rgba(192,57,43,.3);
      margin-top: 8px;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }

    .login-btn:hover {
      background: var(--primary-dark);
      box-shadow: 0 6px 20px rgba(192,57,43,.4);
      transform: translateY(-1px);
    }

    .login-footer {
      text-align: center;
      margin-top: 28px;
      font-size: .75rem;
      color: var(--text-muted);
    }

    @media (max-width: 768px) {
      .login-left { display: none; }
    }
  </style>
</head>
<body style="margin:0;padding:0;">

<div class="login-page">
  <!-- Côté gauche décoratif -->
  <div class="login-left">
    <div class="login-brand">
      <div class="login-brand-icon"><i class="fas fa-tshirt"></i></div>
      <div>
        <div class="login-brand-name">Tsingy Rouge</div>
        <div class="login-brand-sub">Madagascar</div>
      </div>
    </div>

    <div class="login-tagline">
      <h2>Gérez vos ventes <span>intelligemment</span></h2>
      <p>Plateforme complète de gestion commerciale pour votre boutique de T-Shirts.</p>
    </div>

    <div class="login-features">
      <div class="login-feature">
        <i class="fas fa-chart-line"></i>
        <span>Analyses de performances en temps réel</span>
      </div>
      <div class="login-feature">
        <i class="fas fa-users"></i>
        <span>Suivi vendeurs & classements automatiques</span>
      </div>
      <div class="login-feature">
        <i class="fas fa-balance-scale"></i>
        <span>Comparaison de périodes avancée</span>
      </div>
      <div class="login-feature">
        <i class="fas fa-crown"></i>
        <span>Meilleurs clients >300 pièces/mois détectés</span>
      </div>
    </div>
  </div>

  <!-- Formulaire -->
  <div class="login-right">
    <div class="login-form-box fade-up">
      <h1>Connexion</h1>
      <p>Entrez vos identifiants pour accéder à votre espace.</p>

      <?php if (!empty($login_error)): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-exclamation-triangle"></i>
        <?= e($login_error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="">
        <?= csrf_field() ?>

        <div class="login-input-group">
          <i class="fas fa-envelope login-input-icon"></i>
          <input type="email" name="email" class="form-control"
                 placeholder="Adresse email"
                 value="<?= e($_POST['email'] ?? '') ?>"
                 required autofocus>
        </div>

        <div class="login-input-group">
          <i class="fas fa-lock login-input-icon"></i>
          <input type="password" name="password" class="form-control"
                 placeholder="Mot de passe" required>
        </div>

        <button type="submit" class="login-btn">
          <i class="fas fa-sign-in-alt"></i>
          Se connecter
        </button>
      </form>

      <div class="login-footer">
        &copy; <?= date('Y') ?> Tsingy Rouge Madagascar · v<?= APP_VERSION ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
