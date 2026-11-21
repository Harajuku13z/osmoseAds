<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bibliothèque des métiers et de leurs grappes de mots-clés locaux.
 *
 * Chaque mot-clé devient une page « {libellé} à {ville} » avec :
 *  - des mots-clés secondaires (champ sémantique propre à la page),
 *  - un angle éditorial pour éviter les pages génériques.
 * Les métiers personnalisés (option oa_custom_trades) s'ajoutent / remplacent ceux-ci.
 */
class OA_Trades {

    const MAX_PER_CITY = 8;

    public static function all() {
        $trades = self::builtin();
        $custom = get_option('oa_custom_trades', array());
        if (is_array($custom)) {
            foreach ($custom as $id => $trade) {
                $trades[$id] = $trade;
            }
        }
        foreach ($trades as $id => &$trade) {
            $trade['id'] = $id;
            foreach ($trade['keywords'] as $kid => &$kw) {
                $kw['id'] = $kid;
                $kw += array('secondary' => array(), 'angle' => '', 'hub' => false);
            }
        }
        return $trades;
    }

    public static function get($id) {
        $all = self::all();
        return isset($all[$id]) ? $all[$id] : null;
    }

    public static function keyword($trade_id, $keyword_id) {
        $trade = self::get($trade_id);
        if ($trade && isset($trade['keywords'][$keyword_id])) {
            return $trade['keywords'][$keyword_id];
        }
        return null;
    }

    /**
     * « à Lyon », « au Creusot », « aux Sables-d'Olonne », « à La Rochelle », « à L'Isle-Adam ».
     */
    public static function preposition_city($city) {
        $city = trim($city);
        if (preg_match('/^Le\s+(.+)$/u', $city, $m)) {
            return 'au ' . $m[1];
        }
        if (preg_match('/^Les\s+(.+)$/u', $city, $m)) {
            return 'aux ' . $m[1];
        }
        return 'à ' . $city;
    }

    /** Expression-clé principale de la page. */
    public static function focus_keyphrase($label, $city) {
        $label = trim($label);
        // Minuscule initiale sauf sigle (ex. « VMC »).
        if (!preg_match('/^\p{Lu}{2,}/u', $label)) {
            $label = mb_strtolower(mb_substr($label, 0, 1)) . mb_substr($label, 1);
        }
        return $label . ' ' . self::preposition_city($city);
    }

    private static function kw($label, array $secondary, $angle = '', $hub = false) {
        return array('label' => $label, 'secondary' => $secondary, 'angle' => $angle, 'hub' => $hub);
    }

