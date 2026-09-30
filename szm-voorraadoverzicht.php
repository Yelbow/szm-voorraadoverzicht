<?php
/**
 * Plugin Name:       SZM Voorraadoverzicht
 * Description:       Voorraadoverzicht in wp-admin: rij = product + kleur, kolom = maat, cel = voorraad / verkocht over een gekozen periode. Periode-toggle, CSV-export en een inline bewerken-modus voor eenduidig editbare cellen (precies 1 onderliggende variatie).
 * Version:           1.0.1
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Studio Zonder Meer
 * License:           GPL-2.0-or-later
 * Text Domain:       szm-voorraadoverzicht
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SZM_VOORRAAD_VERSION', '1.0.1' );

/**
 * Self-updates through WordPress's native Plugins/Updates screen — no
 * separate updater plugin needed on client sites. Checks the GitHub repo
 * for new tags and shows the normal "Update available" notice.
 */
require_once __DIR__ . '/inc/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5p4\PucFactory;
add_action( 'init', function () {
	$update_checker = PucFactory::buildUpdateChecker(
		'https://github.com/Yelbow/szm-voorraadoverzicht',
		__FILE__,
		'szm-voorraadoverzicht'
	);
	$update_checker->setBranch( 'main' );
	// If the repo is private, uncomment and set a fine-grained,
	// read-only-on-this-repo GitHub access token:
	// $update_checker->setAuthentication( 'ghp_xxxxxxxxxxxxxxxxxxxx' );
} );

/**
 * WooCommerce is a hard requirement (wc_get_orders(), wc_get_product(), ...
 * throughout this plugin) — the "Requires Plugins" header already blocks
 * activation on a site without it (WP 6.5+), TGM additionally nudges an
 * admin to install/activate it on older WP versions where that header is
 * ignored.
 */
require_once __DIR__ . '/inc/tgm-plugin-activation/class-tgm-plugin-activation.php';
add_action( 'tgmpa_register', 'szm_voorraad_register_required_plugins' );
function szm_voorraad_register_required_plugins() {
	tgmpa(
		array(
			array(
				'name'     => 'WooCommerce',
				'slug'     => 'woocommerce',
				'required' => true,
			),
		),
		array(
			'id'           => 'szm-voorraadoverzicht',
			'menu'         => 'szm-voorraad-install-plugins',
			'has_notices'  => true,
			'is_automatic' => false,
		)
	);
}

/**
 * Voorraadoverzicht - admin pagina
 * Rij = product + kleur, kolom = maat, cel = voorraad / verkocht (gekozen periode)
 * Tabel 1: zwart, wit, navy (ook "black"/"white" tellen mee)
 * Tabel 2: alle overige kleuren
 * Alleen gepubliceerde producten.
 * Periode-toggle bovenaan: 3mnd, 6mnd, 12mnd, 2 jaar, sinds begin. Default 6mnd.
 * CSV-export knop, per maat twee losse kolommen (voorraad / verkocht), zelfde periode als scherm.
 * Bewerken-toggle: standaard uit. Aan gezet zijn cellen met precies 1 onderliggende
 * variatie en een numerieke voorraad klikbaar, editen en wegklikken slaat direct op
 * via AJAX voor die ene specifieke variatie. Cellen die meerdere maat-spellingen
 * samenvoegen (bv "one size" en "M" op hetzelfde product) blijven altijd read-only,
 * omdat niet ondubbelzinnig is welke variatie bedoeld wordt.
 *
 * Producten worden gegroepeerd op product-ID, niet op naam, zodat producten met
 * dezelfde naam (bv twee varianten "Heren tanga string") als losse rijen blijven staan.
 *
 * Laatste kolom "Date Created" toont de aanmaakdatum van het product (niet per variatie),
 * alleen op de eerste rij per product zodat de tabel niet herhaalt.
 *
 * Cellen met een 🚫 symbool zijn permanent uitverkocht: niet op voorraad en geen
 * nabestellingen toegestaan. Cellen met een ⚠️ symbool hebben nog voorraad, maar geen
 * nabestellingen toegestaan: die voorraad loopt dus straks definitief leeg. Werkt zowel
 * bij voorraadbeheer op parent- als op variatieniveau. Bij samengevoegde cellen
 * (meerdere spellingen in 1 cel) verschijnt een symbool alleen als alle onderliggende
 * variaties dezelfde status hebben. In de CSV staan dit twee aparte kolommen per maat
 * (ja/nee).
 *
 * Bewerkbare cellen (precies 1 onderliggende variatie) hebben ook een "NB"-checkbox om
 * nabestellingen aan/uit te zetten. Wijzigen slaat direct op via AJAX en werkt het
 * status-symbool meteen bij, zonder pagina-herlaad. De checkbox ondersteunt alleen
 * aan/uit, niet de WooCommerce-optie "nabestellen met klant informeren".
 */

