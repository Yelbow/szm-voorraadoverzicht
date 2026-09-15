# SZM Voorraadoverzicht

A single-file plugin (plus a bundled updater library) that shows a
voorraad/verkocht overview per product + kleur + maat in wp-admin. Built
from a functions.php snippet so it can be installed and updated the same
way across multiple client sites.

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

The repo root *is* the plugin — `szm-voorraadoverzicht.php` and `inc/`
live at the top level (required for the self-updater below to find the
main file via the GitHub API; it looks at the repo root, not a
subfolder).

1. Download/clone this repo into
   `wp-content/plugins/szm-voorraadoverzicht/` (the folder name matters —
   it must match the plugin slug the updater expects), or zip the repo
   contents into a folder named `szm-voorraadoverzicht` and upload via
   Plugins → Add New → Upload Plugin.
2. Activate. Requires WooCommerce (declared via the `Requires Plugins`
   header on WP 6.5+, and nudged via TGM on older versions).
3. Go to **Voorraadoverzicht** in the admin menu.

## Updates: fully native, no extra plugin required

The plugin bundles [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)
(`inc/plugin-update-checker/`, MIT licensed) and points it at this GitHub
repo. Client sites need **nothing extra installed** — WordPress's own
Plugins/Updates screen shows "Update available" the same way it does for
a WordPress.org plugin.

To ship an update:
1. Make your changes, bump `Version:` in the plugin header.
2. Commit and push to `main`.
3. Each site picks it up on its own update-check schedule (WordPress
   checks roughly twice a day), or force it sooner from that site's
   Dashboard → Updates → "Check Again".
4. Update from wp-admin like any other plugin — pull-based, so a site
   only updates when someone (you, logged in) clicks Update.

The GitHub repo (`Yelbow/szm-voorraadoverzicht`) is public on purpose —
the code has no client-specific data or secrets in it, and public means
the updater needs no access token embedded in the plugin on every client
server (a private repo would require that, which is a token-leak risk
across sites you don't all monitor equally).
