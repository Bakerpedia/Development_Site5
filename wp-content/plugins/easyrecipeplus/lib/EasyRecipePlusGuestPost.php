<?php
/**
 * Class EasyRecipePlusGuestPost
 *
 * Handles Guest Post processing
 */
class EasyRecipePlusGuestPost {

    /**
     * @var EasyRecipePlusSettings
     */
    private $settings;

    public $gpUserID;
    public $gpCopyDetails;
    public $gpUseGravity;
    public $gpGravityFormID;
    public $gpDetailsPage;
    public $gpEntryPage;
    public $gpThanksPage;
    public $gpHideFooter;

    public $lblGPName;
    public $lblGPEmail;
    public $lblGPWebsite;
    public $lblGPContinue;
    public $lblGPPostTitle;
    public $lblGPHint;
    public $lblGPMessage;
    public $lblGPSubmitPost;

    private $plugin;

    const GUESTPOST = '/\[\s*easyguestpost\s*(?:title\s*=\s*[\'|"](.*)[\'|"])?\s*\]/i';

    /**
     * Save a reference to the global settings
     * Add a reference to this instance in an array indexed by postID so that, given a postID, we can figure out which instance to use
     *
     * @param EasyRecipePlus $plugin
     */
    function __construct(EasyRecipePlus $plugin) {
        $this->plugin = $plugin;
        $this->settings = EasyRecipePlusSettings::getInstance();
    }

    /**
     * @param $post
     * @return null
     */
    public function process(&$post) {
        switch ($post->ID) {
            /**
             * The details page can either be the standard plain template or it could be a Gravity form (TODO)
             */
            case $this->settings->gpDetailsPage :
                if (preg_match(self::GUESTPOST, $post->post_content, $regs)) {
                    $data = new stdClass();
                    $data->title = isset($regs[1]) ? $regs[1] : 'Submit A Guest Post';
                    $data->next = get_permalink($this->settings->gpEntryPage);
                    $data->nonce = wp_create_nonce('guestpostdetails');
                    $data->lblGPName = $this->settings->lblGPName;
                    $data->lblGPEmail = $this->settings->lblGPEmail;
                    $data->lblGPWebsite = $this->settings->lblGPWebsite;
                    $data->lblGPContinue = $this->settings->lblGPContinue;
                    $template = new EasyRecipePlusTemplate(EasyRecipePlus::$EasyRecipePlusDir. "/templates/easyrecipe-guestpostdetails.html");
                    $post->post_content = preg_replace(self::GUESTPOST, $template->replace($data), $post->post_content);
                }
                break;

            case $this->settings->gpEntryPage :
                if (preg_match(self::GUESTPOST, $post->post_content)) {
                    $post->post_content = preg_replace(self::GUESTPOST, $this->guestPostEntry(), $post->post_content);
//                    $newPosts[count($newPosts)] = $post;
                    return $post;
                }
                break;
            /*
            * Insert the guest post
            * If the insert returns, then we have a valid post - check if we need to display it
            */
            case $this->settings->gpThanksPage :
                $guestPost = $this->guestPostInsert();
                if (preg_match(self::GUESTPOST, $post->post_content)) {
                    $post->post_content = preg_replace(self::GUESTPOST, $guestPost['post_content'], $post->post_content);
                }
                break;
        }

        return null;
    }

    public function guestPostInsert() {
        global $allowedposttags;
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';

        /**
         * Check the nonce first
         */
        if (!wp_verify_nonce($nonce, 'guestpostentry')) {
            wp_redirect(get_permalink($this->settings->gpDetailsPage));
            exit();
        }

        $post = array();
        $postData = isset($_POST['ERGuestPost']) ? $_POST['ERGuestPost'] : '';
        // $gfLead = 0;

        if ($postData == '' || !isset($postData['egpname']) || !isset($postData['egpemail']) || !isset($postData['egpurl'])) {
            wp_redirect(get_permalink($this->settings->gpDetailsPage));
            exit();
        }

        if ($postData == '' || !isset($postData['egpname']) || !isset($postData['egpemail']) || !isset($postData['egpurl'])) {
            wp_redirect(get_permalink($this->settings->gpDetailsPage));
            exit();
        }


        $postData = stripslashes_deep($postData);
        $post['post_title'] = sanitize_text_field($postData['egpTitle']);
        $postID = (int) $_POST['postID'];
        $post['post_author'] = $this->settings->gpUserID;
        /**
         * Accept time and link tags
         */
        $allowedposttags['time'] = array('itemprop' => true, 'datetime' => true);
        $allowedposttags['link'] = array('itemprop' => true, 'href' => true);
        $post['post_content'] = wp_kses_post(stripslashes($_POST['guestpost']));
        $post['post_status'] = 'pending';

        /**
         * TODO - Add in a check for wrappers
         */
        // add_filter('wp_insert_post_data', array ($this, 'postSave'), 10, 2);


        if ($postID == 0) {
            $postID = wp_insert_post($post);
        } else {
            $post['ID'] = $postID;
            wp_update_post($post);
        }
        add_post_meta($postID, '_guestPost', true);
        add_post_meta($postID, '_guestComment', sanitize_text_field($postData['egpcomment']));
        add_post_meta($postID, '_guestAuthor', sanitize_text_field($postData['egpname']));
        add_post_meta($postID, '_guestEmail', sanitize_email($postData['egpemail']));
        add_post_meta($postID, '_guestURL', esc_url_raw($postData['egpurl']));
        return $post;
    }

