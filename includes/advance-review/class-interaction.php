<?php
/**
 * Advance review interaction class
 *
 * @package wpWax\Directorist
 * @subpackage Review
 * @since 7.x
 */
namespace wpWax\Directorist\Review;

defined( 'ABSPATH' ) || die();

class Interaction {

	const AJAX_ACTION = 'directorist_comment_interaction';

	public static function init() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'handle_request' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'handle_request' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 20 );
	}

	public static function enqueue_scripts() {
		wp_localize_script(
			'directorist-main-script',
			'directorist',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::AJAX_ACTION ),
				'action'  => self::AJAX_ACTION
			)
		);
	}

	public static function handle_request() {
		$nonce       = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		$comment_id  = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
		$interaction = isset( $_POST['interaction'] ) ? wp_unslash( $_POST['interaction'] ) : '';

		try {
			if ( ! wp_verify_nonce( $nonce, self::AJAX_ACTION ) ) {
				throw new \Exception( __( 'Invalid request.', 'diretorist' ), 401 );
			}

			if ( empty( $comment_id ) ) {
				throw new \Exception( __( 'Comment id cannot be empty.', 'diretorist' ), 400 );
			}

			if ( is_null( get_comment( $comment_id ) ) ) {
				throw new \Exception( __( 'Comment does not exist!.', 'diretorist' ), 400 );
			}

			if ( empty( $interaction ) || ( ! method_exists( __CLASS__, 'handle_' . $interaction ) ) ) {
				throw new \Exception( __( 'Your intended action is not clear. Please click on a valid interaction.', 'diretorist' ), 400 );
			}

			if ( in_array( $interaction, self::get_login_required_interactions(), true ) && ! is_user_logged_in() ) {
				throw new \Exception( __( 'You must login to complete this action.', 'diretorist' ) );
			}

			$response = call_user_func( array( __CLASS__, 'handle_' . $interaction ), $comment_id );

			if ( empty( $response ) ) {
				$response = __( 'Thank you for your feedback.', 'diretorist' );
			}

			wp_send_json_success( $response );

		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage(), $e->getCode() );
		}
	}

	protected static function handle_report( $comment_id ) {
		$reported = get_comment_meta( $comment_id, 'reported', true );

		if ( empty( $reported ) ) {
			$reported = 0;
		}

		update_comment_meta( $comment_id, 'reported', $reported + 1 );

		return __( 'Thank you for reporting.', 'directorist' );
	}

	protected static function handle_helpful( $comment_id ) {
		$helpful = get_comment_meta( $comment_id, 'helpful', true );

		if ( empty( $helpful ) ) {
			$helpful = 0;
		}

		update_comment_meta(  $comment_id, 'helpful', $helpful + 1 );

		return __( 'Nice to hear that it has been helpful.', 'directorist' );
	}

	protected static function handle_unhelpful( $comment_id ) {
		$unhelpful = get_comment_meta( $comment_id, 'unhelpful', true );

		if ( empty( $unhelpful ) ) {
			$unhelpful = 0;
		}

		update_comment_meta(  $comment_id, 'unhelpful', $unhelpful + 1 );

		return __( 'Thank you for your feedback.', 'directorist' );
	}

	protected static function get_login_required_interactions() {
		return array(
			'report'
		);
	}
}

Interaction::init();
