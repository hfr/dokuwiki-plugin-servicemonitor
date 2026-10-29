<?php

if (!defined('DOKU_INC')) {
    exit;
}

global $conf;

// Konfiguration laden
$config = $conf['plugin']['servicemonitor']['services'] ?? '';
$command = $conf['plugin']['servicemonitor']['command'] ?? '/usr/local/bin/service-status';

$services = [];
$params = '';

foreach (preg_split('/\r?\n/', $config) as $line) {
    $line = trim($line);

    if ($line === '' || strpos($line, '|') === false) {
        continue;
    }

    [$name, $description] = explode('|', $line, 2);

    $name = trim($name);
    $description = trim($description);

    if ($name === '') {
        continue;
    }

    $services[$name] = [
        'name'        => $name,
        'status'      => 'unknown',
    ];
    $params .= ' '.escapeshellarg($name);
}

// Status-Skript ausführen

if (is_executable($command) && !empty($services)) {
    $args = array_map('escapeshellarg', array_keys($services));

    $cmd = escapeshellarg($command) . ' ' .
           implode(' ', $args) .
           ' 2>/dev/null';

    $output = [];
    $exitCode = 0;

    exec($cmd, $output, $exitCode);

    foreach ($output as $line) {
        $parts = explode(':', $line, 2);

        if (count($parts) !== 2) {
            continue;
        }

        $name = trim($parts[0]);
        $status = trim($parts[1]);

        if (isset($services[$name])) {
            $services[$name]['status'] = $status;
        }
    }
}

// JSON-Daten für ajaxloader bereitstellen
$AJAX_JSON = [
    'success'  => true,
    'services' => array_values($services),
];
