<?php
/**
 * Advance review walker class.
 *
 * @package wpWax\Directorist
 * @subpackage Review
 * @since 7.x
 */
namespace wpWax\Directorist\Review;

defined( 'ABSPATH' ) || die();

use Walker_Comment;
use function wpWax\Directorist\Review\show_rating_stars;

class Walker extends Walker_Comment {

	/**
	 * Starts the list before the elements are added.
	 *
	 * @see Walker::start_lvl()
	 * @global int $comment_depth
	 *
	 * @param string $output Used to append additional content (passed by reference).
	 * @param int    $depth  Optional. Depth of the current comment. Default 0.
	 * @param array  $args   Optional. Uses 'style' argument for type of HTML list. Default empty array.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$GLOBALS['comment_depth'] = $depth + 1;

		$output .= '<ul class="directorist-review-single__comments">' . "\n";
	}

	/**
	 * Ends the list of items after the elements are added.
	 *
	 * @see Walker::end_lvl()
	 * @global int $comment_depth
	 *
	 * @param string $output Used to append additional content (passed by reference).
	 * @param int    $depth  Optional. Depth of the current comment. Default 0.
	 * @param array  $args   Optional. Will only append content if style argument value is 'ol' or 'ul'.
	 *                       Default empty array.
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$GLOBALS['comment_depth'] = $depth + 1;

		$output .= "</ul><!-- .directorist-review-single__comments -->\n";
	}

	/**
	 * Ends the element output, if needed.
	 *
	 * @see Walker::end_el()
	 * @see wp_list_comments()
	 *
	 * @param string     $output  Used to append additional content. Passed by reference.
	 * @param WP_Comment $comment The current comment object. Default current comment.
	 * @param int        $depth   Optional. Depth of the current comment. Default 0.
	 * @param array      $args    Optional. An array of arguments. Default empty array.
	 */
	public function end_el( &$output, $comment, $depth = 0, $args = array() ) {
		if ( ! empty( $args['end-callback'] ) ) {
			ob_start();
			call_user_func( $args['end-callback'], $comment, $args, $depth );
			$output .= ob_get_clean();
			return;
		}

		$output .= "</li><!-- #comment-{$comment->comment_ID} -->\n";
	}

	/**
	 * Outputs a comment in the HTML5 format.
	 *
	 * @since 3.6.0
	 *
	 * @see wp_list_comments()
	 *
	 * @param WP_Comment $comment Comment to display.
	 * @param int        $depth   Depth of the current comment.
	 * @param array      $args    An array of arguments.
	 */
	protected function html5_comment( $comment, $depth, $args ) {
		$commenter          = wp_get_current_commenter();
		$show_pending_links = ! empty( $commenter['comment_author'] );
		$has_parent         = (bool) $comment->comment_parent;
		$rating             = Comment::get_rating( get_comment_ID() );

		if ( $commenter['comment_author_email'] ) {
			$moderation_note = __( 'Your comment is awaiting moderation.' );
		} else {
			$moderation_note = __( 'Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.' );
		}

		$comment_class = 'directorist-review-single';

		if ( ! empty( $args['has_children'] ) ) {
			$comment_class .= ' directorist-review-single__has-comments';
		}

		if ( $has_parent ) {
			$comment_class .= ' directorist-review-single--comment';
		}

		$helpful   = get_comment_meta( get_comment_ID(), 'helpful', true );
		$helpful   = empty( $helpful ) ? 0 : absint( $helpful );
		$unhelpful = get_comment_meta( get_comment_ID(), 'unhelpful', true );
		$unhelpful = empty( $unhelpful ) ? 0 : absint( $unhelpful );

		$attachments = (array) get_comment_meta( get_comment_ID(), 'attachments', true )
		?>
		<li id="comment-<?php comment_ID(); ?>" <?php comment_class( $comment_class ); ?>>
			<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
				<div class="directorist-review-single__contents-wrap">
					<header class="directorist-review-single__header">
						<div class="directorist-review-single__author">
							<div class="directorist-review-single__author__img comment-author vcard">
								<?php if ( $args['avatar_size'] != 0 ) {
									echo get_avatar( $comment, $args['avatar_size'] );
								} ?>
							</div>
							<div class="directorist-review-single__author__details">
								<h2 class="fn"><?php comment_author_link(); ?> <time datetime="<?php echo esc_attr( get_comment_date( 'Y-m-d H:i:s' ) ); ?>"><?php comment_date( apply_filters( 'directorist_review_date_format', 'F Y' ) ); ?></time></h2>

								<?php if ( ! $has_parent ) : ?>
									<span class="directorist-rating-stars">
										<?php show_rating_stars( $rating ); ?>
									</span>
								<?php endif; ?>
							</div>
						</div>
						<?php if ( ! $has_parent ) : ?>
							<div class="directorist-review-single__report">
								<a <?php self::add_interaction( 'report' ); ?> href="#"><i class="la la-flag"></i> Report</a>
							</div>
						<?php endif; ?>
					</header>
					<div class="directorist-review-single__content">
						<?php comment_text(); ?>

						<?php if ( ! empty( $attachments ) ) : ?>
							<div class="directorist-review-single__content__img">
								<?php
								$dir = wp_get_upload_dir();
								foreach ( $attachments as $attachment ) {
									printf( '<img src="%s" alt="comment attachment image" />', $dir['baseurl'] . '/' . $attachment );
								}
								?>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<?php
				if ( $comment->comment_approved == '0' ) { ?>
					<em class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.' ); ?></em><br/><?php
				} ?>
				<footer class="directorist-review-single__feedback">
					<a <?php self::add_interaction( 'helpful' ); ?> role="button" data-count="<?php echo $helpful; ?>" href="#" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-up"></i> <?php echo esc_html_x( 'Helpful', 'comment feedback button', 'directorist' ); ?> (<span><?php echo $helpful; ?></span>)</a>
					<a <?php self::add_interaction( 'unhelpful' ); ?> role="button" data-count="<?php echo $unhelpful; ?>" href="#" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-down"></i> <?php echo esc_html_x( 'Not Helpful', 'comment feedback button', 'directorist' ); ?> (<span><?php echo $unhelpful; ?></span>)</a>
				</footer>

				<?php comment_reply_link(
					array_merge(
						$args,
						array(
							/* translators: 1: is the reply icon */
							'reply_text' => sprintf( esc_html( '%1$s Reply', 'directorist' ), '<img class="far fa-comment-alt"></img>' ),
							'depth'      => $depth,
							'max_depth'  => $args['max_depth'],
							'add_below'  => 'div-comment',
							'before'     => '<div class="directorist-review-single__reply">',
							'after'      => '</div>'
						)
					)
				); ?>
			</article><!-- .comment-body -->
		<?php
	}

	protected static function add_interaction( $interaction = '' ) {
		printf( 'data-comment-interaction="%s:%s"', get_comment_ID(), $interaction );
	}
}
