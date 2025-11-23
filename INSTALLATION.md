# Guide d'Installation - Osmose ADS

## Prérequis

- WordPress 5.0 ou supérieur
- PHP 7.4 ou supérieur
- Accès FTP/SFTP ou au panneau d'administration WordPress
- (Optionnel) Clé API OpenAI ou Groq pour la personnalisation IA

## Installation

### Méthode 1 : Installation manuelle

1. **Télécharger le plugin**
   - Téléchargez le dossier `osmose-ads` complet

2. **Uploader le plugin**
   - Connectez-vous à votre site via FTP/SFTP
   - Naviguez vers `/wp-content/plugins/`
   - Uploadez le dossier `osmose-ads`

3. **Activer le plugin**
   - Connectez-vous à votre administration WordPress
   - Allez dans **Extensions**
   - Trouvez "Osmose ADS" dans la liste
   - Cliquez sur **Activer**

### Méthode 2 : Installation via l'interface WordPress

1. Allez dans **Extensions > Ajouter**
2. Cliquez sur **Téléverser une extension**
3. Sélectionnez le fichier ZIP du plugin
4. Cliquez sur **Installer maintenant**
5. Cliquez sur **Activer l'extension**

## Configuration initiale

### 1. Configurer les réglages de base

1. Allez dans **Osmose ADS > Réglages**
2. Configurez les options suivantes :
   - **Personnalisation IA** : Activez si vous souhaitez utiliser l'IA pour personnaliser le contenu
   - **Fournisseur IA** : Choisissez OpenAI ou Groq
   - **Clé API** : Entrez votre clé API (requis si personnalisation IA activée)
   - **Téléphone Entreprise** : Ajoutez le numéro formaté et brut
   - **Services** : Ajoutez la liste de vos services

### 2. Ajouter des villes

1. Allez dans **Osmose ADS > Villes**
2. Cliquez sur le formulaire "Ajouter une Ville"
3. Remplissez les informations :
   - Nom de la ville
   - Code postal
   - Département
   - Région
   - Population (optionnel)

### 3. Créer votre premier template

**Option A : Création automatique avec IA**

1. Allez dans **Osmose ADS > Templates**
2. Cliquez sur "Créer depuis un Service"
3. Entrez le nom du service
4. (Optionnel) Ajoutez un prompt personnalisé pour l'IA
5. Cliquez sur "Générer le Template"
6. Le template sera créé automatiquement avec du contenu généré par l'IA

**Option B : Création manuelle**

1. Allez dans **Osmose ADS > Templates**
2. Créez un nouveau template manuellement
3. Remplissez le contenu HTML avec les variables disponibles

### 4. Générer des annonces

1. Allez dans **Osmose ADS > Génération en Masse**
2. Sélectionnez un service (le template sera créé automatiquement si nécessaire)
3. Sélectionnez les villes pour lesquelles créer des annonces
4. Cliquez sur "Générer les Annonces"
5. Les annonces seront créées avec un contenu personnalisé pour chaque ville

## Variables disponibles dans les templates

Vous pouvez utiliser ces variables dans vos templates, elles seront automatiquement remplacées :

- `[VILLE]` - Nom de la ville
- `[DÉPARTEMENT]` - Département
- `[RÉGION]` - Région
- `[CODE_POSTAL]` - Code postal
- `[FORM_URL]` - URL du formulaire de devis
- `[PHONE]` - Numéro de téléphone formaté
- `[PHONE_RAW]` - Numéro de téléphone brut (pour liens tel:)
- `[TITRE]` - Titre de l'annonce

## Personnalisation du template public

Pour personnaliser l'affichage public des annonces, vous pouvez :

1. Créer un fichier `single-ad.php` dans votre thème actif
2. Copier le contenu depuis `osmose-ads/public/templates/single-ad.php`
3. Modifier selon vos besoins

## Permaliinks

Après l'activation du plugin, allez dans **Réglages > Permaliinks** et cliquez sur "Enregistrer" pour régénérer les règles de réécriture.

## Dépannage

### Les annonces ne s'affichent pas

- Vérifiez que les permaliinks sont bien configurés
- Allez dans **Réglages > Permaliinks** et cliquez sur "Enregistrer"
- Vérifiez que le statut des annonces est "Publié"

### L'IA ne génère pas de contenu

- Vérifiez que votre clé API est correctement configurée
- Vérifiez que vous avez des crédits disponibles sur votre compte API
- Vérifiez les logs d'erreur WordPress pour plus de détails

### Les villes ne s'affichent pas

- Vérifiez que les villes ont le statut "Publié"
- Vérifiez que les Custom Post Types sont bien enregistrés

## Support

Pour toute question ou problème, consultez la documentation complète dans le fichier `README.md`.



