<?php
/*
Plugin Name: BP Invisible reCAPTCHA
Description: Use Google's Invisible reCAPTCHA to thwart bad signups.
Version: 0.1
Author: r-a-y
Author URI: http://profiles.wordpress.org/r-a-y
License: GPLv2 or later
Text Domain: bp-invisible-recaptcha
*/

/**
 * BP Invisible reCAPTCHA Core.
 *
 * @package bp-invisible-recaptcha
 * @subpackage Core
 */

add_action( 'bp_include', array( 'Im_Invisible_Can_You_See_Me', 'init' ) );

/**
 * BP Invisible reCAPTCHA.
 *
 * No admin page at the moment.  You need to define
 * 'BP_INVISIBLE_RECAPTCHA_SITEKEY' and 'BP_INVISIBLE_RECAPTCHA_SECRET' in
 * wp-config.php.
 *
 * To generate some invisible reCAPTCHA keys, visit:
 * https://www.google.com/recaptcha/admin/create
 *
 * @since 0.1
 */
class Im_Invisible_Can_You_See_Me {
	/**
	 * Static initializer.
	 *
	 * @since 0.1
	 */
	public static function init() {
		return new self();
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	protected function __construct() {
		// Must be defined.
		if ( ! defined( 'BP_INVISIBLE_RECAPTCHA_SITEKEY' ) || ! defined( 'BP_INVISIBLE_RECAPTCHA_SECRET' ) ) {
			return;
		}

		// Hooks.
		add_action( 'bp_signup_validate', array( $this, 'validate' ) );
		add_action( 'bp_before_registration_submit_buttons', array( $this, 'add_recaptcha' ) );
	}

	/**
	 * Validate invisible reCAPTCHA response.
	 *
	 * @since 0.1
	 */
	public function validate() {
		$bp = buddypress();

		if ( empty( $_POST['g-recaptcha-response'] ) ) {
			$bp->signup->errors['signup_password_confirm'] = esc_html__( 'Please turn on javascript to register for an account', 'bp-invisible-recaptcha' );
		}

		$verify = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
			'body' => array(
				'secret'   => constant( 'BP_INVISIBLE_RECAPTCHA_SECRET' ),
				'response' => $_POST['g-recaptcha-response']
			)
		) );

		$verify = json_decode( $verify['body'] );

		if ( ! isset( $verify->success ) ) {
			$bp->signup->errors['signup_password_confirm'] = esc_html__( 'Something went wrong when verifying the spam prevention response', 'bp-invisible-recaptcha' );
		}

		if ( false === $verify->success ) {
			$bp->signup->errors['signup_password_confirm'] = esc_html__( 'You did not pass our spam prevention check', 'bp-invisible-recaptcha' );
		}
	}

	/**
	 * Add invisible reCAPTCHA to registration form.
	 *
	 * We have to add the 'signup_submit' field again due to the way we are
	 * submitting the form after the reCAPTCHA check.
	 *
	 * @since 0.1
	 */
	public function add_recaptcha() {
		$sitekey = constant( 'BP_INVISIBLE_RECAPTCHA_SITEKEY' );

		$output = <<<BTN
<script>
function bpIRSubmit(token) {
	document.getElementById('signup_form').submit();
}
</script>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<div id='recaptcha' class="g-recaptcha" data-sitekey="{$sitekey}" data-bind="signup_submit" data-callback="bpIRSubmit" data-size="invisible"></div>
<input type="hidden" name="signup_submit" value="1" />

BTN;

		echo $output;
	}

}
