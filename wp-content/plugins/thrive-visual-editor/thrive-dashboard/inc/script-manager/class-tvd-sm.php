<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

final class TVD_SM {


	/**
	 * The single instance of the class.
	 *
	 * @var TVD_SM singleton instance.
	 */
	protected static $_instance = null;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->includes();
	}

	/**
	 * Main Instance.
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @return TVD_SM
	 */
	public static function instance() {
		if ( empty( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Include needed files.
	 */
	private function includes() {

		require_once( 'class-tvd-sm-constants.php' );

		/* if neither TTB nor TAR are active, don't display anything */
		if ( ! TVD_SM_Constants::is_architect_active() && ! TVD_SM_Constants::is_ttb_active() ) {
			return;
		}

		require_once( 'includes/admin/classes/class-tvd-sm-admin.php' );
		require_once( 'includes/frontend/classes/class-tvd-sm-frontend.php' );


	}
}

function TVD_SM() {
	return TVD_SM::instance();
}

TVD_SM();
