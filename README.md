# TSINGY ROUGE MADAGASCAR — Guide d'installation

## Prérequis

| Outil       | Version minimum |
|-------------|----------------|
| PHP         | 8.0+           |
| MySQL       | 8.0+           |
| Apache/Nginx| (HTTPS recommandé) |
| Composer    | Optionnel      |

---

## 1. Cloner / Déposer le projet

```bash
# Copier le dossier tsingy_rouge dans votre répertoire web
cp -r tsingy_rouge /var/www/html/
# ou pour WAMP/XAMPP
cp -r tsingy_rouge C:/wamp64/www/
```

---

## 2. Créer la base de données

```bash
mysql -u root -p
```

```sql
CREATE DATABASE tsingy_rouge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

```bash
mysql -u root -p tsingy_rouge < database/schema.sql
```

---

## 3. Configurer la connexion

Éditez `config/database.php` :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tsingy_rouge');
define('DB_USER', 'votre_user');
define('DB_PASS', 'votre_mot_de_passe');
```

Éditez `config/constants.php` :

```php
define('APP_URL', 'http://localhost/tsingy_rouge');
// ou : define('APP_URL', 'https://monsite.mg');
```

---

## 4. Créer les dossiers uploads

```bash
mkdir -p uploads/vendeurs uploads/produits
chmod 755 uploads uploads/vendeurs uploads/produits
```

---

## 5. Configurer Apache (Virtual Host)

```apache
<VirtualHost *:80>
    ServerName tsingy.local
    DocumentRoot /var/www/html/tsingy_rouge
    DirectoryIndex index.php

    <Directory /var/www/html/tsingy_rouge>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/tsingy_error.log
    CustomLog ${APACHE_LOG_DIR}/tsingy_access.log combined
</VirtualHost>
```

---

## 6. Connexion admin

Après installation, accédez à :

```
http://localhost/tsingy_rouge/index.php?page=login
```

**Identifiants par défaut :**

| Champ    | Valeur              |
|----------|---------------------|
| Email    | admin@tsingy-rouge.mg |
| Mot de passe | Admin@2026    |

> ⚠️ Changez le mot de passe immédiatement après la première connexion !

Pour générer un nouveau hash :
```php
echo password_hash('NouveauMotDePasse', PASSWORD_BCRYPT, ['cost' => 12]);
```

---

## 7. Structure des URLs

| Page            | URL                                              |
|-----------------|--------------------------------------------------|
| Dashboard       | `index.php?page=dashboard`                       |
| Ventes          | `index.php?page=ventes`                          |
| Nouvelle vente  | `index.php?page=ventes&action=create`            |
| Clients         | `index.php?page=clients`                         |
| Produits        | `index.php?page=produits`                        |
| Vendeurs        | `index.php?page=vendeurs`                        |
| Secteurs/Villes | `index.php?page=secteurs`                        |
| Analyses        | `index.php?page=analyses`                        |
| Comparaison     | `index.php?page=comparaison`                     |

---

## 8. Workflow recommandé

```
1. Créer les secteurs
2. Créer les villes (associées aux secteurs)
3. Créer les vendeurs (associés aux secteurs)
4. Créer les produits (T-Shirts avec stock)
5. Créer les clients (associés à ville + secteur)
6. Enregistrer les ventes
7. Consulter le dashboard + analyses
8. Utiliser la comparaison de périodes
```

---

## 9. Fonctionnalités clés

### Clients VIP (> 300 pièces/mois)
Le système détecte automatiquement les clients ayant acheté plus de 300 pièces
dans le mois courant. Ils apparaissent :
- En alerte dorée sur le dashboard
- En alerte dorée sur la page clients
- Dans la vue `v_top_clients_mensuel` (MySQL)

Pour ajuster le seuil, modifiez dans `config/constants.php` :
```php
define('CLIENT_TOP_SEUIL', 300);
```

### Comparaison de périodes
Accédez à `index.php?page=comparaison` et configurez jusqu'à 5 intervalles.
Vous pouvez filtrer par vendeur, secteur ou ville pour des comparaisons ciblées.

### Alertes stock
Le seuil d'alerte par produit est défini dans le champ `stock_min`.
Les produits en dessous de ce seuil apparaissent dans :
- La topbar (badge rouge)
- Le dashboard (carte stock faible)
- La page produits (badge statut)

---

## 10. Sécurité

- **SQL Injection** : toutes les requêtes utilisent PDO + requêtes préparées
- **XSS** : toutes les sorties passent par `htmlspecialchars()` via `e()`
- **CSRF** : token sur tous les formulaires POST
- **Sessions** : `session_start()` avec vérification de rôle
- **Uploads** : validation type MIME + taille (2MB max) + nom aléatoire
- **Mots de passe** : hashés avec `password_hash()` (BCRYPT, cost=12)

---

## 11. Extension future

| Fonctionnalité | Fichier à créer                    |
|----------------|------------------------------------|
| Export PDF     | `helpers/export_pdf.php` (mPDF)    |
| Export Excel   | `helpers/export_excel.php` (PhpSpreadsheet) |
| API REST       | `api/` (routes JSON)               |
| Notifications  | `helpers/notifications.php`        |
| Multi-langue   | `lang/fr.php`, `lang/mg.php`       |

---

## 12. Arborescence finale

```
tsingy_rouge/
├── index.php                ← Point d'entrée
├── config/
│   ├── database.php
│   └── constants.php
├── helpers/
│   └── functions.php
├── models/
│   ├── DashboardModel.php
│   ├── VenteModel.php
│   └── Models.php           ← Vendeur/Client/Produit/Secteur/Ville
├── views/
│   ├── auth/login.php
│   ├── layouts/             ← header/sidebar/topbar/footer
│   ├── dashboard/index.php
│   ├── ventes/index.php
│   ├── clients/index.php
│   ├── produits/index.php
│   ├── vendeurs/index.php
│   ├── secteurs/index.php
│   ├── villes/index.php
│   ├── analyses/index.php
│   └── comparaison/index.php
├── assets/
│   ├── css/app.css
│   └── js/app.js
├── uploads/
│   ├── vendeurs/
│   └── produits/
└── database/
    └── schema.sql
```

---

*Tsingy Rouge Madagascar · v1.0.0 · PHP natif + MySQL + Bootstrap 5*
"# tsingy_rouge_Mada" 
