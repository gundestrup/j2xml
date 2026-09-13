<?php

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
        $r->setAccessible(true);
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
