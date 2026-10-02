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
- 1.0.14 staat lokaal klaar (commit de8c20a), niet gepusht/gereleased. Zeg ja → push, tag `v1.0.14`, `gh release create`.
- XXL-kolom weghalen uit `SZM_MAAT_VOLGORDE`? Nu staat XXL erbij omdat NL-productdata XXL heeft.
- Na update op live: exacte NB-alert-tekst doorsturen (`id=…, raw=…, beheer=…`) — live NB-terugspringen is lokaal niet te reproduceren.
- SPEC.md: voorstel om je woorden toe te voegen: "i want one checkboxs that changes both instead of multiple checkboxes… they have to be synced anyway" en "in de orginele versie werden die samengevoegd en in een keer bewerkt de nederlandse en engelse versie want die hebben namelijk de zelfde voorraa". Zeg ja en ik zet ze erin.

## Wacht op jou (2026-10-02, rename naar szm-woo-suite)
- De rename staat lokaal klaar, ongecommit en ongepusht (backup + undo: `~/Documents/szm-woo-suite-rename-backup-2026-10-02/README.md`). Zeg ja op: eerst release met nieuwe versie (beide `Version:`-headers hoger dan live) terwijl de repo nog `szm-voorraadoverzicht` heet, live laten updaten, daarna pas GitHub-repo hernoemen naar `szm-woo-suite`. Pas dan push ik.
- Orderscherm-features (PLAN.md "Woo-suite uitbreiding"): welke statussen wil je precies? Staan "ingepakt & klaar voor verzending" en "aangemeld bij pakketdienst" vast, of komen er meer?

