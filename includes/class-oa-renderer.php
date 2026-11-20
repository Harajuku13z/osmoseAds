<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Transforme le JSON de l'IA en HTML propre, sémantique et éditable.
 * Tout texte issu de l'IA est échappé ; seul **gras** est converti.
 * Les éléments dépendant des réglages (boutons, badges, maillage) passent par des
 * shortcodes pour rester à jour si l'entreprise modifie ses infos.
 */
class OA_Renderer {

    private $data;
    private $ctx;
    private $images;
    private $img_i = 0;

    /**
     * @param array $data   Sortie IA (validée).
     * @param array $ctx    Contexte (focus, ville, hub_url…).
     * @param int[] $images IDs des pièces jointes à utiliser (déjà ordonnés).
     */
    public function __construct(array $data, array $ctx, array $images) {
        $this->data   = $data;
        $this->ctx    = $ctx;
        $this->images = array_values(array_filter(array_map('intval', $images), 'wp_attachment_is_image'));
    }

    public static function inline($text) {
        $text = esc_html(trim((string) $text));
        $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text);
        return str_replace('*', '', $text);
    }

    private function p(array $paras, $class = '') {
        $out = '';
        foreach ($paras as $p) {
            if ('' !== trim((string) $p)) {
                $out .= '<p' . ($class ? ' class="' . esc_attr($class) . '"' : '') . '>' . self::inline($p) . "</p>\n";
            }
        }
        return $out;
    }

    private function alt($index) {
        $alts = isset($this->data['image_alts']) ? (array) $this->data['image_alts'] : array();
        if (0 === $index) {
            // Garantit l'expression-clé dans l'attribut alt de la première image.
            return OA_Prompts::ucfirst($this->ctx['focus']);
        }
        if (!empty($alts[$index])) {
            return trim($alts[$index]);
        }
        return OA_Prompts::ucfirst($this->ctx['keyword']['label']) . ' — ' . $this->ctx['city']['name'] . ' (' . ($index + 1) . ')';
    }

    private function next_image($size = 'large', $class = 'oa-figure', $attrs = array()) {
        if (!isset($this->images[$this->img_i])) {
            return '';
        }
        $id  = $this->images[$this->img_i];
        $alt = $this->alt($this->img_i);
        $this->img_i++;
        $src = wp_get_attachment_image_src($id, $size);
        if (!$src) {
            return '';
        }
        $extra = '';
        foreach ($attrs as $k => $v) {
            $extra .= ' ' . $k . '="' . esc_attr($v) . '"';
        }
        return '<figure class="' . esc_attr($class) . '"><img class="wp-image-' . $id . '" src="' . esc_url($src[0]) . '" width="' . (int) $src[1] . '" height="' . (int) $src[2] . '" alt="' . esc_attr($alt) . '"' . $extra . " /></figure>\n";
    }

    public function html() {
        $d     = $this->data;
        $ctx   = $this->ctx;
        $focus = $ctx['focus'];
        $s     = OA_Settings::all();
        $html  = '';

        // HERO
        $hero_img = $this->next_image('full', 'oa-hero__media', array('fetchpriority' => 'high', 'loading' => 'eager', 'decoding' => 'async'));
        $html    .= '<div class="oa-hero alignwide' . ($hero_img ? ' has-media' : '') . "\">\n" . $hero_img
            . '<div class="oa-hero__inner"><p class="oa-hero__tagline">' . self::inline($d['hero_tagline']) . "</p>\n[oa_cta]\n[oa_trust]</div>\n</div>\n";

        // INTRO
        $html .= '<div class="oa-intro">' . "\n" . $this->p((array) $d['intro']) . "</div>\n";

        if (!empty($ctx['hub_url']) && !empty($ctx['hub_label'])) {
            $html .= '<p class="oa-hublink">' . esc_html('Cette prestation fait partie de notre offre de ') . '<a href="' . esc_url($ctx['hub_url']) . '">' . esc_html(mb_strtolower($ctx['hub_label'])) . ' ' . esc_html(OA_Trades::preposition_city($ctx['city']['name'])) . "</a>.</p>\n";
        }

        // SECTIONS (une image toutes les deux sections, alternée gauche/droite)
        $sections = (array) $d['sections'];
        $ext_done = false;
        foreach ($sections as $i => $sec) {
            $img   = (0 === $i % 2) ? $this->next_image('large', 'oa-figure oa-figure--' . ((0 === $i % 4) ? 'right' : 'left'), array('loading' => 'lazy', 'decoding' => 'async')) : '';
            $html .= '<section class="oa-section' . ($img ? ' has-media' : '') . "\">\n";
            $html .= '<h2>' . self::inline($sec['heading']) . "</h2>\n" . $img;
            $html .= $this->p((array) $sec['paragraphs']);
            $bullets = array_filter(array_map('trim', (array) $sec['bullets']));
            if ($bullets) {
                $html .= "<ul class=\"oa-list\">\n";
                foreach ($bullets as $b) {
                    $html .= '<li>' . self::inline($b) . "</li>\n";
                }
                $html .= "</ul>\n";
            }
            // Lien externe d'autorité, une fois, après la 2e section.
            if (!$ext_done && $i >= 1 && $s['external_links'] && !empty($ctx['trade']['links'])) {
                $url   = key($ctx['trade']['links']);
                $label = current($ctx['trade']['links']);
                $html .= '<p class="oa-note"><strong>Bon à savoir :</strong> pour les règles en vigueur et les aides disponibles, consultez le site officiel <a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($label) . "</a>.</p>\n";
                $ext_done = true;
            }
            $html .= "</section>\n";
        }

        // ÉTAPES
        if (!empty($d['steps']['items'])) {
            $html .= "<section class=\"oa-steps\">\n<h2>" . self::inline($d['steps']['heading']) . "</h2>\n<ol class=\"oa-steps__list\">\n";
            foreach ((array) $d['steps']['items'] as $step) {
                $html .= '<li><strong class="oa-steps__title">' . esc_html(str_replace('*', '', $step['title'])) . '</strong> ' . self::inline($step['text']) . "</li>\n";
            }
            $html .= "</ol>\n</section>\n";
        }

        // LOCAL
        if (!empty($d['local']['paragraphs'])) {
            $html .= "<section class=\"oa-local\">\n<h2>" . self::inline($d['local']['heading']) . "</h2>\n" . $this->p((array) $d['local']['paragraphs']);
            if (!empty($ctx['neighbours'])) {
                $html .= '<p class="oa-zone"><strong>Zone d\'intervention :</strong> ' . esc_html($ctx['city']['name'] . ', ' . implode(', ', wp_list_pluck($ctx['neighbours'], 'name'))) . " et alentours.</p>\n";
            }
            $html .= "</section>\n";
        }

        // GALERIE (images restantes)
        $rest = array();
        while (isset($this->images[$this->img_i]) && count($rest) < 6) {
            $rest[] = $this->next_image('medium_large', 'oa-gallery__item', array('loading' => 'lazy', 'decoding' => 'async'));
        }
        $rest = array_filter($rest);
        if ($rest) {
            $html .= "<section class=\"oa-gallery\">\n<h2>" . esc_html('Nos réalisations en images') . "</h2>\n<div class=\"oa-gallery__grid\">\n" . implode('', $rest) . "</div>\n</section>\n";
        }

        // FAQ — le titre contient toujours l'expression-clé (critère « sous-titres »).
        if (!empty($d['faq'])) {
            $html .= "<section class=\"oa-faq\">\n<h2>" . esc_html('Questions fréquentes : ' . $focus) . "</h2>\n";
            foreach ((array) $d['faq'] as $j => $f) {
                $html .= '<details class="oa-faq__item"' . (0 === $j ? ' open' : '') . '><summary>' . esc_html(str_replace('*', '', $f['question'])) . "</summary>\n<div class=\"oa-faq__answer\">" . $this->p(array($f['answer'])) . "</div></details>\n";
            }
            $html .= "</section>\n";
        }

        // CTA FINAL avec lien interne garanti.
        $home  = $s['quote_url'] && 0 === strpos($s['quote_url'], home_url()) ? $s['quote_url'] : home_url('/');
        $html .= "<section class=\"oa-cta alignwide\">\n<h2>" . self::inline($d['cta']['heading']) . "</h2>\n" . $this->p(array($d['cta']['text']))
            . '<p class="oa-cta__company">' . esc_html('Une réalisation signée ') . '<a href="' . esc_url($home) . '">' . esc_html($s['company_name']) . "</a>, votre artisan de confiance.</p>\n[oa_cta]\n</section>\n";

        // MAILLAGE dynamique (autres services de la ville + villes voisines).
        $html .= "[oa_maillage]\n";

        return "<div class=\"oa-page\">\n" . $html . "</div>\n";
    }

    public function used_images() {
        return array_slice($this->images, 0, $this->img_i);
    }
}
