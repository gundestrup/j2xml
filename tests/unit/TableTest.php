<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use eshiol\J2xml\Table\Table;
use eshiol\J2xml\Table\Tag;

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
        $r->setAccessible(true);
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
        $r->setAccessible(true);

        // Create a Table instance without calling the constructor
        $table = (new ReflectionClass(Table::class))->newInstanceWithoutConstructor();
        $result = $r->invoke($table, 'id', 42);
        self::assertSame('<id>42</id>', $result);
    }

    public function testSetValueStringValue(): void
    {
        $r = new ReflectionMethod(Table::class, '_setValue');
        $r->setAccessible(true);
        $table = (new ReflectionClass(Table::class))->newInstanceWithoutConstructor();
        $result = $r->invoke($table, 'title', 'Hello World');
        self::assertStringContainsString('<title>', $result);
        self::assertStringContainsString('Hello World', $result);
        self::assertStringContainsString('<![CDATA[', $result);
    }

    public function testSetValueEmptyString(): void
    {
        $r = new ReflectionMethod(Table::class, '_setValue');
        $r->setAccessible(true);
        $table = (new ReflectionClass(Table::class))->newInstanceWithoutConstructor();
        $result = $r->invoke($table, 'alias', '');
        self::assertSame('<alias />', $result);
    }

    public function testSetValueObjectWithSingleProperty(): void
    {
        $r = new ReflectionMethod(Table::class, '_setValue');
        $r->setAccessible(true);
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
        $r->setAccessible(true);
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
        $r->setAccessible(true);
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

    // ------------------------------------------------------------------
    // IMAGE_MATCH_STRING constant
    // ------------------------------------------------------------------

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
        $r->setAccessible(true);
        $table = $this->tableInstance();
        $result = $r->invoke($table);
        // With no properties, should produce an empty table tag
        self::assertStringContainsString('<table>', $result);
        self::assertStringContainsString('</table>', $result);
    }

    public function testSerializeWithScalarProperties(): void
    {
        $r = new ReflectionMethod(Table::class, '_serialize');
        $r->setAccessible(true);
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
        $r->setAccessible(true);
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
        $r->setAccessible(true);
        $table = $this->tableInstance();

        // Set up the excluded list (normally done in constructor)
        $excludedProp = new ReflectionProperty(Table::class, '_excluded');
        $excludedProp->setAccessible(true);
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
        $r->setAccessible(true);
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
        $r->setAccessible(true);
        $table = $this->tableInstance();

        $jsonProp = new ReflectionProperty(Table::class, '_jsonEncode');
        $jsonProp->setAccessible(true);
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
}
