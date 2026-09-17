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
use eshiol\J2xml\Version;

/**
 * Unit tests for eshiol\J2xml\Version.
 *
 * Version has no Joomla framework dependencies beyond the _JEXEC guard,
 * so all four public methods can be tested directly.
 */
final class VersionTest extends TestCase
{
    public function testGetShortVersionReturnsReleaseAndDevLevel(): void
    {
        $result = Version::getShortVersion();
        self::assertSame(Version::$RELEASE . '.' . Version::$DEV_LEVEL, $result);
    }

    public function testGetFullVersionIncludesBuild(): void
    {
        $result = Version::getFullVersion();
        $expected = Version::$RELEASE . '.' . Version::$DEV_LEVEL;
        if (Version::$DEV_STATUS) {
            $expected .= '-' . Version::$DEV_STATUS;
        }
        $expected .= '.' . Version::$BUILD;
        self::assertSame($expected, $result);
    }

    public function testGetLongVersionIncludesCodename(): void
    {
        $result = Version::getLongVersion();
        self::assertStringContainsString(Version::$RELEASE, $result);
        self::assertStringContainsString(Version::$DEV_LEVEL, $result);
        self::assertStringContainsString('build', $result);
        self::assertStringContainsString(Version::$BUILD, $result);
    }

    public function testDocversionCompareEqualReturnsZero(): void
    {
        self::assertSame(0, Version::docversion_compare(Version::$DOCVERSION));
    }

    public function testDocversionCompareOlderReturnsNegativeOne(): void
    {
        self::assertSame(-1, Version::docversion_compare('1.0.0'));
    }

    public function testDocversionCompareNewerReturnsOne(): void
    {
        self::assertSame(1, Version::docversion_compare('99.99.99'));
    }

    public function testDocversionCompareSameMajorMinorReturnsZero(): void
    {
        // 21.12.0 vs 21.12.0 → equal
        self::assertSame(0, Version::docversion_compare('21.12.0'));
    }

    public function testDocversionCompareNewerMinorReturnsOne(): void
    {
        // 21.13.0 vs 21.12.0 → newer
        self::assertSame(1, Version::docversion_compare('21.13.0'));
    }

    public function testDocversionCompareOlderMinorReturnsNegativeOne(): void
    {
        // 21.11.0 vs 21.12.0 → older
        self::assertSame(-1, Version::docversion_compare('21.11.0'));
    }

    public function testDocversionCompareTrailingZonesStripped(): void
    {
        // 21.12 vs 21.12.0 → rtrim(".0") on "21.12" leaves "21.12" (ends with '2'),
        // so both arrays are ["21","12"] → equal → 0
        self::assertSame(0, Version::docversion_compare('21.12'));
    }

    public function testProductIsJ2xml(): void
    {
        self::assertSame('J2XML', Version::$PRODUCT);
    }

    public function testDocversionIsString(): void
    {
        self::assertIsString(Version::$DOCVERSION);
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Version::$DOCVERSION);
    }
}
