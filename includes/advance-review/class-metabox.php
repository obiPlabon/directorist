<?php
/**
 * Comment metabox class.
 *
 * @package wpWax\Directorist
 * @subpackage Review
 * @since 7.x
 */
namespace wpWax\Directorist\Review;

defined( 'ABSPATH' ) || die();

class Metabox {
	public static function init() {
		add_action( 'add_meta_boxes_comment', array( __CLASS__, 'register' ) );
	}

	public static function register( $comment ) {
		if ( get_post_type( $comment->comment_post_ID ) !== ATBDP_POST_TYPE ) {
			return;
		}

		add_meta_box(
			'directorist-comment-mb',
			( $comment->comment_type === 'review' ? __( 'Review extra', 'directorist' ) : __( 'Comment extra', 'directorist' ) ),
			array( __CLASS__, 'render' ),
			'comment',
			'normal',
			'high'
		);
	}

	public static function render() {
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th>Helpful</th><td>10</td>
				</tr>
				<tr>
					<th>Unhelpful</th><td>10</td>
				</tr>
				<tr>
					<th>Report</th><td>10</td>
				</tr>
			</tbody>
		</table>
		<?php
	}
}

Metabox::init();
