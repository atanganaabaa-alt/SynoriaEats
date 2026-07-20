# SynoriaEats

Livraison de repas — sœur de **Synoria**, suite type Google. Style Uber Eats / EasyFood.

**Laravel 13** · Blade + Tailwind · API Sanctum · auth email/mdp **ou Google** · PostgreSQL (cible prod)

Plan détaillé : [docs/SPRINTS.md](docs/SPRINTS.md)

## Prérequis

- PHP 8.3+ avec extensions `pdo_pgsql` (prod) / `pdo_sqlite` (tests)
- Composer, Node 20+
- PostgreSQL 16+ (recommandé)

```bash
# Exemple Ubuntu
sudo apt install php8.3-pgsql postgresql
sudo -u postgres createuser -s "$USER"
createdb synoriaeats
```

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate

# Éditer .env : DB_* + GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET
php artisan migrate
php artisan storage:link
npm install && npm run build
php artisan serve
```

Dev tout-en-un : `composer run dev`

## Auth (pas de comptes démo)

- **Inscription** : nom, email, téléphone, rôle (Client / Restaurateur / Livreur), mot de passe
- **Connexion** : email + ton mot de passe
- **Continuer avec Google** (OAuth) — configure la console Google Cloud :
  - URI de redirection : `{APP_URL}/auth/google/callback`

Aucun mot de passe partagé en base. Chacun crée son compte.

## API REST (Sanctum)

| Méthode | Endpoint | Auth |
|---------|----------|------|
| POST | `/api/register` | — |
| POST | `/api/login` | — → `{ token }` |
| GET | `/api/me` | Bearer |
| POST | `/api/logout` | Bearer |
| GET | `/api/restaurants` | — |
| GET | `/api/restaurants/{slug}` | — |
| POST | `/api/restaurants` | resto / admin |
| POST | `/api/restaurants/{id}/menu-items` | resto / admin |

## Sprint 2 — livré

Panier, checkout Mobile Money (Orange / MTN), notifications, gestion commandes restaurateur, historique client.

Prochain : **Sprint 3** — livraison, suivi temps réel, évaluations.
