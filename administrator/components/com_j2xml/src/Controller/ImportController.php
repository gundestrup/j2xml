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

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Import controller for J2XML.
 *
 * @since  __DEPLOY_VERSION__
 */
class ImportController extends BaseController
{
	/**
	 * Import data.
	 *
	 * @return  boolean
	 */
	public function import(): bool
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		$this->checkToken();

		$model = $this->getModel('import');

		$result = $model->import();

		$app = Factory::getApplication();
		$redirect_url = $app->getUserState('com_j2xml.redirect_url');

		if (!$redirect_url)
		{
			$redirect_url = base64_decode($app->input->get('return', ''));
		}

		if (!Uri::isInternal($redirect_url))
		{
			$redirect_url = '';
		}

		if (empty($redirect_url))
		{
			$redirect_url = Route::_('index.php?option=com_j2xml&view=import', false);
		}
		else
		{
			$app->setUserState('com_j2xml.redirect_url', '');
			$app->setUserState('com_j2xml.message', '');
		}

		$this->setRedirect($redirect_url);

		return $result;
	}

	/**
	 * Import data from drag & drop ajax upload.
	 *
	 * @return  void
	 */
	public function ajax_upload(): void
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		$app = Factory::getApplication();
		$message = $app->getUserState('com_j2xml.message');

		$jform = $app->input->post->get('jform', [], 'array');
		$data = [];
		foreach ($jform as $k => $v)
		{
			if (str_starts_with($k, 'import_'))
			{
				$data[substr($k, 7)] = $v;
			}
		}
		$app->setUserState('com_j2xml.import.data', $data);
		Log::add(new LogEntry('setUserState(\'com_j2xml.import.data\'): ' . print_r($data, true), Log::DEBUG, 'com_j2xml'));

		ob_start();
		$result = $this->import();
		ob_end_clean();

		$redirect = $this->redirect;

		$app->getSession()->set('application.queue', $app->getMessageQueue());

		header('Content-Type: application/json');

		echo new JsonResponse(['redirect' => $redirect], $message, !$result);

		$app->close();
	}
}
