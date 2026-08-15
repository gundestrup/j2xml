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

namespace Joomla\Component\J2xml\Administrator\View;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Base RAW view for J2XML exports.
 *
 * @since  __DEPLOY_VERSION__
 */
class AbstractRawView extends HtmlView
{
	/**
	 * The list of IDs to be exported.
	 *
	 * @var array
	 */
	protected $ids;

	/**
	 * The params object.
	 *
	 * @var Registry
	 */
	protected $params;

	/**
	 * The export method name (e.g. 'content', 'categories').
	 *
	 * @var string
	 */
	protected $exportMethod = '';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  Configuration array
	 */
	public function __construct($config = [])
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		parent::__construct($config);

		$app = Factory::getApplication();
		$jform = $app->input->post->get('jform', [], 'array');

		$this->ids = explode(',', $jform['cid'] ?? '');
		unset($jform['cid']);

		$this->params = new Registry();
		$this->params->loadArray($jform);
	}

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse
	 *
	 * @return  boolean
	 */
	public function display($tpl = null)
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		$params = new Registry();
		foreach ($this->params->toArray() as $k => $v)
		{
			$params->set(str_starts_with($k, 'export_') ? substr($k, 7) : $k, $v);
		}

		$app = Factory::getApplication();
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$j2xml = new \eshiol\J2xml\Exporter($db, $app);
		$exportMethod = $this->exportMethod ?: strtolower($this->getName());
		$xml = null;
		$j2xml->$exportMethod($this->ids, $xml, $params);

		$out = 'j2xml' . str_replace('.', '', \eshiol\J2xml\Version::$DOCVERSION) . (new \Joomla\CMS\Date\Date('now'))->format('YmdHis');

		$dom = new \DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml->asXML());
		$data = $dom->saveXML();

		$document = $app->getDocument();
		$compression = $params->get('compression', 0);

		if (!\extension_loaded('zlib') || ini_get('zlib.output_compression'))
		{
			$document->setMimeEncoding('text/xml', true);
			$app->setHeader('Content-disposition', 'attachment; filename="' . $out . '.xml"', true);
		}
		elseif ($compression)
		{
			$document->setMimeEncoding('application/gzip', true);
			$app->setHeader('Content-disposition', 'attachment; filename="' . $out . '.gz"', true);
			$data = gzencode($data, 4);
		}
		else
		{
			$document->setMimeEncoding('text/xml', true);
			$app->setHeader('Content-disposition', 'attachment; filename="' . $out . '.xml"', true);
		}

		echo $data;

		return true;
	}
}
