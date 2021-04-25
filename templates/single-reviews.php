<?php
/**
 * Single view advanced review template.
 *
 * @author  wpWax
 * @since   x
 * @version x
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use Directorist\Helper;
use wpWax\Directorist\Review\Review_Data;
use wpWax\Directorist\Review\Walker as Review_Walker;
use Directorist\Directorist_Single_Listing as Directorist_Listing;

use function wpWax\Directorist\Review\show_rating_stars;
use function wpWax\Directorist\Review\get_rating_markup;
use function wpWax\Directorist\Review\get_criteria_names;
use function wpWax\Directorist\Review\is_criteria_enabled;
use function wpWax\Directorist\Review\get_media_uploader_markup;

$listing         = Directorist_Listing::instance();
$review_rating   = Review_Data::get_rating( get_the_ID() );
$review_count    = Review_Data::get_review_count( get_the_ID() );
$criteria_rating = Review_Data::get_criteria_rating( get_the_ID() );
?>

<div class="directorist-review-container">
	<div class="directorist-review-content">
		<div class="directorist-review-content__header">
			<h3><?php printf( '%s <span>%s</span>', strip_tags( get_the_title() ), get_comments_number() ); ?></h3>
			<a href="#respond" class="directorist-btn directorist-btn-primary"><span class="fa fa-star"></span> <?php esc_html_e( 'Write a review', 'directorist' ); ?></a>
		</div><!-- ends: .directorist-review-content__header -->

		<?php if ( have_comments() ) : ?>
			<div class="directorist-review-content__overview">
				<div class="directorist-review-content__overview__rating">
					<span class="directorist-rating-point"><?php echo $review_rating; ?></span>
					<span class="directorist-rating-stars">
						<?php show_rating_stars( $review_rating ); ?>
					</span>
					<span class="directorist-rating-overall"><?php printf( _n( '%s review', '%s reviews', $review_count, 'directorist' ), number_format_i18n( $review_count ) ); ?></span>
				</div>
				<div class="directorist-review-content__overview__benchmarks">
					<?php
					if ( is_criteria_enabled() ) :
						foreach ( get_criteria_names() as $criteria_key => $criteria_name ) :
							$_rating = isset( $criteria_rating[ $criteria_key ] ) ? $criteria_rating[ $criteria_key ] : 0;
							?>
							<div class="directorist-benchmark-single">
								<label><?php echo $criteria_name; ?></label>
								<progress value="<?php echo esc_attr( $_rating ); ?>" max="5"><?php echo $_rating; ?></progress>
								<strong><?php echo $_rating; ?></strong>
							</div>
							<?php
						endforeach;
					endif;
					?>
				</div>
			</div><!-- ends: .directorist-review-content__overview -->

			<ul class="commentlist directorist-review-content__reviews">
				<?php wp_list_comments( array(
					'avatar_size' => 50,
					'format'      => 'html5',
					'walker'      => new Review_Walker(),
				) ); ?>
			</ul>

			<?php
			if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) :
				echo '<nav class="directorist-pagination">';
				paginate_comments_links( array(
					'prev_text' => '&larr;',
					'next_text' => '&rarr;',
					'type'      => 'list',
				) );
				echo '</nav>';
			endif;
			?>
		<?php else : ?>
			<div class="directorist-review-content__reviews">
				<p class="directorist-review-single directorist-noreviews">
					<?php printf( esc_html__( 'There are no reviews yet. %1$sBe the first reviewer%2$s.', 'directorist' ), '<a href="#respond">', '</a>' ); ?>
				</p>
			</div>
		<?php endif; ?>
	</div><!-- ends: .directorist-review-content -->

	<?php
	$commenter = wp_get_current_commenter();
	$req       = get_option( 'require_name_email' );
	$html_req  = ( $req ? " required='required'" : '' );

	$fields = array(
		'author' => sprintf(
			'<div class="directorist-form-group form-group-author">%s %s</div>',
			sprintf(
				'<label for="author">%s%s</label>',
				esc_html__( 'Name', 'directorist' ),
				( $req ? ' <span class="required">*</span>' : '' )
			),
			sprintf(
				'<input id="author" class="directorist-form-element" placeholder="%s" name="author" type="text" value="%s" size="30" maxlength="245"%s />',
				esc_attr__( 'Enter your name', 'directorist' ),
				esc_attr( $commenter['comment_author'] ),
				$html_req
			)
		),
		'email'  => sprintf(
			'<div class="directorist-form-group form-group-email">%s %s</div>',
			sprintf(
				'<label for="email">%s%s</label>',
				esc_html__( 'Email', 'directorist' ),
				( $req ? ' <span class="required">*</span>' : '' )
			),
			sprintf(
				'<input id="email" class="directorist-form-element" placeholder="%s" name="email" type="email" value="%s" size="30" maxlength="100" aria-describedby="email-notes"%s />',
				esc_attr__( 'Enter your email', 'directorist' ),
				esc_attr( $commenter['comment_author_email'] ),
				$html_req
			)
		),
		'url'    => sprintf(
			'<div class="directorist-form-group form-group-url">%s %s</div>',
			sprintf(
				'<label for="url">%s</label>',
				esc_html__( 'Website', 'directorist' ),
			),
			sprintf(
				'<input id="url" class="directorist-form-element" placeholder="%s" name="url" type="url" value="%s" size="30" maxlength="200" />',
				esc_attr__( 'Enter your website', 'directorist' ),
				esc_attr( $commenter['comment_author_url'] )
			)
		),
	);

	$comment_field = sprintf(
		'<div class="directorist-form-group form-group-comment">%s %s</div>',
		sprintf(
			'<label for="comment">%s</label>',
			_x( 'Comment', 'noun', 'directorist' )
		),
		sprintf( '<textarea id="comment" class="directorist-form-element" placeholder="%s" name="comment" cols="30" rows="10" maxlength="65525" required="required"></textarea>',
			esc_attr__( 'Share your experience and help others make better choices', 'directorist' )
		)
	);

	$criteria_markup = '<div class="directorist-review-criteria">%s</div>' . "\n";

	if ( is_criteria_enabled() ) {
		$criteria_items_markup = '';
		foreach ( get_criteria_names() as $criteria_key => $criteria_name ) {
			$criteria_items_markup .= get_rating_markup( $criteria_name, $criteria_key ) . "\n";
		}

		$criteria_markup = sprintf( $criteria_markup, $criteria_items_markup );

		unset( $criteria_items_markup );
	} else {
		$criteria_markup = sprintf( $criteria_markup, get_rating_markup( __( 'Rating', 'directorist' ) ) );
	}

	$comment_field = $criteria_markup . "\n" . $comment_field . "\n" . get_media_uploader_markup();

	$args = array(
		'fields'             => $fields,
		'comment_field'      => $comment_field,
		'class_container'    => 'directorist-review-submit',
		'title_reply'        => __( 'Leave a Review', 'directorist' ),
		'title_reply_before' => '<div class="directorist-review-submit__header"><h3 id="reply-title">',
		'title_reply_after'  => '</h3></div>',
		'class_form'         => 'comment-form directorist-review-submit__form',
		'class_submit'       => 'directorist-btn directorist-btn-primary',
		'label_submit'       => __( 'Submit your review', 'directorist' ),
		'format'             => 'html5',
		'submit_field'       => '<div class="directorist-form-group">%1$s %2$s</div>',
		'submit_button'      => '<button name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s">%4$s</button>',
	);

	comment_form( $args );
	?>
</div>
