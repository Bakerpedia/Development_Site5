<?php

/*
 Copyright (c) 2010-2015 Box Hill LLC
All Rights Reserved
No part of this software may be reproduced, copied, modified or adapted, without the prior written consent of Box Hill LLC.
Commercial use and distribution of any part of this software is not allowed without express and prior written consent of Box Hill LLC.
 */

/**
 * Class EasyRecipePlus
 */
class EasyRecipePlus {

    public static $EasyRecipePlusDir;
    public static $EasyRecipePlusUrl;

    public static $pluginVersion;

    private $pluginName = 'EasyRecipePlus';

    const VERSIONCHECKURL = "http://www.easyrecipeplugin.com/checkVersion.php";

    const DIAGNOSTICS_URL = 'http://support.easyrecipeplugin.com/wp-admin/admin-ajax.php';

    const SWOOPJS = '<script type="text/javascript" id="spxw_script" src="http://ardrone.swoop.com/js/spxw.js" data-domain="%s" data-theme="red" data-serverbase="http://ardrone.swoop.com/"></script>';
    const BIGOVENBUTTON = '<a href="" class="ERSSaveBtn bigoven">Save</a>';

    const ZIPLISTJS = "<script type='text/javascript' src='http://www.ziplist.com/javascripts/wk.js'></script>";
    const ZIPLISTBUTTON = "<a class='ziplist-button add-recipe large ERSSaveBtn' href='http://www.ziplist.com/webkitchen/button/add_recipe?as_partner=%s&amp;url=%s' target='_blank'>%s</a>";
    const GOOGLESNIPPETURL = 'https://developers.google.com/structured-data/testing-tool?url=%s';


// FIXME - display yield on style 6 if no nutrition data

    const ENDPOINTREGEX = '%/easyrecipe-(print|diagnostics|style|printstyle)(?:/([^?/]+))?%';

    /** @var EasyRecipePlusSettings */
    private $settings;

    private $postContent = array();
    private $recipesHTML = array();

    private $easyrecipes = array();
    private $formatting = false;
    private $styleName;
    private $styleData;
    public $isGuest = false;
    private $postMeta;
    private $guestPosters = array();

    private $loadJSInFooter = false;

    private $homeURL;

    private $filterExcerpt;

    private $uiVersion = '';
    private $mceVersion = '';
    private $wpVersion = '';
    private $pluploadVersion = '';

    private $ratingMethod;

    /**
     * @var WP_Post
     */
    private $genesisPost;

    private $converted;
    private $convertedType;


    function __construct($pluginDir, $pluginURL, $version) {




        /**
         * For convenience
         *
         * Save some gulp build time by not having to reprocess this file every time the version changes
         * Fix up HTTP protocol for admin using SSL
         */
        self::$pluginVersion = $version;

        self::$EasyRecipePlusDir = $pluginDir;
        self::$EasyRecipePlusUrl = is_ssl() ? preg_replace('%^http://%i', 'https://', $pluginURL) : $pluginURL;

        $this->homeURL = home_url();

        add_action('wp_ajax_nopriv_easyrecipeUpload', array($this, 'guestPostUpload'));
        add_action('wp_ajax_easyrecipeUpload', array($this, 'guestPostUpload'));

        add_action('wp_ajax_easyrecipeImport', array($this, 'importUpload'));
        add_action('wp_ajax_easyrecipeCreate', array($this, 'importCreate'));

        add_action('wp_ajax_easyrecipeSnippet', array($this, 'getSnippet'));


        /**
         * Delay adding any hooks until we check other plugiuns
         */
        add_action('plugins_loaded', array($this, 'pluginsLoaded'));

    }

    /**
     * If this is EasyRecipe Free, make sure we don't already have EasyRecipe Plus running
     * If not, go ahead and add our hooks
     */
    function pluginsLoaded() {
        global $wp_version;


        /**
         * If EasyRecipe Beta is installed and active, then don't do any more  processing at all for this (plus) version
         */
        $plugins = get_option('active_plugins', array());
        if (in_array("easyrecipebeta/easyrecipebeta.php", $plugins)) {
            add_action('admin_notices', array($this, 'showBetaActive'));
            return;
        }

        /**
         * The new jQuery UI and tinymce versions introduced in WP 3.9 required major changes to EasyRecipe code and UI CSS
         * It's just a lot easier (and safer) to create separate JS and CSS sources for WP 3.9+
         */
        if (version_compare($wp_version, '3.9.dev', '>')) {
            $this->wpVersion = '-3.9';
            $this->uiVersion = '-1.10.4';
            $this->mceVersion = '-4.0.20';
            $this->pluploadVersion = '-2.1.1';
        }
        add_action('admin_menu', array($this, 'addMenus'));
        add_action('admin_init', array($this, 'initialiseAdmin'));
        add_action('init', array($this, 'initialise'));

        add_action('the_post', array($this, 'thePost'));

        /**
         * Need this to explicitly allow the datetime & link tags when future posts are published
         */
        add_action('publish_future_post', array($this, 'publishFuturePost'), 0, 1);
        /**
         * Hook into the fooderific scan run action
         */
        add_action(EasyRecipePlusFooderific::FOODERIFIC_SCAN, array($this, 'fdScan'), 10, 1);

    }

    /**
     * Set up stuff we need if we're on an admin page
     */
    function initialiseAdmin() {

        /**
         * If Foodereific is enabled, hook into the save and status transition actions
         */
        if ($this->settings->enableFooderific) {
            /**
             * Hook into post updates and status transitions as late as possible
             */
            add_action('save_post', array($this, 'fdPostChanged'), 32000, 2);
            add_action('transition_post_status', array($this, 'fdPostStatusChanged'), 32000, 3);
        }

        /**
         * Hook into the post save action to check if we need to tweak taxomonmies for the post
         */
        add_action('save_post', array($this, 'postChanged'), 10, 2);

        /**
         * Need to be able to edit posts at a minimum
         */
        if (!current_user_can('edit_posts')) {
            return;
        }

        /**
         * Only someone who can edit theme options can change the styling
         * This is a better capability to check than edit_plugins which is only for super admins
         */
        if (current_user_can('edit_theme_options')) {
            add_action('wp_ajax_easyrecipeCustomCSS', array($this, 'updateCustomCSS'));
            add_action('wp_ajax_easyrecipeSaveStyle', array($this, 'saveStyle'));
        }

        add_action("load-post.php", array($this, 'loadPostAdmin'));
        add_action("load-post-new.php", array($this, 'loadPostAdmin'));

        add_action('admin_enqueue_scripts', array($this, 'enqueAdminScripts'));
        add_filter('plugin_action_links', array($this, 'pluginActionLinks'), 10, 2);

        add_action('wp_ajax_easyrecipeConvert', array($this, 'convertRecipe'));

        add_action('wp_ajax_easyrecipeSupport', array($this, 'sendSupport'));

        $this->settings = EasyRecipePlusSettings::getInstance();

        /**
         * Don't need update_plugin capability to just check for a new update
         */
        new EasyRecipePlusAutoUpdate(self::$pluginVersion, self::VERSIONCHECKURL, $this->settings->licenseKey, true);

        /**
         * Show the Fooderific admin wp_pointer
         */
        add_action('admin_enqueue_scripts', array($this, 'enqueueFooderificWPPointer'));

        /**
         *  Add the hook that will schedule a site scan if it's requested
         *  A request to this also sets the fooderificEnabled setting to TRUE
         */
        add_action('wp_ajax_easyrecipeScanSchedule', array($this, 'fdScanSchedule'));

    }


