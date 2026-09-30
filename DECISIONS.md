# DECISIONS

- 2026-09-15: Packaged the raw functions.php snippet as its own plugin
  (`szm-voorraadoverzicht.php`) instead of leaving it as a code-snippet
  plugin entry, matching the existing `szm-admin-menu-manager` /
  `szm-hover-animations` / `szm-absolute-positioning` pattern: bundled
  [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)
  pointed at `github.com/Yelbow/szm-voorraadoverzicht` (branch `main`),
  and TGM Plugin Activation to nudge/require WooCommerce. Public repo, no
  auth token needed — see README.md for the reasoning (already validated
  on the sibling plugins).
- 2026-09-15: Added a `Requires Plugins: woocommerce` header (WP 6.5+
  hard activation block) on top of the TGM nudge, since every function in
  this plugin (`wc_get_orders`, `wc_get_product`, ...) hard-depends on
  WooCommerce being active — TGM alone only recommends/nudges, it doesn't
  block activation on older WP versions.
- 2026-09-15: Snippet logic (queries, cell-merging, AJAX handlers, CSS/JS)
  copied verbatim, no refactor — only the plugin scaffolding (header,
  `ABSPATH` guard, version constant, updater init, TGM registration) was
  added around it. Docblock comment at the top is the original spec
  comment, kept intact rather than rewritten into a doc-comment style
  guide, since it's this plugin's only design documentation.
- 2026-09-25: Activatie op live (modernmenthongs.nl) gaf een fatale fout:
  `Cannot redeclare szm_render_voorraadoverzicht()`. De oorspronkelijke
  snippet stond nog actief in Code Snippets. Eerst de snippet uitzetten,
  dan pas de plugin activeren. Er komen bewust geen `function_exists`-guards
  bij: dan draaien snippet en plugin stil naast elkaar.
- 2026-09-30: Updates op live kwamen niet aan ("up to date"): PUC pakt GitHub's
  *latest release* vóór tags, en alleen v1.0.1 had een Release. Elke versie
  krijgt nu ook `gh release create vX.Y.Z --latest`. Volgorde: bump `Version:` +
  `SZM_VOORRAAD_VERSION`, commit, tag, push main+tag, gh release.
- 2026-09-30: NB-checkbox werd op live niet onthouden: variaties met
  voorraadbeheer op parent-niveau erven `backorders` van de parent, dus
  `set_backorders()` op de variatie werd genegeerd. Handler schakelt zo'n variatie
  nu over op eigen voorraadbeheer (met parent-voorraad als startwaarde, dus
  voorraad kan per variatie verdubbeld lijken) en verifieert na opslaan. In 1.0.3.
- 2026-09-30: 1.0.11 = exact 1.0.4-kolommen/-weergave, plus: (a) samengevoegde cellen
  (one size + M) tonen in bewerkmodus per variatie een eigen regel met voorraad + NB
  (verborgen buiten bewerkmodus, dus geen extra rij/streepje); (b) voorraad-handler
  schakelt parent-beheerde variatie ook over op eigen beheer en verifieert na opslaan.
  NB-terugspringen op live lokaal NIET gereproduceerd (mmt, Polylang aan, ook met
  parent-voorraad: werkt). Oorzaak live onbekend, wacht op diag-melding uit live.

## 2026-09-30 — 1.0.11 t/m 1.0.14
- Extra kolommen (`L-en`, `One-size-en`) kwamen van Engelse producten, niet van de samenvoeg-code. Fix: alleen producten in de Polylang-standaardtaal tonen (`szm_alleen_standaardtaal`).
- Samengevoegde cel (one size + M) = 1 waarde + 1 NB-vinkje, wijzigt alle variaties erin (`variation_ids`). Geen sub-regels: gebruiker wil geen extra rijen/streepjes, kolommen exact als 1.0.4.
- Parent-beheerde voorraad: opslaan op variatie wordt genegeerd; handlers schakelen over op eigen beheer en controleren na opslaan (409 met diagnose).
- 1.0.14: verkocht telde EN-bestellingen niet mee (variatie-id -> NL-id via Polylang), `wc_get_orders` gaf ook OrderRefund-objecten terug (`type => shop_order` nodig), terugbetalingen worden afgetrokken, voorraad-edit zet vertalingen expliciet gelijk.
- Live NB-terugspringen niet lokaal te reproduceren; nodig: exacte alert-tekst van live na update.
