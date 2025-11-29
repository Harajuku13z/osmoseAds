<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

global $wpdb;
$table_name = $wpdb->prefix . 'osmose_ads_visits';

// Vérifier que la table existe
$table_exists = ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name);

// Récupérer les statistiques
 $total_visits = 0;
$visits_today = 0;
$visits_this_week = 0;
$visits_this_month = 0;
$visits_by_ad = array();
$visits_by_referrer = array();
$visits_by_device = array();
$visits_by_browser = array();
$recent_visits = array();
$visits_last7_labels = array();
$visits_last7_counts = array();
$total_bots_visits = 0;

// Filtrer par annonce si demandé
$filter_ad_id = isset($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;

if ($table_exists) {
    // Vérifier si la colonne is_bot existe pour pouvoir exclure les bots
    $has_is_bot_column = false;
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'is_bot'");
    if (!empty($columns)) {
        $has_is_bot_column = true;
    }

    // Total des visites (en excluant les bots si possible)
    if ($filter_ad_id > 0) {
        if ($has_is_bot_column) {
            $total_visits = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE ad_id = %d AND (is_bot != 1 OR is_bot IS NULL)",
                $filter_ad_id
            ));
            $total_bots_visits = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE ad_id = %d AND is_bot = 1",
                $filter_ad_id
            ));
        } else {
            $total_visits = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE ad_id = %d",
                $filter_ad_id
            ));
        }
    } else {
        if ($has_is_bot_column) {
            $total_visits = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE (is_bot != 1 OR is_bot IS NULL)");
            $total_bots_visits = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_bot = 1");
        } else {
            $total_visits = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        }
    }
    
    // Visites aujourd'hui (en excluant les bots si possible)
    if ($filter_ad_id > 0) {
        if ($has_is_bot_column) {
            $visits_today = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(visit_date) = %s AND ad_id = %d AND (is_bot != 1 OR is_bot IS NULL)",
                current_time('Y-m-d'),
                $filter_ad_id
            ));
        } else {
            $visits_today = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(visit_date) = %s AND ad_id = %d",
                current_time('Y-m-d'),
                $filter_ad_id
            ));
        }
    } else {
        if ($has_is_bot_column) {
            $visits_today = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(visit_date) = %s AND (is_bot != 1 OR is_bot IS NULL)",
                current_time('Y-m-d')
            ));
        } else {
            $visits_today = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(visit_date) = %s",
                current_time('Y-m-d')
            ));
        }
    }
    
    // Visites cette semaine (en excluant les bots si possible)
    if ($filter_ad_id > 0) {
        if ($has_is_bot_column) {
            $visits_this_week = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE YEARWEEK(visit_date, 1) = YEARWEEK(%s, 1) AND ad_id = %d AND (is_bot != 1 OR is_bot IS NULL)",
                current_time('Y-m-d'),
                $filter_ad_id
            ));
        } else {
            $visits_this_week = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE YEARWEEK(visit_date, 1) = YEARWEEK(%s, 1) AND ad_id = %d",
                current_time('Y-m-d'),
                $filter_ad_id
            ));
        }
    } else {
        if ($has_is_bot_column) {
            $visits_this_week = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE YEARWEEK(visit_date, 1) = YEARWEEK(%s, 1) AND (is_bot != 1 OR is_bot IS NULL)",
                current_time('Y-m-d')
            ));
        } else {
            $visits_this_week = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE YEARWEEK(visit_date, 1) = YEARWEEK(%s, 1)",
                current_time('Y-m-d')
            ));
        }
    }
    
    // Visites ce mois (en excluant les bots si possible)
    if ($filter_ad_id > 0) {
        if ($has_is_bot_column) {
            $visits_this_month = (int) $wpdb->get_var($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE YEAR(visit_date) = %d AND MONTH(visit_date) = %d AND ad_id = %d AND (is_bot != 1 OR is_bot IS NULL)",
                current_time('Y'),
                current_time('m'),
                $filter_ad_id
            )));
        } else {
            $visits_this_month = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE YEAR(visit_date) = %d AND MONTH(visit_date) = %d AND ad_id = %d",
                current_time('Y'),
                current_time('m'),
                $filter_ad_id
            ));
        }
    } else {
        if ($has_is_bot_column) {
            $visits_this_month = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE YEAR(visit_date) = %d AND MONTH(visit_date) = %d AND (is_bot != 1 OR is_bot IS NULL)",
                current_time('Y'),
                current_time('m')
            ));
        } else {
            $visits_this_month = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE YEAR(visit_date) = %d AND MONTH(visit_date) = %d",
                current_time('Y'),
                current_time('m')
            ));
        }
    }

    // Visites des 7 derniers jours (y compris aujourd'hui)
    $labels_tmp = array();
    $counts_tmp = array();
    $start_timestamp = current_time('timestamp') - (6 * DAY_IN_SECONDS);
    $start_date = date('Y-m-d', $start_timestamp);

    if ($filter_ad_id > 0) {
        if ($has_is_bot_column) {
            $rows_7days = $wpdb->get_results($wpdb->prepare(
                "SELECT DATE(visit_time) as visit_date, COUNT(*) as count
                 FROM $table_name
                 WHERE visit_time >= %s AND ad_id = %d AND (is_bot != 1 OR is_bot IS NULL)
                 GROUP BY DATE(visit_time)
                 ORDER BY visit_date ASC",
                $start_date . ' 00:00:00',
                $filter_ad_id
            ), ARRAY_A);
        } else {
            $rows_7days = $wpdb->get_results($wpdb->prepare(
                "SELECT DATE(visit_time) as visit_date, COUNT(*) as count
                 FROM $table_name
                 WHERE visit_time >= %s AND ad_id = %d
                 GROUP BY DATE(visit_time)
                 ORDER BY visit_date ASC",
                $start_date . ' 00:00:00',
                $filter_ad_id
            ), ARRAY_A);
        }
    } else {
        if ($has_is_bot_column) {
            $rows_7days = $wpdb->get_results($wpdb->prepare(
                "SELECT DATE(visit_time) as visit_date, COUNT(*) as count
                 FROM $table_name
                 WHERE visit_time >= %s AND (is_bot != 1 OR is_bot IS NULL)
                 GROUP BY DATE(visit_time)
                 ORDER BY visit_date ASC",
                $start_date . ' 00:00:00'
            ), ARRAY_A);
        } else {
            $rows_7days = $wpdb->get_results($wpdb->prepare(
                "SELECT DATE(visit_time) as visit_date, COUNT(*) as count
                 FROM $table_name
                 WHERE visit_time >= %s
                 GROUP BY DATE(visit_time)
                 ORDER BY visit_date ASC",
                $start_date . ' 00:00:00'
            ), ARRAY_A);
        }
    }

    $map_7days = array();
    if (!empty($rows_7days)) {
        foreach ($rows_7days as $row) {
            $map_7days[$row['visit_date']] = (int) $row['count'];
        }
    }

    for ($i = 0; $i < 7; $i++) {
        $ts = $start_timestamp + ($i * DAY_IN_SECONDS);
        $date_key = date('Y-m-d', $ts);
        $labels_tmp[] = date_i18n('d/m', $ts);
        $counts_tmp[] = isset($map_7days[$date_key]) ? (int) $map_7days[$date_key] : 0;
    }

    $visits_last7_labels = $labels_tmp;
    $visits_last7_counts = $counts_tmp;
    
    // Visites par annonce (en excluant les bots si possible)
    if ($has_is_bot_column) {
        $visits_by_ad = $wpdb->get_results(
            "SELECT ad_id, ad_slug, city_name, COUNT(*) as count 
             FROM $table_name 
             WHERE (is_bot != 1 OR is_bot IS NULL)
             GROUP BY ad_id, ad_slug, city_name 
             ORDER BY count DESC 
             LIMIT 20",
            ARRAY_A
        );
    } else {
        $visits_by_ad = $wpdb->get_results(
            "SELECT ad_id, ad_slug, city_name, COUNT(*) as count 
             FROM $table_name 
             GROUP BY ad_id, ad_slug, city_name 
             ORDER BY count DESC 
             LIMIT 20",
            ARRAY_A
        );
    }
    
    // Visites par referrer (domaine)
    if ($has_is_bot_column) {
        $visits_by_referrer = $wpdb->get_results(
            "SELECT referrer_domain, COUNT(*) as count 
             FROM $table_name 
             WHERE referrer_domain IS NOT NULL AND referrer_domain != '' AND (is_bot != 1 OR is_bot IS NULL)
             GROUP BY referrer_domain 
             ORDER BY count DESC 
             LIMIT 20",
            ARRAY_A
        );
    } else {
        $visits_by_referrer = $wpdb->get_results(
            "SELECT referrer_domain, COUNT(*) as count 
             FROM $table_name 
             WHERE referrer_domain IS NOT NULL AND referrer_domain != ''
             GROUP BY referrer_domain 
             ORDER BY count DESC 
             LIMIT 20",
            ARRAY_A
        );
    }
    
    // Visites par type d'appareil
    if ($has_is_bot_column) {
        $visits_by_device = $wpdb->get_results(
            "SELECT device_type, COUNT(*) as count 
             FROM $table_name 
             WHERE device_type IS NOT NULL AND (is_bot != 1 OR is_bot IS NULL)
             GROUP BY device_type 
             ORDER BY count DESC",
            ARRAY_A
        );
    } else {
        $visits_by_device = $wpdb->get_results(
            "SELECT device_type, COUNT(*) as count 
             FROM $table_name 
             WHERE device_type IS NOT NULL
             GROUP BY device_type 
             ORDER BY count DESC",
            ARRAY_A
        );
    }
    
    // Visites par navigateur
    if ($has_is_bot_column) {
        $visits_by_browser = $wpdb->get_results(
            "SELECT browser, COUNT(*) as count 
             FROM $table_name 
             WHERE browser IS NOT NULL AND (is_bot != 1 OR is_bot IS NULL)
             GROUP BY browser 
             ORDER BY count DESC",
            ARRAY_A
        );
    } else {
        $visits_by_browser = $wpdb->get_results(
            "SELECT browser, COUNT(*) as count 
             FROM $table_name 
             WHERE browser IS NOT NULL
             GROUP BY browser 
             ORDER BY count DESC",
            ARRAY_A
        );
    }
    
    // Dernières visites
    if ($has_is_bot_column) {
        $recent_visits = $wpdb->get_results(
            "SELECT * FROM $table_name 
             WHERE (is_bot != 1 OR is_bot IS NULL)
             ORDER BY visit_time DESC 
             LIMIT 100",
            ARRAY_A
        );
    } else {
        $recent_visits = $wpdb->get_results(
            "SELECT * FROM $table_name 
             ORDER BY visit_time DESC 
             LIMIT 100",
            ARRAY_A
        );
    }
}

