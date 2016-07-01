<?php
/**
 * Formatting functions for taking care of proper number formats and such
 *
 * @package     AffiliateWP
 * @subpackage  Functions/Formatting
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;



/**
 * Get Currencies
 *
 * @since 1.0
 * @return array $currencies A list of the available currencies
 */
function affwp_get_currencies() {

	$currencies = array(
		'USD' => __( 'US Dollars', 'affiliate-wp' ),
		'EUR' => __( 'Euros', 'affiliate-wp' ),
		'ARS' => __( 'Argentine Peso', 'affiliate-wp' ),
		'AUD' => __( 'Australian Dollars', 'affiliate-wp' ),
		'BDT' => __( 'Bangladeshi Taka', 'affiliate-wp' ),
		'BRL' => __( 'Brazilian Real', 'affiliate-wp' ),
		'BGN' => __( 'Bulgarian Lev', 'affiliate-wp' ),
		'CAD' => __( 'Canadian Dollars', 'affiliate-wp' ),
		'CLP' => __( 'Chilean Peso', 'affiliate-wp' ),
		'CNY' => __( 'Chinese Yuan', 'affiliate-wp' ),
		'COP' => __( 'Colombian Peso', 'affiliate-wp' ),
		'HRK' => __( 'Croatia Kuna', 'affiliate-wp' ),
		'CZK' => __( 'Czech Koruna', 'affiliate-wp' ),
		'DKK' => __( 'Danish Krone', 'affiliate-wp' ),
		'DOP' => __( 'Dominican Peso', 'affiliate-wp' ),
		'EGP' => __( 'Egyptian Pound', 'affiliate-wp' ),
		'HKD' => __( 'Hong Kong Dollar', 'affiliate-wp' ),
		'HUF' => __( 'Hungarian Forint', 'affiliate-wp' ),
		'ISK' => __( 'Icelandic Krona', 'affiliate-wp' ),
		'IDR' => __( 'Indonesia Rupiah', 'affiliate-wp' ),
		'INR' => __( 'Indian Rupee', 'affiliate-wp' ),
		'ILS' => __( 'Israeli Shekel', 'affiliate-wp' ),
		'JPY' => __( 'Japanese Yen', 'affiliate-wp' ),
		'KIP' => __( 'Lao Kip', 'affiliate-wp' ),
		'MYR' => __( 'Malaysian Ringgits', 'affiliate-wp' ),
		'MXN' => __( 'Mexican Peso', 'affiliate-wp' ),
		'NPR' => __( 'Nepali Rupee', 'affiliate-wp' ),
		'NGN' => __( 'Nigerian Naira', 'affiliate-wp' ),
		'NOK' => __( 'Norwegian Krone', 'affiliate-wp' ),
		'NZD' => __( 'New Zealand Dollar', 'affiliate-wp' ),
		'PYG' => __( 'Paraguayan Guaraní', 'affiliate-wp' ),
		'PHP' => __( 'Philippine Pesos', 'affiliate-wp' ),
		'PLN' => __( 'Polish Zloty', 'affiliate-wp' ),
		'GBP' => __( 'Pounds Sterling', 'affiliate-wp' ),
		'RON' => __( 'Romanian Leu', 'affiliate-wp' ),
		'RUB' => __( 'Russian Ruble', 'affiliate-wp' ),
		'SAR' => __( 'Saudi Arabian Riyal', 'affiliate-wp' ),
		'SGD' => __( 'Singapore Dollar', 'affiliate-wp' ),
		'ZAR' => __( 'South African Rand', 'affiliate-wp' ),
		'KRW' => __( 'South Korean Won', 'affiliate-wp' ),
		'SEK' => __( 'Swedish Krona', 'affiliate-wp' ),
		'CHF' => __( 'Swiss Franc', 'affiliate-wp' ),
		'TWD' => __( 'Taiwan New Dollars', 'affiliate-wp' ),
		'THB' => __( 'Thai Baht', 'affiliate-wp' ),
		'TRY' => __( 'Turkish Lira', 'affiliate-wp' ),
		'AED' => __( 'United Arab Emirates Dirham', 'affiliate-wp' ),
		'UAH' => __( 'Ukrainian Hryvnia', 'affiliate-wp' ),
		'VND' => __( 'Vietnamese Dong', 'affiliate-wp' ),
	);

	return apply_filters( 'affwp_currencies', $currencies );
}


