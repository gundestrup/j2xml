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
use eshiol\J2xml\Table\Table;
use eshiol\J2xml\Table\Tag;
use Joomla\Database\DatabaseInterface;

/**
 * Unit tests for eshiol\J2xml\Table\Table — pure-logic methods.
 *
 * Tests the static and protected methods that don't require a database
 * connection: xml2array, _setValue, fixDate, and the numeric lookup
 * short-circuits (getArticleId, getUserId, getMenuId with numeric input).
 */
final class TableTest extends TestCase
{
    /**
     * Call a private/protected static method via reflection.
     */
    private static function invokeStatic(string $method, array $args = [])
    {
        $r = new ReflectionMethod(Table::class, $method);
        return $r->invokeArgs(null, $args);
    }

    // ------------------------------------------------------------------
    // xml2array — converts SimpleXMLElement to a PHP array
    // ------------------------------------------------------------------

    public function testXml2ArraySimpleElement(): void
    {
        $xml = new SimpleXMLElement('<title>Hello</title>');
        $result = self::invokeStatic('xml2array', [$xml]);
        self::assertSame('Hello', $result);
    }

    public function testXml2ArrayElementWithAttributes(): void
    {
        $xml = new SimpleXMLElement('<item id="42" name="test">value</item>');
        $result = self::invokeStatic('xml2array', [$xml]);
        self::assertIsArray($result);
        self::assertSame('42', $result['id']);
        self::assertSame('test', $result['name']);
        self::assertSame('value', $result['value']);
    }

    public function testXml2ArrayElementWithChildren(): void
    {
        $xml = new SimpleXMLElement('<content><title>Test</title><alias>test</alias></content>');
        $result = self::invokeStatic('xml2array', [$xml]);
        self::assertIsArray($result);
        self::assertSame('Test', $result['title']);
        self::assertSame('test', $result['alias']);
    }

    public function testXml2ArrayEmptyElement(): void
    {
        $xml = new SimpleXMLElement('<empty></empty>');
        $result = self::invokeStatic('xml2array', [$xml]);
        // Empty element with no children and no text → null
        self::assertNull($result);
    }

    public function testXml2ArrayWithHtmlEntityDecode(): void
    {
        $xml = new SimpleXMLElement('<title>café</title>');
        $result = self::invokeStatic('xml2array', [$xml, true]);
        self::assertSame('café', $result);
    }

    public function testXml2ArrayWithoutHtmlEntityDecode(): void
    {
        $xml = new SimpleXMLElement('<title>café</title>');
        $result = self::invokeStatic('xml2array', [$xml, false]);
        self::assertSame('café', $result);
    }

    public function testXml2ArrayNestedChildren(): void
    {
        $xml = new SimpleXMLElement('<root><a><b>value</b></a></root>');
        $result = self::invokeStatic('xml2array', [$xml]);
        self::assertIsArray($result);
        self::assertIsArray($result['a']);
        self::assertSame('value', $result['a']['b']);
    }

    public function testXml2ArrayUnicodeContent(): void
    {
        $xml = new SimpleXMLElement('<title>café — naïve</title>');
        $result = self::invokeStatic('xml2array', [$xml]);
        self::assertSame('café — naïve', $result);
    }

    public function testXml2ArrayCdataContent(): void
    {
        $xml = simplexml_load_string('<root><introtext><![CDATA[<p>Hello</p>]]></introtext></root>');
        $result = self::invokeStatic('xml2array', [$xml]);
        self::assertSame('<p>Hello</p>', $result['introtext']);
    }

    public function testXml2ArrayMultipleChildrenSameName(): void
    {
        $xml = simplexml_load_string('<root><tag>alpha</tag><tag>beta</tag></root>');
        $result = self::invokeStatic('xml2array', [$xml]);
        // When multiple children have the same name, SimpleXML wraps them
        self::assertIsArray($result);
        self::assertArrayHasKey('tag', $result);
    }

    // ------------------------------------------------------------------
    // _setValue — generates XML fragments from key/value pairs
    // ------------------------------------------------------------------

    public function testSetValueNumericValue(): void
    {
        // _setValue is protected, need an instance
        $r = new ReflectionMethod(Table::class, '_setValue');

        // Create a Table instance without calling the constructor
        $table = (new ReflectionClass(Table::class))->newInstanceWithoutConstructor();
        $result = $r->invoke($table, 'id', 42);
        self::assertSame('<id>42</id>', $result);
    }

