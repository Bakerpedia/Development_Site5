<?php

/**
 * Class EasyRecipePlusUpdate
 *
 * This is run after an update to check if there is any specific processing required for the newly installed update
 *
 * May also be called from other locations (e.g. from Settings) if some update condition is present.
 * It will also be called during CRON runs
 */
class EasyRecipePlusUpdate {

    static private $taxonomies;

    public static function check(EasyRecipePlusSettings $settings) {

//        $log = EasyRecipePlusLogger::getLog('update');

        /**
         * If the settings haven't been updated to show that we've created taxonomies, schedule the taxonomy creation.
         * Do this in the background because it may take quite some time especially on underpowered shared servers (10+ secs on our dedicated test server for our moderately sized test blog)
         */
        if (!$settings->taxonomiesCreated) {
            $scheduler = new EasyRecipePlusScheduler(EasyRecipePlusTaxonomies::UPDATE_TAXONOMIES);
            /**
             * If we are running in CRON, set up the hook to catch the update event when it fires
             * Otherwise, get the scheduler to schedule the update via cron right now
             * Both of these situations might occur multiple times before the taxonomy creation is complete. The scheduler will handle that
             */
            if (defined('DOING_CRON')) {
                /**
                 * If the job isn't already running, set it so but allow it to timeout after 10 mins so that if it fails, it won't be flagged as running forever
                 * Then setup the hook to actually do the work
                 */
                if (!$scheduler->isRunning()) {
                    self::$taxonomies = new EasyRecipePlusTaxonomies($scheduler);
                    add_action(EasyRecipePlusTaxonomies::UPDATE_TAXONOMIES, array(self::$taxonomies, 'updateAll'));
                }
            } else {
                $scheduler->runNow();
            }
        }

    }
    /**
     * Version 2802 installed into easyrecipeplus.tmp
     * Unfortunately, although WP creates the .tmp directory if the zip install file doesn't have the correct directory structure, it doesn't remove the .tmp directory on the next (correct) update
     * TODO - remove this once all the affected sites get fixed
     */
    public static function check2802() {
        /**
         * We only need to fix this if the .tmp directory exists
         */
        $badDirectory = WP_PLUGIN_DIR . '/easyrecipeplus.tmp';
        if (!file_exists($badDirectory)) {
            return;
        }
        /**
         * We should never run this code from the plugin in the .tmp directory, but be paranoid - it would be disastrous to do so
         */
        if (strpos(EasyRecipePlus::$EasyRecipePlusDir, 'easyrecipeplus.tmp') !== false) {
            return;
        }
        /**
         * Completely remove the easyrecipeplus.tmp directory
         */
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($badDirectory), RecursiveIteratorIterator::CHILD_FIRST);

        /** @var SplFileInfo $fileinfo */
        foreach ($files as $fileinfo) {
            $fileName = $fileinfo->getFilename();
            if ($fileName != '.' && $fileName != '..') {
                if ($fileinfo->isDir()) {
                    @rmdir($fileinfo->getRealPath());
                } else {
                    @unlink($fileinfo->getRealPath());
                }
            }
        }

        @rmdir($badDirectory);
    }
}

