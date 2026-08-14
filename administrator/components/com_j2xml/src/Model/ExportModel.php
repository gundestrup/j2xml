<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2026 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
namespace Joomla\Component\J2xml\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\MVC\Model\FormModel;

/**
 * Export model.
 *
 * @since 3.9.0
 */
class ExportModel extends FormModel
{

	/**
	 * The model context
	 *
	 * @var string
	 */
	protected $context = 'j2xml';

	/**
	 * Constructor.
	 *
	 * @param array $config
	 *			An optional associative array of configuration settings.
	 *
	 * @see JModelLegacy
	 * @since 3.9.0
	 */
	public function __construct($config = [])
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		$layout = Factory::getApplication()->input->get('layout', 'default');
		if ($layout != 'default')
		{
			$this->context .= '.' . $layout;
		}

		parent::__construct($config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param array $data
	 *			Data for the form.
	 * @param boolean $loadData
	 *			True if the form is to load its own data (default case), false if not.
	 *
	 * @return JForm|boolean A JForm object on success, false on failure
	 *
	 * @since 3.9.0
	 */
	public function getForm($data = [], $loadData = true)
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		try
		{
			$form = $this->loadForm($this->context, 'export', [
				'control' => 'jform',
				'load_data' => false
			]);

			$layout = Factory::getApplication()->input->get('layout', 'default');
			if ($layout != 'default')
			{
				$form->loadFile('export_' . $layout);

				if ($layout != 'users')
				{
					$form->loadFile('export_users');
					/* if ($layout == 'contact')
					{
						$form->setFieldAttribute('export_contacts', 'type', 'hidden');
					} */
				}
				/* else
				{
					$form->setFieldAttribute('export_users', 'type', 'hidden');
				} */
			}

			if ($loadData)
			{
				// Get the data for the form.
				$data = $this->loadFormData();
			}
			else
			{
				$data = [];
			}

			// Allow for additional modification of the form, and events to be triggered.
			// We pass the data because plugins may require it.
			$this->preprocessForm($form, $data);

			// Load the data into the form after the plugins have operated.
			$form->bind($data);
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return mixed The data for the form.
	 *
	 * @since 3.9.0
	 */
	protected function loadFormData()
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		// Check the session for previously entered form data.
		$data   = Factory::getApplication()->getUserState('com_j2xml.export.data', []);
		Log::add(new LogEntry('getUserState(\'com_j2xml.export.data\'): ' . print_r($data, true), Log::DEBUG, 'com_j2xml'));
		$jform  = [];
		foreach($data as $k => $v)
		{
			$jform['export_' . $k] = $v;
		}

		$params = ComponentHelper::getParams('com_j2xml');
		$data   = array_merge($params->toArray(), $jform);
		Log::add(new LogEntry('data: ' . print_r($data, true), Log::DEBUG, 'com_j2xml'));

		$this->preprocessData($this->context, $data);

		return $data;
	}
}
