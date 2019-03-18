<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

if ( ! class_exists( 'TCB_Editor_Ajax' ) ) {

	/**
	 * Handles all ajax interactions from the editor page
	 *
	 * Class TCB_Editor_Ajax
	 */
	class TCB_Editor_Ajax {
		const ACTION = 'tcb_editor_ajax';
		const NONCE_KEY = 'tve-le-verify-sender-track129';

		/**
		 *
		 * Add parameters to the localization of the main frame javascript
		 *
		 * @param array $data
		 *
		 * @return array
		 */
		public function localize( $data ) {
			$data['ajax'] = array(
				'action' => self::ACTION,
			);

			return $data;
		}

		/**
		 * Init the object, during the AJAX request. Adds ajax handlers and verifies nonces
		 */
		public function init() {
			add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
		}

		/**
		 * Handles the ajax call
		 */
		public function handle() {
			if ( wp_verify_nonce( $this->param( 'nonce' ), self::NONCE_KEY ) === false ) {
				$this->error( __( 'This page has expired. Please reload and try again', 'thrive-cb' ), 403, 'nonce_expired' );
			}

			$custom = $this->param( 'custom' );
			if ( empty( $custom ) || ! method_exists( $this, 'action_' . $custom ) ) {
				$this->error( 'Invalid request.', 404 );
			}
			$action   = 'action_' . $custom;
			$response = call_user_func( array( $this, $action ) );
			if ( $this->param( 'expect' ) === 'html' ) {
				wp_die( $response );
			}

			$this->json( $response );
		}

		/**
		 * @param string $key
		 * @param mixed  $default
		 *
		 * @return mixed
		 */
		protected function param( $key, $default = null ) {
			return isset( $_POST[ $key ] ) ? $_POST[ $key ] : ( isset( $_GET[ $key ] ) ? $_GET[ $key ] : $default );
		}

		/**
		 *
		 * @param string|WP_Error $message
		 * @param int             $code
		 * @param string          $str_code
		 */
		protected function error( $message, $code = 500, $str_code = '' ) {

			if ( is_wp_error( $message ) ) {
				$message = $message->get_error_message();
			}
			status_header( $code );

			if ( $this->param( 'expect' ) === 'html' ) {
				wp_die( $message, $code );
			}

			$json = array(
				'error'   => true,
				'message' => $message,
			);
			if ( $str_code ) {
				$json['code'] = $str_code;
			}
			wp_send_json( $json );
		}

		/**
		 * Send a json success response
		 *
		 * Makes sure the response always contain a 'message' and a success field
		 *
		 * @param array $data
		 */
		protected function json( $data ) {
			if ( is_scalar( $data ) ) {
				$data = array(
					'message' => $data,
				);
			}
			if ( ! isset( $data['success'] ) ) {
				$data['success'] = true;
			}
			wp_send_json( $data );
		}

		/** ------------------ AJAX endpoints after this point ------------------ **/

		/**
		 * Saves the user-selected post_types to use in autocomplete search for links
		 *
		 * @return string success message
		 */
		public function action_save_link_post_types() {
			/**
			 * Make sure there is no extra data
			 */
			$all_post_types = get_post_types();
			$post_types     = $this->param( 'post_types', array() );
			update_option( 'tve_hyperlink_settings', array_intersect( $post_types, $all_post_types ) );

			return __( 'Settings saved', 'thrive-cb' );
		}

		/**
		 * Search a post ( used in quick search for link elements )
		 * Will search in a range of post types, filterable
		 *
		 */
		public function action_post_search() {
			$s = trim( wp_unslash( $this->param( 'q' ) ) );
			$s = trim( $s );

			$selected_post_types = array( 'post', 'page', 'product' );

			/**
			 * Add filter to allow hooking into the selected post types
			 */
			$selected_post_types = apply_filters( 'tcb_autocomplete_selected_post_types', $selected_post_types );

			if ( ! $this->param( 'ignore_settings' ) ) {//do not ignore user settings
				/**
				 * post types saved by the user
				 */
				$selected_post_types = maybe_unserialize( get_option( 'tve_hyperlink_settings', $selected_post_types ) );
			}

			if ( $this->param( 'search_lightbox' ) ) {
				/**
				 * Filter that allows custom post types to be included in search results for site linking
				 */
				$post_types_data = apply_filters( 'tcb_link_search_post_types', array(
					'tcb_lightbox' => array(
						'name'         => __( 'TCB Lightbox', 'thrive-cb' ),
						'event_action' => 'thrive_lightbox',
					),
				) );

				foreach ( $post_types_data as $key => $value ) {
					/**
					 * if the key is numeric, the value is actually a post type, if not, the value is information for the post type
					 */
					$selected_post_types[] = is_numeric( $key ) ? $value : $key;
				}
			}

			$args = array(
				'post_type'   => $selected_post_types,
				'post_status' => 'publish',
				's'           => $s,
				'numberposts' => 20,
			);

			$posts = array();
			foreach ( get_posts( $args ) as $item ) {
				$title = $item->post_title;
				if ( ! empty( $s ) ) {
					$quoted           = preg_quote( $s, '#' );
					$item->post_title = preg_replace( "#($quoted)#i", '<b>$0</b>', $item->post_title );
				}

				$post = array(
					'label'    => $item->post_title,
					'title'    => $title,
					'id'       => $item->ID,
					'value'    => $item->post_title,
					'url'      => get_permalink( $item->ID ),
					'type'     => $item->post_type,
					'is_popup' => isset( $post_types_data[ $item->post_type ] ) && ! empty( $post_types_data[ $item->post_type ]['event_action'] ),
				);
				if ( $post['is_popup'] ) {
					$post['url']            = '#' . $post_types_data[ $item->post_type ]['name'] . ': ' . $title;
					$post['event_action']   = $post_types_data[ $item->post_type ]['event_action'];
					$post['post_type_name'] = $post_types_data[ $item->post_type ]['name'];
				}

				$posts [] = $post;
			}

			$posts = apply_filters( 'tcb_autocomplete_returned_posts', $posts, $s );

			wp_send_json( $posts );
		}

		/**
		 * Saves user template (code and picture)
		 *
		 * @return array
		 */
		public function action_save_user_template() {
			$existing_templates = get_option( 'tve_user_templates' );
			$template_name      = str_replace( '\\', '', $this->param( 'template_name' ) );
			$new_template       = array(
				'name'        => $template_name,
				'content'     => $this->param( 'template_content' ),
				'type'        => $this->param( 'template_type', '' ),
				'id_category' => $this->param( 'template_category' ),
				'css'         => $this->param( 'custom_css_rules' ),
				'media_css'   => json_decode( stripslashes( $this->param( 'media_rules' ) ), true ),
			);

			if ( $existing_templates && is_array( $existing_templates ) ) {
				foreach ( $existing_templates as $tpl ) {
					if ( is_array( $tpl ) && ! empty( $tpl['name'] ) && $tpl['name'] == $new_template['name'] ) {
						$this->error( __( 'That template name already exists, please use another name', 'thrive-cb' ) );
					}
				}
			}

			if ( isset( $_FILES['img_data'] ) ) {

				if ( ! function_exists( 'wp_handle_upload' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}

				add_filter( 'upload_dir', 'tve_filter_upload_user_template_location' );

				$moved_file = wp_handle_upload( $_FILES['img_data'], array(
					'action'                   => 'tcb_editor_ajax',
					'unique_filename_callback' => sanitize_file_name( $new_template['name'] . '.png' ),
				) );

				remove_filter( 'upload_dir', 'tve_filter_upload_user_template_location' );

				if ( empty( $moved_file['url'] ) ) {
					$this->error( __( 'Template could not be generated', 'thrive-cb' ) );
				}

				$new_template = array_merge( $new_template, array( 'image_url' => $moved_file['url'] ) );
			}

			$new_template          = apply_filters( 'tcb_hook_save_user_template', $new_template );
			$existing_templates [] = $new_template;

			update_option( 'tve_user_templates', $existing_templates );

			return array(
				'text'              => __( 'Template saved!', 'thrive-cb' ),
				'content_templates' => tcb_elements()->get( 'ct' )->get_list(),
			);
		}

		public function action_save_user_template_category() {
			$template_categories = get_option( 'tve_user_templates_categories' );

			$category_name = $this->param( 'category_name' );
			if ( empty( $category_name ) ) {
				$this->error( __( 'Invalid parameters!', 'thrive-cb' ) );
			}

			if ( ! is_array( $template_categories ) ) {
				$template_categories = array();
			}

			$last_category = end( $template_categories );
			if ( ! empty( $last_category ) ) {
				$index = $last_category['id'] + 1;
			} else {
				$index = 0;
			}

			$new_category          = array(
				'id'   => $index,
				'name' => $category_name,
			);
			$template_categories[] = $new_category;

			update_option( 'tve_user_templates_categories', $template_categories );

			$this->json( array( 'text' => __( 'Category saved!', 'thrive-cb' ), 'response' => $new_category ) );
		}

		/**
		 * process and display wp editor contents
		 * used in "Insert Shortcode" element
		 */
		public function action_render_shortcode() {
			if ( empty( $_POST['content'] ) ) {
				$this->error( __( 'The content is empty. Please input some content.', 'thrive-cb' ) );
			}

			$_POST['content']    = stripslashes( $_POST['content'] );
			$rendered_short_code = tcb_render_wp_shortcode( ( $_POST['content'] ) );
			$this->json( array(
				'text'     => __( 'Success! Your content was added.', 'thrive-cb' ),
				'response' => $rendered_short_code,
			) );
		}

		/**
		 * Ajax listener to save the post in database.  Handles "Save" and "Update" buttons together.
		 * If either button pressed, then write to saved field.
		 * If publish button pressed, then write to both save and published fields
		 *
		 * @return array
		 */
		public function action_save_post() {
			@ini_set( 'memory_limit', '512M' );

			if ( ! ( $post_id = $this->param( 'post_id' ) ) || ! current_user_can( 'edit_post', $post_id ) ) {
				return array(
					'success' => false,
					'message' => __( 'You do not have the required permission for this action', 'thrive-cb' ),
				);
			}
			$post_id  = (int) $post_id;
			$tcb_post = tcb_post( $post_id );

			do_action( 'tcb_ajax_save_post', $post_id );

			$landing_page_template = $this->param( 'tve_landing_page', 0 );

			$inline_rules     = $this->param( 'inline_rules' );
			$clippath_pattern = '/clip-path:(.+?);/';

			$inline_rules = preg_replace_callback( $clippath_pattern, array(
				$this,
				'replace_clip_path',
			), $inline_rules );

			$response = array(
				'success' => true,
			);

			if ( ( $custom_action = $this->param( 'custom_action' ) ) ) {
				switch ( $custom_action ) {
					case 'landing_page': //change or remove the landing page template for this post
						tcb_landing_page( $post_id )->change_template( $landing_page_template );
						break;
					case 'cloud_landing_page':
						$valid = tve_get_cloud_template_config( $landing_page_template );
						if ( $valid === false ) { /* this is not a valid cloud landing page template - most likely, some of the files were deleted */
							$current = tve_post_is_landing_page( $post_id );

							return array(
								'success'          => false,
								'current_template' => $current,
								'error'            => __( 'Some of the required files were not found. Please try re-downloading this template', 'thrive-cb' ),
								'message'          => __( 'Some of the required files were not found. Please try re-downloading this template', 'thrive-cb' ),
							);
						}
						/* if valid, go on with the regular change of template */
						tcb_landing_page( $post_id )->change_template( $landing_page_template );
						$response['message'] = __( 'All changes saved.', 'thrive-cb' );
						break;
					case 'landing_page_reset':
						/* clear the contents of the current landing page */
						if ( ! ( $landing_page_template = tve_post_is_landing_page( $post_id ) ) ) {
							break;
						}

						tcb_landing_page( $post_id, $landing_page_template )->reset();

						$response['message'] = __( 'All changes saved.', 'thrive-cb' );
						break;
					case 'landing_page_delete':
						$template_index = intval( $landing_page_template );
						$contents       = get_option( 'tve_saved_landing_pages_content' );
						$meta           = get_option( 'tve_saved_landing_pages_meta' );

						unset( $contents[ $template_index ], $meta[ $template_index ] );
						/* array_values - reorganize indexes */
						update_option( 'tve_saved_landing_pages_content', array_values( $contents ) );
						update_option( 'tve_saved_landing_pages_meta', array_values( $meta ) );
						break;
				}

				/** trigger also a post / page update for the caching plugins to know there has been a save */
				if ( ! empty( $content ) ) {
					wp_update_post( array(
						'ID'                => $post_id,
						'post_modified'     => current_time( 'mysql' ),
						'post_modified_gmt' => current_time( 'mysql' ),
						'post_title'        => get_the_title( $post_id ),
					) );
				}

				$response['revisions'] = tve_get_post_revisions( $post_id );

				return $response;
			}

			$key           = $landing_page_template ? ( '_' . $landing_page_template ) : '';
			$content       = $this->param( 'tve_content' );
			$content_split = tve_get_extended( $content );
			$content       = str_replace( array( '<!--tvemorestart-->', '<!--tvemoreend-->' ), '', $content );
			update_post_meta( $post_id, "tve_content_before_more{$key}", $content_split['main'] );
			update_post_meta( $post_id, "tve_content_more_found{$key}", $content_split['more_found'] );
			update_post_meta( $post_id, "tve_custom_css{$key}", $inline_rules );

			/* user defined Custom CSS rules here, had to use different key because tve_custom_css was already used */
			update_post_meta( $post_id, "tve_user_custom_css{$key}", $this->param( 'tve_custom_css' ) );
			tve_update_post_meta( $post_id, 'tve_page_events', $this->param( 'page_events', array() ) );

			if ( $this->param( 'update' ) == 'true' ) {
				update_post_meta( $post_id, "tve_updated_post{$key}", $content );
				/**
				 * If there is not WP content in the post, migrate it to TCB2-editor only mode
				 */
				$tcb_post->maybe_auto_migrate( false );
				$tcb_post->enable_editor();

				$tve_stripped_content = $this->param( 'tve_stripped_content' );
				$tve_stripped_content = str_replace( array(
					'<!--tvemorestart-->',
					'<!--tvemoreend-->',
				), '', $tve_stripped_content );
				$tcb_post->update_plain_text_content( $tve_stripped_content );
			}

			/* global options for a post that are not included in the editor */
			$tve_globals             = empty( $_POST['tve_globals'] ) ? array() : array_filter( $_POST['tve_globals'] );
			$tve_globals['font_cls'] = $this->param( 'custom_font_classes', array() );
			update_post_meta( $post_id, "tve_globals{$key}", $tve_globals );
			/* custom fonts used for this post */
			tve_update_post_custom_fonts( $post_id, $tve_globals['font_cls'] );

			if ( $landing_page_template ) {
				update_post_meta( $post_id, 'tve_landing_page', $this->param( 'tve_landing_page' ) );
				/* global Scripts for landing pages */
				update_post_meta( $post_id, 'tve_global_scripts', $this->param( 'tve_global_scripts', array() ) );
				if ( ! empty( $_POST['tve_landing_page_save'] ) ) {

					/* save the contents of the current landing page for later use */
					$template_content = array(
						'before_more'        => $content_split['main'],
						'more_found'         => $content_split['more_found'],
						'content'            => $content,
						'inline_css'         => $_POST['inline_rules'],
						'custom_css'         => $_POST['tve_custom_css'],
						'tve_globals'        => empty( $_POST['tve_globals'] ) ? array() : array_filter( $_POST['tve_globals'] ),
						'tve_global_scripts' => empty( $_POST['tve_global_scripts'] ) ? array() : $_POST['tve_global_scripts'],
					);
					$template_meta    = array(
						'name'             => $this->param( 'tve_landing_page_save' ),
						'tags'             => $this->param( 'template_tags' ),
						'template'         => $landing_page_template,
						'theme_dependency' => get_post_meta( $post_id, 'tve_disable_theme_dependency', true ),
						'set'              => get_post_meta( $post_id, 'tve_landing_set', true ),
						'date'             => date( 'Y-m-d' ),
					);
					/**
					 * if this is a cloud template, we need to store the thumbnail separately, as it has a different location
					 */
					$config = tve_get_cloud_template_config( $landing_page_template, false );
					if ( $config !== false && ! empty( $config['thumb'] ) ) {
						$template_meta['thumbnail'] = $config['thumb'];
					}
					if ( empty( $template_content['more_found'] ) ) { // save some space
						unset( $template_content['before_more'] ); // this is the same as the tve_save_post field
						unset( $template_content['more_found'] );
					}
					$templates_content = get_option( 'tve_saved_landing_pages_content' ); // this should get unserialized automatically
					$templates_meta    = get_option( 'tve_saved_landing_pages_meta' ); // this should get unserialized automatically
					if ( empty( $templates_content ) ) {
						$templates_content = array();
						$templates_meta    = array();
					}
					$templates_content [] = $template_content;
					$templates_meta []    = $template_meta;

					// make sure these are not autoloaded, as it is a potentially huge array
					add_option( 'tve_saved_landing_pages_content', null, '', 'no' );

					update_option( 'tve_saved_landing_pages_content', $templates_content );
					update_option( 'tve_saved_landing_pages_meta', $templates_meta );

					$response['saved_lp_templates'] = tve_landing_pages_load();
				}
			} else {
				delete_post_meta( $post_id, 'tve_landing_page' );
			}
			tve_update_post_meta( $post_id, 'thrive_icon_pack', empty( $_POST['has_icons'] ) ? 0 : 1 );
			tve_update_post_meta( $post_id, 'tve_has_masonry', empty( $_POST['tve_has_masonry'] ) ? 0 : 1 );
			tve_update_post_meta( $post_id, 'tve_has_typefocus', empty( $_POST['tve_has_typefocus'] ) ? 0 : 1 );
			tve_update_post_meta( $post_id, 'tve_has_wistia_popover', empty( $_POST['tve_has_wistia_popover'] ) ? 0 : 1 );

			/**
			 * trigger also a post / page update for the caching plugins to know there has been a save
			 * update post here so we can have access to its meta when a revision of it is saved
			 *
			 * @see tve_save_post_callback
			 */
			if ( ! empty( $content ) ) {
				if ( $landing_page_template ) {
					remove_all_filters( 'save_post' );
					add_action( 'save_post', 'tve_save_post_callback' );
				}
				wp_update_post( array(
					'ID'                => $post_id,
					'post_modified'     => current_time( 'mysql' ),
					'post_modified_gmt' => current_time( 'mysql' ),
					'post_title'        => get_the_title( $post_id ),
				) );
			}

			$response['revisions'] = tve_get_post_revisions( $post_id );

			return $response;

		}

		/**
		 * Redirects the save post to an external method
		 */
		public function action_save_post_external() {
			if ( ! $this->param( 'external_action' ) ) {
				$this->error( 'Invalid Request!' );
			}

			return apply_filters( 'tcb_ajax_' . $this->param( 'external_action' ), array(), $_REQUEST );
		}

		/**
		 * Update wp options
		 *
		 * @return int
		 */
		public function action_update_option() {
			$option_name  = $this->param( 'option_name' );
			$option_value = $this->param( 'option_value' );

			$allowed = array(
				'tve_display_save_notification',
				'tve_social_fb_app_id',
				'tve_comments_disqus_shortname',
				'tve_comments_facebook_admins',
				'tcb_pinned_elements',
			);
			if ( ! in_array( $option_name, $allowed ) ) {
				$this->error( 'Invalid', 403 );
			}

			if ( $option_name === 'tve_comments_facebook_admins' ) {
				$tve_comments_facebook_admins_arr = explode( ';', $option_value );
				$result                           = update_option( $option_name, $tve_comments_facebook_admins_arr );
			} elseif ( $option_name === 'tcb_pinned_elements' ) {
				$result = update_user_option( get_current_user_id(), $option_name, $option_value );
			} else {
				$result = update_option( $option_name, $option_value );
			}

			return (int) $result;
		}

		/**
		 * @return array
		 */
		public function action_get_api() {
			$api   = $this->param( 'api' );
			$force = $this->param( 'force' );
			$extra = $this->param( 'extra' );

			if ( ! $api || ! array_key_exists( $api, Thrive_Dash_List_Manager::available() ) ) {
				return array();
			}
			$connection = Thrive_Dash_List_Manager::connectionInstance( $api );

			return $connection->get_api_data( $extra, $force );
		}

		/**
		 * Get extra fields from api
		 *
		 * @return array
		 */
		public function action_get_api_extra() {
			$api    = $this->param( 'api' );
			$extra  = $this->param( 'extra' );
			$params = $this->param( 'params' );

			if ( ! $api || ! array_key_exists( $api, Thrive_Dash_List_Manager::available() ) ) {
				return array();
			}

			$connection = Thrive_Dash_List_Manager::connectionInstance( $api );

			return $connection->get_api_extra( $extra, $params );
		}

		public function action_custom_menu() {
			ob_start();
			include plugin_dir_path( dirname( __FILE__ ) ) . 'views/elements/menu-generated.php';
			$content = ob_get_contents();
			ob_end_clean();

			$this->json( array( 'response' => $content ) );
		}


		public function action_load_content_template() {
			/** @var TCB_Ct_Element $ct */
			$ct       = tcb_elements()->get( 'ct' );
			$template = $ct->load( (int) $this->param( 'template_key' ) );

			add_filter( 'tcb_is_editor_page_ajax', '__return_true' );

			$template['html_code'] = tve_do_wp_shortcodes( tve_thrive_shortcodes( stripslashes( $template['html_code'] ), true ), true );
			if ( ! empty( $template['media_css'][0] ) ) {
				$imports = explode( ';@import', $template['media_css'][0] );

				foreach ( $imports as $key => $import ) {
					if ( strpos( $import, '@import' ) === false ) {
						$import = '@import' . $import;
					}
					$template['imports'][ $key ] = $import;
				}
			}

			return $template;
		}

		public function action_delete_content_template() {
			/** @var TCB_Ct_Element $ct */
			$ct = tcb_elements()->get( 'ct' );

			return array(
				'list'    => $ct->delete( $this->param( 'key' ) ),
				'message' => __( 'Content template deleted', 'thrive-cb' ),
			);
		}

		/**
		 * Returns Current Post Revisions
		 */
		public function action_revisions() {
			$post_id = $this->param( 'post_id' );
			if ( empty( $post_id ) || ! is_numeric( $post_id ) ) {
				$this->error( __( 'Invalid Post Parameter', 'thrive-cb' ) );
			}

			$revisions = tve_get_post_revisions( $post_id );

			wp_send_json( $revisions );
		}

		/**
		 * Enables / Disables Theme CSS to Architect Page
		 */
		public function action_theme_css() {
			$post_id    = $this->param( 'post_id' );
			$disable_it = $this->param( 'disabled' );
			if ( empty( $post_id ) || ! is_numeric( $post_id ) ) {
				$this->error( __( 'Invalid Post Parameter', 'thrive-cb' ) );
			}

			update_post_meta( $post_id, 'tve_disable_theme_dependency', $disable_it ? 1 : 0 );

			$this->json( array() );
		}

		/**
		 * Crud Operations on global gradients
		 */
		public function action_global_gradients() {
			$name        = $this->param( 'name' );
			$gradient    = $this->param( 'gradient' );
			$id          = $this->param( 'id' );
			$active      = ! is_numeric( $this->param( 'active' ) ) ? 1 : 0;
			$custom_name = is_numeric( $this->param( 'custom_name' ) )
			               && in_array( $this->param( 'custom_name' ), array(
				0,
				1,
			) ) ? $this->param( 'custom_name' ) : 0;

			$max_name_characters          = 50;
			$global_gradients_option_name = 'thrv_global_gradients';

			if ( empty( $name ) || empty( $gradient ) || ! is_string( $gradient ) || ! is_string( $name ) ) {
				/**
				 * The color has to have a name and it must be a valid string
				 */
				$this->error( 'Invalid Parameters! A gradient must contain a name and a gradient string!' );
			}

			if ( strlen( $name ) > $max_name_characters ) {
				$this->error( 'Invalid color name! It must contain a maximum of ' . $max_name_characters . ' characters' );
			}

			$global_gradients = get_option( $global_gradients_option_name, array() );

			if ( ! is_array( $global_gradients ) ) {
				/**
				 * Security check: if the option is not empty and somehow the stored value is not an array, make it an array.
				 */
				$global_gradients = array();
			}

			if ( ! is_numeric( $id ) ) {
				/**
				 * ADD Action
				 */
				$gradient_id = count( $global_gradients );

				$global_gradients[] = array(
					'id'          => $gradient_id,
					'gradient'    => $gradient,
					'name'        => $name,
					'active'      => $active,
					'custom_name' => $custom_name,
				);

			} else {
				/**
				 *  Edit Gradient
				 */
				$index = - 1;

				foreach ( $global_gradients as $key => $global_g ) {
					if ( intval( $global_g['id'] ) === intval( $id ) ) {
						$index = $key;
						break;
					}
				}

				if ( $index > - 1 ) {
					$global_gradients[ $index ]['gradient'] = $gradient;
					$global_gradients[ $index ]['name']     = $name;
					$global_gradients[ $index ]['active']   = $active;

					if ( $custom_name ) {
						/**
						 * Update the custom name only if the value is 1
						 */
						$global_gradients[ $index ]['custom_name'] = $custom_name;
					}
				}
			}

			update_option( $global_gradients_option_name, $global_gradients );

			$this->json( $global_gradients );

		}

		/**
		 * CRUD Operations on global colors
		 */
		public function action_global_colors() {
			$name        = $this->param( 'name' );
			$color       = $this->param( 'color' );
			$id          = $this->param( 'id' );
			$active      = ! is_numeric( $this->param( 'active' ) ) ? 1 : 0;
			$custom_name = is_numeric( $this->param( 'custom_name' ) )
			               && in_array( $this->param( 'custom_name' ), array(
				0,
				1,
			) ) ? $this->param( 'custom_name' ) : 0;

			$max_name_characters       = 50;
			$global_colors_option_name = 'thrv_global_colours';

			if ( empty( $name ) || empty( $color ) || ! is_string( $color ) || ! is_string( $name ) ) {
				/**
				 * The color has to have a name and it must be a valid string
				 */
				$this->error( __( 'Invalid Parameters! A color must contain a name and a color string!', 'thrive-cb' ) );
			}

			if ( substr( $color, 0, 3 ) !== 'rgb' ) {
				/**
				 * The color must be a valid RGB string
				 */
				$this->error( 'Invalid color format! It must be a valid RGB string!' );
			}

			if ( strlen( $name ) > $max_name_characters ) {
				$this->error( 'Invalid color name! It must contain a maximum of ' . $max_name_characters . ' characters' );
			}

			$global_colors = get_option( $global_colors_option_name, array() );
			if ( ! is_array( $global_colors ) ) {
				/**
				 * Security check: if the option is not empty and somehow the stored value is not an array, make it an array.
				 */
				$global_colors = array();
			}

			if ( ! is_numeric( $id ) ) {
				/**
				 * ADD Action
				 */
				$color_id = count( $global_colors );

				$global_colors[] = array(
					'id'          => $color_id,
					'color'       => $color,
					'name'        => $name,
					'active'      => $active,
					'custom_name' => $custom_name,
				);

			} else {
				/**
				 *  Edit Color
				 */
				$index = - 1;

				foreach ( $global_colors as $key => $global_c ) {
					if ( intval( $global_c['id'] ) === intval( $id ) ) {
						$index = $key;
						break;
					}
				}

				if ( $index > - 1 ) {
					$global_colors[ $index ]['color']  = $color;
					$global_colors[ $index ]['name']   = $name;
					$global_colors[ $index ]['active'] = $active;

					if ( $custom_name ) {
						/**
						 * Update the custom name only if the value is 1
						 */
						$global_colors[ $index ]['custom_name'] = $custom_name;
					}
				}
			}

			update_option( $global_colors_option_name, $global_colors );

			$this->json( $global_colors );
		}

		/**
		 * Update Template Variables
		 */
		public function action_template_options() {
			$name        = $this->param( 'name' );
			$type        = $this->param( 'type', '' );
			$value       = $this->param( 'value', '' );
			$id          = $this->param( 'id' );
			$custom_name = is_numeric( $this->param( 'custom_name' ) )
			               && in_array( $this->param( 'custom_name' ), array( 0, 1 ) ) ? $this->param( 'custom_name' ) : 0;

			if ( ! in_array( $type, array( 'color', 'gradient' ) ) ) {
				$this->error( 'Invalid type' );
			}

			if ( empty( $name ) || empty( $value ) || ! is_string( $value ) || ! is_string( $name ) || ! is_numeric( $id ) ) {
				/**
				 * The Gradient has to have a name and it must be a valid string
				 */
				$this->error( 'Invalid Parameters! A color must contain a name, an id and a color string!' );
			}

			$post_id = intval( $this->param( 'post_id', 0 ) );

			if ( empty( $post_id ) ) {
				$this->error( 'Something went wrong! Please contact the support team!' );
			}

			if ( tve_post_is_landing_page( $post_id ) ) {

				tcb_landing_page( $post_id )->update_template_css_variable( $id, array(
					'key'         => $type,
					'value'       => $value,
					'name'        => $name,
					'custom_name' => $custom_name,
				) );
			}
		}

		/**
		 * Function used to update custom options
		 *
		 * Used for updating the custom colors (Favorites Colors)
		 * Used for updating the custom gradients (Favorites Gradients)
		 */
		public function action_custom_options() {
			$type   = $this->param( 'type', '' );
			$values = $this->param( 'values', array() );

			if ( ! in_array( $type, array( 'colours', 'gradients' ) ) ) {
				$this->error( 'Invalid type' );
			}

			update_option( 'thrv_custom_' . $type, $values );
		}

		/**
		 * CRUD Operations on Global Styles
		 */
		public function action_global_styles() {
			$name       = $this->param( 'name' );
			$type       = $this->param( 'type' );
			$identifier = $this->param( 'identifier' );
			$css        = $this->param( 'css' );
			$fonts      = $this->param( 'fonts', array() );
			$dom        = $this->param( 'dom' );
			$active     = $this->param( 'active' );
			$ignore_css = $this->param( 'ignore_css', false );

			if ( empty( $identifier ) ) {
				$this->error( 'A shared style must contain an identifier!' );
			}


			if ( ! in_array( $type, array( 'button', 'section', 'contentbox' ) ) ) {
				$this->error( 'Invalid Type!' );
			}

			if ( strpos( $identifier, 'set_' ) !== false ) {

				$post_id = intval( $this->param( 'post_id' ) );

				if ( tve_post_is_landing_page( $post_id ) ) {
					tcb_landing_page( $post_id )->update_template_style( $identifier, $type, $name, $css, $fonts, $ignore_css );
				}

				return;
			}

			$global_style_option_name = 'tve_global_' . $type . '_styles';

			if ( ! in_array( $global_style_option_name, array( 'tve_global_button_styles', 'tve_global_section_styles', 'tve_global_contentbox_styles' ) ) ) {
				/**
				 * Aditional check!!
				 */
				$this->error( 'Aditional check. Option Name Fail!' );
			}
			$global_styles = get_option( $global_style_option_name, array() );

			if ( ! is_array( $global_styles ) ) {
				/**
				 * Security check: if the option is not empty and somehow the stored value is not an array, make it an array.
				 */
				$global_styles = array();
			}

			if ( empty( $global_styles[ $identifier ] ) ) {

				if ( empty( $dom['attr'] ) ) {
					$dom['attr'] = array();
				}

				/**
				 * Add New Global Style
				 */
				$global_styles[ $identifier ] = array(
					'name'  => $name,
					'css'   => $css,
					'dom'   => $dom,
					'fonts' => $fonts,
				);
			} else {
				/**
				 * Edit Global Style
				 */
				if ( false === $ignore_css ) {
					$global_styles[ $identifier ]['css']   = $css;
					$global_styles[ $identifier ]['fonts'] = $fonts;
				}
				if ( $name ) {
					$global_styles[ $identifier ]['name'] = $name;
				}

				if ( is_numeric( $active ) && 0 === intval( $active ) ) {
					unset( $global_styles[ $identifier ] );
				}
			}

			update_option( $global_style_option_name, $global_styles );

			$this->json( $global_styles );
		}

		/**
		 * Generate post Grid Ajax Call
		 */
		public function action_post_grid() {
			require_once plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'inc/classes/class-tcb-post-grid.php';
			$post_grid = new TCB_Post_Grid( $_POST );
			$html      = $post_grid->render();

			$this->json( array( 'html' => $html ) );
		}

		/**
		 * Ajax that returns the categories for post grid elements that begins with a certain string
		 */
		public function action_post_grid_categories() {
			$search_term = isset( $_POST['term'] ) ? $_POST['term'] : '';

			require_once plugin_dir_path( __FILE__ ) . '/class-tcb-element-abstract.php';
			require_once plugin_dir_path( __FILE__ ) . 'elements/class-tcb-postgrid-element.php';

			$response = TCB_Postgrid_Element::get_categories( $search_term );

			wp_send_json( $response );
		}

		/**
		 * Ajax that returns the tags for post grid elements that begins with a certain string
		 */
		public function action_post_grid_tags() {
			$search_term = isset( $_POST['term'] ) ? $_POST['term'] : '';

			require_once plugin_dir_path( __FILE__ ) . '/class-tcb-element-abstract.php';
			require_once plugin_dir_path( __FILE__ ) . 'elements/class-tcb-postgrid-element.php';

			$response = TCB_Postgrid_Element::get_tags( $search_term );

			wp_send_json( $response );
		}

		/**
		 * Ajax that returns the tags for post grid elements that begins with a certain string
		 */
		public function action_post_grid_custom_taxonomies() {
			$search_term = isset( $_POST['term'] ) ? $_POST['term'] : '';

			require_once plugin_dir_path( __FILE__ ) . '/class-tcb-element-abstract.php';
			require_once plugin_dir_path( __FILE__ ) . 'elements/class-tcb-postgrid-element.php';

			$response = TCB_Postgrid_Element::get_custom_taxonomies( $search_term );

			wp_send_json( $response );
		}

		/**
		 *  Ajax that returns the users for post grid elements that begins with a certain string
		 */
		public function action_post_grid_users() {
			$search_term = isset( $_POST['term'] ) ? $_POST['term'] : '';

			require_once plugin_dir_path( __FILE__ ) . '/class-tcb-element-abstract.php';
			require_once plugin_dir_path( __FILE__ ) . 'elements/class-tcb-postgrid-element.php';

			$response = TCB_Postgrid_Element::get_authors( $search_term );

			wp_send_json( $response );
		}

		/**
		 *  Ajax that returns the individual posts or pages for post grid elements that begins with a certain string
		 */
		public function action_post_grid_individual_post_pages() {
			$search_term = isset( $_POST['term'] ) ? $_POST['term'] : '';

			require_once plugin_dir_path( __FILE__ ) . '/class-tcb-element-abstract.php';
			require_once plugin_dir_path( __FILE__ ) . 'elements/class-tcb-postgrid-element.php';

			$response = TCB_Postgrid_Element::get_posts_list( $search_term );

			wp_send_json( $response );
		}

		/**
		 * Creates a new Thrive Lightbox
		 *
		 * @return array
		 */
		public function action_create_lightbox() {
			$post_id = $this->param( 'post_id' );
			if ( ! $post_id ) {
				return array();
			}

			$landing_page_template = tve_post_is_landing_page( $post_id );
			$lightbox_title        = $this->param( 'title' );

			if ( $landing_page_template ) {
				$tcb_landing_page = tcb_landing_page( $post_id, $landing_page_template );
				$lightbox_id      = $tcb_landing_page->new_lightbox( $lightbox_title );
			} else {
				$lightbox_id = TCB_Lightbox::create( $lightbox_title, '', array(), array() );
			}

			return array(
				'lightbox' => array(
					'id'       => $lightbox_id,
					'title'    => $lightbox_title,
					'edit_url' => tcb_get_editor_url( $lightbox_id ),
				),
				'message'  => __( 'Lightbox created', 'thrive-cb' ),
			);
		}

		/**
		 * Includes and returns the font-awesome.svg file from /editor/css/fonts
		 *
		 * @return string svg icons available for users
		 */
		public function action_font_awesome_svg() {

			ob_start();
			include( TVE_TCB_ROOT_PATH . 'editor/css/fonts/font-awesome.svg' );
			$content = ob_get_contents();
			ob_end_clean();

			return $content;
		}

		/**
		 * Fetches a list of Cloud templates for an element
		 *
		 * @return array
		 */
		public function action_cloud_content_templates() {
			if ( ! ( $type = $this->param( 'type' ) ) ) {
				$this->error( __( 'Invalid element type', 'thrive-cb' ), 500 );
			}

			/** @var TCB_Cloud_Template_Element_Abstract $element */
			if ( ! ( $element = tcb_elements()->element_factory( $type ) ) || ! is_a( $element, 'TCB_Cloud_Template_Element_Abstract' ) ) {
				$this->error( __( 'Invalid element type', 'thrive-cb' ) . " ({$type})", 500 );
			}

			$templates = $element->get_cloud_templates();

			if ( is_wp_error( $templates ) ) {
				$this->error( $templates );
			}

			return array(
				'success'   => true,
				'templates' => $templates,
			);
		}

		/**
		 * Downloads a template from the cloud ( or fetches a template stored local )
		 *
		 * @return array
		 */
		public function action_cloud_content_template_download() {
			if ( ! ( $type = $this->param( 'type' ) ) ) {
				$this->error( __( 'Invalid element type', 'thrive-cb' ), 500 );
			}

			if ( ! ( $id = $this->param( 'id' ) ) ) {
				$this->error( __( 'Missing template id', 'thrive-cb' ) . " ({$type})", 500 );
			}

			/** @var TCB_Cloud_Template_Element_Abstract $element */
			if ( ! ( $element = tcb_elements()->element_factory( $type ) ) || ! is_a( $element, 'TCB_Cloud_Template_Element_Abstract' ) ) {
				$this->error( __( 'Invalid element type', 'thrive-cb' ) . " ({$type})", 500 );
			}

			$data = $element->get_cloud_template_data( $id );

			if ( is_wp_error( $data ) ) {
				$this->error( $data );
			}

			return array(
				'success' => true,
				'data'    => $data,
			);
		}

		/**
		 * Callback for preg_replace
		 * Adds vendor prefix for clip-path for safari
		 */
		public function replace_clip_path( $matches ) {
			return $matches[0] . ' -webkit-clip-path:' . $matches[1] . '; ';
		}

		/**
		 * Return all symbols
		 */
		public function action_get_symbols() {

			$element_type = ( $this->param( 'type' ) ) ? $this->param( 'type' ) : 'symbol';

			/** @var TCB_Symbol_Element $element */
			if ( ! ( $element = tcb_elements()->element_factory( $element_type ) ) || ! is_a( $element, 'TCB_Element_Abstract' ) ) {
				$this->error( __( 'Invalid element type', 'thrive-cb' ), 500 );
			}

			$args = ( $this->param( 'args' ) ) ? $this->param( 'args' ) : array();

			$symbols = $element->get_all( $args );

			if ( is_wp_error( $symbols ) ) {
				$this->error( __( 'Error when retrieving symbols', 'thrive-cb' ), 500 );
			}

			return array(
				'success' => true,
				'symbols' => $symbols,
			);
		}

		/**
		 * Save symbol when it gets edited from TAR
		 *
		 * @return array
		 */
		public function action_save_symbol() {

			/** @var TCB_Symbol_Element $element */
			if ( ! ( $element = tcb_elements()->element_factory( 'symbol' ) ) || ! is_a( $element, 'TCB_Element_Abstract' ) ) {
				$this->error( __( 'Invalid element type', 'thrive-cb' ), 500 );
			}

			$symbol_data = array(
				'id'          => $this->param( 'id' ),
				'thumb_path'  => $this->param( 'thumb_path' ),
				'content'     => $this->param( 'symbol_content' ),
				'css'         => $this->param( 'symbol_css' ),
				'term_id'     => $this->param( 'tcb_symbols_tax' ),
				'tve_globals' => $this->param( 'tve_globals' ),
			);

			if ( ! ( $id = $this->param( 'id' ) ) ) {
				//if we don't have an id => we are creating a symbol, which needs to have a title
				if ( ! ( $title = $this->param( 'symbol_title' ) ) ) {
					$this->error( __( 'Missing symbol title', 'thrive-cb' ), 500 );
				}

				$symbol_data['symbol_title'] = $title;
				$data                        = $element->create_symbol( $symbol_data );
			} else {
				$data = $element->edit_symbol( $symbol_data );
			}

			if ( is_wp_error( $data ) ) {
				$this->error( $data );
			}

			do_action( 'tcb_after_symbol_save', array_merge( $_POST, $data ) );

			return array(
				'success' => true,
				'data'    => $data,
			);
		}

		/**
		 * When elements have extra css we need to do an extra save after we process the css for the symbol.
		 * i.e call to action element
		 */
		public function action_save_symbol_extra_css() {

			if ( ! ( $id = $this->param( 'id' ) ) ) {
				$this->error( __( 'Missing symbol id', 'thrive-cb' ), 500 );
			}

			/** @var TCB_Symbol_Element $element */
			if ( ! ( $element = tcb_elements()->element_factory( 'symbol' ) ) || ! is_a( $element, 'TCB_Element_Abstract' ) ) {
				$this->error( __( 'Invalid element type', 'thrive-cb' ), 500 );
			}

			$symbol_data = array(
				'id'  => $id,
				'css' => $this->param( 'css' ),
			);

			/**
			 * Save updated css with the proper selectors after a symbol was created
			 */
			$response = $element->save_extra_css( $symbol_data );

			if ( is_wp_error( $response ) ) {
				$this->error( $response );
			}

			return array(
				'success' => true,
				'data'    => $response,
			);

		}

		/**
		 * Save the file resulted from the content of an html elemenet
		 *
		 * @return array
		 */
		public function action_save_content_thumb() {

			if ( ! isset( $_FILES['preview_file'] ) ) {
				$this->error( __( 'Missing preview file', 'thrive-cb' ), 500 );
			}

			/** @var TCB_Symbol_Element $element */
			if ( ! ( $element = tcb_elements()->element_factory( 'symbol' ) ) || ! is_a( $element, 'TCB_Element_Abstract' ) ) {
				$this->error( __( 'Invalid element type', 'thrive-cb' ), 500 );
			}

			$data = $element->generate_preview( $this->param( 'post_id' ) );

			if ( is_wp_error( $data ) ) {
				$this->error( $data );
			}

			return array(
				'success' => true,
				'data'    => $data,
			);

		}

		/**
		 * Render widget for editor frame
		 *
		 * @return array
		 */
		public function action_widget_render() {
			global $wp_widget_factory;

			$widget_data = $this->param( 'data' );
			$widget_type = $this->param( 'widget' );

			$content     = '';
			$widget_data = array_map( 'wp_unslash', $widget_data );

			foreach ( $wp_widget_factory->widgets as $widget ) {
				if ( $widget->option_name === $widget_type ) {

					ob_start();

					$widget->widget( tve_get_sidebar_default_args( $widget ), $widget_data );

					$content = ob_get_contents();

					ob_end_clean();
				}
			}

			$content .= sprintf( '<div class="widget-config" style="display: none;">__CONFIG_thrive_widget__%s__CONFIG_thrive_widget__</div>',
				json_encode( array_merge( $widget_data, array( 'type' => $widget_type ) ) )
			);

			return array(
				'success' => true,
				'content' => $content,
			);
		}
	}
}
global $tcb_ajax_handler;
$tcb_ajax_handler = new TCB_Editor_Ajax();

/**
 * If ajax call, register the handler
 */
if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	$tcb_ajax_handler->init();
} else {
	/* in other cases, generate nonce and assign it */
	add_filter( 'tcb_main_frame_localize', array( $tcb_ajax_handler, 'localize' ) );
}

