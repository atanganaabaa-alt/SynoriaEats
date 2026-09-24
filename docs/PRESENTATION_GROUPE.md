# SynoriaEats — Dossier projet (groupe)

**À lire par tous les membres avant la présentation (jeudi).**  
Objectif : que **chacun** puisse expliquer le projet, la stack, les choix techniques et les itérations — même s’il n’a pas codé toutes les parties.

| Document | Usage |
|----------|--------|
| **Celui-ci** | Dossier complet (stack, pourquoi, architecture, sprints, démo) |
| [FICHE_ORALE.md](FICHE_ORALE.md) | Aide-mémoire court pour l’oral (2–3 min / personne) |
| [SPRINTS.md](SPRINTS.md) | Détail des 9 sprints |
| [LOCAL.md](LOCAL.md) | Lancer le projet en local |
| [DEPLOY_O2SWITCH.md](DEPLOY_O2SWITCH.md) | Déploiement prod |

**URL prod (si déployée) :** `https://synoriaeats.gsi2026.com`  
**Repo :** `https://github.com/atanganaabaa-alt/SynoriaEats`

---

## 1. C’est quoi SynoriaEats ?

**SynoriaEats** est une plateforme de **commande et livraison de repas** (style Uber Eats / EasyFood), pensée pour le contexte camerounais :

- restos locaux (ndolé, poulet DG, grillades…)
- paiement **Mobile Money** (Orange Money, MTN MoMo)
- suivi de livraison
- validation admin des restaurateurs / livreurs
- conseillère IA (**Sara**) pour aider à choisir

Elle fait partie de la **suite Synoria** (avec le réseau social Synoria).

### Acteurs (rôles)

| Rôle | Ce qu’il fait |
|------|----------------|
| **Client** | Parcourt les restos, commande, paie, suit la livraison, note |
| **Restaurateur** | Crée son resto, menu, gère les commandes (après validation admin) |
| **Livreur** | Prend des missions, livre (compte créé/validé par l’admin) |
| **Admin** | Approuve restos/livreurs, commissions, audit commandes |

---

## 2. Stack technique (à connaître par cœur)

### Backend
| Techno | Rôle |
|--------|------|
| **PHP 8.3+** | Langage serveur |
| **Laravel 13** | Framework web (MVC, routes, Eloquent ORM, queues, etc.) |
| **Laravel Sanctum** | Auth API (tokens Bearer) |
| **Laravel Socialite** | Connexion Google OAuth |
| **Laravel Breeze** | Squelette auth (login/register/reset) |
| **MySQL / PostgreSQL** | Base de données (MySQL souvent sur o2switch ; Postgres en cible) |
| **PHPUnit** | Tests automatisés |

### Frontend
| Techno | Rôle |
|--------|------|
| **Blade** | Templates HTML côté serveur |
| **Tailwind CSS** | Styles utilitaires + **dark mode** (`darkMode: 'class'`) |
| **Alpine.js** | Interactivité légère (menus, chat, toggles) |
| **Vite** | Build CSS/JS |
| **Leaflet + OpenStreetMap** | Carte de suivi livraison |

### Services externes / intégrations
| Service | Usage |
|---------|--------|
| **Google OAuth** | « Continuer avec Google » |
| **Cloudinary** (optionnel) | Photos restos / plats |
| **Twilio / Orange SMS** | Notifications SMS / WhatsApp |
| **Orange Money / MTN MoMo** | Paiement (sandbox en dev) |
| **OpenAI / Claude** (optionnel) | Mode cloud de Sara ; sinon moteur **local gratuit** |

### Hébergement
- **o2switch** (cPanel, mutualisé) pour la démo / prod scolaire
- Document root → dossier `public/` de Laravel

---

## 3. Pourquoi cette stack ? (questions fréquentes du jury)

### Pourquoi Laravel plutôt que Node/Express ?
- Le brief historique citait parfois Node/Express/JWT.
- On a choisi **Laravel** car :
  - **fullstack** rapide : auth, migrations, Eloquent, Blade déjà intégrés
  - écosystème mature pour apps métier (commandes, rôles, admin)
  - **Sanctum** remplace proprement JWT pour l’API
  - un seul langage côté serveur (PHP) + front Blade = déploiement simple sur o2switch
- Équivalent conceptuel : Express ≈ routes Laravel, JWT ≈ Sanctum, React SPA optionnel plus tard via l’API.

### Pourquoi Blade + Tailwind plutôt qu’une SPA React/Vue ?
- Délai de projet / sprints : livrer des **écrans réels** vite
- Pages server-rendered simples
- Alpine.js pour le JS nécessaire sans complexité SPA
- L’**API REST** existe déjà pour une future app mobile / front moderne

### Pourquoi Tailwind ?
- UI cohérente et responsive rapidement
- Dark mode natif via classes `dark:`
- Moins de CSS custom à maintenir

### Pourquoi MySQL sur o2switch ?
- Le plus simple sur hébergement mutualisé
- PostgreSQL reste la cible « idéale » ; le code Eloquent s’adapte

### Pourquoi pas WebSockets pour la carte ?
- Sur mutualisé, WebSockets sont lourds / limités
- **Polling JSON** (toutes les ~4 s) suffit pour le suivi livraison
- Plus simple à déployer et à expliquer

