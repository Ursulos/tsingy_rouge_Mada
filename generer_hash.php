<?php
// ============================================================
// generer_hash.php — Générateur de mot de passe
// Ouvrir dans le navigateur, copier le hash, l'insérer en base
// SUPPRIMER ce fichier après utilisation !
// ============================================================
$mdp = $_POST['mdp'] ?? '';
$hash = '';
if ($mdp) $hash = password_hash($mdp, PASSWORD_BCRYPT);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Générateur de hash</title>
<style>
body { font-family: sans-serif; max-width: 600px; margin: 60px auto; padding: 20px; }
input[type=text] { width:100%; padding:10px; font-size:1rem; border:1px solid #ccc; border-radius:6px; }
button { margin-top:10px; padding:10px 20px; background:#c0392b; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:1rem; }
.hash { margin-top:20px; padding:15px; background:#f8f8f8; border:1px solid #ddd; border-radius:6px; word-break:break-all; font-family:monospace; font-size:.85rem; }
.sql { margin-top:10px; padding:15px; background:#e8f8e8; border:1px solid #a8d8a8; border-radius:6px; font-family:monospace; font-size:.82rem; }
.warn { color:#c0392b; font-weight:bold; margin-top:20px; }
</style>
</head>
<body>
<h2>🔐 Générateur de mot de passe — Tsingy Rouge</h2>
<form method="POST">
  <label>Nouveau mot de passe :</label><br><br>
  <input type="text" name="mdp" value="<?= htmlspecialchars($mdp) ?>" placeholder="Entrez votre mot de passe" autofocus>
  <button type="submit">Générer</button>
</form>
<?php if ($hash): ?>
<div class="hash"><strong>Hash bcrypt :</strong><br><?= htmlspecialchars($hash) ?></div>
<div class="sql">
<strong>SQL à exécuter dans phpMyAdmin :</strong><br><br>
UPDATE users SET password = '<?= htmlspecialchars($hash) ?>' WHERE email = 'admin@tsingy-rouge.mg';
</div>
<?php endif; ?>
<p class="warn">⚠️ SUPPRIMEZ ce fichier après utilisation !</p>
</body>
</html>