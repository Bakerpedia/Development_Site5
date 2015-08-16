<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://zach-adams.com
 * @since      1.0.0
 *
 * @package    Media_Library_Refresher
 * @subpackage Media_Library_Refresher/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Media_Library_Refresher
 * @subpackage Media_Library_Refresher/admin
 * @author     Zach Adams <zach@zach-adams.com>
 */
class Media_Library_Refresher_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $media_library_refresher    The ID of this plugin.
	 */
	private $media_library_refresher;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $media_library_refresher       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $media_library_refresher, $version ) {

		$this->media_library_refresher = $media_library_refresher;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->media_library_refresher, plugin_dir_url( __FILE__ ) . 'css/media-library-refresher-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {


		wp_register_script( $this->media_library_refresher, plugin_dir_url( __FILE__ ) . 'js/media-library-refresher-admin.js', array( 'jquery' ), $this->version, TRUE );

		$data = array(
			'plugin_dir_path' => plugin_dir_url( __FILE__ ),
			'post_id'         => '-1',
			'nonce'           => wp_create_nonce( "mlr-security-check" )
		);
		wp_localize_script( $this->media_library_refresher, 'mlr', $data );

		wp_enqueue_script( $this->media_library_refresher );

	}

		/**
	 * Adds the options page to the WordPress dashboard menu
	 *
	 * @since    1.0.0
	 */
	public function add_settings_page() {
		add_options_page(
			'Media Library Refresher',
			'Media Library Refresher',
			'manage_options',
			'media-library-refresher',
			array( $this, 'render_settings_page' )
		);
	}

		/**
	 * Renders the settings page content
	 *
	 * @since    1.0.0
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h2>Media Library Refresher</h2>
			<div class="loader"></div>
			<div class="check"></div>
			<div class="scan-begin">
				<p>Click Scan Media Library to begin!</p>
				<button type="button" class="button scan">
					Scan Media Library
				</button>
			</div>
			<div class="scan-results">
				<h4>Number of Media Items ready to rebuild: <span class="variable-media-items">0</span></h4>
			</div>
			<div class="begin-linking">
				<button type="button" class="button media_items">
					Begin Refreshing Media Library
				</button>
			</div>
			<div class="linking-results">
				<h3>Refreshing Media Items <span class="first-num">1</span> of <span class="second-num">1</span></h3>
				<ul class="updates">

				</ul>
			</div>
		</div>
	<?php
	}


	public function mlr_get_media_items() {
		check_ajax_referer( 'mlr-security-check', 'security' );

		$post_id = intval( $_POST['post_id'] );

		if ( ! $post_id ) {
			die();
		}

		$items = new WP_Query( array(
			'post_status' => 'any',
			'post_type'   => 'attachment',
			'posts_per_page' => -1,
			'fields'         => 'ids'
		) );

		$return = array();
		if ( isset( $items->posts ) && ! empty( $items->posts ) ) {
			foreach ( (array) $items->posts as $id ) {
				array_push( $return, $id );
			}
		}
		echo json_encode($return);
		die();
	}

	/**
	 * Link all variations via ajax function
	 */
	public function mlr_refresh_item() {

		check_ajax_referer( 'mlr-security-check', 'security' );

		@set_time_limit( 0 );

		$attachmentid = intval( $_POST['attachmentid'] );

		$offset = intval($_POST['offset']);

		$upload_dir = wp_upload_dir();

		if ( ! $attachmentid || !isset($offset) ) {
			echo 'No attachment id or offset';
			die();
		}

		$attachment = get_post($attachmentid);

		$meta = wp_get_attachment_metadata($attachmentid, "_wp_attachment_metadata", true);
	    $file = $meta['file'];
	    $file_path = basename(dirname((dirname($file)))) . '/' . basename(dirname($file)) . '/' . basename($file);

	    $adding_meta = update_post_meta($attachmentid, '_wp_attached_file', $file_path);

		$attach_data = wp_generate_attachment_metadata( $attachmentid, get_attached_file( $attachmentid ) );

		$newid = wp_update_attachment_metadata( $attachmentid,  $attach_data );

		$newoffset = $offset + 1;
		echo json_encode(array(
			'new_offset' => $newoffset,
			'attachment_title'  => $attachment->post_name
		));
		die();
	}
}