/**
 * Get the store's set currency
 *
 * @since 1.0
 * @return string The currency code
 */
function affwp_get_currency() {
	$currency = affiliate_wp()->settings->get( 'currency', 'USD' );
	return apply_filters( 'affwp_currency', $currency );
}

/**
 * Sanitize Amount
 *
 * Returns a sanitized amount by stripping out thousands separators.
 *
 * @since 1.0
 * @param string $amount amount amount to format
 * @return string $amount Newly sanitized amount
 */
function affwp_sanitize_amount( $amount ) {
	global $affwp_options;

	$thousands_sep = affiliate_wp()->settings->get( 'thousands_separator', ',' );
	$decimal_sep   = affiliate_wp()->settings->get( 'decimal_separator', '.' );

	// Remove non-numeric numbers
	$amount = preg_replace("/([^0-9\\.])/i", "", $amount );

	// Sanitize the amount
	if ( $decimal_sep == ',' && false !== ( $found = strpos( $amount, $decimal_sep ) ) ) {
		if ( $thousands_sep == '.' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
			$amount = str_replace( $thousands_sep, '', $amount );
		} elseif( empty( $thousands_sep ) && false !== ( $found = strpos( $amount, '.' ) ) ) {
			$amount = str_replace( '.', '', $amount );
		}

		$amount = str_replace( $decimal_sep, '.', $amount );
	} elseif( $thousands_sep == ',' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
		$amount = str_replace( $thousands_sep, '', $amount );
	}

	$decimals = apply_filters( 'affwp_sanitize_amount_decimals', affwp_get_decimal_count(), $amount );
	$amount   = number_format( floatval( $amount ), absint( $decimals ), '.', '' );

	return apply_filters( 'affwp_sanitize_amount', $amount );
}

/**
 * Returns a nicely formatted amount.
 *
 * @since 1.0
 *
 * @param string $amount   Price amount to format
 * @param string $decimals Whether or not to use decimals.  Useful when set to false for non-currency numbers.
 *
 * @return string $amount Newly formatted amount or Price Not Available
 */
function affwp_format_amount( $amount, $decimals = true ) {
	global $affwp_options;

	$thousands_sep = affiliate_wp()->settings->get( 'thousands_separator', ',' );
	$decimal_sep   = affiliate_wp()->settings->get( 'decimal_separator', '.' );

	// Format the amount
	if ( $decimal_sep == ',' && false !== ( $sep_found = strpos( $amount, $decimal_sep ) ) ) {
		$whole = substr( $amount, 0, $sep_found );
		$part = substr( $amount, $sep_found + 1, ( strlen( $amount ) - 1 ) );
		$amount = $whole . '.' . $part;
	}

	// Strip , from the amount (if set as the thousands separator)
	if ( $thousands_sep == ',' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
		$amount = floatval( str_replace( ',', '', $amount ) );
	}

	if ( empty( $amount ) ) {
		$amount = 0;
	}

	if( $decimals ) {
		$decimals = apply_filters( 'affwp_format_amount_decimals', affwp_get_decimal_count(), $amount );
	} else {
		$decimals = 0;
	}

	$formatted = number_format( $amount, $decimals, $decimal_sep, $thousands_sep );

	return apply_filters( 'affwp_format_amount', $formatted, $amount, $decimals, $decimal_sep, $thousands_sep );
}

/**
 * Retrieves the number of decimals to round to
 *
 * @since 1.8
 * @return int Number of decimal places
 */
function affwp_get_decimal_count() {
	return apply_filters( 'affwp_decimal_count', 2 );
}

