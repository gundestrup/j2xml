<?php

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
