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
		add_filter( 'allowed_redirect_hosts' , array( __CLASS__, 'wpac_allowed_redirect_hosts' ) );

		if ( ! is_admin() && ! wpac_is_login_page() && $_REQUEST['WPACEnable'] === self::get_secret() ) {
			add_filter( 'comments_array', array( __CLASS__, 'comments_query_filter' ) );
			add_action( 'wp_head', array( __CLASS__, 'on_head' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
			add_filter( 'gettext', array( __CLASS__, 'filter_gettext' ), 20, 3 );
			add_filter( 'wp_die_handler', array( __CLASS__, 'wpac_wp_die_handler' ) );
			add_filter( 'option_page_comments', array( __CLASS__, 'wpac_option_page_comments' ) );
			add_filter( 'option_comments_per_page', array( __CLASS__, 'wpac_option_comments_per_page' ) );
		}
	}

	public static function wpac_option_comments_per_page($comments_per_page) {
		return(wpac_is_ajax_request() && isset($_REQUEST['WPACAll']) && $_REQUEST['WPACAll']) ?
			0 : $comments_per_page;
	}

	public static function wpac_option_page_comments( $page_comments ) {
		return ( wpac_is_ajax_request() && isset( $_REQUEST['WPACAll'] ) && $_REQUEST['WPACAll'] ) ? false : $page_comments;
	}

	public static function wpac_wp_die_handler($handler) {
		if ($handler != "_default_wp_die_handler") return $handler;
		return "wpac_default_wp_die_handler";
	}

	public static function wpac_default_wp_die_handler( $message, $title = '', $args = array() ) {
		// Set X-WPAC-ERROR if script "dies" when posting comment
		if ( wpac_is_ajax_request() ) {
			header( 'X-WPAC-ERROR: 1' );
		}
		return _default_wp_die_handler( $message, $title, $args );
	}

	public static function enqueue_scripts() {
		// Skip if scripts should not be injected
		if ( ! self::inject_scripts() ) {
			return;
		}

		$version = wpac_get_version();
		$jsPath = plugins_url('js/', __FILE__);
		$inFooter = wpac_get_option("placeScriptsInFooter");
		// wpac_get_option('debug') || wpac_get_option('useUncompressedScripts')
		if (true) {
			wp_enqueue_script('jsuri', $jsPath.'jsuri.js', array(), $version, $inFooter);
			wp_enqueue_script('jQueryBlockUi', $jsPath.'jquery.blockUI.js', array('jquery'), $version, $inFooter);
			wp_enqueue_script('jQueryIdleTimer', $jsPath.'idle-timer.js', array('jquery'), $version, $inFooter);
			wp_enqueue_script('waypoints', $jsPath.'jquery.waypoints.js', array('jquery'), $version, $inFooter);
			wp_enqueue_script('wpAjaxifyComments', $jsPath.'wp-ajaxify-comments.js', array('jquery', 'jQueryBlockUi', 'jsuri', 'jQueryIdleTimer', 'waypoints'), $version, $inFooter);
		} else {
			wp_enqueue_script('wpAjaxifyComments', $jsPath.'wp-ajaxify-comments.min.js', array('jquery'), $version, $inFooter);
		}
	}

	public static function inject_scripts() {
		if ( self::is_ajax_request() ) {
			return false;
		}

		if ( wpac_get_option( 'alwaysIncludeScripts' ) ) {
			return true;
		}

		if ( wpac_get_option( 'debug' ) ) {
			return true;
		}

		if ( wpac_comments_enabled() ) {
			return true;
		}

		if ( is_page() || is_single() ) {
			global $post;
			if ( get_comments_number( $post->ID ) > 0 || self::load_comments_async() ) {
				return true;
			}
		}

		return false;
	}

	public static function on_head() {
		// Skip if scripts should not be injected
		if (!wpac_inject_scripts()) return;

		echo '<script type="text/javascript">/* <![CDATA[ */';

		echo 'if (!window["WPAC"]) var WPAC = {};';

		// Options
		echo 'WPAC._Options = {';
		$wpac_config = wpac_get_config();
		foreach($wpac_config as $config) {
			foreach($config['options'] as $optionName => $option) {
				if (isset($option['specialOption']) && $option['specialOption']) continue;
				$value = trim(wpac_get_option($optionName));
				if (strlen($value) == 0) $value = $option['default'];
				echo $optionName.':';
				switch ($option['type']) {
					case 'int': echo $value.','; break;
					case 'boolean': echo $value ? 'true,' : 'false,'; break;
					default: echo '"'.wpac_js_escape($value).'",';
				}
			}
		}
		echo 'commentsEnabled:'.(wpac_comments_enabled() ? 'true' : 'false').',';
		echo 'version:"'.wpac_get_version().'"};';

		// Callbacks
		echo 'WPAC._Callbacks = {';
		echo '"beforeSelectElements": function(dom) {'.wpac_get_option('callbackOnBeforeSelectElements').'},';
		echo '"beforeUpdateComments": function(newDom, commentUrl) {'.wpac_get_option('callbackOnBeforeUpdateComments').'},';
		echo '"afterUpdateComments": function(newDom, commentUrl) {'.wpac_get_option('callbackOnAfterUpdateComments').'},';
		echo '"beforeSubmitComment": function() {'.wpac_get_option('callbackOnBeforeSubmitComment').'},';
		echo '"afterPostComment": function(commentUrl, unapproved) {'.wpac_get_option('callbackOnAfterPostComment').'}';
		echo '};';

		echo '/* ]]> */</script>';
	}

	function wpac_comments_enabled() {
		$commentPagesUrlRegex = wpac_get_option('commentPagesUrlRegex');
		if ($commentPagesUrlRegex) {
			return preg_match($commentPagesUrlRegex, wpac_get_page_url()) > 0;
		} else {
			global $post;
			return (is_page() || is_single()) && comments_open($post->ID) && (!get_option('comment_registration') || is_user_logged_in());
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

	public static function wpac_allowed_redirect_hosts($content){
		$baseUrl = wpac_get_option('baseUrl');
		if ($baseUrl) {
			$baseUrlHost = parse_url($baseUrl, PHP_URL_HOST);
			if ($baseUrlHost !== false) $content[] = $baseUrlHost;
		}
		return $content;
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

	public static function load_comments_async() {
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