/**
 * Formats referral rate based on the given type.
 *
 * @since 1.9
 *
 * @param int    $rate   Referral rate.
 * @param string $type   Optional. Rate type. Accepts 'percentage' or 'flat'. Default 'percentage'.
 * @return string Formatted rate string.
 */
function affwp_format_rate( $rate, $type = 'percentage' ) {
	if ( 'percentage' === $type ) {
		$rate = affwp_abs_number_round( $rate * 100 ) . '%';
	} else {
		$rate = affwp_currency_filter( $rate );
	}

	/**
	 * Filter the rate format.
	 *
	 * @since 1.9
	 *
	 * @param string $rate Formatted rate.
	 * @param string $type Rate type.
	 */
	return apply_filters( 'affwp_format_rate', $rate, $type );
}

/**
 * Formats the currency display
 *
 * @since 1.0
 * @param string $amount amount
 * @return array $currency Currencies displayed correctly
 */
function affwp_currency_filter( $amount ) {

	$currency = affwp_get_currency();
	$position = affiliate_wp()->settings->get( 'currency_position', 'before' );

	$negative = $amount < 0;

	if( $negative ) {
		$amount = substr( $amount, 1 ); // Remove proceeding "-" -
	}

	$amount = affwp_format_amount( $amount );

	if ( $position == 'before' ):
		switch ( $currency ):
			case "GBP" :
				$formatted = '&pound;' . $amount;
				break;
			case "BRL" :
				$formatted = 'R&#36;' . $amount;
				break;
			case "EUR" :
				$formatted = '&euro;' . $amount;
				break;
			case "USD" :
			case "AUD" :
			case "CAD" :
			case "HKD" :
			case "MXN" :
			case "SGD" :
				$formatted = '&#36;' . $amount;
				break;
			case 'RON' :
				$formatted = 'lei' . $amount;
				break;
			case 'UAH' :
				$formatted = '&#8372;' . $amount;
				break;
			case "JPY" :
				$formatted = '&yen;' . $amount;
				break;
			case "KRW" :
				$formatted = '&#8361;' . $amount;
				break;
			default :
			    $formatted = $currency . ' ' . $amount;
				break;
		endswitch;
		$formatted = apply_filters( 'affwp_' . strtolower( $currency ) . '_currency_filter_before', $formatted, $currency, $amount );
	else :
		switch ( $currency ) :
			case "GBP" :
				$formatted = $amount . '&pound;';
				break;
			case "BRL" :
				$formatted = $amount . 'R&#36;';
				break;
			case "EUR" :
				$formatted = $amount . '&euro;';
				break;
			case "USD" :
			case "AUD" :
			case "CAD" :
			case "HKD" :
			case "MXN" :
			case "SGD" :
				$formatted = $amount . '&#36;';
				break;
			case 'RON' :
				$formatted = $amount . 'lei';
				break;
			case 'UAH' :
				$formatted = $amount . '&#8372;';
				break;
			case "JPY" :
				$formatted = $amount . '&yen;';
				break;
			case "KRW" :
				$formatted = $amount . '&#8361;';
				break;
			default :
			    $formatted = $amount . ' ' . $currency;
				break;
		endswitch;
		$formatted = apply_filters( 'affwp_' . strtolower( $currency ) . '_currency_filter_after', $formatted, $currency, $amount );
	endif;

	if( $negative ) {
		// Prepend the mins sign before the currency sign
		$formatted = '-' . $formatted;
	}

	return $formatted;
}

/**
 * Set the number of decimal places per currency
 *
 * @since 1.4.2
 * @param int $decimals Number of decimal places
 * @return int $decimals
*/
function affwp_currency_decimal_filter( $decimals = 2 ) {
	global $affwp_options;

	$currency = affwp_get_currency();

	switch ( $currency ) {
		case 'RIAL' :
		case 'JPY' :
		case 'TWD' :
		case 'KRW' :

			$decimals = 0;
			break;
	}

	return $decimals;
}
add_filter( 'affwp_decimal_count', 'affwp_currency_decimal_filter' );

