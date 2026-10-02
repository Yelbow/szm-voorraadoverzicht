# SZM Woo Suite

Formerly **SZM Voorraadoverzicht** (renamed 2026-10-02, see "Old name /
migration" below). WooCommerce tools for client sites, installed and
updated the same way everywhere. Currently one module: a
voorraad/verkocht overview per product + kleur + maat in wp-admin, built
from a functions.php snippet.

## Files

- `szm-woo-suite.php`: the plugin main file (header, load-once guard,
  updater, TGM). Activate this one on new installs.
- `szm-voorraadoverzicht.php`: compat loader with its own plugin header.
  It only `require_once`s `szm-woo-suite.php`. Sites that activated the
  plugin under its old name keep this file active.
- `inc/voorraadoverzicht.php`: the Voorraadoverzicht module (the original
  snippet code, unchanged).
- `inc/plugin-update-checker/`, `inc/tgm-plugin-activation/`: bundled libs.

## What it shows

Two tables under **Voorraadoverzicht** in the admin menu:

1. Standaardkleuren — zwart, wit, navy (and their English spellings
   black/white count too).
2. Overige kleuren — every other color.

Rows are grouped by product ID (not name), so two products that happen to
share a name stay as separate rows. Columns are sizes (fixed order
XXS–XXL, only columns with data show); each cell is `voorraad / verkocht`
for the selected period.

- Period toggle at the top: 3 months, 6 months, 12 months, 2 years, or
  since the beginning. Defaults to 6 months.
- CSV export button, same period as the screen, with separate
  voorraad/verkocht/permanent-uitverkocht/loopt-leeg columns per size.
- 🚫 = permanently sold out (not in stock, no backorders). ⚠️ = still in
  stock but backorders are off, so that stock will run out for good. A
  merged cell (multiple size spellings collapsed into one, e.g. "one
  size" + "M") only shows a symbol when every underlying variation agrees.
- Bewerken-toggle (off by default): once on, cells backed by exactly one
  variation become clickable — edit the stock number or flip the NB
  (backorders) checkbox and it saves immediately via AJAX, no page
  reload. Cells that merge multiple variations stay read-only always,
  since it isn't unambiguous which variation is meant.

## Install on a site

The repo root *is* the plugin: both plugin files and `inc/` live at the
top level (required for the self-updater below to find the main file via
the GitHub API; it looks at the repo root, not a subfolder).

1. Download/clone this repo into `wp-content/plugins/szm-woo-suite/`, or
   zip the repo contents into a folder named `szm-woo-suite` and upload via
   Plugins → Add New → Upload Plugin. The folder name is not load-bearing
   (the updater keeps whatever folder the plugin is installed in, and the
   code uses no hardcoded paths), `szm-woo-suite` is just the convention.
   A GitHub "Download ZIP" extracts to `szm-voorraadoverzicht-main/`
   (repo name + branch): rename it before uploading.
2. Activate **SZM Woo Suite** (`szm-woo-suite.php`). Requires WooCommerce
   (declared via the `Requires Plugins` header on WP 6.5+, and nudged via
   TGM on older versions). The compat loader row is hidden from the
   Plugins list once one of the two files is active.
3. Go to **Voorraadoverzicht** in the admin menu.

## Old name / migration

Until 2026-10-02 the plugin was `szm-voorraadoverzicht/szm-voorraadoverzicht.php`
and that is what existing sites (modernmenthongs.nl) have in their
`active_plugins` option. On those sites nothing has to be done:

- The updater keeps the existing folder name (`szm-voorraadoverzicht/`) on
  update; it never moves the plugin to `szm-woo-suite/`.
- After the update `szm-voorraadoverzicht.php` is the compat loader, so
  the stored active entry still points at an existing file and the plugin
  stays active. Menu slug (`voorraadoverzicht`), AJAX actions, nonces and
  CSV filename are unchanged; the plugin stores no options of its own.
- Do not remove or rename `szm-voorraadoverzicht.php` while any site
  still has it active.
- Never have two copies active (e.g. an old `szm-voorraadoverzicht/` and a
  new `szm-woo-suite/` folder). Between two copies of the new code the
  load-once guard skips the second one and shows an admin notice. A pre-rename
  copy has no guard, so activating the new one next to it fails with
  "Cannot redeclare"; WordPress refuses that activation and the site keeps
  running.

## Updates: fully native, no extra plugin required

The plugin bundles [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)
(`inc/plugin-update-checker/`, MIT licensed) and points it at this GitHub
repo. Client sites need **nothing extra installed** — WordPress's own
Plugins/Updates screen shows "Update available" the same way it does for
a WordPress.org plugin.

To ship an update:
1. Make your changes, bump `Version:` in **both** plugin headers
   (`szm-woo-suite.php` and `szm-voorraadoverzicht.php`) and
   `SZM_WOO_SUITE_VERSION`. Both headers matter: the updater reads the
   installed and the new version from whichever file is active on a site
   (the compat loader on legacy sites, the main file on new installs).
2. Commit, tag `vX.Y.Z`, push `main` + tag, `gh release create vX.Y.Z --latest`
   (see DECISIONS.md 2026-09-30: PUC prefers the latest release over tags).
3. Each site picks it up on its own update-check schedule (WordPress
   checks roughly twice a day), or force it sooner from that site's
   Dashboard → Updates → "Check Again".
4. Update from wp-admin like any other plugin — pull-based, so a site
   only updates when someone (you, logged in) clicks Update.

The update source is the `SZM_WOO_SUITE_REPO_URL` constant in
`szm-woo-suite.php`. The GitHub repo is still named
`Yelbow/szm-voorraadoverzicht`; after renaming it to `szm-woo-suite` only
that one line changes.

The GitHub repo is public on purpose —
the code has no client-specific data or secrets in it, and public means
the updater needs no access token embedded in the plugin on every client
server (a private repo would require that, which is a token-leak risk
across sites you don't all monitor equally).