add_action('admin_menu', function() {
    add_menu_page(
        'Voorraadoverzicht',
        'Voorraadoverzicht',
        'manage_woocommerce',
        'voorraadoverzicht',
        'szm_render_voorraadoverzicht',
        'dashicons-chart-bar',
        56
    );
});

// Exacte kleurnamen (case-insensitief) die in tabel 1 vallen, met normalisatie naar weergavenaam
define('SZM_STANDAARD_KLEUREN', [
    'zwart' => 'Zwart',
    'black' => 'Zwart',
    'wit'   => 'Wit',
    'white' => 'Wit',
    'navy'  => 'Navy',
]);

// Vaste maatvolgorde voor kolommen, alleen kolommen met data worden getoond
define('SZM_MAAT_VOLGORDE', ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL']);

// Tekstkleur per kleurnaam (case-insensitief). Combinatiekleuren (bv "Zwart Wit") vallen terug op de standaardtekstkleur.
define('SZM_KLEUR_CODES', [
    'zwart' => '#000000', 'black' => '#000000',
    'wit' => '#8a8a8a', 'white' => '#8a8a8a',
    'navy' => '#1a2a5e',
    'rood' => '#c62828', 'licht rood' => '#e57373',
    'roze' => '#d81b60',
    'felroze' => '#ff1493',
    'licht blauw' => '#4a90d9',
    'grijs' => '#757575', 'licht grijs' => '#a6a6a6',
    'khaki' => '#8a8a5c',
    'groen' => '#2e7d32',
    'bruin' => '#6d4c33',
    'paars' => '#6a1b9a',
    'teal' => '#00796b',
    'goud' => '#b8860b',
    'zilver' => '#9e9e9e',
    'geel' => '#b39700',
]);

// Periode-opties voor de toggle: key => aantal maanden (null = sinds begin)
define('SZM_PERIODES', [
    '3mnd'       => ['label' => '3 maanden',  'maanden' => 3],
    '6mnd'       => ['label' => '6 maanden',  'maanden' => 6],
    '12mnd'      => ['label' => '12 maanden', 'maanden' => 12],
    '2jaar'      => ['label' => '2 jaar',     'maanden' => 24],
    'sindsbegin' => ['label' => 'Sinds begin', 'maanden' => null],
]);

function szm_render_voorraadoverzicht() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('Geen toegang.');
    }

    $periode_key = isset($_GET['periode']) ? sanitize_key($_GET['periode']) : '6mnd';
    if (!array_key_exists($periode_key, SZM_PERIODES)) {
        $periode_key = '6mnd';
    }
    $maanden = SZM_PERIODES[$periode_key]['maanden'];
    $sinds = $maanden ? strtotime('-' . $maanden . ' months') : null;

    $verkoop_per_variatie = szm_get_verkoop_periode($sinds);
    $rijen = szm_verzamel_productdata($verkoop_per_variatie);

    echo '<div class="wrap"><h1>Voorraadoverzicht</h1>';
    echo '<p>Elke cel toont <strong>voorraad / verkocht (' . esc_html(SZM_PERIODES[$periode_key]['label']) . ')</strong>.</p>';
    ?>
    <style>
        .szm-tabel-wrap { overflow-x: auto; margin-bottom: 2em; border: 1px solid #dcdcde; }
        .szm-tabel { border-collapse: collapse; width: 100%; white-space: nowrap; font-size: 13px; }
        .szm-tabel th, .szm-tabel td { padding: 8px 12px; border-bottom: 1px solid #dcdcde; text-align: center; }
        .szm-tabel th:nth-child(n+3), .szm-tabel td:nth-child(n+3) { padding: 8px 6px; }
        .szm-tabel th:first-child, .szm-tabel td:first-child {
            text-align: left; position: sticky; left: 0; z-index: 1;
            width: 260px; min-width: 260px; max-width: 260px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            border-right: 1px solid #dcdcde;
        }
        .szm-tabel th:nth-child(2), .szm-tabel td:nth-child(2) {
            text-align: left; position: sticky; left: 260px; z-index: 1;
            width: 90px; min-width: 90px; border-right: 1px solid #dcdcde;
        }
        .szm-tabel thead th { background: #f0f0f1; position: sticky; top: 0; z-index: 2; }
        .szm-tabel thead th:first-child, .szm-tabel thead th:nth-child(2) { z-index: 3; }
        .szm-rij-a td { background: #ffffff; }
        .szm-rij-b td { background: #f6f7f7; }
        .szm-leeg { color: #a7aaad; }
        .szm-product { display: flex; align-items: center; gap: 8px; }
        .szm-product img { width: 24px; height: 24px; object-fit: cover; border-radius: 3px; flex-shrink: 0; }
        .szm-product a { text-decoration: none; color: inherit; }
        .szm-product a:hover { text-decoration: underline; }
        .szm-verkocht-plus { color: #2e7d32; font-weight: 500; }
        .szm-datum-cel { color: #50575e; font-size: 12px; white-space: nowrap; }
        .szm-uitverkocht-symbool { color: #b32d2e; margin-left: 4px; }
        .szm-loopt-leeg-symbool { margin-left: 4px; }
        .szm-nabestel-toggle {
            display: block; margin-top: 2px; font-size: 11px; color: #a7aaad;
            cursor: default; user-select: none;
        }
        .szm-nabestel-toggle input { vertical-align: middle; margin-right: 2px; }
        .szm-edit-actief .szm-nabestel-toggle { color: #2c3338; cursor: pointer; }
        .szm-toolbar { margin: 12px 0 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .szm-periode-toggle { display: flex; gap: 6px; }
        .szm-periode-toggle a {
            padding: 6px 14px; border: 1px solid #dcdcde; border-radius: 4px;
            text-decoration: none; font-size: 13px; color: #2c3338; background: #fff;
        }
        .szm-periode-toggle a.actief { background: #2271b1; border-color: #2271b1; color: #fff; }
        .szm-toolbar-rechts { display: flex; gap: 8px; }
        .szm-export-btn {
            padding: 6px 14px; border: 1px solid #2271b1; border-radius: 4px;
            text-decoration: none; font-size: 13px; color: #2271b1; background: #fff;
            cursor: pointer;
        }
        .szm-export-btn:hover { background: #f0f6fc; }
        #szm-edit-toggle.szm-edit-aan { background: #2271b1; color: #fff; }
        .szm-edit-actief .szm-editable-voorraad { cursor: pointer; }
        .szm-edit-actief .szm-editable-voorraad:hover { background: #eef6fc; }
        .szm-voorraad-input { width: 50px; padding: 2px 4px; font-size: 13px; text-align: center; }
        .szm-flash-ok { background: #d7f5d7 !important; transition: background 0.3s; }
        .szm-flash-fout { background: #f7d7d7 !important; transition: background 0.3s; }
    </style>
    <?php

    $export_url = wp_nonce_url(
        admin_url('admin-post.php?action=szm_export_csv&periode=' . $periode_key),
        'szm_export_csv'
    );

    echo '<div class="szm-toolbar">';
    echo '<div class="szm-periode-toggle">';
    foreach (SZM_PERIODES as $key => $data) {
        $klasse = ($key === $periode_key) ? 'actief' : '';
        $url = esc_url(admin_url('admin.php?page=voorraadoverzicht&periode=' . $key));
        echo '<a href="' . $url . '" class="' . esc_attr($klasse) . '">' . esc_html($data['label']) . '</a>';
    }
    echo '</div>';
    echo '<div class="szm-toolbar-rechts">';
    echo '<button type="button" id="szm-edit-toggle" class="szm-export-btn" aria-pressed="false">Bewerken aan</button>';
    echo '<a href="' . esc_url($export_url) . '" class="szm-export-btn">Exporteer CSV</a>';
    echo '</div>';
    echo '</div>';

    echo '<h2>Standaardkleuren</h2>';
    szm_render_tabel($rijen['standaard']);

    echo '<h2>Overige kleuren</h2>';
    szm_render_tabel($rijen['overig']);

    echo '</div>';

    szm_render_edit_script();
}

function szm_render_edit_script() {
    $nonce = wp_create_nonce('szm_voorraad_edit');
    ?>
    <script>
    (function() {
        var editActief = false;
        var toggleBtn = document.getElementById('szm-edit-toggle');
        var wrap = document.querySelector('.wrap');
        var nonce = '<?php echo esc_js($nonce); ?>';

        // Leest een AJAX-antwoord als JSON. Bij een niet-JSON antwoord (verlopen nonce = "-1",
        // WAF/cache-pagina, PHP-fout) een duidelijke fout gooien in plaats van stil te falen.
        function leesJson(res) {
            return res.text().then(function(tekst) {
                try {
                    return JSON.parse(tekst);
                } catch (err) {
                    throw new Error('Server antwoordde met HTTP ' + res.status + ' en geen geldig JSON' + (res.status === 403 ? ' (sessie/nonce verlopen? herlaad de pagina)' : '') + '.');
                }
            });
        }

        function updateStatusSymbool(cel, voorraadWaarde, nabestellingenToegestaan) {
            var symbolSpan = cel.querySelector('.szm-status-symbool');
            if (!symbolSpan) return;
            var voorraadNum = parseInt(voorraadWaarde, 10);
            var opVoorraad = !isNaN(voorraadNum) && voorraadNum > 0;
            if (!opVoorraad && !nabestellingenToegestaan) {
                symbolSpan.innerHTML = '<span class="szm-uitverkocht-symbool" title="Permanent uitverkocht">🚫</span>';
            } else if (opVoorraad && !nabestellingenToegestaan) {
                symbolSpan.innerHTML = '<span class="szm-loopt-leeg-symbool" title="Laatste voorraad, geen nabestellingen">⚠️</span>';
            } else {
                symbolSpan.innerHTML = '';
            }
        }

        toggleBtn.addEventListener('click', function() {
            editActief = !editActief;
            wrap.classList.toggle('szm-edit-actief', editActief);
            toggleBtn.classList.toggle('szm-edit-aan', editActief);
            toggleBtn.textContent = editActief ? 'Bewerken uit' : 'Bewerken aan';
            toggleBtn.setAttribute('aria-pressed', editActief ? 'true' : 'false');

            document.querySelectorAll('.szm-nabestel-checkbox').forEach(function(checkbox) {
                checkbox.disabled = !editActief;
            });
        });

        document.querySelectorAll('.szm-editable-voorraad').forEach(function(cel) {
            cel.addEventListener('click', function() {
                if (!editActief || cel.querySelector('input[type="number"]')) return;

                var waardeSpan = cel.querySelector('.szm-voorraad-waarde');
                var huidigeWaarde = cel.getAttribute('data-voorraad');
                var input = document.createElement('input');
                input.type = 'number';
                input.min = '0';
                input.step = '1';
                input.className = 'szm-voorraad-input';
                input.value = huidigeWaarde;

                waardeSpan.replaceWith(input);
                input.focus();
                input.select();

                function opslaanEnSluiten() {
                    var nieuweWaarde = parseInt(input.value, 10);
                    if (isNaN(nieuweWaarde) || nieuweWaarde < 0) {
                        nieuweWaarde = parseInt(huidigeWaarde, 10);
                    }

                    var span = document.createElement('span');
                    span.className = 'szm-voorraad-waarde';
                    span.textContent = nieuweWaarde;
                    input.replaceWith(span);

                    if (String(nieuweWaarde) === String(huidigeWaarde)) {
                        return;
                    }

                    var variationId = cel.getAttribute('data-variation-id');
                    var data = new URLSearchParams();
                    data.append('action', 'szm_update_voorraad');
                    data.append('nonce', nonce);
                    data.append('variation_id', variationId);
                    data.append('voorraad', nieuweWaarde);

                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: data.toString()
                    })
                    .then(leesJson)
                    .then(function(json) {
                        if (json.success) {
                            cel.setAttribute('data-voorraad', json.data.voorraad);
                            span.textContent = json.data.voorraad;
                            cel.classList.add('szm-flash-ok');
                            var nabestellingenToegestaan = cel.getAttribute('data-nabestellingen') === '1';
                            updateStatusSymbool(cel, json.data.voorraad, nabestellingenToegestaan);
                        } else {
                            span.textContent = huidigeWaarde;
                            cel.classList.add('szm-flash-fout');
                            alert(json.data && json.data.message ? json.data.message : 'Opslaan mislukt.');
                        }
                        setTimeout(function() {
                            cel.classList.remove('szm-flash-ok', 'szm-flash-fout');
                        }, 1200);
                    })
                    .catch(function(err) {
                        span.textContent = huidigeWaarde;
                        cel.classList.add('szm-flash-fout');
                        alert('Opslaan mislukt: ' + err.message);
                        setTimeout(function() {
                            cel.classList.remove('szm-flash-fout');
                        }, 1200);
                    });
                }

                input.addEventListener('blur', opslaanEnSluiten);
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        input.blur();
                    } else if (e.key === 'Escape') {
                        input.value = huidigeWaarde;
                        input.blur();
                    }
                });
            });
        });

        document.querySelectorAll('.szm-nabestel-toggle').forEach(function(label) {
            label.addEventListener('click', function(e) {
                e.stopPropagation();
                if (!editActief) e.preventDefault();
            });
        });

        document.querySelectorAll('.szm-nabestel-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function(e) {
                var cel = checkbox.closest('.szm-editable-voorraad');
                var vorigeWaarde = checkbox.checked ? '0' : '1'; // waarde vóór deze toggle
                var nieuweWaarde = checkbox.checked ? '1' : '0';
                var variationId = cel.getAttribute('data-variation-id');
                var huidigeVoorraad = cel.getAttribute('data-voorraad');

                var data = new URLSearchParams();
                data.append('action', 'szm_update_nabestellingen');
                data.append('nonce', nonce);
                data.append('variation_id', variationId);
                data.append('toegestaan', nieuweWaarde);

                fetch(ajaxurl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: data.toString()
                })
                .then(leesJson)
                .then(function(json) {
                    if (json.success) {
                        var toegestaan = !!json.data.backorders_allowed;
                        cel.setAttribute('data-nabestellingen', toegestaan ? '1' : '0');
                        checkbox.checked = toegestaan;
                        cel.classList.add('szm-flash-ok');
                        updateStatusSymbool(cel, json.data.voorraad, toegestaan);
                    } else {
                        checkbox.checked = (vorigeWaarde === '1');
                        cel.classList.add('szm-flash-fout');
                        alert(json.data && json.data.message ? json.data.message : 'Opslaan mislukt.');
                    }
                    setTimeout(function() {
                        cel.classList.remove('szm-flash-ok', 'szm-flash-fout');
                    }, 1200);
                })
                .catch(function(err) {
                    checkbox.checked = (vorigeWaarde === '1');
                    cel.classList.add('szm-flash-fout');
                    alert('Opslaan mislukt: ' + err.message);
                    setTimeout(function() {
                        cel.classList.remove('szm-flash-fout');
                    }, 1200);
                });
            });
        });
    })();
    </script>
    <?php
}

add_action('admin_post_szm_export_csv', 'szm_export_csv');

function szm_export_csv() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('Geen toegang.');
    }
    check_admin_referer('szm_export_csv');

    $periode_key = isset($_GET['periode']) ? sanitize_key($_GET['periode']) : '6mnd';
    if (!array_key_exists($periode_key, SZM_PERIODES)) {
        $periode_key = '6mnd';
    }
    $maanden = SZM_PERIODES[$periode_key]['maanden'];
    $sinds = $maanden ? strtotime('-' . $maanden . ' months') : null;

    $verkoop_per_variatie = szm_get_verkoop_periode($sinds);
    $rijen = szm_verzamel_productdata($verkoop_per_variatie);

    // Alle maten over beide groepen heen verzamelen, zodat de kolommen overal gelijk zijn
    $aanwezige_maten = [];
    foreach (['standaard', 'overig'] as $groep) {
        foreach ($rijen[$groep] as $product_data) {
            foreach ($product_data['kleuren'] as $maten) {
                foreach (array_keys($maten) as $maat) {
                    $aanwezige_maten[$maat] = true;
                }
            }
        }
    }
    $maat_kolommen = array_values(array_intersect(SZM_MAAT_VOLGORDE, array_keys($aanwezige_maten)));
    $onbekend = array_diff(array_keys($aanwezige_maten), SZM_MAAT_VOLGORDE);
    sort($onbekend);
    $maat_kolommen = array_merge($maat_kolommen, $onbekend);

    $bestandsnaam = 'voorraadoverzicht-' . $periode_key . '-' . date('Y-m-d') . '.csv';

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $bestandsnaam . '"');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // BOM voor correcte weergave van speciale tekens in Excel

    $header = ['Groep', 'Product', 'Kleur'];
    foreach ($maat_kolommen as $maat) {
        $header[] = $maat . ' voorraad';
        $header[] = $maat . ' verkocht';
        $header[] = $maat . ' permanent uitverkocht';
        $header[] = $maat . ' laatste voorraad geen nabestelling';
    }
    $header[] = 'Date Created';
    fputcsv($out, $header, ',');

    $groep_labels = ['standaard' => 'Standaard', 'overig' => 'Overig'];

    foreach (['standaard', 'overig'] as $groep) {
        foreach ($rijen[$groep] as $product_data) {
            $naam = $product_data['_naam'];
            $datum = $product_data['_datum'];
            foreach ($product_data['kleuren'] as $kleur => $maten) {
                $regel = [$groep_labels[$groep], $naam, $kleur];
                foreach ($maat_kolommen as $maat) {
                    if (isset($maten[$maat])) {
                        $regel[] = $maten[$maat]['voorraad'];
                        $regel[] = $maten[$maat]['verkocht'];
                        $regel[] = !empty($maten[$maat]['alle_uitverkocht']) ? 'ja' : 'nee';
                        $regel[] = !empty($maten[$maat]['alle_loopt_leeg']) ? 'ja' : 'nee';
                    } else {
                        $regel[] = '';
                        $regel[] = '';
                        $regel[] = '';
                        $regel[] = '';
                    }
                }
                $regel[] = $datum;
                fputcsv($out, $regel, ',');
            }
        }
    }

    fclose($out);
    exit;
}

function szm_get_verkoop_periode($sinds) {
    $verkoop = [];

    $args = [
        'status' => ['wc-completed', 'wc-processing'],
        'limit'  => -1,
    ];
    if ($sinds) {
        $args['date_created'] = '>=' . $sinds;
    }

    $orders = wc_get_orders($args);

    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            $variation_id = $item->get_variation_id();
            if (!$variation_id) continue;

            if (!isset($verkoop[$variation_id])) {
                $verkoop[$variation_id] = 0;
            }
            $verkoop[$variation_id] += $item->get_quantity();
        }
    }

    return $verkoop;
}

function szm_normaliseer_maat($ruw) {
    // Geen maat, meerdere maten in 1 waarde, of expliciet "one size" -> valt in de M-kolom
    if ($ruw === '-' || strpos($ruw, ',') !== false) {
        return 'M';
    }

    $m = strtolower(trim($ruw));
    $map = [
        'xxs' => 'XXS', 'xs' => 'XS', 's' => 'S', 'm' => 'M', 'l' => 'L',
        'xl' => 'XL', '1xl' => 'XL', // 1XL samengevoegd met XL
        'xxl' => 'XXL', '2xl' => 'XXL',
        'one size' => 'M', 'one-size' => 'M', 'onesize' => 'M',
    ];

    return $map[$m] ?? ucfirst($m);
}

function szm_verzamel_productdata($verkoop_per_variatie) {
    // structuur: ['standaard' => [ product_id => [ '_naam' => .., '_thumb' => url, '_id' => id, '_datum' => 'd-m-Y', 'kleuren' => [ 'Kleur' => [ 'Maat' => ['voorraad'=>.., 'verkocht'=>.., 'variation_ids'=>[..]] ] ] ] ], 'overig' => [...]]
    // Gegroepeerd op product-ID (niet op naam), zodat producten met dezelfde naam los blijven staan.
    $ruw = ['standaard' => [], 'overig' => []];
    $standaard_kleuren = SZM_STANDAARD_KLEUREN;

    $product_ids = wc_get_products([
        'type'   => 'variable',
        'status' => 'publish',
        'limit'  => -1,
        'return' => 'ids',
    ]);

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product) continue;
        $naam = $product->get_name();
        $thumb = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
        $datum_created = $product->get_date_created();
        $datum = $datum_created ? $datum_created->date('d-m-Y') : '-';

        foreach ($product->get_children() as $variatie_id) {
            $variatie = wc_get_product($variatie_id);
            if (!$variatie) continue;

            $attrs = $variatie->get_variation_attributes();
            $kleur_ruw = szm_vind_attribuut($attrs, ['kleur', 'color']);
            $maat_ruw  = szm_vind_attribuut($attrs, ['maat', 'size', 'maten']);
            $maat = szm_normaliseer_maat($maat_ruw);

            $kleur_key = strtolower(trim($kleur_ruw));
            $is_standaard = isset($standaard_kleuren[$kleur_key]);
            $kleur_label = $is_standaard ? $standaard_kleuren[$kleur_key] : trim($kleur_ruw);

            $voorraad = $variatie->get_stock_quantity();
            $voorraad = is_null($voorraad) ? '-' : $voorraad;
            $verkocht = $verkoop_per_variatie[$variatie_id] ?? 0;
            // Permanent uitverkocht: niet op voorraad en nabestellingen niet toegestaan.
            // is_in_stock() en backorders_allowed() lossen dit correct op, ongeacht of
            // voorraadbeheer op parent- of variatieniveau staat.
            $op_voorraad = $variatie->is_in_stock();
            $nabestellingen_toegestaan = $variatie->backorders_allowed();
            $permanent_uitverkocht = !$op_voorraad && !$nabestellingen_toegestaan;
            // Nog op voorraad, maar zonder nabestellingen: loopt straks definitief leeg.
            $loopt_leeg = $op_voorraad && !$nabestellingen_toegestaan;

            $groep = $is_standaard ? 'standaard' : 'overig';

            if (!isset($ruw[$groep][$product_id])) {
                $ruw[$groep][$product_id] = ['_naam' => $naam, '_thumb' => $thumb, '_id' => $product_id, '_datum' => $datum, 'kleuren' => []];
            }
            szm_voeg_cel_toe($ruw[$groep][$product_id]['kleuren'], $kleur_label, $maat, $voorraad, $verkocht, $variatie_id, $permanent_uitverkocht, $loopt_leeg, $nabestellingen_toegestaan);
        }
    }

    return $ruw;
}

