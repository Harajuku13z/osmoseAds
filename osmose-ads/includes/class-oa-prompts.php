<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Construction des prompts et du schéma JSON des pages.
 */
class OA_Prompts {

    /** Plans éditoriaux tournants : deux villes voisines n'auront pas la même structure. */
    private static $structures = array(
        'Problème → solutions → méthode → matériaux → budget',
        'Signes qui doivent alerter → diagnostic → intervention → durabilité → entretien',
        'Pourquoi agir maintenant → nos prestations détaillées → déroulé du chantier → garanties → coûts',
        'Contexte local et bâti → techniques adaptées → étapes → erreurs à éviter → financement',
        'Cas concrets fréquents → réponse technique → délais → sécurité et normes → conseils d\'entretien',
        'Comparatif des solutions → avantages/inconvénients → recommandation → mise en œuvre → suivi',
    );

    private static $openers = array(
        'commence par une situation concrète vécue par un habitant',
        'commence par un constat technique précis',
        'commence par la question que se pose le client',
        'commence par un conseil d\'expert immédiatement utile',
        'commence par l\'enjeu (sécurité, confort ou budget) propre à ce besoin',
    );

    public static function page_schema() {
        $str  = array('type' => 'string');
        $strs = array('type' => 'array', 'items' => $str);
        $obj  = function (array $props) {
            return array(
                'type'                 => 'object',
                'properties'           => $props,
                'required'             => array_keys($props),
                'additionalProperties' => false,
            );
        };

        return $obj(array(
            'seo_title'        => $str,
            'meta_description' => $str,
            'h1'               => $str,
            'hero_tagline'     => $str,
            'intro'            => $strs,
            'sections'         => array('type' => 'array', 'items' => $obj(array(
                'heading'    => $str,
                'paragraphs' => $strs,
                'bullets'    => $strs,
            ))),
            'steps'            => $obj(array(
                'heading' => $str,
                'items'   => array('type' => 'array', 'items' => $obj(array('title' => $str, 'text' => $str))),
            )),
            'local'            => $obj(array('heading' => $str, 'paragraphs' => $strs)),
            'faq'              => array('type' => 'array', 'items' => $obj(array('question' => $str, 'answer' => $str))),
            'cta'              => $obj(array('heading' => $str, 'text' => $str)),
            'image_alts'       => $strs,
            'excerpt'          => $str,
        ));
    }

    public static function system() {
        $s = OA_Settings::all();
        $rules = trim((string) $s['extra_rules']);

        return "Tu es un rédacteur web senior spécialisé en SEO local pour les artisans du bâtiment en France, et un expert technique du métier concerné. "
            . "Tu écris en français impeccable, au « {$s['person']} » de l'entreprise, avec un ton {$s['tone']}.\n\n"
            . "PRINCIPES :\n"
            . "- Chaque page doit être réellement utile : informations techniques exactes, conseils concrets, fourchettes de prix prudentes (« à partir de », « en moyenne ») uniquement quand c'est pertinent.\n"
            . "- Zéro contenu générique : chaque paragraphe doit apporter une information. Pas de remplissage.\n"
            . "- Personnalisation locale crédible : climat et contexte régional, type de bâti courant dans la région, communes voisines. N'invente JAMAIS de faits vérifiables (noms de rues, chantiers réalisés, chiffres, avis clients, distinctions, prix exacts, monuments) que tu ne connais pas avec certitude.\n"
            . "- Interdits : « n'hésitez pas », « dans le monde d'aujourd'hui », « en effet », « véritable », « incontournable », « de A à Z », « que vous soyez… ou… », listes de superlatifs, promesses non tenables (« le moins cher », « n°1 »).\n"
            . "- Lisibilité (critères AIOSEO TruSEO) : phrases de 20 mots maximum pour au moins 80 % d'entre elles, paragraphes de 2 à 4 phrases (moins de 120 mots), au moins 30 % de phrases contenant un mot de transition (ainsi, en revanche, par ailleurs, d'abord, ensuite, enfin, donc, toutefois, par exemple, de plus, c'est pourquoi, en outre, cependant), voix active, pas deux phrases consécutives qui commencent par le même mot.\n"
            . "- Tu peux mettre en gras 2 à 4 expressions importantes avec **double astérisque**. Aucun autre balisage (ni HTML, ni titres markdown, ni liens).\n"
            . ($rules ? "\nCONSIGNES SUPPLÉMENTAIRES DE L'ENTREPRISE :\n{$rules}\n" : '');
    }

