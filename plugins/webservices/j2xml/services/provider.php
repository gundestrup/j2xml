<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Webservices.j2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2026 Helios Ciancio. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                require_once JPATH_PLUGINS . '/webservices/j2xml/j2xml.php';

                $plugin = new \PlgWebservicesJ2xml(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('webservices', 'j2xml')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
