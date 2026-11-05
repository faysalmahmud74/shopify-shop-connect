<?php

/*
 * Dev launcher used by `shopify app dev` (see shopify.web.toml).
 * Starts `php artisan serve` and opens the Swagger UI (/docs) in the default browser once the server is up.
 * Set NO_BROWSER=1 to skip opening the browser.
 */

$php = PHP_BINARY;
$port = getenv('SERVER_PORT') ?: getenv('PORT') ?: '8000';
$host = rtrim((string) getenv('HOST'), '/');

// HOST is the tunnel URL injected by the Shopify CLI; fall back to localhost when run on its own.
$docsUrl = ($host !== '' && stripos($host, 'example.com') === false ? $host : "http://127.0.0.1:$port") . '/docs';

$server = proc_open(
    [$php, 'artisan', 'serve', '--port=' . $port],
    [STDIN, STDOUT, STDERR],
    $pipes,
    __DIR__
);

if (!is_resource($server)) {
    fwrite(STDERR, "Could not start the Laravel server\n");
    exit(1);
}

if (!getenv('NO_BROWSER')) {
    // Give the server and the tunnel a few seconds to come up.
    sleep(5);

    $command = match (PHP_OS_FAMILY) {
        'Windows' => 'start "" ' . escapeshellarg($docsUrl),
        'Darwin' => 'open ' . escapeshellarg($docsUrl),
        default => 'xdg-open ' . escapeshellarg($docsUrl) . ' > /dev/null 2>&1 &',
    };
    pclose(popen($command, 'r'));
    fwrite(STDOUT, "Swagger UI: $docsUrl\n");
}

exit(proc_close($server));
