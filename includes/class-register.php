<?php

class Affiliate_WP_Register {

	private $errors;

	/**
	 * Get things started
	 *
	 * @since 1.0
	 */
	public function __construct() {

		add_action( 'affwp_affiliate_register', array( $this, 'process_registration' ) );
		add_action( 'user_register', array( $this, 'auto_register_user_as_affiliate' ) );
		add_action( 'user_new_form', array( $this, 'add_as_affiliate' ) );
		add_action( 'user_register', array( $this, 'process_add_as_affiliate' ) );
		add_action( 'added_existing_user', array( $this, 'process_add_as_affiliate' ) );
		add_action( 'admin_footer', array( $this, 'scripts' ) );

	}

	/**
	 * Register Form
	 *
	 * @since 1.2
	 * @global $affwp_register_redirect
	 * @param string $redirect Redirect page URL
	 * @return string Register form
	*/
	public function register_form( $redirect = '' ) {
		global $affwp_register_redirect;

		if ( empty( $redirect ) ) {
			$redirect = affiliate_wp()->tracking->get_current_page_url();
		}

		$affwp_register_redirect = $redirect;

		ob_start();

		affiliate_wp()->templates->get_template_part( 'register' );

		return apply_filters( 'affwp_register_form', ob_get_clean() );

	}

	/**
	 * Process registration form submission
	 *
	 * @since 1.0
	 */
	public function process_registration( $data ) {

		if ( ! isset( $_POST['affwp_register_nonce'] ) || ! wp_verify_nonce( $_POST['affwp_register_nonce'], 'affwp-register-nonce' ) ) {
			return;
		}

		do_action( 'affwp_pre_process_register_form' );

		if ( ! is_user_logged_in() ) {

			// Loop through required fields and show error message
			foreach ( $this->required_fields() as $field_name => $value ) {

				$field = sanitize_text_field( $_POST[ $field_name ] );

				if ( empty( $field ) ) {
					$this->add_error( $value['error_id'], $value['error_message'] );
				}
			}

			if ( username_exists( $data['affwp_user_login'] ) ) {
				$this->add_error( 'username_unavailable', __( 'Username already taken', 'affiliate-wp' ) );
			}

			if ( ! validate_username( $data['affwp_user_login'] ) || strstr( $data['affwp_user_login'], ' ' ) ) {
				if ( is_multisite() ) {
					$this->add_error( 'username_invalid', __( 'Invalid username. Only lowercase letters (a-z) and numbers are allowed', 'affiliate-wp' ) );
				} else {
					$this->add_error( 'username_invalid', __( 'Invalid username', 'affiliate-wp' ) );
				}
			}

			if ( is_numeric( $data['affwp_user_login'] ) ) {
				$this->add_error( 'username_invalid_numeric', __( 'Invalid username. Usernames must include at least one letter', 'affiliate-wp' ) );
			}

			if ( email_exists( $data['affwp_user_email'] ) ) {
				$this->add_error( 'email_unavailable', __( 'Email address already taken', 'affiliate-wp' ) );
			}

			if ( empty( $data['affwp_user_email'] ) || ! is_email( $data['affwp_user_email'] ) ) {
				$this->add_error( 'email_invalid', __( 'Invalid email', 'affiliate-wp' ) );
			}

			if ( ! empty( $data['affwp_payment_email'] ) && $data['affwp_payment_email'] != $data['affwp_user_email'] && ! is_email( $data['affwp_payment_email'] ) ) {
				$this->add_error( 'payment_email_invalid', __( 'Invalid payment email', 'affiliate-wp' ) );
			}

			if ( ( ! empty( $_POST['affwp_user_pass'] ) && empty( $_POST['affwp_user_pass2'] ) ) || ( $_POST['affwp_user_pass'] !== $_POST['affwp_user_pass2'] ) ) {
				$this->add_error( 'password_mismatch', __( 'Passwords do not match', 'affiliate-wp' ) );
			}

		} else {

			// Loop through required fields and show error message
			foreach ( $this->required_fields() as $field_name => $value ) {

				if( ! empty( $value['logged_out'] ) ) {
					continue;
				}

				$field = sanitize_text_field( $_POST[ $field_name ] );

				if ( empty( $field ) ) {
					$this->add_error( $value['error_id'], $value['error_message'] );
				}
			}

		}

		$terms_of_use = affiliate_wp()->settings->get( 'terms_of_use' );
		if ( ! empty( $terms_of_use ) && empty( $_POST['affwp_tos'] ) ) {
			$this->add_error( 'empty_tos', __( 'Please agree to our terms of use', 'affiliate-wp' ) );
		}

		if ( affwp_is_recaptcha_enabled() && ! $this->recaptcha_response_is_valid( $data ) ) {
			$this->add_error( 'recaptcha_required', __( 'Please verify that you are not a robot', 'affiliate-wp' ) );
		}

		if ( ! empty( $_POST['affwp_honeypot'] ) ) {
			$this->add_error( 'spam', __( 'Nice try honey bear, don\'t touch our honey', 'affiliate-wp' ) );
		}

		if ( affwp_is_affiliate() ) {
			$this->add_error( 'already_registered', __( 'You are already registered as an affiliate', 'affiliate-wp' ) );
		}

		do_action( 'affwp_process_register_form' );

		// only log the user in if there are no errors
		if ( empty( $this->errors ) ) {
			$this->register_user();

			$redirect = apply_filters( 'affwp_register_redirect', $data['affwp_redirect'] );

			if ( $redirect ) {
				wp_redirect( $redirect ); exit;
			}

		}

	}

