# Mühle Prautitz – neue Webseite mit Redaktionssystem

Ersatz für die bisherige Wix-Seite <https://www.angelteich-muehle-prautitz.com>.
Rustikal-traditionelles Design, schlankes PHP-Redaktionssystem (CMS) ohne Datenbank, gebaut für Strato-Webhosting.
Die Analyse der alten Seite steht in [`docs/ANALYSE.md`](docs/ANALYSE.md).

---

## 1. Aufbau

```
app/              Programmcode, Vorlagen, Konfiguration   (nicht öffentlich)
  lib/            CMS-Logik (Anmeldung, Medien, Seiten-Erzeugung …)
  templates/      Vorlagen für Webseite (site/) und CMS (admin/)
  config.sample.php
content/          Inhalte als JSON – das „Gedächtnis“ der Webseite (nicht öffentlich)
storage/          Benutzer, Sitzungen, Sicherungen, Protokolle      (nicht öffentlich)
public/           Webroot: erzeugte HTML-Seiten, CSS/JS, Bilder, /admin, /api
tools/            Kommandozeilen-Werkzeuge (Seiten bauen, Wix-Import, Benutzer anlegen)
.github/workflows Vorschau auf GitHub Pages, Bild-Import, Veröffentlichung zu Strato
```

**So funktioniert es:** Das CMS unter `/admin/` speichert Änderungen als JSON in `content/` und erzeugt danach
sofort alle Seiten als **statische HTML-Dateien** in `public/`. Besucher bekommen also nur fertiges HTML –
kein PHP, keine Datenbank, kein Angriffspunkt. PHP läuft nur im CMS und im Kontaktformular.

Dieselben Vorlagen erzeugen auch die **Vorschau auf GitHub Pages** (dort ohne CMS und mit deaktiviertem Kontaktformular).

### Seiten

| Adresse | Inhalt |
|---|---|
| `/` | Start: Hinweise, Angebote, Öffnungszeiten, Geschichte, Zeitleiste, Video, Kontakt |
| `/muehle/` | Mühle |
| `/forellenzucht/` | Forellenzucht, Bestellungen, Fischverkauf |
| `/angelteich/` | Angelteich, Öffnungszeiten, Teichordnung & Preisliste (PDF) |
| `/25jahre/` | Chronik des Teichbaus 1992/93 |
| `/kontakt/` | Familie, Kontaktdaten, Kontaktformular |
| `/impressum/`, `/datenschutz/` | Pflichtseiten |

Die bisherigen Adressen bleiben gültig (z. B. `/muehle`, `/kontakt`).

---

## 2. Vorschau auf GitHub (Feinabstimmung)

1. Auf github.com ein **neues Repository** anlegen (z. B. `muehle-prautitz`, gern *privat*).
2. Projekt hochladen – am einfachsten mit **GitHub Desktop** („Add existing repository“ → diesen Ordner wählen → „Publish repository“).
   Alternativ im Terminal:
   ```bash
   git remote add origin https://github.com/<konto>/muehle-prautitz.git
   git push -u origin main
   ```
3. Im Repository: **Settings → Pages → Source: „GitHub Actions“**.
4. **Actions → „Bilder von Wix übernehmen“ → Run workflow.** Lädt alle Fotos und die beiden PDFs der alten Seite,
   optimiert sie und speichert sie im Repository. (Nur einmal nötig.)
5. Jeder Push auf `main` baut die Vorschau automatisch neu. Adresse: `https://<konto>.github.io/muehle-prautitz/`
   (bei privaten Repositories nur mit passendem GitHub-Tarif). Die Vorschau ist für Suchmaschinen gesperrt.

> Läuft die Vorschau unter einer eigenen Domain (ohne Unterordner), unter
> *Settings → Secrets and variables → Actions → Variables* die Variable `BASE_PATH` mit dem Wert `/` anlegen.

### Lokal ansehen (optional, PHP 8.1+ nötig)

```bash
php tools/import-wix.php          # Bilder holen (einmalig, Internet nötig)
php tools/build.php               # Seiten nach public/ erzeugen
cp app/config.sample.php app/config.php   # setup_key + app_secret eintragen
php -S localhost:8000 -t public   # → http://localhost:8000  und  http://localhost:8000/admin/
```

