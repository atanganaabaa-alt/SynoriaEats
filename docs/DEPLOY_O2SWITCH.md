# Déploiement SynoriaEats sur o2switch (1ʳᵉ version)

Guide **pas à pas** pour mettre en ligne SynoriaEats sur un hébergement **mutualisé o2switch** (cPanel).

Références utiles o2switch :
- [Accès SSH](https://faq.o2switch.fr/)
- [Installer Composer](https://faq.o2switch.fr/guides/php/installer-composer/)
- Bases MySQL / PostgreSQL via les assistants cPanel

> SynoriaEats exige **PHP ≥ 8.3**. Sur o2switch, MySQL est le plus simple pour la v1 (PostgreSQL est aussi proposé si tu préfères rester aligné avec le `.env.example`).

---

## Vue d’ensemble

```
Ton PC ──git push──► GitHub
                         │
o2switch ◄──git pull─────┘
   │
   ├── ~/synoriaeats/          ← code Laravel (hors web)
   │     ├── app/, routes/, …
   │     ├── public/           ← seul dossier exposé
   │     └── .env              ← secrets (jamais dans Git)
   │
   └── public_html/  ──symlink──► ~/synoriaeats/public
```

**Règle d’or :** le document root du site doit pointer vers le dossier `public/` de Laravel, **pas** la racine du projet.

---

## 0. Prérequis avant de commencer

### Chez toi
- [ ] Repo à jour sur GitHub (`master` / tag `sprint-4`)
- [ ] Compte o2switch + domaine (ex. `synoriaeats.fr` ou sous-domaine)
- [ ] Accès cPanel (identifiants du mail « Bienvenue chez o2switch »)
- [ ] (Recommandé) Client SSH : Terminal mac/Linux, ou PuTTY / Windows Terminal

### Sur o2switch (à vérifier dans cPanel)
- [ ] **Select PHP Version** → **8.3** (ou 8.4 si dispo)
- [ ] Extensions PHP cochées : `pdo`, `mysqlnd` **ou** `pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `curl`, `zip`, `intl`
- [ ] **Autorisation SSH** : ton IP publique en liste blanche

Trouver ton IP : [https://ifconfig.me](https://ifconfig.me) puis cPanel → **Sécurité** → **Autorisation SSH**.

---

## 1. Domaine & SSL

1. cPanel → **Domaines** / **Sous-domaines**  
   - Domaine principal : `public_html`  
   - Ou sous-domaine : `app.tondomaine.fr` → dossier dédié (ex. `app.tondomaine.fr`)

2. cPanel → **SSL/TLS Status** (Let’s Encrypt)  
   - Active **AutoSSL** / Let’s Encrypt sur le domaine  
   - Attends que `https://…` fonctionne (souvent quelques minutes)

Note l’URL finale, ex. : `https://synoriaeats.fr`

---

## 2. Base de données (MySQL — recommandé v1)

1. cPanel → **Bases de données MySQL**
2. Créer une base : `identifiant_synoria` (o2switch préfixe souvent ton user)
3. Créer un utilisateur MySQL + mot de passe **fort**
4. **Ajouter l’utilisateur à la base** avec **TOUS LES PRIVILÈGES**
5. Noter :
   - Host : en général `localhost`
   - Nom DB : `xxx_synoria`
   - User : `xxx_user`
   - Password : `…`

> **PostgreSQL** (option) : cPanel → PostgreSQL Databases / phpPgAdmin. Même logique. Dans `.env` utilise `DB_CONNECTION=pgsql` et le port `5432`.

---

## 3. Accès SSH

### Option A — Terminal cPanel
cPanel → **Terminal** (après whitelist IP).

### Option B — depuis ton PC
```bash
ssh TON_USER@TON_SERVEUR.o2switch.net
# même login / mdp que cPanel, port 22
```

Vérifie PHP :
```bash
php -v          # doit afficher 8.3.x ou +
which composer  # souvent déjà présent
composer -V
```

Si `composer` manque, suis la [FAQ Composer o2switch](https://faq.o2switch.fr/guides/php/installer-composer/).

---

## 4. Clé SSH GitHub (pour `git clone` / `git pull`)

Sur le serveur :
```bash
ssh-keygen -t ed25519 -C "o2switch-synoriaeats" -f ~/.ssh/id_ed25519 -N ""
cat ~/.ssh/id_ed25519.pub
```

1. Copie la clé publique  
2. GitHub → repo **SynoriaEats** → **Settings** → **Deploy keys** → Add key (lecture seule OK)  
   *ou* ton compte GitHub → SSH keys

Test :
```bash
ssh -T git@github.com
# Hi atanganaabaa-alt! You've successfully authenticated...
```

---

## 5. Cloner le projet (hors de public_html)

```bash
cd ~
git clone git@github.com:atanganaabaa-alt/SynoriaEats.git synoriaeats
cd ~/synoriaeats
git checkout master
# optionnel : git checkout sprint-4
```

Structure attendue :
```text
~/synoriaeats/app
~/synoriaeats/public/index.php
~/synoriaeats/composer.json
```

---

## 6. Brancher le site web sur `public/`

### Si le site utilise `public_html` (domaine principal)

**Attention :** sauvegarde d’abord ce qu’il y a dans `public_html`.

```bash
cd ~
# Sauvegarde éventuelle
mv public_html public_html_backup_$(date +%Y%m%d) 2>/dev/null || true

# Lien symbolique : public_html → Laravel public/
ln -s ~/synoriaeats/public ~/public_html
```

Vérifie :
```bash
ls -la ~/public_html
# doit montrer index.php de Laravel
```

### Si sous-domaine (ex. dossier `app.tondomaine.fr`)

Dans cPanel, pointe le document root du sous-domaine vers :
```text
/home/TON_USER/synoriaeats/public
```
(ou crée un symlink équivalent).

---

## 7. Fichier `.env` de production

```bash
cd ~/synoriaeats
cp .env.example .env
nano .env   # ou vim / éditeur cPanel File Manager
```

Contenu **minimal v1** (adapte les valeurs) :

```env
APP_NAME=SynoriaEats
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://TON-DOMAINE.fr

APP_LOCALE=fr
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=xxx_synoria
DB_USERNAME=xxx_user
DB_PASSWORD=MOT_DE_PASSE_FORT

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@TON-DOMAINE.fr"
MAIL_FROM_NAME="${APP_NAME}"

# Google OAuth (optionnel v1)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://TON-DOMAINE.fr/auth/google/callback

# Paiements : sandbox OK pour première version publique de test
SYNORIA_PAYMENTS_SANDBOX=true
SYNORIA_COMMISSION_RATE=0.10

# Notifs : logs serveur (pas de SMS/WhatsApp tant que pas de clés)
SYNORIA_NOTIFICATION_CHANNELS=log

# Livraison
SYNORIA_DELIVERY_FEE_PER_KM=200
SYNORIA_DELIVERY_MIN_FEE=500
SYNORIA_DELIVERY_MAX_FEE=5000

# Cloudinary (fortement recommandé en prod pour les photos menu)
CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
CLOUDINARY_UPLOAD_PRESET=
CLOUDINARY_FOLDER=synoriaeats
```

Points importants :
- **`APP_DEBUG=false`** en prod (sinon fuite d’infos)
- **`QUEUE_CONNECTION=sync`** : plus simple sur mutualisé (pas de worker permanent)
- **`SYNORIA_PAYMENTS_SANDBOX=true`** tant que tu n’as pas les clés Orange/MTN réelles

---

## 8. Installer les dépendances PHP

```bash
cd ~/synoriaeats

# Force parfois nécessaire sur mutualisé
composer install --no-dev --optimize-autoloader --no-interaction

php artisan key:generate
```

Si erreur mémoire Composer :
```bash
php -d memory_limit=512M $(which composer) install --no-dev --optimize-autoloader
```

---

## 9. Assets front (CSS/JS Vite)

### Méthode A — build en local puis commit/upload (simple)

Sur ton PC :
```bash
npm ci
npm run build
git add public/build
git commit -m "Build assets production"
git push
```

Sur o2switch :
```bash
cd ~/synoriaeats && git pull
```

### Méthode B — build sur le serveur

Si Node/npm est installé (sinon installe un Node binaire userland, FAQ communautaire o2switch) :
```bash
cd ~/synoriaeats
npm ci
npm run build
```

Sans `public/build/manifest.json`, le site sera **sans styles**.

---

## 10. Permissions, storage, migrations

```bash
cd ~/synoriaeats

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

php artisan storage:link
php artisan migrate --force

# Compte admin (hors inscription publique)
php artisan synoria:admin admin@TON-DOMAINE.fr 'ChoisisUnMotDePasseFort!' --name="Admin"
```

Puis caches prod :
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 11. Cron Laravel (obligatoire)

cPanel → **Cron Jobs** → une fois par minute :

```bash
* * * * * /usr/local/bin/php /home/TON_USER/synoriaeats/artisan schedule:run >> /dev/null 2>&1
```

Adapte le chemin `php` si besoin :
```bash
which php
```

---

## 12. Vérifications navigateur

1. `https://TON-DOMAINE.fr` → page d’accueil SynoriaEats  
2. `/register` → créer un **Client** et un **Restaurateur**  
3. `/login` → admin créé à l’étape 10  
4. Admin → valider le restaurant  
5. Ajouter plats / boissons / accompagnements  
6. Passer une commande (paiement sandbox)

### Google OAuth (si activé)
Console Google Cloud → URI de redirection autorisée :
```text
https://TON-DOMAINE.fr/auth/google/callback
```
Identique à `GOOGLE_REDIRECT_URI` dans `.env`.

---

## 13. Déploiements suivants (mises à jour)

```bash
cd ~/synoriaeats
php artisan down

git pull origin master
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
# si tu rebuild sur le serveur : npm ci && npm run build

php artisan up
```

---

## 14. Checklist « 1ʳᵉ version OK »

| Contrôle | OK ? |
|----------|------|
| HTTPS vert (SSL) | |
| `APP_DEBUG=false` | |
| Page d’accueil + CSS chargés | |
| Inscription / connexion | |
| Admin créé + `/admin` | |
| Restaurant validé + menu visible | |
| Commande sandbox | |
| Photos Cloudinary (ou storage local) | |
| Cron `schedule:run` | |
| `.env` **pas** dans Git | |

---

## 15. Problèmes fréquents sur o2switch

### Page blanche / 500
```bash
tail -n 80 ~/synoriaeats/storage/logs/laravel.log
```
Causes classiques : mauvais `.env`, permissions `storage`, PHP < 8.3, `APP_KEY` vide.

### CSS / JS absents
→ `public/build` manquant : refaire `npm run build` (local ou serveur).

### « 404 » sur toutes les URLs sauf `/`
→ document root pas sur `public/`, ou rewrite Apache.  
Dans `public/.htaccess` Laravel doit être présent (il l’est dans le repo).  
Si le site est en sous-dossier, configure correctement le vhost / document root cPanel.

### Erreur base de données
→ préfixe o2switch du nom de DB/user, host `localhost`, droits utilisateur.

### Composer « Allowed memory size »
```bash
php -d memory_limit=512M $(which composer) install --no-dev
```

### Uploads images
Sans Cloudinary, les fichiers vont dans `storage` via `storage:link`.  
En prod, **Cloudinary** évite les quotas disque mutualisé.

### Queue / jobs
Avec `QUEUE_CONNECTION=sync`, pas besoin de worker.  
Si tu passes à `database`, ajoute un cron :
```bash
* * * * * cd /home/TON_USER/synoriaeats && php artisan queue:work --stop-when-empty
```

---

## 16. Sécurité v1 (minimum)

- [ ] Mot de passe admin long et unique  
- [ ] `APP_DEBUG=false`  
- [ ] Pas de `.env` exposé (hors `public/`)  
- [ ] SSL forcé (cPanel → Force HTTPS Redirect si dispo)  
- [ ] Paiements réels **désactivés** tant que sandbox  
- [ ] Sauvegarde cPanel (Backup) avant chaque gros changement  

---

## Ordre chronologique résumé

1. PHP 8.3 + extensions + whitelist SSH  
2. Domaine + SSL  
3. Créer MySQL  
4. SSH + clé GitHub  
5. `git clone` dans `~/synoriaeats`  
6. Symlink / document root → `public`  
7. `.env` production  
8. `composer install` + `key:generate`  
9. Build assets  
10. `migrate` + `synoria:admin` + caches  
11. Cron  
12. Tester le parcours complet  

Si tu bloques à une étape précise (SSH, symlink, migrate, CSS), envoie le message d’erreur exact et on le corrige ciblé.
