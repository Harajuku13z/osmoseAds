#!/bin/bash

###############################################################################
# Script de Désactivation du Mode Debug WordPress
# Usage: bash disable-wp-debug.sh
###############################################################################

echo "======================================"
echo "  Désactivation du Mode Debug WordPress"
echo "======================================"
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Chercher wp-config.php
WP_CONFIG=""

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
    exit 1
fi

if [ ! -z "$WP_CONFIG_PATH" ]; then
    WP_CONFIG="$WP_CONFIG_PATH"
fi

print_success "Fichier wp-config.php trouvé : $WP_CONFIG"
echo ""

# Vérifier les permissions
if [ ! -w "$WP_CONFIG" ]; then
    print_error "Pas de permissions d'écriture sur wp-config.php"
    exit 1
fi

# Créer une sauvegarde
BACKUP_FILE="${WP_CONFIG}.backup-$(date +%Y%m%d-%H%M%S)"
cp "$WP_CONFIG" "$BACKUP_FILE"
print_success "Sauvegarde créée : $BACKUP_FILE"
echo ""

# Supprimer les lignes de debug
print_warning "Suppression des lignes de debug..."

# Méthode 1 : Supprimer le bloc complet OSMOSE ADS
sed -i.tmp '/MODE DEBUG ACTIVÉ - OSMOSE ADS/,/========================================/d' "$WP_CONFIG"

# Méthode 2 : Supprimer les lignes individuelles
sed -i.tmp '/define.*WP_DEBUG.*true/d' "$WP_CONFIG"
sed -i.tmp '/define.*WP_DEBUG_LOG.*true/d' "$WP_CONFIG"
sed -i.tmp '/define.*WP_DEBUG_DISPLAY.*false/d' "$WP_CONFIG"
sed -i.tmp '/ini_set.*display_errors/d' "$WP_CONFIG"

# Nettoyer les lignes vides multiples
sed -i.tmp '/^$/N;/^\n$/D' "$WP_CONFIG"

rm -f "${WP_CONFIG}.tmp"

# Réactiver WP_DEBUG en mode production (false)
INSERT_LINE=$(grep -n "C'est tout\|That's all" "$WP_CONFIG" | head -1 | cut -d: -f1)

if [ -z "$INSERT_LINE" ]; then
    INSERT_LINE=$(wc -l < "$WP_CONFIG")
fi

# Ajouter WP_DEBUG false
{
    head -n $((INSERT_LINE - 1)) "$WP_CONFIG"
    echo "define('WP_DEBUG', false);"
    echo ""
    tail -n +$INSERT_LINE "$WP_CONFIG"
} > "${WP_CONFIG}.new"

mv "${WP_CONFIG}.new" "$WP_CONFIG"

print_success "Mode debug désactivé avec succès !"
echo ""

# Proposer de supprimer le fichier debug.log
WP_CONTENT_DIR=$(dirname "$WP_CONFIG")/wp-content
DEBUG_LOG="${WP_CONTENT_DIR}/debug.log"

if [ -f "$DEBUG_LOG" ]; then
    echo "Le fichier debug.log existe toujours : $DEBUG_LOG"
    echo "Voulez-vous le supprimer ? (y/n)"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        rm -f "$DEBUG_LOG"
        print_success "Fichier debug.log supprimé"
    else
        print_warning "Fichier debug.log conservé"
    fi
fi

echo ""
echo "======================================"
echo "   Désactivation Terminée"
echo "======================================"
echo ""
echo "Sauvegarde disponible ici :"
echo "  $BACKUP_FILE"
echo ""