    public function testSetValueStringValue(): void
    {
        $r = new ReflectionMethod(Table::class, '_setValue');
        $table = (new ReflectionClass(Table::class))->newInstanceWithoutConstructor();
        $result = $r->invoke($table, 'title', 'Hello World');
        self::assertStringContainsString('<title>', $result);
        self::assertStringContainsString('Hello World', $result);
        self::assertStringContainsString('<![CDATA[', $result);
    }

    public function testSetValueEmptyString(): void
    {
        $r = new ReflectionMethod(Table::class, '_setValue');
        $table = (new ReflectionClass(Table::class))->newInstanceWithoutConstructor();
        $result = $r->invoke($table, 'alias', '');
        self::assertSame('<alias />', $result);
    }

    public function testSetValueObjectWithSingleProperty(): void
    {
        $r = new ReflectionMethod(Table::class, '_setValue');
        $table = (new ReflectionClass(Table::class))->newInstanceWithoutConstructor();
        $obj = new stdClass();
        $obj->name = 'test';
        $result = $r->invoke($table, 'field', $obj);
        // Single-property object: the property value is extracted and wrapped
        self::assertStringContainsString('<field>', $result);
        self::assertStringContainsString('test', $result);
        self::assertStringContainsString('</field>', $result);
    }

    public function testSetValueObjectWithMultipleProperties(): void
    {
        $r = new ReflectionMethod(Table::class, '_setValue');
        $table = (new ReflectionClass(Table::class))->newInstanceWithoutConstructor();
        $obj = new stdClass();
        $obj->name = 'test';
        $obj->value = 'data';
        $result = $r->invoke($table, 'field', $obj);
        self::assertStringContainsString('<field', $result);
        self::assertStringContainsString('<name>', $result);
        self::assertStringContainsString('test', $result);
        self::assertStringContainsString('<value>', $result);
        self::assertStringContainsString('data', $result);
    }

    public function testSetValuePreservesSpecialChars(): void
    {
        $r = new ReflectionMethod(Table::class, '_setValue');
        $table = (new ReflectionClass(Table::class))->newInstanceWithoutConstructor();
        $result = $r->invoke($table, 'content', '<p>Hello & "world"</p>');
        // _setValue uses htmlentities + CDATA, so special chars are encoded
        self::assertStringContainsString('<![CDATA[', $result);
        self::assertStringContainsString('Hello', $result);
        self::assertStringContainsString('world', $result);
    }

    // ------------------------------------------------------------------
    // fixDate — normalizes date strings
    // ------------------------------------------------------------------

    public function testFixDateEmptyReturnsNull(): void
    {
        $result = self::invokeStatic('fixDate', ['']);
        self::assertNull($result);
    }

    public function testFixDateZeroDateReturnsNull(): void
    {
        $result = self::invokeStatic('fixDate', ['0000-00-00 00:00:00']);
        self::assertNull($result);
    }

    public function testFixDateEpochReturnsNull(): void
    {
        $result = self::invokeStatic('fixDate', ['1970-01-01 00:00:00']);
        self::assertNull($result);
    }

    // ------------------------------------------------------------------
    // Lookup methods with numeric input (short-circuit, no DB needed)
    // ------------------------------------------------------------------

    public function testGetArticleIdNumericReturnsInput(): void
    {
        $result = Table::getArticleId(42);
        self::assertSame(42, $result);
    }

    public function testGetArticleIdNumericZeroReturnsInput(): void
    {
        $result = Table::getArticleId(0);
        self::assertSame(0, $result);
    }

    public function testGetMenuIdNumericReturnsInput(): void
    {
        $result = Table::getMenuId(101);
        self::assertSame(101, $result);
    }

    public function testGetMenuIdNumericZeroReturnsInput(): void
    {
        $result = Table::getMenuId(0);
        self::assertSame(0, $result);
    }

    public function testGetUsergroupIdPositiveNumericReturnsInput(): void
    {
        self::assertSame(7, Table::getUsergroupId(7));
    }

    public function testGetAccessIdNumericReturnsInput(): void
    {
        self::assertSame(5, Table::getAccessId(5));
    }

    public function testGetAccessIdZeroFallsBackToSpecialAccess(): void
    {
        self::assertSame(3, Table::getAccessId(0));
    }

