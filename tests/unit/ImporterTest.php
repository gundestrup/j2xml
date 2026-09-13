<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use eshiol\J2xml\Importer;

/**
 * Unit tests for eshiol\J2xml\Importer — pure-logic methods.
 *
 * isSupported() is a simple array membership check that doesn't
 * require a database or application instance.
 */
final class ImporterTest extends TestCase
{
    /**
     * Call isSupported without invoking the constructor (which needs a DB).
     */
    private static function callIsSupported(string $version): bool
    {
        $importer = (new ReflectionClass(Importer::class))->newInstanceWithoutConstructor();
        return $importer->isSupported($version);
    }

    public function testIsSupportedReturnsTrueFor211200(): void
    {
        self::assertTrue(self::callIsSupported('211200'));
    }

    public function testIsSupportedReturnsTrueFor190200(): void
    {
        self::assertTrue(self::callIsSupported('190200'));
    }

    public function testIsSupportedReturnsTrueFor150900(): void
    {
        self::assertTrue(self::callIsSupported('150900'));
    }

    public function testIsSupportedReturnsTrueFor120500(): void
    {
        self::assertTrue(self::callIsSupported('120500'));
    }

    public function testIsSupportedReturnsFalseForUnknownVersion(): void
    {
        self::assertFalse(self::callIsSupported('999999'));
    }

    public function testIsSupportedReturnsFalseForEmptyString(): void
    {
        self::assertFalse(self::callIsSupported(''));
    }

    public function testIsSupportedReturnsFalseForOldVersion(): void
    {
        self::assertFalse(self::callIsSupported('100000'));
    }
}
