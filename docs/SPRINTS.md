# SynoriaEats — plan en 9 sprints

App livraison de repas (**Laravel** fullstack + API Sanctum), suite Synoria.  
Références produit : Uber Eats / EasyFood / QuickLunch.

> Stack retenue pour SynoriaEats : **Laravel 13** (web Blade + API REST token Bearer), **PostgreSQL** en cible prod, auth **email/mot de passe + Google**.  
> (Le brief historique QuickLunch citait Node/Express/JWT — ici équivalent Laravel/Sanctum.)

Convention Git : **un commit (ou tag) de fin de sprint** sur `master` à chaque livraison.

---

## Sprint 1 — La base : s'inscrire et voir les restaurants
**25 Mai → 08 Juin**

Fondations. Sans ça, rien d'autre ne fonctionne.

### Fonctionnalités
- Inscription / connexion (Client, Restaurateur, Livreur) — Admin hors inscription publique
- Continuer avec **Google** ou compte email + **mot de passe personnel** (pas de mdp démo)
- Rôles + auth web (session) et API (Sanctum / Bearer token)
- Profil restaurateur (nom, adresse, logo, horaires, …)
- Gestion des plats (nom, prix, photo, disponibilité)
- Liste restaurants + menu côté client
- PostgreSQL (cible) + migrations (`users`, `restaurants`, `menu_items`, …)
- API REST de base (`/api/register`, `/api/login`, `/api/restaurants`, …)

### Résultat visible
Un client s'inscrit, parcourt les restos et voit les menus.  
Un restaurateur crée son profil et ajoute ses plats.

---

## Sprint 2 — Commander et payer ✅
**22 Juin → 06 Juillet**

Cœur métier — **livré**.

- Panier session + checkout (adresse, téléphone)
- Paiement **Orange Money** + **MTN MoMo** (sandbox local, prod via `.env`)
- Confirmation client / restaurateur + notifications (**log**, **Twilio SMS/WhatsApp**, **Orange SMS API**)
- Gestion commandes restaurateur (accepter → préparer → prête)
- Historique client

### Résultat visible
Commander, payer, resto gère en temps réel.

---

## Sprint 3 — Livraison et évaluations ✅
**20 Juillet → 03 Août**

- Attribution livreur (claim mission)
- Interface livreur (missions, GPS, livraison)
- Suivi statut (polling JSON côté client)
- Géoloc / frais livraison (base resto + FCFA/km)
- Notation resto + livreur
- Notifications de statut (client, resto, livreur)

### Résultat visible
Chaîne complète commande → livraison + notes.

---

## Sprint 4 — Back-office et finition ✅
**17 Août → 31 Août**

- Dashboard admin (CA, livraisons, satisfaction)
- Gestion comptes (suspendre) + validation restaurants
- Commissions (rapport + taux `.env`)
- Filtres restaurants (note, frais, tri)
- UI/UX responsive (nav + grilles admin)
- Tests + correctifs
- Déploiement VPS — [docs/DEPLOY_VPS.md](DEPLOY_VPS.md)

### Résultat visible
Plateforme prête prod, admin opérationnel.

---

## Sprint 5 — Authentification renforcée ✅
**Août 2026**

- Google OAuth (client / restaurateur). Admin et livreur : connexion Google seulement si le compte existe déjà.
- Email / mot de passe en secours.
- Restaurateur : preuves (RCCM, CNI, justificatif) uploadées vers Cloudinary (URL seule en base).
- Colonne `restaurants.status` : `pending` / `approved` / `rejected` (synchro `is_validated`).
- Admin Blade (pas Filament : on garde un seul back-office cohérent avec Breeze déjà en prod) : dossiers, approuver / rejeter.
- Middleware `approved` + `RestaurantPolicy` : pas de menu / publication tant que non approuvé.
- Livreurs : plus d’inscription publique. Création admin + partenaire + validation préalable.

### Résultat visible
Connexion Google en un clic. Un resto n’apparaît qu’après validation. Les livreurs n’arrivent que via partenariat.

---

## Sprint 6 — Médias & menu enrichi ✅
**Août 2026**

- Upload Cloudinary (URL seule en base) : plats, boissons, logo, couverture / cadre resto.
- Catégories menu : Plats, Boissons, Accompagnements (liés aux plats), Desserts.
- Restaurateur : lie des accompagnements à un plat avec prix additionnel (`0` = inclus).
- Client : choix multiple d’accompagnements sur les plats, section Boissons séparée.
- Les accompagnements ne sont pas commandables seuls au catalogue (options du plat uniquement).

### Résultat visible
Photos hébergées Cloudinary (ou local en dev). Un plat « poisson 5000 FCFA » propose attiéké inclus + options payantes. Les boissons ont leur propre rubrique.

## Sprint 7 — Matching intelligent & carte temps réel
Score de pertinence + Leaflet + positions Echo/Reverb pendant la livraison.

## Sprint 8 — Suivi de préparation vérifiable
Historique horodaté des statuts + notifications fiables + audit admin.

## Sprint 9 — Agent IA compagnon
Chat culinaire contextuel (menu, budget, attente).