### Pourquoi une IA (Sara) ?
- Différenciateur produit : aide à choisir selon budget / goûts
- Mode **local** sans coût API pour la démo scolaire
- Mode cloud possible si clé OpenAI/Claude

---

## 4. Architecture (vue simple)

```
Navigateur (Blade + Tailwind + Alpine)
        │
        ▼
   Laravel (routes web + API)
        │
        ├── Controllers (Auth, Cart, Checkout, Owner, Courier, Admin, Companion)
        ├── Models Eloquent (User, Restaurant, MenuItem, Order, …)
        ├── Services (frais livraison, matching, IA, notifs, MediaUrl)
        └── DB (users, restaurants, menu_items, orders, conversations_ia, …)
        │
        └── Intégrations : Google, Cloudinary, Twilio, Mobile Money
```

### Auth
- **Web** : session (cookies) via Breeze
- **API** : token Sanctum (`Authorization: Bearer …`)
- Google OAuth via Socialite (`/auth/google/redirect` → callback)

### Flux commande (à raconter à l’oral)
1. Client choisit un resto / plats → **panier**
2. Checkout (adresse, téléphone, GPS optionnel) → **Mobile Money**
3. Restaurateur accepte → prépare → prêt
4. Livreur claim la mission → en livraison (carte) → livré
5. Client note resto (+ livreur)

---

## 5. Itérations = 9 sprints (résumé)

| Sprint | Thème | Ce qu’on peut montrer |
|--------|--------|------------------------|
| **1** | Auth + restos + menus | Inscription, rôles, catalogue |
| **2** | Commander / payer | Panier, checkout MoMo, notifs |
| **3** | Livraison + notes | Missions livreur, GPS, avis |
| **4** | Admin | Dashboard, validation, commissions |
| **5** | Auth renforcée | Google, preuves resto, approval |
| **6** | Médias & menu | Photos, accompagnements, boissons |
| **7** | Matching + carte | Tri par position, Leaflet live |
| **8** | Timeline & audit | Historique statuts, logs notifs |
| **9** | Compagnon IA | Chat Sara (local ou cloud) |

Détail : [SPRINTS.md](SPRINTS.md)

### Évolutions UX récentes (après sprint 9)
- Mode **sombre** persistant + bascule FR/EN
- Accueil avec **vraies photos** de plats
- Conseillère **Sara** (listes Markdown, langue, format court)
- Seeder démo **restos camerounais** (`CameroonDemoSeeder`)
- Déploiement o2switch documenté

---

## 6. Fonctionnalités clés à maîtriser

1. **Multi-rôles** avec règles métier (ex. livreur pas d’inscription libre)
2. **Validation admin** avant qu’un resto soit visible
3. **Panier + checkout** + paiement Mobile Money
4. **Chaîne livraison** complète avec carte
5. **Matching** restos selon position / prix / frais / notes
6. **i18n FR/EN** + dark mode
7. **Sara** : recommandations à partir du menu réel
8. **API REST** pour extension mobile

---

## 7. Démo recommandée (ordre)

1. Accueil → photos, dark mode, FR/EN
2. Catalogue `/restaurants` → géoloc + matching
3. Fiche resto → ajouter au panier
4. Checkout → payer (sandbox)
5. Espace restaurateur → changer statut
6. Espace livreur → mission + carte
7. Admin → dossier resto / commandes
8. Sara → « j’ai 5000 FCFA, quelque chose de local »

Compte démo seed (si seedé) :  
`owner.demo@synoriaeats.test` / `password`  
Admin : créé via `php artisan synoria:admin …`

---

## 8. Limites / honnêteté (le jury aime ça)

- Paiements souvent en **sandbox** sans clés marchands réelles
- Géoloc mobile fiable surtout en **HTTPS**
- Google OAuth nécessite de **vrais** Client ID / Secret Google Cloud
- Chat Sara en local = règles + menu réel (pas un LLM cloud tant qu’il n’y a pas de clé)
- Mutualisé o2switch : pas de WebSockets, polling à la place

---

## 9. Répartition orale suggérée (exemple)

Adapte selon les membres du groupe :

| Membre | Angle |
|--------|--------|
| A | Vision produit + acteurs + démo client |
| B | Stack backend Laravel + DB + pourquoi pas Node |
| C | Frontend Blade/Tailwind/Alpine + dark mode + i18n |
| D | Commande / paiement / livraison / carte |
| E | Admin, sécurité (approval), Google OAuth, déploiement o2switch |
| F | IA Sara + matching + perspectives (app mobile via API) |

Chacun lit au minimum : **ce dossier** + **FICHE_ORALE.md**.

---

## 10. Checklist veille de présentation

- [ ] Repo GitHub à jour
- [ ] Site prod accessible (ou démo locale qui marche)
- [ ] Compte admin / client / resto prêts
- [ ] Chaque membre a lu ce dossier
- [ ] Chaque membre a 2–3 phrases sur « pourquoi Laravel »
- [ ] Une démo chronométrée (~8–10 min) répétée une fois

Bonne présentation.
