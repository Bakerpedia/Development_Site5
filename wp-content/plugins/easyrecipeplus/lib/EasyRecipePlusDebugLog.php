<?php


/**
 * Class EasyRecipePlusDebugLog
 *
 * Logs stuff to help track down bugs that are otherwise impossible to trace without server/xdebug access
 */
class EasyRecipePlusDebugLog {
    private $logDirectory;

    /**
     * Sets up a log directory. Assumed to be in uploads/___PluginName___ unless it's explicitly passed
     * Not much we can do if we get errors trying to find somewhere to write the log file
     *
     * @param string $logDirectory
     */
    function __construct($logDirectory = '') {
        if (!empty($logDirectory)) {
            $this->logDirectory = $logDirectory;
        } else {
            $uploadDirectory = wp_upload_dir();
            if ($uploadDirectory['error'] !== false) {
                return;
            }
            /**
             * Normalize the path just in case we're running on a Windows server
             */
            $this->logDirectory = rtrim(str_replace('\\', '/', $uploadDirectory['basedir']) . "/EasyRecipePlus", '/');
        }
        /**
         * If the log directory isn't present and a directory, try to create it
         */
        if (!file_exists($this->logDirectory)) {
            @mkdir($this->logDirectory, 0777, true);
        }
        /**
         * If the log directory doesn't exist, isn't writeable and/or isn't a directory, then we're screwed
         */
        if (!file_exists($this->logDirectory) || !is_writable($this->logDirectory) || !is_dir($this->logDirectory)) {
            $this->logDirectory = null;
        }
        /**
         * Just in case we have a server with autoindexing set ON, write a dummy index.html so the log file names can't be accessed from the web
         */
        if (!file_exists($this->logDirectory . "/index.html")) {
            file_put_contents($this->logDirectory . "/index.html", "<html><body></body></html>");
        }
    }

    function setHooks() {
        /**
         * Try to catch where recipe HTML corruption occurs
         * We assume it's during an update that occurs in some circumstance we haven't allowed for
         */
        add_action('pre_post_update', array($this, 'prePostUpdate'), 10, 2);
        add_action('post_updated', array($this, 'postUpdated'), 10, 3);
    }

    private function getFileName() {
        return sprintf("%s/%s-%d.log", $this->logDirectory, date('ymd-His'), getmypid());
    }

    /**
     * Check if the update that's about to occur is going to trash the recipe HTML
     *
     * @param int $postID
     * @param array $data
     */
    function prePostUpdate($postID, $data) {
        /**
         * Do a quick check to make sure we actually have content and that there's an EasyRecipe in it
         */
        if (empty($data['post_content']) || strpos($data['post_content'], 'endeasyrecipe') === false) {
            return;
        }
        /**
         * Just  return if all is OK
         * The presence of the title attribute in the endeasyrecipe div indicates a corrupt recipe
         */
        if (!preg_match('/<div class="endeasyrecipe"[^>]+title=/', $data['post_content'])) {
            return;
        }
        /**
         * The data is bad - log anything that might help track down what's going on
         */
        $debugData = new stdClass();
        $debugData->hook = "Pre post";
        $debugData->postID = $postID;
        $debugData->data = $data;
        $debugData->server = $_SERVER;
        $debugData->get = empty($_GET) ? array() : $_GET;
        $debugData->post = empty($_POST) ? array() : $_POST;
        $debugData->backtrace = debug_backtrace();
        file_put_contents($this->getFileName(), serialize($debugData));

    }

    /**
     * @param int $postID
     * @param WP_Post $postAfter
     * @param WP_Post $postBefore
     */
    function postUpdated($postID, $postAfter, $postBefore) {

        /**
         * Do a quick check to make sure we actually have content and that there's an EasyRecipe in it
         */
        if (empty($postAfter->post_content) || strpos($postAfter->post_content, 'endeasyrecipe') === false) {
            return;
        }
        /**
         * Just  return if all is OK
         * The presence of the title attribute in the endeasyrecipe div indicates a corrupt recipe
         */
        if (!preg_match('/<div class="endeasyrecipe"[^>]+title=/', $postAfter->post_content)) {
            return;
        }
        /**
         * The data is bad - log anything that might help track down what's going on
         */
        $debugData = new stdClass();
        $debugData->hook = "Post updated";
        $debugData->postID = $postID;
        $debugData->postBefore = $postBefore;
        $debugData->postAfter = $postAfter;
        $debugData->server = $_SERVER;
        $debugData->get = empty($_GET) ? array() : $_GET;
        $debugData->post = empty($_POST) ? array() : $_POST;
        $debugData->backtrace = debug_backtrace();
        $sData = serialize($debugData);
        $usData = unserialize($sData);
        file_put_contents($this->getFileName(), serialize($debugData));
    }

    /**
     * Lists any debug logs
     */
    function showLogs() {
        /* @var $wp_rewrite WP_Rewrite */
        global $wp_rewrite;

        $siteDiagnosticsURL = home_url();
        if (!$wp_rewrite->using_permalinks()) {
            $siteDiagnosticsURL .= "?";
        }

        $files = scandir($this->logDirectory, SCANDIR_SORT_DESCENDING);
        $data = new stdClass();

        $data->LOGS = array();
        foreach ($files as $file) {
            if ($file == '.' || $file == '..' || $file == 'index.html') {
                continue;
            }
            $item = new stdClass();
            $item->logfile = $file;
            $item->siteDiagnosticsURL = $siteDiagnosticsURL;
            $data->LOGS[] = $item;
        }
        $template = new EasyRecipePlusTemplate(EasyRecipePlus::$EasyRecipePlusDir . "/templates/easyrecipe-debuglogs.html");
        $html = $template->replace($data);
        header("HTTP/1.1 200 OK");
        header("Content-Length: " . strlen($html));
        echo $html;
        exit();
    }

    /**
     * @param string $logFile
     */
    function showLog($logFile) {
        $serialized = file_get_contents($this->logDirectory . "/$logFile");
        $data = unserialize($serialized);
        echo "<pre>\n";
        foreach ($data as $key => $value) {
            echo "<h2>$key</h2>\n";
            echo htmlspecialchars(print_r($value,true));
        }

        exit;
    }
}
