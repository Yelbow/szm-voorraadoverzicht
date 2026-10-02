---
status: active
priority: 8
---

# TODO

Nog niet getest op een echte WordPress-site (alleen `php -l` gedaan) —
verificatiestappen staan in PLAN.md, niet hier: dat zijn dingen die getest
worden, geen beslissingen die jij hoeft te nemen. Niets openstaand dat op
jou wacht.

## Wacht op jou (2026-10-01)
- XXL-kolom weghalen uit `SZM_MAAT_VOLGORDE`? Nu staat XXL erbij omdat NL-productdata XXL heeft.
- Na update op live: exacte NB-alert-tekst doorsturen (`id=…, raw=…, beheer=…`) — live NB-terugspringen is lokaal niet te reproduceren.
- SPEC.md: voorstel om je woorden toe te voegen: "i want one checkboxs that changes both instead of multiple checkboxes… they have to be synced anyway" en "in de orginele versie werden die samengevoegd en in een keer bewerkt de nederlandse en engelse versie want die hebben namelijk de zelfde voorraa". Zeg ja en ik zet ze erin.

## Wacht op jou (2026-10-02, rename naar szm-woo-suite)
- Release v1.0.15 is gedaan (2026-10-02, https://github.com/Yelbow/szm-voorraadoverzicht/releases/tag/v1.0.15): rename naar SZM Woo Suite plus de 1.0.12-1.0.14 wijzigingen. Wacht op jou: update op live (modernmenthongs.nl) via wp-admin > Plugins (eventueel eerst Dashboard > Updates > "Opnieuw controleren") en laat weten dat de pluginrij 1.0.15 toont en Voorraadoverzicht nog werkt. Daarna volgt stap 5: GitHub-repo hernoemen naar `szm-woo-suite` en `SZM_WOO_SUITE_REPO_URL` aanpassen (backup + undo: `~/Documents/szm-woo-suite-rename-backup-2026-10-02/README.md`).
- Orderscherm-features (PLAN.md "Woo-suite uitbreiding"): welke statussen wil je precies? Staan "ingepakt & klaar voor verzending" en "aangemeld bij pakketdienst" vast, of komen er meer?

