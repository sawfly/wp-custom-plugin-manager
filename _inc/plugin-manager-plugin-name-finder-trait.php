<?php

/**
 * Trait Plugin_Manager_Plugin_Name_Finder_Trait
 * Find Plugin Name in file that passed by file pointer
 */
trait Plugin_Manager_Plugin_Name_Finder_Trait
{
    /**
     * Looks for Plugin Name inside comment in php code
     * @param $fp pointer on the opened file
     *
     * @return bool
     */
    protected function findByFilePointer(&$fp){
        $isTagOpened = false;
        $isTagClosed = false;
        $isCommentsStarted = false;
        $isCommentsEnded = false;

        while ($string = fgets($fp)) {
            if (trim($string) === '<?php' || trim($string) === '<?') {
                $isTagOpened = true;
            }
            if (!$isTagOpened) {
                continue;
            }
            if (trim($string) === '?>') {
                $isTagClosed = true;
            }
            if ($isTagClosed) {
                continue;
            }
            if (strpos($string, '/**') !== false) {
                $isCommentsStarted = true;
            }
            if (!$isCommentsStarted) {
                continue;
            }
            if (strpos($string, '*/') !== false) {
                $isCommentsEnded = true;
            }
            if ($isCommentsEnded) {
                continue;
            }

            preg_match('/Plugin +Name *:([\s\S]+)$/i', $string, $m);

            if (!empty($m)) {
                return true;
            }
        }

        return false;
    }
}