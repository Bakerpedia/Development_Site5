<?php
/**
 * Logging module
 *
 * Relies on EasyLogger being installed and active - does nothing if it's not
 */
/**
 * If EasyLogger isn't installed, create stub classes
 */
if (!class_exists('EasyLogger', false)) {
    class EasyRecipePlusLogger {
        /**
         * @param $logfile
         * @return EasyLoggerLog
         */
        static function getLog(/** @noinspection PhpUnusedParameterInspection */
            $logfile) {
            return new EasyLoggerLog();
        }
    }

    if (!class_exists('EasyLoggerLog', false)) {
        class EasyLoggerLog {

            function comment($msg) {
            }

            function disable($level) {
            }

            function enable($level) {
            }

            function debug($msg) {
            }

            function info($msg) {
            }

            function warn($msg) {
            }

            function error($msg) {
            }

            function fatal($msg) {
            }
        }
    }
} else {
    /**
     * Class EasyRecipePlusLogger
     *
     * Use the real EasyLogger
     */
    class EasyRecipePlusLogger {
        /**
         * @param string $logfile
         *
         * @return EasyLoggerLog
         */
        static function getLog($logfile) {
            return EasyLogger::getLog($logfile);
        }
    }

}