    public function testFixDateNormalDateUsesJoomlaDate(): void
    {
        // The Joomla date stub returns an empty SQL value, but this exercises
        // the non-empty branch and confirms that it does not return null.
        self::assertSame('', self::invokeStatic('fixDate', ['2024-01-02 03:04:05']));
    }

    // ------------------------------------------------------------------
    // IMAGE_MATCH_STRING constant
    // ------------------------------------------------------------------

    public function testGetTagIdReturnsDatabaseId(): void
    {
        $query = new class implements \Joomla\Database\QueryInterface {
            public function clear(?string $clause = null): self { return $this; }
            public function select($value): self { return $this; }
            public function from($value): self { return $this; }
            public function where($value): self { return $this; }
        };
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(static fn (string $value): string => $value);
        $db->method('quote')->willReturnCallback(static fn ($value): string => "'" . $value . "'");
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadResult')->willReturn(9);

        self::assertSame(9, Table::getTagId('news', $db));
    }

    public function testGetTagIdReturnsFalseOnDatabaseFailure(): void
    {
        $query = new class implements \Joomla\Database\QueryInterface {
            public function clear(?string $clause = null): self { return $this; }
            public function select($value): self { return $this; }
            public function from($value): self { return $this; }
            public function where($value): self { return $this; }
        };
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(static fn (string $value): string => $value);
        $db->method('quote')->willReturnCallback(static fn ($value): string => "'" . $value . "'");
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadResult')->willThrowException(new RuntimeException('database failure'));

        self::assertFalse(Table::getTagId('news', $db));
    }

    public function testImageMatchStringIsRegex(): void
    {
        self::assertIsString(Table::IMAGE_MATCH_STRING);
        // Verify it matches an img tag with src
        $html = '<p>Some text</p><img src="images/test.jpg" alt="test" />';
        self::assertSame(1, preg_match(Table::IMAGE_MATCH_STRING, $html, $matches));
        self::assertSame('images/test.jpg', $matches[1]);
    }

    public function testImageMatchStringNoMatchReturnsZero(): void
    {
        $html = '<p>No image here</p>';
        self::assertSame(0, preg_match(Table::IMAGE_MATCH_STRING, $html));
    }

    public function testImageMatchStringMultipleImages(): void
    {
        $html = '<img src="a.jpg" /><img src="b.jpg" />';
        self::assertSame(2, preg_match_all(Table::IMAGE_MATCH_STRING, $html, $matches));
        self::assertSame(['a.jpg', 'b.jpg'], $matches[1]);
    }

    // ------------------------------------------------------------------
    // _serialize — generates XML from object properties
    // ------------------------------------------------------------------

    private function tableInstance(): Table
    {
        return (new ReflectionClass(Table::class))->newInstanceWithoutConstructor();
    }

    public function testSerializeEmptyObjectProducesTag(): void
    {
        $r = new ReflectionMethod(Table::class, '_serialize');
        $table = $this->tableInstance();
        $result = $r->invoke($table);
        // With no properties, should produce an empty table tag
        self::assertStringContainsString('<table>', $result);
        self::assertStringContainsString('</table>', $result);
    }

    public function testSerializeWithScalarProperties(): void
    {
        $r = new ReflectionMethod(Table::class, '_serialize');
        $table = $this->tableInstance();
        $table->id = 42;
        $table->title = 'Test Article';
        $result = $r->invoke($table);
        self::assertStringContainsString('<id>42</id>', $result);
        self::assertStringContainsString('<title>', $result);
        self::assertStringContainsString('Test Article', $result);
    }

    public function testSerializeExcludesUnderscoreProperties(): void
    {
        $r = new ReflectionMethod(Table::class, '_serialize');
        $table = $this->tableInstance();
        $table->id = 1;
        $table->_internal = 'secret';
        $result = $r->invoke($table);
        self::assertStringContainsString('<id>1</id>', $result);
        self::assertStringNotContainsString('secret', $result);
        self::assertStringNotContainsString('_internal', $result);
    }

