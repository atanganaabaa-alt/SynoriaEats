# SynoriaEats — lancer l’app en local

Guide des commandes pour démarrer, relancer et dépanner SynoriaEats sur ta machine.  
À garder à jour quand le setup local change.

**Dernière mise à jour :** 23 août 2026 (sprints 1–9 ✅, compagnon + guide local).

---

## 1. Prérequis

| Outil | Version cible |
|-------|----------------|
| PHP | 8.3+ |
| Composer | 2.x |
| Node.js | 20+ (22 OK) |
| npm | fourni avec Node |

Extensions PHP utiles : `pdo_sqlite` (dev simple), `pdo_pgsql` (Postgres), `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`.

```bash
php -v
composer -V
node -v
npm -v
```

---

## 2. Première installation

Depuis la racine du projet (`/home/sara/Projects/SynoriaEats`) :

```bash
cd /home/sara/Projects/SynoriaEats

composer install
cp .env.example .env
php artisan key:generate
```

### Base de données — option A : SQLite (recommandé en local)

Dans `.env` :

```env
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite
# laisse DB_HOST / DB_PORT / DB_USERNAME / DB_PASSWORD commentés ou vides
# Laravel utilisera database/database.sqlite
```

Puis :

```bash
touch database/database.sqlite
php artisan migrate
php artisan storage:link
npm install
npm run build
```

### Base de données — option B : PostgreSQL

```bash
sudo apt install php8.3-pgsql postgresql   # si besoin
sudo -u postgres createuser -s "$USER"     # une fois
createdb synoriaeats
```

Dans `.env` :

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=synoriaeats
DB_USERNAME=ton_user
DB_PASSWORD=
```

Puis :

```bash
php artisan migrate
php artisan storage:link
npm install
npm run build
```

---

## 3. Lancer l’app au quotidien

### Option simple (2 terminaux)

**Terminal 1 — serveur Laravel**

```bash
cd /home/sara/Projects/SynoriaEats
php artisan serve
```

→ [http://127.0.0.1:8000](http://127.0.0.1:8000)

**Terminal 2 — Vite (CSS/JS hot reload)** — utile si tu modifies les vues/assets

```bash
cd /home/sara/Projects/SynoriaEats
npm run dev
```

Si tu ne touches pas au front, `npm run build` une fois suffit ; tu peux ne garder que `php artisan serve`.

### Option tout-en-un

```bash
composer run dev
```

Lance en parallèle : `artisan serve`, queue, logs (`pail`), Vite.

### Arrêter

- `Ctrl+C` dans le(s) terminal(aux)
- Vérifier qu’aucun vieux `artisan serve` ne traîne :

```bash
pkill -f "artisan serve" || true
```

---

## 4. Compte admin (login)

Il n’y a **pas** d’admin magique livré avec le code : le mot de passe n’est jamais stocké en clair, et aucun seed ne crée un admin par défaut.

### Créer ou réinitialiser un admin

```bash
php artisan synoria:admin admin@synoria.test 'Admin123!' --name="Admin Synoria"
```

| Champ | Valeur (exemple local) |
|-------|-------------------------|
| URL | http://127.0.0.1:8000/login |
| Email | `admin@synoria.test` |
| Mot de passe | celui que tu passes à `synoria:admin` (ex. `Admin123!`) |

La commande **crée** le compte s’il n’existe pas, ou **met à jour** le mot de passe / rôle s’il existe déjà (min. 8 caractères).

Ensuite : menu **Admin** → dashboard, restos, livreurs, audit commandes (`/admin/orders`).

### Voir les admins déjà en base

```bash
php artisan tinker --execute="echo App\Models\User::query()->where('role','admin')->pluck('email');"
```

Tu ne peux **pas** retrouver un ancien mot de passe : il est hashé. Il faut le **réécrire** avec `synoria:admin`.

### Après `migrate:fresh`

La base est vide → recrée l’admin :

```bash
php artisan migrate:fresh
php artisan synoria:admin admin@synoria.test 'Admin123!' --name="Admin Synoria"
```

---

## 5. Config locale utile (`.env`)

Valeurs typiques en dev :

```env
APP_URL=http://127.0.0.1:8000
APP_DEBUG=true

