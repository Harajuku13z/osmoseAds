# 🔗 Guide : URLs Propres pour les Annonces

## ✅ Ce qui a été modifié

Les annonces utilisent maintenant des **URLs propres** au lieu des paramètres GET :

- ❌ **Ancien format** : `https://bretagne.normesrenovation.fr/?ad=couverture-et-toiture-allaire`
- ✅ **Nouveau format** : `https://bretagne.normesrenovation.fr/couverture-et-toiture-allaire`

## 🚀 Comment Appliquer les Changements

### Étape 1 : Mettre à jour le Plugin

**Option A : Via FTP (Recommandé)**

1. Téléchargez la dernière version depuis GitHub :
   👉 https://github.com/Harajuku13z/osmoseAds/archive/refs/heads/main.zip

2. Via FTP :
   - **Sauvegardez** d'abord le dossier actuel (au cas où)
   - Supprimez le dossier `/wp-content/plugins/osmose-ads/`
   - Uploadez le nouveau dossier complet

3. Réactivez le plugin dans WordPress

**Option B : Mettre à jour les fichiers spécifiques**

Téléchargez et uploadez ces 3 fichiers :
- `includes/class-osmose-ads.php`
- `includes/class-osmose-ads-post-types.php`
- `includes/class-osmose-ads-rewrite.php`

### Étape 2 : Rafraîchir les Rewrite Rules (IMPORTANT !)

**C'est l'étape la plus importante !** Sans cela, les URLs propres ne fonctionneront pas.

1. Connectez-vous à **WordPress Admin**
2. Allez dans **Réglages → Permaliens**
3. **Ne modifiez rien**, cliquez simplement sur **"Enregistrer les modifications"**
4. Cela rafraîchira les rewrite rules WordPress

**Alternative via SSH :**

```bash
# Connexion SSH
ssh votre-user@bretagne.normesrenovation.fr

# Aller dans WordPress
cd htdocs/wordpress  # ou public_html, ou www

# Rafraîchir les rewrite rules via WP-CLI
wp rewrite flush
```

### Étape 3 : Tester

1. Testez l'ancienne URL (elle devrait rediriger automatiquement) :
   ```
   https://bretagne.normesrenovation.fr/?ad=couverture-et-toiture-allaire
   ```
   → Devrait rediriger vers : `https://bretagne.normesrenovation.fr/couverture-et-toiture-allaire`

2. Testez la nouvelle URL :
   ```
   https://bretagne.normesrenovation.fr/couverture-et-toiture-allaire
   ```
   → Devrait fonctionner directement !

## 🔄 Redirections Automatiques

Le plugin redirige automatiquement :

- ✅ `?ad=slug` → `/slug` (301 - Redirection permanente)
- ✅ `/ad/slug` → `/slug` (301 - Redirection permanente)

Cela garantit que :
- Les anciens liens continuent de fonctionner
- Le SEO n'est pas impacté (redirection 301)
- Les utilisateurs sont automatiquement redirigés vers la nouvelle URL

## 📋 Vérification

### Vérifier que ça fonctionne

1. **Testez une annonce** :
   - Allez sur une page d'annonce
   - Regardez l'URL dans la barre d'adresse
   - Elle devrait être propre : `/couverture-et-toiture-allaire`

2. **Testez la redirection** :
   - Utilisez l'ancienne URL `?ad=slug`
   - Vous devriez être automatiquement redirigé vers `/slug`

3. **Vérifiez les permaliens** :
   - WordPress Admin → Réglages → Permaliens
   - Assurez-vous que les permaliens sont activés (pas "Simple")

## ⚠️ Problèmes Courants

### Problème : Les URLs propres ne fonctionnent pas

**Solution :**
1. Allez dans **Réglages → Permaliens**
2. Cliquez sur **"Enregistrer les modifications"**
3. Videz le cache WordPress (si vous utilisez un plugin de cache)

### Problème : Erreur 404 sur les nouvelles URLs

**Solution :**
1. Vérifiez que les permaliens sont activés dans WordPress
2. Rafraîchissez les rewrite rules (voir Étape 2)
3. Vérifiez que le fichier `.htaccess` est présent et modifiable

### Problème : Les redirections ne fonctionnent pas

**Solution :**
1. Vérifiez que le plugin est bien activé
2. Vérifiez les logs WordPress pour voir s'il y a des erreurs
3. Assurez-vous que les fichiers ont bien été mis à jour

## 🔧 Configuration Avancée

### Si vous voulez un préfixe personnalisé

Si vous préférez avoir `/annonces/slug` au lieu de `/slug`, modifiez dans `class-osmose-ads-post-types.php` :

```php
'rewrite' => array(
    'slug'       => 'annonces', // Préfixe personnalisé
    'with_front' => false,
    'feeds'      => true,
    'pages'     => true,
),
```

Puis rafraîchissez les rewrite rules.

## 📊 Avantages des URLs Propres

- ✅ **Meilleur SEO** : Les URLs propres sont mieux indexées par Google
- ✅ **Plus professionnel** : URLs plus courtes et lisibles
- ✅ **Meilleure expérience utilisateur** : URLs faciles à partager
- ✅ **Compatibilité** : Fonctionne avec tous les plugins SEO
- ✅ **Redirections automatiques** : Les anciens liens continuent de fonctionner

## 🎯 Résumé

1. ✅ **Mettre à jour** le plugin
2. ✅ **Rafraîchir** les rewrite rules (Réglages → Permaliens → Enregistrer)
3. ✅ **Tester** les nouvelles URLs
4. ✅ **Profiter** des URLs propres !

---

**Les URLs propres sont maintenant actives ! 🎉**