    public function testSerializeExcludesExcludedFields(): void
    {
        $r = new ReflectionMethod(Table::class, '_serialize');
        $table = $this->tableInstance();

        // Set up the excluded list (normally done in constructor)
        $excludedProp = new ReflectionProperty(Table::class, 'excluded');
        $excludedProp->setValue($table, ['asset_id', 'parent_id', 'lft', 'rgt', 'level', 'checked_out', 'checked_out_time']);

        $table->id = 1;
        $table->asset_id = '99';
        $table->title = 'Test';
        $result = $r->invoke($table);
        self::assertStringContainsString('<id>1</id>', $result);
        self::assertStringNotContainsString('asset_id', $result);
        self::assertStringNotContainsString('99', $result);
    }

    public function testSerializeWithoutTagProducesFragmentOnly(): void
    {
        $r = new ReflectionMethod(Table::class, '_serialize');
        $table = $this->tableInstance();
        $table->id = 5;
        $result = $r->invoke($table, false);
        // Without tag, should not have <table> wrapper
        self::assertStringContainsString('<id>5</id>', $result);
        self::assertStringNotContainsString('<table>', $result);
    }

    public function testSerializeJsonEncodesJsonFields(): void
    {
        $r = new ReflectionMethod(Table::class, '_serialize');
        $table = $this->tableInstance();

        $jsonProp = new ReflectionProperty(Table::class, 'jsonEncode');
        $jsonProp->setValue($table, ['params']);

        $table->id = 1;
        $table->params = '{"show_title":"1"}';
        $result = $r->invoke($table);
        self::assertStringContainsString('<id>1</id>', $result);
        self::assertStringContainsString('<params>', $result);
    }

    // ------------------------------------------------------------------
    // toXML — public wrapper around _serialize
    // ------------------------------------------------------------------

    public function testToXmlProducesTaggedOutput(): void
    {
        $table = $this->tableInstance();
        $table->id = 7;
        $table->title = 'Hello';
        $result = $table->toXML();
        self::assertStringContainsString('<table>', $result);
        self::assertStringContainsString('<id>7</id>', $result);
        self::assertStringContainsString('Hello', $result);
        self::assertStringContainsString('</table>', $result);
    }

    // ------------------------------------------------------------------
    // Tag::convertPathsToIds — edge cases without DB
    // ------------------------------------------------------------------

    public function testConvertPathsToIdsEmptyReturnsInput(): void
    {
        $result = Tag::convertPathsToIds([]);
        self::assertSame([], $result);
    }

    public function testConvertPathsToIdsNullReturnsNull(): void
    {
        $result = Tag::convertPathsToIds(null);
        self::assertNull($result);
    }

    public function testConvertPathsToIdsReturnsDatabaseIds(): void
    {
        $query = new class implements \Joomla\Database\QueryInterface {
            public function clear(?string $clause = null): self { return $this; }
            public function select($value): self { return $this; }
            public function from($value): self { return $this; }
            public function where($value): self { return $this; }
        };
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quote')->willReturnCallback(static fn ($value): string => "'" . $value . "'");
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadColumn')->willReturn([4, 7]);

        self::assertSame([4, 7], Tag::convertPathsToIds(['news', 'news', 'blog'], $db));
    }

    public function testConvertPathsToIdsReturnsFalseOnDatabaseFailure(): void
    {
        $query = new class implements \Joomla\Database\QueryInterface {
            public function clear(?string $clause = null): self { return $this; }
            public function select($value): self { return $this; }
            public function from($value): self { return $this; }
            public function where($value): self { return $this; }
        };
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quote')->willReturnCallback(static fn ($value): string => "'" . $value . "'");
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadColumn')->willThrowException(new RuntimeException('database failure'));

        self::assertFalse(Tag::convertPathsToIds(['news'], $db));
    }

    // ------------------------------------------------------------------
    // Shared export/import helpers
    // ------------------------------------------------------------------

    private function querySpy(): \Joomla\Database\QueryInterface
    {
        return new class implements \Joomla\Database\QueryInterface {
            public array $parts = [];

            public function clear(?string $clause = null): self { return $this; }
            public function select($value): self { $this->parts[] = 'SELECT ' . $value; return $this; }
            public function from($value): self { $this->parts[] = 'FROM ' . $value; return $this; }
            public function join($type, $value): self { $this->parts[] = $type . ' JOIN ' . $value; return $this; }
            public function where($value): self { $this->parts[] = 'WHERE ' . (is_array($value) ? implode(' AND ', $value) : $value); return $this; }
            public function concatenate(array $columns, string $separator): string { return 'CONCAT(' . implode(',', $columns) . ')'; }
            public function __toString(): string { return implode(' ', $this->parts); }
        };
    }