?>

<div class="osmose-visit-stats-page">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4" style="margin-top: 20px;">
        <div>
            <h1 class="h3 mb-1"><?php _e('Statistiques de Visites', 'osmose-ads'); ?></h1>
            <p class="text-muted mb-0"><?php _e('Analysez les visites de vos annonces et leur provenance (les bots sont exclus des statistiques si détectés).', 'osmose-ads'); ?></p>
            <?php if (!empty($total_bots_visits)): ?>
                <p class="text-warning mb-0" style="font-size: 0.9em;">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?php printf(__('%d visite(s) de bot détectée(s) et exclue(s) des statistiques', 'osmose-ads'), number_format_i18n($total_bots_visits)); ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="recalculate-bots-visits-btn" onclick="if(confirm('<?php echo esc_js(__('Recalculer le statut Bot/Humain pour toutes les visites et appels ? Cette opération peut prendre quelques secondes.', 'osmose-ads')); ?>')) { recalculateBotStatusVisits(); }">
                <i class="bi bi-robot"></i> <?php _e('Recalculer Bots/Humains', 'osmose-ads'); ?>
            </button>
            <?php if ($filter_ad_id > 0): ?>
                <a href="<?php echo admin_url('admin.php?page=osmose-ads-visits'); ?>" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-1"></i><?php _e('Retirer le filtre', 'osmose-ads'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

