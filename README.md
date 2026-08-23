# SynoriaEats

Livraison de repas — sœur de **Synoria**, suite type Google. Style Uber Eats / EasyFood.

**Laravel 13** · Blade + Tailwind · API Sanctum · auth email/mdp **ou Google** · PostgreSQL (cible prod)

**Sprints 1 → 9 livrés** (auth, commande, livraison, admin, matching, carte live, audit, compagnon).

Plan détaillé : [docs/SPRINTS.md](docs/SPRINTS.md)  
**Lancer en local (commandes) :** [docs/LOCAL.md](docs/LOCAL.md)

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

> **Pas de données démo** : la base démarre vide. Seuls les restaurants créés par de vrais restaurateurs apparaissent, et uniquement après ajout d’au moins un plat au menu. Pour repartir de zéro : `php artisan migrate:fresh`.

Dev tout-en-un : `composer run dev`

## Auth (pas de comptes démo)

- **Inscription** : Client ou Restaurateur (email/mdp ou Google). **Pas d’inscription livreur libre.**
- **Restaurateur** : RCCM + pièce d’identité à l’inscription. Compte `en attente` jusqu’à validation admin.
- **Livreur** : créé par l’admin (partenariat) puis approuvé. Ensuite connexion email/mdp ou Google (même email).
- **Connexion** : email + mot de passe, ou **Continuer avec Google**
- URI Google : `{APP_URL}/auth/google/callback`

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

Panier, checkout Mobile Money (Orange / MTN), notifications SMS/WhatsApp (Twilio), gestion commandes restaurateur, historique client.

### Notifications (Twilio)

En dev, les messages partent dans les logs Laravel :

```env
SYNORIA_NOTIFICATION_CHANNELS=log
```

En prod, SMS/WhatsApp (Twilio) et/ou SMS Orange :

```env
SYNORIA_NOTIFICATION_CHANNELS=orange_sms,whatsapp
TWILIO_ACCOUNT_SID=AC...
TWILIO_AUTH_TOKEN=...
TWILIO_SMS_FROM=+1415...
TWILIO_WHATSAPP_FROM=14155238886
ORANGE_SMS_CLIENT_ID=...
ORANGE_SMS_CLIENT_SECRET=...
ORANGE_SMS_SENDER=2370000
```

Pour WhatsApp sandbox Twilio, le client doit d’abord envoyer « join … » au numéro sandbox depuis son téléphone.

### Restaurateur — ajouter des plats

1. S’inscrire **Restaurateur** + déposer RCCM et pièce d’identité
2. Attendre l’approbation admin (`/admin/restaurants`)
3. Menu **Mon resto** → ajouter les plats

## Sprint 5 — livré

Auth renforcée : Google prioritaire, preuves restaurateur, plus d’inscription livreur libre, middleware `approved`.

## Sprint 3 — livré

Livraison (missions livreur), suivi GPS (polling), frais selon distance, notations resto/livreur.

Parcours :
1. Restaurateur marque la commande **Prête**
2. Livreur → **Missions** → prendre → récupérer → livrer (+ partage GPS)
3. Client suit le statut / position sur la fiche commande
4. Après livraison → noter resto + livreur

```env
SYNORIA_DELIVERY_FEE_PER_KM=200
SYNORIA_DELIVERY_MIN_FEE=0
```

## Sprint 4 — livré

Back-office admin : dashboard CA/livraisons/notes, suspendre comptes, valider restos, rapport commissions, filtres catalogue.

```bash
php artisan synoria:admin admin@synoria.test 'Admin123!'
# puis /admin — détails : docs/LOCAL.md
```

## Sprint 9 — livré

Compagnon culinaire (`/companion` + widget) : budget, menu, temps d’attente. Moteur local sans clé ; OpenAI optionnel via `OPENAI_API_KEY`.

## Docs

- Lancer en local : [docs/LOCAL.md](docs/LOCAL.md)
- Plan sprints : [docs/SPRINTS.md](docs/SPRINTS.md)
- Déploiement VPS : [docs/DEPLOY_VPS.md](docs/DEPLOY_VPS.md)  
- Déploiement **o2switch** : [docs/DEPLOY_O2SWITCH.md](docs/DEPLOY_O2SWITCH.md)
