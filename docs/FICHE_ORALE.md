# SynoriaEats — Fiche orale (aide-mémoire)

À imprimer ou avoir sur téléphone le jour J.  
Dossier complet : [PRESENTATION_GROUPE.md](PRESENTATION_GROUPE.md)

---

## Pitch (30 secondes)

> SynoriaEats est une plateforme de **livraison de repas** pour le Cameroun, sœur de Synoria.  
> Un client commande chez un resto local, paie en **Mobile Money**, suit sa livraison.  
> Restaurateurs et livreurs sont **validés par un admin**.  
> On a aussi **Sara**, une conseillère qui aide à choisir selon le budget et les goûts.

---

## Stack (à réciter)

| Couche | Choix |
|--------|--------|
| Backend | **PHP 8.3 + Laravel 13** |
| Auth web | Sessions (Breeze) |
| Auth API | **Sanctum** (Bearer) |
| Google | **Socialite** OAuth |
| Front | **Blade + Tailwind + Alpine + Vite** |
| Carte | **Leaflet / OpenStreetMap** |
| DB | MySQL (o2switch) / PostgreSQL (cible) |
| Hébergement | **o2switch** |

---

## Pourquoi Laravel (et pas Node) ?

1. Tout-en-un : auth, ORM, migrations, templates
2. Livraison rapide des sprints scolaires
3. Sanctum = équivalent moderne de JWT pour l’API
4. Déploiement simple sur hébergement mutualisé PHP

## Pourquoi Blade + Tailwind (et pas React) ?

1. Écrans livrés vite sans SPA lourde
2. Alpine pour l’interactivité
3. API REST déjà là pour une future app mobile

## Pourquoi polling (pas WebSocket) ?

Sur mutualisé, le polling est plus simple et suffisant pour suivre une livraison.

---

## 9 sprints (une ligne chacun)

1. Auth + restos + menus
2. Panier + paiement MoMo
3. Livraison + notes
4. Admin
5. Google + validation restos/livreurs
6. Photos + accompagnements
7. Matching géoloc + carte live
8. Timeline / audit notifications
9. Compagnon IA (Sara)

---

## Démo flash (ordre)

Accueil → restos → panier → checkout → resto accepte → livreur → carte → Sara

---

## Phrases utiles si on te challenge

- **« C’est juste un template ? »**  
  Non : rôles métier, approval admin, matching, livraison GPS, paiement MoMo, IA contextualisée au menu.

- **« Où est le mobile ? »**  
  L’API Sanctum est prête ; le web Blade est le client v1.

- **« L’IA invente des plats ? »**  
  Non : Sara s’appuie sur le catalogue / menu réel (et un mode local sans clé API).

- **« Et la sécu ? »**  
  Auth session + Sanctum, middleware `approved`, docs restaurateur, pas d’inscription livreur libre.

---

## Liens

- Prod : `https://synoriaeats.gsi2026.com`
- Repo : GitHub `SynoriaEats`
- Doc sprints : `docs/SPRINTS.md`
