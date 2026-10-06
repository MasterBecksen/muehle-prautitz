# Analyse: angelteich-muehle-prautitz.com (Stand 06.10.2026)

## Ist-Zustand

| Bereich | Befund |
|---|---|
| Technik | Wix Website Builder, Inhalte und Bilder liegen auf Wix-Servern (static.wixstatic.com) |
| Seiten | Startseite, Mühle, Forellenzucht, Angelteich, 25 Jahre Angelteich, Wir über Uns (Kontakt), Impressum (im „More“-Menü versteckt) |
| Downloads | Teichordnung (PDF), Preisliste (PDF) |
| Medien | ca. 35 Fotos (Luftbilder, Teiche, Mühlentechnik, historische Baufotos 1992/93), 1 Vimeo-Video |
| Kontakt | Wix-Kontaktformular, Adresse, Telefon, E-Mail |

## Schwachstellen

1. **Rechtliches**
   - Impressum verweist auf „§ 10 MDStV“ – dieser Staatsvertrag gilt seit 2007 nicht mehr. Heute maßgeblich: § 5 DDG (Digitale-Dienste-Gesetz, seit Mai 2024) und § 18 MStV. Telefonnummer fehlt im Impressum.
   - **Keine Datenschutzerklärung** auffindbar, obwohl ein Kontaktformular, ein Vimeo-Video und Wix-Tracking eingebunden sind.
   - Vimeo und Wix-Ressourcen werden ohne Einwilligung geladen (Datenübertragung an Dritte).
2. **Struktur / Inhalt**
   - Impressum ist nur über „More“ erreichbar; es fehlt ein Footer mit Pflichtlinks.
   - Öffnungszeiten sind über drei Seiten verteilt und teilweise widersprüchlich (Mühle: Mi–Sa, obwohl die Mühle seit 01.01.2025 stillsteht).
   - Aktuelle Hinweise (Reservierungen, Sommerpause, Abfischen) sind im Fließtext versteckt und veralten unbemerkt.
   - Bilder ohne oder mit generischen Alt-Texten; historische Fotos ohne Alt-Text.
3. **Technik / Performance**
   - Hohe Ladezeit durch Wix-JavaScript (mehrere hundert KB), Original-Fotos mit bis zu 5472 px.
   - Keine strukturierten Daten (Schema.org LocalBusiness), kaum Meta-Beschreibungen.
   - Abhängigkeit vom Wix-Abo; Inhalte nicht portabel.

## Ziel-Architektur (neu)

- **Öffentliche Seite**: rein statisches HTML, aus Vorlagen erzeugt → schnell, sicher, auf GitHub Pages und Strato identisch.
- **CMS**: schlankes PHP-Flat-File-CMS unter `/admin` (kein Datenbankserver), speichert JSON, erzeugt nach jedem Speichern die statischen Seiten neu.
- **Sicherheit**: Code und Inhalte außerhalb des Webroots, Passwort-Hashing (Argon2id), optionale 2-Faktor-Anmeldung (TOTP), CSRF-Schutz, Login-Drosselung, sichere Session-Cookies, strenge Security-Header (CSP, HSTS …), Bild-Neuberechnung beim Upload (entfernt Metadaten/Schadcode), kein PHP in Upload-Ordnern, automatische Backups.
- **Datenschutz**: Schriften lokal, Video erst nach Klick (2-Klick-Lösung), kein Tracking, Kontaktformular ohne Drittanbieter.
- **Barrierefreiheit**: semantisches HTML, Skip-Link, Tastaturbedienung, ausreichende Kontraste, Alt-Texte, „reduzierte Bewegung“ wird respektiert.
- **SEO**: sprechende URLs (alte Adressen bleiben gültig), Meta-Daten, Open Graph, JSON-LD, sitemap.xml.