---

## 3. Umzug zu Strato

**Voraussetzungen:** Strato-Hosting mit PHP **8.2 oder 8.3** (im Kundenbereich unter *Einstellungen → PHP-Version*)
und SFTP-Zugang. SSL-Zertifikat für die Domain aktivieren.

### Variante A – automatisch per GitHub Actions (empfohlen)

1. Im Repository unter **Settings → Secrets and variables → Actions** folgende Secrets anlegen:

   | Secret | Wert |
   |---|---|
   | `STRATO_HOST` | `ssh.strato.de` |
   | `STRATO_USER` | SFTP-Benutzer laut Strato-Kundenbereich |
   | `STRATO_PASSWORD` | SFTP-Passwort |
   | `STRATO_DIR` | Zielordner, z. B. `/muehle` |
   | `SITE_URL` | `https://www.angelteich-muehle-prautitz.com` |
   | `SETUP_KEY` | zufällige Zeichenkette, mind. 16 Zeichen |
   | `APP_SECRET` | zufällige Zeichenkette, mind. 32 Zeichen |
   | `DEPLOY_TOKEN` | zufällige Zeichenkette, mind. 32 Zeichen |

   Zufällige Werte erzeugen z. B. mit `openssl rand -base64 36` oder einem Passwort-Manager.
2. **Actions → „Auf Strato veröffentlichen“ → Run workflow → Haken bei „Erstinstallation“.**
3. Im Strato-Kundenbereich unter **Domains → Domainverwaltung** das Ziel der Domain auf den Ordner
   **`/muehle/public`** setzen. (Geht das nicht, auf `/muehle` zeigen lassen – die mitgelieferte `.htaccess`
   im Hauptordner leitet dann intern nach `public/` um und sperrt alle anderen Ordner.)
4. `https://www.angelteich-muehle-prautitz.com/admin/` aufrufen → **Ersteinrichtung** mit dem `SETUP_KEY`
   → erstes Administrator-Konto anlegen → anmelden → unter **Mein Konto** die 2-Faktor-Anmeldung aktivieren.
5. Spätere Software-Updates: denselben Workflow **ohne** Haken starten. Inhalte, Bilder und Benutzer auf dem Server
   bleiben dabei unangetastet; die Seiten werden danach automatisch neu erzeugt.

### Variante B – manuell per SFTP-Programm (z. B. FileZilla)

1. Lokal `php tools/import-wix.php` und `php tools/build.php` ausführen.
2. `app/config.sample.php` nach `app/config.php` kopieren und `setup_key`, `app_secret`, `deploy_token` eintragen.
3. Alle Ordner außer `.git`, `.github` und `docs` in einen Ordner auf dem Webspace hochladen.
4. Weiter wie oben ab Schritt 3.

> **Wichtig:** Sobald die Seite bei Strato läuft, ist **der Server die Quelle der Inhalte**. Änderungen an
> `content/` im Repository werden bei normalen Updates nicht mehr übertragen. Den Server-Stand sichern Sie im CMS
> unter *Sicherungen → ZIP herunterladen*.

### Wechsel von Wix

DNS bzw. Domain erst auf Strato umstellen, wenn die neue Seite fertig geprüft ist. Danach das Wix-Abo kündigen.

---

## 4. Redaktionssystem – Kurzanleitung

| Aufgabe | Wo |
|---|---|
| Schließtag, Abfischen, Reservierung ankündigen | **Aktuelles** → „Neuer Hinweis“ → Enddatum setzen (verschwindet dann automatisch) |
| Öffnungszeiten ändern | **Öffnungszeiten** – wirkt auf allen Seiten und in der Fußzeile |
| Text oder Bild einer Seite ändern | **Seiten** → Seite wählen → Baustein bearbeiten → „Speichern & veröffentlichen“ |
| Neue Preisliste | **Bilder & PDFs** → PDF hochladen → Seite *Angelteich* → Baustein „Downloads“ → Datei auswählen |
| Neue Fotos | direkt im Baustein über „Auswählen“ → „Neu hochladen“ |
| Fehler rückgängig machen | **Sicherungen** → früheren Stand wiederherstellen |

