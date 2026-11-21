/* Osmose Ads — admin */
(function ($) {
  'use strict';

  function api(action, data) {
    data = data || {};
    data.action = 'oa_' + action;
    data.nonce = OA.nonce;
    return $.post(OA.ajax, data).then(function (r) {
      if (r && r.success) return r.data;
      return $.Deferred().reject((r && r.data && r.data.message) || 'Erreur inconnue').promise();
    }, function (xhr) {
      var msg = (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || ('Erreur serveur (' + xhr.status + ')');
      return $.Deferred().reject(msg).promise();
    });
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function toast(msg, type) {
    var $t = $('<div class="oa-toast oa-toast--' + (type || 'info') + '">').text(msg).appendTo('body');
    setTimeout(function () { $t.addClass('is-in'); }, 10);
    setTimeout(function () { $t.removeClass('is-in'); setTimeout(function () { $t.remove(); }, 300); }, 4200);
  }

  function debounce(fn, ms) {
    var t;
    return function () {
      var a = arguments, self = this;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(self, a); }, ms);
    };
  }

  function pickMedia(title, multiple, cb) {
    var frame = wp.media({ title: title, multiple: multiple ? 'add' : false, library: { type: 'image' }, button: { text: 'Utiliser' } });
    frame.on('select', function () {
      cb(frame.state().get('selection').toJSON());
    });
    frame.open();
  }

  /* ================================================================== */
  /* Générateur                                                          */
  /* ================================================================== */
  function initGenerator() {
    var state = { trade: null, tradeName: '', cities: [], keywords: [], selected: {}, photos: { _pool: [] }, thumbs: {} };
    var STORE = 'oa_gen_state';

    try {
      var saved = JSON.parse(localStorage.getItem(STORE) || 'null');
      if (saved && saved.cities) { state.cities = saved.cities; state.photos = saved.photos || state.photos; state.thumbs = saved.thumbs || {}; }
    } catch (e) {}

    function persist() {
      try { localStorage.setItem(STORE, JSON.stringify({ cities: state.cities, photos: state.photos, thumbs: state.thumbs })); } catch (e) {}
    }

    function selectedKeywords() {
      return state.keywords.filter(function (k) { return state.selected[k.id]; });
    }

    /* ---- Métier ---- */
    $('input[name="oa_trade"]').on('change', function () {
      var id = this.value;
      state.trade = id;
      state.tradeName = $(this).closest('.oa-trade').find('strong').text();
      $('#oa-kw-list').html('<p class="oa-muted">Chargement…</p>');
      api('trade', { trade: id }).then(function (t) {
        state.keywords = t.keywords;
        state.selected = {};
        t.keywords.slice(0, OA.max).forEach(function (k) { state.selected[k.id] = true; });
        renderKeywords();
        $('#oa-kw-suggest').prop('disabled', false);
      }, function (e) { toast(e, 'error'); });
      api('bank_get', { trade: id }).then(function (r) { state.bankCount = r.photos.length; refresh(); });
      refresh();
    });

    /* ---- Villes ---- */
    var $q = $('#oa-city-q'), $res = $('#oa-city-results');
    $q.on('input keyup', debounce(function () {
      var v = $q.val().trim();
      if (v.length < 2) { $res.prop('hidden', true); return; }
      api('city_search', { q: v }).then(function (list) {
        if (!list.length) { $res.html('<li class="oa-muted">Aucune commune trouvée</li>').prop('hidden', false); return; }
        $res.html(list.map(function (c, i) {
          return '<li data-i="' + i + '"><strong>' + esc(c.name) + '</strong> <span>' + esc(c.postcode) + ' · ' + esc(c.dept_name) + ' · ' + Number(c.population).toLocaleString('fr-FR') + ' hab.</span></li>';
        }).join('')).prop('hidden', false).data('list', list);
      });
    }, 250));
    $res.on('click', 'li[data-i]', function () {
      addCities([$res.data('list')[$(this).data('i')]]);
      $q.val('').focus();
      $res.prop('hidden', true);
    });
    $(document).on('click', function (e) { if (!$(e.target).closest('.oa-search').length) $res.prop('hidden', true); });

    function addCities(list) {
      var added = 0;
      list.forEach(function (c) {
        if (!state.cities.some(function (x) { return x.code === c.code; })) { state.cities.push(c); added++; }
      });
      renderCities();
      return added;
    }

    function renderCities() {
      var $c = $('#oa-cities');
      if (!state.cities.length) { $c.html('<p class="oa-muted">Aucune ville sélectionnée.</p>'); }
      else {
        $c.html(state.cities.map(function (c, i) {
          return '<span class="oa-chip">' + esc(c.name) + ' <small>' + esc(c.postcode) + '</small><button type="button" data-rm="' + i + '" aria-label="Retirer">×</button></span>';
        }).join('') + (state.cities.length > 1 ? '<button type="button" class="button-link oa-clear" id="oa-cities-clear">Tout retirer</button>' : ''));
      }
      var $sel = $('#oa-near-center').empty();
      if (!state.cities.length) $sel.append('<option value="">— ajoutez d\'abord une ville —</option>');
      state.cities.forEach(function (c) { $sel.append($('<option>').val(c.code).text(c.name)); });
      persist();
      refresh();
    }
    $('#oa-cities').on('click', '[data-rm]', function () { state.cities.splice($(this).data('rm'), 1); renderCities(); });
    $('#oa-cities').on('click', '#oa-cities-clear', function () { state.cities = []; renderCities(); });

    $('#oa-near-go').on('click', function () {
      var code = $('#oa-near-center').val();
      if (!code) return toast('Ajoutez d\'abord une ville centre.', 'error');
      var $l = $('#oa-near-list').html('<p class="oa-muted">Recherche…</p>');
      api('city_nearby', { code: code, radius: $('#oa-near-radius').val(), min_pop: $('#oa-near-pop').val(), depts: $('#oa-near-depts').val() }).then(function (list) {
        if (!list.length) { $l.html('<p class="oa-muted">Aucune commune dans ce rayon avec ces critères.</p>'); return; }
        $l.html('<div class="oa-near__head"><label class="oa-check"><input type="checkbox" id="oa-near-all" checked> Tout cocher (' + list.length + ')</label><button type="button" class="button button-primary" id="oa-near-add">Ajouter la sélection</button></div><div class="oa-near__grid">' +
          list.map(function (c, i) {
            return '<label><input type="checkbox" data-i="' + i + '" checked> ' + esc(c.name) + ' <small>' + c.distance + ' km · ' + Number(c.population).toLocaleString('fr-FR') + ' hab.</small></label>';
          }).join('') + '</div>').data('list', list);
      }, function (e) { $l.html('<p class="oa-error">' + esc(e) + '</p>'); });
    });
    $('#oa-near-list').on('change', '#oa-near-all', function () { $('#oa-near-list .oa-near__grid input').prop('checked', this.checked); });
    $('#oa-near-list').on('click', '#oa-near-add', function () {
      var list = $('#oa-near-list').data('list');
      var picked = $('#oa-near-list .oa-near__grid input:checked').map(function () { return list[$(this).data('i')]; }).get();
      var n = addCities(picked);
      toast(n + ' ville(s) ajoutée(s)', 'ok');
    });

    $('#oa-bulk-go').on('click', function () {
      var $b = $(this).prop('disabled', true);
      $('#oa-bulk-msg').text('Recherche…');
      api('city_bulk', { lines: $('#oa-bulk').val() }).then(function (r) {
        var n = addCities(r.cities);
        $('#oa-bulk-msg').text(n + ' ajoutée(s)' + (r.missing.length ? ' — introuvables : ' + r.missing.join(', ') : ''));
      }, function (e) { $('#oa-bulk-msg').text(e); }).always(function () { $b.prop('disabled', false); });
    });

    /* ---- Mots-clés ---- */
    function renderKeywords() {
      var $l = $('#oa-kw-list');
      if (!state.keywords.length) { $l.html('<p class="oa-muted">Aucun mot-clé pour ce métier.</p>'); refresh(); return; }
      var example = state.cities[0] ? state.cities[0].name : 'votre ville';
      $l.html(state.keywords.map(function (k) {
        var on = !!state.selected[k.id];
        return '<label class="oa-kw' + (on ? ' is-on' : '') + '"><input type="checkbox" data-kw="' + esc(k.id) + '"' + (on ? ' checked' : '') + '>' +
          '<span class="oa-kw__label"><strong>' + esc(k.label) + '</strong> <em>' + esc(prep(example)) + '</em>' + (k.hub ? ' <span class="oa-tag">Page principale</span>' : '') + '</span>' +
          (k.secondary && k.secondary.length ? '<small>' + esc(k.secondary.join(' · ')) + '</small>' : '') + '</label>';
      }).join(''));
      renderPhotoKeywords();
      refresh();
    }
    function prep(city) {
      if (/^Le\s/.test(city)) return 'au ' + city.slice(3);
      if (/^Les\s/.test(city)) return 'aux ' + city.slice(4);
      return 'à ' + city;
    }
    $('#oa-kw-list').on('change', 'input[data-kw]', function () {
      var id = $(this).data('kw');
      if (this.checked && selectedKeywords().length >= OA.max) {
        this.checked = false;
        return toast('Maximum ' + OA.max + ' pages par ville : décochez un mot-clé d\'abord.', 'error');
      }
      state.selected[id] = this.checked;
      $(this).closest('.oa-kw').toggleClass('is-on', this.checked);
      renderPhotoKeywords();
      refresh();
    });
    $('#oa-kw-add').on('click', function () {
      var label = $('#oa-kw-new').val().trim();
      if (!label) return;
      if (!state.trade) return toast('Choisissez d\'abord un métier.', 'error');
      var id = label.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
      if (state.keywords.some(function (k) { return k.id === id; })) return toast('Ce mot-clé existe déjà.', 'error');
      state.keywords.push({ id: id, label: label, secondary: $('#oa-kw-new-sec').val().split(',').map(function (s) { return s.trim(); }).filter(Boolean), angle: '', hub: false });
      state.selected[id] = selectedKeywords().length < OA.max;
      $('#oa-kw-new, #oa-kw-new-sec').val('');
      renderKeywords();
    });
    $('#oa-kw-new').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('#oa-kw-add').click(); } });
    $('#oa-kw-suggest').on('click', function () {
      var $b = $(this).prop('disabled', true).text('Analyse en cours…');
      api('suggest_keywords', { trade: state.trade, city: state.cities[0] ? state.cities[0].name : '' }).then(function (list) {
        var n = 0;
        list.forEach(function (k) {
          if (!state.keywords.some(function (x) { return x.id === k.id; })) { k.hub = false; state.keywords.push(k); n++; }
        });
        renderKeywords();
        toast(n + ' suggestion(s) ajoutée(s) — cochez celles qui vous intéressent.', 'ok');
      }, function (e) { toast(e, 'error'); }).always(function () { $b.prop('disabled', false).text('✨ Suggérer avec l\'IA'); });
    });

    /* ---- Photos ---- */
    function renderPhotoKeywords() {
      var kws = selectedKeywords();
      $('#oa-photo-kw').html(kws.map(function (k) {
        return '<div class="oa-photo-pool"><div class="oa-photo-pool__head"><strong>' + esc(k.label) + '</strong> <button type="button" class="button" data-oa-photos="' + esc(k.id) + '">Choisir</button></div><div class="oa-thumbs" data-oa-thumbs="' + esc(k.id) + '"></div></div>';
      }).join(''));
      Object.keys(state.photos).forEach(renderThumbs);
    }
    function renderThumbs(key) {
      var ids = state.photos[key] || [];
      $('[data-oa-thumbs="' + key + '"]').html(ids.map(function (id) {
        return '<span class="oa-thumb"><img src="' + esc(state.thumbs[id] || '') + '" alt=""><button type="button" data-rm-photo="' + id + '" data-key="' + esc(key) + '">×</button></span>';
      }).join('') || '<span class="oa-muted">Aucune photo</span>');
      refresh();
    }
    $(document).on('click', '[data-oa-photos]', function () {
      var key = $(this).data('oa-photos');
      pickMedia('Photos — ' + (key === '_pool' ? 'banque commune' : key), true, function (sel) {
        state.photos[key] = state.photos[key] || [];
        sel.forEach(function (a) {
          if (state.photos[key].indexOf(a.id) === -1) state.photos[key].push(a.id);
          state.thumbs[a.id] = (a.sizes && (a.sizes.thumbnail || a.sizes.medium || a.sizes.full).url) || a.url;
        });
        renderThumbs(key);
        persist();
      });
    });
    $(document).on('click', '[data-rm-photo]', function () {
      var key = $(this).data('key'), id = $(this).data('rm-photo');
      state.photos[key] = (state.photos[key] || []).filter(function (x) { return x !== id; });
      renderThumbs(key);
      persist();
    });

    /* ---- Récapitulatif ---- */
    function photoCount() {
      var all = {};
      Object.keys(state.photos).forEach(function (k) { (state.photos[k] || []).forEach(function (id) { all[id] = 1; }); });
      return Object.keys(all).length;
    }
    function refresh() {
      var kw = selectedKeywords().length, nc = state.cities.length, total = kw * nc;
      $('#oa-kw-count').text(kw + ' / ' + OA.max).toggleClass('is-full', kw >= OA.max);
      $('#oa-sum-trade').text(state.tradeName || '—');
      $('#oa-sum-cities').text(nc);
      $('#oa-sum-kw').text(kw);
      $('#oa-sum-photos').text(photoCount());
      $('#oa-sum-total').text(total);
      $('#oa-launch').prop('disabled', !total);
      if (total) {
        var minutes = Math.ceil(total * 0.9 / Math.max(1, OA.workers));
        $('#oa-estimate').text(OA.manual
          ? 'Mode manuel : ' + total + ' prompts à coller dans ChatGPT.'
          : 'Durée estimée : ~' + (minutes < 60 ? minutes + ' min' : Math.round(minutes / 6) / 10 + ' h') + ' avec ' + OA.workers + ' générations en parallèle.');
      } else {
        $('#oa-estimate').text('');
      }
      if (!photoCount() && total) {
        $('#oa-estimate').append(state.bankCount
          ? '<br>Aucune photo choisie : la banque du métier (' + state.bankCount + ' photos) sera utilisée.'
          : '<br><b>Aucune photo</b> : ajoutez-en à l\'étape 4, sinon les pages seront sans image.');
      }
    }

    $('#oa-launch').on('click', function () {
      var kws = selectedKeywords();
      if (!state.trade || !kws.length || !state.cities.length) return;
      var total = kws.length * state.cities.length;
      if (total > 200 && !window.confirm('Vous allez créer ' + total + ' pages. Continuer ?')) return;
      var $b = $(this).prop('disabled', true).text('Création des tâches…');
      api('create_batch', {
        trade: state.trade,
        cities: JSON.stringify(state.cities),
        keywords: JSON.stringify(kws),
        photos: JSON.stringify(state.photos),
        status: $('#oa-status').val(),
        skip_existing: $('#oa-skip').is(':checked') ? '1' : '0'
      }).then(function (r) {
        if (!r.created) {
          toast('Rien à générer : ' + r.skipped + ' page(s) existent déjà.', 'error');
          $b.prop('disabled', false).text('Lancer la génération');
          return;
        }
        try { localStorage.removeItem(STORE); } catch (e) {}
        window.location = OA.queueUrl + '&autostart=1';
      }, function (e) {
        toast(e, 'error');
        $b.prop('disabled', false).text('Lancer la génération');
      });
    });

    renderCities();
    Object.keys(state.photos).forEach(renderThumbs);
  }

  /* ================================================================== */
  /* File d'attente                                                      */
  /* ================================================================== */
  function initQueue() {
    var running = false, active = 0, filter = '', stopRequested = false, manualId = 0;
    var LABELS = { pending: 'En attente', running: 'En cours', done: 'Terminée', failed: 'Échec', manual: 'À coller', cancelled: 'Annulée' };

    function renderStats(s, fatal) {
      var finished = s.done + s.failed + s.cancelled;
      var pct = s.total ? Math.round(finished / s.total * 100) : 0;
      $('#oa-q-bar').css('width', pct + '%');
      $('#oa-q-stats').html(
        '<span><b>' + s.done + '</b> terminées</span>' +
        '<span><b>' + (s.pending + s.running) + '</b> restantes</span>' +
        (s.manual ? '<span><b>' + s.manual + '</b> à coller</span>' : '') +
        (s.failed ? '<span class="oa-error"><b>' + s.failed + '</b> échecs</span>' : '') +
        (s.avg_score ? '<span>Score moyen <b>' + s.avg_score + '</b>/100</span>' : '') +
        (s.tokens ? '<span>' + Number(s.tokens).toLocaleString('fr-FR') + ' tokens</span>' : '') +
        '<span class="oa-muted">' + pct + ' %</span>'
      );
      if (fatal && running) { stop(); toast('Génération interrompue : ' + fatal.message, 'error'); }
    }

    function load() {
      return api('queue', { status: filter }).then(function (r) {
        renderStats(r.stats, r.fatal);
        if (!r.jobs.length) { $('#oa-q-rows').html('<tr><td colspan="6" class="oa-muted">Aucune tâche.</td></tr>'); return; }
        $('#oa-q-rows').html(r.jobs.map(function (j) {
          var score = +j.seo_score ? '<span class="oa-score oa-score--' + (j.seo_score >= 80 ? 'good' : j.seo_score >= 60 ? 'ok' : 'bad') + '">' + j.seo_score + '</span>' : '';
          var acts = '';
          if (j.view) acts += '<a class="button button-small" target="_blank" href="' + esc(j.view) + '">Voir</a> ';
          if (j.status === 'manual') acts += '<button class="button button-small button-primary" data-manual="' + j.id + '">Ouvrir</button> ';
          if (j.status === 'failed' || j.status === 'cancelled') acts += '<button class="button button-small" data-act="retry" data-id="' + j.id + '">Relancer</button> ';
          if (j.status === 'pending' || j.status === 'manual') acts += '<button class="button-link" data-act="cancel" data-id="' + j.id + '">Annuler</button>';
          return '<tr><td><strong>' + esc(j.city_name) + '</strong></td><td>' + esc(j.kw) + '</td>' +
            '<td><span class="oa-jstatus oa-jstatus--' + j.status + '">' + (LABELS[j.status] || j.status) + '</span>' + (j.publish_at ? '<br><small class="oa-muted">publiée le ' + esc(j.publish_at.slice(0, 16)) + '</small>' : '') + '</td>' +
            '<td>' + score + '</td><td class="oa-small">' + esc(j.message || '') + '</td><td class="oa-right oa-nowrap">' + acts + '</td></tr>';
        }).join(''));
      });
    }

    function worker() {
      if (stopRequested) { active--; if (!active) finish(); return; }
      api('process').then(function (r) {
        renderStats(r.stats, r.fatal);
        if (!r.job) { active--; if (!active) finish(); return; }
        if (r.job.status === 'done') load();
        if (r.fatal) { active--; if (!active) finish(); return; }
        worker();
      }, function (e) {
        toast(e, 'error');
        setTimeout(worker, 4000);
      });
    }

    function start() {
      if (running) return;
      running = true; stopRequested = false;
      $('#oa-q-start').prop('hidden', true);
      $('#oa-q-stop').prop('hidden', false);
      $(window).on('beforeunload.oa', function () { return 'La génération est en cours.'; });
      for (var i = 0; i < Math.max(1, OA.workers); i++) { active++; setTimeout(worker, i * 700); }
    }
    function stop() {
      stopRequested = true;
      $('#oa-q-stop').prop('disabled', true).text('Arrêt après les tâches en cours…');
    }
    function finish() {
      running = false;
      $(window).off('beforeunload.oa');
      $('#oa-q-start').prop('hidden', false);
      $('#oa-q-stop').prop('hidden', true).prop('disabled', false).text('⏸ Pause');
      load();
      if (!stopRequested) toast('File d\'attente terminée ✔', 'ok');
    }

    $('#oa-q-start').on('click', start);
    $('#oa-q-stop').on('click', stop);
    $('#oa-q-prepare').on('click', function () {
      var $b = $(this).prop('disabled', true).text('Préparation…');
      (function loop() {
        api('process').then(function (r) {
          if (r.job) return loop();
          $b.prop('disabled', false).text('Préparer les prompts');
          filter = 'manual';
          $('#oa-q-filter button').removeClass('is-active').filter('[data-status="manual"]').addClass('is-active');
          load();
        }, function (e) { toast(e, 'error'); $b.prop('disabled', false).text('Préparer les prompts'); });
      })();
    });

    $('#oa-q-filter').on('click', 'button', function () {
      $(this).addClass('is-active').siblings().removeClass('is-active');
      filter = $(this).data('status');
      load();
    });
    $('#oa-q-rows').on('click', '[data-act]', function () {
      api('job_action', { act: $(this).data('act'), id: $(this).data('id') }).then(load);
    });
    $('[data-bulk]').on('click', function () {
      var act = $(this).data('bulk');
      if (act === 'cancel_pending' && !window.confirm('Annuler toutes les tâches en attente ?')) return;
      api('job_action', { act: act }).then(function () { load(); toast('C\'est fait.', 'ok'); });
    });

    /* Mode manuel */
    var $m = $('#oa-manual');
    var manualWaiting = false, manualBusy = false, lastClip = '';
    function manualMsg(html) { $('#oa-manual-msg').html(html); }
    function copyText(text) {
      var ta = $('#oa-manual-prompt')[0];
      try { ta.value = text; ta.select(); document.execCommand('copy'); } catch (e) {}
      if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).catch(function () {});
    }
    function looksLikePage(t) { return t && t.indexOf('{') !== -1 && /"(sections|faq|meta_title|h1)"/.test(t); }
    function openManual(id) {
      manualId = id; manualWaiting = false;
      $('#oa-manual-prompt').val('Chargement…');
      $('#oa-manual-json').val('');
      $('#oa-manual-send').prop('disabled', true);
      manualMsg('Préparation du prompt…');
      $m.prop('hidden', false);
      var left = $('#oa-q-rows [data-manual]').length;
      $('#oa-manual-left').text(left ? '— ' + left + ' page(s) à traiter' : '');
      api('manual_prompt', { id: id }).then(function (r) {
        $('#oa-manual-prompt').val(r.prompt);
        $('#oa-manual-send').prop('disabled', false).focus();
        manualMsg('Prêt : cliquez sur « Envoyer à ChatGPT ».');
      }, function (e) { $('#oa-manual-prompt').val(e); manualMsg('<span class="oa-error">' + esc(e) + '</span>'); });
    }
    function importManual(json) {
      if (manualBusy) return;
      manualBusy = true; manualWaiting = false;
      $('#oa-manual-json').val(json);
      manualMsg('Réponse détectée — création de la page…');
      api('manual_import', { id: manualId, json: json }).then(function (r) {
        manualMsg('<span class="oa-ok">✔ Page créée — score SEO <b>' + r.score + '</b>/100.</span> <a target="_blank" href="' + esc(r.view) + '">Voir</a>');
        load().then(function () {
          var $next = $('#oa-q-rows [data-manual]').filter(function () { return +$(this).data('manual') !== +manualId; }).first();
          if ($('#oa-manual-chain').is(':checked') && $next.length) {
            setTimeout(function () { openManual($next.data('manual')); }, 1200);
          } else {
            setTimeout(function () { $m.prop('hidden', true); }, 2500);
          }
        });
      }, function (e) {
        manualWaiting = true;
        manualMsg('<span class="oa-error">' + esc(e) + '</span> Recopiez la réponse complète dans ChatGPT puis revenez.');
      }).always(function () { manualBusy = false; });
    }
    function readClipboard(fromClick) {
      if (!navigator.clipboard || !navigator.clipboard.readText || !window.isSecureContext) {
        if (fromClick) { $('.oa-manual-raw').prop('open', true); $('#oa-manual-json').focus(); manualMsg('Votre navigateur bloque la lecture du presse-papier : collez la réponse dans le champ ci-dessous.'); }
        return;
      }
      navigator.clipboard.readText().then(function (t) {
        if (looksLikePage(t) && t !== lastClip) { lastClip = t; importManual(t); }
        else if (fromClick && t === lastClip) manualMsg('<span class="oa-error">C\'est la réponse de la page précédente.</span> Copiez la nouvelle réponse dans ChatGPT.');
        else if (fromClick) manualMsg('<span class="oa-error">Le presse-papier ne contient pas la réponse JSON.</span> Cliquez sur « Copier » sous le bloc de code dans ChatGPT, puis réessayez.');
      }, function () {
        if (fromClick) { $('.oa-manual-raw').prop('open', true); $('#oa-manual-json').focus(); manualMsg('Autorisez l\'accès au presse-papier, ou collez la réponse dans le champ ci-dessous.'); }
      });
    }
    $('#oa-q-rows').on('click', '[data-manual]', function () { openManual($(this).data('manual')); });
    $m.on('click', '.oa-modal__close', function () { manualWaiting = false; $m.prop('hidden', true); });
    $m.on('click', function (e) { if (e.target === this) { manualWaiting = false; $m.prop('hidden', true); } });
    $('#oa-manual-send').on('click', function () {
      var prompt = $('#oa-manual-prompt').val();
      copyText(prompt);
      var q = encodeURIComponent(prompt);
      window.open(q.length < 12000 ? 'https://chatgpt.com/?q=' + q : 'https://chatgpt.com/', '_blank', 'noopener');
      manualWaiting = true;
      manualMsg('ChatGPT rédige la page… Quand il a fini, cliquez sur « Copier » sous le bloc de code et revenez sur cet onglet.');
    });
    // Retour sur l'onglet : on lit le presse-papier et on crée la page si la réponse est là.
    $(window).on('focus', function () { if (manualWaiting && !$m.prop('hidden')) setTimeout(function () { readClipboard(false); }, 300); });
    $('#oa-manual-paste').on('click', function () { readClipboard(true); });
    $('#oa-manual-copy').on('click', function () { copyText($('#oa-manual-prompt').val()); toast('Prompt copié', 'ok'); });
    $('#oa-manual-import').on('click', function () { importManual($('#oa-manual-json').val()); });
    $('#oa-manual-json').on('paste', function () { var el = this; setTimeout(function () { if (looksLikePage(el.value)) importManual(el.value); }, 50); });

    load();
    setInterval(function () { if (!document.hidden) load(); }, running ? 8000 : 10000);
    if (/autostart=1/.test(location.search) && !OA.manual) start();
    if (/autostart=1/.test(location.search) && OA.manual) $('#oa-q-prepare').click();
  }

  /* ================================================================== */
  /* Pages                                                               */
  /* ================================================================== */
  function initPages() {
    /* Banque de photos */
    var bank = [];
    function trade() { return $('#oa-bank-trade').val(); }
    function renderBank() {
      $('#oa-bank-thumbs').html(bank.map(function (p) {
        return '<span class="oa-thumb"><img src="' + esc(p.url) + '" alt=""><button type="button" data-bank-rm="' + p.id + '">×</button></span>';
      }).join('') || '<span class="oa-muted">Aucune photo dans la banque de ce métier.</span>');
    }
    function saveBank() {
      return api('bank_save', { trade: trade(), ids: JSON.stringify(bank.map(function (p) { return p.id; })) }).then(function (r) { bank = r.photos; renderBank(); });
    }
    function loadBank() { api('bank_get', { trade: trade() }).then(function (r) { bank = r.photos; renderBank(); }); }
    $('#oa-bank-trade').on('change', loadBank);
    $('#oa-bank-pick').on('click', function () {
      pickMedia('Banque de photos', true, function (sel) {
        sel.forEach(function (a) {
          if (!bank.some(function (p) { return p.id === a.id; })) bank.push({ id: a.id, url: (a.sizes && (a.sizes.thumbnail || a.sizes.full).url) || a.url });
        });
        saveBank().then(function () { toast('Banque enregistrée (' + bank.length + ' photos).', 'ok'); });
      });
    });
    $('#oa-bank-thumbs').on('click', '[data-bank-rm]', function () {
      var id = $(this).data('bank-rm');
      bank = bank.filter(function (p) { return p.id !== id; });
      saveBank();
    });
    $('#oa-bank-apply-missing, #oa-bank-apply-all').on('click', function () {
      var mode = $(this).data('mode');
      if (!bank.length) return toast('Ajoutez d\'abord des photos à la banque de ce métier.', 'error');
      if (mode === 'all' && !window.confirm('Remplacer les photos de toutes les pages de ce métier par celles de la banque ? Le texte ne change pas.')) return;
      var $btns = $('#oa-bank-apply-missing, #oa-bank-apply-all').prop('disabled', true);
      var $bar = $('#oa-bank-progress').prop('hidden', false).find('i').css('width', 0);
      api('photo_targets', { trade: mode === 'all' ? trade() : '', mode: mode }).then(function (ids) {
        if (!ids.length) { $('#oa-bank-msg').text('Aucune page à traiter.'); $btns.prop('disabled', false); return; }
        var total = ids.length, done = 0, errors = 0;
        (function next() {
          var chunk = ids.splice(0, 5);
          if (!chunk.length) {
            $('#oa-bank-msg').text(done + ' page(s) mises à jour' + (errors ? ', ' + errors + ' erreur(s)' : '') + '. Rechargement…');
            setTimeout(function () { location.reload(); }, 1200);
            return;
          }
          api('rerender', { ids: JSON.stringify(chunk) }).then(function (res) {
            res.forEach(function (r) { if (r.error) errors++; else done++; });
            $bar.css('width', Math.round((total - ids.length) / total * 100) + '%');
            $('#oa-bank-msg').text((total - ids.length) + ' / ' + total + '…');
            next();
          }, function (e) { toast(e, 'error'); $btns.prop('disabled', false); });
        })();
      });
    });
    if ($('#oa-bank-trade').length) loadBank();

    $(document).on('click', '[data-oa-toggle]', function () {
      $(this).closest('tr').next('.oa-checks-row').prop('hidden', function (i, h) { return !h; });
    });
    $(document).on('click', '[data-oa-reanalyze]', function () {
      var $tr = $(this).closest('tr'), $b = $(this).prop('disabled', true);
      api('reanalyze', { post_id: $tr.data('post') }).then(function (a) {
        var cls = a.score >= 80 ? 'good' : a.score >= 60 ? 'ok' : 'bad';
        $tr.find('.oa-score').attr('class', 'oa-score oa-score--' + cls).text(a.score);
        toast('Nouveau score : ' + a.score + '/100', 'ok');
      }, function (e) { toast(e, 'error'); }).always(function () { $b.prop('disabled', false); });
    });
    $(document).on('click', '[data-oa-regen]', function () {
      if (!window.confirm('Régénérer cette page ? Le contenu actuel sera remplacé (l\'URL ne change pas).')) return;
      var $b = $(this).prop('disabled', true);
      api('regenerate', { post_id: $(this).closest('tr').data('post') }).then(function () {
        toast('Ajoutée à la file d\'attente.', 'ok');
        $b.text('En file…');
      }, function (e) { toast(e, 'error'); $b.prop('disabled', false); });
    });
  }

  /* ================================================================== */
  /* Réglages                                                            */
  /* ================================================================== */
  function initSettings() {
    function showTab(id) {
      id = (id || 'entreprise').replace('#', '');
      if (!$('#tab-' + id).length) id = 'entreprise';
      $('.oa-tab').prop('hidden', true);
      $('#tab-' + id).prop('hidden', false);
      $('.oa-tabs a').removeClass('is-active').filter('[href="#' + id + '"]').addClass('is-active');
      $('#oa-tab-input').val(id);
    }
    $('.oa-tabs').on('click', 'a', function (e) { e.preventDefault(); history.replaceState(null, '', this.hash); showTab(this.hash); });
    showTab(location.hash);

    function providerToggle() {
      var p = $('input[name="oa[ai_provider]"]:checked').val();
      $('[data-oa-show]').each(function () { $(this).toggle($(this).data('oa-show').split(',').indexOf(p) !== -1); });
    }
    $('input[name="oa[ai_provider]"]').on('change', providerToggle);
    providerToggle();

    $('#oa-test-ai').on('click', function () {
      var $m = $('#oa-test-msg').text('Test en cours…');
      api('test_ai').then(function (r) { $m.html('<span class="oa-ok">✔ ' + esc(r.message) + '</span>'); }, function (e) { $m.html('<span class="oa-error">✗ ' + esc(e) + '</span>'); });
    });
    $('#oa-connect-btn').on('click', function () {
      var key = $.trim($('#oa-connect-key').val()), $m = $('#oa-connect-msg'), $b = $(this);
      if (!/^sk-/.test(key)) { $m.html('<span class="oa-error">La clé doit commencer par « sk- ».</span>'); return; }
      $b.prop('disabled', true); $m.text('Vérification auprès d\'OpenAI…');
      api('connect_ai', { key: key }).then(function (r) {
        $m.html('<span class="oa-ok">✔ ' + esc(r.message) + '</span>');
        $('#oa-connect-status').removeClass('is-off').addClass('is-ok').text('● Connecté');
        $('#oa-connect-key').val('');
        $('input[name="oa[ai_key]"]').val(r.masked);
        $('#oa-model').val(r.model);
        $('input[name="oa[ai_provider]"][value="openai"]').prop('checked', true);
        providerToggle();
        toast('Compte OpenAI connecté et activé.', 'ok');
      }, function (e) { $m.html('<span class="oa-error">✗ ' + esc(e) + '</span>'); })
        .always(function () { $b.prop('disabled', false); });
    });
    $('#oa-cg-models-btn').on('click', function () {
      api('models').then(function (list) {
        $('#oa-cg-models').html(list.map(function (m) { return '<option value="' + esc(m) + '">'; }).join(''));
        toast(list.length + ' modèles disponibles dans votre forfait.', 'ok');
        $('#oa-cg-model').focus();
      }, function (e) { toast(e, 'error'); });
    });
    $('#oa-cg-test').on('click', function () {
      var $m = $('#oa-cg-msg').text('Test en cours avec votre forfait…');
      api('test_ai').then(function (r) { $m.html('<span class="oa-ok">✔ ' + esc(r.message) + '</span>'); }, function (e) { $m.html('<span class="oa-error">✗ ' + esc(e) + '</span>'); });
    });
    $('#oa-models-btn').on('click', function () {
      api('models').then(function (list) {
        $('#oa-models').html(list.map(function (m) { return '<option value="' + esc(m) + '">'; }).join(''));
        toast(list.length + ' modèles disponibles — tapez dans le champ pour choisir.', 'ok');
        $('#oa-model').focus();
      }, function (e) { toast(e, 'error'); });
    });
    $('#oa-logo-btn').on('click', function () {
      pickMedia('Logo', false, function (sel) {
        $('#oa-logo-id').val(sel[0].id);
        $('#oa-logo-img').attr('src', sel[0].url).prop('hidden', false);
      });
    });
  }

  $(function () {
    if (OA.page === 'oa-generate') initGenerator();
    if (OA.page === 'oa-queue') initQueue();
    if (OA.page === 'oa-pages') initPages();
    if (OA.page === 'oa-settings') initSettings();
  });
})(jQuery);
