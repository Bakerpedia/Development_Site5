<?php
/**
 * Provides basic run-time tests to ensure the plugin can run in the current WordPress environment
 *
 * @package Media Library Assistant
 * @since 0.1
 */

/**
 * Class MLA (Media Library Assistant) Test provides basic run-time tests
 * to ensure the plugin can run in the current WordPress envrionment.
 *
 * @package Media Library Assistant
 * @since 0.1
 */
class MLATest {
	/**
	 * True if WordPress version is 4.3 or newer
	 *
	 * @since 2.13
	 *
	 * @var	boolean
	 */
	public static $wp_4dot3_plus = null;
	
	/**
	 * Initialization function, similar to __construct()
	 *
	 * @since 0.60
	 *
	 * @return	void
	 */
	public static function initialize() {
		MLATest::$wp_4dot3_plus = version_compare( get_bloginfo( 'version' ), '4.2.99', '>=' );

		/*
		 * This is the earliest effective place to add E_STRICT to error_reporting
		 */
		//error_reporting( E_ALL | E_STRICT | E_DEPRECATED | E_USER_DEPRECATED | E_USER_ERROR | E_USER_WARNING );
		//error_reporting( E_ALL | E_STRICT );
	}

	/**
	 * Test that your PHP version is at least that of the $min_php_version
	 *
	 * @since 0.1
	 *
	 * @param	string	representing the minimum required version of PHP, e.g. '5.3.2'
	 *
	 * @return	string	'' if pass else error message
	 */
	public static function min_php_version( $min_version )
	{
		$current_version = phpversion();
		if ( version_compare( $current_version, $min_version, '<' ) ) {
			return sprintf( '<li>The plugin requires PHP %1$s or newer; you have %2$s.<br />Contact your system administrator about updating your version of PHP.</li>', /*$1%s*/ $min_version, /*$2%s*/ $current_version );
		}

		return '';
	}
	
	/**
	 * Test that your WordPress version is at least that of the $min_version
	 *
	 * @since 0.1
	 *
	 * @param string	representing the minimum required version of WordPress, e.g. '3.5.0'
	 *
	 * @return	string	'' if pass else error message
	 */
	public static function min_WordPress_version( $min_version )
	{
		$current_version = get_bloginfo( 'version' );
		if ( version_compare( $current_version, $min_version, '<' ) ) {
			return sprintf( '<li>The plugin requires WordPress %1$s or newer; you have %2$s.<br />Contact your system administrator about updating your version of WordPress.</li>', /*$1%s*/ $min_version, /*$2%s*/ $current_version );
		}

		return '';
	}
	
} // class MLATest
?>