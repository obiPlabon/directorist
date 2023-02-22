<?php
/**
 * Sanitization functions definition should be here.
 *
 * @package Directorist
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Directorist get allowed attributes
 *
 * @return array
 */
function directorist_get_allowed_attributes() {
    $allowed_attributes = array(
        'style'       => array(),
        'class'       => array(),
        'id'          => array(),
        'name'        => array(),
        'rel'         => array(),
        'type'        => array(),
        'href'        => array(),
        'value'       => array(),
        'action'      => array(),
        'selected'    => array(),
		'checked'     => array(),
        'for'         => array(),
        'placeholder' => array(),
        'cols'        => array(),
        'rows'        => array(),
        'maxlength'   => array(),
        'required'    => array(),

        'xmlns'   => array(),
        'width'   => array(),
        'height'  => array(),
        'viewBox' => array(),
        'fill'    => array(),
        'd'       => array(),

		'data-custom-field' => array(),
    );

    return apply_filters( 'directorist_get_allowed_attributes', $allowed_attributes );
}

/**
 * Directorist get allowed form input tags
 *
 * @return array
 */
function directorist_get_allowed_form_input_tags() {
    $allowed_attributes = directorist_get_allowed_attributes();

    return apply_filters( 'directorist_get_allowed_form_input_tags', [
        'input'    => $allowed_attributes,
        'select'   => $allowed_attributes,
        'option'   => $allowed_attributes,
        'textarea' => $allowed_attributes,
    ] );
}

/**
 * Directorist get allowed svg tags
 *
 * @return array
 */
function directorist_get_allowed_svg_tags() {
    $allowed_attributes = directorist_get_allowed_attributes();

    return apply_filters( 'directorist_get_allowed_svg_tags', [
        'svg'  => $allowed_attributes,
        'g'    => $allowed_attributes,
        'path' => $allowed_attributes,
    ] );
}

/**
 * Directorist get allowed HTML tags
 *
 * @return array
 */
function directorist_get_allowed_html() {

    $allowed_attributes = directorist_get_allowed_attributes();

    $allowed_html = array(
        'h1'     => $allowed_attributes,
        'h2'     => $allowed_attributes,
        'h3'     => $allowed_attributes,
        'h4'     => $allowed_attributes,
        'h5'     => $allowed_attributes,
        'h6'     => $allowed_attributes,
        'p'      => $allowed_attributes,
        'a'      => $allowed_attributes,
		'ul'     => $allowed_attributes,
		'li'     => $allowed_attributes,
        'span'   => $allowed_attributes,
        'form'   => $allowed_attributes,
        'div'    => $allowed_attributes,
        'label'  => $allowed_attributes,
        'button' => $allowed_attributes,
    );

    $allowed_html = array_merge(
        $allowed_html,
        directorist_get_allowed_form_input_tags(),
        directorist_get_allowed_svg_tags()
    );

    return apply_filters( 'directorist_get_allowed_html', $allowed_html );
}


/**
 * Directorist KSES
 *
 * Filters text content and strips out disallowed HTML.
 *
 * This function makes sure that only the allowed HTML element names, attribute
 * names, attribute values, and HTML entities will occur in the given text string.
 *
 * This function expects unslashed data.
 *
 * @param string $content
 * @param string $allowed_html
 *
 * @return string
 */
function directorist_kses( $content, $allowed_html = 'all' ) {

    $allowed_html_types = [
        'all'        => directorist_get_allowed_html(),
        'form_input' => directorist_get_allowed_form_input_tags(),
        'svg'        => directorist_get_allowed_svg_tags(),
    ];

    $allowed_html_type = ( in_array( $allowed_html, $allowed_html_types ) ) ? $allowed_html_types[ $allowed_html ] : $allowed_html_types[ 'all' ];

    return wp_kses( $content, $allowed_html_type );
}
