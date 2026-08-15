<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2026 Helios Ciancio <info (at) eshiol (dot) it> (https://www.eshiol.it). All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 */

namespace Joomla\Component\J2xml\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Base export controller for J2XML.
 *
 * Handles both raw (XML download) and JSON (API) export formats.
 * Subclasses set $viewName to determine which export method to call.
 *
 * @since  __DEPLOY_VERSION__
 */
class ExportController extends BaseController
{
	/**
	 * The view name for this controller (e.g. 'content', 'categories').
	 *
	 * @var string
	 */
	protected $viewName = '';

	/**
	 * Display method for raw export — renders the XML view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters
	 *
	 * @return  void
	 */
	public function display($cachable = false, $urlparams = false)
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		$app = $this->app;
		$jform = $app->input->post->get('jform', [], 'array');
		$data = [];
		foreach ($jform as $k => $v)
		{
			if (str_starts_with($k, 'export_'))
			{
				$data[substr($k, 7)] = $v;
			}
		}
		$app->setUserState('com_j2xml.export.data', $data);
		Log::add(new LogEntry('setUserState(\'com_j2xml.export.data\'): ' . print_r($data, true), Log::DEBUG, 'com_j2xml'));

		$view = $this->viewName ?: $this->getName();
		$this->input->set('view', $view);
		parent::display();
	}

	/**
	 * Export content as JSON (for format=json requests).
	 *
	 * @return  void
	 */
	public function export(): void
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		if (!Session::checkToken('request'))
		{
			Log::add(new LogEntry(Text::_('JINVALID_TOKEN'), Log::WARNING, 'com_j2xml'));
			echo new JsonResponse();
			return;
		}

		$app = $this->app;
		$data = $app->input->post->getArray();
		$app->setUserState('com_j2xml.send.data', $data);
		Log::add(new LogEntry('setUserState(\'com_j2xml.send.data\'): ' . print_r($data, true), Log::DEBUG, 'com_j2xml'));

		$cid = (array) $this->input->get('cid', [0], 'array');

		$exportMethod = $this->viewName ?: $this->getName();

		$db = \Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
		$j2xml = new \eshiol\J2xml\Exporter($db, $this->app);
		$xml = null;
		$j2xml->$exportMethod($cid, $xml, new Registry());

		$dom = new \DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml->asXML());
		$xmlData = $dom->saveXML();

		echo new JsonResponse($xmlData);
	}

	/**
	 * Execute a task by name. Handles both raw and JSON formats.
	 *
	 * @param   string  $task  The task to execute
	 *
	 * @return  mixed   The return value from the task
	 */
	public function execute($task)
	{
		$format = $this->input->getCmd('format');
		if ($format === 'json')
		{
			if ($task === 'display' || $task === '' || $task === $this->getName())
			{
				$task = 'export';
			}
		}

		return parent::execute($task);
	}
}
