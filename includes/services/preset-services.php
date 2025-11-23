<?php
/**
 * Services préconfigurés pour la création de templates
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Liste des services préconfigurés avec leurs configurations
 */
function osmose_ads_get_preset_services() {
    return array(
        'plomberie' => array(
            'name' => 'Dépannage et réparation de fuites d\'eau',
            'category' => 'Plomberie',
            'icon' => 'fa-faucet-drip',
            'keywords' => 'fuite eau, plomberie, réparation, dépannage urgence, robinet, canalisation, débouchage',
            'description' => 'Service professionnel de dépannage et réparation de fuites d\'eau, intervention rapide 24/7',
            'sections' => array(
                'intervention_urgence' => 'Intervention d\'urgence 24h/24',
                'types_reparations' => 'Types de réparations effectuées',
                'zone_intervention' => 'Zone d\'intervention',
                'devis_gratuit' => 'Devis gratuit et sans engagement',
                'garantie' => 'Garantie sur les interventions'
            )
        ),
        'electricite' => array(
            'name' => 'Dépannage et installation électrique',
            'category' => 'Électricité',
            'icon' => 'fa-bolt',
            'keywords' => 'électricien, dépannage électrique, installation électrique, panne courant, tableau électrique',
            'description' => 'Électricien professionnel pour tous vos besoins en dépannage et installation électrique',
            'sections' => array(
                'intervention_rapide' => 'Intervention rapide en cas de panne',
                'installations' => 'Installations électriques neuves',
                'mise_norme' => 'Mise aux normes',
                'depannage' => 'Dépannage électrique',
                'diagnostic' => 'Diagnostic complet'
            )
        ),
        'chauffage' => array(
            'name' => 'Installation et réparation de chauffage',
            'category' => 'Chauffage',
            'icon' => 'fa-temperature-high',
            'keywords' => 'chauffage, chaudière, plomberie chauffage, radiateur, installation chauffage',
            'description' => 'Spécialiste en installation et réparation de systèmes de chauffage',
            'sections' => array(
                'installation' => 'Installation de systèmes de chauffage',
                'entretien' => 'Entretien et maintenance',
                'reparation' => 'Réparation de chaudières',
                'optimisation' => 'Optimisation énergétique',
                'conseil' => 'Conseil et expertise'
            )
        ),
        'renovation' => array(
            'name' => 'Rénovation et rénovation énergétique',
            'category' => 'Rénovation',
            'icon' => 'fa-hammer',
            'keywords' => 'rénovation, rénovation énergétique, isolation, travaux, maison',
            'description' => 'Expert en rénovation et rénovation énergétique pour améliorer votre confort',
            'sections' => array(
                'isolation' => 'Isolation thermique et phonique',
                'chauffage' => 'Modernisation du chauffage',
                'ventilation' => 'Installation de ventilation',
                'fenetres' => 'Remplacement de fenêtres',
                'aide_financiere' => 'Aides financières disponibles'
            )
        ),
        'peinture' => array(
            'name' => 'Peinture intérieure et extérieure',
            'category' => 'Peinture',
            'icon' => 'fa-paint-roller',
            'keywords' => 'peintre, peinture intérieure, peinture extérieure, ravalement, décoration',
            'description' => 'Peintre professionnel pour vos travaux de peinture intérieure et extérieure',
            'sections' => array(
                'peinture_interieure' => 'Peinture intérieure',
                'peinture_exterieure' => 'Peinture extérieure',
                'preparation' => 'Préparation des supports',
                'finition' => 'Finition et décoration',
                'conseil_couleurs' => 'Conseil en couleurs'
            )
        ),
        'menuiserie' => array(
            'name' => 'Menuiserie et pose de fenêtres',
            'category' => 'Menuiserie',
            'icon' => 'fa-door-open',
            'keywords' => 'menuiserie, fenêtres, portes, parquet, agencement',
            'description' => 'Menuisier professionnel pour vos travaux de menuiserie et pose de fenêtres',
            'sections' => array(
                'fenetres' => 'Pose de fenêtres',
                'portes' => 'Installation de portes',
                'parquet' => 'Pose de parquet',
                'agencement' => 'Agencement sur mesure',
                'reparation' => 'Réparation et rénovation'
            )
        ),
        'carrelage' => array(
            'name' => 'Carrelage et revêtement de sol',
            'category' => 'Carrelage',
            'icon' => 'fa-th-large',
            'keywords' => 'carrelage, faïence, revêtement sol, salle de bain, cuisine',
            'description' => 'Spécialiste en pose de carrelage et revêtements de sol',
            'sections' => array(
                'carrelage_sol' => 'Carrelage de sol',
                'faïence_mur' => 'Faïence et carrelage mural',
                'revetement' => 'Autres revêtements',
                'preparation' => 'Préparation des supports',
                'joints' => 'Pose et traitement des joints'
            )
        ),
        'toiture' => array(
            'name' => 'Couverture et toiture',
            'category' => 'Toiture',
            'icon' => 'fa-home',
            'keywords' => 'toiture, couverture, tuiles, ardoise, zinguerie, gouttières',
            'description' => 'Couvreur professionnel pour tous vos travaux de toiture',
            'sections' => array(
                'reparation' => 'Réparation de toiture',
                'renovation' => 'Rénovation complète',
                'gouttieres' => 'Installation de gouttières',
                'zinguerie' => 'Travaux de zinguerie',
                'isolation' => 'Isolation de toiture'
            )
        ),
        'isolation' => array(
            'name' => 'Isolation thermique et phonique',
            'category' => 'Isolation',
            'icon' => 'fa-snowflake',
            'keywords' => 'isolation, isolation thermique, isolation phonique, laine de verre, ouate cellulose',
            'description' => 'Expert en isolation thermique et phonique pour améliorer votre confort',
            'sections' => array(
                'isolation_murs' => 'Isolation des murs',
                'isolation_combles' => 'Isolation des combles',
                'isolation_plancher' => 'Isolation des planchers',
                'ventilation' => 'Ventilation et aération',
                'economie_energie' => 'Économies d\'énergie'
            )
        ),
        'serrurerie' => array(
            'name' => 'Serrurerie et sécurité',
            'category' => 'Serrurerie',
            'icon' => 'fa-lock',
            'keywords' => 'serrurier, dépannage serrurerie, ouverture porte, serrure, sécurité',
            'description' => 'Serrurier professionnel pour vos besoins en serrurerie et sécurité',
            'sections' => array(
                'depannage' => 'Dépannage et ouverture de porte',
                'changement_serrure' => 'Changement de serrure',
                'securite' => 'Renforcement de sécurité',
                'surmesure' => 'Serrurerie sur mesure',
                'intervention_urgence' => 'Intervention d\'urgence 24/7'
            )
        ),
    );
}