	/**
	 * Verify reCAPTCHA response is valid using a POST request to the Google API
	 *
	 * @access private
	 * @since  1.7
	 * @param  array   $data
	 * @return boolean
	 */
	private function recaptcha_response_is_valid( $data ) {
		if ( ! affwp_is_recaptcha_enabled() || empty( $data['g-recaptcha-response'] ) || empty( $data['g-recaptcha-remoteip'] ) ) {
			return false;
		}

		$verify = wp_safe_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'body' => array(
					'secret'   => affiliate_wp()->settings->get( 'recaptcha_secret_key' ),
					'response' => $data['g-recaptcha-response'],
					'remoteip' => $data['g-recaptcha-remoteip']
				)
			)
		);

		$verify = json_decode( wp_remote_retrieve_body( $verify ) );

		return ( ! empty( $verify->success ) && true === $verify->success );
	}

	/**
	 * Register Form Required Fields
	 *
	 * @access      public
	 * @since       1.1.4
	 * @return      array
	 */
	public function required_fields() {
		$required_fields = array(
			'affwp_user_name' 	=> array(
				'error_id'      => 'empty_name',
				'error_message' => __( 'Please enter your name', 'affiliate-wp' ),
				'logged_out'    => true
			),
			'affwp_user_login' 	=> array(
				'error_id'      => 'empty_username',
				'error_message' => __( 'Invalid username', 'affiliate-wp' ),
				'logged_out'    => true
			),
			'affwp_user_url' 	=> array(
				'error_id'      => 'invalid_url',
				'error_message' => __( 'Please enter a website URL', 'affiliate-wp' )
			),
			'affwp_user_pass' 	=> array(
				'error_id'      => 'empty_password',
				'error_message' => __( 'Please enter a password', 'affiliate-wp' ),
				'logged_out'    => true
			)
		);

		return apply_filters( 'affwp_register_required_fields', $required_fields );
	}

	/**
	 * Register the affiliate / user
	 *
	 * @since 1.0
	 */
	private function register_user() {

		$status = affiliate_wp()->settings->get( 'require_approval' ) ? 'pending' : 'active';

		if ( ! is_user_logged_in() ) {

			$name       = explode( ' ', sanitize_text_field( $_POST['affwp_user_name'] ) );
			$user_first = $name[0];
			$user_last  = isset( $name[1] ) ? $name[1] : '';

			$args = array(
				'user_login'    => sanitize_text_field( $_POST['affwp_user_login'] ),
				'user_email'    => sanitize_text_field( $_POST['affwp_user_email'] ),
				'user_pass'     => sanitize_text_field( $_POST['affwp_user_pass'] ),
				'display_name'  => $user_first . ' ' . $user_last,
				'first_name'    => $user_first,
				'last_name'     => $user_last
			);

			$user_id = wp_insert_user( $args );

		} else {

			$user_id = get_current_user_id();
			$user    = (array) get_userdata( $user_id );
			$args    = (array) $user['data'];

		}

		// promotion method
		$promotion_method = isset( $_POST['affwp_promotion_method'] ) ? sanitize_text_field( $_POST['affwp_promotion_method'] ) : '';

		if ( $promotion_method ) {
			update_user_meta( $user_id, 'affwp_promotion_method', $promotion_method );
		}

		// website URL
		$website_url = isset( $_POST['affwp_user_url'] ) ? sanitize_text_field( $_POST['affwp_user_url'] ) : '';

		if ( $website_url ) {
			wp_update_user( array( 'ID' => $user_id, 'user_url' => $website_url ) );
		}

		$affiliate_id = affwp_add_affiliate( array(
			'status'        => $status,
			'user_id'       => $user_id,
			'payment_email' => ! empty( $_POST['affwp_payment_email'] ) ? sanitize_text_field( $_POST['affwp_payment_email'] ) : '',
			'status'        => affiliate_wp()->settings->get( 'require_approval' ) ? 'pending' : 'active',
		) );

		if ( ! is_user_logged_in() ) {
			$this->log_user_in( $user_id, sanitize_text_field( $_POST['affwp_user_login'] ) );
		}

		// Retrieve affiliate ID. Resolves issues with caching on some hosts, such as GoDaddy
		$affiliate_id = affwp_get_affiliate_id( $user_id );

		do_action( 'affwp_register_user', $affiliate_id, $status, $args );
	}

	/**
	 * Log the user in
	 *
	 * @since 1.0
	 */
	private function log_user_in( $user_id = 0, $user_login = '', $remember = false ) {

		$user = get_userdata( $user_id );
		if ( ! $user )
			return;

		wp_set_auth_cookie( $user_id, $remember );
		wp_set_current_user( $user_id, $user_login );
		do_action( 'wp_login', $user_login, $user );

	}

	/**
	 * Register a user as an affiliate during user registration
	 *
	 * @since 1.1
	 * @return bool
	 */
	public function auto_register_user_as_affiliate( $user_id = 0 ) {

		if ( ! affiliate_wp()->settings->get( 'auto_register' ) ) {
			return;
		}

		if ( did_action( 'affwp_affiliate_register' ) ) {
			return;
		}

		$affiliate_id = affwp_add_affiliate( array( 'user_id' => $user_id ) );

		if ( ! $affiliate_id ) {
			return;
		}

		$status = affwp_get_affiliate_status( $affiliate_id );
		$user   = (array) get_userdata( $user_id );
		$args   = (array) $user['data'];

		/**
		 * Fires after a new user has been auto-registered as an affiliate
		 *
		 * @since  1.7
		 * @param  int    $affiliate_id
		 * @param  string $status
		 * @param  array  $args
		 */
		do_action( 'affwp_auto_register_user', $affiliate_id, $status, $args );

	}

	/**
	 * Register a submission error
	 *
	 * @since 1.0
	 */
	public function add_error( $error_id, $message = '' ) {
		$this->errors[ $error_id ] = $message;
	}

	/**
	 * Print errors
	 *
	 * @since 1.0
	 */
	public function print_errors() {

		if ( empty( $this->errors ) ) {
			return;
		}

		echo '<div class="affwp-errors">';

		foreach( $this->errors as $error_id => $error ) {

			echo '<p class="affwp-error">' . esc_html( $error ) . '</p>';

		}

		echo '</div>';

	}

	/**
	 * Get errors
	 *
	 * @since 1.1
	 * @return array
	 */
	public function get_errors() {

		if ( empty( $this->errors ) ) {
			return array();
		}

		return $this->errors;

	}

	/**
	 * Adds an "Add As Affiliate" checkbox to the WordPress "Add New User" screen
	 * On multisite this will only show when the "Skip Confirmation Email" checkbox is enabled
	 *
	 * @since 1.8
	 * @return void
	 */
	public function add_as_affiliate( $context ) {

		if ( affiliate_wp()->settings->get( 'auto_register' ) ) {
			return;
		}

		?>
		<table id="affwp-create-affiliate" class="form-table" style="margin-top:0;">
			<tr>
				<th scope="row"><label for="create-affiliate-<?php echo $context; ?>"><?php _e( 'Add as Affiliate',  'affiliate-wp' ); ?></label></th>
				<td>
					<label for="create-affiliate-<?php echo $context; ?>"><input type="checkbox" id="create-affiliate-<?php echo $context; ?>" name="affwp_create_affiliate" value="1" /> <?php _e( 'Add the user as an affiliate.', 'affiliate-wp' ); ?></label>
				</td>
			</tr>
			<?php if ( ! affiliate_wp()->emails->is_email_disabled() ) : ?>
			<tr>
				<th scope="row"><label for="disable-affiliate-email-<?php echo $context; ?>"><?php _e( 'Disable Affiliate Email',  'affiliate-wp' ); ?></label></th>
				<td>
					<label for="disable-affiliate-email-<?php echo $context; ?>"><input type="checkbox" id="disable-affiliate-email-<?php echo $context; ?>" name="disable_affiliate_email" value="1" /> <?php _e( 'Disable the application accepted email sent to the affiliate.', 'affiliate-wp' ); ?></label>
				</td>
			</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * Adds a new affiliate when the "Add As Affiliate" checkbox is enabled
	 * Only works when "Skip Confirmation Email" is enabled
	 *
	 * @since 1.8
	 * @return void
	 */
	public function process_add_as_affiliate( $user_id = 0 ) {

		if ( affiliate_wp()->settings->get( 'auto_register' ) ) {
			return;
		}

		$add_affiliate     = isset( $_POST['affwp_create_affiliate'] ) ? $_POST['affwp_create_affiliate'] : '';
		$skip_confirmation = isset( $_POST['noconfirmation'] ) ? $_POST['noconfirmation'] : '';

		if ( is_multisite() && ! ( $add_affiliate && $skip_confirmation ) ) {
			return;
		} elseif ( ! $add_affiliate ) {
			return;
		}

		if ( $add_affiliate && isset( $_POST['disable_affiliate_email'] ) ) {
			add_filter( 'affwp_notify_on_approval', '__return_false' );
		}

		// add the affiliate
		affwp_add_affiliate( array( 'user_id' => $user_id ) );

	}

	/**
	 * Scripts
	 *
	 * @since 1.8
	 * @return void
	 */
	function scripts() {

		if ( affiliate_wp()->settings->get( 'auto_register' ) ) {
			return;
		}

		global $pagenow;

		/**
		 * Javascript for the "Add New User" screen on (multisite only)
		 */
		if ( ( ! empty( $pagenow ) && ( 'user-new.php' === $pagenow ) && is_multisite() ) ) : ?>

		<script>
		jQuery(document).ready(function($) {

			var optionSkipConfirmation = $('input[name="noconfirmation"]');

			// show or hide the add affiliate table based on the "Skip Confirmation" checkbox option
			optionSkipConfirmation.click( function(e) {

				var tableNoConfirmation = this.closest('table');
				var tableAddAffiliate = $( tableNoConfirmation ).next('table');

				if ( this.checked ) {
					tableAddAffiliate.show();

				} else {
					tableAddAffiliate.hide();
				}

			});

			var tableNoConfirmation = $( optionSkipConfirmation ).closest('table');
			var tableAddAffiliate = $( tableNoConfirmation ).next('table');

			if ( optionSkipConfirmation.is(':checked') ) {
				tableAddAffiliate.show();
			} else {
				tableAddAffiliate.hide();
			}

		});

		</script>

		<?php endif;

	}

}
