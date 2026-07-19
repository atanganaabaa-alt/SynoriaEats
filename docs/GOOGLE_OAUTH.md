# Configurer Google Login (SynoriaEats)

L’erreur `Missing required parameter: client_id` apparaît quand `GOOGLE_CLIENT_ID` est vide dans `.env`.

## Étapes (2 minutes)

1. Ouvre [Google Cloud Console → Credentials](https://console.cloud.google.com/apis/credentials)
2. Crée un projet (ex. `SynoriaEats`) si besoin
3. **Écran de consentement OAuth** → External → appli `SynoriaEats` → ton email
4. **Identifiants** → **Créer des identifiants** → **ID client OAuth**
   - Type : **Application Web**
   - Nom : `SynoriaEats Local`
   - **URI de redirection autorisés** (exactement) :
     ```
     http://127.0.0.1:8000/auth/google/callback
     ```
5. Copie **Client ID** et **Client Secret**
6. Dans le projet :
   ```bash
   php artisan synoria:google "TON_CLIENT_ID" "TON_CLIENT_SECRET"
   php artisan serve
   ```
7. Sur `/login` → **Continuer avec Google**

Astuce : en mode test Google, ajoute ton Gmail comme **utilisateur test** sur l’écran de consentement.