# Notifications dans storage/logs (pas de vrai SMS)
SYNORIA_NOTIFICATION_CHANNELS=log
SYNORIA_PAYMENTS_SANDBOX=true

# Compagnon culinaire (Sprint 9) — sans clé = moteur local
SYNORIA_COMPANION_ENABLED=true
OPENAI_API_KEY=
```

Après changement de `.env` :

```bash
php artisan config:clear
```

### Google OAuth (optionnel)

```bash
php artisan synoria:google "CLIENT_ID" "CLIENT_SECRET"
```

Détails : [docs/GOOGLE_OAUTH.md](GOOGLE_OAUTH.md)

---

## 6. Migrations & reset

```bash
# Appliquer les nouvelles migrations
php artisan migrate

# Tout remettre à zéro (⚠️ efface les données)
php artisan migrate:fresh

# Recréer admin après un fresh
php artisan synoria:admin admin@synoria.test 'Admin123!' --name="Admin Synoria"
```

---

## 7. Caches / vues (si page bizarre après un pull)

```bash
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

Assets front :

```bash
npm run build
# ou en live :
npm run dev
```

---

## 8. Tests (si PHPUnit est installé)

```bash
composer install   # pour avoir vendor/bin/phpunit si manquant
php artisan test
# ou un sprint ciblé :
php artisan test --filter=Sprint9CompanionTest
```

---

## 9. Parcours de test multi-rôles

Chrome et Firefox = **sessions séparées** (idéal).

| Navigateur | Rôle |
|------------|------|
| Chrome | Client |
| Firefox | Livreur (ou admin) |

Boutons utiles dans la nav : **Déconnexion** / **Changer de compte**.

Ordre typique :

1. Créer admin (`synoria:admin`)
2. Inscrire un **restaurateur** → admin approuve le resto
3. Restaurateur ajoute des plats
4. Créer un **livreur** depuis Admin → l’approuver
5. Client commande → resto avance les statuts → livreur prend la mission
6. Tester carte live, timeline (Sprint 8), compagnon (Sprint 9)

Pages utiles :

| URL | Rôle |
|-----|------|
| `/restaurants` | Catalogue + compagnon |
| `/companion` | Chat compagnon |
| `/selection` | Préférences de tri restos |
| `/admin` | Dashboard admin |
| `/admin/orders` | Audit commandes |
| `/owner/orders` | Commandes resto |
| `/courier/missions` | Missions livreur |

### Compagnon (Sprint 9)

Sur catalogue / fiche resto / commande : bouton **Compagnon** (bas gauche), ou page `/companion`.

Exemples :
- `J’ai 5000 FCFA`
- `Que me recommandes-tu ?`
- `Combien de temps d’attente ?`

Sans `OPENAI_API_KEY` → réponses locales basées sur le menu réel. Avec clé → mode IA + fallback local.

---

## 10. Dépannage rapide

| Problème | Commande / fix |
|----------|----------------|
| Page blanche / ancienne vue | `php artisan view:clear` |
| `.env` ignoré | `php artisan config:clear` |
| CSS cassé | `npm run build` ou `npm run dev` |
| `APP_URL` incorrect | mettre `http://127.0.0.1:8000` (pas d’IP inversée) |
| Port 8000 pris | `php artisan serve --port=8001` |
| Migration manquante | `php artisan migrate` |
| Compagnon muet | recharger la page ; vérifier Alpine/Vite chargé |
| Déconnexion impossible | utiliser **Changer de compte** dans la nav |

Logs :

```bash
tail -f storage/logs/laravel.log
```

---

## 11. Après un `git pull`

```bash
cd /home/sara/Projects/SynoriaEats
composer install
npm install
php artisan migrate
npm run build
php artisan view:clear
php artisan config:clear
php artisan serve
```

---

## Voir aussi

- Plan des sprints : [SPRINTS.md](SPRINTS.md)
- Google OAuth : [GOOGLE_OAUTH.md](GOOGLE_OAUTH.md)
- Déploiement o2switch : [DEPLOY_O2SWITCH.md](DEPLOY_O2SWITCH.md)
- Déploiement VPS : [DEPLOY_VPS.md](DEPLOY_VPS.md)
