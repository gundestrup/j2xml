<?php
/**
 * Shared bootstrap for J2XML CLI test scripts.
 *
 * This bootstraps Joomla CMS enough to use the database and JTable classes
 * without needing a full web application. It uses the ConsoleApplication
 * which is designed for CLI use.
 */

const _JEXEC = 1;
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

// Define constants that the application normally sets
defined('JDEBUG') or define('JDEBUG', false);

define('JPATH_BASE', '/var/www/html');

// Load defines
require JPATH_BASE . '/includes/defines.php';

// Use the modern bootstrap
require JPATH_LIBRARIES . '/bootstrap.php';

// Load the configuration
require JPATH_CONFIGURATION . '/configuration.php';

// Load the backward compatibility classmap
if (file_exists(JPATH_BASE . '/plugins/behaviour/compat/src/classmap/classmap.php')) {
    require JPATH_BASE . '/plugins/behaviour/compat/src/classmap/classmap.php';
} elseif (file_exists(JPATH_BASE . '/plugins/behaviour/compat6/src/classmap/classmap.php')) {
    require JPATH_BASE . '/plugins/behaviour/compat6/src/classmap/classmap.php';
}

// Set up a minimal application via the DI container
$container = Joomla\CMS\Factory::getContainer();

// Register the database service if not already registered
if (!$container->has('Joomla\\Database\\DatabaseInterface')) {
    $config = new JConfig();
    $container->register(new \Joomla\CMS\Service\Provider\Database(), $config);
}

// Get the database directly
$db = $container->get('Joomla\\Database\\DatabaseInterface');

// Register the database in the container (replaces deprecated Factory::$database)
$container->set('Joomla\\Database\\DatabaseInterface', $db);

// Set up a dummy user (admin).
// Factory::$user is deprecated since Joomla 5.0 but is still the only
// mechanism that works in a minimal CLI bootstrap without a full
// application/session. The container-based replacement requires an
// application identity (set via IdentityAware::setIdentity()), which is
// not available here. Test code only; not shipped in the package.
$user = new Joomla\CMS\User\User(['id' => 42, 'name' => 'Admin', 'username' => 'admin']);
Joomla\CMS\Factory::$user = $user;

// Register the J* alias for DatabaseDriver if needed
if (!class_exists('JDatabaseDriver')) {
    class_alias('\\Joomla\\Database\\DatabaseDriver', 'JDatabaseDriver');
}

/**
 * Helper to get the database
 */
function getDb() {
    return Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
}
