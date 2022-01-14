<?php

declare(strict_types=1);

namespace Kelly;

final class Storage
{
    private const ITEM_PATTERN = "%s\n%s\n";

    private const KEY_PATTERN = "%s\n";

    private const DELETED_POSTFIX = 'deleted';

    private const KEY_POSTFIX = 'key';

    /**
     * @var int parameter which configure key distribution between files, 1 - means that all keys will be distributed
     * between 10^1 = 10 files, 2 - 100 files, 3 - 1000 files. Max value is 10.
     * @todo move to config
     */
    private const GRANULARITY = 1;

    public static function add(string $key, $value)
    {
        $filename = static::getFileName($key);

        if (false ===  file_put_contents($filename, sprintf(self::ITEM_PATTERN, $key, $value) . file_get_contents($filename))) {
            throw new \RuntimeException('Error while saving to file');
        }

        static::removeFromDeleted($key);
    }

    public static function update(string $key, $value)
    {
        static::add($key, $value);
    }

    public static function delete(string $key)
    {
        $deletedKeys = self::openFile($key, self::DELETED_POSTFIX);

        if (static::isDeleted($key)) {
            return;
        }

        if (false === fwrite($deletedKeys, sprintf(self::KEY_PATTERN, $key))) {
            throw new \RuntimeException('Error while saving to file');
        }

        // @todo maybe need to return value
    }

    private static function isDeleted(string $key): bool
    {
        // @todo how about storing keys in memory
        $lines = self::openFileAsLines($key, self::DELETED_POSTFIX);

        if (null === $lines) {
            false;
        }

        // @todo use more optimized for search structure, ex. binary tree or skip list
        foreach ($lines as $index => $line) {
            if ($key === $line) {
                return true;
            }
        }

        return false;
    }

    private static function removeFromDeleted(string $key)
    {
        $lines = self::openFileAsLines($key, self::DELETED_POSTFIX);

        if (null === $lines) {
            return;
        }

        foreach ($lines as $index => $line) {
            if ($key === $line) {
                unset($lines[$index]);

                file_put_contents(self::getFileName($key, self::DELETED_POSTFIX), implode("\n", $lines));
            }
        }
    }

    public static function get(string $key)
    {
        if (static::isDeleted($key)) {
            return null;
        }

        $lines = static::openFileAsLines($key);

        foreach ($lines as $index => $line) {
            if ($key === $line) {
                return $lines[$index + 1];
            }
        }

        return null;
    }

    private static function openFileAsLines(string $key, ?string $postfix = null): ?array
    {
        $filename = static::getFileName($key, $postfix);
        if (!file_exists($filename)) {
            return null;
        }

        return file($filename, FILE_IGNORE_NEW_LINES);
    }

    private static function openFile(string $key, string $postfix = null)
    {
        if (false === ($file = fopen(static::getFileName($key,$postfix), 'a+'))) {
            throw new \RuntimeException('Cannot open storage file');
        }

        return $file;
    }

    private static function getFileName(string $filename, ?string $postfix = null): string
    {
        $postfix = null === $postfix ? '' : ('_' . $postfix);

        $filename = substr((string)crc32($filename), -1 * self::GRANULARITY) . $postfix;

        return __DIR__ . '/../data/' . $filename;
    }
}