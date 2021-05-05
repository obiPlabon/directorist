<?php
/**
 * Comment form handler class.
 *
 * @package wpWax\Directorist
 * @subpackage Review
 * @since 7.x
 */
namespace wpWax\Directorist\Review;

defined( 'ABSPATH' ) || die();

class Form_Handler {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'init_handler' ) );
		add_action( 'comment_post_redirect', array( __CLASS__, 'comment_post_redirect' ) );

		if ( ! is_admin() && ! wpac_is_login_page() ) {
			if ( $_REQUEST['WPACEnable'] === self::get_secret() ) {
				add_filter( 'comments_array', 'wpac_comments_query_filter' );
				add_action( 'wp_head', 'wpac_initialize' );
				add_action( 'wp_enqueue_scripts', 'wpac_enqueue_scripts' );
				add_filter( 'gettext', 'wpac_filter_gettext', 20, 3 );
				add_filter( 'wp_die_handler', 'wpac_wp_die_handler' );
				add_filter( 'option_page_comments', 'wpac_option_page_comments' );
				add_filter( 'option_comments_per_page', 'wpac_option_comments_per_page' );
			}
		}
	}

	public static function init_handler() {
		if ( isset( $_GET['WPACUnapproved'] ) ) {
			header( 'X-WPAC-UNAPPROVED: ' . $_GET['WPACUnapproved'] );
		}

		if ( isset( $_GET['WPACUrl'] ) ) {
			header( 'X-WPAC-URL: ' . $_GET['WPACUrl'] );
		}
	}

	public static function unparse_url( $url_parts ) {
		$scheme   = isset( $url_parts['scheme'] ) ? $url_parts['scheme'] . '://' : '';
		$host     = isset( $url_parts['host'] ) ? $url_parts['host'] : '';
		$port     = isset( $url_parts['port'] ) ? ':' . $url_parts['port'] : '';
		$user     = isset( $url_parts['user'] ) ? $url_parts['user'] : '';
		$pass     = isset( $url_parts['pass'] ) ? ':' . $url_parts['pass']  : '';
		$pass     = ( $user || $pass ) ? "$pass@" : '';
		$path     = isset( $url_parts['path'] ) ? $url_parts['path'] : '';
		$query    = isset( $url_parts['query'] ) ? '?' . $url_parts['query'] : '';
		$fragment = isset( $url_parts['fragment'] ) ? '#' . $url_parts['fragment'] : '';

		return "$scheme$user$pass$host$port$path$query$fragment";
	}

	public static function comment_post_redirect( $location ) {
		global $comment;
		// If base url is defined, replace Wordpress site url by base url
		$url      = $location;
		$base_url = wpac_get_option( 'baseUrl' );

		if ( $base_url ) {
			$site_url = rtrim( get_site_url(), '/' );
			if ( strpos( strtolower( $url ), strtolower( $site_url ) ) === 0 ) {
				$url = preg_replace( '/' . preg_quote( $site_url, '/' ) . '/', rtrim( $base_url, '/' ), $url, 1 );
			}
		}

		// Add "disable cache" query parameter
		if ( wpac_get_option( 'disableCache' ) ) {
			$url_parts = parse_url( $url );
			$queryParam = 'WPACRandom=' . time();
			$url_parts['query'] = isset( $url_parts['query'] ) ? $url_parts['query'] . '&' . $queryParam : $queryParam;
			$url = self::unparse_url( $url_parts );
		}

		// Add query parameter (WPACUnapproved and WPACUrl)
		$urlParts = parse_url($url);
		$queryParam = 'WPACUnapproved='.(($comment && $comment->comment_approved == '0') ? '1' : '0').'&WPACUrl='.urlencode($url);
		$urlParts['query'] = isset($urlParts['query']) ? $urlParts['query'].'&'.$queryParam : $queryParam;
		$url = self::unparse_url($urlParts);

		return $url;
	}

	public static function get_secret() {
		return substr( md5(
			NONCE_SALT.AUTH_KEY.LOGGED_IN_KEY.NONCE_KEY.AUTH_SALT.SECURE_AUTH_SALT.LOGGED_IN_SALT.NONCE_SALT
		), 0 , 10 );
	}

	public static function is_ajax_request() {
		return isset( $_SERVER['HTTP_X_WPAC_REQUEST'] ) && $_SERVER['HTTP_X_WPAC_REQUEST'];
	}

	public static function comments_query_filter( $query ) {
		// No comment filtering if request is a fallback or WPAC-AJAX request
		if ( isset( $_REQUEST['WPACFallback'] ) && $_REQUEST['WPACFallback'] ) {
			return $query;
		}

		if ( self::is_ajax_request() ) {
			$skip = ( ( isset( $_REQUEST['WPACSkip'] ) && is_numeric( $_REQUEST['WPACSkip'] ) && $_REQUEST['WPACSkip'] > 0 ) ) ? $_REQUEST['WPACSkip'] : 0;
			$take = ( ( isset( $_REQUEST['WPACTake'] ) && is_numeric( $_REQUEST['WPACTake'] ) && $_REQUEST['WPACTake'] > 0 ) ) ? $_REQUEST['WPACTake'] : count( $query );

			if ( get_option( 'comment_order' ) === 'desc' ) {
				return array_slice( $query, -$skip-$take, $take ); // Comment order: Newest at the top
			} else {
				return array_slice( $query, $skip, $take ); // Comment order:Oldest on the top
			}
		} else {
			// Test asyncCommentsThreshold
			$asyncCommentsThreshold = wpac_get_option( 'asyncCommentsThreshold' );
			$commentsCount = count( $query );

			if ( strlen( $asyncCommentsThreshold ) == 0 || $commentsCount == 0 || $asyncCommentsThreshold > $commentsCount ) {
				return $query;
			}

			// Filter/remove comments and set options to load comments with secondary AJAX request
			echo '<script type="text/javascript">WPAC._Options["loadCommentsAsync"] = true;</script>';

			return array();
		}
	}

	public static function filter_gettext( $translation, $text, $domain ) {
		if ( $domain != 'default' ) {
			return $translation;
		}

		$customWordpressTexts = array(
			strtolower(WPAC_WP_ERROR_PLEASE_TYPE_COMMENT) => 'textErrorTypeComment',
			strtolower(WPAC_WP_ERROR_COMMENTS_CLOSED) => 'textErrorCommentsClosed',
			strtolower(WPAC_WP_ERROR_MUST_BE_LOGGED_IN) => 'textErrorMustBeLoggedIn',
			strtolower(WPAC_WP_ERROR_FILL_REQUIRED_FIELDS) => 'textErrorFillRequiredFields',
			strtolower(WPAC_WP_ERROR_INVALID_EMAIL_ADDRESS) => 'textErrorInvalidEmailAddress',
			strtolower(WPAC_WP_ERROR_POST_TOO_QUICKLY) => 'textErrorPostTooQuickly',
			strtolower(WPAC_WP_ERROR_DUPLICATE_COMMENT) => 'textErrorDuplicateComment',
		);

		$lowerText = strtolower($text);

		if ( array_key_exists( $lowerText, $customWordpressTexts ) ) {
			$customText = wpac_get_option($customWordpressTexts[$lowerText]);
			if ($customText) return $customText;
		}
		return $translation;
	}

	function load_comments_async() {
		$asyncCommentsThreshold = wpac_get_option('asyncCommentsThreshold');
		if (strlen($asyncCommentsThreshold) == 0) return false;

		global $post;
		$commentsCount = $post ? (int)get_comments_number($post->ID) : 0;
		return (
			$commentsCount > 0 &&
			$asyncCommentsThreshold <= $commentsCount
		);
	}
}
