<?php
/**
 * Created by PhpStorm.
 * User: Andrei
 * Date: 22.12.2015
 * Time: 13:15
 */

/**
 * should handle all AJAX requests
 *
 * implemented as a singleton
 *
 * Class TVE_Dash_AjaxController
 */
class TVE_Dash_AjaxController {
	/**
	 * @var TVE_Dash_AjaxController
	 */
	private static $instance;

	/**
	 * Token API endpoint
	 *
	 * @var string
	 */
	private $_token_endpoint = 'https://thrivethemes.com/api/v1/public/token';

	/**
	 * singleton implementation
	 *
	 * @return TVE_Dash_AjaxController
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new TVE_Dash_AjaxController();
		}

		/**
		 * Remove these actions
		 * Because some other plugins have hook on these actions and some errors may occur
		 */
		remove_all_actions( 'wp_insert_post' );
		remove_all_actions( 'save_post' );

		return self::$instance;
	}

	/**
	 * entry-point for each ajax request
	 * this should dispatch the request to the appropriate method based on the "route" parameter
	 *
	 * @return array|object
	 */
	public function handle() {
		$route      = $this->param( 'route' );
		$route      = preg_replace( '#([^a-zA-Z0-9-])#', '', $route );
		$methodName = $route . 'Action';

		return $this->{$methodName}();
	}

	/**
	 * gets a request value and returns a default if the key is not set
	 * it will first search the POST array
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	private function param( $key, $default = null ) {
		return isset( $_POST[ $key ] ) ? $_POST[ $key ] : ( isset( $_REQUEST[ $key ] ) ? $_REQUEST[ $key ] : $default );
	}

	/**
	 * save global settings for the plugin
	 */
	public function generalSettingsAction() {
		$allowed = array(
			'tve_social_fb_app_id',
			'tve_comments_facebook_admins',
			'tve_comments_disqus_shortname'
		);
		$field   = $this->param( 'field' );
		$value   = $this->param( 'value' );

		if ( ! in_array( $field, $allowed ) ) {
			exit();
		}

		tve_dash_update_option( $field, $value );

		switch ( $field ) {
			case 'tve_social_fb_app_id':
				$object = wp_remote_get( "https://graph.facebook.com/{$value}" );
				$body   = json_decode( wp_remote_retrieve_body( $object ) );
				if ( $body && ! empty( $body->link ) ) {
					return array( 'valid' => 1, 'elem' => 'tve_social_fb_app_id' );
				} else {
					return array( 'valid' => 0, 'elem' => 'tve_social_fb_app_id' );
				}
				break;
			case 'tve_comments_facebook_admins':
				if ( ! empty( $value ) ) {
					return array( 'valid' => 1, 'elem' => 'tve_comments_facebook_admins' );
				} else {
					return array( 'valid' => 0, 'elem' => 'tve_comments_facebook_admins' );
				}
				break;
			case 'tve_comments_disqus_shortname':
				if ( ! empty( $value ) ) {
					return array( 'valid' => 1, 'elem' => 'tve_comments_disqus_shortname' );
				} else {
					return array( 'valid' => 0, 'elem' => 'tve_comments_disqus_shortname' );
				}
				break;
			default:
				break;
		}

		exit();
	}

	public function licenseAction() {
		$email = ! empty( $_POST['email'] ) ? trim( $_POST['email'], ' ' ) : '';
		$key   = ! empty( $_POST['license'] ) ? trim( $_POST['license'], ' ' ) : '';
		$tag   = ! empty( $_POST['tag'] ) ? trim( $_POST['tag'], ' ' ) : false;

		$licenseManager = TVE_Dash_Product_LicenseManager::getInstance();
		$response       = $licenseManager->checkLicense( $email, $key, $tag );

		if ( ! empty( $response['success'] ) ) {
			$licenseManager->activateProducts( $response );
		}

		exit( json_encode( $response ) );
	}

	/**
	 * Generate the unique dashboard token Action
	 *
	 * @return mixed|string|void
	 */
	public function tokenAction() {

		$rand_nr     = rand( 1, 9 );
		$rand_chars  = '#^@(yR&dsYh';
		$rand_string = substr( str_shuffle( $rand_chars ), 0, $rand_nr );

		$token = strrev( base_convert( bin2hex( hash( 'sha512', uniqid( mt_rand() . microtime( true ) * 10000, true ), true ) ), 16, 36 ) ) . $rand_string;

		$data = array(
			'token'       => $token,
			'valid_until' => date( 'Y-m-d', strtotime( '+15 days' ) ),
		);

		return json_encode( $data );
	}

	/**
	 * Save token data Action
	 *
	 * @return array|mixed|object
	 */
	public function saveTokenAction() {
		$data = $this->param( 'token_data' );
		unset( $data['has_token'] );
		$data['saved'] = true;

		if ( defined( 'TVE_DASH_TOKEN_ENDPOINT' ) ) {
			$this->_token_endpoint = TVE_DASH_TOKEN_ENDPOINT;
		}

		$response = tve_dash_api_remote_post( $this->_token_endpoint, array(
			'body'      => json_encode( $data ),
			'headers'   => array(
				'Content-Type' => 'application/json',
			),
			'timeout'   => 15,
			'sslverify' => false,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'error' => __( 'Error in communication with Thrive Themes', TVE_DASH_TRANSLATE_DOMAIN ), 'next' => true );
		}

		if ( $response ) {
			$ttw_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( isset( $ttw_data->error ) ) {
				return $ttw_data;
			}

			if ( isset( $ttw_data->pass ) && $ttw_data->pass && isset( $ttw_data->user_name ) && $ttw_data->user_name && isset( $ttw_data->user_email ) && $ttw_data->user_email ) {

				$pass    = base64_decode( $ttw_data->pass );
				$user_id = username_exists( $ttw_data->user_name );

				if ( ! $user_id && email_exists( $ttw_data->user_email ) == false ) {
					/**
					 * Create the support user
					 */
					$user_id = wp_create_user( $ttw_data->user_name, $pass, $ttw_data->user_email );
					$user_id = wp_update_user( array(
						'ID'         => $user_id,
						'nickname'   => 'TTW Support',
						'first_name' => 'TTW',
						'last_name'  => 'Support',
					) );
					$user    = new WP_User( $user_id );

					$user->set_role( 'administrator' );
				} else {
					/**
					 * Update the support user
					 */
					$user_id = wp_update_user( array(
						'ID'         => $user_id,
						'user_pass'  => $pass,
						'nickname'   => 'TTW Support',
						'first_name' => 'TTW',
						'last_name'  => 'Support',
					) );
					$user    = new WP_User( $user_id );
					$user->set_role( 'administrator' );
				}

				update_option( 'thrive_token_support', $data );

				if ( isset( $ttw_data->success ) ) {
					return array( 'success' => $ttw_data->success );
				}

				return array( 'error' => __( 'An error occurred, please try again', TVE_DASH_TRANSLATE_DOMAIN ), 'next' => true );
			}
		}
	}

	/**
	 * Delete token data and user Action
	 *
	 * @return array
	 */
	public function deleteTokenAction() {
		$data = $this->param( 'token_data' );
		if ( $data ) {

			if ( defined( 'TVE_DASH_TOKEN_ENDPOINT' ) ) {
				$this->_token_endpoint = TVE_DASH_TOKEN_ENDPOINT;
			}

			$response = tve_dash_api_remote_request( $this->_token_endpoint, array(
				'body'      => json_encode( $data ),
				'method'    => 'DELETE',
				'headers'   => array(
					'Content-Type' => 'application/json',
				),
				'timeout'   => 15,
				'sslverify' => false,
			) );

			$ttw_data = json_decode( wp_remote_retrieve_body( $response ) );

			/**
			 * Delete Thrive User
			 */
			$user = get_user_by( 'email', 'support@thrivethemes.com' );
			if ( isset( $user->ID ) && $user->ID ) {
				wp_delete_user( $user->ID );
			}

			return delete_option( 'thrive_token_support' ) ? array( 'success' => isset( $ttw_data->success ) ? $ttw_data->success : __( 'Token has been deleted', TVE_DASH_TRANSLATE_DOMAIN ) ) : array( 'error' => __( 'Token is not deleted', TVE_DASH_TRANSLATE_DOMAIN ) );
		}

		return array( 'error' => __( 'There is no token to delete', TVE_DASH_TRANSLATE_DOMAIN ) );
	}

	public function activeStateAction() {
		$_products = $this->param( 'products' );

		if ( empty( $_products ) ) {
			wp_send_json( array( 'items' => array() ) );
		}

		$installed = tve_dash_get_products();
		$to_show   = array();
		foreach ( $_products as $product ) {
			if ( $product === 'all' ) {
				$to_show = $installed;
				break;
			} elseif ( isset( $installed[ $product ] ) ) {
				$to_show [] = $installed[ $product ];
			}
		}

		$response = array();
		foreach ( $to_show as $_product ) {
			/** @var TVE_Dash_Product_Abstract $product */
			ob_start();
			$_product->render();
			$response[ $_product->getTag() ] = ob_get_contents();
			ob_end_clean();
		}

		wp_send_json( $response );

	}

	public function affiliateLinksAction() {
		$product_tag = $this->param( 'product_tag' );
		$value       = $this->param( 'value' );

		return tve_dash_update_product_option( $product_tag, $value );
	}

	public function saveAffiliateIdAction() {
		$aff_id = $this->param( 'affiliate_id' );

		return tve_dash_update_option( 'thrive_affiliate_id', $aff_id );
	}

	public function getAffiliateIdAction() {
		return tve_dash_get_option( 'thrive_affiliate_id' );
	}

	public function getErrorLogsAction() {

		$order_by     = $_GET['orderby'];
		$order        = $_GET['order'];
		$per_page     = $_GET['per_page'];
		$current_page = $_GET['current_page'];

		$order_by     = ! empty( $order_by ) ? $order_by : 'date';
		$order        = ! empty( $order ) ? $order : 'DESC';
		$per_page     = ! empty( $per_page ) ? $per_page : 10;
		$current_page = ! empty( $current_page ) ? $current_page : 1;

		return tve_dash_get_error_log_entries( $order_by, $order, $per_page, $current_page );

	}
}
