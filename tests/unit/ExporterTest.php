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
use eshiol\J2xml\Exporter;
use eshiol\J2xml\Version;

/**
 * Unit tests for eshiol\J2xml\Exporter — pure-logic methods.
 *
 * _root() creates the root SimpleXMLElement for export. It uses
 * Uri::root() which is stubbed, so it can be tested via reflection
 * without a database or application instance.
 */
final class ExporterTest extends TestCase
{
    /**
     * Call _root() without invoking the constructor (which needs a DB + app).
     */
    private static function callRoot(): SimpleXMLElement
    {
        $exporter = (new ReflectionClass(Exporter::class))->newInstanceWithoutConstructor();
        $r = new ReflectionMethod(Exporter::class, '_root');
        return $r->invoke($exporter);
    }

    public function testRootReturnsSimpleXMLElement(): void
    {
        $xml = self::callRoot();
        self::assertInstanceOf(SimpleXMLElement::class, $xml);
    }

    public function testRootHasJ2xmlTagWithVersion(): void
    {
        $xml = self::callRoot();
        self::assertSame('j2xml', $xml->getName());
        self::assertSame(Version::$DOCVERSION, (string) $xml['version']);
    }

    public function testRootHasBaseChild(): void
    {
        $xml = self::callRoot();
        self::assertNotNull($xml->base);
    }

    public function testRootBaseIsString(): void
    {
        $xml = self::callRoot();
        self::assertIsString((string) $xml->base);
    }
}