    /**
     * @param array $ctx  Contexte construit par OA_Generator::context().
     */
    public static function user(array $ctx, $retry_note = '') {
        $s       = OA_Settings::all();
        $city    = $ctx['city'];
        $kw      = $ctx['keyword'];
        $trade   = $ctx['trade'];
        $focus   = $ctx['focus'];
        $seed    = abs(crc32($city['code'] . '|' . $kw['id']));
        $struct  = self::$structures[$seed % count(self::$structures)];
        $opener  = self::$openers[($seed >> 3) % count(self::$openers)];
        $words   = (int) $s['word_target'];
        $nsect   = $words >= 1500 ? 6 : ($words >= 1000 ? 5 : 4);
        $occ     = max(5, (int) round($words / 160));
        $neigh   = $ctx['neighbours'] ? implode(', ', wp_list_pluck($ctx['neighbours'], 'name')) : 'non disponible';
        $sibs    = $ctx['siblings'] ? implode(' ; ', $ctx['siblings']) : 'aucune';
        $company = $s['company_name'];

        $lines   = array();
        $lines[] = "Rédige le contenu complet d'une page de service locale.";
        $lines[] = '';
        $lines[] = 'EXPRESSION-CLÉ PRINCIPALE (à reprendre EXACTEMENT, même orthographe) : « ' . $focus . ' »';
        $lines[] = 'Service : ' . $kw['label'] . ' — Métier : ' . $trade['name'];
        if (!empty($kw['secondary'])) {
            $lines[] = 'Mots-clés secondaires à intégrer naturellement : ' . implode(', ', $kw['secondary']);
        }
        $lines[] = 'Champ lexical technique à mobiliser (choisis ce qui est pertinent) : ' . $trade['lexicon'];
        if (!empty($kw['angle'])) {
            $lines[] = 'Angle éditorial de cette page : ' . $kw['angle'];
        }
        if (!empty($kw['hub'])) {
            $lines[] = "Cette page est la page PRINCIPALE du métier pour la ville : elle présente l'entreprise et donne une vue d'ensemble des prestations, chacune en quelques phrases.";
        } else {
            $lines[] = 'NE développe PAS ces sujets qui ont leur propre page pour cette ville (évite la cannibalisation, cite-les au plus en une phrase) : ' . $sibs;
        }
        $lines[] = '';
        $lines[] = 'VILLE : ' . $city['name'] . ' (' . $city['postcode'] . '), ' . $city['dept_name'] . ' (' . $city['dept_code'] . '), région ' . $city['region']
            . ($city['population'] ? ', environ ' . number_format_i18n($city['population']) . ' habitants' : '') . '.';
        $lines[] = 'Communes proches également desservies : ' . $neigh . '.';
        $lines[] = '';
        $lines[] = 'ENTREPRISE : ' . $company
            . ($s['base_city'] ? ', basée à ' . $s['base_city'] : '')
            . ($s['years'] ? ', ' . $s['years'] . " ans d'expérience" : '') . '.';
        if ($s['certifications']) {
            $lines[] = 'Garanties / certifications réelles : ' . preg_replace('/\s*\n\s*/', ', ', $s['certifications']) . '. (N\'en cite aucune autre.)';
        }
        if ($s['usps']) {
            $lines[] = 'Points forts réels : ' . preg_replace('/\s*\n\s*/', ' ; ', $s['usps']);
        }
        $lines[] = '';
        $lines[] = 'CONTRAINTES SEO (vérifiées automatiquement, la page est rejetée si elles ne sont pas respectées) :';
        $lines[] = '1. seo_title : commence EXACTEMENT par « ' . self::ucfirst($focus) . ' », 45 à 60 caractères au total, accrocheur (bénéfice ou garantie).';
        $lines[] = '2. meta_description : 130 à 155 caractères, contient « ' . $focus . ' », un bénéfice concret et un appel à l\'action.';
        $lines[] = '3. h1 : contient « ' . $focus . ' », différent du seo_title, 70 caractères maximum.';
        $lines[] = '4. intro : 2 paragraphes ; la PREMIÈRE phrase contient « ' . $focus . ' ». ' . ucfirst($opener) . '.';
        $lines[] = '5. sections : exactement ' . $nsect . ' sections suivant ce fil conducteur : ' . $struct . '. Chaque section : un titre H2 précis (pas de titre générique), 2 à 3 paragraphes, et 0 à 5 puces courtes (tableau vide si non pertinent). L\'expression-clé apparaît dans 1 ou 2 titres de section maximum ; les autres titres utilisent les mots-clés secondaires.';
        $lines[] = '6. steps : 4 à 5 étapes concrètes du déroulé d\'intervention (titre court + 1 à 2 phrases).';
        $lines[] = '7. local : section dédiée à ' . $city['name'] . ' et ses environs (contexte climatique/bâti régional, communes voisines, réactivité). 2 paragraphes, sans faits inventés.';
        $lines[] = '8. faq : 5 questions réellement posées par les clients (prix, délais, aides, garanties, réglementation…) avec des réponses de 2 à 4 phrases ; au moins une question mentionne ' . $city['name'] . '.';
        $lines[] = '9. cta : titre court + 1 à 2 phrases incitant à demander un devis.';
        $lines[] = '10. Longueur totale visée : ' . $words . ' mots (±10 %). Utilise l\'expression-clé exacte environ ' . $occ . ' fois dans l\'ensemble (intro, sections, local, faq), de façon naturelle.';
        $lines[] = '11. image_alts : ' . max(1, (int) $ctx['image_count']) . ' textes alternatifs descriptifs (photos de chantiers de ' . mb_strtolower($trade['name']) . '), le premier contient l\'expression-clé.';
        $lines[] = '12. hero_tagline : une phrase d\'accroche de 8 à 14 mots. excerpt : résumé de 25 à 35 mots.';

        if ($retry_note) {
            $lines[] = '';
            $lines[] = 'CORRECTIONS DEMANDÉES SUR LA VERSION PRÉCÉDENTE : ' . $retry_note;
        }

        return implode("\n", $lines);
    }

    public static function keyword_suggestion($trade_name, $city_name, array $existing) {
        return "Métier : {$trade_name}. Ville cible : {$city_name}.\n"
            . 'Propose 12 prestations distinctes que les particuliers recherchent sur Google pour ce métier (intentions de recherche locales à forte valeur commerciale), sans doublon avec : ' . implode(', ', $existing) . ".\n"
            . "Chaque ligne au format : Libellé de la prestation | 3 mots-clés secondaires séparés par des virgules | angle éditorial en une phrase.\n"
            . "Le libellé doit pouvoir être suivi de « à {$city_name} » (ex. « Rénovation de toiture »). Renvoie les 12 lignes dans le champ text, séparées par des retours à la ligne.";
    }

    public static function ucfirst($s) {
        return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }
}
