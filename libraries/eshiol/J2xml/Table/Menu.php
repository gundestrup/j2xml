<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       21.1.355
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

/**
 *
 * Menu Table
 *
 */
class Menu extends \eshiol\J2XML\Table\Table
{

	/**
	 * Constructor
	 *
	 * @param object $db
	 *        	Database connector
	 *
	 * @since 17.1.294
	 */
	function __construct (&$db)
	{
		parent::__construct('#__menu', 'id', $db);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \eshiol\J2XML\Table\Table::toXML()
	 */
	function toXML ($mapKeysToText = false)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

		if ($this->type == 'component')
		{
			$this->_aliases['component_id'] = 'SELECT ' . $this->_db->qn('name') . ' FROM ' . $this->_db->qn('#__extensions') . ' WHERE ' .
					 $this->_db->qn('extension_id') . ' = ' . (int) $this->component_id;

			$args = [];
			parse_str(parse_url($this->link, PHP_URL_QUERY), $args);
			if (isset($args['option']) && ($args['option'] == 'com_content'))
			{
				if (isset($args['view']) && ($args['view'] == 'article'))
				{
					$this->_aliases['article_id'] = 'SELECT CONCAT(' . $this->_db->qn('c.path') . ', ' . $this->_db->q('/') . ', ' .
							 $this->_db->qn('a.alias') . ')' . ' FROM ' . $this->_db->qn('#__content') . ' a' . ' INNER JOIN ' .
							 $this->_db->qn('#__categories') . ' c' . ' ON ' . $this->_db->qn('a.catid') . ' = ' . $this->_db->qn('c.id') . ' WHERE ' .
							 $this->_db->qn('a.id') . ' = ' . (int) $args['id'];
					 \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry('article_id: ' . $this->_aliases['article_id'], \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
				}
			}
		}
		return parent::_serialize();
	}

	/**
	 * Export menu
	 *
	 * @param int $id
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 19.2.318
	 */
	public static function export ($id, &$xml, $options)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry('id: ' . $id, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry('options: ' . print_r($options, true), \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

		$db = \Joomla\CMS\Factory::getDbo();
		$item = new Menu($db);
		if (!$item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/menu/id[text() = '" . $item->id . "']"))
		{
			return;
		}

		$args = [];
		parse_str(parse_url($item->link ?: '', PHP_URL_QUERY), $args);

		if (isset($args['option']) && ($args['option'] == 'com_content'))
		{
			if (isset($args['view']) && ($args['view'] == 'article'))
			{
				\eshiol\J2XML\Table\Content::export($args['id'], $xml, $options);
			}
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();

		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		if (isset($options['images']) && $options['images'])
		{
			if ($imgs = json_decode($item->params))
			{
				if (isset($imgs->menu_image))
				{
					Image::export($imgs->menu_image, $xml, $options);
				}
			}
		}

		/* export children */
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('menutype') . ' = ' . $db->quote($item->menutype))
			->where($db->quoteName('parent_id') . ' = ' . $id)
			->order($db->quoteName('lft'));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		$ids_menu = $db->setQuery($query)->loadColumn();

		foreach ($ids_menu as $id_menu)
		{
			Menu::export($id_menu, $xml, $options);
		}
	}

	/**
	 * importing menus
	 *
	 * @param SimpleXMLElement $xml
	 * @param \JRegistry $params
	 *
	 * @since 19.2.318
	 */
	public static function import ($xml, &$params)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

		$db = \Joomla\CMS\Factory::getDbo();
		$import_menus = $params->get('import_menus', '1');

		foreach ($xml->xpath("//j2xml/menu[not(title = '')]") as $record)
		{
			self::prepareData($record, $data, $params);

			$query = $db->getQuery(true)
				->select($db->quoteName([
					'id',
					'title'
			]))
				->from($db->quoteName('#__menu'))
				->where($db->quoteName('path') . ' = ' . $db->quote($data['path']));
			\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
			$db->setQuery($query);
			$menu = $db->loadObject();

			$import_menus = 2;
			if (!$menu || ($import_menus == 2))
			{
				$table = new Menu($db);

				if (!$menu)
				{ // new menu
					$data['id'] = null;
				}
				else // menu already exists
				{
					$data['id'] = $menu->id;
					$table->load($data['id']);
				}

				if (isset($data['component_id']) && $data['component_id'])
				{
					$query = $db->getQuery(true)
						->select($db->quoteName('extension_id'))
						->from($db->quoteName('#__extensions'))
						->where($db->quoteName('type') . ' = ' . $db->quote('component'))
						->where($db->quoteName('element') . ' = ' . $db->quote($data['component_id']));
					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
					$component = $db->setQuery($query)->loadResult();

					if (!$component)
					{
						$error = \Joomla\CMS\Language\Text::sprintf('LIB_J2XML_ERROR_COMPONENT_NOT_FOUND', $data['component_id']);
						\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_MENU_NOT_IMPORTED', $data['title'], $error), \Joomla\CMS\Log\Log::WARNING, 'lib_j2xml'));
						continue;
					}
				}
				else
				{
					$component = 0;
				}

				$data['component_id'] = $component;

				if ($data['type'] == 'component')
				{
					if (isset($data['link']) && $data['link'])
					{
						$args = [];
						parse_str(parse_url($data['link'], PHP_URL_QUERY), $args);
						if (isset($args['option']))
						{
							if ($args['option'] == 'com_content')
							{
								if (isset($args['view']) && ($args['view'] == 'article'))
								{
									if (empty($data['article_id']))
									{
										$error = \Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_NOT_FOUND', 0);
										\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_MENU_NOT_IMPORTED', $data['title'], $error), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
										continue;

									}

									$args['id'] = self::getArticleId($data['article_id']);
									if ($args['id'] == 0)
									{
										$error = \Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_NOT_FOUND', $data['article_id']);
										\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_MENU_NOT_IMPORTED', $data['title'], $error), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
										continue;
									}
									$data['link'] = 'index.php?' . http_build_query($args);
								}
							}
							else
							{
								$query = $db->getQuery(true)
									->select($db->quoteName('extension_id'))
									->from($db->quoteName('#__extensions'))
									->where($db->quoteName('type') . ' = ' . $db->quote('component'))
									->where($db->quoteName('element') . ' = ' . $db->quote($args['option']));
								\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
								$component = $db->setQuery($query)->loadResult();
								if (!$component)
								{
									$error = \Joomla\CMS\Language\Text::sprintf('LIB_J2XML_ERROR_COMPONENT_NOT_FOUND', $args['option']);
									\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_MENU_NOT_IMPORTED', $data['title'], $error), \Joomla\CMS\Log\Log::WARNING, 'lib_j2xml'));
									continue;
								}
							}
						}
						else
						{
							\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_MENU_NOT_IMPORTED', $data['title'], \Joomla\CMS\Language\Text::_('LIB_J2XML_ERROR_UNKNOWN')), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
							continue;
						}
					}
				}

				// Trigger the onContentBeforeSave event.
				$table->bind($data);
				if ($table->store())
				{
					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_MENU_IMPORTED', $table->title), \Joomla\CMS\Log\Log::INFO, 'lib_j2xml'));
					// Trigger the onContentAfterSave event.
				}
				else
				{
					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_MENU_NOT_IMPORTED', $data['title'], $table->getError()), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
				}

				$table = null;
			}
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \eshiol\J2XML\Table::prepareData()
	 *
	 * @since 19.2.318
	 */
	public static function prepareData ($record, &$data, $params)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

		parent::prepareData($record, $data, $params);

		$path = $data['path'];
		$i = strrpos($path, '/');
		if ($i === false)
		{
			$data['parent_id'] = 1;
		}
		else
		{
			$data['parent_id'] = self::getMenuId($data['menutype'] . '/' . substr($path, 0, $i));
		}
		$data['level'] = substr_count($path , '/') + 1;

		if (!isset($data['img']))
		{
			$data['img'] = '';
		}
		if (!isset($data['link']))
		{
			$data['link'] = '';
		}
	}
}
