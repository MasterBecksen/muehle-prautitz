<?php
declare(strict_types=1);

// Einstiegspunkt des Redaktionssystems. Der eigentliche Code liegt außerhalb des Webroots.
require dirname(__DIR__, 2) . '/app/bootstrap.php';

\Muehle\Site::$basePath = rtrim((string) config('base_path', ''), '/');
\Muehle\Admin::run();