Bausteine: Text mit Bild · Öffnungszeiten · Bildergalerie · Kacheln · Hinweisbox · Downloads · Zeitleiste · Video · Kontakt.
Textformatierung: `**fett**`, `*kursiv*`, `[Linktext](https://…)`, Zeilen mit `- ` werden zur Liste.

Rollen: **Redakteur** (Inhalte) und **Administrator** (zusätzlich Einstellungen, Benutzer, Sicherungen, Protokoll).

---

## 5. Sicherheit & Technik

- **Statische Ausgabe**: Besucher erhalten nur HTML/CSS/JS; Code und Daten liegen außerhalb des Webroots.
- **Anmeldung**: Argon2id-Passwort-Hashes, Mindestlänge 12, optionale 2-Faktor-Anmeldung (TOTP, z. B. Google/Microsoft Authenticator)
  mit Schutz gegen Code-Wiederverwendung, Sperre nach 5 Fehlversuchen (steigend), gleiche Antwortzeit für unbekannte Benutzer.
- **Sitzungen**: `__Host-`-Cookie, `HttpOnly`, `Secure`, `SameSite=Strict`, neue Sitzungs-ID nach Anmeldung, Ablauf nach 30 Min. Inaktivität / 8 Std.
- **CSRF-Schutz** auf allen Formularen + Herkunftsprüfung; alle Eingaben werden nach Whitelist bereinigt, Ausgaben maskiert.
- **Uploads**: Typprüfung am Dateiinhalt, Bilder werden neu berechnet (entfernt EXIF/GPS und eingeschleusten Code),
  bereinigte Dateinamen, im Upload-Ordner wird nichts ausgeführt.
- **HTTP-Header**: Content-Security-Policy ohne `unsafe-inline`, HSTS, X-Frame-Options, Referrer- und Permissions-Policy, HTTPS-Zwang.
- **Datenschutz**: keine Cookies für Besucher, kein Tracking, Schriften lokal, Vimeo erst nach Klick, IP-Adressen nur gehasht protokolliert.
- **Kontaktformular**: Honeypot, signiertes Zeit-Token, Drosselung (5/Std.), Schutz vor Header-Injection, Versand über Strato-`mail()`.
- **Sicherungen**: automatische Versionierung jeder Inhaltsdatei, ZIP-Komplettsicherung, Wiederherstellung per Klick.
- **Performance**: responsive WebP-Bilder (480/960/1600 px), Lazy Loading, feste Bildmaße, Caching-Header, ca. 30 KB CSS+JS.
- **Barrierefreiheit**: semantisches HTML, Skip-Link, Tastaturbedienung (inkl. Bildergalerie), Fokus-Markierung, Alt-Texte, reduzierte Bewegung.
- **SEO**: sprechende URLs, Meta-Beschreibungen je Seite, Open Graph, Schema.org `LocalBusiness`, `sitemap.xml`.

Keine Fremdabhängigkeiten zur Laufzeit. Mitgelieferte Dritt-Dateien: Schriften *Vollkorn* und *Alegreya Sans*
(SIL Open Font License) und `qrcode-generator` (MIT) für den 2FA-QR-Code, siehe [`docs/LIZENZEN.md`](docs/LIZENZEN.md).

---

## 6. Vor dem Livegang prüfen

- [ ] **Impressum und Datenschutzerklärung** rechtlich prüfen lassen (Vorlagen sind aktualisiert: § 5 DDG statt MDStV; ggf. USt-IdNr. ergänzen).
- [ ] Texte auf der Startseite (Zeitleiste: 1717, 1948 „Firma GROSSE“) mit dem Original abgleichen.
- [ ] Öffnungszeiten der Mühle – laut alter Seite stehen die Räder seit 01.01.2025 still; Mühlen-Öffnungszeiten ggf. entfernen.
- [ ] Bildbeschreibungen (Alt-Texte) unter *Bilder & PDFs* ergänzen, besonders für die Familienfotos.
- [ ] Kontaktformular auf Strato testen (Absender `webseite@…` muss als Postfach oder Weiterleitung existieren).
- [ ] Teichordnung und Preisliste auf Aktualität prüfen.