    public static function builtin() {
        return array(
            'couvreur' => array(
                'name'        => 'Couvreur',
                'schema_type' => 'RoofingContractor',
                'lexicon'     => 'charpente, liteaux, chevrons, faîtage, noue, rive, solin, écran sous-toiture, tuiles mécaniques, tuiles canal, ardoise naturelle, zinc, étanchéité, infiltration, mousse, lichen, gouttière, descente EP, isolation des combles, DTU 40',
                'links'       => array('https://france-renov.gouv.fr' => 'France Rénov\''),
                'keywords'    => array(
                    'couvreur'            => self::kw('Couvreur', array('entreprise de couverture', 'artisan couvreur', 'devis toiture'), 'présentation de l\'entreprise et de l\'ensemble des prestations toiture', true),
                    'renovation-toiture'  => self::kw('Rénovation de toiture', array('rénovation complète de toit', 'remplacement de tuiles', 'prix rénovation toiture m²'), 'diagnostic, étapes d\'un chantier complet, budget au m², aides à la rénovation'),
                    'refection-toiture'   => self::kw('Réfection de toiture', array('réfection complète de couverture', 'dépose et repose de tuiles', 'réfection de charpente'), 'quand refaire sa toiture : signes d\'usure, durée de vie des matériaux, déroulé'),
                    'zinguerie'           => self::kw('Zinguerie', array('gouttières zinc', 'chéneaux', 'noues et solins', 'descentes d\'eaux pluviales'), 'évacuation des eaux pluviales, matériaux zinc / alu / cuivre, entretien'),
                    'hydrofuge-toiture'   => self::kw('Traitement hydrofuge de toiture', array('hydrofuge coloré', 'imperméabilisation de toiture', 'protection des tuiles'), 'intérêt, fréquence, produits incolores vs colorés, conditions météo d\'application'),
                    'demoussage-toiture'  => self::kw('Démoussage de toiture', array('nettoyage de toiture', 'traitement anti-mousse', 'lichens et algues'), 'risques de la mousse, méthode douce vs haute pression, entretien préventif'),
                    'reparation-fuite'    => self::kw('Réparation de fuite de toiture', array('recherche de fuite', 'infiltration toiture', 'bâchage d\'urgence'), 'urgence, intervention rapide, causes fréquentes de fuites, mise hors d\'eau'),
                    'charpente'           => self::kw('Charpente', array('charpentier couvreur', 'traitement de charpente', 'renforcement de charpente'), 'charpente traditionnelle vs fermettes, traitement insectes et champignons'),
                    'isolation-toiture'   => self::kw('Isolation de toiture', array('isolation des combles', 'sarking', 'isolation sous rampants'), 'performances thermiques, techniques, aides MaPrimeRénov\''),
                    'fenetre-de-toit'     => self::kw('Pose de fenêtre de toit', array('fenêtre de toit', 'puits de lumière', 'remplacement de fenêtre de toit'), 'lumière dans les combles, étanchéité du raccord, dimensions courantes'),
                    'couverture-ardoise'  => self::kw('Couverture en ardoise', array('toiture ardoise', 'ardoise naturelle', 'pose au crochet'), 'qualité des ardoises, techniques de pose, longévité'),
                    'nettoyage-toiture'   => self::kw('Nettoyage de toiture', array('nettoyage toit', 'lavage de toiture', 'entretien de couverture'), 'fréquence d\'entretien, prix, combinaison avec traitement hydrofuge'),
                ),
            ),
            'plombier' => array(
                'name'        => 'Plombier',
                'schema_type' => 'Plumber',
                'lexicon'     => 'canalisation, raccord, siphon, cumulus, ballon d\'eau chaude, mitigeur, colonne d\'eau, PER, cuivre, multicouche, évacuation, débouchage, fuite encastrée, compteur, réducteur de pression, robinetterie, sanitaires',
                'links'       => array('https://www.economie.gouv.fr/dgccrf/Publications/Vie-pratique/Fiches-pratiques/Depannage-a-domicile' => 'DGCCRF — dépannage à domicile'),
                'keywords'    => array(
                    'plombier'            => self::kw('Plombier', array('artisan plombier', 'entreprise de plomberie', 'plombier pas cher'), 'présentation et toutes les prestations de plomberie', true),
                    'depannage-plomberie' => self::kw('Dépannage plomberie', array('plombier urgence', 'intervention rapide', 'plombier 7j/7'), 'urgence, délais, tarifs transparents'),
                    'recherche-fuite'     => self::kw('Recherche de fuite d\'eau', array('détection de fuite', 'fuite encastrée', 'caméra thermique'), 'méthodes non destructives, prise en charge assurance'),
                    'debouchage'          => self::kw('Débouchage de canalisation', array('canalisation bouchée', 'débouchage WC', 'hydrocurage'), 'causes, techniques (furet, hydrocurage), prévention'),
                    'chauffe-eau'         => self::kw('Remplacement de chauffe-eau', array('installation cumulus', 'ballon d\'eau chaude', 'chauffe-eau thermodynamique'), 'choix de capacité, technologies, durée de vie'),
                    'salle-de-bain'       => self::kw('Rénovation de salle de bain', array('création salle de bain', 'douche à l\'italienne', 'salle de bain PMR'), 'projet clé en main, accessibilité, délais'),
                    'remplacement-wc'     => self::kw('Remplacement de WC', array('WC suspendu', 'installation sanibroyeur', 'réparation chasse d\'eau'), 'types de WC, pose, entretien'),
                    'canalisation'        => self::kw('Remplacement de canalisation', array('réfection de plomberie', 'tuyauterie plomb', 'colonne d\'eau'), 'vieilles installations, matériaux modernes'),
                ),
            ),
            'electricien' => array(
                'name'        => 'Électricien',
                'schema_type' => 'Electrician',
                'lexicon'     => 'tableau électrique, disjoncteur différentiel, NF C 15-100, mise à la terre, Consuel, prise, circuit spécialisé, domotique, VMC, borne de recharge IRVE, éclairage LED, diagnostic électrique',
                'links'       => array('https://www.service-public.fr/particuliers/vosdroits/F16096' => 'Service-Public — diagnostic électricité'),
                'keywords'    => array(
                    'electricien'           => self::kw('Électricien', array('artisan électricien', 'entreprise d\'électricité', 'électricien agréé'), 'présentation et toutes les prestations', true),
                    'depannage-electrique'  => self::kw('Dépannage électrique', array('panne de courant', 'électricien urgence', 'disjoncteur qui saute'), 'urgence, diagnostic de panne'),
                    'mise-aux-normes'       => self::kw('Mise aux normes électrique', array('norme NF C 15-100', 'rénovation électrique', 'sécurité électrique'), 'risques d\'une installation ancienne, étapes, Consuel'),
                    'tableau-electrique'    => self::kw('Remplacement de tableau électrique', array('changement tableau électrique', 'disjoncteurs', 'interrupteur différentiel'), 'signes d\'un tableau vétuste, prix'),
                    'renovation-electrique' => self::kw('Rénovation électrique', array('refaire l\'électricité', 'installation électrique complète', 'électricité maison ancienne'), 'chantier en site occupé, planification'),
                    'borne-recharge'        => self::kw('Installation de borne de recharge', array('borne IRVE', 'wallbox', 'recharge voiture électrique'), 'qualification IRVE, puissance, aides'),
                    'eclairage'             => self::kw('Installation d\'éclairage', array('éclairage LED', 'spots encastrés', 'éclairage extérieur'), 'ambiance, économies d\'énergie'),
                    'domotique'             => self::kw('Domotique', array('maison connectée', 'volets roulants connectés', 'pilotage chauffage'), 'confort, économies, solutions évolutives'),
                ),
            ),
            'chauffagiste' => array(
                'name'        => 'Chauffagiste',
                'schema_type' => 'HVACBusiness',
                'lexicon'     => 'chaudière gaz à condensation, pompe à chaleur air-eau, plancher chauffant, radiateurs, désembouage, entretien annuel, ramonage, poêle à granulés, COP, certification RGE QualiPAC, MaPrimeRénov\', CEE',
                'links'       => array('https://france-renov.gouv.fr' => 'France Rénov\''),
                'keywords'    => array(
                    'chauffagiste'          => self::kw('Chauffagiste', array('entreprise de chauffage', 'installateur chauffage', 'artisan chauffagiste'), 'présentation et prestations chauffage', true),
                    'pompe-a-chaleur'       => self::kw('Installation de pompe à chaleur', array('PAC air-eau', 'PAC air-air', 'aides pompe à chaleur'), 'dimensionnement, aides, rendement selon climat local'),
                    'entretien-chaudiere'   => self::kw('Entretien de chaudière', array('contrat d\'entretien', 'révision chaudière', 'attestation d\'entretien'), 'obligation légale, sécurité, contrat'),
                    'remplacement-chaudiere'=> self::kw('Remplacement de chaudière', array('chaudière à condensation', 'changement chaudière', 'chaudière fioul'), 'fin du fioul, alternatives, aides'),
                    'depannage-chauffage'   => self::kw('Dépannage chauffage', array('panne chaudière', 'plus de chauffage', 'chauffagiste urgence'), 'urgence hivernale, délais'),
                    'plancher-chauffant'    => self::kw('Plancher chauffant', array('chauffage au sol', 'plancher rafraîchissant'), 'confort, compatibilité PAC'),
                    'poele-granules'        => self::kw('Installation de poêle à granulés', array('poêle à pellets', 'poêle à bois', 'conduit de fumée'), 'autonomie, coût, conduit'),
                    'desembouage'           => self::kw('Désembouage de radiateurs', array('radiateurs froids', 'nettoyage circuit chauffage'), 'symptômes, gains énergétiques'),
                ),
            ),
            'climatisation' => array(
                'name'        => 'Climatisation',
                'schema_type' => 'HVACBusiness',
                'lexicon'     => 'climatiseur réversible, monosplit, multisplit, gainable, fluide frigorigène, attestation de capacité, SEER, SCOP, unité extérieure, entretien de clim, nuisances sonores',
                'links'       => array('https://www.ecologie.gouv.fr' => 'Ministère de la Transition écologique'),
                'keywords'    => array(
                    'climatisation'         => self::kw('Installation de climatisation', array('climatiseur', 'installateur clim', 'clim réversible'), 'présentation et prestations', true),
                    'clim-reversible'       => self::kw('Climatisation réversible', array('pompe à chaleur air-air', 'chauffage et rafraîchissement'), 'double usage été/hiver'),
                    'clim-gainable'         => self::kw('Climatisation gainable', array('gainable discret', 'climatisation centralisée'), 'esthétique, maisons et locaux'),
                    'entretien-clim'        => self::kw('Entretien de climatisation', array('nettoyage clim', 'recharge gaz clim', 'contrat entretien'), 'hygiène, performance, obligations'),
                    'depannage-clim'        => self::kw('Dépannage de climatisation', array('clim en panne', 'fuite de gaz clim'), 'urgence en période de chaleur'),
                ),
            ),
            'peintre' => array(
                'name'        => 'Peintre',
                'schema_type' => 'HousePainter',
                'lexicon'     => 'sous-couche, enduit de lissage, ratissage, peinture acrylique, glycéro, finition mate / satinée, papier peint, toile de verre, protection des sols, ravalement, crépi, lasure',
                'links'       => array('https://france-renov.gouv.fr' => 'France Rénov\''),
                'keywords'    => array(
                    'peintre'               => self::kw('Peintre en bâtiment', array('artisan peintre', 'entreprise de peinture'), 'présentation et prestations', true),
                    'peinture-interieure'   => self::kw('Peinture intérieure', array('peinture murs et plafonds', 'rénovation intérieure'), 'préparation des supports, finitions'),
                    'peinture-exterieure'   => self::kw('Peinture extérieure', array('peinture façade', 'peinture volets', 'boiseries extérieures'), 'résistance aux intempéries'),
                    'ravalement-facade'     => self::kw('Ravalement de façade', array('rénovation façade', 'nettoyage façade', 'enduit façade'), 'obligations municipales, techniques'),
                    'papier-peint'          => self::kw('Pose de papier peint', array('pose de revêtement mural', 'papier peint intissé'), 'tendances, préparation'),
                    'enduit-decoratif'      => self::kw('Enduit décoratif', array('béton ciré', 'tadelakt', 'stuc'), 'effets matière, entretien'),
                ),
            ),
            'menuisier' => array(
                'name'        => 'Menuisier',
                'schema_type' => 'GeneralContractor',
                'lexicon'     => 'menuiserie PVC, aluminium, bois, double vitrage, Uw, rupture de pont thermique, dormant, pose en rénovation, pose en dépose totale, volet roulant, porte d\'entrée, dressing, escalier',
                'links'       => array('https://france-renov.gouv.fr' => 'France Rénov\''),
                'keywords'    => array(
                    'menuisier'             => self::kw('Menuisier', array('artisan menuisier', 'entreprise de menuiserie'), 'présentation et prestations', true),
                    'fenetres'              => self::kw('Pose de fenêtres', array('remplacement de fenêtres', 'fenêtres PVC', 'double vitrage'), 'matériaux, performances, aides'),
                    'porte-entree'          => self::kw('Pose de porte d\'entrée', array('porte blindée', 'porte d\'entrée aluminium'), 'sécurité, isolation'),
                    'volets-roulants'       => self::kw('Volets roulants', array('motorisation de volets', 'volets solaires'), 'confort, sécurité'),
                    'dressing'              => self::kw('Dressing sur mesure', array('placard sur mesure', 'aménagement intérieur'), 'optimisation des espaces'),
                    'escalier'              => self::kw('Escalier sur mesure', array('escalier bois', 'rénovation d\'escalier'), 'styles, contraintes techniques'),
                    'portail'               => self::kw('Pose de portail', array('portail motorisé', 'clôture'), 'motorisation, matériaux'),
                ),
            ),
            'macon' => array(
                'name'        => 'Maçon',
                'schema_type' => 'GeneralContractor',
                'lexicon'     => 'fondations, dalle béton, parpaing, chaînage, linteau, mur porteur, IPN, extension, permis de construire, déclaration préalable, enduit, drainage, DTU 20.1',
                'links'       => array('https://www.service-public.fr/particuliers/vosdroits/F1986' => 'Service-Public — permis de construire'),
                'keywords'    => array(
                    'macon'                 => self::kw('Maçon', array('entreprise de maçonnerie', 'artisan maçon'), 'présentation et prestations', true),
                    'extension-maison'      => self::kw('Extension de maison', array('agrandissement maison', 'surélévation'), 'démarches, étapes, budget'),
                    'ouverture-mur-porteur' => self::kw('Ouverture de mur porteur', array('pose IPN', 'création d\'ouverture'), 'étude structure, sécurité'),
                    'dalle-beton'           => self::kw('Dalle béton', array('chape', 'terrasse béton'), 'épaisseur, ferraillage, séchage'),
                    'muret-cloture'         => self::kw('Construction de muret', array('mur de clôture', 'mur de soutènement'), 'règles d\'urbanisme, matériaux'),
                    'renovation-maconnerie' => self::kw('Rénovation de maçonnerie', array('reprise de fissures', 'rejointoiement pierre'), 'pathologies du bâti ancien'),
                ),
            ),
            'facadier' => array(
                'name'        => 'Façadier',
                'schema_type' => 'GeneralContractor',
                'lexicon'     => 'ravalement, enduit monocouche, crépi, ITE, hydrofuge façade, fissures, traitement anti-mousse, échafaudage, nettoyage basse pression, peinture pliolite',
                'links'       => array('https://france-renov.gouv.fr' => 'France Rénov\''),
                'keywords'    => array(
                    'facadier'              => self::kw('Façadier', array('entreprise de façade', 'artisan façadier'), 'présentation et prestations', true),
                    'ravalement'            => self::kw('Ravalement de façade', array('rénovation de façade', 'enduit façade'), 'obligations, étapes'),
                    'ite'                   => self::kw('Isolation thermique par l\'extérieur', array('ITE', 'isolation façade', 'bardage isolant'), 'performances, aides'),
                    'nettoyage-facade'      => self::kw('Nettoyage de façade', array('démoussage façade', 'hydrogommage'), 'techniques adaptées au support'),
                    'fissures-facade'       => self::kw('Traitement des fissures de façade', array('réparation fissures', 'microfissures'), 'diagnostic, solutions'),
                ),
            ),
            'isolation' => array(
                'name'        => 'Isolation',
                'schema_type' => 'GeneralContractor',
                'lexicon'     => 'résistance thermique R, laine de verre, laine de roche, ouate de cellulose, fibre de bois, pare-vapeur, ponts thermiques, DPE, passoire thermique, MaPrimeRénov\', RGE',
                'links'       => array('https://france-renov.gouv.fr' => 'France Rénov\''),
                'keywords'    => array(
                    'isolation'             => self::kw('Entreprise d\'isolation', array('isolation maison', 'artisan RGE'), 'présentation et prestations', true),
                    'isolation-combles'     => self::kw('Isolation des combles', array('combles perdus', 'combles aménagés', 'soufflage'), 'gains énergétiques, aides'),
                    'isolation-murs'        => self::kw('Isolation des murs', array('isolation intérieure', 'doublage'), 'techniques ITI / ITE'),
                    'isolation-sol'         => self::kw('Isolation du sol', array('isolation vide sanitaire', 'isolation sous-sol'), 'confort, humidité'),
                    'renovation-energetique'=> self::kw('Rénovation énergétique', array('rénovation globale', 'audit énergétique', 'DPE'), 'audit, gains de classe DPE'),
                ),
            ),
            'paysagiste' => array(
                'name'        => 'Paysagiste',
                'schema_type' => 'LandscapingBusiness',
                'lexicon'     => 'aménagement paysager, gazon en rouleau, arrosage automatique, taille de haies, élagage, terrasse bois, dallage, clôture végétale, massifs, essences locales, crédit d\'impôt services à la personne',
                'links'       => array('https://www.service-public.fr/particuliers/vosdroits/F12' => 'Service-Public — services à la personne'),
                'keywords'    => array(
                    'paysagiste'            => self::kw('Paysagiste', array('jardinier paysagiste', 'entreprise d\'espaces verts'), 'présentation et prestations', true),
                    'amenagement-jardin'    => self::kw('Aménagement de jardin', array('création de jardin', 'conception paysagère'), 'étude, plantations adaptées au climat local'),
                    'entretien-jardin'      => self::kw('Entretien de jardin', array('tonte', 'taille de haies', 'contrat d\'entretien'), 'saisonnalité, crédit d\'impôt'),
                    'elagage'               => self::kw('Élagage', array('abattage d\'arbre', 'taille d\'arbres'), 'sécurité, réglementation'),
                    'terrasse'              => self::kw('Création de terrasse', array('terrasse bois', 'terrasse composite', 'dallage'), 'matériaux, durabilité'),
                    'cloture'               => self::kw('Pose de clôture', array('clôture rigide', 'brise-vue', 'portillon'), 'règles de mitoyenneté'),
                ),
            ),
            'serrurier' => array(
                'name'        => 'Serrurier',
                'schema_type' => 'Locksmith',
                'lexicon'     => 'cylindre, serrure multipoints, A2P, porte claquée, ouverture fine, blindage, coffre-fort, rideau métallique, devis avant intervention',
                'links'       => array('https://www.economie.gouv.fr/dgccrf/Publications/Vie-pratique/Fiches-pratiques/Depannage-a-domicile' => 'DGCCRF — dépannage à domicile'),
                'keywords'    => array(
                    'serrurier'             => self::kw('Serrurier', array('artisan serrurier', 'serrurerie'), 'présentation et prestations', true),
                    'ouverture-porte'       => self::kw('Ouverture de porte', array('porte claquée', 'clés perdues', 'serrurier urgence'), 'urgence, ouverture fine sans casse, tarifs'),
                    'changement-serrure'    => self::kw('Changement de serrure', array('remplacement cylindre', 'serrure 3 points', 'serrure A2P'), 'niveau de sécurité, assurance'),
                    'porte-blindee'         => self::kw('Pose de porte blindée', array('blindage de porte', 'porte anti-effraction'), 'certifications, choix'),
                    'rideau-metallique'     => self::kw('Rideau métallique', array('dépannage rideau métallique', 'motorisation'), 'commerces, urgence'),
                ),
            ),
            'carreleur' => array(
                'name'        => 'Carreleur',
                'schema_type' => 'GeneralContractor',
                'lexicon'     => 'carrelage grès cérame, faïence, chape, ragréage, double encollage, joints époxy, plinthes, pose droite, pose en chevrons, grand format, étanchéité sous carrelage',
                'links'       => array('https://france-renov.gouv.fr' => 'France Rénov\''),
                'keywords'    => array(
                    'carreleur'             => self::kw('Carreleur', array('artisan carreleur', 'pose de carrelage'), 'présentation et prestations', true),
                    'carrelage-sol'         => self::kw('Pose de carrelage', array('carrelage sol', 'grand format'), 'préparation, types de pose'),
                    'faience'               => self::kw('Pose de faïence', array('faïence salle de bain', 'crédence cuisine'), 'étanchéité, finitions'),
                    'carrelage-exterieur'   => self::kw('Carrelage extérieur', array('terrasse carrelée', 'carrelage antidérapant', 'plots'), 'gel, pentes, matériaux'),
                    'douche-italienne'      => self::kw('Douche à l\'italienne', array('receveur extra-plat', 'douche maçonnée'), 'étanchéité, accessibilité'),
                ),
            ),
            'renovation' => array(
                'name'        => 'Rénovation',
                'schema_type' => 'GeneralContractor',
                'lexicon'     => 'rénovation clé en main, coordination des corps d\'état, plans, maîtrise d\'œuvre, planning de chantier, réception des travaux, garanties, assurance décennale, aides',
                'links'       => array('https://france-renov.gouv.fr' => 'France Rénov\''),
                'keywords'    => array(
                    'renovation'            => self::kw('Entreprise de rénovation', array('rénovation maison', 'travaux de rénovation'), 'présentation et prestations', true),
                    'renovation-maison'     => self::kw('Rénovation de maison', array('rénovation complète', 'maison ancienne'), 'clé en main, planning'),
                    'renovation-appartement'=> self::kw('Rénovation d\'appartement', array('rénovation intérieure', 'copropriété'), 'contraintes de copropriété'),
                    'renovation-cuisine'    => self::kw('Rénovation de cuisine', array('cuisine sur mesure', 'pose de cuisine'), 'ergonomie, délais'),
                    'amenagement-combles'   => self::kw('Aménagement de combles', array('combles aménageables', 'création de pièce'), 'faisabilité, démarches'),
                ),
            ),
        );
    }
}