    /**
     * Set up stuff we'll need on non-admin pages and stuff we'll need in both admin and non-admin
     */
    function initialise() {

        /**
         * Register our taxonomies now - this must be done before we ever instantiate the settings
         * TODO - what if we want to use custom names/slugs?
         */
        $args = array('public'            => true,
                      'show_admin_column' => false,
                      'show_in_menu'      => false,
                      'show_in_nav_menu'  => false,
                      'show_tagcloud'     => false,
                      'show_ui'           => false,
                      'rewrite'           => false);

        $args['label'] = 'Courses';
        register_taxonomy('course', 'post', $args);
        // enable for pages too?

        $args['label'] = 'Cuisines';
        register_taxonomy('cuisine', 'post', $args);

        wp_register_style("easyrecipe-UI", self::$EasyRecipePlusUrl . "/ui/easyrecipeUI{$this->uiVersion}.css", array('wp-admin', 'wp-pointer'), self::$pluginVersion);

        $this->settings = EasyRecipePlusSettings::getInstance();

        /**
         * Logging for those pesky bugs that are otherwise impossible to track down without server/xdebug access
         */
        if ($this->settings->enableDebugLog) {
            $debugLog = new EasyRecipePlusDebugLog();
            $debugLog->setHooks();
        }
        /*
        * Everything past here is not needed on admin pages
        */
        if (is_admin()) {
            return;
        }

        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));

        /**
         * IF we are filtering out non-display stuff in excerpts, hook into the excerpt stuff
         */
        if ($this->settings->filterExcerpts) {
            add_filter('get_the_excerpt', array($this, 'theExcerpt'), 1);
        }

        /**
         * If this is one of our non-existent pages (print, diagnostics or custom styles) hook in early so 404 handlers don't stuff it up
         */
        if (preg_match('%/easyrecipe-print/(\d+)-(\d)+/?%', $_SERVER['REQUEST_URI'], $regs)) {
            $print = new EasyRecipePlusPrint($this);
            $print->printRecipe($regs[1], $regs[2]);
            exit;
        }
        if (preg_match('%/easyrecipe-(print|diagnostics|style|printstyle|debuglogs|log)(?:/([^?/]+))?%', $_SERVER['REQUEST_URI'])) {
            add_filter('wp_headers', array($this, 'checkRewrites'), 0);
        } else {
            add_action('the_posts', array($this, 'thePosts'), 0);

            /**
             * Insert the admin bar "EasyRecipe Format" menu item if this user can edit theme options
             */
            if (current_user_can('edit_theme_options')) {
                add_action('wp_before_admin_bar_render', array($this, 'adminBarMenu'));
            }
            /**
             * Hook into the comment save if we're using EasyRecipe ratings
             */
            if ($this->settings->ratings == 'EasyRecipe') {
                add_action('comment_post', array($this, 'ratingSave'));
            }
        }
        /*
        * Override the default style for preview?
        */
        if (isset($_REQUEST['style']) && current_user_can("edit_theme_options")) {
            $this->styleName = $_REQUEST['style'];
        } else {
            $this->styleName = $this->settings->style;
        }

        /*
         * Make sure our head gets run before the enqueued stuff is output
        */
        add_action('wp_head', array($this, 'addHead'), 0);
        /*
        * Add the custom CSS very late so it overrides everything else
        */
        add_action('wp_head', array($this, 'addExtraCSS'), 100);
    }



    /**
     * EasyIndex Beta is active - show a message
     */
    function showBetaActive() {

        /** @noinspection PhpUnusedLocalVariableInspection */
        $plus = 'Plus';

        echo <<<EOD
<div id="message" class="updated">
<p>EasyRecipe Beta is installed and active. EasyRecipe $plus is disabled</p>
</div>
EOD;
    }



    /**
     * Add the "EasyRecipe Format" option to the admin bar if the current user is an admin
     */
    function adminBarMenu() {
        /** @var $wp_admin_bar WP_Admin_Bar */
        global $wp_admin_bar;

        $root_menu = array('parent' => false, 'id' => 'ERFormatMenu', 'title' => 'EasyRecipe Format', 'href' => '#');
        $wp_admin_bar->add_menu($root_menu);
    }

    /**
     * Load the EasyRecipe settings page
     */
    function loadSettingsPage() {
        wp_enqueue_style("easyrecipe-UI");
        wp_enqueue_style("easyrecipe-settings", self::$EasyRecipePlusUrl . "/css/easyrecipe-settings-min.css", array('easyrecipe-UI'), self::$pluginVersion);


        wp_enqueue_style("thickbox");
        wp_enqueue_script('thickbox');
        wp_enqueue_script('easyrecipe-settings', self::$EasyRecipePlusUrl . "/js/easyrecipe-settings-min.js", array('jquery-ui-dialog', 'jquery-ui-slider', 'jquery-ui-autocomplete', 'jquery-ui-tabs', 'jquery-ui-button', 'thickbox'), self::$pluginVersion, true);


        wp_enqueue_style("easyrecipe-plupload", self::$EasyRecipePlusUrl . "/plupload/css/jquery.ui.plupload{$this->pluploadVersion}.css", array('easyrecipe-UI'), self::$pluginVersion);
        if ($this->wpVersion == '-3.9') {
            wp_enqueue_script('easyrecipe-plupload', self::$EasyRecipePlusUrl . "/plupload/jquery.ui.plupload{$this->pluploadVersion}.js", array('plupload-all', 'jquery-ui-progressbar', 'jquery-ui-button', 'jquery-ui-sortable'),
                self::$pluginVersion, true);
        } else {
            wp_enqueue_script('plupload');
            wp_enqueue_script('plupload-html5');
            wp_enqueue_script('plupload-flash');
            wp_enqueue_script('plupload-silverlight');
            wp_enqueue_script('plupload-html4');

            wp_enqueue_script('easyrecipe-plupload', self::$EasyRecipePlusUrl . "/plupload/jquery.ui.plupload{$this->pluploadVersion}.js", array('plupload-html4', 'jquery-ui-progressbar', 'jquery-ui-button', 'jquery-ui-sortable'), self::$pluginVersion, true);
        }
        wp_enqueue_script('easyrecipe-import', self::$EasyRecipePlusUrl . "/js/easyrecipe-import-min.js", array('easyrecipe-plupload', 'easyrecipe-settings'), self::$pluginVersion, true);

        $this->settings = EasyRecipePlusSettings::getInstance();
    }

    /**
     */
    function addMenus() {
        $this->settings = EasyRecipePlusSettings::getInstance();
        $hook = add_menu_page('EasyRecipe Settings', 'EasyRecipe', 'manage_options', $this->pluginName, array($this->settings, 'showPage'), self::$EasyRecipePlusUrl . "/images/chef16.png");
        add_action("load-$hook", array($this, 'loadSettingsPage'));
    }

    /**
     * Called before the post admin page is loaded
     * Queue up all the stuff we need
     * Remove the post from the object cache
     */
    function loadPostAdmin() {
        global $typenow;
        /**
         * Custom post types don't necessarily have an editor so don't output EasyRecipe stuff if it's not needed
         * Check the gloabl $typenow instead of the REQUEST - the REQUEST isn't set on post Add
         * TODO - add an option to select which post types EasyRecipe should be active on rather than test individual cases
         */
//        if (empty($_REQUEST['post_type']) || $_REQUEST['post_type'] != 'soliloquy' || $_REQUEST['post_type'] != 'easyindex') {
        if (empty($typenow) || ($typenow != 'soliloquy' && $typenow != 'easyindex' && $typenow != 'sidebar')) {

            wp_enqueue_style("easyrecipe-UI");
            wp_enqueue_style("easyrecipe-entry", self::$EasyRecipePlusUrl . "/css/easyrecipe-entry-min.css", array('easyrecipe-UI'), self::$pluginVersion);

            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_script('jquery-ui-autocomplete');
            wp_enqueue_script('jquery-ui-button');
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_script('easyrecipe-entry', self::$EasyRecipePlusUrl . "/js/easyrecipe-entry{$this->wpVersion}-min.js", array('jquery-ui-dialog', 'jquery-ui-autocomplete', 'jquery-ui-button', 'jquery-ui-tabs'), self::$pluginVersion, true);

            add_filter('tiny_mce_before_init', array($this, 'mcePreInitialise'));
            add_filter('mce_external_plugins', array($this, 'mcePlugins'));
            add_filter('mce_buttons', array($this, 'mceButtons'));
            add_action('admin_footer', array($this, 'addDialogHTML'));

            /**
             * Remove the object cache for this post because we may have cached the post as modified by thePosts() below.
             * Normally this wouldn't be a problem since object caches aren't normaly persistent and don't survive a page load,
             * but they may be persistent if there's a caching plugin installed (e.g. W3 Total Cache)
             */
            if (isset($_REQUEST['post'])) {
                if ($this->settings->updateObjectCache) {
                    wp_cache_delete($_REQUEST['post'], 'posts');
                }
            }

            add_action('add_meta_boxes', array($this, 'addMetaBoxes'));
        }
    }

    /**
     * Enqueues the scripts to handle guest post stuff on the posts page
     *
     * @param $hook
     */
    function enqueAdminScripts($hook) {
        if ($hook == 'edit.php' && $this->settings->gpUserID != 0) {
            add_filter('manage_post_posts_columns', array($this, 'manageColumns'));
            add_action('manage_post_posts_custom_column', array($this, 'customColumn'), 10, 2);
            add_action('admin_footer', array($this, 'adminPostsFooter'));
            wp_enqueue_style("easyrecipe-posts", self::$EasyRecipePlusUrl . "/css/easyrecipe-posts-min.css", array('easyrecipe-UI'), self::$pluginVersion);
            wp_enqueue_script('easyrecipe-posts', self::$EasyRecipePlusUrl . "/js/easyrecipe-posts-min.js", array('jquery-ui-dialog', 'jquery-ui-autocomplete', 'jquery-ui-button', 'jquery-ui-tabs'), self::$pluginVersion, true);
        }
    }

    /**
     */
    function enqueueScripts() {
        /*
        * We only need our stuff if there's an EasyRecipe on the post/page
        */
        if (count($this->easyrecipes) == 0) {
            return;
        }

        /**
         * Hook into the comments stuff to add the rating widget, display rating on individual comments and save a rating
         * and ajax calls don't trigger the wp_enqueue_scripts action where this was done previously
         */
        if ($this->settings->ratings == 'EasyRecipe') {
            add_action('comment_form', array($this, 'commentForm'), 0);
            add_action('comment_post', array($this, 'ratingSave'));

            // TODO - get ratings from all comments in one DB read when the comments start to display
            // Otherwise we'll have heaps of single row accesses if there's loads of comments
            add_filter('get_comment_text', array($this, 'ratingDisplay'), 100);
        }

        /**
         * Set the translate switch if this isn't in the US
         */
        if (get_locale() != 'en_US') {
            EasyRecipePlusTemplate::setTranslate('easyrecipe');
        }


        if ($this->settings->removeMicroformat) {
            add_filter('post_class', array($this, 'postClass'), 100);
            ob_start(array($this, 'fixMicroformats'));
        }

        $this->styleData = EasyRecipePlusStyles::getStyleData($this->styleName, $this->settings->customTemplates);
        wp_enqueue_style('easyrecipestyle-reset', self::$EasyRecipePlusUrl . "/css/easyrecipe-style-reset-min.css", array(), self::$pluginVersion);
        wp_enqueue_style("easyrecipebuttonUI", self::$EasyRecipePlusUrl . "/ui/easyrecipe-buttonUI.css", array('easyrecipestyle-reset'), self::$pluginVersion);
        /**
         * If the style directory starts with an underscore, it's a custom style
         */
        if ($this->styleData->directory[0] == '_') {
            wp_enqueue_style("easyrecipestyle", "/easyrecipe-style/style.css", array('easyrecipestyle-reset'), self::$pluginVersion . ".{$this->styleData->version}");
        } else {
            wp_enqueue_style("easyrecipestyle", self::$EasyRecipePlusUrl . "/styles/$this->styleName/style.css", array('easyrecipestyle-reset'), self::$pluginVersion . ".{$this->styleData->version}");
        }

        if (file_exists(self::$EasyRecipePlusDir . "/styles/$this->styleName/style.js")) {
            wp_enqueue_script('easyrecipestyle', self::$EasyRecipePlusUrl . "/styles/$this->styleName/style.js", array($this->pluginName), self::$pluginVersion . ".{$this->styleData->version}", $this->loadJSInFooter);
        }
        wp_enqueue_script($this->pluginName, self::$EasyRecipePlusUrl . "/js/easyrecipe-min.js", array('jquery', 'jquery-ui-button'), self::$pluginVersion, $this->loadJSInFooter);

        /**
         * Load any fonts used by the style
         * TODO - the enqueue name should be unique
         */
        foreach ($this->styleData->fonts as $font) {
            switch ($font['provider']) {
                case 'google' :
                    wp_enqueue_style("easyrecipefonts-" . $font['provider'], "http://fonts.googleapis.com/css?family=" . $font['fonts']);
                    break;
            }
        }

        /**
         * Load format dialogs and UI CSS if logged in as admin
         * Use our own version of an unobtrusive jQuery UI theme to prevent interference from themes and plugins that override standard stuff
         *
         * edit_theme_options is a better capability to check than edit_plugins (which is limited to super admins)
         *
         * Don't setup Live Formatting if the theme is being customized.
         *
         */
        if (current_user_can("edit_theme_options") && !class_exists('WP_Customize_Control', false)) {
            /*
             * Use an unobtrusive grey scheme for the formatting dialog so it doesn't visually overpower the recipe's styling
            */
            wp_enqueue_style("easyrecipe-FormatUI", self::$EasyRecipePlusUrl . "/formatui/easyrecipeFormatUI{$this->uiVersion}.css", array(), self::$pluginVersion);
            wp_enqueue_style("easyrecipeformat", self::$EasyRecipePlusUrl . "/css/easyrecipe-format-min.css", array('easyrecipe-FormatUI'), self::$pluginVersion);

            wp_enqueue_script('easyrecipeformat', self::$EasyRecipePlusUrl . "/js/easyrecipe-format-min.js", array('jquery', 'jquery-ui-slider', 'jquery-ui-autocomplete', 'jquery-ui-accordion', 'jquery-ui-dialog', 'jquery-ui-tabs', 'jquery-ui-button', 'json2'), self::$pluginVersion, $this->loadJSInFooter);
            add_action('wp_footer', array($this, 'addFormatDialog'), 0);
        }

        if ($this->settings->enableSwoop) {
            add_action('wp_footer', array($this, 'addSwoop'), 32767);
        }

        if ($this->settings->saveButton == 'BigOven') {
            add_action('wp_footer', array($this, 'addRecipeJSON'));
        }
    }


    /**
     *  FOODERIFIC
     *
     *  A site scan has been requested.
     *  If Fooderific is not currently enabled, then enable it
     */
    function fdScanSchedule() {

        $this->settings->enableFooderific = true;

        $fooderific = new EasyRecipePlusFooderific();
        $fooderific->scanSchedule();
    }

    /**
     * Actually run the site scan
     *
     * @param $postID
     */
    function fdScan($postID) {
        $fooderific = new EasyRecipePlusFooderific();
        $fooderific->scanRun($postID);
    }


    /**
     * A post has changed - see if we need to update the terms/taxonomies
     *
     * @param integer $postID
     * @param WP_Post $post
     */
    function postChanged(/** @noinspection PhpUnusedParameterInspection */
        $postID, $post) {
        $taxonomies = new EasyRecipePlusTaxonomies();
        $taxonomies->update($post);
    }

    /**
     * A post has changed - Let the Fooderific code decide what to do
     *
     * @param      $postID
     * @param null $post
     */
    function fdPostChanged($postID, $post = null) {
        $fooderific = new EasyRecipePlusFooderific();
        $fooderific->postChanged($postID, $post);
    }

    /**
     * A post's status has changed. Let the Fooderific code decide what to do
     *
     * @param $newStatus
     * @param $oldStatus
     * @param $post
     */
    function fdPostStatusChanged($newStatus, $oldStatus, $post) {
        $fooderific = new EasyRecipePlusFooderific();
        $fooderific->postStatusChanged($newStatus, $oldStatus, $post);
    }

    /**
     * Adds the snippet view and guest posts meta boxes
     *
     * Temporarily disable - Google no longer displays sample search results
     */
    function addMetaBoxes() {
        if (false) {
            add_meta_box('easyrecipe-snippet',
                'Google Rich Snippet Preview  &nbsp; &nbsp; &nbsp; &nbsp; <a href="http://www.easyrecipeplugin.com/support/google-doesnt-show-rich-snippets-for-my-posts/" target="_blank">Not seeing what you expected?</a>',
                array($this, 'snippetViewMetaBox'), 'post', 'normal', 'high');
        }
    }

    /**
     * @param $post WP_Post
     */
    function snippetViewMetaBox($post) {
        $data = new stdClass();
        $data->isPublished = $post->post_status == 'publish' && $post->post_password == '';
        $data->postID = $post->ID;
        $snippet = new EasyRecipePlusTemplate(self::$EasyRecipePlusDir . "/templates/easyrecipe-snippet.html");
        echo $snippet->replace($data);
    }

    /**
     * Snippets  - get a posts's snippet from Google and return the body
     */
    function getSnippet() {

        $id = $_POST['id'];
        $return = new stdClass();

        $url = sprintf(self::GOOGLESNIPPETURL, urlencode(get_permalink($id)));

        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $args = array('user-agent' => $ua, 'timeout' => 15);
        $response = wp_remote_get($url, $args);

        $return->id = $id;

        if (is_wp_error($response)) {
            $return->html = "Could not connect to www.google.com";
            $return->status = -1;
        } else {
            $html = $response['body'];
            $return->html = substr($html, strpos($html, "<body"));
            $return->status = $response['response']['code'];
        }
        echo json_encode($return);
        exit();
    }



    /**
     * Display the admin pointer about fooderific until it gets dismissed
     */
    function enqueueFooderificWPPointer() {
        if (current_user_can('edit_theme_options')) {
            $dismissed = explode(',', (string)get_user_meta(get_current_user_id(), 'dismissed_wp_pointers', true));

            if (!in_array('easyrecipe-fooderific', $dismissed)) {
                wp_enqueue_style('wp-pointer');
                wp_enqueue_script('wp-pointer');
                wp_enqueue_script('easyrecipe-wppointer', self::$EasyRecipePlusUrl . "/js/easyrecipe-wppointer-min.js", array('wp-pointer'), self::$pluginVersion);

                add_action('admin_print_footer_scripts', array($this, 'adminPostsFooterFooderific'));
            }
        }
    }


    function customColumn($column, $postID) {
        if ($column == 'guestpost') {
            $meta = get_metadata('post', $postID);
            if (isset($meta['_guestPost'])) {
                printf('<div id="ERGP_%d" class="ERGPAPoster">&nbsp;</div>', $postID);
                $item = new stdClass();

                $item->name = $meta['_guestAuthor'][0];
                $item->email = $meta['_guestEmail'][0];
                $item->url = $meta['_guestURL'][0];
                if ($item->url != '' && stripos($item->url, 'http://') !== 0) {
                    $item->url = 'http://' . $item->url;
                }
                $item->comment = $meta['_guestComment'][0];
                $item->title = get_the_title($postID);
                $this->guestPosters[$postID] = $item;
            } else {
                echo "";
            }
        }
    }

    function manageColumns($columns) {
        $newColumns = array();
        foreach ($columns as $key => $value) {
            if ($key == 'author') {
                $newColumns['guestpost'] = '<div class="ERGPAColumnHdr">&nbsp;</div>';
            }
            $newColumns[$key] = $value;
        }
        return $newColumns;
    }


    function adminPostsFooter() {
        /**
         * Get the template - preserve comments because we need to leave the template comments intact
         */
        $template = new EasyRecipePlusTemplate(self::$EasyRecipePlusDir . "/templates/easyrecipe-guestpostadmin.html");
        $html = $template->getTemplateHTML(EasyRecipePlusTemplate::PRESERVECOMMENTS);
        $html = str_replace("'", "\\'", $html);

        $guestPosters = str_replace("'", "\\'", json_encode($this->guestPosters));
        echo <<<EOD
<script type="text/javascript">
/* <![CDATA[ */
window.EASYRECIPE = window.EASYRECIPE || {};
EASYRECIPE.guestPosters = '$guestPosters';
EASYRECIPE.guestTemplate = '$html';
/* ]]> */
</script>
EOD;
    }



    /**
     * Output the stuff for the wp_pointer message after an update
     * Save the new version so we only display the message once
     * TODO - re-do this
     */
    function adminPostsFooterFooderific() {
        $this->settings->update();

        $data = new stdClass();
        $data->plus = 'Plus';
        $data->version = self::$pluginVersion;
        $template = new EasyRecipePlusTemplate(self::$EasyRecipePlusDir . "/templates/easyrecipe-fooderific.html");
        $html = str_replace("'", '&apos;', $template->replace($data));
        $html = str_replace("\r", "", $html);
        $html = str_replace("\n", " ", $html);
        echo <<<EOD
<script type="text/javascript">
// <![CDATA[
window.EASYRECIPE = window.EASYRECIPE || {};
EASYRECIPE.wppHTML = '$html';
EASYRECIPE.wppWidth = 425;
EASYRECIPE.wppPosition = {edge:'top', align:'center'};
EASYRECIPE.wppSelector = '#wpadminbar';
///]]>
</script>
EOD;
    }


    function getAuthor(/** @noinspection PhpUnusedParameterInspection */
        $author) {
        return $this->postMeta['_guestAuthor'][0];
    }

    function getAuthorURL($link) {
        $url = $this->postMeta['_guestURL'][0];
        if ($url != '') {
            return $url;
        }
        return $link;
    }

    function importCreate() {
        // TODO - move all err handling to Import class
        $result = new stdClass();
        if (!isset($_POST['files'])) {
            $result->status = 'FAIL';
            $result->error = "No files found";
            die(json_encode($result));
        }
        $import = new EasyRecipePlusImport();

        die(json_encode($import->createPosts()));
    }

    /**
     * Handle uploads of import source data
     */
    function importUpload() {
        // TODO - move all err handling to Import class
        $result = new stdClass();
        /*
        * Make sure we are error free
        */
        if ($_FILES["file"]["error"] > 0) {
            $result->status = 'FAIL';
            $result->error = $_FILES["file"]["error"];
            die(json_encode($result));
        }
//        if (!class_exists('EasyRecipeImport', false)) {  TODO - is this necessary?
//            require_once 'EasyRecipePlusImport.php';
//        }
        $import = new EasyRecipePlusImport();

        die(json_encode($import->validate()));
    }

    function guestPostUpload() {
        $guestPost = new EasyRecipePlusGuestPost($this);
        $guestPost->guestPostUpload();
    }


    /**
     * Many (most?) Wordpress themes seem to have have broken implementations of hfeed & hcard microformats
     * These errors prevent the Google Rich Snippet test tool from generating a rich snippet sample
     * We don't know if it affects the Google results or just the test tool - but better to be safe than sorry by removing the broken stuff
     * It appears that removing the broken stuff has no effect on the blog - which makes sense as it's broken anyway!
     *
     * @param $page
     *
     * @return mixed
     */
    function fixMicroformats($page) {
        $page = str_replace("hfeed", "", $page);
        $page = str_replace("hentry", "", $page);
        $page = str_replace("vcard", "", $page);
        $page = str_replace('class=""', "", $page);
        return $page;
    }

    /**
     * Remove hentry from the post classes
     *
     * This *should* get caught by fixMicroformats() above, but some plugins (e.g. Wordpress SEO)
     * stuff up output buffer handlers by arbitrarily doing a buffer clean
     *
     * @param $classes
     *
     * @return mixed
     */
    function postClass($classes) {
        if (($ix = array_search('hentry', $classes)) !== false) {
            unset($classes[$ix]);
        }
        return $classes;
    }

    /**
     * Get the custom and extra CSS
     *
     * Custom CSS is from Live Formatting and is json encoded
     * Extra CSS is from the settings page and is plain text
     *
     * @param string $print
     *
     * @return string
     */
    public function getCSS($print = '') {
        $customCSS = trim($this->settings->{"custom{$print}CSS"});
        if ($customCSS != '') {
            $customCSS = json_decode(stripslashes($customCSS));
            if (!$customCSS) { // TODO- handle this error better
                $customCSS = array();
            }
        } else {
            $customCSS = array();
        }

        // todo - check this construct works *** Should check for empty() - not empty strings
        $extraCSS = trim($this->settings->{"extra{$print}CSS"});
        $css = '';
        if ($customCSS != '' || $extraCSS != '') {
            $css = "<style type=\"text/css\">\n";
            foreach ($customCSS as $selector => $style) {
//                $style = addslashes($style);
                /*
                * Make the selectors VERY specific in an attempt override theme CSS
                * Also make them "important"
                */
                if (stripos($selector, ".easyrecipe") === 0) {
                    $selector = "html body div" . $selector;
                } else {
                    if (stripos($selector, "div.easyrecipe") === 0) {
                        $selector = "html body " . $selector;
                    } else {
                        if (stripos($selector, "html body") === false) {
                            $selector = "html body " . $selector;
                        }
                    }
                }
                /**
                 * Make the custom styles !important to override any very specific theme settings
                 *
                 * But if the current user can use Live Formatting, DON'T make the custom styles !important
                 * When Live Formatting is active the custom styes are applied to elements directly in their style attribute
                 * Making the custom styles CSS !important overrides inherited custom styles when Live Formatting
                 */
                if (!current_user_can("edit_theme_options")) {
                    $styles = explode(';', $style);
                    $style = '';
                    foreach ($styles as $s) {
                        $s = trim($s);
                        if (!empty($s)) {
                            if (!preg_match('/!\s*important\s*/', $s)) {
                                $s .= '!important';
                            }
                            $style .= "$s;";
                        }
                    }

                }
                $css .= "$selector { $style }\n";
            }
            $css .= $extraCSS;
            $css .= "</style>\n";
        }
        return $css;
    }

    public function addHead() {

        global $post;

        if (isset($post)) {
            /**
             * Use compressed CSS & JS for release versions
             */
            /** @noinspection PhpUnusedLocalVariableInspection */
            $min = '-min';
            switch ($post->ID) {
                case $this->settings->gpDetailsPage :
                    wp_enqueue_style("easyrecipe-guestpost", self::$EasyRecipePlusUrl . "/css/easyrecipe-guestpost$min.css", array(), self::$pluginVersion);
                    wp_enqueue_script('easyrecipe-guestpost', self::$EasyRecipePlusUrl . "/js/easyrecipe-guestpostdetails$min.js", array('jquery'), self::$pluginVersion, true);
                    break;

                case $this->settings->gpEntryPage:
                    wp_enqueue_style("easyrecipe-admincommon", get_site_url() . '/wp-admin/css/common.css');
                    wp_enqueue_style("easyrecipe-UI");
                    wp_enqueue_style("easyrecipe-entry", self::$EasyRecipePlusUrl . "/css/easyrecipe-entry$min.css", array('easyrecipe-UI'), self::$pluginVersion);
                    wp_enqueue_style("easyrecipe-guestpost", self::$EasyRecipePlusUrl . "/css/easyrecipe-guestpost$min.css", array('easyrecipe-entry'), self::$pluginVersion);
                    wp_enqueue_style("easyrecipe-guestposteditor", self::$EasyRecipePlusUrl . "/css/easyrecipe-guestposteditor{$this->wpVersion}$min.css", array('easyrecipe-entry'), self::$pluginVersion);
                    wp_enqueue_script('plupload');
                    wp_enqueue_script('plupload-html5');
                    wp_enqueue_script('plupload-flash');
                    wp_enqueue_script('plupload-silverlight');
                    wp_enqueue_script('plupload-html4');

                    wp_enqueue_script('easyrecipe-entry', self::$EasyRecipePlusUrl . "/js/easyrecipe-entry{$this->wpVersion}$min.js", array('jquery-ui-dialog', 'jquery-ui-autocomplete', 'jquery-ui-button', 'jquery-ui-tabs'), self::$pluginVersion, true);
                    wp_enqueue_script('easyrecipe-guestpost', self::$EasyRecipePlusUrl . "/js/easyrecipe-guestpostentry{$this->wpVersion}$min.js", array('plupload', 'easyrecipe-entry', 'jquery-ui-progressbar'), self::$pluginVersion, true);
                    add_action('wp_footer', array($this, 'addDialogHTML'));
                    wp_deregister_script('easyrecipeformat');
                    wp_deregister_script($this->pluginName);

                    break;

                case $this->settings->gpThanksPage :
                    break;
            }
        }
    }

    public function addExtraCSS() {
        echo $this->getCSS();
    }

    /**
     * Process a "Save style" from Live Formatting
     */
    public function saveStyle() {
        if (current_user_can("edit_theme_options")) {
            if (!isset($this->settings)) {
                $this->settings = EasyRecipePlusSettings::getInstance();
            }

            // FIXME - check that the new style actually exists!
            $this->settings->style = isset($_POST['style']) ? $_POST['style'] : '';
            $this->settings->update();
        }
        die('OK');
    }

    /**
     * Process the update from the format javascript via ajax
     */
    public function updateCustomCSS() {
        if (current_user_can("edit_theme_options")) {
            if (!isset($this->settings)) {
                $this->settings = EasyRecipePlusSettings::getInstance();
            }

            $setting = isset($_POST['isPrint']) ? "customPrintCSS" : "customCSS";
            $this->settings->{$setting} = isset($_POST['css']) ? stripslashes_deep($_POST['css']) : "";
            $this->settings->update();

        }
        /**
         * The return isn't necessary but it helps with unit testing
         */
        die('OK');
    }

    /**
     * @param $styleData
     * @param bool $isPrint
     * @return string
     */
    function getFormatDialog($styleData, $isPrint = false) {
        $data = new stdClass();
        $data->SECTIONS = array();
        $id = 0;

        /**
         * Get the formatting data for each formattable element
         * Add more specificity to each target in an attempt to override any specific theme CSS
         */
        $formats = @json_decode($styleData->formatting);
        if ($formats) {
            foreach ($formats as $format) {
                $item = new stdClass();
                if (stripos($format->target, ".easyrecipe") === 0) {
                    $format->target = "html body div" . $format->target;
                } else {
                    if (stripos($format->target, "div.easyrecipe") === 0) {
                        $format->target = "html body " . $format->target;
                    } else {
                        if (stripos($format->target, "html body") === false) {
                            $format->target = "html body " . $format->target;
                        }
                    }
                }

                $item->section = $format->section;
                $format->id = $item->id = $id++;
                $data->SECTIONS[] = $item;
            }
        }

        /**
         * Get all the styles we have
         */

        $styles = EasyRecipePlusStyles::getStyles($this->settings->customTemplates, $isPrint);

        $data->STYLES = array();
        $styleThumbs = array();
        foreach ($styles as $style) {
            $item = new stdClass();
            $item->directory = $style->directory;
            $item->selected = $item->directory == $this->styleName ? 'selected="selected"' : '';
            $item->style = $style->style;
            $styleThumbs[$style->directory] = $style->thumbnail;
            $data->STYLES[] = $item;
        }
        $data->stylethumb = $styleData->thumbnail;

        $template = new EasyRecipePlusTemplate(self::$EasyRecipePlusDir . "/templates/easyrecipe-format.html");
        $html = $template->replace($data);

        $template = new EasyRecipePlusTemplate(self::$EasyRecipePlusDir . "/templates/easyrecipe-fontchange.html");
        $fontChangeHTML = $template->replace($data);
        $fontChangeHTML = str_replace("\r", "", $fontChangeHTML);
        $fontChangeHTML = str_replace("\n", " ", $fontChangeHTML);
        $fontChangeHTML = str_replace("'", '\0x27', $fontChangeHTML);
        $fontChangeHTML = trim(preg_replace('/> \s+</i', '> <', $fontChangeHTML));
        $ajaxURL = admin_url('admin-ajax.php');
        $cssType = $isPrint ? 'customPrintCSS' : 'customCSS';
        /**
         * Fix extra slashes that may be hanging around from earlier versions
         * Then escape single quotes with a slash
         */
        $customCSS = str_replace("'", "\\'", stripslashes_deep($this->settings->$cssType));
        if ($customCSS == '') {
            $customCSS = '{}';
        }
        $formats = json_encode($formats);
        $formats = str_replace("'", '\'', $formats);

        if ($isPrint) {
            $minHeight = 550;
            $print = 'true';
        } else {
            $minHeight = 638;
            $print = 'false';
        }

        $thumbs = json_encode($styleThumbs);
        $url = self::$EasyRecipePlusUrl;
        $pluginVersion = self::$pluginVersion;
        $html .= <<<EOD
<script type="text/javascript">
/* <![CDATA[ */
window.EASYRECIPE = window.EASYRECIPE || {};
EASYRECIPE.isPrint = $print;
EASYRECIPE.minHeight = $minHeight;
EASYRECIPE.formatting = '$formats';
EASYRECIPE.customCSS = '$customCSS';
EASYRECIPE.easyrecipeURL = '$url';
EASYRECIPE.wpVersion = '$this->wpVersion';
EASYRECIPE.version = '$pluginVersion';
EASYRECIPE.ajaxURL = '$ajaxURL';
EASYRECIPE.styleThumbs = '$thumbs';
EASYRECIPE.fontChangeHTML = '$fontChangeHTML';
/* ]]> */
</script>
EOD;
        return $html;
    }

    function addFormatDialog() {
        echo $this->getFormatDialog($this->styleData);
    }

    function addSwoop() {
        printf(self::SWOOPJS, $this->settings->swoopSiteID);
    }

    /**
     * Add the recipe(s) data as a json string
     */
    function addRecipeJSON() {
        // Add a JSON respresentation of the recipe(s)
    }

    function doConvert($matches) {
        $this->converted = true;
        $convert = new EasyRecipePlusConvert();
        return $convert->convertHTML($matches[2], $matches[1]);
    }

    /**
     * Possibly convert other recipe plugin data
     * Most of these are handled by shortcodes which we replace with an EasyRecipe equivalent
     * Recipage is a little more involved. It is hrecipe formatted and we can't reliably extract the recipe code with a regex, so we need to use a DOMDocument
     *
     * @param $postID
     * @param $postType
     * @param $content
     * @return mixed|string
     */
    public function possiblyConvert($postID, $postType, $content) {

        $this->converted = false;
        $this->convertedType = $postType;
        if ($this->settings->displayZiplist) {
            $content = preg_replace_callback('/\[amd-(zlrecipe)-recipe:(\d+)\]/', array($this, 'doConvert'), $content);
            if ($this->converted && $this->settings->ratings != 'Disabled') {
                $this->ratingMethod = 'SelfRated';
            }
        }
        if ($this->settings->displayRecipeCard) {
            $content = preg_replace_callback('/\[(yumprint)-recipe id=[\'"](\d+)[\'"]\]/', array($this, 'doConvert'), $content);
            if ($this->converted && $this->settings->ratings != 'Disabled') {
                $this->ratingMethod = 'SelfRated';
            }
        }
        if ($this->settings->displayGMC) {
            $content = preg_replace_callback('/\[(gmc_recipe) (\d+)\]/', array($this, 'doConvert'), $content);
            if ($this->converted && $this->settings->ratings != 'Disabled') {
                $this->ratingMethod = 'SelfRated';
            }
        }
        if ($this->settings->displayUltimateRecipe) {
//            if ($postType == 'recipe') {
            $content = str_replace('[recipe]', "[ultimate-recipe id='$postID']", $content);
            $content = preg_replace_callback('/\[(ultimate-recipe) id=["\'](\d+|random)["\']\]/i', array($this, 'doConvert'), $content);
            if ($this->converted && $this->settings->ratings != 'Disabled') {
                $this->ratingMethod = 'SelfRated';
            }
            $this->convertedType = 'post';
//            }
        }

        if ($this->settings->displayRecipage) {
            /**
             * Do a quick check before we go to the expense of instantiating a DOMDocument
             */
            if (strpos($content, 'hrecipe f') !== false) {
                $document = new EasyRecipePlusDOMDocument($content);
                if ($document->isValid()) {
                    /** @var DOMElement $hrecipe */
                    $hrecipe = $document->getElementByClassName('hrecipe');
                    if ($hrecipe) {
                        $matches = array();
                        $matches[1] = 'recipage';
                        $matches[2] = $postID;
                        $convertedRecipe = $this->doConvert($matches);
                        /** @var DOMDocumentFragment $fragment */
                        $fragment = $document->createDocumentFragment();
                        $fragment->appendXML($convertedRecipe);
                        $hrecipe->parentNode->replaceChild($fragment, $hrecipe);
                        $content = $document->saveHTML();
                        $content = preg_replace('%^.*</head><body>(.*)</body></html>\s*$%sm', '$1', $content);
                    }
                }
            }
        }

        return $content;
    }

    function customStyleHandler($file, $isPrint = false) {
        /*
        * Is it a thumbnail request from settings?
        * This will look something like "styles/style000/images/thumb.jpg"
        * The actual file lives in the custom styles directory
        */
        if (preg_match('%styles/.*$%si', $file, $regs)) {
            $path = $this->settings->customTemplates . "/$file";
        } else {

            /*
            * Get the approriate custom styles directory
            */
            $print = $isPrint ? 'print' : '';
            $customDir = $this->settings->customTemplates . "/{$print}styles/" . substr($this->styleName, 1);

            $path = "$customDir/$file";
        }
        /*
           * Make sure that whatever is being requested actually exists
        */

        if (!@file_exists($path)) {
            exit();
        }

        /*
           * Before we serve it, see if we can just return a 304
        */
        $lastMod = gmdate('D, d M Y H:i:s \G\M\T', @filemtime($path));

        if (array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER)) {
            $if_modified_since = strtotime(preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]));
            if ($if_modified_since >= $lastMod) {
                header("HTTP/1.1 304 Not Modified");
                header("Connection: Keep-Alive", true);
                exit();
            }
        }

        /*
        * Serve the file with the appropriate headers
        * Everything we serve here should be cacheable for forever
        */
        $expires = gmdate('D, d M Y H:i:s \G\M\T', time() + 86400 * 365);

        $ftype = wp_check_filetype($file);
        $fs = @filesize($path);
        header("HTTP/1.1 200 OK");
        header("Content-type: " . $ftype['type']);
        header("Content-length: $fs");
        header("Last-Modified: " . $lastMod);
        header("Expires: " . $expires);
        header("Cache-Control: max-age=31536000");
        header("Pragma: ");

        readfile($path);
        exit();
    }


    /**
     * Check if this is one of our rewrite endpoints (non-existent pages)
     *
     * @param array $headers
     * @return array
     */
    function checkRewrites($headers) {

        /**
         * Just return if it's nothing we're interested in
         */
        if (!preg_match('%/easyrecipe-(printstyle|diagnostics|style|print|debuglogs|log)(?:/((?:.*/)?[^?/]+))?%', $_SERVER['REQUEST_URI'], $regs)) {
            return $headers;
        }

        switch ($regs[1]) {
//            case 'print' :
//                if (preg_match('/^(\d+)-(\d+)$/', $regs[2], $regs)) {
//                    $print = new EasyRecipePlusPrint($this);
//                    $print->printRecipe($regs[1], $regs[2]);
//                    exit;
//                }
//                break;

            case 'diagnostics' :
                if (current_user_can('administrator')) {
                    $diagnostics = new EasyRecipePlusDiagnostics();
                    $diagnostics->show();
                }
                break;

            case 'debuglogs' :
                if (current_user_can('administrator')) {
                    $debugLog = new EasyRecipePlusDebugLog();
                    $debugLog->showLogs();
                }
                break;

            case 'log' :
                if (current_user_can('administrator')) {
                    $debugLog = new EasyRecipePlusDebugLog();
                    $debugLog->showLog($regs[2]);
                }
                break;

            case 'style' :
                $this->customStyleHandler($regs[2]);
                break;

            case 'printstyle' :
                $this->customStyleHandler($regs[2], true);
                break;
        }
        return $headers;
    }

    /**
     * Make allowance for Genesis grids.
     *
     * This is very experimental - it's hard to be sure of the possible paths thru Genesis without a LOT more work
     *
     * If we are on a Genesis grid and the content is limited, process the recipes as though this was an excerpt.
     * Genesis doesn't apply the "the_content" filter before generating limited length content and there doesn't appear
     * to be any clean way to hook into Genesis's "content limited" processing to handle recipes as per excerpts.
     * Genesis also doesn't honor manual excerpts when generating limited length content
     *
     * Eleven40 is a good test theme for this
     */
    function genesisGridLoopContent() {
        global $_genesis_loop_args, $pages;

        if ($this->genesisPost != null) {
            if (in_array('genesis-feature', get_post_class())) {
                if (!empty($_genesis_loop_args['feature_content_limit'])) {
                    $this->genesisPost->post_content = $this->filterExcerpt($this->genesisPost->post_content);
                    if (!empty($pages[0])) {
                        $pages[0] = $this->genesisPost->post_content;
                    }
                }
            } else {
                if (!empty($_genesis_loop_args['grid_content_limit'])) {
                    $this->genesisPost->post_content = $this->filterExcerpt($this->genesisPost->post_content);
                }
                if (!empty($pages[0])) {
                    $pages[0] = $this->genesisPost->post_content;
                }
            }
        }
        /** @noinspection PhpUndefinedFunctionInspection */
        genesis_grid_loop_content();
    }

    /**
     * Set up filters on Author stuff if this is a guest post (Plus version)
     * Also see if we're doing a genesis grid item which has a limited length and attempt to make those look reasonable if we are.
     * Otherwise, Genesis may just arbitrarily truncate the content and leave open HTML block elements - this will usually totally stuff up the layout
     *
     * @param Object $post
     */
    function thePost($post) {
        global $_genesis_loop_args;

        $this->genesisPost = null;

        if ($this->settings->genesisGrid) {
            if (!empty($_genesis_loop_args) && !empty($this->easyrecipes[$post->ID])) {
                $this->genesisPost = $post;
                if (isset($_genesis_loop_args['grid_content_limit'])) {
                    if (has_action('genesis_post_content', 'genesis_grid_loop_content')) {
                        remove_action('genesis_post_content', 'genesis_grid_loop_content');
                        add_action('genesis_post_content', array($this, 'genesisGridLoopContent'));
                    }
                    if (has_action('genesis_entry_content', 'genesis_grid_loop_content')) {
                        remove_action('genesis_entry_content', 'genesis_grid_loop_content');
                        add_action('genesis_entry_content', array($this, 'genesisGridLoopContent'));
                    }
                }
            }
        }

        $meta = get_post_meta($post->ID, '');
        if (isset($meta['_guestPost'])) {
            $this->postMeta = $meta;
            add_filter('the_author', array($this, 'getAuthor'));
            add_filter('the_author_url', array($this, 'getAuthorURL'));
            add_filter('author_link', array($this, 'getAuthorURL'));
        } else {
            unset($this->postMeta);
            remove_filter('the_author', array($this, 'getAuthor'));
            remove_filter('the_author_url', array($this, 'getAuthorURL'));
            remove_filter('author_link', array($this, 'getAuthorURL'));
        }
    }


    /**
     * Remove non display stuff
     *
     * @param $content
     *
     * @return mixed
     */
    function filterExcerpt($content) {
        $dom = new EasyRecipePlusDOMDocument($content);
        $dom->removeElementsByClassName('ERSSavePrint', 'div');
        $dom->removeElementsByClassName('ERSRating', 'div');
        $dom->removeElementsByClassName('ERSRatings', 'div');
        $dom->removeElementsByClassName('ERSClear', 'div');
        $dom->removeElementsByClassName('endeasyrecipe', 'div');
        $dom->removeElementsByClassName('ERSLinkback', 'div');
        $content = $dom->getHTML(true);
        /**
         * Remove empty lines left over from the deletions
         */
        return preg_replace('/(\r\n|\n)(?:\r\n|\n)+/', '$1', $content);
    }

    /**
     * Set a the filterExcerpt flag that will get checked in theContent()
     * This function only gets hooked in if our "Filter excerpt" option is checked
     *
     * @param string $text
     *
     * @return string
     */
    function theExcerpt($text = '') {
        /**
         * Only filter if the excerpt will be generated
         */
        $this->filterExcerpt = ($text == '');
        return $text;
    }

    /**
     * Replaces a formatted recipe's HTML with a shortcode
     *
     * @param $match
     *
     * @return string
     */
    function getRecipeShortcode($match) {
        $nRecipe = count($this->recipesHTML);
        $this->recipesHTML[] = $match[1];
        return "[easyrecipe n=\"$nRecipe\"]";
    }

    /**
     * Replace a recipe shortcode with the original HTML
     * Since there is no way to specify the sequence of shortcode evaluations, we need to run the recipe HTML itself thru the shortcode process
     *
     * @param $attributes
     *
     * @return mixed
     */
    function replaceRecipeShortcode($attributes) {
        return do_shortcode($this->recipesHTML[$attributes['n']]);
    }

    /**
     * Replace formatted EasyRecipe HTML with a shortcode
     * This function will be called before wpauto() mangles the recipe HTML by replacing it with a shortcode which gets expanded in replaceRecipeShortcode() after
     * wpauto() has done its worst. The "the_content" filter doesn't get applied in some themes and in that case, we'll just have to live with mangled HTML
     *
     * @param $content
     *
     * @return mixed
     */

    function theContent($content) {
        /**
         * Replace the recipe HTML with a shortcode
         */
        return preg_replace_callback('%(<div[^>]+class="easyrecipe.*?<div[^>]+class="endeasyrecipe".+?</div>.*?</div>)%si', array($this, 'getRecipeShortcode'), $content);
    }

    /**
     * Process any EasyRecipes in all the posts on the page
     * We need to do this here rather than in the_content hook because by then it's too late to queue up the scripts/styles we'll need
     *
     * @param $posts
     *
     * @return array
     */
    function thePosts($posts) {
        /* @var $wp_rewrite WP_Rewrite */
        global $wp_rewrite;

        /** @global  $wpdb wpdb */
        global $wpdb;

        /**
         * We don't want to process anything if it's a missing URL
         */
        if (is_404()) {
            return $posts;
        }
        global $shortcode_tags;

        $guestpost = null;
        $newPosts = array();
        /**
         * Process each post and replace placeholders with relevant data
         */
        /** @var WP_Post $post */
        foreach ($posts as $post) {

            /**
             * Have we already processed this post?
             */
            if (isset($this->easyrecipes[$post->ID])) {
                $post->post_content = $this->postContent[$post->ID];
                $newPosts[] = $post;
                continue;
            }

            /**
             * We may have to change the rating method (e.g. for Ziplist recipes) so make a local copy
             */
            $this->ratingMethod = $this->settings->ratings;
            if (!is_admin()) {
                $post->post_content = $this->possiblyConvert($post->ID, $post->post_type, $post->post_content);
                $post->post_type = $this->convertedType;
            }


            /**
             * Handle the guest post shortcodes here because WP doesn't process them until much later
             * We may need to redirect so we need to do it before anything else has a chance to do output
             * We may also need to process a recipe
             */

            if ($post->ID == $this->settings->gpDetailsPage || $post->ID == $this->settings->gpEntryPage || $post->ID == $this->settings->gpThanksPage) {
                if (empty($guestPost)) {
                    $guestPost = new EasyRecipePlusGuestPost($this);
                }
                $gpResult = $guestPost->process($post);
                /**
                 * If $guestPost->process() returns something, we processed the GP entry page
                 * In this case, all we need to do is save it in $newPosts and continue
                 * Otherwise, continue with processing since there may conceivably be an EasyRecipe on the post/page
                 *
                 * TODO - do we really need to do this?  Won't it get picked up just below anyway?
                 */
                if ($gpResult) {
                    $newPosts[] = $gpResult;
                    continue;
                }
            }

            $postDOM = new EasyRecipePlusDocument($post->post_content);

            if (!$postDOM->isEasyRecipe) {
                if (strncasecmp($post->post_content, '[easyrecipe_page]', 17) === 0) {
                    $this->easyrecipes[$post->ID] = true;
                    $post->post_content = str_replace('[easyrecipe_page]', '', $post->post_content);
                }
                $newPosts[] = $post;
                continue;
            }


            $postDOM->setSettings($this->settings);
            /**
             * Mark this post as an easyrecipe so that the comment and rating processing know
             */
            $this->easyrecipes[$post->ID] = true;

            /**
             * Make sure we haven't already formatted this post. This can happen in preview mode where WP replaces the post_content
             * of the parent with the autosave content which we've already processed.
             * If this is the case, save the formatted code and mark this post as having been processed
             * TODO - are there implications for the object cache for themes that re-read posts?
             */
            if ($postDOM->isFormatted) {
                $this->postContent[$post->ID] = $post->post_content;
                $newPosts[] = $post;
                continue;
            }

            /**
             * Fix possibly broken times in older posts
             * Fix the Cholesterol typo oops in early versions
             */

            if ($postDOM->recipeVersion < '3') {
                $postDOM->fixTimes("preptime");
                $postDOM->fixTimes("cooktime");
                $postDOM->fixTimes("duration");
                $postDOM->setParentValueByClassName("cholestrol", $this->settings->lblCholesterol, "Cholestrol");
            }

            $data = new stdClass();

            /**
             * Get the ratings from the comment meta table if we use the EasyRecipe comment method
             * Other rating methods are handled in EasyRecipePlusDocument->applyStyle()
             * hasRatings is left unset for Self Rating
             */

            if ($this->ratingMethod == 'EasyRecipe') {
                $q = "SELECT COUNT(*) AS count, SUM(meta_value) AS sum FROM $wpdb->comments JOIN $wpdb->commentmeta ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID ";
                $q .= "WHERE comment_approved = 1 AND meta_key = 'ERRating' AND comment_post_ID = $post->ID AND meta_value > 0";
                $ratings = $wpdb->get_row($q);

                if ((int)$ratings->count > 0) {
                    $data->ratingCount = $ratings->count;
                    $data->ratingValue = number_format($ratings->sum / $ratings->count, 1);
                    $data->ratingPC = $data->ratingValue * 100 / 5;
                    $data->hasRating = true;
                } else {
                    $data->hasRating = false;
                }
            } else {
                if ($this->ratingMethod == 'Disabled') {
                    $data->hasRating = false;
                }
            }

            switch ($this->settings->saveButton) {
                case 'Ziplist' :
                    $data->saveButtonJS = self::ZIPLISTJS;
                    $data->saveButton = sprintf(self::ZIPLISTBUTTON, $this->settings->ziplistPartnerKey, urlencode(get_permalink($post->ID)), $this->settings->lblSave);
                    $data->hasSave = true;
                    break;
                case 'BigOven':
                    $data->saveButtonJS = '';
                    $data->saveButton = sprintf(self::BIGOVENBUTTON, self::$EasyRecipePlusUrl);
                    $data->hasSave = true;

                    break;
            }

            $this->settings->getLabels($data);


            $data->hasLinkback = $this->settings->allowLink;
            $data->displayPrint = $this->settings->displayPrint;
            $data->style = $this->styleName;
            $data->title = $post->post_title;
            $data->blogname = get_option("blogname"); // TODO - do all this stuff at initialise time?
            $data->siteURL = $this->homeURL;

            /**
             * If the site isn't using permalinks then just pass the print stuff as a qurerystring param
             */
            if ($wp_rewrite->using_permalinks()) {
                $data->sitePrintURL = $data->siteURL;
            } else {
                $data->sitePrintURL = $data->siteURL . "?";
            }

            $data->postID = $post->ID;
            $data->recipeurl = get_permalink($post->ID);
            $data->convertFractions = $this->settings->convertFractions;

            if ($this->styleName[0] == '_') {
                $styleName = substr($this->styleName, 1);
                $templateFile = $this->settings->customTemplates . "/styles/$styleName/style.html";
            } else {
                $templateFile = self::$EasyRecipePlusDir . "/styles/$this->styleName/style.html";
            }
            $template = new EasyRecipePlusTemplate($templateFile);

            $data->isLoggedIn = is_user_logged_in();

            /**
             * Apply styles to the recipe data and return the content with recipes replace by a shortcode and also each recipe's HTML
             * Also keep a copy so we don't have to reformat in the case where the theme asks for the same post again
             *
             * This didn't work!  Some themes don't call the_content() (esp for excerpts) so we can't rely on hooking into that to supply the formatted html
             * We need to do it right here - it seems that the_posts is the only reliable place to replace the base recipe HTML with the formatted recipe HTML
             */

            /**
             * Replace the original content with the one that has the easyrecipe(s) nicely formatted and marked up
             * Also keep a copy so we don't have to reformat in the case where the theme asks for the same post again
             */
            $this->postContent[$post->ID] = $post->post_content = $postDOM->applyStyle($template, $data);
            /**
             * If we haven't already done so, hook into the_content filter to stop wpauto() messing with recipe HTML
             */
            if (empty($shortcode_tags['easyrecipe'])) {
                add_filter('the_content', array($this, 'theContent'), 0);
                add_shortcode('easyrecipe', array($this, 'replaceRecipeShortcode'));
            }

            /**
             * Some themes do a get_post() again instead of using the posts as modified by plugins
             * So make sure our modified post is in cache so the get_post() picks up the modified version not the original
             * Need to do both add and replace since add doesn't replace and replace doesn't add and we can't be sure if the cache key exists at this point
             * But only do this if absolutely necessary
             */
            if ($this->settings->updateObjectCache) {
                wp_cache_add($post->ID, $post, 'posts');
                wp_cache_replace($post->ID, $post, 'posts');
            }
            $newPosts[] = $post;
        }
        return $newPosts;
    }

    /**
     * Explicitly allow the itemprop, datetime and link attributes otherwise WP will strip them
     *
     * @param $postID
     */
    function publishFuturePost($postID) {
        global $allowedposttags;

        $post = get_post($postID);
        if (strpos($post->post_content, 'easyrecipe') !== false) {
            $allowedposttags['time'] = array('itemprop' => true, 'datetime' => true);
            $allowedposttags['link'] = array('itemprop' => true, 'href' => true);
            $this->settings = EasyRecipePlusSettings::getInstance();
            if ($this->settings->enableFooderific) {
                $this->fdPostStatusChanged('publish', 'future', $post);
            }
        }

    }

    /**
     * Check to see if the post content contains the wrappers we use to facilitate line insertion above & below a recipe
     *
     * If they exist, strip them out FIXME
     *
     * @param $data
     * @param $postarr
     *
     * @return
     */
    function postSave($data, /** @noinspection PhpUnusedParameterInspection */
                      $postarr) {
// TODO - update the taxonomy stuff
        /*
                if (strpos($data['post_content'], 'easyrecipeWrapper') !== false) {
                    $content = stripslashes($data['post_content']);
                    $dom = new $this->documentClass($content);
                    $content = $dom->stripWrappers();
                    if ($content !== null) {
                        $data['post_content'] = $content;
                    }
                }
        */
        return $data;

    }

    function commentForm($postID) {

        /*
        * Only add ratings for easy recipes
        */
        if (!isset($this->easyrecipes[$postID]) || !$this->easyrecipes[$postID]) {
            return;
        }

        $rateLabel = $this->settings->lblRateRecipe;

        echo <<<EOD
<span class="ERComment">
<span style="float:left">$rateLabel: </span>
<span class="ERRateBG">
<span class="ERRateStars"></span>
</span>
<input type="hidden" class="inpERRating" name="ERRating" value="0" />
&nbsp;
</span>
EOD;
    }

    function ratingSave($commentID) {
        if (isset($_POST['ERRating'])) {
            $rating = (int)$_POST['ERRating'];
            if ($rating > 0) {
                add_comment_meta($commentID, 'ERRating', $rating, true);
            }
        }
    }

    function ratingDisplay($comment) {
        global $post;

        /*
        * Only display comment ratings if the post is an EasyRecipe
        */
        if (!$this->easyrecipes[$post->ID]) {
            return $comment;
        }

        $rating = get_comment_meta(get_comment_ID(), 'ERRating', true);
        if ($rating == '') {
            $rating = 0;
        }
        $stars = "";
        if ($rating > 0) {
            $rating *= 20;

            $stars = <<<EOD
      <div class="ERRatingComment">
      <div style="width:$rating%" class="ERRatingCommentInner"></div>
      </div >
EOD;
        }
        return $comment . $stars;
    }

    function pluginActionLinks($links, $pluginFile) {
        if ($pluginFile == "easyrecipeplus/easyrecipeplus.php") {
            $links[] = '<a href="admin.php?page=EasyRecipePlus">' . __('Settings') . '</a>';
        }
        return $links;
    }

    /**
     * Send a support query and possibly diagnostics to EasyRecipePlus support
     * // FIXME - fix the plugin name in the support template on the "Click here ..." link - else won't work for beta version
     */

    function sendSupport() {
        $diagnostics = new EasyRecipePlusDiagnostics();
        $diagnostics->send(self::DIAGNOSTICS_URL, array('action' => 'easysupportDiagnostic'));
    }

    /**
     * Create the settings - it will convert from the FREE settings if this is the PLUS version
     */
    function pluginActivated() {


        $data = http_build_query(array('action' => 'activate', 'site' => get_site_url()));
        wp_remote_post("http://www.easyrecipeplugin.com/installed.php", array('body' => $data, "blocking" => false));

    }

    function pluginDeactivated() {
        $data = http_build_query(array('action' => 'deactivate', 'site' => get_site_url()));
        wp_remote_post("http://www.easyrecipeplugin.com/installed.php", array('body' => $data, "blocking" => false));
    }

    /**
     * Allow <link> and <time> tags in <divs>
     *
     * @param $init
     *
     * @return
     */
    function mcePreInitialise($init) {
        $extendedElements = 'link[itemprop|href],time[itemprop|datetime]';
        $validChildren = '+div[time|link]';

        if (isset($init['extended_valid_elements'])) {
            $init['extended_valid_elements'] .= ',' . $extendedElements;
        } else {
            $init['extended_valid_elements'] = $extendedElements;
        }
        if (isset($init['valid_children'])) {
            $init['valid_children'] .= ',' . $validChildren;
        } else {
            $init['valid_children'] = $validChildren;
        }

        return $init;
    }

    /**
     * Add our tinyMCE plugin
     *
     * @param $plugins
     *
     * @return array
     */
    function mcePlugins($plugins) {
        $plugins = (array)$plugins;
        $plugins['easyrecipe'] = self::$EasyRecipePlusUrl . "/js/easyrecipe-mce{$this->mceVersion}-min.js?v=" . self::$pluginVersion;
        $plugins['noneditable'] = self::$EasyRecipePlusUrl . "/js/noneditable{$this->mceVersion}-min.js?v=" . self::$pluginVersion;
        if ($this->isGuest) {
            $plugins['guestpost'] = self::$EasyRecipePlusUrl . "/js/easyrecipe-guestpost-mce{$this->mceVersion}-min.js?v=" . self::$pluginVersion;
        }
        return $plugins;
    }

    /**
     * Add our tinyMCE buttons
     *
     * @param $buttons
     *
     * @return array
     */
    function mceButtons($buttons) {
        if ($this->isGuest) {
            $buttons[] = 'easyrecipeImage';
        }
        $buttons[] = 'easyrecipeEdit';
        $buttons[] = 'easyrecipeAdd';
        $buttons[] = 'easyrecipeTest';
        return $buttons;
    }

    /**
     * Insert the easyrecipe dialogs and template HTML at the end of the
     * page - they're display:none by default
     */
    function addDialogHTML() {
        global $post, $wp_version;

        if (!$this->isGuest && !isset($post)) {
            return;
        }
        $data = new stdClass();
        $data->isSelfRated = $this->settings->ratings == 'SelfRated';
        $template = new EasyRecipePlusTemplate(self::$EasyRecipePlusDir . "/templates/easyrecipe-entry.html");
        echo $template->replace($data);


        $template = new EasyRecipePlusTemplate(self::$EasyRecipePlusDir . "/templates/easyrecipe-select.html");
        echo $template->replace();

        $data = new stdClass();
        $data->easyrecipeURL = self::$EasyRecipePlusUrl;
        $template = new EasyRecipePlusTemplate(self::$EasyRecipePlusDir . "/templates/easyrecipe-convert.html");
        echo $template->replace($data);

        $template = new EasyRecipePlusTemplate(self::$EasyRecipePlusDir . "/templates/easyrecipe-htmlwarning.html");
        echo $template->getTemplateHTML();

        /**
         * Get the basic data template
         * We need to preserve comments here because the template is processed by the javascript template engine and it needs the INCLUDEIF/REPEATS
         */

        $template = new EasyRecipePlusTemplate(self::$EasyRecipePlusDir . "/templates/easyrecipe-template.html");

        $html = $template->getTemplateHTML(EasyRecipePlusTemplate::PRESERVECOMMENTS);
        $html = preg_replace('/\n */', ' ', $html);
        $html = trim(str_replace("'", "\"", $html));

        /*
        * Unless this is a guest post, get the URL we can test at Google (as long as it's published)
        */
        if (!$this->isGuest) {
            $testURL = $post->post_status == 'publish' ? urlencode(get_permalink($post->ID)) : '';
        } else {
            $testURL = '';
        }

        if ($this->isGuest) {
            /** @var $guestAuthor WP_User */
            $guestAuthor = get_user_by('id', $this->settings->gpUserID);
            /** @noinspection PhpUndefinedFieldInspection */
            $author = str_replace("'", '\x27', json_encode(str_replace('"', '\\"', $guestAuthor->data->display_name)));
        } else {
            $author = str_replace("'", '\x27', json_encode(str_replace('"', '\\"', $this->settings->author)));
        }


        $cuisines = str_replace("'", '\x27', json_encode(explode('|', str_replace('"', '\\"', $this->settings->cuisines))));
        $recipeTypes = str_replace("'", '\x27', json_encode(explode('|', str_replace('"', '\\"', $this->settings->recipeTypes))));

        $ingredients = str_replace("'", '\x27', json_encode(str_replace('"', '\\"', $this->settings->lblIngredients)));
        $instructions = str_replace("'", '\x27', json_encode(str_replace('"', '\\"', $this->settings->lblInstructions)));
        $notes = str_replace("'", '\x27', json_encode(str_replace('"', '\\"', $this->settings->lblNotes)));
        if (!function_exists('get_upload_iframe_src')) {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
        }
        // $upIframeSrc = get_upload_iframe_src();
        $guestPost = $this->isGuest ? 'true' : 'false';
        $noWarn = $this->settings->noHTMLWarn ? 'true' : 'false';
        $wpurl = get_bloginfo('wpurl');
        $url = self::$EasyRecipePlusUrl;
        $pluginVersion = self::$pluginVersion;
        echo <<<EOD
<script type="text/javascript">
/* <![CDATA[ */
window.EASYRECIPE = window.EASYRECIPE || {};
EASYRECIPE.ingredients ='$ingredients';
EASYRECIPE.instructions ='$instructions';
EASYRECIPE.notes ='$notes';
EASYRECIPE.version = '$pluginVersion';
EASYRECIPE.easyrecipeURL = '$url';
EASYRECIPE.recipeTemplate = '$html';
EASYRECIPE.testURL = '$testURL';
EASYRECIPE.author = '$author';
EASYRECIPE.recipeTypes = '$recipeTypes';
EASYRECIPE.cuisines = '$cuisines';
EASYRECIPE.isGuest = $guestPost;
EASYRECIPE.wpurl = '$wpurl';
EASYRECIPE.wpVersion = '$wp_version';
EASYRECIPE.postID = $post->ID;
EASYRECIPE.noHTMLWarn = $noWarn;
/* ]]> */
</script>
EOD;
    }

    /**
     * Convert a recipe from other plugins
     */
    function convertRecipe() {
        $convert = new EasyRecipePlusConvert();
        $convert->convertRecipe();
    }
}

