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