/**
 * Convert an object to an associative array.
 *
 * Can handle multidimensional arrays
 *
 * @since 1.0
 *
 * @param unknown $data
 * @return array
 */
function affwp_object_to_array( $data ) {
	if ( is_array( $data ) || is_object( $data ) ) {
		$result = array();
		foreach ( $data as $key => $value ) {
			$result[ $key ] = affwp_object_to_array( $value );
		}
		return $result;
	}
	return $data;
}

/**
 * Month Num To Name
 *
 * Takes a month number and returns the name three letter name of it.
 *
 * @since 1.0
 *
 * @param unknown $n
 * @return string Short month name
 */
function affwp_month_num_to_name( $n ) {
	$timestamp = mktime( 0, 0, 0, $n, 1, 2005 );

	return date_i18n( "M", $timestamp );
}

/**
 * Checks whether function is disabled.
 *
 * @since 1.0
 *
 * @param string  $function Name of the function.
 * @return bool Whether or not function is disabled.
 */
function affwp_is_func_disabled( $function ) {
	$disabled = explode( ',',  ini_get( 'disable_functions' ) );

	return in_array( $function, $disabled );
}

if ( ! function_exists( 'cal_days_in_month' ) ) {
	// Fallback in case the calendar extension is not loaded in PHP
	// Only supports Gregorian calendar
	function cal_days_in_month( $calendar, $month, $year ) {
		return date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
	}
}



/**
 * Get the referral format value
 *
 * @since 1.6
 * @param string $format referral format passed in via [affiliate_referral_url] shortcode
 * @return string affiliate ID or username
 */
function affwp_get_referral_format_value( $format = '', $affiliate_id = 0 ) {

	// get affiliate's user ID
	$user_id = affwp_get_affiliate_user_id( $affiliate_id );

	if ( ! $format ) {
		$format = affwp_get_referral_format();
	}

	switch ( $format ) {

		case 'username':
			$value = urlencode( affwp_get_affiliate_username( $affiliate_id ) );
			break;

		case 'id':
		default:
			$value = affwp_get_affiliate_id( $user_id );
			break;

	}

	return apply_filters( 'affwp_get_referral_format_value', $value, $format, $affiliate_id );
}

/**
 * Gets the referral format from Affiliates -> Settings -> General
 *
 * @since  1.6
 * @return string "id" or "username"
 */
function affwp_get_referral_format() {

	$referral_format = affiliate_wp()->settings->get( 'referral_format' );

	return $referral_format;

}

/**
 * Checks whether pretty referral URLs is enabled from Affiliates -> Settings -> General
 *
 * @since  1.6
 * @return boolean
 */
function affwp_is_pretty_referral_urls() {

	$is_pretty_affiliate_urls = affiliate_wp()->settings->get( 'referral_pretty_urls' );

	if ( $is_pretty_affiliate_urls ) {
		return (bool) true;
	}

	return (bool) false;

}

/**
 * Checks whether reCAPTCHA is enabled since it requires three options
 *
 * @since  1.7
 * @return boolean
 */
function affwp_is_recaptcha_enabled() {

	$checkbox   = affiliate_wp()->settings->get( 'recaptcha_enabled', 0 );
	$site_key   = affiliate_wp()->settings->get( 'recaptcha_site_key', '' );
	$secret_key = affiliate_wp()->settings->get( 'recaptcha_secret_key', '' );
	$enabled    = ( ! empty( $checkbox ) && ! empty( $site_key ) && ! empty( $secret_key ) );

	return (bool) apply_filters( 'affwp_recaptcha_enabled', $enabled );

}

