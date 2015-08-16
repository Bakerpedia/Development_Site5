<?php

/*
 Copyright (c) 2010-2015 Box Hill LLC
All Rights Reserved
No part of this software may be reproduced, copied, modified or adapted, without the prior written consent of Box Hill LLC.
Commercial use and distribution of any part of this software is not allowed without express and prior written consent of Box Hill LLC.
 */

/**
 * Class EasyRecipePlusPrint
 *
 * Print a recipe
 */
class EasyRecipePlusPrint {

    const JQUERYJS = "https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js";
    const JQUERYUIJS = "https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js";
    const JQUERYUICSS = "https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css";

    private $plugin;

    /**
     * EasyRecipePlusPrint constructor.
     * @param EasyRecipePlus $plugin Pointer back to EasyRecipePlus so we can acces some stuff there
     */
    public function __construct(EasyRecipePlus $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Get the post, extract the recipe and combine with the current style and output it
     *
     * @param integer $postID The post ID to print
     * @param integer $recipeIX The zero based index of the recipe in the post
     */
    public function printRecipe($postID, $recipeIX) {
        /** @var $wpdb wpdb */
        global $wpdb;

        $settings = EasyRecipePlusSettings::getInstance();

        /**
         * Be paranoid and force the ID to an integer
         */
        $postID = (int)$postID;
        $q = "SELECT * FROM $wpdb->posts WHERE ID = $postID";
        $post = $wpdb->get_row($q);

        if (!$post) {
            return;
        }

        /**
         * Process the [br] shortcodes and remove the spurious <br>'s that wp_auto() inserts
         */
        $content = str_replace("[br]", "<br>", $post->post_content);
        $content = preg_replace('%</div>\s*</p></div>%im', '</div></div>', $content);

        $content = $this->plugin->possiblyConvert($postID, $post->post_type, $content);

        $postDOM = new EasyRecipePlusDocument($content);

        if (!$postDOM->isEasyRecipe) {
            return;
        }

        /**
         * If the post is formatted already then it came from the Object cache (?)
         * If that's the case we need to re-read the original
         */
        if ($postDOM->isFormatted) {
            $post = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "posts WHERE ID = $postID");

            $content = str_replace("[br]", "<br>", $post->post_content);
            $content = preg_replace('%</div>\s*</p></div>%im', '</div></div>', $content);

            $content = $this->plugin->possiblyConvert($postID, '', $content);
            $postDOM = new EasyRecipePlusDocument($content);

            if (!$postDOM->isEasyRecipe) {
                return;
            }
        }

        if (isset($_GET['style'])) {
            $styleName = $_GET['style'];
        } else {
            $styleName = $settings->printStyle;
        }


//        $printStyleData = call_user_func(array($this->stylesClass, 'getStyleData'), $styleName, $settings->get('customTemplates'), true);
        $printStyleData = EasyRecipePlusStyles::getStyleData($styleName, $settings->customTemplates, true);
        if (get_locale() != 'en_US') {
            EasyRecipePlusTemplate::setTranslate('easyrecipe');
        }

        /**
         * Fix possibly broken times in older posts
         * Fix the Cholesterol oops in early versions
         */

        if ($postDOM->recipeVersion < '3') {
            $postDOM->fixTimes("preptime");
            $postDOM->fixTimes("cooktime");
            $postDOM->fixTimes("duration");
            $postDOM->setParentValueByClassName("cholestrol", $settings->lblCholesterol, "Cholestrol");
        }

        $postDOM->setSettings($settings);
        $data = new stdClass();
        $data->hasRating = false;
        $data->convertFractions = $settings->convertFractions;

        $settings->getLabels($data);
        $data->hasLinkback = $settings->allowLink;
        $data->title = $post->post_title;
        $data->blogname = get_option("blogname");
        $data->recipeurl = get_permalink($post->ID);

        $data->customCSS = $this->plugin->getCSS('Print');
        $data->extraPrintHeader = $settings->extraPrintHeader;

        $data->easyrecipeURL = EasyRecipePlus::$EasyRecipePlusUrl;


        $recipe = $postDOM->getRecipe($recipeIX);
        $photoURL = $postDOM->findPhotoURL($recipe);
        $data->hasPhoto = !empty($photoURL);

        $data->jqueryjs = self::JQUERYJS;
        $data->jqueryuijs = self::JQUERYUIJS;
        $data->jqueryuicss = self::JQUERYUICSS;

        if (current_user_can('edit_posts')) {
            $data->isAdmin = true;
            $data->formatDialog = $this->plugin->getFormatDialog($printStyleData, true);
            $cssLink = '<link href="' . EasyRecipePlus::$EasyRecipePlusUrl . '/css/%s?version=' . EasyRecipePlus::$pluginVersion . '" rel="stylesheet" type="text/css"/>';
            $jsLink = '<script type="text/javascript" src="' . EasyRecipePlus::$EasyRecipePlusUrl . '/js/%s?version=' . EasyRecipePlus::$pluginVersion . '"></script>';

            $data->formatCSS = sprintf($cssLink, 'easyrecipe-format-min.css');
            $data->formatJS = sprintf($jsLink, 'easyrecipe-format-min.js');
        } else {
            $data->formatDialog = '';
            $data->printJS = '<script type="text/javascript" src="' . EasyRecipePlus::$EasyRecipePlusUrl . '/js/easyrecipe-print-min.js?version=' . EasyRecipePlus::$pluginVersion . '"></script>';
        }

        $data->style = $styleName;

        if ($data->style[0] == '_') {
            $style = substr($data->style, 1);
            $data->css = "/easyrecipe-printstyle";
            $templateFile = $settings->customTemplates . "/printstyles/$style/style.html";
        } else {
            $data->css = EasyRecipePlus::$EasyRecipePlusUrl . "/printstyles/$data->style";
            $templateFile = EasyRecipePlus::$EasyRecipePlusDir . "/printstyles/$data->style/style.html";
        }

        $data->css .= "/style.css?version=" . EasyRecipePlus::$pluginVersion . ".{$printStyleData->version}";

        $template = new EasyRecipePlusTemplate($templateFile);

        /**
         * Brain dead IE shows "friendly" error pages (i.e. it's non-compliant) so we need to force a 200
         */
        header("HTTP/1.1 200 OK");
        /**
         * Set the character encoding explicitly
         */
        $charset = get_bloginfo('charset');
        header("Content-Type:text/html; charset=$charset");
        echo $postDOM->formatRecipe($recipe, $template, $data);
        flush();

        exit();
    }
}
