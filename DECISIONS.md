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
