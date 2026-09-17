<?php
/**
 * @package     Joomla.Cli
 * @subpackage  J2xml
 *
 * @since       2.5
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2026 Helios Ciancio. All Rights Reserved
 * @copyright   Copyright (C) 2026 Svend Gundestrup. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

/**
 * This is a J2XML script which should be called from the command-line, not the
 * web. For example something like:
 * /usr/bin/php /path/to/site/cli/j2xml.php -f j2xml_file.xml
 */

// Make sure we're being called from the command line, not a web interface
if (array_key_exists('REQUEST_METHOD', $_SERVER)) die();

define('DS', DIRECTORY_SEPARATOR);

// Initialize Joomla framework
const _JEXEC = 1;

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1); // nosemgrep: search-active-debug

// In CLI context, Joomla's web-stack services (router, SiteApplication)
// may be lazily resolved when booting components (e.g. com_users).  Those
// services read $_SERVER['HTTP_HOST'] / ['REQUEST_URI'] to build a base
// URI, and fail with "Could not parse the requested URI" when the values
// are missing or are file paths.  Populate the minimum variables that
// Joomla's AbstractUri needs to parse successfully.
if (empty($_SERVER['HTTP_HOST']))
{
    $_SERVER['HTTP_HOST'] = 'localhost';
}
if (empty($_SERVER['REQUEST_URI']))
{
    $_SERVER['REQUEST_URI'] = '/cli/j2xml.php';
}
if (empty($_SERVER['SCRIPT_NAME']))
{
    $_SERVER['SCRIPT_NAME'] = '/cli/j2xml.php';
}
if (empty($_SERVER['SERVER_NAME']))
{
    $_SERVER['SERVER_NAME'] = 'localhost';
}

// Load system defines
if (file_exists(dirname(dirname(__FILE__)).'/defines.php'))
{
    require_once dirname(dirname(__FILE__)).'/defines.php';
}

if (!defined('_JDEFINES'))
{
    define('JPATH_BASE', dirname(dirname(__FILE__)));
    require_once JPATH_BASE.'/includes/defines.php';
}

// Bootstrap the CMS libraries and configuration (framework.php
// defines JDEBUG and loads configuration.php which bootstrap.php does not).
require_once JPATH_BASE.'/includes/framework.php';

// Boot the DI container and alias the session to the CLI session — the
// same pattern Joomla's own cli/joomla.php uses.  Without this, any call
// to Factory::getApplication() (or the session) raises "Failed to start
// application" under Joomla 5/6.
$container = \Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')
    ->alias('JSession', 'session.cli')
    ->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\SessionInterface::class, 'session.cli');

/**
 * @package  Joomla.CLI
 * @since    2.5
 */
class J2xmlCli extends \Joomla\CMS\Application\CliApplication
{
    private static $codes = array('message'=>'i','notice'=>'!','error'=>'x');

    /**
     * Entry point for the script
     *
     * @return  void
     *
     * @since   2.5.1
     */
    public function doExecute(): void
    {
        // Merge the default translation with the current translation
        $this->loadLanguages();

        $filename = $this->getInput()->get('f',null,'');

        if (!$filename)
        {
            echo "Usage /usr/bin/php /path/to/site/cli/j2xml.php -f j2xml_file.xml";
            exit(1);
        }

        if (!file_exists($filename))
        {
            echo "File {$filename} not found"; // NOSONAR — CLI-only script (guarded by REQUEST_METHOD check at line 25), no browser output
            exit(1);
        }

        \Joomla\CMS\Log\Log::addLogger(array('text_file' => 'j2xml.php', 'extension' => 'com_j2xml'), \Joomla\CMS\Log\Log::ALL, array('lib_j2xml','cli_j2xml'));
        \Joomla\CMS\Log\Log::addLogger(array('logger' => 'echo', 'extension' => 'com_j2xml'), \Joomla\CMS\Log\Log::ALL & ~\Joomla\CMS\Log\Log::DEBUG, array('lib_j2xml','cli_j2xml'));

        $data = $this->loadFile($filename);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NONET);
        if (!$xml)
        {
            $this->reportXmlErrors();
            exit(0);
        }

