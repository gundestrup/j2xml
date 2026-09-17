<?php
/**
 * @package     Joomla.Plugins
 * @subpackage  System.J2xml
 *
 * @version     __DEPLOY_VERSION__
 * @since       3.9
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
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;

/**
 * J2XML installer script (Joomla 5/6 InstallerScriptInterface form).
 */
return new class () implements InstallerScriptInterface, DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * The J2XML Version we are updating from
     *
     * @var    string|null
     * @since  3.9.232
     */
    protected ?string $fromVersion = null;

    /**
     * This method is called after a extension is installed.
     *
     * @param   InstallerAdapter  $adapter  the adapter calling this method
     *
     * @return  boolean  true on success
     */
    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * This method is called after a extension is uninstalled.
     *
     * @param   InstallerAdapter  $adapter  the adapter calling this method
     *
     * @return  boolean  true on success
     */
    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * This method is called after a extension is updated.
     *
     * @param   InstallerAdapter  $adapter  the adapter calling this method
     *
     * @return  boolean  true on success
     */
    public function update(InstallerAdapter $adapter): bool
    {
        $this->deleteUnexistingFiles();

        return true;
    }

    /**
     * Function to act prior to installation process begins
     *
     * @param   string            $type     Which action is happening (install|uninstall|discover_install|update)
     * @param   InstallerAdapter  $adapter  the adapter calling this method
     *
     * @return  boolean  true on success
     */
    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'update')
        {
            $db    = $this->getDatabase();
            $query = $db->getQuery()->clear()
                ->select('*')
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_j2xml'));

            $db->setQuery($query);

            $j2xml = $db->loadObject();

            if ($j2xml)
            {
                $manifestValues = json_decode($j2xml->manifest_cache, true);

                if (is_array($manifestValues) && array_key_exists('version', $manifestValues))
                {
                    $this->fromVersion = $manifestValues['version'];

                    if (version_compare($this->fromVersion, '3.9.3', '<'))
                    {
                        Factory::getApplication()->enqueueMessage(\Joomla\CMS\Language\Text::_('COM_J2XML_NOTINSTALLED'));

                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Runs right after any installation action is preformed on the extension.
     *
     * @param   string            $type     Type of PostFlight action. Possible values are:
     *                                      - * install
     *                                      - * update
     *                                      - * discover_install
     * @param   InstallerAdapter  $adapter  the adapter calling this method
     *
     * @return  boolean  true on success
     */
    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        // Add token column to #__j2xml_websites if the table exists
        // (created by J2XML Pro).
        $db = $this->getDatabase();
        $tables = $db->getTableList();
        $prefix = $db->getPrefix();
        $tableName = $prefix . 'j2xml_websites';

        if (in_array($tableName, $tables))
        {
            $columns = $db->getTableColumns('#__j2xml_websites');
            if (!isset($columns['token']))
            {
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName('#__j2xml_websites')
                    . ' ADD COLUMN ' . $db->quoteName('token') . ' TEXT NULL'
                )->execute();
            }
        }

        return true;
    }

    /**
     * Delete files that should not exist
     *
     * @return  void
     */
    public function deleteUnexistingFiles(): void
    {
        $files = [
            /*
             * 3.9.232
             */
            '/administrator/components/com_j2xml/controllers/cpanel.json.php',
            '/administrator/components/com_j2xml/controllers/cpanel.php',
            '/language/en-GB/en-GB.lib_eshiol.ini',
            '/language/en-GB/en-GB.lib_eshiol.sys.ini',
        ];

        // Note: there is an issue while deleting folders using the ftp mode
        $folders = [
            /*
             * 3.9.232
             */
            '/administrator/components/com_j2xml/views/cpanel',
        ];

        Factory::getApplication()->getLanguage()->load('com_j2xml', JPATH_ADMINISTRATOR);

        foreach ($files as $file)
        {
            if (\Joomla\Filesystem\File::exists(JPATH_ROOT . $file))
            {
                if (\Joomla\Filesystem\File::delete(JPATH_ROOT . $file))
                {
                    Factory::getApplication()->enqueueMessage(\Joomla\CMS\Language\Text::sprintf('COM_J2XML_FILE_DELETED', $file));
                }
                else
                {
                    Factory::getApplication()->enqueueMessage(\Joomla\CMS\Language\Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $file));
                }
            }
        }

        foreach ($folders as $folder)
        {
            if (\Joomla\Filesystem\Folder::exists(JPATH_ROOT . $folder))
            {
                if (\Joomla\Filesystem\Folder::delete(JPATH_ROOT . $folder))
                {
                    Factory::getApplication()->enqueueMessage(\Joomla\CMS\Language\Text::sprintf('COM_J2XML_FOLDER_DELETED', $folder));
                }
                else
                {
                    Factory::getApplication()->enqueueMessage(\Joomla\CMS\Language\Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $folder));
                }
            }
        }
    }
};