// Combineert cellen die na maat-normalisatie op dezelfde combinatie uitkomen (bv "S" en "s", of "one size" met M).
// Elke onderliggende variatie-id wordt bijgehouden zodat een cel met precies 1 variatie bewerkbaar kan zijn,
// en een cel met meerdere samengevoegde variaties bewust read-only blijft (niet ondubbelzinnig welke bedoeld is).
function szm_voeg_cel_toe(&$kleuren, $kleur, $maat, $voorraad, $verkocht, $variatie_id, $permanent_uitverkocht = false, $loopt_leeg = false, $nabestellingen_toegestaan = false) {
    if (!isset($kleuren[$kleur][$maat])) {
        $kleuren[$kleur][$maat] = [
            'voorraad' => $voorraad,
            'verkocht' => $verkocht,
            'variation_ids' => [$variatie_id],
            'alle_uitverkocht' => $permanent_uitverkocht,
            'alle_loopt_leeg' => $loopt_leeg,
            'nabestellingen_toegestaan' => $nabestellingen_toegestaan,
        ];
        return;
    }

    $bestaand = $kleuren[$kleur][$maat];
    $nieuwe_voorraad = $bestaand['voorraad'];
    if (is_numeric($voorraad)) {
        $nieuwe_voorraad = is_numeric($nieuwe_voorraad) ? $nieuwe_voorraad + $voorraad : $voorraad;
    }

    $variation_ids = $bestaand['variation_ids'];
    if (!in_array($variatie_id, $variation_ids, true)) {
        $variation_ids[] = $variatie_id;
    }

    // Een samengevoegde cel is alleen "permanent uitverkocht" of "loopt leeg" als ALLE
    // onderliggende variaties dezelfde status hebben. nabestellingen_toegestaan is bij
    // samengevoegde cellen niet betekenisvol (bewerkbaar is dan sowieso false).
    $alle_uitverkocht = $bestaand['alle_uitverkocht'] && $permanent_uitverkocht;
    $alle_loopt_leeg = $bestaand['alle_loopt_leeg'] && $loopt_leeg;

    $kleuren[$kleur][$maat] = [
        'voorraad' => $nieuwe_voorraad,
        'verkocht'  => $bestaand['verkocht'] + $verkocht,
        'variation_ids' => $variation_ids,
        'alle_uitverkocht' => $alle_uitverkocht,
        'alle_loopt_leeg' => $alle_loopt_leeg,
        'nabestellingen_toegestaan' => $bestaand['nabestellingen_toegestaan'],
    ];
}