        \Joomla\CMS\Plugin\PluginHelper::importPlugin('j2xml');
        $this->getDispatcher()->dispatch('onBeforeImport',
            new \Joomla\Event\Event('onBeforeImport', array('cli_j2xml.import', &$xml)));
        if (!$xml || (strtoupper($xml->getName()) != 'J2XML') || !isset($xml['version']))
        {
            $this->out(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
            return;
        }

        $xmlVersion = $xml['version'];
        $xmlVersionNumber = self::toVersionNumber((string) $xmlVersion);

        $j2xmlVersion = class_exists('eshiol\J2xmlpro\Version') ? eshiol\J2xmlpro\Version::$DOCVERSION : eshiol\J2xml\Version::$DOCVERSION;
        $j2xmlVersionNumber = self::toVersionNumber($j2xmlVersion);

        if (($xmlVersionNumber == $j2xmlVersionNumber) || ($xmlVersionNumber == "150900") || ($xmlVersionNumber == "120500"))
        {
            set_time_limit(120);
            // set_time_limit(120);
            $params = \Joomla\CMS\Component\ComponentHelper::getParams('com_j2xml');

            $iparams = $this->buildImportParams($params, $xml);

            $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $importer = class_exists('eshiol\J2xmlpro\Importer') ? new eshiol\J2xmlpro\Importer($db, $this) : new eshiol\J2xml\Importer($db, $this);
            $importer->import($xml, $iparams);
        }
        else
        {
            $this->out(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED', $xmlVersion),'error');
        }
    }

    /**
     * Load the J2XML translations, falling back to the default language.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function loadLanguages(): void
    {
        $lang = $this->getLanguage();
        $lang->load('com_j2xml', JPATH_ADMINISTRATOR, null, false, false)
            || $lang->load('com_j2xml', JPATH_ADMINISTRATOR, null, true);
        $lang->load('lib_j2xml', JPATH_SITE, null, false, false)
            || $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, false, false)
            // Fallback to the lib_j2xml file in the default language
            || $lang->load('lib_j2xml', JPATH_SITE, null, true)
            || $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, true);
    }

    /**
     * Read the import file (gzip-compressed or plain) as UTF-8.
     *
     * @param   string  $filename  the file to import
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function loadFile(string $filename): string
    {
        $data = implode(gzfile($filename));
        if (!$data)
        {
            $data = (string) file_get_contents($filename); // NOSONAR — CLI-only script, user already has filesystem access
        }

        if (!mb_detect_encoding($data, 'UTF-8'))
        {
            $data = mb_convert_encoding($data, 'UTF-8');
        }

        return $data;
    }

    /**
     * Output the libxml errors collected while parsing the import file.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function reportXmlErrors(): void
    {
        foreach (libxml_get_errors() as $error) {
            switch ($error->level) {
                default:
                case LIBXML_ERR_WARNING:
                    $this->out(sprintf('%d - %s at line %d',
                        $error->message, $error->line,
                        'message')
                    );
                    break;
                case LIBXML_ERR_ERROR:
                    $this->out(sprintf('%d - %s at line %d',
                        $error->message, $error->line,
                        'notice')
                    );
                    break;
                case LIBXML_ERR_FATAL:
                    $this->out(sprintf('%d - %s at line %d',
                        $error->message, $error->line,
                        'error')
                    );
                    break;
            }
        }
        libxml_clear_errors();
    }

    /**
     * Convert a dotted version string to the packed numeric form used to
     * compare J2XML document versions.
     *
     * @param   string  $version  the version string (e.g. "4.5.2")
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private static function toVersionNumber(string $version): string
    {
        $version = explode(".", (string) $version);

        return $version[0] . substr('0' . $version[1], strlen($version[1]) - 1) . substr('0' . $version[2], strlen($version[2]) - 1);
    }

    /**
     * Build the import parameter registry from the component options.
     *
     * @param   \Joomla\Registry\Registry  $params  the component options
     * @param   \SimpleXMLElement          $xml     the document being imported
     *
     * @return  \Joomla\Registry\Registry
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildImportParams(\Joomla\Registry\Registry $params, \SimpleXMLElement $xml): \Joomla\Registry\Registry
    {
        $iparams = new \Joomla\Registry\Registry();
        $iparams->set('version', (string) $xml['version']);
        $iparams->set('categories', $params->get('import_categories', 1));
        $iparams->set('contacts', $params->get('import_contacts', 1));
        $iparams->set('fields', $params->get('import_fields', 1));
        $iparams->set('images', $params->get('import_images', 1));
        $iparams->set('keep_id', $params->get('keep_id', 0));
        $iparams->set('tags', $params->get('import_tags', 1));
        $iparams->set('users', $params->get('import_users', 1));
        $iparams->set('superusers', $params->get('import_superusers', 0));
        $iparams->set('usernotes', $params->get('import_usernotes', 1));
        $iparams->set('viewlevels', $params->get('import_viewlevels', 1));
        $iparams->set('content', $params->get('import_content', 1));
        $iparams->set('weblinks', $params->get('import_weblinks'));
        $iparams->set('logger', 'cli');

        $iparams->set('keep_frontpage', $params->get('keep_frontpage'));
        $iparams->set('keep_rating', $params->get('keep_rating'));

        if ($params->get('keep_category', 1) == 2)
        {
            $iparams->set('content_category_forceto', $params->get('category'));
        }

        $iparams->set('keep_data', $params->get('keep_data'));

        return $iparams;
    }

    /**
     * Enqueue a system message.
     *
     * @param   string  $msg   The message to enqueue.
     * @param   string  $type  The message type. Default is message.
     *
     * @return  void
     *
     * @since   2.5.1
     */
    public function enqueueMessage($msg, $type = 'message'): void
    {
        $this->out(sprintf("%s - %s",self::$codes[$type],$msg));
    }

    /**
     * Returns the application name.
     *
     * Required by CMSApplicationInterface under Joomla 5/6 (the parent
     * CliApplication no longer provides a concrete implementation).
     *
     * @return  string
     *
     * @since   4.5.0
     */
    public function getName(): string
    {
        return 'cli';
    }
}

// Instantiate the CLI application directly and execute.  The
// Service\Provider\CliApplication class does not exist in Joomla 5/6
// (it was replaced by the Console provider), so we construct J2xmlCli
// directly. framework.php already set up the DI container.  Registering
// the instance with Factory is required so that Factory::getApplication()
// inside the library code resolves to this CLI application.
$cli = new J2xmlCli();
\Joomla\CMS\Factory::$application = $cli;
$cli->execute();