<script>
function recalculateBotStatusVisits() {
    var $btn = jQuery('#recalculate-bots-visits-btn');
    
    jQuery.ajax({
        url: osmoseAds.ajax_url,
        type: 'POST',
        data: {
            action: 'osmose_ads_recalculate_bot_status',
            nonce: osmoseAds.nonce
        },
        beforeSend: function() {
            $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> <?php echo esc_js(__('Recalcul en cours...', 'osmose-ads')); ?>');
        },
        success: function(response) {
            if (response.success) {
                var stats = response.data.stats || {};
                var calls = stats.calls || {};
                var visits = stats.visits || {};
                
                alert(
                    '<?php echo esc_js(__('Recalibrage terminé.', 'osmose-ads')); ?>\n\n' +
                    '<?php echo esc_js(__('Appels', 'osmose-ads')); ?>: ' +
                    (calls.total || 0) + ' (<?php echo esc_js(__('Bots', 'osmose-ads')); ?>: ' + (calls.bots || 0) + ', <?php echo esc_js(__('Humains', 'osmose-ads')); ?>: ' + (calls.humans || 0) + ')\n' +
                    '<?php echo esc_js(__('Visites', 'osmose-ads')); ?>: ' +
                    (visits.total || 0) + ' (<?php echo esc_js(__('Bots', 'osmose-ads')); ?>: ' + (visits.bots || 0) + ', <?php echo esc_js(__('Humains', 'osmose-ads')); ?>: ' + (visits.humans || 0) + ')'
                );
                
                // Recharger la page pour rafraîchir les stats
                location.reload();
            } else {
                var msg = (response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('Erreur inconnue lors du recalcul.', 'osmose-ads')); ?>';
                alert('<?php echo esc_js(__('Erreur lors du recalcul:', 'osmose-ads')); ?> ' + msg);
            }
        },
        error: function() {
            alert('<?php echo esc_js(__('Erreur lors de la communication avec le serveur.', 'osmose-ads')); ?>');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bi bi-robot"></i> <?php echo esc_js(__('Recalculer Bots/Humains', 'osmose-ads')); ?>');
        }
    });
}
</script>

    
    <?php if (!$table_exists): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php _e('La table de tracking des visites n\'existe pas encore. Elle sera créée automatiquement lors de la première visite.', 'osmose-ads'); ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistiques globales -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-4 text-primary mb-2"><?php echo number_format_i18n($total_visits); ?></div>
                    <h6 class="text-muted"><?php _e('Total des Visites', 'osmose-ads'); ?></h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-4 text-success mb-2"><?php echo number_format_i18n($visits_today); ?></div>
                    <h6 class="text-muted"><?php _e('Aujourd\'hui', 'osmose-ads'); ?></h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-4 text-info mb-2"><?php echo number_format_i18n($visits_this_week); ?></div>
                    <h6 class="text-muted"><?php _e('Cette Semaine', 'osmose-ads'); ?></h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-4 text-warning mb-2"><?php echo number_format_i18n($visits_this_month); ?></div>
                    <h6 class="text-muted"><?php _e('Ce Mois', 'osmose-ads'); ?></h6>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Colonne gauche -->
        <div class="col-lg-8">
            <!-- Graphique 7 derniers jours -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up-arrow me-2"></i><?php _e('Visites sur les 7 derniers jours', 'osmose-ads'); ?>
                    </h5>
                    <?php if ($filter_ad_id > 0): ?>
                        <span class="badge bg-secondary"><?php _e('Filtré par annonce', 'osmose-ads'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($table_exists && !empty($visits_last7_labels)): ?>
                        <canvas id="osmose-visits-7days-chart" height="120"></canvas>
                    <?php else: ?>
                        <p class="text-muted mb-0"><?php _e('Aucune visite enregistrée pour les 7 derniers jours.', 'osmose-ads'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Visites par annonce -->
            <?php if (!empty($visits_by_ad)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i><?php _e('Visites par Annonce', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?php _e('Annonce', 'osmose-ads'); ?></th>
                                        <th><?php _e('Ville', 'osmose-ads'); ?></th>
                                        <th class="text-center"><?php _e('Visites', 'osmose-ads'); ?></th>
                                        <th class="text-center"><?php _e('Pourcentage', 'osmose-ads'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($visits_by_ad as $ad_visit): 
                                        $percentage = $total_visits > 0 ? ($ad_visit['count'] / $total_visits) * 100 : 0;
                                        $ad = $ad_visit['ad_id'] ? get_post($ad_visit['ad_id']) : null;
                                    ?>
                                        <tr>
                                            <td>
                                                <?php if ($ad): ?>
                                                    <a href="<?php echo admin_url('admin.php?page=osmose-ads-visits&ad_id=' . $ad_visit['ad_id']); ?>">
                                                        <?php echo esc_html($ad->post_title); ?>
                                                    </a>
                                                    <br><small class="text-muted">#<?php echo esc_html($ad_visit['ad_id']); ?></small>
                                                <?php else: ?>
                                                    <?php echo esc_html($ad_visit['ad_slug'] ?: 'Annonce #' . $ad_visit['ad_id']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html($ad_visit['city_name'] ?: '—'); ?></td>
                                            <td class="text-center">
                                                <strong><?php echo number_format_i18n($ad_visit['count']); ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo esc_attr($percentage); ?>%">
                                                        <?php echo number_format_i18n($percentage, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Dernières visites -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i><?php _e('Dernières Visites', 'osmose-ads'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_visits)): ?>
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th><?php _e('Date/Heure', 'osmose-ads'); ?></th>
                                        <th><?php _e('Annonce', 'osmose-ads'); ?></th>
                                        <th><?php _e('Ville', 'osmose-ads'); ?></th>
                                        <th><?php _e('Provenance', 'osmose-ads'); ?></th>
                                        <th><?php _e('Appareil', 'osmose-ads'); ?></th>
                                        <th><?php _e('Navigateur', 'osmose-ads'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_visits as $visit): 
                                        $ad = $visit['ad_id'] ? get_post($visit['ad_id']) : null;
                                        $referrer_domain_short = $visit['referrer_domain'] ? (strlen($visit['referrer_domain']) > 30 ? substr($visit['referrer_domain'], 0, 30) . '...' : $visit['referrer_domain']) : 'Direct';
                                    ?>
                                        <tr>
                                            <td>
                                                <small><?php echo date_i18n('d/m/Y H:i', strtotime($visit['visit_time'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($ad): ?>
                                                    <a href="<?php echo get_permalink($visit['ad_id']); ?>" target="_blank">
                                                        <?php echo esc_html(strlen($ad->post_title) > 40 ? substr($ad->post_title, 0, 40) . '...' : $ad->post_title); ?>
                                                    </a>
                                                    <br><small class="text-muted">#<?php echo esc_html($visit['ad_id']); ?></small>
                                                <?php else: ?>
                                                    <?php echo esc_html($visit['ad_slug'] ?: 'Annonce #' . $visit['ad_id']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html($visit['city_name'] ?: '—'); ?></td>
                                            <td>
                                                <?php if ($visit['referrer_domain']): ?>
                                                    <a href="<?php echo esc_url($visit['referrer']); ?>" target="_blank" title="<?php echo esc_attr($visit['referrer']); ?>">
                                                        <?php echo esc_html($referrer_domain_short); ?>
                                                    </a>
                                                    <?php if ($visit['utm_source']): ?>
                                                        <br><small class="text-muted">UTM: <?php echo esc_html($visit['utm_source']); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted"><?php _e('Direct', 'osmose-ads'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?php echo esc_html($visit['device_type'] ?: '—'); ?></small></td>
                                            <td><small><?php echo esc_html($visit['browser'] ?: '—'); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">
                            <i class="bi bi-eye-slash me-2"></i>
                            <?php _e('Aucune visite enregistrée pour le moment.', 'osmose-ads'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Colonne droite -->
        <div class="col-lg-4">
            <!-- Visites par provenance -->
            <?php if (!empty($visits_by_referrer)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i><?php _e('Provenance', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($visits_by_referrer as $referrer): 
                                $percentage = $total_visits > 0 ? ($referrer['count'] / $total_visits) * 100 : 0;
                            ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <strong><?php echo esc_html($referrer['referrer_domain']); ?></strong>
                                        <br><small class="text-muted"><?php echo number_format_i18n($referrer['count']); ?> visites</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?php echo number_format_i18n($percentage, 1); ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Visites par appareil -->
            <?php if (!empty($visits_by_device)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-device-hdd me-2"></i><?php _e('Type d\'Appareil', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($visits_by_device as $device): 
                            $percentage = $total_visits > 0 ? ($device['count'] / $total_visits) * 100 : 0;
                        ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo esc_html($device['device_type']); ?></span>
                                    <span class="text-muted"><?php echo number_format_i18n($device['count']); ?> (<?php echo number_format_i18n($percentage, 1); ?>%)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Visites par navigateur -->
            <?php if (!empty($visits_by_browser)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-browser-chrome me-2"></i><?php _e('Navigateur', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($visits_by_browser as $browser): 
                            $percentage = $total_visits > 0 ? ($browser['count'] / $total_visits) * 100 : 0;
                        ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo esc_html($browser['browser']); ?></span>
                                    <span class="text-muted"><?php echo number_format_i18n($browser['count']); ?> (<?php echo number_format_i18n($percentage, 1); ?>%)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($table_exists && !empty($visits_last7_labels)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    (function() {
        var ctx = document.getElementById('osmose-visits-7days-chart');
        if (!ctx) {
            return;
        }

        var labels = <?php echo wp_json_encode(array_values($visits_last7_labels)); ?>;
        var dataCounts = <?php echo wp_json_encode(array_values($visits_last7_counts)); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '<?php echo esc_js(__('Visites', 'osmose-ads')); ?>',
                    data: dataCounts,
                    borderWidth: 1,
                    borderRadius: 4,
                    backgroundColor: 'rgba(34, 113, 177, 0.6)',
                    borderColor: 'rgba(34, 113, 177, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' <?php echo esc_js(__('visites', 'osmose-ads')); ?>';
                            }
                        }
                    }
                }
            }
        });
    })();
    </script>
<?php endif; ?>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

