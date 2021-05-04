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
		add_action( 'edit_comment', array( __CLASS__, 'on_edit_comment' ), 10, 2 );
	}

	public static function on_edit_comment( $id, $data ) {
		file_put_contents( __DIR__ . '/data.txt', print_r( $data, 1 ) );
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

	public static function render( $comment ) {
		$builder     = Builder::get( $comment->comment_post_ID );
		$comment_id  = $comment->comment_ID;
		$helpful     = (int) get_comment_meta( $comment_id, 'helpful', true );
		$unhelpful   = (int) get_comment_meta( $comment_id, 'unhelpful', true );
		$reported    = (int) get_comment_meta( $comment_id, 'reported', true );
		$rating      = (float) get_comment_meta( $comment_id, 'rating', true );
		$attachments = get_comment_meta( $comment_id, 'attachments', true );

		$criteria = $builder->get_rating_criteria();

		$rating_required = $builder->get_field_prop( 'rating', 'required', true ) ? 'required="required"' : '';
		?>
		<style>
		.comment-attachments {
			display: flex;
		}
		.comment-attachments a {
			display: block;
			max-width: 150px;
			flex: 0 0 150px;
			margin: 5px;
		}
		.comment-attachments img {
			height: 150px;
			width: 100%;
			object-fit: cover;
			padding: 3px;
			border: 1px solid #eee;
			border-radius: 3px;
		}
		</style>
		<table class="form-table">
			<tbody>
				<tr>
					<th><?php esc_html_e( 'Helpful', 'directorist' ); ?></th>
					<td><?php echo $helpful; ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Unhelpful', 'directorist' ); ?></th>
					<td><?php echo $unhelpful; ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Reported', 'directorist' ); ?></th>
					<td><?php echo $reported; ?></td>
				</tr>
				<?php if ( $builder->is_field_active( 'media' ) && ! empty( $attachments ) && is_array( $attachments ) ) : ?>
					<tr>
						<th><?php esc_html_e( 'Images', 'directorist' ); ?></th>
						<td>
							<div class="comment-attachments">
								<?php foreach ( $attachments as $attachment ) {
									printf( '<a href="%1$s" target="_blank"><img src="%1$s" alt=""></a>', self::get_image_url( $attachment ) );
								} ?>
							</div>
						</td>
					</tr>
				<?php endif; ?>

				<?php if ( $builder->is_field_active( 'rating' ) ) : ?>
					<tr><td colspan="2"><hr></td></tr>
					<?php if ( ! empty( $criteria ) ) : ?>
						<tr>
							<th><?php esc_html_e( 'Avg Rating', 'directorist' ); ?></th>
							<td><?php echo $rating; ?></td>
						</tr>
						<?php
						$criteria_rating = Comment::get_criteria_rating( $comment_id );
						foreach ( $criteria as $k => $v ) :
							$r = isset( $criteria_rating[ $k ] ) ? $criteria_rating[ $k ] : 0;
							?>
							<tr>
								<th><?php echo $v; ?></th>
								<td>
									<select name="rating[<?php echo $k; ?>]" <?php echo $rating_required; ?>>
										<option value="0">No Rating</option>
										<option value="1" <?php selected( $r, 1 ); ?>>1</option>
										<option value="2" <?php selected( $r, 2 ); ?>>2</option>
										<option value="3" <?php selected( $r, 3 ); ?>>3</option>
										<option value="4" <?php selected( $r, 4 ); ?>>4</option>
										<option value="5" <?php selected( $r, 5 ); ?>>5</option>
									</select>
								</td>
							</tr>
						<?php
						endforeach; ?>
					<?php else : $r = floor( $rating ); ?>
						<tr>
							<th><?php esc_html_e( 'Rating', 'directorist' ); ?></th>
							<td>
								<select name="rating" <?php echo $rating_required; ?>>
									<option value="0">No Rating</option>
									<option value="1" <?php selected( $r, 1 ); ?>>1</option>
									<option value="2" <?php selected( $r, 2 ); ?>>2</option>
									<option value="3" <?php selected( $r, 3 ); ?>>3</option>
									<option value="4" <?php selected( $r, 4 ); ?>>4</option>
									<option value="5" <?php selected( $r, 5 ); ?>>5</option>
								</select>
							</td>
						</tr>
					<?php endif; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	protected static function get_image_url( $attachment ) {
		$dir = wp_get_upload_dir();
		return trailingslashit( $dir['baseurl'] ) . $attachment;
	}
}

Metabox::init();
