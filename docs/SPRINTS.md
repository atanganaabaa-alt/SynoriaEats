# SynoriaEats — plan en 4 sprints

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

## Sprint 2 — Commander et payer
**22 Juin → 06 Juillet**

Cœur métier.

- Panier + commande + adresse
- Orange Money + MTN MoMo
- Confirmation client / restaurateur
- SMS/WhatsApp (Twilio / Orange)
- Gestion commandes restaurateur (accepter → prête)
- Historique client

### Résultat visible
Commander, payer, resto gère en temps réel.

---

## Sprint 3 — Livraison et évaluations
**20 Juillet → 03 Août**

- Attribution livreur
- Interface livreur (missions, livraison)
- Suivi statut temps réel
- Géoloc / frais livraison
- Notation resto + livreur
- Notifications de statut

### Résultat visible
Chaîne complète commande → livraison + notes.

---

## Sprint 4 — Back-office et finition
**17 Août → 31 Août**

- Dashboard admin (CA, livraisons, satisfaction)
- Gestion comptes (suspendre, valider resto)
- Commissions
- Filtres restaurants
- UI/UX responsive (+ mobile plus tard)
- Tests + correctifs
- Déploiement VPS

### Résultat visible
Plateforme en prod, admin opérationnel.
