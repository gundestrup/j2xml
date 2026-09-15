<?php
/**
 * Small database-driver-neutral query helper for the integration suite.
 *
 * Reads SQL from STDIN, expands Joomla's #__ prefix from configuration.php,
 * and prints scalar/column/row results for the shell test runner.
 */

declare(strict_types=1);

$mode = $argv[1] ?? 'scalar';
$sql = stream_get_contents(STDIN);
require '/var/www/html/configuration.php';

$config = new JConfig();
$driver = strtolower((string) ($config->dbtype ?? 'mysqli'));
$host = (string) $config->host;
$port = null;
if (preg_match('/^([^:]+):(\d+)$/', $host, $matches)) {
    $host = $matches[1];
    $port = $matches[2];
}
$database = (string) $config->db;
$user = (string) $config->user;
$password = (string) $config->password;
$prefix = (string) $config->dbprefix;
$sql = str_replace('#__', $prefix, $sql);

if (str_contains($driver, 'pgsql') || str_contains($driver, 'postgres')) {
    $dsn = "pgsql:host={$host};dbname={$database}" . ($port ? ";port={$port}" : '');
    $pdo = new PDO($dsn, $user, $password);
} else {
    $dsn = "mysql:host={$host};dbname={$database}" . ($port ? ";port={$port}" : '');
    $pdo = new PDO($dsn, $user, $password);
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if ($mode === 'component-api') {
    $table = $prefix . 'extensions';
    $statement = $pdo->prepare("SELECT params FROM {$table} WHERE element='com_j2xml' AND type='component'");
    $statement->execute();
    $params = json_decode((string) $statement->fetchColumn(), true) ?: [];
    $params['api'] = '1';
    $params['debug'] = '0';
    $statement = $pdo->prepare("UPDATE {$table} SET params=? WHERE element='com_j2xml' AND type='component'");
    $statement->execute([json_encode($params, JSON_THROW_ON_ERROR)]);
    echo (string) $statement->rowCount();
    exit;
}

if ($mode === 'token') {
    $userId = (int) ($argv[2] ?? 0);
    $profiles = $prefix . 'user_profiles';
    $statement = $pdo->prepare("DELETE FROM {$profiles} WHERE profile_key LIKE 'joomlatoken%' AND user_id=?");
    $statement->execute([$userId]);
    $seed = random_bytes(32);
    $token = base64_encode('sha256:' . $userId . ':' . hash_hmac('sha256', $seed, (string) $config->secret));
    $statement = $pdo->prepare("INSERT INTO {$profiles} (user_id, profile_key, profile_value, ordering) VALUES (?, 'joomlatoken.token', ?, 1)");
    $statement->execute([$userId, base64_encode($seed)]);
    $statement = $pdo->prepare("INSERT INTO {$profiles} (user_id, profile_key, profile_value, ordering) VALUES (?, 'joomlatoken.enabled', '1', 2)");
    $statement->execute([$userId]);
    echo $token;
    exit;
}

$statement = $pdo->query($sql);

if ($mode === 'exec') {
    echo (string) $statement->rowCount();
    exit;
}

if ($mode === 'column') {
    $values = $statement->fetchAll(PDO::FETCH_COLUMN);
    echo implode("\n", array_map(static fn ($value): string => (string) $value, $values));
    exit;
}

if ($mode === 'json') {
    echo json_encode($statement->fetchAll(PDO::FETCH_ASSOC), JSON_THROW_ON_ERROR);
    exit;
}

echo (string) ($statement->fetchColumn() ?: '');
