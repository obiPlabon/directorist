<?php
/**
 * @author  wpWax
 * @since   6.7
 * @version 6.7
 */

use \Directorist\Directorist_Single_Listing;
use \Directorist\Helper;

if ( ! defined( 'ABSPATH' ) ) exit;

$listing = Directorist_Single_Listing::instance();
?>

<div class="directorist-single-contents-area directorist-w-100">
	<div class="<?php Helper::directorist_container_fluid(); ?>">
		<?php $listing->notice_template(); ?>

		<div class="<?php Helper::directorist_row(); ?>">

			<div class="<?php Helper::directorist_single_column(); ?>">

				<?php Helper::get_template( 'single/top-actions' ); ?>

				<div class="directorist-single-wrapper">

					<?php
					$listing->header_template();

					foreach ( $listing->content_data as $section ) {
						$listing->section_template( $section );
					}
					?>

					<div class="directorist-review-container">

						<?php /*
						<div class="directorist-review-content">
							<div class="directorist-review-content__header">
								<h3>Foodies Ratings <span>452</span></h3>
								<a href="#directorist-add-review" class="directorist-btn directorist-btn-primary"><span class="fa fa-star"></span> Write a Review</a>
							</div><!-- ends: .directorist-review-content__header -->

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

							<div class="directorist-review-content__reviews">
								<div class="directorist-review-single">
									<div class="directorist-review-single__contents-wrap">
										<div class="directorist-review-single__header">
											<div class="directorist-review-single__author">
												<div class="directorist-review-single__author__img">
													<img src="https://via.placeholder.com/300x300" alt="">
												</div>
												<div class="directorist-review-single__author__details">
													<h2>Rodrick <span>August 2019</span></h2>
													<span class="directorist-rating-stars">
														<i class="fa fa-star"></i>
														<i class="fa fa-star"></i>
														<i class="fa fa-star"></i>
														<i class="fa fa-star"></i>
														<i class="fa fa-star"></i>
													</span>
												</div>
											</div>
											<div class="directorist-review-single__report">
												<a href=""><i class="la la-flag"></i> Report</a>
											</div>
										</div>
										<div class="directorist-review-single__content">
											<p>Kequi officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusan tium doloremque laudantium, totam rem.</p>
											<div class="directorist-review-single__content__img">
												<img src="https://via.placeholder.com/300x300" alt="">
												<img src="https://via.placeholder.com/300x300" alt="">
												<img src="https://via.placeholder.com/300x300" alt="">
											</div>
										</div>
									</div>
									<div class="directorist-review-single__feedback">
										<a href="" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-up"></i> Helpful <span>6</span></a>
										<a href="" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-down"></i> Not Helpful <span>2</span></a>
									</div>
									<div class="directorist-review-single__reply">
										<a href=""><i class="far fa-comment-alt"></i> Reply</a>
									</div>
								</div><!-- ends: .directorist-review-single -->


								<div class="directorist-review-single directorist-review-single__has-comments">
									<div class="directorist-review-single__contents-wrap">
										<div class="directorist-review-single__header">
											<div class="directorist-review-single__author">
												<div class="directorist-review-single__author__img">
													<img src="https://via.placeholder.com/300x300" alt="">
												</div>
												<div class="directorist-review-single__author__details">
													<h2>Rodrick <span>August 2019</span></h2>
													<span class="directorist-rating-stars">
														<i class="fa fa-star"></i>
														<i class="fa fa-star"></i>
														<i class="fa fa-star"></i>
														<i class="fa fa-star"></i>
														<i class="fa fa-star"></i>
													</span>
												</div>
											</div>
											<div class="directorist-review-single__report">
												<a href=""><i class="la la-flag"></i> Report</a>
											</div>
										</div>
										<div class="directorist-review-single__content">
											<p>Kequi officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusan tium doloremque laudantium, totam rem.</p>
										</div>
									</div>
									<div class="directorist-review-single__feedback">
										<a href="" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-up"></i> Helpful <span>6</span></a>
										<a href="" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-down"></i> Not Helpful <span>2</span></a>
									</div>
									<div class="directorist-review-single__reply">
										<a href=""><i class="far fa-comment-alt"></i> Reply</a>
									</div>
									<div class="directorist-review-single__comments">
										<div class="directorist-review-single directorist-review-single--comment directorist-review-single__has-comments">
											<div class="directorist-review-single__contents-wrap">
												<div class="directorist-review-single__header">
													<div class="directorist-review-single__author">
														<div class="directorist-review-single__author__img">
															<img src="https://via.placeholder.com/300x300" alt="">
														</div>
														<div class="directorist-review-single__author__details">
															<h2>Rodrick <span>August 2019</span></h2>
														</div>
													</div>
												</div>
												<div class="directorist-review-single__content">
													<p>Kequi officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusan tium doloremque laudantium, totam rem.</p>
												</div>
											</div>
											<div class="directorist-review-single__feedback">
												<a href="" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-up"></i> Helpful <span>6</span></a>
												<a href="" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-down"></i> Not Helpful <span>2</span></a>
											</div>
											<div class="directorist-review-single__reply">
												<a href=""><i class="far fa-comment-alt"></i> Reply</a>
											</div>

											<div class="directorist-review-single__comments">
												<div class="directorist-review-single directorist-review-single--comment">
													<div class="directorist-review-single__contents-wrap">
														<div class="directorist-review-single__header">
															<div class="directorist-review-single__author">
																<div class="directorist-review-single__author__img">
																	<img src="https://via.placeholder.com/300x300" alt="">
																</div>
																<div class="directorist-review-single__author__details">
																	<h2>Rodrick <span>August 2019</span></h2>
																</div>
															</div>
														</div>
														<div class="directorist-review-single__content">
															<p>Kequi officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusan tium doloremque laudantium, totam rem.</p>
														</div>
													</div>
													<div class="directorist-review-single__feedback">
														<a href="" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-up"></i> Helpful <span>6</span></a>
														<a href="" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-down"></i> Not Helpful <span>2</span></a>
													</div>
													<div class="directorist-review-single__reply">
														<a href=""><i class="far fa-comment-alt"></i> Reply</a>
													</div>
												</div><!-- ends: .directorist-review-single -->
											</div>
										</div><!-- ends: .directorist-review-single -->

										<div class="directorist-review-single directorist-review-single--comment directorist-review-single__has-comments">
											<div class="directorist-review-single__contents-wrap">
												<div class="directorist-review-single__header">
													<div class="directorist-review-single__author">
														<div class="directorist-review-single__author__img">
															<img src="https://via.placeholder.com/300x300" alt="">
														</div>
														<div class="directorist-review-single__author__details">
															<h2>Rodrick <span>August 2019</span></h2>
														</div>
													</div>
												</div>
												<div class="directorist-review-single__content">
													<p>Kequi officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusan tium doloremque laudantium, totam rem.</p>
												</div>
											</div>
											<div class="directorist-review-single__feedback">
												<a href="" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-up"></i> Helpful <span>6</span></a>
												<a href="" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-down"></i> Not Helpful <span>2</span></a>
											</div>
											<div class="directorist-review-single__reply">
												<a href=""><i class="far fa-comment-alt"></i> Reply</a>
											</div>

											<div class="directorist-review-single__comments">
												<div class="directorist-review-single directorist-review-single--comment">
													<div class="directorist-review-single__contents-wrap">
														<div class="directorist-review-single__header">
															<div class="directorist-review-single__author">
																<div class="directorist-review-single__author__img">
																	<img src="https://via.placeholder.com/300x300" alt="">
																</div>
																<div class="directorist-review-single__author__details">
																	<h2>Rodrick <span>August 2019</span></h2>
																</div>
															</div>
														</div>
														<div class="directorist-review-single__content">
															<p>Kequi officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusan tium doloremque laudantium, totam rem.</p>
														</div>
													</div>
													<div class="directorist-review-single__feedback">
														<a href="" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-up"></i> Helpful <span>6</span></a>
														<a href="" class="directorist-btn directorist-btn-outline-dark"><i class="far fa-thumbs-down"></i> Not Helpful <span>2</span></a>
													</div>
													<div class="directorist-review-single__reply">
														<a href=""><i class="far fa-comment-alt"></i> Reply</a>
													</div>
												</div><!-- ends: .directorist-review-single -->
											</div>
										</div><!-- ends: .directorist-review-single -->
									</div>
								</div><!-- ends: .directorist-review-single -->
							</div>ends: .directorist-review-content__reviews

							<div class="directorist-review-content__pagination">
								<ul>
									<li><a href=""><i class="la la-arrow-left"></i></a></li>
									<li class="active"><a href="">1</a></li>
									<li><a href="">2</a></li>
									<li><a href="">3</a></li>
									<li><a href="">4</a></li>
									<li><a href=""><i class="la la-arrow-right"></i></a></li>
								</ul>
							</div>
						</div><!-- ends: .directorist-review-content -->
						 */ ?>

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
					</div><!-- ends: .directorist-review-container -->
				</div>

			</div>

			<?php Helper::get_template( 'single-sidebar' ); ?>

		</div>
	</div>
</div>
