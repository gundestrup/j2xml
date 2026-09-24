<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       1.5.3.39
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

use eshiol\J2xml\Version;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Utilities\ArrayHelper;

/**
 *
 * Table
 *
 */
#[\AllowDynamicProperties]
class Table extends \Joomla\CMS\Table\Table
{

    /**
     * An array of key names to be not exported
     *
     * @var array
     * @since 1.5.3.39
     */
    protected array $excluded = [];

    /**
     * An array of key names to be exported as alias
     *
     * @var array
     * @since 1.5.3.39
     */
    protected array $aliases = [];

    /**
     * An array of key names to be exported in json encoded format
     *
     * @var array
     * @since 13.6.116
     */
    protected array $jsonEncode = [];

    /**
     *
     * @var string
     * @since 18.8.310
     */
    const IMAGE_MATCH_STRING = '/<img.*?src="([^"]*)".*?[^>]*>/s';

    /**
     * Object constructor to set table and key fields.
     * In most cases this will
     * be overridden by child classes to explicitly set the table and key fields
     * for a particular database table.
     *
     * @param
     *          string Name of the table to model.
     * @param
     *          string Name of the primary key field in the table.
     * @param
     *          object JDatabase connector object.
     * @since 1.0
     */
    public function __construct ($table, $key, &$db)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        parent::__construct($table, $key, $db);

