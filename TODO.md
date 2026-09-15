# TODO

- Onbevestigd — nog niet getest op een echte WordPress-site (alleen
  `php -l` gedaan):
  - Fresh activate op een site zonder WooCommerce: verschijnt de
    "Install Required Plugins"-melding (of de WP 6.5+ activatie-blokkade
    via `Requires Plugins`)?
  - Bewerken-toggle: cel met precies 1 variatie -> klikken, waarde
    wijzigen, wegklikken -> AJAX-save, status-symbool en NB-checkbox
    werken meteen bij zonder page reload.
  - CSV-export: BOM/encoding klopt in Excel, kolommen kloppen voor beide
    tabellen (standaard + overig) samen.
  - Self-updater: nieuwe git tag/release op GitHub -> "Update
    available" verschijnt op een site met deze plugin actief.