    private function tableWithDatabase(DatabaseInterface $db): Table
    {
        return new class($db) extends Table {
            private DatabaseInterface $database;

            public function __construct(DatabaseInterface $db)
            {
                $this->database = $db;
            }

            public function getDatabase(): DatabaseInterface
            {
                return $this->database;
            }
        };
    }

    public function testBuildTagAliasUsesSharedTagQuery(): void
    {
        $query = $this->querySpy();
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(static fn (string $value): string => $value);
        $db->method('quote')->willReturnCallback(static fn ($value): string => "'" . $value . "'");

        $table = $this->tableWithDatabase($db);
        $table->id = 12;
        $method = new ReflectionMethod(Table::class, 'buildTagAlias');
        $method->invoke($table, 'com_content.article');

        $aliases = new ReflectionProperty(Table::class, 'aliases');
        $sql = $aliases->getValue($table)['tag'];
        self::assertStringContainsString('SELECT t.path', $sql);
        self::assertStringContainsString('#__contentitem_tag_map', $sql);
        self::assertStringContainsString("type_alias = 'com_content.article'", $sql);
        self::assertStringContainsString("m.content_item_id = '12'", $sql);
    }

    public function testBuildAssociationAliasUsesSharedAssociationQuery(): void
    {
        $query = $this->querySpy();
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(static fn (string $value): string => $value);
        $db->method('quote')->willReturnCallback(static fn ($value): string => "'" . $value . "'");

        $table = $this->tableWithDatabase($db);
        $table->id = 12;
        $method = new ReflectionMethod(Table::class, 'buildAssociationAlias');
        $method->invoke($table, '#__content', 'com_content.item');

        $aliases = new ReflectionProperty(Table::class, 'aliases');
        $sql = $aliases->getValue($table)['association'];
        self::assertStringContainsString('#__associations', $sql);
        self::assertStringContainsString('#__content', $sql);
        self::assertStringContainsString('#__categories', $sql);
        self::assertStringContainsString("asso1.context = 'com_content.item'", $sql);
        self::assertStringContainsString('asso1.id = 12', $sql);
    }

    public function testIsExportedDetectsExistingItem(): void
    {
        $xml = new SimpleXMLElement('<j2xml><content><id>12</id></content></j2xml>');
        self::assertTrue(self::invokeStatic('isExported', [&$xml, 'content', 12]));
        self::assertFalse(self::invokeStatic('isExported', [&$xml, 'content', 13]));
    }

    public function testAppendItemXmlAddsSerializedItem(): void
    {
        $xml = new SimpleXMLElement('<j2xml />');
        $item = $this->tableInstance();
        $item->id = 7;
        $item->title = 'Shared helper';

        self::invokeStatic('appendItemXml', [$item, &$xml]);

        self::assertSame('7', (string) $xml->table->id);
        self::assertStringContainsString('Shared helper', $xml->asXML());
    }

    public function testResolveAssociationDataNormalizesAssociationList(): void
    {
        $query = $this->querySpy();
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(static fn (string $value): string => $value);
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadResult')->willReturn('en-GB');

        $data = ['associationlist' => ['association' => ['news/article']]];
        self::invokeStatic('resolveAssociationData', [&$data, static fn (): int => 25, '#__content', $db]);

        self::assertSame(['en-GB' => 25], $data['associations']);
        self::assertArrayNotHasKey('associationlist', $data);
    }

    public function testResolveAssociationDataIgnoresStarLanguage(): void
    {
        $query = $this->querySpy();
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(static fn (string $value): string => $value);
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadResult')->willReturn('*');

        $data = ['association' => 'news/article'];
        self::invokeStatic('resolveAssociationData', [&$data, static fn (): int => 25, '#__content', $db]);

        self::assertSame([], $data['associations']);
        self::assertArrayNotHasKey('association', $data);
    }

    public function testGetCategorisedItemIdResolvesPath(): void
    {
        $query = $this->querySpy();
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(static fn (string $value): string => $value);
        $db->method('quote')->willReturnCallback(static fn ($value): string => "'" . $value . "'");
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadResult')->willReturn(17);

        self::assertSame(17, Table::getArticleId('news/article', 0, $db));
    }
}
