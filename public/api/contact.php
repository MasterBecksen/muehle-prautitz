<?php
declare(strict_types=1);

// Kontaktformular-Endpunkt (nur auf dem PHP-Server, z. B. Strato).
require dirname(__DIR__, 2) . '/app/bootstrap.php';

\Muehle\Contact::handle();
