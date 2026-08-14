<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       1.5.1
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2023 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
namespace eshiol\J2xml\Table;
defined('JPATH_PLATFORM') or define('JPATH_PLATFORM', JPATH_LIBRARIES);

use eshiol\J2xml\Table\Category;
use eshiol\J2xml\Table\Field;
use eshiol\J2xml\Table\Image;
use eshiol\J2xml\Table\Table;
use eshiol\J2xml\Table\Tag;
use eshiol\J2xml\Table\User;
use eshiol\J2xml\Table\Viewlevel;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\SiteRouter;
use Joomla\CMS\Version;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Utilities\ArrayHelper;

/**
 *
 * Content Table
 *
 */
class Content extends Table
{

	/**
	 * Constructor
	 *
	 * @param \Joomla\Database\DatabaseDriver $db
	 *			A database connector object
	 *
	 * @since 1.5.1
	 */
	public function __construct (\Joomla\Database\DatabaseDriver $db)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

		parent::__construct('#__content', 'id', $db);

	/**
	 * $version = new \Joomla\CMS\Version();
	 * if ($version->isCompatible('3.4'))
	 * {
	 * // Set the alias since the column is called state
	 * $this->setColumnAlias('published', 'state');
	 * }
	 */
	}

	/**
	 * Export item list to xml
	 *
	 * @access public
	 */
	function toXML ($mapKeysToText = false)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

		$this->_excluded = array_merge($this->_excluded, array(
				'sectionid',
				'mask',
				'title_alias',
				'ordering'
		));

		// $this->_aliases['featured'] = 'SELECT IFNULL(f.ordering,0) FROM
		// #__content_frontpage f RIGHT JOIN #__content a ON f.content_id = a.id
		// WHERE a.id = ' . (int)$this->id;
		$this->_aliases['featured'] = (string) $this->_db->getQuery(true)
			->select('COALESCE(' . $this->_db->quoteName('f.ordering') . ', 0)')
			->from($this->_db->quoteName('#__content_frontpage', 'f'))
			->join('RIGHT',
				$this->_db->quoteName('#__content', 'a') . ' ON ' . $this->_db->quoteName('f.content_id') . ' = ' . $this->_db->quoteName('a.id'))
			->where($this->_db->quoteName('a.id') . ' = ' . (int) $this->id);

		$this->_aliases['featured_up'] = (string) $this->_db->getQuery(true)
			->select($this->_db->quoteName('f.featured_up'))
			->from($this->_db->quoteName('#__content_frontpage', 'f'))
			->join('RIGHT',
				$this->_db->quoteName('#__content', 'a') . ' ON ' . $this->_db->quoteName('f.content_id') . ' = ' . $this->_db->quoteName('a.id'))
			->where($this->_db->quoteName('a.id') . ' = ' . (int) $this->id);

		$this->_aliases['featured_down'] = (string) $this->_db->getQuery(true)
			->select($this->_db->quoteName('f.featured_down'))
			->from($this->_db->quoteName('#__content_frontpage', 'f'))
			->join('RIGHT',
				$this->_db->quoteName('#__content', 'a') . ' ON ' . $this->_db->quoteName('f.content_id') . ' = ' . $this->_db->quoteName('a.id'))
			->where($this->_db->quoteName('a.id') . ' = ' . (int) $this->id);

		// $this->_aliases['rating_sum'] = 'SELECT IFNULL(rating_sum,0) FROM
		// #__content_rating f RIGHT JOIN #__content a ON f.content_id = a.id
		// WHERE a.id = ' . (int)$this->id;
		$this->_aliases['rating_sum'] = (string) $this->_db->getQuery(true)
			->select('COALESCE(' . $this->_db->quoteName('rating_sum') . ', 0)')
			->from($this->_db->quoteName('#__content_rating', 'f'))
			->join('RIGHT',
				$this->_db->quoteName('#__content', 'a') . ' ON ' . $this->_db->quoteName('f.content_id') . ' = ' . $this->_db->quoteName('a.id'))
			->where($this->_db->quoteName('a.id') . ' = ' . (int) $this->id);

		// $this->_aliases['rating_count'] = 'SELECT IFNULL(rating_count,0) FROM
		// #__content_rating f RIGHT JOIN #__content a ON f.content_id = a.id
		// WHERE a.id = ' . (int)$this->id;
		$this->_aliases['rating_count'] = (string) $this->_db->getQuery(true)
			->select('COALESCE(' . $this->_db->quoteName('rating_count') . ', 0)')
			->from($this->_db->quoteName('#__content_rating', 'f'))
			->join('RIGHT',
				$this->_db->quoteName('#__content', 'a') . ' ON ' . $this->_db->quoteName('f.content_id') . ' = ' . $this->_db->quoteName('a.id'))
			->where($this->_db->quoteName('a.id') . ' = ' . (int) $this->id);

		$slug = $this->alias ? ($this->id . ':' . $this->alias) : $this->id;

		// We need to make sure we are always using the site router, even if the language plugin is executed in admin app.
		$router = CMSApplication::getRouter('site');
		$url = $router->build(RouteHelper::getArticleRoute($slug, $this->catid, $this->language));

		$canonical = str_replace(\Joomla\CMS\Uri\Uri::base(true) . '/', \Joomla\CMS\Uri\Uri::root(), $url);
		// $this->_aliases['canonical'] = 'SELECT \'' . $canonical . '\' FROM
		// DUAL';
		$serverType = $this->_db->getServerType();
		if ($serverType === 'sqlserver')
		{
			$this->_aliases['canonical'] = (string) $this->_db->getQuery(true)
				->select($this->_db->quote($canonical))
				->from($this->_db->quoteName('DUAL'));
		}
		else
		{
			$this->_aliases['canonical'] = (string) $this->_db->getQuery(true)->select($this->_db->quote($canonical));
		}

		// $this->_aliases['tag']='SELECT t.path FROM #__tags t,
		// #__contentitem_tag_map m WHERE type_alias = "com_content.article"
		// AND
		// t.id = m.tag_id AND m.content_item_id = '. (int)$this->id;
		$this->_aliases['tag'] = (string) $this->_db->getQuery(true)
			->select($this->_db->quoteName('t.path'))
			->from($this->_db->quoteName('#__tags', 't'))
			->from($this->_db->quoteName('#__contentitem_tag_map', 'm'))
			->where($this->_db->quoteName('type_alias') . ' = ' . $this->_db->quote('com_content.article'))
			->where($this->_db->quoteName('t.id') . ' = ' . $this->_db->quoteName('m.tag_id'))
			->where($this->_db->quoteName('m.content_item_id') . ' = ' . $this->_db->quote((string) $this->id));

		// $this->_aliases['field'] = 'SELECT f.name, v.value FROM
		// #__fields_values v, #__fields f WHERE f.id = v.field_id AND
		// v.item_id = '. (int)$this->id;
		$query = $this->_db->getQuery(true)
				->select($this->_db->quoteName('f.name'))
				->select($this->_db->quoteName('v.value'))
				->from($this->_db->quoteName('#__fields_values', 'v'))
				->from($this->_db->quoteName('#__fields', 'f'))
				->where($this->_db->quoteName('f.id') . ' = ' . $this->_db->quoteName('v.field_id'))
				->where($this->_db->quoteName('v.item_id') . ' = ' . $this->_db->quote((string) $this->id));
		$query->where($this->_db->quoteName('f.type') . ' <> ' . $this->_db->quote('subform'));
		$this->_aliases['field'] = (string) $query;

		$query = $this->_db->getQuery(true)
			->select($this->_db->quoteName('f.id'))
			->select($this->_db->quoteName('f.name'))
			->from($this->_db->quoteName('#__fields', 'f'));
		$fields = array();
		foreach ($this->_db->setQuery($query)->loadObjectList() as $field)
		{
			$fields['field' . $field->id] = $field->name;
		}

		$query = $this->_db->getQuery(true)
			->select($this->_db->quoteName('f.name'))
			->select($this->_db->quoteName('v.value'))
			->from($this->_db->quoteName('#__fields_values', 'v'))
			->from($this->_db->quoteName('#__fields', 'f'))
			->where($this->_db->quoteName('f.type') . ' = ' . $this->_db->quote('subform'))
			->where($this->_db->quoteName('f.id') . ' = ' . $this->_db->quoteName('v.field_id'))
			->where($this->_db->quoteName('v.item_id') . ' = ' . $this->_db->quote((string) $this->id));
		$fieldValues = $this->_db->setQuery($query)->loadObjectList();
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

			$query = $this->_db->getQuery(true)
				->select($this->_db->quote($field->name))
				->select($this->_db->quote($subformValue));
			if ($serverType === 'sqlserver')
			{
				$query->from($this->_db->quoteName('DUAL'));
			}
			$this->_aliases['field'] .= ' UNION ' . (string) $query;
		}

		$query = $this->_db->getQuery(true);
		$this->_aliases['association'] = (string) $query
			->select($query->concatenate(array($this->_db->quoteName('cc.path'), $this->_db->quoteName('c.alias')), '/'))
			->from($this->_db->quoteName('#__associations', 'asso1'))
			->join('INNER', $this->_db->quoteName('#__associations', 'asso2') . ' ON ' . $this->_db->quoteName('asso1.key') . ' = ' . $this->_db->quoteName('asso2.key'))
			->join('INNER', $this->_db->quoteName('#__content', 'c') . ' ON ' . $this->_db->quoteName('asso2.id') . ' = ' . $this->_db->quoteName('c.id'))
			->join('INNER', $this->_db->quoteName('#__categories', 'cc') . ' ON ' . $this->_db->quoteName('c.catid') . ' = ' . $this->_db->quoteName('cc.id'))
			->where(array(
				$this->_db->quoteName('asso1.id') . ' = ' . (int) $this->id,
				$this->_db->quoteName('asso1.context') . ' = ' . $this->_db->quote('com_content.item'),
				$this->_db->quoteName('asso2.id') . ' <> ' . (int) $this->id));

		return parent::toXML($mapKeysToText);
	}

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *			xml
	 * @param \JRegistry $params
	 *			@option int 'content' 0: No (default); 1: Yes, if not exists;
	 *			2: Yes, overwrite if exists
	 *			@option int 'com_content_category_default'
	 *			@option int 'content_category_forceto'
	 *			@option string 'context'
	 *
	 * @throws
	 * @return void
	 * @access public
	 *
	 * @since 18.8.301
	 */
	public static function import ($xml, &$params)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

		$import_content = $params->get('content', 0);
		if ($import_content == 0)
		{
			return;
		}

		//$params->def('content_category_default', self::getCategoryId('uncategorised', 'com_content'));
		$force_to = $params->get('content_category_forceto');
		$context = $params->get('context', 'com_content.article');
		$db = \Joomla\CMS\Factory::getDbo();
		$nullDate = $db->getNullDate();
		$userid = \Joomla\CMS\Factory::getUser()->id;
		\Joomla\CMS\Plugin\PluginHelper::importPlugin('content');

		$params->set('extension', 'com_content');
		$import_categories = $params->get('categories');
		if ($import_categories)
		{
			Category::import($xml, $params);
		}

		$keep_id        = $params->get('keep_id', 0);
		$keep_frontpage = $params->get('keep_data', 0);
		$keep_rating    = $params->get('keep_data', 0);
		$keep_data      = $params->get('keep_data', 0);

		$mvcFactory = Factory::getApplication()->bootComponent('com_content')->getMVCFactory();

		foreach ($xml->xpath("//j2xml/content[not(name = '')]") as $record)
		{
			self::prepareData($record, $data, $params);

			$id = $data['id'];
			if ($force_to)
			{
				$data['catid'] = $force_to;
			}

			$content = $db->setQuery(
				$query = $db->getQuery(true)
					->select(
						array(
							$db->quoteName('id'),
							$db->quoteName('title'),
							'GREATEST(' . $db->quoteName('created') . ',' . $db->quoteName('modified') . ') ' . $db->quoteName('modified')
						))
					->from($db->quoteName('#__content'))
					->where($db->quoteName('catid') . ' = ' . $db->quote($data['catid']))
					->where($db->quoteName('alias') . ' = ' . $db->quote($data['alias'])))
				->loadObject();

			$table = $mvcFactory->createModel('Article', 'Administrator', ['ignore_request' => true]);

			if ((($import_content == 1) && $content) || (($import_content == 3) && $content && $content->modified >= $data['modified']))
			{
				if ($id == $content->id)
				{
					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_EXISTS', $data['title'], $id), \Joomla\CMS\Log\Log::NOTICE, 'lib_j2xml'));
				}
				elseif ($keep_id)
				{
					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED', $data['title'], $id, \Joomla\CMS\Language\Text::_('JLIB_DATABASE_ERROR_ARTICLE_UNIQUE_ALIAS')), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
				}
				else
				{
					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_EXISTS', $data['title'], $id . '->' . $content->id), \Joomla\CMS\Log\Log::NOTICE, 'lib_j2xml'));
				}
				continue;
			}
			elseif (($import_content >= 2) && $content && $keep_id && ($id != $content->id))
			{
				\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED', $data['title'], $id, \Joomla\CMS\Language\Text::_('JLIB_DATABASE_ERROR_ARTICLE_UNIQUE_ALIAS')), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
				continue;
			}
			else
			{
				if (!$content)
				{ // new article
					$isNew = true;
					$data['id'] = null;
				}
				else
				{ // article already exists
					$isNew = false;
					$data['id'] = $content->id;
				}

				$results = [];

				if (!in_array(false, $results, true))
				{
					if ($table->save($data))
					{
						// fix hits
						$table->save($data);

						$item = $table->getItem();
						if ($keep_data == 1)
						{
							$sets = [];
							if (isset($data['modified']))
							{
								$sets[] = $db->quoteName('modified') . ' = ' . $db->quote($data['modified']);
							}
							if (isset($data['modified_by']))
							{
								$sets[] = $db->quoteName('modified_by') . ' = ' . $data['modified_by'];
							}
							if (count($sets))
							{
								$query = $db->getQuery(true)
									->update($db->quoteName('#__content'))
									->where($db->quoteName('id') . ' = ' . $item->id);
								foreach ($sets as $set)
								{
									$query->set($set);
								}
								$db->setQuery($query)->execute();	
							}
						}

						if ($keep_frontpage == 0)
						{
							$query = "DELETE FROM #__content_frontpage WHERE content_id = " . $item->id;
						}
						elseif ($data['featured'] == 0)
						{
							$query = "DELETE FROM #__content_frontpage WHERE content_id = " . $item->id;
						}
						else
						{
							$query = 'INSERT IGNORE INTO `#__content_frontpage`' . ' SET content_id = ' . $item->id 
								. ',' . ' ordering = ' . $data['ordering'];
							if (!is_null($data['featured_up']))
							{
								$query .= ',' . ' featured_up = ' . $db->quote($data['featured_up']);
							}
							if (!is_null($data['featured_down']))
							{
								$query .= ',' . ' featured_down = ' . $db->quote($data['featured_down']);
							}
						}
						$db->setQuery($query)->execute();

						if (($keep_rating == 0) || (!isset($data['rating_count'])) || ($data['rating_count'] == 0))
						{
							$query = "DELETE FROM `#__content_rating` WHERE `content_id`=" . $item->id;
							$db->setQuery($query)->execute();
						}
						else
						{
							$rating = new \stdClass();
							$rating->content_id = $item->id;
							$rating->rating_count = $data['rating_count'];
							$rating->rating_sum = $data['rating_sum'];
							$rating->lastip = $_SERVER['REMOTE_ADDR'];
							try
							{
								$db->insertObject('#__content_rating', $rating);
							}
							catch (\Exception $ex)
							{
								$db->updateObject('#__content_rating', $rating, 'content_id');
							}
						}

						if (($keep_id == 1) && ($id > 1))
						{
							try
							{
								self::changeId($item->id, $id);

								$item->id = $id;
							}
							catch (\Exception $ex)
							{
								\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_ID_PRESENT', $item->title, $id, $item->id), \Joomla\CMS\Log\Log::WARNING, 'lib_j2xml'));
								continue;
							}
						}

						if ($id != $item->id)
						{
							\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_IMPORTED', $item->title, $id, $item->id), \Joomla\CMS\Log\Log::INFO,	'lib_j2xml'));
						}
						else
						{
							\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_UPDATED', $item->title, $id), \Joomla\CMS\Log\Log::INFO, 'lib_j2xml'));
						}
					}
					else
					{
						\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED', $data['title'], $id, $table->getError()), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
					}
				}
				else
				{
					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED', $data['title'], $id, $table->getError()), \Joomla\CMS\Log\Log::NOTICE, 'lib_j2xml'));
				}
			}
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Table::prepareData()
	 *
	 * @since 18.8.301
	 */
	public static function prepareData ($record, &$data, $params)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

		$db      = \Joomla\CMS\Factory::getDbo();

		$params->set('extension', 'com_content');
		parent::prepareData($record, $data, $params);

		if (empty($data['id']))
		{
			$data['id'] = 0;
		}

		if (empty($data['alias']) || (trim($data['alias']) == ''))
		{
			$data['alias'] = htmlspecialchars_decode($data['title'], ENT_QUOTES);	
		}
		$data['alias'] = \Joomla\CMS\Filter\OutputFilter::stringURLSafe($data['alias']);
		if (trim(str_replace('-', '', $data['alias'])) == '') {
			$data['alias'] = \Joomla\CMS\Factory::getDate()->format('Y-m-d-H-i-s');
		}

		if (!isset($data['fulltext']))
		{
			$data['fulltext'] = '';
		}
		if (!isset($data['metakey']))
		{
			$data['metakey'] = '';
		}
		if (!isset($data['metadesc']))
		{
			$data['metadesc'] = '';
		}
		if (!isset($data['created_by']))
		{
			$data['created_by'] = \Joomla\CMS\Factory::getUser()->id;
		}
		if (!isset($data['language']))
		{
			$data['language'] = '*';
		}

		// if (!$version->isCompatible('3.4') && isset($data['published']))
		if (isset($data['published']))
		{
			// Set the column since its name is changed from published to state
			$data['state'] = $data['published'];
			unset($data['published']);
		}

		$data['featured'] = (int) ($data['featured'] > 0);
		if ($params->get('keep_frontpage') == 0)
		{
			$data['ordering'] = 0;
		}
		elseif (!isset($data['ordering']))
		{
			$data['ordering'] = $data['featured'];
		}

		if (!isset($data['catid']))
		{
			$data['catid'] = $params->get('com_content_category_default');
		}

		if (empty($data['associations']))
		{
			$data['associations'] = array();
		}

		if (isset($data['associationlist']))
		{
			foreach ($data['associationlist']['association'] as $association)
			{
				$id = self::getArticleId($association);
				if ($id)
				{
					$tag = $db->setQuery($db->getQuery(true)
						->select($db->quoteName('language'))
						->from($db->quoteName('#__content'))
						->where($db->quoteName('id') . ' = ' . $id))
						->loadResult();
					if ($tag !== '*')
					{
						$data['associations'][$tag] = $id;
					}
				}
			}
			unset($data['associationlist']);
		}
		elseif (isset($data['association']))
		{
			$id = self::getArticleId($data['association']);
			if ($id)
			{
				$tag = $db->setQuery($db->getQuery(true)
					->select($db->quoteName('language'))
					->from($db->quoteName('#__content'))
					->where($db->quoteName('id') . ' = ' . $id))
					->loadResult();
				if ($tag !== '*')
				{
					$data['associations'][$tag] = $id;
				}
			}
			unset($data['association']);
		}

		if (!isset($data['introtext']))
		{
			$data['introtext'] = '';
		}
	}

	/**
	 * Export data
	 *
	 * @param int $id
	 *			the id of the item to be exported
	 * @param \SimpleXMLElement $xml
	 *			xml
	 * @param array $options
	 *
	 * @throws
	 * @return void
	 * @access public
	 *
	 * @since 18.8.310
	 */
	public static function export ($id, &$xml, $options)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

		if ($xml->xpath("//j2xml/content/id[text() = '" . $id . "']"))
		{
			return;
		}

		$db = \Joomla\CMS\Factory::getDbo();
		$item = new Content($db);
		if (!$item->load($id))
		{
			return;
		}

		$params = new \Joomla\Registry\Registry($options);
		\Joomla\CMS\Plugin\PluginHelper::importPlugin('j2xml');

		$results = \Joomla\CMS\Factory::getApplication()->triggerEvent('onJ2xmlBeforeExportContent', array(
			'lib_j2xml.article',
			&$item,
			$params
		));

		if ($item->access > 6)
		{
			Viewlevel::export($item->access, $xml, $options);
		}

		if (isset($options['categories']) && $options['categories'] && ($item->catid > 0))
		{
			Category::export($item->catid, $xml, $options);
		}

		if (isset($options['tags']) && $options['tags'])
		{
			$htags = new \Joomla\CMS\Helper\TagsHelper();
			$itemtags = $htags->getItemTags('com_content.article', $id);
			foreach ($itemtags as $itemtag)
			{
				Tag::export($itemtag->tag_id, $xml, $options);
			}
		}

		if (isset($options['fields']) && $options['fields'])
		{
			// load subform fields
			$query = $db->getQuery(true)
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

			$query = $db->getQuery(true)
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

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();

		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		if (isset($options['users']) && $options['users'])
		{
			if ($item->created_by)
			{
				User::export($item->created_by, $xml, $options);
			}
			if ($item->modified_by)
			{
				User::export($item->modified_by, $xml, $options);
			}
		}

		if (isset($options['images']) && $options['images'])
		{
			$img = null;
			$text = $item->introtext . $item->fulltext;
			$_image = preg_match_all(self::IMAGE_MATCH_STRING, $text, $matches, PREG_PATTERN_ORDER);
			if (count($matches[1]) > 0)
			{
				for ($i = 0; $i < count($matches[1]); $i ++)
				{
					if ($_image = $matches[1][$i])
					{
						Image::export($_image, $xml, $options);
					}
				}
			}

			if ($imgs = json_decode($item->images))
			{
				if (isset($imgs->image_fulltext))
				{
					Image::export($imgs->image_fulltext, $xml, $options);
				}

				if (isset($imgs->image_intro))
				{
					Image::export($imgs->image_intro, $xml, $options);
				}
			}

		foreach($db->setQuery($db->getQuery(true)
				->select($db->quoteName('v.value'))
				->from($db->quoteName('#__fields_values', 'v'))
				->from($db->quoteName('#__fields', 'f'))
				->where($db->quoteName('f.id') . ' = ' . $db->quoteName('v.field_id'))
				->where($db->quoteName('v.item_id') . ' = ' . $db->quote((string) $id))
				->where($db->quoteName('f.type') . ' = ' . $db->quote('media')))
				->loadColumn() as $_image)
			{
				Image::export($_image, $xml, $options);
			}

			foreach($db->setQuery($db->getQuery(true)
				->select($db->quoteName('f.fieldparams'))
				->select($db->quoteName('v.value'))
				->from($db->quoteName('#__fields_values', 'v'))
				->from($db->quoteName('#__fields', 'f'))
				->where($db->quoteName('f.id') . ' = ' . $db->quoteName('v.field_id'))
				->where($db->quoteName('v.item_id') . ' = ' . $db->quote((string) $id))
				->where($db->quoteName('f.type') . ' = ' . $db->quote('imagelist')))
				->loadObjectList() as $field)
			{
				$params = json_decode($field->fieldparams);
				$_image = ComponentHelper::getParams('com_media')->get('image_path', 'images') . '/' . (isset($params->directory) ? $params->directory . '/' : '') . $field->value;
				Image::export($_image, $xml, $options);
			}

			foreach($db->setQuery($db->getQuery(true)
				->select($db->quoteName('v.value'))
				->from($db->quoteName('#__fields_values', 'v'))
				->from($db->quoteName('#__fields', 'f'))
				->where($db->quoteName('f.id') . ' = ' . $db->quoteName('v.field_id'))
				->where($db->quoteName('v.item_id') . ' = ' . $db->quote((string) $id))
				->where($db->quoteName('f.type') . ' = ' . $db->quote('editor')))
				->loadColumn() as $text)
			{
				\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($text, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
				$_image = preg_match_all(self::IMAGE_MATCH_STRING, $text, $matches, PREG_PATTERN_ORDER);
				if (count($matches[1]) > 0)
				{
					for ($i = 0; $i < count($matches[1]); $i ++)
					{
						if ($_image = $matches[1][$i])
						{
							Image::export($_image, $xml, $options);
						}
					}
				}
			}
		}

		return $xml;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Table::getCategoryId()
	 */
	public static function getCategoryId ($category, $extension = 'com_content', $defaultCategoryId = 0)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

		return parent::getCategoryId($category, $extension, $defaultCategoryId);
	}

	/**
	 * Change the content ID
	 *
	 * @since 23.2.378
	 */
	public static function changeId($id, $newid)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__ . '(' . $id . ', ' . $newid . ')', \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

		if ($id == $newid)
		{
			return;
		}

		$db      = Factory::getDbo();
		$context = 'com_content.article';

		// Check id
		$query = $db->getQuery(true)
			->select($db->quoteName('title'))
			->from($db->quoteName('#__content'))
			->where($db->quoteName('id') . ' = ' . $id);
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		$title = $db->setQuery($query)->loadResult();
		if (!$title)
		{
			throw new \Exception(\Joomla\CMS\Language\Text::sprintf('com_j2xml_ARTICLE_NOT_FOUND', $id));
		}

		// Check new id
		$query = $db->getQuery(true)
			->select($db->quoteName('title'))
			->from($db->quoteName('#__content'))
			->where($db->quoteName('id') . ' = ' . $newid);
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		if ($db->setQuery($query)->loadObject())
		{
			throw new \Exception(\Joomla\CMS\Language\Text::sprintf('com_j2xml_ARTICLE_UNIQUE_ID', $newid));
		}

		// Content
		$query = $db->getQuery(true)
			->select('MAX(' . $db->quoteName('id') . ')')
			->from($db->quoteName('#__content'));
		$maxid = (int) $db->setQuery($query)->loadResult();
		if ($newid > $maxid)
		{
			$serverType = $db->getServerType();

			if ($serverType === 'postgresql')
			{
				$query = 'ALTER SEQUENCE ' . $db->quoteName('#__content_id_seq') . ' RESTART WITH ' . ($newid + 1);
			}
			else
			{
				$query = 'ALTER TABLE ' . $db->quoteName('#__content') . ' AUTO_INCREMENT = ' . ($newid + 1);
			}
			\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
			$db->setQuery($query)->execute();
		}

		$query = $db->getQuery(true)
			->update($db->quoteName('#__content'))
			->set($db->quoteName('id') . ' = ' . $newid)
			->where($db->quoteName('id') . ' = ' . $id);
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		$db->setQuery($query)->execute();

		// Asset
		$query = $db->getQuery(true)
			->update($db->quoteName('#__assets'))
			->set($db->quoteName('name') . ' = ' . $db->quote($context . '.' . $newid))
			->where($db->quoteName('name') . ' = ' . $db->quote($context . '.' . $id));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		$db->setQuery($query)->execute();

		// Workflow
		$query = $db->getQuery(true)
			->update($db->quoteName('#__workflow_associations'))
			->set($db->quoteName('item_id') . ' = ' . $newid)
			->where($db->quoteName('item_id') . ' = ' . $id)
			->where($db->quoteName('extension') . ' = ' . $db->quote($context));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		$db->setQuery($query)->execute();

		// Field
		$query = $db->getQuery(true)
			->update($db->quoteName('#__fields_values'))
			->set($db->quoteName('item_id') . ' = ' . $newid)
			->where($db->quoteName('item_id') . ' = ' . $id);
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		$db->setQuery($query)->execute();

		// History
		$query = $db->getQuery(true)
			->update($db->quoteName('#__history'))
			->set($db->quoteName('item_id') . ' = ' . $db->quote($context . '.' . $newid))
			->set($db->quoteName('version_data') . ' = REPLACE(' . $db->quoteName('version_data') . ', "{\"id\":' . $newid . ',", "{\"id\":' . $id . ',")')
			->set($db->quoteName('sha1_hash') . ' = SHA1(REPLACE(' . $db->quoteName('version_data') . ', "{\"id\":' . $newid . ',", "{\"id\":' . $id . ',"))')
			->where($db->quoteName('item_id') . ' = ' . $db->quote($context . '.' . $id));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		$db->setQuery($query)->execute();

		// Menu
		$contextPart = explode('.', $context);
		$option      = $contextPart[0];
		$view        = $contextPart[1];
		$query = $db->getQuery(true)
			->select($db->quoteName('extension_id'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote('component'))
			->where($db->quoteName('element') . ' = ' . $db->quote($option));
		$componentId = $db->setQuery($query)->loadResult();

		$query = $db->getQuery(true)
			->update($db->quoteName('#__menu'))
			->set($db->quoteName('link') . ' = ' . $db->quote('index.php?option=' . $option . '&view=' . $view . '&id=' . $newid))
			->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=' . $option . '&view=' . $view . '&id=' . $id))
			->where($db->quoteName('component_id') . ' = ' . $componentId);
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		$db->setQuery($query)->execute();

		// Language association
		if (\Joomla\CMS\Language\Associations::isEnabled())
		{
			$contextLanguage = 'com_content.item';

			$query = $db->getQuery(true)
				->select($db->quoteName('key'))
				->from($db->quoteName('#__associations'))
				->where($db->quoteName('id') . ' = ' . (int) $id)
				->where($db->quoteName('context') . ' = ' . $db->quote($contextLanguage));
			\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

			$key = $db->setQuery($query)->loadResult();
			\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($key, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

			if ($key)
			{
				// update id
				$query = $db->getQuery(true)
					->update($db->quoteName('#__associations'))
					->set($db->quoteName('id') . ' = ' . $newid)
					->where($db->quoteName('id') . ' = ' . $id)
					->where($db->quoteName('key') . ' = ' . $db->quote($key))
					->where($db->quoteName('context') . ' = ' . $db->quote($contextLanguage));
				\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
				$db->setQuery($query)->execute();

				// update key
				$query = $db->getQuery(true)
					->select($db->quoteName('c.id'))
					->select($db->quoteName('c.language'))
					->from($db->quoteName('#__associations', 'a'))
					->join('INNER', $db->quoteName('#__content', 'c') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.id') . ')')
					->where($db->quoteName('key') . ' = ' . $db->quote($key))
					->where($db->quoteName('context') . ' = ' . $db->quote($contextLanguage));
				\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
				$rows = $db->setQuery($query)->loadObjectList();
				$associations = array();
				foreach ($rows as $row)
				{
					$associations[$row->language] = (int) $row->id;
				}
				\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(json_encode($associations), \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
				$newkey   = md5(json_encode($associations));

				$query = $db->getQuery(true)
					->update($db->quoteName('#__associations'))
					->set($db->quoteName('key') . ' = ' . $db->quote($newkey))
					->where($db->quoteName('key') . ' = ' . $db->quote($key))
					->where($db->quoteName('context') . ' = ' . $db->quote($contextLanguage));
				\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
				$db->setQuery($query)->execute();
			}
		}

		// Tags
		$query = $db->getQuery(true)
			->update($db->quoteName('#__contentitem_tag_map'))
			->set($db->quoteName('content_item_id') . ' = ' . $newid)
			->where($db->quoteName('content_item_id') . ' = ' . $id)
			->where($db->quoteName('type_alias') . ' = ' . $db->quote($context));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		$db->setQuery($query)->execute();

		// Frontpage
		$query = $db->getQuery(true)
			->update($db->quoteName('#__content_frontpage'))
			->set($db->quoteName('content_id') . ' = ' . $newid)
			->where($db->quoteName('content_id') . ' = ' . $id);
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		$db->setQuery($query)->execute();

		// Rating
		$query = $db->getQuery(true)
			->update($db->quoteName('#__content_rating'))
			->set($db->quoteName('content_id') . ' = ' . $newid)
			->where($db->quoteName('content_id') . ' = ' . $id);
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		$db->setQuery($query)->execute();
	}
}