function szm_vind_attribuut($attrs, $zoektermen) {
    foreach ($attrs as $key => $waarde) {
        foreach ($zoektermen as $term) {
            if (stripos($key, $term) !== false) {
                return $waarde ?: '-';
            }
        }
    }
    return '-';
}

function szm_kleur_css($kleur_label) {
    $key = strtolower(trim($kleur_label));
    $codes = SZM_KLEUR_CODES;
    return $codes[$key] ?? null;
}

// Kapt productnaam af na 5 woorden met "..."
function szm_verkort_naam($naam) {
    $woorden = preg_split('/\s+/', trim($naam));
    if (count($woorden) <= 5) {
        return $naam;
    }
    return implode(' ', array_slice($woorden, 0, 5)) . '...';
}

function szm_render_tabel($producten) {
    if (empty($producten)) {
        echo '<p>Geen producten.</p>';
        return;
    }

    // Verzamel alle voorkomende maten, in vaste volgorde
    $aanwezige_maten = [];
    foreach ($producten as $product_data) {
        foreach ($product_data['kleuren'] as $maten) {
            foreach (array_keys($maten) as $maat) {
                $aanwezige_maten[$maat] = true;
            }
        }
    }
    $maat_kolommen = array_values(array_intersect(SZM_MAAT_VOLGORDE, array_keys($aanwezige_maten)));
    $onbekend = array_diff(array_keys($aanwezige_maten), SZM_MAAT_VOLGORDE);
    sort($onbekend);
    $maat_kolommen = array_merge($maat_kolommen, $onbekend);

    echo '<div class="szm-tabel-wrap"><table class="szm-tabel"><thead><tr>';
    echo '<th>Product</th><th>Kleur</th>';
    foreach ($maat_kolommen as $maat) {
        echo '<th>' . esc_html($maat) . '</th>';
    }
    echo '<th>Date Created</th>';
    echo '</tr></thead><tbody>';

    $product_index = 0;
    foreach ($producten as $product_data) {
        $rij_klasse = ($product_index % 2 === 0) ? 'szm-rij-a' : 'szm-rij-b';
        $naam = $product_data['_naam'];
        $naam_kort = szm_verkort_naam($naam);
        $thumb = $product_data['_thumb'];
        $datum = $product_data['_datum'];
        $edit_link = get_edit_post_link($product_data['_id']);
        $eerste_rij = true;

        foreach ($product_data['kleuren'] as $kleur => $maten) {
            $kleur_kleur = szm_kleur_css($kleur);
            $kleur_style = $kleur_kleur ? ' style="color:' . esc_attr($kleur_kleur) . ';font-weight:500;"' : '';

            echo '<tr class="' . esc_attr($rij_klasse) . '">';
            if ($eerste_rij) {
                echo '<td title="' . esc_attr($naam) . '"><span class="szm-product">';
                if ($thumb) {
                    echo '<img src="' . esc_url($thumb) . '" alt="" />';
                }
                echo '<a href="' . esc_url($edit_link) . '">' . esc_html($naam_kort) . '</a></span></td>';
            } else {
                echo '<td title="' . esc_attr($naam) . '"></td>';
            }
            echo '<td' . $kleur_style . '>' . esc_html($kleur) . '</td>';

            foreach ($maat_kolommen as $maat) {
                if (isset($maten[$maat])) {
                    $cel = $maten[$maat];
                    $verkocht_klasse = ($cel['verkocht'] > 0) ? ' class="szm-verkocht-plus"' : '';
                    $variation_ids = $cel['variation_ids'] ?? [];
                    $bewerkbaar = (count($variation_ids) === 1 && is_numeric($cel['voorraad']));
                    $status_symbool = '';
                    if (!empty($cel['alle_uitverkocht'])) {
                        $status_symbool = '<span class="szm-uitverkocht-symbool" title="Permanent uitverkocht">🚫</span>';
                    } elseif (!empty($cel['alle_loopt_leeg'])) {
                        $status_symbool = '<span class="szm-loopt-leeg-symbool" title="Laatste voorraad, geen nabestellingen">⚠️</span>';
                    }
                    $status_span = '<span class="szm-status-symbool">' . $status_symbool . '</span>';

                    if ($bewerkbaar) {
                        $vid = $variation_ids[0];
                        $nb_toegestaan = !empty($cel['nabestellingen_toegestaan']) ? '1' : '0';
                        echo '<td class="szm-editable-voorraad" data-variation-id="' . esc_attr($vid) . '" data-voorraad="' . esc_attr($cel['voorraad']) . '" data-nabestellingen="' . esc_attr($nb_toegestaan) . '">';
                        echo '<span class="szm-voorraad-waarde">' . esc_html($cel['voorraad']) . '</span> / <span' . $verkocht_klasse . '>' . esc_html($cel['verkocht']) . '</span> ' . $status_span;
                        echo '<label class="szm-nabestel-toggle" title="Nabestellingen toestaan"><input type="checkbox" class="szm-nabestel-checkbox" disabled' . checked($nb_toegestaan === '1', true, false) . ' /> NB</label>';
                        echo '</td>';
                    } else {
                        echo '<td>' . esc_html($cel['voorraad']) . ' / <span' . $verkocht_klasse . '>' . esc_html($cel['verkocht']) . '</span> ' . $status_span . '</td>';
                    }
                } else {
                    echo '<td class="szm-leeg">-</td>';
                }
            }

            if ($eerste_rij) {
                echo '<td class="szm-datum-cel">' . esc_html($datum) . '</td>';
                $eerste_rij = false;
            } else {
                echo '<td></td>';
            }
            echo '</tr>';
        }
        $product_index++;
    }

    echo '</tbody></table></div>';
}

