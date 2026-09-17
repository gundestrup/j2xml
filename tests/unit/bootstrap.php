<?php
/**
 * @package     J2XML
 *
 * @copyright   Copyright (C) 2026 Svend Gundestrup. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

declare(strict_types=1);

const J2XML_ROOT = __DIR__ . '/../..';

// Load the Joomla stubs so that library classes can be loaded
// without a full Joomla CMS installation.
require_once J2XML_ROOT . '/stubs/joomla.php';

// Manually require the J2XML library classes (no Composer autoloading).
require_once J2XML_ROOT . '/libraries/eshiol/J2xml/Version.php';
require_once J2XML_ROOT . '/libraries/eshiol/J2xml/Table/Table.php';
require_once J2XML_ROOT . '/libraries/eshiol/J2xml/Table/Tag.php';
require_once J2XML_ROOT . '/libraries/eshiol/J2xml/Exporter.php';
require_once J2XML_ROOT . '/libraries/eshiol/J2xml/Importer.php';
