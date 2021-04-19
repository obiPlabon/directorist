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
use Directorist\Directorist_Single_Listing as Directorist_Entry;
use wpWax\Directorist\Review\Walker as Review_Walker;

$listing = Directorist_Entry::instance();
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
					<span class="directorist-rating-point">4.6</span>
					<span class="directorist-rating-stars">
						<i class="fa fa-star"></i>
						<i class="fa fa-star"></i>
						<i class="fa fa-star"></i>
						<i class="fa fa-star"></i>
						<i class="fa fa-star"></i>
					</span>
					<span class="directorist-rating-overall">653 reviews</span>
				</div>
				<div class="directorist-review-content__overview__benchmarks">
					<div class="directorist-benchmark-single">
						<label>Food</label>
						<progress value="5" max="5"> 5.0 </progress>
						<strong>5.0</strong>
					</div>
					<div class="directorist-benchmark-single">
						<label>Location</label>
						<progress value="4.5" max="5"> 4.5 </progress>
						<strong>4.5</strong>
					</div>
					<div class="directorist-benchmark-single">
						<label>Service</label>
						<progress value="4.7" max="5"> 4.7 </progress>
						<strong>4.7</strong>
					</div>
					<div class="directorist-benchmark-single">
						<label>Quality</label>
						<progress value="5" max="5"> 5.0 </progress>
						<strong>5.0</strong>
					</div>
					<div class="directorist-benchmark-single">
						<label>Price</label>
						<progress value="4.2" max="5"> 4.2 </progress>
						<strong>4.2</strong>
					</div>
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
			<p class="directorist-noreviews"><?php esc_html_e( 'There are no reviews yet.', 'directorist' ); ?></p>
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

	if ( \wpWax\Directorist\Review\is_criteria_enabled() ) {
		$criteria_items_markup = '';
		foreach ( \wpWax\Directorist\Review\get_criteria_names() as $criteria_key => $criteria_name ) {
			$criteria_items_markup .= \wpWax\Directorist\Review\get_rating_markup( 'rating['.$criteria_key.']', $criteria_name ) . "\n";
		}

		$criteria_markup = sprintf(
			$criteria_markup,
			$criteria_items_markup
		);

		unset( $criteria_items_markup );
	} else {
		$criteria_markup = sprintf(
			$criteria_markup,
			\wpWax\Directorist\Review\get_rating_markup( 'rating', 'Rating' )
		);
	}

	$comment_field = $criteria_markup . "\n" . $comment_field . "\n" . \wpWax\Directorist\Review\get_media_uploader_markup();

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

	<div class="directorist-review-submit" id="directorist-add-review">
		<div class="directorist-review-submit__header">
			<h3>Leave a Review</h3>
		</div><!-- ends: .directorist-review-submit__header -->
		<div class="directorist-review-submit__form">
			<form action="/">
				<div class="directorist-review-criteria">
					<?php
					foreach ( ['one', 'two', 'three'] as $num ) {
						echo wpWax\Directorist\Review\get_rating_markup( "criteria[{$num}]", ucfirst( $num ) );
					}
					?>
				</div><!-- ends: .directorist-review-criteria -->
				<div class="directorist-form-group">
					<textarea class="directorist-form-element" cols="30" rows="10" placeholder="Share your experience and help others make better choices"></textarea>
				</div>

				<div class="directorist-form-group">
					<label for="">Your Email</label>
					<input class="directorist-form-element" type="text" placeholder="Enter your email">
				</div>
				<div class="directorist-form-group">
					<button class="directorist-btn directorist-btn-primary" type="submit">Submit your review</button>
				</div>
			</form>
		</div><!-- ends: .directorist-review-submit__form -->
	</div><!-- ends: .directorist-review-submit -->
</div>