    function guestPostUpload() {
        nocache_headers();

        $response = new stdClass();

        $postID = $_REQUEST['postID'];
        if ($postID == 0) {
            $post = get_default_post_to_edit('post', true);
            $postID = $post->ID;
            $response->postID = $postID;
        }
        $id = media_handle_upload('file', $postID);

        if (is_wp_error($id)) {
            // todo
            // esc_html($id->get_error_message()) . '</div>';
        }

        $post = get_post($id);

        //$filename = esc_html(basename($post->guid));
        //$title = esc_attr($post->post_title);
        //$link = '';
        // $post->post_mime_type
        $meta = wp_get_attachment_metadata($id);



        $response->jsonrpc = '2.0';
        $response->result = null;
        $response->meta = $meta;
        if (!isset($response->meta['sizes'])) {
            $response->meta['sizes'] = array();
        }
        unset($response->meta['image_meta']);
        $file = basename($meta['file']);
        $response->meta['file'] = $file;

        $response->id = $id;

        $uploadPath = wp_upload_dir();
        $response->meta['filesize'] = number_format(filesize($uploadPath['basedir'] . $uploadPath['subdir'] . '/' . $file), 0) . ' bytes';
        $response->baseurl = pathinfo($post->guid, PATHINFO_DIRNAME) . '/';
        die(json_encode($response));
    }


    function guestPostEntry() {
        $this->plugin->isGuest = true;
        add_filter('tiny_mce_before_init', array($this->plugin, 'mcePreInitialise'));
        add_filter('mce_external_plugins', array($this->plugin, 'mcePlugins'));
        add_filter('mce_buttons', array($this->plugin, 'mceButtons'));

        $data = new stdClass();

        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        $postData = isset($_POST['ERGuestPost']) ? $_POST['ERGuestPost'] : '';

        if (!wp_verify_nonce($nonce, 'guestpostdetails') || $postData == '' || !isset($postData['egpname']) || !isset($postData['egpemail']) || !isset($postData['egpurl'])) {
            wp_redirect(get_permalink($this->settings->gpDetailsPage));
            exit();
        }
        $postData = $_POST['ERGuestPost'];
        $data->name = $postData['egpname'];
        $data->email = $postData['egpemail'];
        $data->url = $postData['egpurl'];

//        add_filter('tiny_mce_before_init', array($this, 'mcePreInitialise'));
//        add_filter('mce_external_plugins', array($this, 'mcePlugins'));
//        add_filter('mce_buttons', array($this, 'mceButtons'));

        ob_flush();  // fixme - check to see if there IS a buffer
        ob_start();
        wp_editor('', 'guestpost');

        $data->editor = ob_get_clean();


        $data->postID = 0;
        $data->gpHideFooter = $this->settings->gpHideFooter;

        $data->lblGPPostTitle = $this->settings->lblGPPostTitle;
        $data->lblGPHint = $this->settings->lblGPHint;
        $data->lblGPMessage = $this->settings->lblGPMessage;
        $data->lblGPSubmitPost = $this->settings->lblGPSubmitPost;

        $data->nonce = wp_create_nonce('guestpostentry');
        $data->next = $data->next = get_permalink($this->settings->gpThanksPage);


        $template = new EasyRecipePlusTemplate(EasyRecipePlus::$EasyRecipePlusDir . "/templates/easyrecipe-upload.html");
        $data->uploadDialog = $template->getTemplateHTML();

        $template = new EasyRecipePlusTemplate(EasyRecipePlus::$EasyRecipePlusDir . "/templates/easyrecipe-links.html");
        $data->linksDialog = $template->getTemplateHTML();

        $template = new EasyRecipePlusTemplate(EasyRecipePlus::$EasyRecipePlusDir . "/templates/easyrecipe-guestpost.html");
        return $template->replace($data);
    }

}