add_action('wp_ajax_szm_update_voorraad', 'szm_update_voorraad');

function szm_update_voorraad() {
    check_ajax_referer('szm_voorraad_edit', 'nonce');

    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Geen toegang.'], 403);
    }

    $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
    $voorraad = isset($_POST['voorraad']) ? intval($_POST['voorraad']) : null;

    if (!$variation_id || $voorraad === null || $voorraad < 0) {
        wp_send_json_error(['message' => 'Ongeldige waarde.'], 400);
    }

    $variatie = wc_get_product($variation_id);
    if (!$variatie || $variatie->get_type() !== 'variation') {
        wp_send_json_error(['message' => 'Variatie niet gevonden.'], 404);
    }

    if (!$variatie->managing_stock()) {
        wp_send_json_error(['message' => 'Voorraadbeheer staat uit voor deze variant.'], 400);
    }

    $variatie->set_stock_quantity($voorraad);
    $variatie->save();

    wp_send_json_success(['voorraad' => $variatie->get_stock_quantity()]);
}

add_action('wp_ajax_szm_update_nabestellingen', 'szm_update_nabestellingen');

function szm_update_nabestellingen() {
    check_ajax_referer('szm_voorraad_edit', 'nonce');

    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Geen toegang.'], 403);
    }

    $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
    $toegestaan = isset($_POST['toegestaan']) ? sanitize_text_field($_POST['toegestaan']) : '';

    if (!$variation_id || !in_array($toegestaan, ['0', '1'], true)) {
        wp_send_json_error(['message' => 'Ongeldige waarde.'], 400);
    }

    $variatie = wc_get_product($variation_id);
    if (!$variatie || $variatie->get_type() !== 'variation') {
        wp_send_json_error(['message' => 'Variatie niet gevonden.'], 404);
    }

    if (!$variatie->managing_stock()) {
        wp_send_json_error(['message' => 'Voorraadbeheer staat uit voor deze variant.'], 400);
    }

    // "notify" (nabestellen met klant informeren) wordt via deze toggle niet apart
    // ondersteund, en valt hiermee terug op simpel aan/uit.
    $variatie->set_backorders($toegestaan === '1' ? 'yes' : 'no');
    $variatie->save();

    // Opnieuw laden en controleren of de wijziging echt is blijven staan. Zonder deze check
    // meldt de UI "gelukt" terwijl bv. een filter of overerving van de parent de waarde
    // terugzet, en springt de checkbox bij herladen weer terug.
    $variatie = wc_get_product($variation_id);
    if ($variatie->backorders_allowed() !== ($toegestaan === '1')) {
        wp_send_json_error(['message' => 'Nabestellingen konden niet worden opgeslagen (wordt overschreven door parent-product of een andere plugin).'], 409);
    }

    wp_send_json_success([
        'backorders_allowed' => $variatie->backorders_allowed(),
        'voorraad' => $variatie->get_stock_quantity(),
    ]);
}
