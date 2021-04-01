<?php
/**
 * Install Function
 *
 * @package     Directorist
 * @copyright   Copyright (c) 2018, AazzTech
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use wpWax\Directorist\Background_Updater;

if ( ! class_exists( 'ATBDP_Installation' ) ) :

/**
 * Installation class.
 */
class ATBDP_Installation {

	/**
	 * DB updates and callbacks that need to be run per version.
	 *
	 * @var array
	 */
	private static $db_updates = array(
		'7.0.3.2' => array(
			'directorist_7032_do_some_db_updates',
			'directorist_7032_do_something_else',
		),
	);

	/**
	 * Background update class.
	 *
	 * @var object
	 */
	private static $background_updater;

	/**
	 *It installs the required features or options for the plugin to run properly.
		* @link https://codex.wordpress.org/Function_Reference/register_post_type
		* @return void
		*/
	public static function install()
	{
		include_once  ATBDP_INC_DIR.'review-rating/class-review-rating-database.php'; // include review class
		require_once ATBDP_CLASS_DIR . 'class-custom-post.php'; // include custom post class
		require_once ATBDP_CLASS_DIR . 'class-roles.php'; // include custom roles and Caps
		$ATBDP_Custom_Post = new ATBDP_Custom_Post();
		$ATBDP_Custom_Post->register_new_post_types();
		$Review_DB = new ATBDP_Review_Rating_DB();
		$Review_DB->create_table(); // create table for storing reviews and ratings of the listings
		flush_rewrite_rules(); // lets flash the rewrite rules as we have registered the custom post

		// Add custom ATBDP_Roles & Capabilities
		if( ! get_option( 'atbdp_roles_mapped' ) ) {
			$roles = new ATBDP_Roles;
			$roles->add_caps();
		}

		// Insert atbdp_roles_mapped option to the db to prevent mapping meta cap
		add_option( 'atbdp_roles_mapped', true );

		$atbdp_option = get_option('atbdp_option');
		$atpdp_setup_wizard = apply_filters( 'atbdp_setup_wizard', true );

		if( ! $atbdp_option && $atpdp_setup_wizard ) {
			set_transient( '_directorist_setup_page_redirect', true, 30 );
		}

		self::maybe_update_db_version();
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'init_background_updater' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
	}

	/**
	 * Init background updates
	 */
	public static function init_background_updater() {
		include_once ATBDP_INC_DIR . 'classes/class-background-updater.php';
		self::$background_updater = new Background_Updater();
	}

	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * This function is hooked into admin_init to affect admin only.
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_directorist'] ) ) { // WPCS: input var ok.
			check_admin_referer( 'directorist_db_update', 'directorist_db_update_nonce' );
			self::update();
			// WC_Admin_Notices::add_notice( 'update' );
		}

		if ( ! empty( $_GET['force_update_directorist'] ) ) { // WPCS: input var ok.
			check_admin_referer( 'directorist_force_db_update', 'directorist_force_db_update_nonce' );
			$blog_id = get_current_blog_id();

			// Used to fire an action added in WP_Background_Process::_construct() that calls WP_Background_Process::handle_cron_healthcheck().
			// This method will make sure the database updates are executed even if cron is disabled. Nothing will happen if the updates are already running.
			do_action( 'wp_' . $blog_id . '_directorist_updater_cron' );

			wp_safe_redirect( admin_url( 'edit.php?post_type=at_biz_dir&page=atbdp-settings' ) );
			exit;
		}
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 */
	private static function update() {
		$current_db_version = get_option( 'directorist_db_version' );
		// $logger             = wc_get_logger();
		$update_queued      = false;

		foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					// $logger->info(
					// 	sprintf( 'Queuing %s - %s', $version, $update_callback ),
					// 	array( 'source' => 'wc_db_updates' )
					// );
					self::$background_updater->push_to_queue( $update_callback );
					$update_queued = true;
				}
			}
		}

		if ( $update_queued ) {
			self::$background_updater->save()->dispatch();
		}
	}

	/**
	 * Update DB version to current.
	 *
	 * @param string|null $version New WooCommerce DB version or null.
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'directorist_db_version' );
		add_option( 'directorist_db_version', is_null( $version ) ? ATBDP_VERSION : $version );
	}

	/**
	 * See if we need to show or run database updates during install.
	 *
	 */
	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			if ( apply_filters( 'directorist_enable_auto_update_db', false ) ) {
				self::init_background_updater();
				self::update();
			} else {
				// WC_Admin_Notices::add_notice( 'update' );
			}
		} else {
			self::update_db_version();
		}
	}

	/**
	 * Is a DB update needed?
	 *
	 * @return boolean
	 */
	private static function needs_db_update() {
		$current_db_version = get_option( 'directorist_db_version', null );
		$updates            = self::get_db_update_callbacks();

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, max( array_keys( $updates ) ), '<' );
	}
}

ATBDP_Installation::init();

endif;
