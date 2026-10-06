<?php
/**
 * Standard-Konfiguration. NICHT hier ändern, sondern nach app/config.php kopieren
 * und nur die abweichenden Werte eintragen. app/config.php ist von Git ausgeschlossen.
 */
return [
    // Öffentliche Adresse der Webseite (ohne Schrägstrich am Ende)
    'base_url' => 'https://www.angelteich-muehle-prautitz.com',

    // Pfad-Präfix, falls die Seite in einem Unterordner liegt (z. B. GitHub Pages: "/repo-name")
    'base_path' => '',

    // Einmal-Schlüssel für die Ersteinrichtung des ersten Admin-Kontos (/admin/?r=setup).
    // Nach dem Anlegen des ersten Kontos ist die Einrichtung automatisch gesperrt.
    'setup_key' => '',

    // Geheimer Schlüssel für Signaturen (Kontaktformular, Deploy-Hook). Mind. 32 zufällige Zeichen.
    'app_secret' => '',

    // Token für den Deploy-Hook (leer = deaktiviert). Aufruf: /admin/?r=hook&token=...
    'deploy_token' => '',

    'mail' => [
        // Absender muss eine Adresse der eigenen Domain sein (Strato-Vorgabe)
        'from' => 'webseite@angelteich-muehle-prautitz.com',
        'from_name' => 'Webseite Mühle Prautitz',
    ],

    'security' => [
        'session_idle_minutes' => 30,
        'session_max_hours' => 8,
        'login_max_attempts' => 5,
        'login_lock_minutes' => 15,
        'contact_max_per_hour' => 5,
    ],

    'uploads' => [
        'max_mb' => 15,
        'image_widths' => [480, 960, 1600],
        'image_max_width' => 2400,
        'jpeg_quality' => 82,
        'webp_quality' => 78,
    ],

    // Wie viele Sicherungsstände pro Datei aufbewahrt werden
    'backups_keep' => 30,
];
