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

use PHPUnit\Framework\TestCase;

final class ImportSettingsTest extends TestCase
{
    private function importForm(): SimpleXMLElement
    {
        $xml = simplexml_load_file(J2XML_ROOT . '/administrator/components/com_j2xml/forms/import.xml', 'SimpleXMLElement', LIBXML_NONET);
        self::assertNotFalse($xml);

        return $xml;
    }

    public function testImportFormExposesAllEntitySwitches(): void
    {
        $xml = $this->importForm();
        $fields = array_map(
            static fn (SimpleXMLElement $field): string => (string) $field['name'],
            iterator_to_array($xml->xpath('//field') ?: [])
        );

        foreach ([
            'import_content',
            'import_categories',
            'import_users',
            'import_contacts',
            'import_modules',
            'import_menus',
            'import_tags',
            'import_fields',
            'import_images',
            'import_viewlevels',
            'import_weblinks',
        ] as $field)
        {
            self::assertContains($field, $fields);
        }
    }

    public function testImportFormContainsOverwriteAndNewerModes(): void
    {
        $xml = $this->importForm();
        $content = $xml->xpath('//field[@name="import_content"]')[0];
        $options = array_map(
            static fn (SimpleXMLElement $option): string => (string) $option['value'],
            iterator_to_array($content->xpath('./option') ?: [])
        );

        self::assertSame(['', '1', '2', '3'], $options);
    }

    public function testComprehensiveFixtureContainsAllSupportedEntityNodes(): void
    {
        $xml = simplexml_load_file(J2XML_ROOT . '/tests/fixtures/all-content-types.xml', 'SimpleXMLElement', LIBXML_NONET);
        self::assertNotFalse($xml);

        foreach (['user', 'content', 'category', 'contact', 'module', 'menu', 'tag', 'field', 'usernote'] as $node)
        {
            self::assertNotEmpty($xml->xpath('/j2xml/' . $node));
        }
    }
}
