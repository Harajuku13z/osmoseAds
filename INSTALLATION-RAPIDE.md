# Installation Rapide - Osmose ADS

## ⚡ Installation en 5 minutes

### 1. Uploader le plugin

**Via FTP :**
- Copiez le dossier `osmose-ads` dans `/wp-content/plugins/`
- Le chemin final doit être : `/wp-content/plugins/osmose-ads/osmose-ads.php`

**Via WordPress :**
- Créez un ZIP du dossier `osmose-ads`
- Allez dans **Extensions > Ajouter > Téléverser une extension**
- Uploadez le ZIP

### 2. Activer

- Allez dans **Extensions**
- Trouvez "Osmose ADS"
- Cliquez sur **Activer**

### 3. Flush des Permaliinks (OBLIGATOIRE)

- Allez dans **Réglages > Permaliinks**
- Cliquez sur **Enregistrer** (même sans rien changer)

### 4. Configuration minimale

1. **Réglages** : `Osmose ADS > Réglages`
   - Ajoutez votre téléphone
   - Ajoutez vos services (ex: "Plomberie", "Électricité")

2. **Villes** : `Osmose ADS > Villes`
   - Ajoutez au moins une ville avec code postal et département

### 5. Test

- Allez dans `Osmose ADS > Génération en Masse`
- Sélectionnez un service
- Sélectionnez une ville
- Cliquez sur "Générer les Annonces"

## ✅ Vérification

Si tout fonctionne :
- ✅ Le menu "Osmose ADS" apparaît
- ✅ Les pages admin s'affichent
- ✅ Une annonce est créée et accessible publiquement

## ❌ Problèmes ?

1. **Erreur fatale** : Vérifiez PHP 7.4+ et WordPress 5.0+
2. **Menu non visible** : Vérifiez que vous êtes administrateur
3. **Pages 404** : Allez dans Permaliinks et cliquez sur Enregistrer
4. **Classes manquantes** : Vérifiez que tous les fichiers sont présents

Consultez `GUIDE-INSTALLATION-SANS-ERREUR.md` pour plus de détails.

## 📝 Structure des fichiers

Le plugin doit avoir cette structure :

```
osmose-ads/
├── osmose-ads.php (fichier principal)
├── includes/
├── admin/
├── public/
└── README.md
```

Tous les fichiers doivent être présents pour éviter les erreurs.

