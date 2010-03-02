<?php

class Sabre_TestUtil {

    /**
     * This function deletes all the contents of the temporary directory.
     * 
     * @return void
     */
    static function clearTempDir() {

        self::deleteTree(SABRE_TEMPDIR,false);
    
    }


    static private function deleteTree($path,$deleteRoot = true) {

        foreach(scandir($path) as $node) {

            if ($node=='.' || $node=='..') continue;
            $myPath = $path.'/'. $node;
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
