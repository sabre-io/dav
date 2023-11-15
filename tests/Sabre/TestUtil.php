<?php

declare(strict_types=1);

namespace Sabre;

class TestUtil
{
    public const SABRE_TEMPDIR = __DIR__.'/../temp/';

    /**
     * This function deletes all the contents of the temporary directory.
     */
    public static function clearTempDir(): void
    {
        self::deleteTree(self::SABRE_TEMPDIR, false);
    }

    private static function deleteTree($path, $deleteRoot = true): void
    {
        foreach (scandir($path) as $node) {
            if ('.' == $node || '..' == $node) {
                continue;
            }
            $myPath = $path.'/'.$node;
            if (is_file($myPath)) {
                unlink($myPath);
            } else {
                self::deleteTree($myPath);
            }
        }
        if ($deleteRoot) {
            rmdir($path);
        }
    }
}
