# PLAN

> Verificatiestappen (moved from TODO.md, 2026-09-21: testen/checken, geen beslissing die Jelle hoeft te nemen).

- [ ] Fresh activate op een site zonder WooCommerce: verschijnt de
      "Install Required Plugins"-melding (of de WP 6.5+ activatie-blokkade via
      `Requires Plugins`)?
- [ ] Bewerken-toggle: cel met precies 1 variatie -> klikken, waarde wijzigen,
      wegklikken -> AJAX-save, status-symbool en NB-checkbox werken meteen bij
      zonder page reload.
- [ ] CSV-export: BOM/encoding klopt in Excel, kolommen kloppen voor beide
      tabellen (standaard + overig) samen.
- [ ] Self-updater: nieuwe git tag/release op GitHub -> "Update available"
      verschijnt op een site met deze plugin actief.

## Woo-suite uitbreiding (2026-10-02)

Doel: voorraadoverzicht uitbreiden tot `szm-woo-suite` met (1) klik-om-te-kopieren op het orderscherm en (2) extra orderstatussen. Bron: SPEC.md 2026-10-02.

**Harde regels:** bouwen en testen alleen op de lokale kopie (`~/Ventures/MMT/docker-site`, fse-test). MMT live (modernmenthongs.nl) wordt niet aangeraakt, geen push/release zonder expliciete goedkeuring van Jelle. De repo is publiek: geen klantgegevens of screenshots erin.

| # | Type | Eerst bepalen | AI-voorwerk (alleen lokaal) |
|---|---|---|---|
| 1 | Execute | Klik-om-te-kopieren: naam, postcode, huisnummer, e-mail (geen mailto), geen visuele of layoutverandering | Bouwen op lokale MMT-kopie, testen op het bestelscherm (WooCommerce HPOS en klassiek), geen layoutverschil (screenshot voor/na) |
| 2 | Grilling | Welke statussen precies ("ingepakt & klaar voor verzending", "aangemeld bij pakketdienst", ...), mails of acties per status, wat A2WL met statussen doet | Overzicht van WooCommerce custom-status aanpak (register_post_status + wc_order_statuses) en conflict-risico met de A2WL (AliExpress) plugin |
| 3 | Execute | Statussen bouwen na besluit sessie 2 | Lokaal, met test op een testbestelling |

Verificatie rename (2026-10-02, lokaal getest, zie DECISIONS.md): daadwerkelijke GitHub-update is nog niet getest (vereist push). Header/constante-mismatch (1.0.13 vs 1.0.14) opgelost in 1.0.15: beide headers en `SZM_WOO_SUITE_VERSION` = 1.0.15. Stub en hoofdbestand moeten bij elke release dezelfde versie krijgen.

