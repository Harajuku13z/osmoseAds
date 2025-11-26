#!/bin/bash

###############################################################################
# Script d'Activation du Mode Debug WordPress
# Usage: bash enable-wp-debug.sh
###############################################################################

echo "======================================"
echo "   Activation du Mode Debug WordPress"
echo "======================================"
echo ""

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Déterminer le chemin de wp-config.php
WP_CONFIG=""

# Chercher wp-config.php dans les dossiers courants possibles
if [ -f "wp-config.php" ]; then
    WP_CONFIG="wp-config.php"
elif [ -f "../wp-config.php" ]; then
    WP_CONFIG="../wp-config.php"
elif [ -f "../../wp-config.php" ]; then
    WP_CONFIG="../../wp-config.php"
elif [ -f "../../../wp-config.php" ]; then
    WP_CONFIG="../../../wp-config.php"
elif [ -f "../../../../wp-config.php" ]; then
    WP_CONFIG="../../../../wp-config.php"
else
    print_error "Impossible de trouver wp-config.php"
    echo ""
    echo "Veuillez exécuter ce script depuis :"
    echo "  - La racine de WordPress"
    echo "  - Le dossier du plugin osmose-ads"
    echo ""
    echo "Ou spécifiez le chemin manuellement :"
    echo "  WP_CONFIG_PATH=/path/to/wp-config.php bash enable-wp-debug.sh"
    exit 1
fi

# Si un chemin manuel est fourni
if [ ! -z "$WP_CONFIG_PATH" ]; then
    WP_CONFIG="$WP_CONFIG_PATH"
fi

print_success "Fichier wp-config.php trouvé : $WP_CONFIG"
echo ""

# Vérifier les permissions
if [ ! -w "$WP_CONFIG" ]; then
    print_error "Pas de permissions d'écriture sur wp-config.php"
    echo ""
    echo "Exécutez :"
    echo "  chmod 644 $WP_CONFIG"
    echo "  ou connectez-vous en tant que propriétaire du fichier"
    exit 1
fi

print_success "Permissions d'écriture OK"
echo ""

# Créer une sauvegarde
BACKUP_FILE="${WP_CONFIG}.backup-$(date +%Y%m%d-%H%M%S)"
cp "$WP_CONFIG" "$BACKUP_FILE"
print_success "Sauvegarde créée : $BACKUP_FILE"
echo ""

# Vérifier si WP_DEBUG existe déjà
if grep -q "define.*WP_DEBUG" "$WP_CONFIG"; then
    print_warning "WP_DEBUG est déjà défini dans wp-config.php"
    echo ""
    echo "Voulez-vous le remplacer ? (y/n)"
    read -r response
    if [[ ! "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        print_warning "Opération annulée"
        exit 0
    fi
    
    # Supprimer les anciennes lignes de debug
    sed -i.tmp '/define.*WP_DEBUG/d' "$WP_CONFIG"
    sed -i.tmp '/define.*WP_DEBUG_LOG/d' "$WP_CONFIG"
    sed -i.tmp '/define.*WP_DEBUG_DISPLAY/d' "$WP_CONFIG"
    sed -i.tmp '/ini_set.*display_errors/d' "$WP_CONFIG"
    rm -f "${WP_CONFIG}.tmp"
    
    print_success "Anciennes configurations supprimées"
    echo ""
fi

# Trouver la ligne où insérer le code (avant "/* C'est tout" ou "That's all")
INSERT_LINE=$(grep -n "C'est tout\|That's all" "$WP_CONFIG" | head -1 | cut -d: -f1)

if [ -z "$INSERT_LINE" ]; then
    # Si pas trouvé, insérer avant la dernière ligne
    INSERT_LINE=$(wc -l < "$WP_CONFIG")
fi

# Créer le code à insérer
DEBUG_CODE="
// ========================================
// MODE DEBUG ACTIVÉ - OSMOSE ADS
// ========================================
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
// ========================================
"

# Insérer le code de debug
{
    head -n $((INSERT_LINE - 1)) "$WP_CONFIG"
    echo "$DEBUG_CODE"
    tail -n +$INSERT_LINE "$WP_CONFIG"
} > "${WP_CONFIG}.new"

# Remplacer l'ancien fichier
mv "${WP_CONFIG}.new" "$WP_CONFIG"

print_success "Mode debug activé avec succès !"
echo ""

# Créer le fichier debug.log s'il n'existe pas
WP_CONTENT_DIR=$(dirname "$WP_CONFIG")/wp-content
DEBUG_LOG="${WP_CONTENT_DIR}/debug.log"

if [ ! -f "$DEBUG_LOG" ]; then
    touch "$DEBUG_LOG"
    chmod 666 "$DEBUG_LOG"
    print_success "Fichier debug.log créé : $DEBUG_LOG"
else
    print_success "Fichier debug.log existe déjà : $DEBUG_LOG"
fi

echo ""
echo "======================================"
echo "   Configuration Terminée"
echo "======================================"
echo ""
echo "Les erreurs seront enregistrées dans :"
echo "  $DEBUG_LOG"
echo ""
echo "Prochaines étapes :"
echo "  1. Visitez votre site et reproduisez l'erreur"
echo "  2. Consultez le fichier debug.log :"
echo "     tail -f $DEBUG_LOG"
echo ""
echo "  3. Pour lire les dernières erreurs :"
echo "     tail -50 $DEBUG_LOG"
echo ""
echo "  4. Pour chercher les erreurs Osmose ADS :"
echo "     grep -i 'osmose' $DEBUG_LOG"
echo ""
echo "Pour DÉSACTIVER le debug plus tard :"
echo "  bash disable-wp-debug.sh"
echo ""
echo "Sauvegarde disponible ici :"
echo "  $BACKUP_FILE"
echo ""