/**
 * Sanitize values to an absolute number, rounded to the required decimal place
 *
 * Allows zero values, but ignores truly empty values.
 *
 * The correct type will be used automatically, depending on its value:
 *
 * - Whole numbers (including numbers with a 0 value decimal) will be return as ints
 * - Decimal numbers will be returned as floats
 * - Decimal numbers ending with 0 will be returned as strings
 *
 * 1     => (int) 1
 * 1.0   => (int) 1
 * 0.00  => (int) 0
 * 1.01  => (float) 1.01
 * 1.019 => (float) 1.02
 * 1.1   => (string) 1.10
 * 1.10  => (string) 1.10
 * 1.199 => (string) 1.20
 *
 * @param  mixed  $val
 * @param  int    $precision  Number of required decimal places (optional)
 * @return mixed              Returns an int, float or string on success, null when empty
 */
function affwp_abs_number_round( $val, $precision = 2 ) {

	// 0 is a valid value so we check only for other empty values
	if ( is_null( $val ) || '' === $val || false === $val ) {

		return;

	}

	// Value cannot be negative
	$val = abs( $val );

	// Decimal precision must be a absolute integer
	$precision = absint( $precision );

	// Enforce the number of decimal places required (precision)
	$val = sprintf( ( round( $val, $precision ) == intval( $val ) ) ? '%d' : "%.{$precision}f", $val );

	// Convert number to the proper type (int, float, or string) depending on its value
	if ( false !== strpos( $val, '.' ) ) {

		$val = ( '0' !== substr( $val, -1 ) ) ? floatval( $val ) : (string) $val;

	} else {

		$val = absint( $val );

	}

	return $val;

}

/**
 * Makes a URL more human readable by removing unnecessary elements.
 *
 * @since 1.8
 *
 * @param string $url URL to parse.
 * @return string "Human readable" URL.
 */
function affwp_make_url_human_readable( $url ) {
	$parts = parse_url( $url );

	if ( ! $parts ) {
		return $url;
	}

	$path_with_prefixed_slash = empty( $parts['path'] ) ? '' : $parts['path'];
	$path_without_prefix = substr( $path_with_prefixed_slash, 1 );

	if ( ! empty( $parts['query'] ) ) {

		parse_str( $parts['query'], $query_vars );

		/** @var WP $wp */
		global $wp;

		$public_query_vars = $wp->public_query_vars;

		$query_vars_to_keep = array();

		// Whitelist against public (registered) query vars.
		foreach ( $query_vars as $var => $value ) {

			if ( in_array( $var, $public_query_vars ) ) {
				$query_vars_to_keep[ $var ] = $value;
			}
		}
	}

	if ( ! empty( $query_vars_to_keep ) ) {
		$query_string = '?' . http_build_query( $query_vars_to_keep );
	} else {
		$query_string = '';
	}

	if ( empty( $path_without_prefix ) ) {
		$human_readable = $parts['host'];

		if ( ! empty( $query_string ) ) {
			$human_readable = trailingslashit( $human_readable ) . $query_string;
		}
	} else {
		$human_readable = '../' . trailingslashit( $path_without_prefix ) . $query_string;
	}

	return $human_readable;
}

/**
 * Show a tab in the Affiliate Area
 *
 * @since  1.8
 * @return boolean
 */
function affwp_affiliate_area_show_tab( $tab = '' ) {
	return apply_filters( 'affwp_affiliate_area_show_tab', true, $tab );
}

/**
 * Cleans the cache for a given object.
 *
 * @since 1.9
 *
 * @param AffWP\Object $object Object.
 * @return bool True if the item cache was cleaned, false otherwise.
 */
function affwp_clean_item_cache( $object ) {
	if ( ! is_object( $object ) ) {
		return false;
	}

	if ( ! method_exists( $object, 'get_cache_key' ) ) {
		return false;
	}

	$Object_Class      = get_class( $object );
	$cache_key         = $Object_Class::get_cache_key( $object->ID );
	$cache_group       = $Object_Class::$object_type;
	$query_cache_group = $Object_Class::$object_group;

	// Individual object.
	wp_cache_delete( $cache_key, $cache_group );

	// Prime the item cache.
	$Object_Class::get_instance( $object->ID );

	// last_changed for queries.
	wp_cache_set( 'last_changed', microtime(), $query_cache_group );
}
