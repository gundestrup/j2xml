<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use eshiol\J2xml\Messages;

/**
 * Unit tests for eshiol\J2xml\Messages.
 *
 * Messages is a static array of message keys — no framework dependencies.
 */
final class MessagesTest extends TestCase
{
    public function testMessagesArrayHasExpectedCount(): void
    {
        // Count non-null entries (some indices are skipped)
        $count = count(array_filter(Messages::$messages, fn ($v) => $v !== null));
        self::assertGreaterThan(30, $count);
    }

    public function testMessagesContainsArticleImportedKey(): void
    {
        self::assertContains('LIB_J2XML_MSG_ARTICLE_IMPORTED', Messages::$messages);
    }

    public function testMessagesContainsArticleNotImportedKey(): void
    {
        self::assertContains('LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED', Messages::$messages);
    }

    public function testMessagesContainsUserImportedKey(): void
    {
        self::assertContains('LIB_J2XML_MSG_USER_IMPORTED', Messages::$messages);
    }

    public function testMessagesContainsCategoryImportedKey(): void
    {
        self::assertContains('LIB_J2XML_MSG_CATEGORY_IMPORTED', Messages::$messages);
    }

    public function testMessagesContainsTagImportedKey(): void
    {
        self::assertContains('LIB_J2XML_MSG_TAG_IMPORTED', Messages::$messages);
    }

    public function testMessagesContainsMenuImportedKey(): void
    {
        self::assertContains('LIB_J2XML_MSG_MENU_IMPORTED', Messages::$messages);
    }

    public function testMessagesContainsModuleImportedKey(): void
    {
        self::assertContains('LIB_J2XML_MSG_MODULE_IMPORTED', Messages::$messages);
    }

    public function testMessagesContainsFieldImportedKey(): void
    {
        self::assertContains('LIB_J2XML_MSG_FIELD_IMPORTED', Messages::$messages);
    }

    public function testMessagesContainsUsernoteImportedKey(): void
    {
        self::assertContains('LIB_J2XML_MSG_USERNOTE_IMPORTED', Messages::$messages);
    }

    public function testMessagesContainsUserSkippedKey(): void
    {
        self::assertContains('LIB_J2XML_MSG_USER_SKIPPED', Messages::$messages);
    }

    public function testMessagesIndex15IsXmlrpcNotSupported(): void
    {
        self::assertSame('LIB_J2XML_MSG_XMLRPC_NOT_SUPPORTED', Messages::$messages[15]);
    }

    public function testMessagesIndex6IsCategoryImported(): void
    {
        self::assertSame('LIB_J2XML_MSG_CATEGORY_IMPORTED', Messages::$messages[6]);
    }

    public function testMessagesIndex32IsXmlrpcDisabled(): void
    {
        self::assertSame('LIB_J2XML_MSG_XMLRPC_DISABLED', Messages::$messages[32]);
    }

    public function testAllEntriesAreNonEmptyStrings(): void
    {
        foreach (Messages::$messages as $key => $value) {
            if ($value === null) {
                continue;
            }
            self::assertIsString($value, "Index $key should be a string");
            self::assertNotEmpty($value, "Index $key should not be empty");
        }
    }
}