        $this->excluded = [
            'asset_id',
            'parent_id',
            'lft',
            'rgt',
            'level',
            'checked_out',
            'checked_out_time'
        ];
        $this->aliases = [];
    }

    /**
     * Build the SQL alias query for custom fields (including subform fields)
     * and store it in $this->aliases['field'].
     *
     * Shared by Content::toXML() and User::toXML() to avoid code duplication.
     *
     * @return void
     * @since 4.0.0
     */
    protected function buildFieldAliases(): void
    {
        $serverType = $this->getDatabase()->getServerType();

        // Non-subform field values
        $query = $this->getDatabase()->getQuery()->clear()
            ->select($this->getDatabase()->quoteName('f.name'))
            ->select($this->getDatabase()->quoteName('v.value'))
            ->from($this->getDatabase()->quoteName('#__fields_values', 'v'))
            ->from($this->getDatabase()->quoteName('#__fields', 'f'))
            ->where($this->getDatabase()->quoteName('f.id') . ' = ' . $this->getDatabase()->quoteName('v.field_id'))
            ->where($this->getDatabase()->quoteName('v.item_id') . ' = ' . $this->getDatabase()->quote((string) $this->id));
        $query->where($this->getDatabase()->quoteName('f.type') . ' <> ' . $this->getDatabase()->quote('subform'));
        $this->aliases['field'] = (string) $query;

        // Map field IDs to names for subform processing
        $query = $this->getDatabase()->getQuery()->clear()
            ->select($this->getDatabase()->quoteName('f.id'))
            ->select($this->getDatabase()->quoteName('f.name'))
            ->from($this->getDatabase()->quoteName('#__fields', 'f'));
        $fields = [];
        foreach ($this->getDatabase()->setQuery($query)->loadObjectList() as $field)
        {
            $fields['field' . $field->id] = $field->name;
        }

        // Subform field values — decode, rename field IDs to names, re-encode, UNION
        $query = $this->getDatabase()->getQuery()->clear()
            ->select($this->getDatabase()->quoteName('f.name'))
            ->select($this->getDatabase()->quoteName('v.value'))
            ->from($this->getDatabase()->quoteName('#__fields_values', 'v'))
            ->from($this->getDatabase()->quoteName('#__fields', 'f'))
            ->where($this->getDatabase()->quoteName('f.type') . ' = ' . $this->getDatabase()->quote('subform'))
            ->where($this->getDatabase()->quoteName('f.id') . ' = ' . $this->getDatabase()->quoteName('v.field_id'))
            ->where($this->getDatabase()->quoteName('v.item_id') . ' = ' . $this->getDatabase()->quote((string) $this->id));
        $fieldValues = $this->getDatabase()->setQuery($query)->loadObjectList();
        foreach ($fieldValues as $field)
        {
            $subformValue = json_decode($field->value, true);
            foreach ($subformValue as $rowId => $row)
            {
                foreach ($row as $fieldId => $fieldValue)
                {
                    unset($subformValue[$rowId][$fieldId]);
                    $subformValue[$rowId][$fields[$fieldId]] = $fieldValue;
                }
            }
            $subformValue = json_encode($subformValue, true);

            $query = $this->getDatabase()->getQuery()->clear()
                ->select($this->getDatabase()->quote($field->name))
                ->select($this->getDatabase()->quote($subformValue));
            if ($serverType === 'sqlserver')
            {
                $query->from($this->getDatabase()->quoteName('DUAL'));
            }
            $this->aliases['field'] .= ' UNION ' . (string) $query;
        }
    }

    /**
     * Export custom fields (including subform fields) for an item.
     *
     * Shared by Content::export() and User::export() to avoid code duplication.
     *
     * @param int                   $id      The item id
     * @param \SimpleXMLElement     $xml     The XML element to append to
     * @param array                 $options Export options
     * @param \Joomla\Database\DatabaseDriver $db Database driver
     *
     * @return void
     * @since 4.0.0
     */
    protected static function exportFields($id, &$xml, $options, $db): void
    {
        // load subform fields
        $query = $db->getQuery()->clear()
            ->select($db->quoteName('v.value'))
            ->from($db->quoteName('#__fields_values', 'v'))
            ->from($db->quoteName('#__fields', 'f'))
            ->where($db->quoteName('f.type') . ' = ' . $db->quote('subform'))
            ->where($db->quoteName('f.id') . ' = ' . $db->quoteName('v.field_id'));
        $subformValues = $db->setQuery($query)->loadColumn();
        foreach ($subformValues as $subformValue)
        {
            foreach (json_decode($subformValue, true) as $row)
            {
                foreach ($row as $fieldId => $fieldValue)
                {
                    Field::export(substr($fieldId, 5), $xml, $options);
                }
            }
        }

        $query = $db->getQuery()->clear()
            ->select('DISTINCT field_id')
            ->from('#__fields_values')
            ->where('item_id = ' . $db->quote($id));
        $db->setQuery($query);

        $ids_field = $db->loadColumn();
        foreach ($ids_field as $id_field)
        {
            Field::export($id_field, $xml, $options);
        }
    }

    /**
     * Build the SQL alias query for tags assigned to an item.
     *
     * @param string $typeAlias
     *          the Joomla tag type alias
     *
     * @return void
     * @since 4.5.4
     */
    protected function buildTagAlias(string $typeAlias): void
    {
        $db = $this->getDatabase();
        $this->aliases['tag'] = (string) $db->getQuery()->clear()
            ->select($db->quoteName('t.path'))
            ->from($db->quoteName('#__tags', 't'))
            ->from($db->quoteName('#__contentitem_tag_map', 'm'))
            ->where($db->quoteName('type_alias') . ' = ' . $db->quote($typeAlias))
            ->where($db->quoteName('t.id') . ' = ' . $db->quoteName('m.tag_id'))
            ->where($db->quoteName('m.content_item_id') . ' = ' . $db->quote((string) $this->id));
    }

    /**
     * Build a standard association query for a category-backed item.
     *
     * @param string $table
     *          the item database table
     * @param string $context
     *          the Joomla associations context
     * @param string|null $select
     *          the select expression; defaults to the item path
     *
     * @return mixed the association query
     * @since 4.5.4
     */
    protected function buildAssociationQuery(string $table, string $context, ?string $select = null)
    {
        $db = $this->getDatabase();
        $query = $db->getQuery()->clear();
        $select = $select ?? $query->concatenate([$db->quoteName('cc.path'), $db->quoteName('c.alias')], '/');

        return $query
            ->select($select)
            ->from($db->quoteName('#__associations', 'asso1'))
            ->join('INNER', $db->quoteName('#__associations', 'asso2') . ' ON ' . $db->quoteName('asso1.key') . ' = ' . $db->quoteName('asso2.key'))
            ->join('INNER', $db->quoteName($table, 'c') . ' ON ' . $db->quoteName('asso2.id') . ' = ' . $db->quoteName('c.id'))
            ->join('INNER', $db->quoteName('#__categories', 'cc') . ' ON ' . $db->quoteName('c.catid') . ' = ' . $db->quoteName('cc.id'))
            ->where([
                $db->quoteName('asso1.id') . ' = ' . (int) $this->id,
                $db->quoteName('asso1.context') . ' = ' . $db->quote($context),
                $db->quoteName('asso2.id') . ' <> ' . (int) $this->id]);
    }

    /**
     * Build the standard association XML alias for a category-backed item.
     *
     * @param string $table
     *          the item database table
     * @param string $context
     *          the Joomla associations context
     *
     * @return void
     * @since 4.5.4
     */
    protected function buildAssociationAlias(string $table, string $context): void
    {
        $this->aliases['association'] = (string) $this->buildAssociationQuery($table, $context);
    }

    /**
     * Check whether an item has already been exported.
     *
     * @param \SimpleXMLElement $xml
     *          the export document
     * @param string $element
     *          the item XML element name
     * @param mixed $id
     *          the item id
     *
     * @return boolean true when the item is already present
     * @since 4.5.4
     */
    protected static function isExported(&$xml, string $element, $id): bool
    {
        return (bool) $xml->xpath("//j2xml/{$element}/id[text() = '" . $id . "']");
    }

    /**
     * Load an item for export unless it is already present in the XML.
     *
     * @param string $element
     *          the item XML element name
     * @param mixed $id
     *          the item id
     * @param \SimpleXMLElement $xml
     *          the export document
     * @param \Joomla\Database\DatabaseInterface|null $db
     *          the database connector, populated when null
     *
     * @return static|null the loaded item, or null when unavailable/exported
     * @since 4.5.4
     */
    protected static function loadExportItem(string $element, $id, &$xml, &$db)
    {
        if (!is_scalar($id) || filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false)
        {
            return null;
        }

        if (self::isExported($xml, $element, $id))
        {
            return null;
        }

        $db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $item = new static($db); // @phpstan-ignore new.static
        if (!$item->load($id))
        {
            return null;
        }

        return $item;
    }

    /**
     * Append an item's serialized XML to the export document.
     *
     * @param Table $item
     *          the item being exported
     * @param \SimpleXMLElement $xml
     *          the export document
     *
     * @return void
     * @since 4.5.4
     */
    protected static function appendItemXml($item, &$xml): void
    {
        $doc = dom_import_simplexml($xml)->ownerDocument;
        $fragment = $doc->createDocumentFragment();

        $fragment->appendXML($item->toXML());
        $doc->documentElement->appendChild($fragment);
    }

    /**
     * Export the users referenced by an item.
     *
     * @param Table $item
     *          the item being exported
     * @param \SimpleXMLElement $xml
     *          the export document
     * @param array $options
     *          export options
     * @param array $fields
     *          user id property names to export
     *
     * @return void
     * @since 4.5.4
     */
    protected static function exportItemUsers($item, &$xml, $options, array $fields = ['created_by', 'modified_by']): void
    {
        if (empty($options['users']))
        {
            return;
        }

        foreach ($fields as $field)
        {
            if (!empty($item->$field))
            {
                User::export($item->$field, $xml, $options);
            }
        }
    }

    /**
     * Export an item's category when category export is enabled.
     *
     * @param mixed $catid
     *          the category id
     * @param \SimpleXMLElement $xml
     *          the export document
     * @param array $options
     *          export options
     *
     * @return void
     * @since 4.5.4
     */
    protected static function exportItemCategory($catid, &$xml, $options): void
    {
        if (!empty($options['categories']) && ($catid > 0))
        {
            Category::export($catid, $xml, $options);
        }
    }

    /**
     * Export a non-core view level.
     *
     * @param mixed $access
     *          the access level id
     * @param \SimpleXMLElement $xml
     *          the export document
     * @param array $options
     *          export options
     *
     * @return void
     * @since 4.5.4
     */
    protected static function exportItemViewlevel($access, &$xml, $options): void
    {
        if ($access > 6)
        {
            Viewlevel::export($access, $xml, $options);
        }
    }

    /**
     * Export all tags assigned to an item when tag export is enabled.
     *
     * @param string $typeAlias
     *          the Joomla tag type alias
     * @param int $id
     *          the item id
     * @param \SimpleXMLElement $xml
     *          the export document
     * @param array $options
     *          export options
     *
     * @return void
     * @since 4.5.4
     */
    protected static function exportItemTags(string $typeAlias, $id, &$xml, $options): void
    {
        if (empty($options['tags']))
        {
            return;
        }

        $htags = new \Joomla\CMS\Helper\TagsHelper();
        foreach ($htags->getItemTags($typeAlias, $id) as $itemtag)
        {
            Tag::export($itemtag->tag_id, $xml, $options);
        }
    }

    /**
     * Export selected image fields from a JSON object.
     *
     * @param string $json
     *          the JSON value containing image properties
     * @param \SimpleXMLElement $xml
     *          the export document
     * @param array $options
     *          export options
     * @param array $fields
     *          JSON property names to export
     *
     * @return void
     * @since 4.5.4
     */
    protected static function exportImagesFromJson($json, &$xml, $options, array $fields): void
    {
        $images = json_decode($json);
        if (!$images)
        {
            return;
        }

        foreach ($fields as $field)
        {
            if (isset($images->$field))
            {
                Image::export($images->$field, $xml, $options);
            }
        }
    }

    /**
     * Prepare item data and resolve exported association paths.
     *
     * @param \SimpleXMLElement $record
     *          the XML record being imported
     * @param array $data
     *          the item data being imported
     * @param \Joomla\Registry\Registry $params
     *          the import parameters
     * @param string $extension
     *          the item extension
     * @param callable $resolver
     *          resolves an exported association path to a local item id
     * @param string $table
     *          the item database table
     *
     * @return void
     * @since 4.5.4
     */
    protected static function prepareAssociatedData($record, &$data, $params, string $extension, callable $resolver, string $table): void
    {
        $params->set('extension', $extension);
        self::prepareData($record, $data, $params);

        $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        self::resolveAssociationData($data, $resolver, $table, $db);
    }

    /**
     * Resolve exported association paths to local item ids keyed by language.
     *
     * @param array $data
     *          the item data being imported
     * @param callable $resolver
     *          resolves an exported association path to a local item id
     * @param string $table
     *          the item database table
     * @param \Joomla\Database\DatabaseInterface $db
     *          the database connector
     *
     * @return void
     * @since 4.5.4
     */
    protected static function resolveAssociationData(&$data, callable $resolver, string $table, $db): void
    {
        if (empty($data['associations']))
        {
            $data['associations'] = [];
        }

        if (isset($data['associationlist']))
        {
            $associations = $data['associationlist']['association'] ?? [];
            unset($data['associationlist']);
        }
        elseif (isset($data['association']))
        {
            $associations = [$data['association']];
            unset($data['association']);
        }
        else
        {
            return;
        }

        foreach ($associations as $association)
        {
            $id = $resolver($association);
            if (!$id)
            {
                continue;
            }

            $language = $db->setQuery($db->getQuery()->clear()
                ->select($db->quoteName('language'))
                ->from($db->quoteName($table))
                ->where($db->quoteName('id') . ' = ' . (int) $id))
                ->loadResult();
            if ($language !== '*')
            {
                $data['associations'][$language] = (int) $id;
            }
        }
    }

    /**
     * Store an imported table and log the result.
     *
     * @param \Joomla\CMS\Table\Table $table
     *          the table being saved
     * @param string $successKey
     *          the language key for success messages
     * @param string $failureKey
     *          the language key for failure messages
     * @param string $label
     *          the item label used in messages
     * @param string|null $successLabel
     *          the item label used in success messages; defaults to $label
     *
     * @return boolean true when the table was stored
     * @since 4.5.4
     */
    protected static function storeImportedTable($table, string $successKey, string $failureKey, string $label, ?string $successLabel = null): bool
    {
        if ($table->store())
        {
            \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(
                \Joomla\CMS\Language\Text::sprintf($successKey, $successLabel ?? $label),
                \Joomla\CMS\Log\Log::INFO,
                'lib_j2xml'));
            return true;
        }

        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(
            \Joomla\CMS\Language\Text::sprintf($failureKey, $label, $table->getError()),
            \Joomla\CMS\Log\Log::ERROR,
            'lib_j2xml'));

        return false;
    }

    /**
     * Bind imported data, store the table and log a failure.
     *
     * @param \Joomla\CMS\Table\Table $table
     *          the table being saved
     * @param array $data
     *          the imported item data
     * @param string $failureKey
     *          the language key for failure messages
     * @param string $failureField
     *          the data field used as the item label in failures
     *
     * @return boolean true when the table was stored
     * @since 4.5.4
     */
    protected static function bindAndStore($table, array $data, string $failureKey, string $failureField = 'title'): bool
    {
        $table->bind($data);
        if ($table->store())
        {
            return true;
        }

        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(
            \Joomla\CMS\Language\Text::sprintf($failureKey, $data[$failureField] ?? '', $table->getError()),
            \Joomla\CMS\Log\Log::ERROR,
            'lib_j2xml'));

        return false;
    }

    /**
     * Update a column to a new value, optionally with extra conditions.
     *
     * @param \Joomla\Database\DatabaseInterface $db
     *          the database connector
     * @param string $table
     *          the table to update
     * @param string $column
     *          the column to update and match
     * @param mixed $newValue
     *          the SQL expression for the new value
     * @param mixed $oldValue
     *          the SQL expression for the old value
     * @param array $conditions
     *          additional WHERE conditions
     *
     * @return void
     * @since 4.5.4
     */
    protected static function updateColumn($db, string $table, string $column, $newValue, $oldValue, array $conditions = []): void
    {
        $query = $db->getQuery()->clear()
            ->update($db->quoteName($table))
            ->set($db->quoteName($column) . ' = ' . $newValue)
            ->where($db->quoteName($column) . ' = ' . $oldValue);
        foreach ($conditions as $condition)
        {
            $query->where($condition);
        }

        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        $db->setQuery($query)->execute();
    }

    /**
     * Resolve a category-backed item path to a local item id.
     *
     * @param mixed $path
     *          the exported item path or numeric id
     * @param string $table
     *          the item database table
     * @param string $extension
     *          the category extension
     * @param int $defaultId
     *          the id to return when no item exists
     * @param \Joomla\Database\DatabaseInterface|null $db
     *          the database connector
     *
     * @return mixed the local item id or the default
     * @since 4.5.4
     */
    protected static function getCategorisedItemId($path, string $table, string $extension, $defaultId = 0, $db = null)
    {
        if (is_numeric($path))
        {
            return $path;
        }

        $db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $i = strrpos($path, '/');
        $query = $db->getQuery()->clear()
            ->select($db->quoteName('c.id'))
            ->from($db->quoteName($table, 'c'))
            ->join('INNER', $db->quoteName('#__categories', 'cc') . ' ON ' . $db->quoteName('c.catid') . ' = ' . $db->quoteName('cc.id'))
            ->where($db->quoteName('cc.extension') . ' = ' . $db->quote($extension))
            ->where($db->quoteName('c.alias') . ' = ' . $db->quote(substr($path, $i + 1)))
            ->where($db->quoteName('cc.path') . ' = ' . $db->quote(substr($path, 0, $i)));
        $id = $db->setQuery($query)->loadResult();

        return $id ?: $defaultId;
    }

    /**
     * Method to load a row from the database by primary key and bind the fields
     * to the JTable instance properties.
     *
     * @param mixed $keys
     *          An optional primary key value to load the row by, or an array
     *          of fields to match. If not
     *          set the instance property value is used.
     * @param boolean $reset
     *          True to reset the default values before loading the new row.
     *
     * @return boolean True if successful. False if row not found.
     *
     * @link https://docs.joomla.org/JTable/load
     * @since 11.1
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     */
    public function load ($keys = null, $reset = true)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $ret = parent::load($keys, $reset);
        if ($ret)
        {
            if (isset($this->created_by))
            {
                $this->aliases['created_by'] = (string) $this->getDatabase()->getQuery()->clear()
                    ->select($this->getDatabase()->quoteName('username'))
                    ->from($this->getDatabase()->quoteName('#__users'))
                    ->where($this->getDatabase()->quoteName('id') . ' = ' . (int) $this->created_by);
            }
            if (isset($this->created_user_id))
            {
                $this->aliases['created_user_id'] = (string) $this->getDatabase()->getQuery()->clear()
                    ->select($this->getDatabase()->quoteName('username'))
                    ->from($this->getDatabase()->quoteName('#__users'))
                    ->where($this->getDatabase()->quoteName('id') . ' = ' . (int) $this->created_user_id);
            }
            if (isset($this->modified_by))
            {
                $this->aliases['modified_by'] = (string) $this->getDatabase()->getQuery()->clear()
                    ->select($this->getDatabase()->quoteName('username'))
                    ->from($this->getDatabase()->quoteName('#__users'))
                    ->where($this->getDatabase()->quoteName('id') . ' = ' . (int) $this->modified_by);
            }
            if (isset($this->modified_user_id))
            {
                $this->aliases['modified_user_id'] = (string) $this->getDatabase()->getQuery()->clear()
                    ->select($this->getDatabase()->quoteName('username'))
                    ->from($this->getDatabase()->quoteName('#__users'))
                    ->where($this->getDatabase()->quoteName('id') . ' = ' . (int) $this->modified_user_id);
            }
            if (isset($this->catid))
            {
                $this->aliases['catid'] = (string) $this->getDatabase()->getQuery()->clear()
                    ->select($this->getDatabase()->quoteName('path'))
                    ->from($this->getDatabase()->quoteName('#__categories'))
                    ->where($this->getDatabase()->quoteName('id') . ' = ' . (int) $this->catid);
            }
            if (isset($this->access))
            {
                $query = $this->getDatabase()->getQuery()->clear();
                $serverType = $this->getDatabase()->getServerType();

                if ($serverType === 'postgresql')
                {
                    $query->select(
                        'CASE WHEN ' . $this->getDatabase()->quoteName('v.id') . '<=6 THEN TO_CHAR(' . $this->getDatabase()->quoteName('v.id') . ', \'9\') ELSE ' .
                        $this->getDatabase()->quoteName('v.title') . ' END');
                }
                else
                {
                    $query->select(
                        'IF(' . $this->getDatabase()->quoteName('v.id') . '<=6, ' . $this->getDatabase()->quoteName('v.id') . ', ' . $this->getDatabase()->quoteName('v.title') .
                        ')');
                }
                $query->from($this->getDatabase()->quoteName('#__viewlevels', 'v'))
                    ->join('RIGHT',
                        $this->getDatabase()->quoteName($this->_tbl, 'a') . ' ON ' . $this->getDatabase()->quoteName('v.id') . ' = ' . $this->getDatabase()->quoteName('a.access'))
                    ->where($this->getDatabase()->quoteName('a.id') . ' = ' . (int) $this->id);
                $this->aliases['access'] = (string) $query;
            }
        }
        return $ret;
    }

    /**
     * Export item list to xml
     *
     * @param
     *          bool tag use the main class tag
     * @access public
     * @param
     *          boolean Map foreign keys to text values
     */
    protected function _serialize ($tag = true)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        // Initialise variables.
        $xml = [];

        foreach (get_object_vars($this) as $k => $v)
        {
            $item = $this->_serializeField($k, $v);
            if ($item !== null)
            {
                $xml[] = $item;
            }
        }

        $xml = array_merge($xml, $this->_serializeAliases());

        // Return the XML array imploded over new lines.
        if ($tag)
        {
            $class = new \ReflectionClass($this);
            $mainTag = strtolower($class->getShortName());
            $ret = '<' . $mainTag . '>' . implode("\n", $xml) . '</' . $mainTag . '>';
        }
        else
        {
            $ret = implode("\n", $xml);
        }

        // Return the XML array imploded over new lines.
        return $ret;
    }

    /**
     * Serialise a single object property to an XML element.
     *
     * @param string $k
     *          the property name
     * @param mixed $v
     *          the property value
     *
     * @return string|null the XML element, or null when the property
     *         must be skipped (internal, excluded or aliased field)
     */
    private function _serializeField ($k, $v)
    {
        // If the value is null or non-scalar, or the field is internal
        // ignore it.
        if (!is_scalar($v) || ($k[0] == '_'))
        {
            return null;
        }
        if ($this->excluded && in_array($k, $this->excluded))
        {
            return null;
        }
        if ($this->aliases && array_key_exists($k, $this->aliases))
        {
            return null;
        }
        if ($this->jsonEncode && in_array($k, $this->jsonEncode))
        {
            $v = json_encode($v, JSON_NUMERIC_CHECK);
        }
        // collapse json variables
        if ($v)
        {
            $x = json_decode($v);
            if (($x != null) && ($x != $v))
            {
                $v = json_encode($x, JSON_NUMERIC_CHECK);
            }
        }

        return $this->_setValue($k, $v);
    }

    /**
     * Serialise the aliased queries to a list of XML elements.
     *
     * @return array the XML elements produced by the aliased queries
     */
    private function _serializeAliases ()
    {
        $xml = [];

        foreach ($this->aliases as $k => $query)
        {
            $v = $this->getDatabase()->setQuery($query)->loadObjectList();

            if (count($v) == 1)
            {
                $xml[] = $this->_setValue($k, $v[0]);
            }
            elseif ($v)
            {
                $xml[] = '<' . $k . 'list>';
                foreach ($v as $val)
                {
                    $xml[] = $this->_setValue($k, $val);
                }
                $xml[] = '</' . $k . 'list>';
            }
        }

        return $xml;
    }

    protected function _setValue ($k, $v)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $kOpen = $k;
        $xml = '';
        if (is_object($v))
        {
            $x = get_object_vars($v);
            if (count($x) == 1)
            {
                $xml = $this->_setValue($k, array_shift($x));
            }
            else
            {
                foreach ($x as $k1 => $v1)
                {
                    if (substr($k1, 0, 1) != '@')
                    {
                        $xml .= $this->_setValue($k1, $v1);
                    }
                    else
                    {
                        $kOpen .= ' ' . substr($k1, 1) . '="' . $v1 . '"';
                    }
                }
                // Open root node.
                $xml = '<' . $kOpen . '>' . $xml . '</' . $k . '>';
            }
        }
        elseif (is_numeric($v))
        {
            $xml = '<' . $kOpen . '>' . $v . '</' . $k . '>';
        }
        elseif ($v != '')
        {
            $xml = '<' . $kOpen . '><![CDATA[' . self::filterXmlChars($v) . ']]></' . $k . '>';
        }
        else
        {
            $xml = '<' . $kOpen . ' />';
        }

        // Return the XML value.
        return $xml;
    }

    /**
     * Escape a string for use inside a CDATA section, replacing characters
     * that are not valid in XML 1.0 with spaces.
     *
     * @param string $v
     *          the value to filter
     *
     * @return string the filtered value
     */
    private static function filterXmlChars ($v)
    {
        $v = htmlentities($v, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8");
        $xml = '';
        $length = strlen($v);
        for ($i = 0; $i < $length; $i ++)
        {
            $current = ord($v[$i]);
            if (($current == 0x9) || ($current == 0xA) || ($current == 0xD) || (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) || (($current >= 0x10000) && ($current <= 0x10FFFF)))
            {
                $xml .= chr($current);
            }
            else
            {
                $xml .= " ";
            }
        }

        return $xml;
    }

    /**
     * Export item list to xml
     *
     * @access public
     */
    public function toXML ($mapKeysToText = false)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        return $this->_serialize();
    }

    /**
     * Method to convert the object to be imported into an array
     *
     * @param \SimpleXMLElement $record
     *          the object to be imported
     * @param array $data
     *          the array to be imported
     * @param \Joomla\Registry\Registry $params
     *          the parameters of the conversation
     *
     * @throws
     * @return void
     * @access public
     */
    public static function prepareData ($record, &$data, $params, $userId = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $nullDate = null;

        $userid = $userId ?? \Joomla\CMS\Factory::getApplication()->getIdentity()->id;

        $data = self::xml2array($record, version_compare($params->get('version', Version::$DOCVERSION), '19.2.0', 'ne'));

        $data['checked_out'] = 0;
        $data['checked_out_time'] = $nullDate;

        if (isset($data['catid']))
        {
            $data['catid'] = self::getCategoryId($data['catid'], $params->get('extension'), $params->get($params->get('extension') . 'category_default'));
        }

        // Resolve user references to local user ids.
        foreach (['created_by' => $userid, 'created_user_id' => $userid, 'modified_by' => 0, 'modified_user_id' => 0] as $field => $defaultUser)
        {
            if (isset($data[$field]))
            {
                $data[$field] = self::getUserId($data[$field], $defaultUser);
            }
        }

        if (isset($data['access']))
        {
            $data['access'] = self::getAccessId($data['access']);
        }

        // Normalise date fields.
        foreach (['publish_up', 'publish_down', 'created', 'modified'] as $field)
        {
            if (isset($data[$field]))
            {
                $data[$field] = self::fixDate($data[$field]);
            }
        }

        self::decodeLegacyFields($data, $params);
        self::prepareFieldValues($data, $params);

        if (isset($data['tag']))
        {
            $data['tags'] = (array) self::getTagId($data['tag']);
            unset($data['tag']);
        }
        elseif (isset($data['taglist']))
        {
            $data['tags'] = self::getTagId($data['taglist']['tag']);
            unset($data['taglist']);
        }

        if (isset($data['params']))
        {
            $registry = new \Joomla\Registry\Registry($data['params']);
            $data['params'] = $registry->toArray();
        }
    }

    /**
     * Decode htmlspecialchars-encoded fields in pre-19.2 XML documents.
     *
     * @param array $data
     *          the array to be imported
     * @param \Joomla\Registry\Registry $params
     *          the parameters of the conversion
     *
     * @return void
     */
    private static function decodeLegacyFields (&$data, $params)
    {
        $version = $params->get('version');
        if (($version != '15.9.0') && ($version != '12.5.0'))
        {
            return;
        }

        foreach (['title', 'introtext', 'fulltext', 'description'] as $field)
        {
            if (isset($data[$field]))
            {
                $data[$field] = htmlspecialchars_decode($data[$field]);
            }
        }
    }

    /**
     * Map the exported custom field values to com_fields data.
     *
     * @param array $data
     *          the array to be imported
     * @param \Joomla\Registry\Registry $params
     *          the parameters of the conversion
     *
     * @return void
     */
    private static function prepareFieldValues (&$data, $params)
    {
        if (!$params->get('fields', 0))
        {
            return;
        }

        if (isset($data['field']))
        {
            $data['com_fields'] = [
                $data['field']['name'] => $data['field']['value']
            ];
            unset($data['field']);
        }
        elseif (isset($data['fieldlist']['field']))
        {
            $data['com_fields'] = [];
            foreach ($data['fieldlist']['field'] as $field)
            {
                $data['com_fields'][$field['name']] = $field['value'];
            }
            unset($data['fieldlist']);
        }
    }

    /**
     * Get the article id from the article path
     *
     * @param string $article
     *          the path of the article to search for
     * @param int $defaultArticleId
     *          the id to return if the article doesn't exist
     *
     * @return int the id of the article if it exists or the default article id
     */
    public static function getArticleId ($article, $defaultArticleId = 0, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        return self::getCategorisedItemId($article, '#__content', 'com_content', $defaultArticleId, $db);
    }

    /**
     * Get the user id from the username
     *
     * @param string $username
     *          the username of the user to search for
     * @param int $defaultUserId
     *          the id to return if the user doesn't exist
     *
     * @return int the id of the user if it exists or the default user id
     */
    public static function getUserId ($username, $defaultUserId = null, $db = null, $currentUserId = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $query = $db->getQuery()->clear()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('username') . ' = ' . $db->quote($username));
        $userId = $db->setQuery($query)->loadResult();

        $currentUserId = $currentUserId ?? \Joomla\CMS\Factory::getApplication()->getIdentity()->id;
        $userId = $userId ?: ($defaultUserId ?: $currentUserId);

        return $userId;
    }

    /**
     *
     * @param string $usergroup
     * @param boolean $import
     *
     *
     * @return boolean|mixed|stdClass|void|NULL
     * @return mixed The usergroup id on success, boolean false on failure.
     */
    public static function getUsergroupId ($usergroup, $import = true, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        if (empty($usergroup))
        {
            $usergroupId = \Joomla\CMS\Component\ComponentHelper::getParams('com_users')->get('new_usertype');
        }
        elseif (!is_numeric($usergroup))
        {
            $db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $query = $db->getQuery()->clear()
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__j2xml_usergroups', 'g'))
                ->where($db->quoteName('title') . ' = ' . $db->quote($usergroup));

            $usergroupId = $db->setQuery($query)->loadResult();

            if ($import && !$usergroupId)
            {
                // import usergroup tree if it doesn't exists
                $groups = json_decode($usergroup);
                $g = [];
                $usergroupId = 0;
                $parentId = 0;
                for ($j = 0; $j < count($groups); $j ++)
                {
                    $g[] = $groups[$j];
                    $usergroup = json_encode($g, JSON_NUMERIC_CHECK);
                    $query = $db->getQuery()->clear()
                        ->select($db->quoteName('id'))
                        ->from($db->quoteName('#__usergroups'))
                        ->where($db->quoteName('title') . ' = ' . $db->quote($groups[$j]))
                        ->where($db->quoteName('parent_id') . ' = ' . $parentId);
                    $usergroupId = $db->setQuery($query)->loadResult();
                    if (!$usergroupId)
                    {
                        $u = new \Joomla\CMS\Table\Usergroup($db);
                        $u->save([
                            'title' => $groups[$j],
                            'parent_id' => $parentId
                        ]);
                        $usergroupId = $u->id;
                        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_USERGROUP_IMPORTED', $groups[$j]), \Joomla\CMS\Log\Log::INFO, 'lib_j2xml'));
                    }
                    else
                    {
                        $parentId = $usergroupId;
                    }
                }
            }
        }
        elseif ($usergroup > 0)
        {
            $usergroupId = $usergroup;
        }
        else
        {
            $usergroupId = ComponentHelper::getParams('com_users')->get('new_usertype');
        }

        return $usergroupId;
    }

    public static function getAccessId ($access, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        if (is_numeric($access))
        {
            $accessId = $access;
        }
        else
        {
            $db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $query = $db->getQuery()->clear()
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__viewlevels'))
                ->where($db->quoteName('title') . ' = ' . $db->quote($access));
            $accessId = $db->setQuery($query)->loadResult();
        }
        if (!$accessId)
        {
            $accessId = 3;
        }

        return $accessId;
    }

    /**
     * Export the images referenced by img tags in the given HTML.
     *
     * @param string $text
     *          the HTML to scan for images
     * @param \SimpleXMLElement $xml
     *          xml
     * @param array $options
     *          export options
     *
     * @return void
     */
    protected static function exportImagesFromText ($text, &$xml, $options)
    {
        preg_match_all(self::IMAGE_MATCH_STRING, $text, $matches, PREG_PATTERN_ORDER);
        foreach ($matches[1] as $image)
        {
            if ($image)
            {
                Image::export($image, $xml, $options);
            }
        }
    }

    /**
     * Advance a table's auto-increment/sequence past the given id so that
     * imports keeping the source ids do not collide with new rows.
     *
     * @param \Joomla\Database\DatabaseInterface $db
     *          the database connector
     * @param int $value
     *          the next auto-increment value
     * @param string $table
     *          the table name
     * @param string $sequence
     *          the PostgreSQL sequence name
     *
     * @return void
     */
    protected static function resetAutoIncrement ($db, $value, $table, $sequence)
    {
        if ($db->getServerType() === 'postgresql')
        {
            $query = 'ALTER SEQUENCE ' . $db->quoteName($sequence) . ' RESTART WITH ' . $value;
        }
        else
        {
            $query = 'ALTER TABLE ' . $db->quoteName($table) . ' AUTO_INCREMENT = ' . $value;
        }
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        $db->setQuery($query)->execute();
    }

    /**
     * Get the category id from the category path
     *
     * @param mixed $category
     *          string: the path of the category to search for
     *          int: the id of the category
     * @param string $extension
     * @param int $defaultCategoryId
     *          the id to return if the category doesn't exist
     *
     * @return int the id of the category if it exists or the default category id
     */
    public static function getCategoryId ($category, $extension, $defaultCategoryId = 0, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        if (!is_numeric($category))
        {
            $query = $db->getQuery()->clear()
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__categories'))
                ->where($db->quoteName('path') . ' = ' . $db->quote($category))
                ->where($db->quoteName('extension') . ' = ' . $db->quote($extension));
            $categoryId = $db->setQuery($query)->loadResult();
        }
        else
        {
            $query = $db->getQuery()->clear()
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__categories'))
                ->where($db->quoteName('id') . ' = ' . $category)
                ->where($db->quoteName('extension') . ' = ' . $db->quote($extension));
            $categoryId = $db->setQuery($query)->loadResult();
        }
        if (!$categoryId)
        {
            if ($defaultCategoryId)
            {
                $query = $db->getQuery()->clear()
                    ->select($db->quoteName('id'))
                    ->from($db->quoteName('#__categories'))
                    ->where($db->quoteName('id') . ' = ' . $defaultCategoryId)
                    ->where($db->quoteName('extension') . ' = ' . $db->quote($extension));
                $categoryId = $db->setQuery($query)->loadResult();
            }
            if (!$categoryId)
            {
                $query = $db->getQuery()->clear()
                    ->select('MIN(' . $db->quoteName('id') . ')')
                    ->from($db->quoteName('#__categories'))
                    ->where($db->quoteName('extension') . ' = ' . $db->quote($extension));
                $categoryId = $db->setQuery($query)->loadResult();
            }
        }

        return $categoryId;
    }

    /**
     * get tag id from tag path
     *
     * @param string|array $tag
     *          tag path
     *
     * @return mixed An array with tag ids, a single id or false if an error
     *       occurs
     *
     * @since 14.8.240
     */
    public static function getTagId ($tag, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        try
        {
            if (is_array($tag))
            {
                $tags = array_unique($tag);
                $tagIdExpression = $db->getServerType() === 'postgresql'
                    ? "CASE WHEN b.id IS NOT NULL THEN CAST(b.id AS TEXT) ELSE CONCAT('#new#', a.path) END"
                    : "CASE WHEN b.id IS NOT NULL THEN b.id ELSE CONCAT('#new#', a.path) END";
                $query = 'SELECT ' . $tagIdExpression . ' FROM (SELECT ' .
                    $db->quote(array_shift($tags)) . ' as path';
                foreach ($tags as $tag)
                {
                    $query .= ' UNION ALL SELECT ' . $db->quote($tag);
                }
                $query .= ') a LEFT JOIN #__tags b on a.path = b.path';
                $tagId = $db->setQuery($query)->loadColumn();
            }
            else
            {
                $query = $db->getQuery()->clear()
                    ->select($db->quoteName('id'))
                    ->from($db->quoteName('#__tags'))
                    ->where($db->quoteName('path') . ' = ' . $db->quote($tag));
                $tagId = $db->setQuery($query)->loadResult();
            }
        }
        catch (\Exception $ex)
        {
            $tagId = false;
        }

        return $tagId;
    }

    /**
     * fix the datetime
     *
     * @param string $date
     *          the datetime to be fixed
     *
     * @return string the fixed datetime
     *
     * @since 18.8.301
     */
    protected static function fixDate ($date)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        if (empty($date) || ($date == '0000-00-00 00:00:00') || ($date == '1970-01-01 00:00:00'))
        {
            $date = null;
        }
        else
        {
            $d = new \Joomla\CMS\Date\Date($date);
            $date = $d->toSQL(false);
        }

        return $date;
    }

    /**
     * function xml2array
     *
     * @params mixed $xmlObject
     * @params array $out
     *
     * @since 19.2.320
     */
    private static function xml2array ($xmlObject, $htmlEntityDecode = false, $out = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        if (is_object($xmlObject))
        {
            return self::node2array($xmlObject, $htmlEntityDecode, $out);
        }

        if (is_array($xmlObject))
        {
            foreach ($xmlObject as $index => $node)
            {
                $out[$index] = self::xml2array($node, $htmlEntityDecode);
            }

            return $out;
        }

        if (is_string($xmlObject))
        {
            return self::decodeXmlString($xmlObject, $htmlEntityDecode);
        }

        $out = $xmlObject;
        if ($htmlEntityDecode)
        {
            $out = html_entity_decode($out, ENT_QUOTES, 'UTF-8');
        }

        return $out;
    }

    /**
     * Convert an XML element to an array, collecting attributes and
     * either the text value or the child elements.
     *
     * @param \SimpleXMLElement $xmlObject
     *          the element to convert
     * @param boolean $htmlEntityDecode
     *          decode html entities
     * @param array|null $out
     *          the array being built
     *
     * @return mixed the converted value
     */
    private static function node2array ($xmlObject, $htmlEntityDecode, $out)
    {
        $a = $xmlObject->attributes();
        if ($a)
        {
            foreach ($a as $k => $v)
            {
                $out[$k] = (string) $v;
            }
        }

        if (count($xmlObject->children()) > 0)
        {
            foreach ((array) $xmlObject as $index => $node)
            {
                $out[$index] = self::xml2array($node, $htmlEntityDecode);
            }

            return $out;
        }

        if (trim($xmlObject))
        {
            $v = self::decodeXmlString($xmlObject, $htmlEntityDecode);
            if ($a)
            {
                $out['value'] = $v;
            }
            else
            {
                $out = $v;
            }
        }

        return $out;
    }

    /**
     * Decode a text node value: convert %uXXXX escapes to XML entities
     * and optionally decode html entities.
     *
     * @param string $v
     *          the value to decode
     * @param boolean $htmlEntityDecode
     *          decode html entities
     *
     * @return string the decoded value
     */
    private static function decodeXmlString ($v, $htmlEntityDecode)
    {
        $v = preg_replace('/%u([0-9A-F]+)/', '&#x$1;', trim($v));
        if ($htmlEntityDecode)
        {
            $v = html_entity_decode($v, ENT_QUOTES, 'UTF-8');
        }

        return $v;
    }

    /**
     * Import data
     *
     * @param \SimpleXMLElement $xml
     *          xml
     * @param \Joomla\Registry\Registry $params
     *          @option int 'fields' 0: No | 1: Yes, if not exists | 2: Yes,
     *          overwrite if exists
     *          @option string 'context'
     *
     * @throws
     * @return void
     * @access public
     *
     * @since 19.2.323
     */
    public static function import ($xml, &$params, $db = null, $userId = null)
    {
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
     * @since 19.2.323
     */
    public static function export ($id, &$xml, $options, $db = null)
    {
    }

    /**
     * Overloaded bind function.
     *
     * @param array $array
     *          Named array.
     * @param mixed $ignore
     *          An optional array or space separated list of properties to
     *          ignore while binding.
     *
     * @return mixed Null if operation was satisfactory, otherwise returns an
     *       error
     *
     * @see Table::bind()
     * @since 19.2.327
     */
    public function bind ($array, $ignore = '')
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        if (isset($array['params']) && is_array($array['params']))
        {
            $registry = new \Joomla\Registry\Registry($array['params']);
            $array['params'] = (string) $registry;
        }

        // Bind the rules.
        if (isset($array['rules']) && is_array($array['rules']))
        {
            $rules = new Rules($array['rules']);
            $this->setRules($rules);
        }

        return parent::bind($array, $ignore);
    }

    /**
     * Get the menu id from the menu path
     *
     * @param string $menu
     *          the path of the menu to search for
     * @param int $defaultMenuId
     *          the id to return if the menu doesn't exist
     *
     * @return int the id of the menu if it exists or the default menu id
     */
    public static function getMenuId ($menu, $defaultMenuId = 0, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        if (is_numeric($menu))
        {
            $menuId = $menu;
        }
        else
        {
            $db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $query = $db->getQuery()->clear();
            $path = $query->concatenate([$db->quoteName('menutype'), $db->quoteName('path')], '/');
            $query->select($db->quoteName('id'))
                ->from($db->quoteName('#__menu'))
                ->where($path . ' = ' . $db->quote($menu));
            $menuId = $db->setQuery($query)->loadResult();
        }
        if (!$menuId)
        {
            $menuId = $defaultMenuId;
        }

        return $menuId;
    }

    /**
     * Set the associations.
     *
     * @param   integer  $id            The primary key value.
     * @param   string   $language      The language tag.
     * @param   array    $associations  The associated items.
     * @param   string   $context       The associations context.
     */
    protected static function setAssociations($id, $language, $associations, $context, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($id, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($language, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(print_r($associations, true), \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($context, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        if (empty($associations))
        {
            return;
        }

        $db      = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $isEnabled = \Joomla\CMS\Language\Associations::isEnabled();

        if ($isEnabled)
        {
            // Unset any invalid associations
            ArrayHelper::toInteger($associations);

            // Unset any invalid associations
            foreach ($associations as $tag => $itemId)
            {
                if (!$itemId)
                {
                    unset($associations[$tag]);
                }
            }

            // Show a warning if the item isn't assigned to a language but we have associations.
            if ($associations && $language === '*')
            {
                $app = \Joomla\CMS\Factory::getApplication();
                $app->enqueueMessage(
                    \Joomla\CMS\Language\Text::_(strtoupper(strtok($context, '.')) . '_ERROR_ALL_LANGUAGE_ASSOCIATED'),
                    'warning'
                    );
            }

            // Get associationskey for edited item
            $query = $db->getQuery()->clear()
                ->select($db->quoteName('key'))
                ->from($db->quoteName('#__associations'))
                ->where($db->quoteName('context') . ' = ' . $db->quote($context))
                ->where($db->quoteName('id') . ' = ' . (int) $id);
            $db->setQuery($query);
            $old_key = $db->loadResult();

            // Deleting old associations for the associated items
            $query = $db->getQuery()->clear()
                ->delete($db->quoteName('#__associations'))
                ->where($db->quoteName('context') . ' = ' . $db->quote($context));

            if ($associations)
            {
                $query->where('(' . $db->quoteName('id') . ' IN (' . implode(',', $associations) . ') OR '
                        . $db->quoteName('key') . ' = ' . $db->quote($old_key) . ')');
            }
            else
            {
                $query->where($db->quoteName('key') . ' = ' . $db->quote($old_key));
            }

            $db->setQuery($query);
            $db->execute();

            // Adding self to the association
            if ($language !== '*')
            {
                $associations[$language] = (int) $id;
            }

            if (count($associations) > 1)
            {
                // Adding new association for these items
                $key   = md5(json_encode($associations)); // nosemgrep: weak-crypto — non-cryptographic lookup key for #__associations, matches Joomla core // NOSONAR
                $query = $db->getQuery()->clear()
                    ->insert('#__associations');

                foreach ($associations as $itemId)
                {
                    $query->values(((int) $itemId) . ',' . $db->quote($context) . ',' . $db->quote($key));
                }
                $db->setQuery($query);
                $db->execute();
            }
        }
    }

    /**
     * Get the contact id from the contact path
     *
     * @param string $contact
     *          the path of the contact to search for
     * @param int $defaultContactId
     *          the id to return if the contact doesn't exist
     *
     * @return int the id of the contact if it exists or the default contact id
     *
     * @since 20.5.349
     */
    public static function getContactId ($contact, $defaultContactId = 0, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($contact, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        return self::getCategorisedItemId($contact, '#__contact_details', 'com_contact', $defaultContactId, $db);
    }

    /**
     * Get the weblink id from the weblink path
     *
     * @param string $weblink
     *          the path of the weblink to search for
     * @param int $defaultWeblinkId
     *          the id to return if the weblink doesn't exist
     *
     * @return int the id of the weblink if it exists or the default weblink id
     *
     * @since 20.5.349
     */
    public static function getWeblinkId ($weblink, $defaultWeblinkId = 0, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        return self::getCategorisedItemId($weblink, '#__weblinks', 'com_weblinks', $defaultWeblinkId, $db);
    }
}
