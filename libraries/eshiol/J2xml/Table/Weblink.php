<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       15.3.248
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
namespace eshiol\J2xml\Table;

use eshiol\J2xml\Table\Table;
use Joomla\CMS\Component\ComponentHelper;

/**
 *
 * Viewlevel Table
 *
 */
class Weblink extends Table
{

    /**
     * Constructor
     *
     * @param \Joomla\Database\DatabaseDriver $db
     *          A database connector object
     *
     * @since 1.5.3beta3.38
     */
    public function __construct (\Joomla\Database\DatabaseDriver $db)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        parent::__construct('#__weblinks', 'id', $db);
    }

    /**
     * Export item list to xml
     *
     * @access public
     * @since 15.9.261
     */
    function toXML ($mapKeysToText = false)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $this->buildTagAlias('com_weblinks.weblink');
        $this->buildAssociationAlias('#__weblinks', 'com_weblinks.item');

        return parent::toXML($mapKeysToText);
    }

    /**
     * Export data
     *
     * @param int $id
     *          the id of the item to be exported
     * @param \SimpleXMLElement $xml
     *          xml
     * @param array $options
     *
     * @throws
     * @return void
     * @access public
     *
     * @since 18.8.310
     */
    public static function export ($id, &$xml, $options, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $item = static::loadExportItem('weblink', $id, $xml, $db);
        if (!$item)
        {
            return;
        }

        self::exportItemViewlevel($item->access, $xml, $options);
        self::exportItemCategory($item->catid, $xml, $options);
        self::appendItemXml($item, $xml);
        self::exportItemUsers($item, $xml, $options);

        if (isset($options['images']) && $options['images'])
        {
            self::exportImages($item, $xml, $options);
        }
    }

    /**
     * Export the images referenced by the weblink description and the
     * images field.
     *
     * @param Weblink $item
     *          the weblink being exported
     * @param \SimpleXMLElement $xml
     *          the export document
     * @param array $options
     *          the export options
     *
     * @return void
     */
    private static function exportImages ($item, &$xml, $options)
    {
        self::exportImagesFromText($item->description, $xml, $options);
        self::exportImagesFromJson($item->images, $xml, $options, ['image_first', 'image_second']);
    }

    /**
     * Import data
     *
     * @param \SimpleXMLElement $xml
     *          xml
     * @param \JRegistry $params
     *          @option int 'weblinks' 0: No | 1: Yes, if not exists | 2: Yes,
     *          overwrite if exists
     *          @option string 'context'
     *
     * @throws
     * @return void
     * @access public
     *
     * @since 18.8.310
     */
    public static function import ($xml, &$params, $db = null, $userId = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $import_weblinks = $params->get('weblinks', 1);
        if ($import_weblinks == 0)
            return;

        $db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        // Check if the component is installed and enabled.
        if (!ComponentHelper::isEnabled('com_weblinks'))
        {
            return;
        }

        $params->set('extension', 'com_weblinks');
        $params->def('category_default', self::getCategoryId('uncategorised', 'com_weblinks'));

        $import_categories = $params->get('categories');
        if ($import_categories)
        {
            Category::import($xml, $params);
        }

        foreach ($xml->xpath("//j2xml/weblink[not(title = '')]") as $record)
        {
            self::prepareData($record, $data, $params);

            $id = $data['id'];

            $query = $db->getQuery()->clear()
                ->select([
                    $db->quoteName('id'),
                    $db->quoteName('title')
            ])
                ->from($db->quoteName('#__weblinks'))
                ->where($db->quoteName('alias') . ' = ' . $db->quote($data['alias']));
            $item = $db->setQuery($query)->loadObject();

            if (!$item || ($import_weblinks))
            {
                $table = new \eshiol\J2xml\Table\Weblink($db);
                if (!$item)
                {
                    $data['id'] = null;
                }
                else
                {
                    $data['id'] = $item->id;
                    $table->load($data['id']);
                }

                // Trigger the onContentBeforeSave event.
                if (self::bindAndStore($table, $data, 'LIB_J2XML_MSG_WEBLINK_NOT_IMPORTED'))
                {
                    self::setAssociations($table->id, $table->language, $data['associations'], 'com_weblinks.item');

                    \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_WEBLINK_IMPORTED', $table->title), \Joomla\CMS\Log\Log::INFO, 'lib_j2xml'));
                    // Trigger the onContentAfterSave event.
                }
                $table = null;
            }
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see Table::prepareData()
     *
     * @since 20.5.349
     */
    public static function prepareData ($record, &$data, $params, $userId = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        self::prepareAssociatedData($record, $data, $params, 'com_weblinks', static function ($association) {
            return self::getWeblinkId($association);
        }, '#__weblinks');
    }
}
