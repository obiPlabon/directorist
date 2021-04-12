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
			<h3>Foodies Ratings <span>452</span></h3>
			<a href="#directorist-add-review" class="directorist-btn directorist-btn-primary"><span class="fa fa-star"></span> Write a Review</a>
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
					'avatar_size'  => 50,
					'walker'       => new Review_Walker(),
					'format' => 'html5',
					// 'callback'     => '\wpWax\Directorist\Review\comments_callback',
					// 'end-callback' => '\wpWax\Directorist\Review\comments_end_callback',
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
	$args = array(
		'class_container'    => 'directorist-review-submit',
		'title_reply'        => __( 'Leave a Review', 'directorist' ),
		'title_reply_before' => '<div class="directorist-review-submit__header"><h3 id="reply-title">',
		'title_reply_after'  => '</h3></div>',
		'class_form'         => 'comment-form directorist-review-submit__form',
		'class_submit'       => 'directorist-btn directorist-btn-primary',
		'label_submit'       => __( 'Submit your review', 'directorist' ),
		'format'             => 'html5',
		'submit_field'       => '<div class="directorist-form-group">%1$s %2$s</div>',
		'submit_button'      => '<button name="%1$s" type="submit" id="%2$s" class="%3$s">%4$s</button>',
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
					<div class="directorist-review-criteria__single">
						<span class="directorist-review-criteria__single__label">Food</span>
						<select id="directorist-review-criteria__one">
							<option value="1">1</option>
							<option value="2">2</option>
							<option value="3">3</option>
							<option value="4">4</option>
							<option value="5">5</option>
						</select>
					</div><!-- ends: .directorist-review-criteria__one -->
					<div class="directorist-review-criteria__single">
						<span class="directorist-review-criteria__single__label">Location</span>
						<select id="directorist-review-criteria__two">
							<option value="1">1</option>
							<option value="2">2</option>
							<option value="3">3</option>
							<option value="4">4</option>
							<option value="5">5</option>
						</select>
					</div><!-- ends: .directorist-review-criteria__one -->
					<div class="directorist-review-criteria__single">
						<span class="directorist-review-criteria__single__label">Service</span>
						<select id="directorist-review-criteria__three">
							<option value="1">1</option>
							<option value="2">2</option>
							<option value="3">3</option>
							<option value="4">4</option>
							<option value="5">5</option>
						</select>
					</div><!-- ends: .directorist-review-criteria__one -->
					<div class="directorist-review-criteria__single">
						<span class="directorist-review-criteria__single__label">Ambience</span>
						<select id="directorist-review-criteria__four">
							<option value="1">1</option>
							<option value="2">2</option>
							<option value="3">3</option>
							<option value="4">4</option>
							<option value="5">5</option>
						</select>
					</div><!-- ends: .directorist-review-criteria__one -->
					<div class="directorist-review-criteria__single">
						<span class="directorist-review-criteria__single__label">Price</span>
						<select id="directorist-review-criteria__five">
							<option value="1">1</option>
							<option value="2">2</option>
							<option value="3">3</option>
							<option value="4">4</option>
							<option value="5">5</option>
						</select>
					</div><!-- ends: .directorist-review-criteria__one -->

				</div><!-- ends: .directorist-review-criteria -->
				<div class="directorist-form-group">
					<textarea class="directorist-form-element" cols="30" rows="10" placeholder="Share your experience and help others make better choices"></textarea>
				</div>
				<div class="directorist-form-group directorist-review-media-upload">
					<input type="file" name="" id="directorist-add-review-img" multiple>
					<label for="directorist-add-review-img">
						<i class="far fa-image"></i>
						<span>Add a photo</span>
					</label>
					<div class="directorist-review-img-gallery"></div>
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
