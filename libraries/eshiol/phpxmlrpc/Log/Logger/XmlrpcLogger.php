<?php
/**
 * @package		Joomla.Libraries
 * @subpackage	eshiol.XMLRPC
 *
 * @version		__DEPLOY_VERSION__
 * @since		1.5
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		https://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2023 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

namespace Joomla\CMS\Log\Logger;

// no direct access
defined('_JEXEC') or die('Restricted access.');

\JLoader::registerAlias('JLogLoggerXmlrpc', '\Joomla\CMS\\Log\\Logger\\XmlrpcLogger');

/**
 * Joomla XMLRPC logger class.
 *
 * This class is designed to output logs as xmlrpc message
 *
 * @since 4.3.1
 */
class XmlrpcLogger extends \Joomla\CMS\Log\Logger
{
	/**
	 * Constructor.
	 *
	 * @param   array  &$options  Log object options.
	 */
	public function __construct(array &$options)
	{
		// Call the parent constructor.
		parent::__construct($options);

		// Throw an exception if there is not a valid callback
		if (!isset($this->options['service']))
		{
			throw new \RuntimeException(sprintf('%s created without valid service.', get_class($this)));
		}
	}

	/**
	 * Method to add an entry to the log.
	 *
	 * @param JLogEntry $entry
	 *        	The log entry object to add to the log.
	 *
	 * @return void
	 */
	public function addEntry (\Joomla\CMS\Log\LogEntry $entry)
	{
		$service = $this->options['service'];

		switch ($entry->priority)
		{
			case \Joomla\CMS\Log\Log::EMERGENCY:
			case \Joomla\CMS\Log\Log::ALERT:
			case \Joomla\CMS\Log\Log::CRITICAL:
			case \Joomla\CMS\Log\Log::ERROR:
				$service::enqueueMessage($entry->message, 'error');
				break;
			case \Joomla\CMS\Log\Log::WARNING:
				$service::enqueueMessage($entry->message, 'warning');
				break;
			case \Joomla\CMS\Log\Log::NOTICE:
				$service::enqueueMessage($entry->message, 'notice');
				break;
			case \Joomla\CMS\Log\Log::INFO:
				$service::enqueueMessage($entry->message, 'message');
				break;
			default:
				// Ignore other priorities.
				break;
		}
	}
}