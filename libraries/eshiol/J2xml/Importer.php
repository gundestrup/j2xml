<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       1.6.0
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
namespace eshiol\J2xml;

// no direct access
defined('_JEXEC') or die;

use eshiol\J2xml\Table\Category;
use eshiol\J2xml\Table\Contact;
use eshiol\J2xml\Table\Content;
use eshiol\J2xml\Table\Field;
use eshiol\J2xml\Table\Fieldgroup;
use eshiol\J2xml\Table\Image;
use eshiol\J2xml\Table\Menu;
use eshiol\J2xml\Table\Menutype;
use eshiol\J2xml\Table\Module;
use eshiol\J2xml\Table\Tag;
use eshiol\J2xml\Table\User;
use eshiol\J2xml\Table\Usernote;
use eshiol\J2xml\Table\Viewlevel;
use eshiol\J2xml\Table\Weblink;
use eshiol\J2xml\Version;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Database\DatabaseInterface;

/**
 *
 * Importer
 *
 */
class Importer
{
    public const SUPPORTED_XML_VERSIONS = ['211200', '190200', '150900', '120500'];

    protected string $nullDate = '';

    protected ?\Joomla\CMS\User\User $user = null;

    protected int $userId = 0;

    protected string $now = '';

    protected ?string $option = null;

    /**
     * The application instance.
     *
     * @var CMSApplicationInterface
     * @since __DEPLOY_VERSION__
     */
    protected CMSApplicationInterface $app;

    /**
     * CONSTRUCTOR
     *
     * @param   DatabaseInterface           $db   Optional database instance.
     * @param   CMSApplicationInterface     $app  Optional application instance.
     *
     * @since 1.6.0
     */
    function __construct (?DatabaseInterface $db = null, ?CMSApplicationInterface $app = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $db         = $db ?? \Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        $app        = $app ?? \Joomla\CMS\Factory::getApplication();
        $this->app  = $app;
        $serverType = $db->getServerType();

        // Merge the default translation with the current translation
        $jlang = $app->getLanguage();
        $jlang->load('lib_j2xml', JPATH_SITE, 'en-GB', true);
        $jlang->load('lib_j2xml', JPATH_SITE, $jlang->getDefault(), true);
        $jlang->load('lib_j2xml', JPATH_SITE, null, true);

        $this->user     = $app->getIdentity();
        $this->nullDate = $db->getNullDate();
        $this->userId  = $this->user ? (int) $this->user->get('id') : 0;
        $this->now      = (new \Joomla\CMS\Date\Date("now"))->format("%Y-%m-%d-%H-%M-%S");
        $this->option   = (PHP_SAPI != 'cli') ? $app->getInput()->getCmd('option') : 'cli_' .
                 strtolower(get_class($app));

        try {
            $query = "CREATE TABLE IF NOT EXISTS " . $db->quoteName("#__j2xml_usergroups");
            if ($serverType == 'postgresql')
            {
                $query .= " (" .
                    $db->quoteName("id") . " serial NOT NULL," .
                    $db->quoteName("parent_id") . " bigint DEFAULT 0 NOT NULL," .
                    $db->quoteName("title") . " varchar(100) DEFAULT '' NOT NULL," .
                    "PRIMARY KEY (" . $db->quoteName("id") . "));";
                $db->setQuery($query)->execute();
            }
            else
            {
                $query .= " (" .
                    $db->quoteName("id") . " int(10) unsigned NOT NULL," .
                    $db->quoteName("parent_id") . " int(10) unsigned NOT NULL DEFAULT '0'," .
                    $db->quoteName("title") . " varchar(100) NOT NULL DEFAULT ''," .
                    "PRIMARY KEY (" . $db->quoteName("id") . ")) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;";
            }
            $db->setQuery($query)->execute();

            User::syncUsergroupsTable($db);
        }
        catch (\Joomla\Database\Exception\ExecutionFailureException $e)
        {
            // If the query fails we will go on
        }

    }

    /**
     * Import data
     *
     * @param \SimpleXMLElement $xml
     *          xml
     * @param \Joomla\Registry\Registry $options
     *          An optional associative array of settings.
     *          @option boolean 'import_content' import articles
     *          @option int 'default_category'
     *          @option int 'content_category'
     *
     * @throws
     * @return boolean
     * @access public
     *
     * @since 1.6.0
     */
    function import ($xml, $params)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $import_viewlevels = $params->get('viewlevels');
        if ($import_viewlevels)
        {
            Viewlevel::import($xml, $params);
        }

        $import_fields = $params->get('fields', 0);
        if ($import_fields)
        {
            Fieldgroup::import($xml, $params);
            Field::import($xml, $params);
        }

        $import_users = $params->get('users');
        if ($import_users)
        {
            User::import($xml, $params);
        }

        $import_tags = $params->get('tags', 1);
        if ($import_tags)
        {
            Tag::import($xml, $params);
        }

        $import_content = $params->get('content');
        $import_categories = $params->get('categories', 1);
        if ($import_categories && !$import_content)
        {
            $defaultExtension = $params->get('extension') ?: 'com_content';
            $categories = $xml->xpath('//j2xml/category') ?: [];
            $extensions = array_unique(array_map(
                static fn($category) => trim((string) $category->extension) ?: $defaultExtension,
                $categories
            ));
            foreach ($extensions as $extension)
            {
                $params->set('extension', $extension);
                Category::import($xml, $params);
            }
        }
        if ($import_content)
        {
            Content::import($xml, $params);
        }

        $import_images = $params->get('images', 0);
        if ($import_images)
        {
            Image::import($xml, $params);
        }

        $import_usernotes = $params->get('usernotes', 0);
        if ($import_usernotes)
        {
            Usernote::import($xml, $params);
        }

        $import_contacts = $params->get('contacts', 0);
        if ($import_contacts)
        {
            Contact::import($xml, $params);
        }

        $import_weblinks = $params->get('weblinks');
        if ($import_weblinks)
        {
            Weblink::import($xml, $params);
        }

        $import_menus = $params->get('menus', 1);
        if ($import_menus)
        {
            Menutype::import($xml, $params);
            Menu::import($xml, $params);
        }

        $import_modules = $params->get('modules', 1);
        if ($import_modules)
        {
            Module::import($xml, $params);
        }

        if ($params->get('fire', 1))
        {
            \Joomla\CMS\Plugin\PluginHelper::importPlugin('j2xml');
            // Trigger the onAfterImport event.
            $this->app->getDispatcher()->dispatch('onContentAfterImport',
                new \Joomla\Event\Event('onContentAfterImport', [
                    'com_j2xml.import',
                    &$xml,
                    $params
                ]));
        }

        return true;
    }

    /**
     * Return true if the file is supported
     *
     * @param String $version
     *
     * @return boolean
     * @since  21.12.353
     */
    public function isSupported(string $version): bool
    {
        return in_array($version, self::SUPPORTED_XML_VERSIONS, true);
    }
}
