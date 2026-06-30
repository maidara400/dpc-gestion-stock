# DPC Gestion Stock Padel — Guide d'installation

## Prérequis
- PHP 8.1+ avec extensions : pdo_sqlite, mbstring, openssl, tokenizer, xml, ctype, json
- Composer 2.x
- Node.js 18+ et npm

---

## Installation rapide (Windows)

### 1. Installer les dépendances PHP
```bash
composer install
```

### 2. Configurer l'environnement
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Créer et initialiser la base de données SQLite
```bash
# Créer le fichier SQLite
echo "" > database/database.sqlite

# Lancer les migrations + données de test
php artisan migrate --seed
```

### 4. Installer les dépendances JS et compiler les assets
```bash
npm install
npm run build
```

### 5. Lancer le serveur de développement
```bash
# Terminal 1 — Backend Laravel
php artisan serve

# Terminal 2 — Frontend Vite (mode dev avec hot reload)
npm run dev
```

L'application est accessible sur : **http://localhost:8000** (ou **http://localhost:5173** en dev Vite)

---

## Comptes de test (créés par le seeder)

| Rôle        | Email                   | Mot de passe     |
|-------------|-------------------------|------------------|
| Super Admin | admin@padel.club        | Admin@2024!      |
| Caissier    | caissier@padel.club     | Caissier@2024!   |

---

## Architecture des fichiers

```
├── app/
│   ├── Http/Controllers/Api/    ← Tous les contrôleurs API REST
│   ├── Http/Middleware/         ← CheckRole (admin/caissier)
│   ├── Models/                  ← User, Product, Category, Sale, StockMovement, WebhookConfig
│   ├── Providers/               ← ServiceProviders Laravel
│   └── Services/
│       ├── StockService.php     ← Logique métier (entrée dépôt, transfert)
│       └── WebhookService.php   ← Dispatcher Guzzle + signature HMAC
├── database/
│   ├── migrations/              ← 7 migrations (users → webhook_configs)
│   └── seeders/                 ← Données de démo (catégories, produits, utilisateurs)
├── resources/js/
│   ├── app.js                   ← Bootstrap Vue + Axios + Pinia
│   ├── router/                  ← Vue Router (guards auth + role)
│   ├── stores/auth.js           ← Pinia auth store
│   ├── assets/app.css           ← Tailwind + composants CSS
│   └── views/
│       ├── LoginView.vue
│       ├── LayoutView.vue       ← Sidebar + navigation
│       ├── DashboardView.vue    ← Tableau de bord (stats + alertes)
│       ├── CaisseView.vue       ← Interface caissier (vente rapide)
│       ├── InventaireView.vue   ← Entrée dépôt + transferts
│       ├── ProduitsView.vue     ← Gestion CRUD produits
│       ├── VentesView.vue       ← Historique + analytics
│       └── ConfigurationView.vue ← Catégories + Webhooks + Utilisateurs
└── routes/
    ├── api.php                  ← Toutes les routes API REST
    └── web.php                  ← Catch-all SPA
```

---

## API REST — Résumé des endpoints

### Authentification
| Méthode | Endpoint          | Description                        |
|---------|-------------------|------------------------------------|
| POST    | /api/auth/login   | Connexion → retourne Bearer token  |
| POST    | /api/auth/logout  | Déconnexion                        |
| GET     | /api/auth/me      | Utilisateur courant                |

### Produits (admin + caissier : GET, admin : POST/PUT/DELETE)
| Méthode | Endpoint               | Description                             |
|---------|------------------------|-----------------------------------------|
| GET     | /api/products          | Liste produits (filtres: search, category_id, alert) |
| POST    | /api/products          | Créer produit                           |
| PUT     | /api/products/{id}     | Modifier produit                        |
| DELETE  | /api/products/{id}     | Archiver produit                        |

### Stock (admin uniquement)
| Méthode | Endpoint                         | Description                    |
|---------|----------------------------------|--------------------------------|
| POST    | /api/stock/add-depot             | Entrée dépôt (achat fournisseur)|
| POST    | /api/stock/transfer-to-boutique  | Transfert dépôt → boutique     |
| GET     | /api/stock/movements             | Historique mouvements          |

### Ventes
| Méthode | Endpoint             | Description                                    |
|---------|----------------------|------------------------------------------------|
| POST    | /api/sales           | Enregistrer vente (décrémente stock boutique)  |
| GET     | /api/sales/history   | Historique filtrable (period, from, to, category_id) |

### Dashboard / Catégories / Webhooks / Utilisateurs
- `GET /api/dashboard` — Stats globales + alertes
- `GET|POST|PUT|DELETE /api/categories`
- `GET|POST|PUT|DELETE /api/webhooks`
- `GET|POST|PUT|DELETE /api/users`

---

## Webhooks sortants (événements)

L'application envoie un POST JSON vers les URLs configurées sur ces événements :

### `stock.alert`
```json
{
  "event": "stock.alert",
  "product_id": 12,
  "name": "Canette Perrier",
  "location": "boutique",
  "current_stock": 3,
  "threshold": 5
}
```

### `sale.created`
```json
{
  "event": "sale.created",
  "sale_id": 42,
  "product_id": 12,
  "product_name": "Canette Perrier",
  "quantity": 2,
  "unit_price": 2.00,
  "total_price": 4.00,
  "stock_boutique_after": 1,
  "cashier": "Caissier Equipe"
}
```

### `stock.moved`
```json
{
  "event": "stock.moved",
  "movement_type": "transfer",
  "product_id": 12,
  "name": "Canette Perrier",
  "quantity": 10,
  "stock_depot_after": 26,
  "stock_boutique_after": 11
}
```

**Signature HMAC (optionnel) :** si un `secret` est configuré, l'en-tête `X-Webhook-Signature: sha256=<HMAC_SHA256(body, secret)>` est inclus dans chaque requête.

---

## Migration vers MySQL/PostgreSQL (production)

Dans `.env`, remplacez :
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=padel_stock
DB_USERNAME=root
DB_PASSWORD=secret
```

Puis relancez :
```bash
php artisan migrate --seed
```

Aucune modification du code n'est nécessaire (Eloquent ORM abstrait le moteur).

---

## Résolution des problèmes courants

**Erreur 500 sur les routes API :** Vérifiez que `APP_KEY` est définie dans `.env` (`php artisan key:generate`)

**Erreur SQLite "unable to open database file" :** Assurez-vous que `database/database.sqlite` existe et est accessible en écriture.

**CORS en développement Vite :** Le proxy Vite (`vite.config.js`) redirige `/api/*` vers Laravel sur le port 8000. Lancez les deux serveurs en parallèle.
