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
