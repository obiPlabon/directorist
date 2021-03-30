<?php
/**
 * Background updater notice class.
 *
 * @package wpWax/Directorist
 */
namespace wpWax\Directorist;

defined( 'ABSPATH' ) || die();

class Updater_Notice {

	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'show_notice' ) );
	}

	public static function get_screen_ids() {
		$root_slug = 'at_biz_dir';

		return array(
			$root_slug,
			'edit-' . $root_slug,
			'edit-' . $root_slug . '-location',
			'edit-' . $root_slug . '-category',
			'edit-' . $root_slug . '-tags',
			$root_slug . '_page_atbdp-directory-types',
			$root_slug . '_page_atbdp-settings',
			$root_slug . '_page_directorist-status',
			$root_slug . '_page_atbdp-extension',
			'edit-atbdp_listing_review',
			'dashboard',
			'plugins',
		);
	}

	public static function show_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen          = get_current_screen();
		$screen_id       = $screen ? $screen->id : '';

		if ( ! in_array( $screen_id, self::get_screen_ids(), true ) ) {
			return;
		}

		if ( version_compare( get_option( 'directorist_db_version' ), ATBDP_VERSION, '<' ) ) {
			$updater = new Background_Updater();
			if ( $updater->is_updating() || ! empty( $_GET['do_update_directorist'] ) ) { // WPCS: input var ok, CSRF ok.
				self::updating_notice();
			} else {
				self::update_notice();
			}
		} else {
			self::updated_notice();
		}
	}

	public static function update_notice() {
		$update_url = wp_nonce_url(
			add_query_arg( 'do_update_directorist', 'true', admin_url( 'edit.php?post_type=at_biz_dir&page=atbdp-settings' ) ),
			'directorist_db_update',
			'directorist_db_update_nonce'
		);

		?>
		<div id="message" class="updated directorist-message">
			<p>
				<strong><?php esc_html_e( 'Directorist data update', 'directorist' ); ?></strong> &#8211; <?php esc_html_e( 'We need to update your directory database to the latest version.', 'directorist' ); ?>
			</p>
			<p class="submit">
				<a href="<?php echo esc_url( $update_url ); ?>" class="directorist-update-now button-primary">
					<?php esc_html_e( 'Run the updater', 'directorist' ); ?>
				</a>
			</p>
		</div>
		<script type="text/javascript">
			jQuery( '.directorist-update-now' ).click( 'click', function() {
				return window.confirm( '<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'directorist' ) ); ?>' ); // jshint ignore:line
			});
		</script>
		<?php
	}

	public static function updating_notice() {
		$force_update_url = wp_nonce_url(
			add_query_arg( 'force_update_directorist', 'true', admin_url( 'edit.php?post_type=at_biz_dir&page=atbdp-settings' ) ),
			'directorist_force_db_update',
			'directorist_force_db_update_nonce'
		);

		?>
		<div id="message" class="updated directorist-message">
			<p>
				<strong><?php esc_html_e( 'Directorist data update', 'directorist' ); ?></strong> &#8211; <?php esc_html_e( 'Your database is being updated in the background.', 'directorist' ); ?>
				<a href="<?php echo esc_url( $force_update_url ); ?>">
					<?php esc_html_e( 'Taking a while? Click here to run it now.', 'directorist' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public static function updated_notice() {
		?>
		<div id="message" class="updated directorist-message">
			<a class="directorist-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'directorist-hide-notice', 'update', remove_query_arg( 'do_update_directorist' ) ), 'directorist_hide_notices_nonce', '_directorist_notice_nonce' ) ); ?>"><?php _e( 'Dismiss', 'directorist' ); ?></a>

			<p><?php _e( 'Directorist data update complete. Thank you for updating to the latest version!', 'directorist' ); ?></p>
		</div>
		<?php
	}
}
add_action( 'admin_init', array( __NAMESPACE__ . '\Updater_Notice', 'init' ) );
