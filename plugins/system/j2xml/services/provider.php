<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.J2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Svend Gundestrup
 * @copyright   Copyright (C) 2026 Svend Gundestrup. All Rights Reserved.
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
                require_once JPATH_PLUGINS . '/system/j2xml/j2xml.php';

                $dispatcher = $container->get(DispatcherInterface::class);
                $plugin = new \plgSystemJ2xml(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('system', 'j2xml')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
