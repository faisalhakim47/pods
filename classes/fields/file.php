<?php

/**
 * @package Pods\Fields
 */
class PodsField_File extends PodsField {

	/**
	 * Field Type Group
	 *
	 * @var string
	 * @since 2.0
	 */
	public static $group = 'Relationships / Media';

	/**
	 * Field Type Identifier
	 *
	 * @var string
	 * @since 2.0
	 */
	public static $type = 'file';

	/**
	 * Field Type Label
	 *
	 * @var string
	 * @since 2.0
	 */
	public static $label = 'File / Image / Video';

	/**
	 * API caching for fields that need it during validate/save
	 *
	 * @var \PodsAPI
	 * @since 2.3
	 */
	protected static $api = false;

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {

		self::$label = __( 'File / Image / Video', 'pods' );

	}

	/**
	 * Add admin_init actions.
	 *
	 * @since 2.3
	 */
	public function admin_init() {

		// Hook into AJAX for Uploads.
		add_action( 'wp_ajax_pods_upload', array( $this, 'admin_ajax_upload' ) );
		add_action( 'wp_ajax_nopriv_pods_upload', array( $this, 'admin_ajax_upload' ) );

	}

	/**
	 * {@inheritdoc}
	 */
	public function options() {

		$sizes = get_intermediate_image_sizes();

		$image_sizes = array();

		foreach ( $sizes as $size ) {
			$image_sizes[ $size ] = ucwords( str_replace( '-', ' ', $size ) );
		}

		$options = array(
			self::$type . '_format_type'            => array(
				'label'      => __( 'Upload Limit', 'pods' ),
				'default'    => 'single',
				'type'       => 'pick',
				'data'       => array(
					'single' => __( 'Single File', 'pods' ),
					'multi'  => __( 'Multiple Files', 'pods' ),
				),
				'dependency' => true,
			),
			self::$type . '_uploader'               => array(
				'label'      => __( 'File Uploader', 'pods' ),
				'default'    => 'attachment',
				'type'       => 'pick',
				'data'       => apply_filters( 'pods_form_ui_field_' . self::$type . '_uploader_options', array(
						'attachment' => __( 'Upload and/or Select (Media Library)', 'pods' ),
						'plupload'   => __( 'Upload only (Plupload)', 'pods' ),
					)
				),
				'dependency' => true,
			),
			self::$type . '_attachment_tab'         => array(
				'label'      => __( 'Attachments Default Tab', 'pods' ),
				'depends-on' => array( self::$type . '_uploader' => 'attachment' ),
				'default'    => 'upload',
				'type'       => 'pick',
				'data'       => array(
					// These keys must match WP media modal router names.
					'upload' => __( 'Upload File', 'pods' ),
					'browse' => __( 'Media Library', 'pods' ),
				),
			),
			self::$type . '_edit_title'             => array(
				'label'   => __( 'Editable Title', 'pods' ),
				'default' => 1,
				'type'    => 'boolean',
			),
			self::$type . '_show_edit_link'         => array(
				'label'   => __( 'Show Edit Link', 'pods' ),
				'default' => 0,
				'type'    => 'boolean',
			),
			self::$type . '_linked'                 => array(
				'label'   => __( 'Show Download Link', 'pods' ),
				'default' => 0,
				'type'    => 'boolean',
			),
			self::$type . '_limit'                  => array(
				'label'      => __( 'Max Number of Files', 'pods' ),
				'depends-on' => array( self::$type . '_format_type' => 'multi' ),
				'default'    => 0,
				'type'       => 'number',
			),
			self::$type . '_restrict_filesize'      => array(
				'label'      => __( 'Restrict File Size', 'pods' ),
				'depends-on' => array( self::$type . '_uploader' => 'plupload' ),
				'default'    => '10MB',
				'type'       => 'text',
			),
			self::$type . '_type'                   => array(
				'label'      => __( 'Restrict File Types', 'pods' ),
				'default'    => apply_filters( 'pods_form_ui_field_' . self::$type . '_type_default', 'images' ),
				'type'       => 'pick',
				'data'       => apply_filters( 'pods_form_ui_field_' . self::$type . '_type_options', array(
						'images' => __( 'Images (jpg, jpeg, png, gif)', 'pods' ),
						'video'  => __( 'Video (mpg, mov, flv, mp4, etc..)', 'pods' ),
						'audio'  => __( 'Audio (mp3, m4a, wav, wma, etc..)', 'pods' ),
						'text'   => __( 'Text (txt, csv, tsv, rtx, etc..)', 'pods' ),
						'any'    => __( 'Any Type (no restriction)', 'pods' ),
						'other'  => __( 'Other (customize allowed extensions)', 'pods' ),
					)
				),
				'dependency' => true,
			),
			self::$type . '_allowed_extensions'     => array(
				'label'       => __( 'Allowed File Extensions', 'pods' ),
				'description' => __( 'Separate file extensions with a comma (ex. jpg,png,mp4,mov)', 'pods' ),
				'depends-on'  => array( self::$type . '_type' => 'other' ),
				'default'     => apply_filters( 'pods_form_ui_field_' . self::$type . '_extensions_default', '' ),
				'type'        => 'text',
			),
			self::$type . '_field_template'         => array(
				'label'      => __( 'List Style', 'pods' ),
				'help'       => __( 'You can choose which style you would like the files to appear within the form.', 'pods' ),
				'depends-on' => array( self::$type . '_type' => 'images' ),
				'default'    => apply_filters( 'pods_form_ui_field_' . self::$type . '_template_default', 'rows' ),
				'type'       => 'pick',
				'data'       => apply_filters( 'pods_form_ui_field_' . self::$type . '_type_templates', array(
						'rows'  => __( 'Rows', 'pods' ),
						'tiles' => __( 'Tiles', 'pods' ),
					)
				),
			),
			/*
            self::$type . '_image_size' => array(
                'label' => __( 'Excluded Image Sizes', 'pods' ),
                'description' => __( 'Image sizes not to generate when processing the image', 'pods' ),
                'depends-on' => array( self::$type . '_type' => 'images' ),
                'default' => 'images',
                'type' => 'pick',
                'pick_format_type' => 'multi',
                'pick_format_multi' => 'checkbox',
                'data' => apply_filters(
                    'pods_form_ui_field_' . self::$type . '_image_size_options',
                    $image_sizes
                )
            ),
			*/
			self::$type . '_add_button'             => array(
				'label'   => __( 'Add Button Text', 'pods' ),
				'default' => __( 'Add File', 'pods' ),
				'type'    => 'text',
			),
			self::$type . '_modal_title'            => array(
				'label'      => __( 'Modal Title', 'pods' ),
				'depends-on' => array( self::$type . '_uploader' => 'attachment' ),
				'default'    => __( 'Attach a file', 'pods' ),
				'type'       => 'text',
			),
			self::$type . '_modal_add_button'       => array(
				'label'      => __( 'Modal Add Button Text', 'pods' ),
				'depends-on' => array( self::$type . '_uploader' => 'attachment' ),
				'default'    => __( 'Add File', 'pods' ),
				'type'       => 'text',
			),

			/* WP GALLERY OUTPUT */
			self::$type . '_wp_gallery_output'      => array(
				'label'      => __( 'Output as a WP Gallery', 'pods' ),
				'help'       => sprintf( __( '<a href="%s" target="_blank">Click here for more info</a>', 'pods' ), 'https://codex.wordpress.org/The_WordPress_Gallery' ),
				'depends-on' => array( self::$type . '_type' => 'images' ),
				'dependency' => true,
				'type'       => 'boolean',
			),
			self::$type . '_wp_gallery_link'        => array(
				'label'      => __( 'Gallery image links', 'pods' ),
				'depends-on' => array( self::$type . '_wp_gallery_output' => 1 ),
				'type'       => 'pick',
				'data'       => array(
					'post' => __( 'Attachment Page', 'pods' ),
					'file' => __( 'Media File', 'pods' ),
					'none' => __( 'None', 'pods' ),
				),
			),
			self::$type . '_wp_gallery_columns'     => array(
				'label'      => __( 'Gallery image columns', 'pods' ),
				'depends-on' => array( self::$type . '_wp_gallery_output' => 1 ),
				'type'       => 'pick',
				'data'       => array(
					'1' => 1,
					'2' => 2,
					'3' => 3,
					'4' => 4,
					'5' => 5,
					'6' => 6,
					'7' => 7,
					'8' => 8,
					'9' => 9,
				),
			),
			self::$type . '_wp_gallery_random_sort' => array(
				'label'      => __( 'Gallery randomized order', 'pods' ),
				'depends-on' => array( self::$type . '_wp_gallery_output' => 1 ),
				'type'       => 'boolean',
			),
			self::$type . '_wp_gallery_size'        => array(
				'label'      => __( 'Gallery image size', 'pods' ),
				'depends-on' => array( self::$type . '_wp_gallery_output' => 1 ),
				'type'       => 'pick',
				'data'       => $this->data_image_sizes(),
			),
		);

		return $options;

	}

	/**
	 * {@inheritdoc}
	 */
	public function schema( $options = null ) {

		$schema = false;

		return $schema;

	}

	/**
	 * {@inheritdoc}
	 */
	public function display( $value = null, $name = null, $options = null, $pod = null, $id = null ) {

		if ( 1 === (int) pods_v( self::$type . '_wp_gallery_output', $options, 0 ) ) {
			return $this->do_wp_gallery( $value, $options );
		}

		if ( is_array( $value ) && ! empty( $value ) ) {
			if ( isset( $value['ID'] ) ) {
				$value = wp_get_attachment_url( $value['ID'] );
			} else {
				$attachments = $value;
				$value       = array();

				foreach ( $attachments as $v ) {
					if ( ! is_array( $v ) ) {
						$value[] = $v;
					} elseif ( isset( $v['ID'] ) ) {
						$value[] = wp_get_attachment_url( $v['ID'] );
					}
				}

				$value = implode( ' ', $value );
			}
		}

		return $value;

	}

	/**
	 * {@inheritdoc}
	 */
	public function input( $name, $value = null, $options = null, $pod = null, $id = null ) {

		$options         = (array) $options;
		$form_field_type = PodsForm::$field_type;

		if ( ! is_admin() ) {
			include_once( ABSPATH . '/wp-admin/includes/template.php' );

			if ( is_multisite() ) {
				include_once( ABSPATH . '/wp-admin/includes/ms.php' );
			}
		}

		if ( ( ( defined( 'PODS_DISABLE_FILE_UPLOAD' ) && true === PODS_DISABLE_FILE_UPLOAD ) || ( defined( 'PODS_UPLOAD_REQUIRE_LOGIN' ) && is_bool( PODS_UPLOAD_REQUIRE_LOGIN ) && true === PODS_UPLOAD_REQUIRE_LOGIN && ! is_user_logged_in() ) || ( defined( 'PODS_UPLOAD_REQUIRE_LOGIN' ) && ! is_bool( PODS_UPLOAD_REQUIRE_LOGIN ) && ( ! is_user_logged_in() || ! current_user_can( PODS_UPLOAD_REQUIRE_LOGIN ) ) ) ) && ( ( defined( 'PODS_DISABLE_FILE_BROWSER' ) && true === PODS_DISABLE_FILE_BROWSER ) || ( defined( 'PODS_FILES_REQUIRE_LOGIN' ) && is_bool( PODS_FILES_REQUIRE_LOGIN ) && true === PODS_FILES_REQUIRE_LOGIN && ! is_user_logged_in() ) || ( defined( 'PODS_FILES_REQUIRE_LOGIN' ) && ! is_bool( PODS_FILES_REQUIRE_LOGIN ) && ( ! is_user_logged_in() || ! current_user_can( PODS_FILES_REQUIRE_LOGIN ) ) ) ) ) {
			?>
			<p>You do not have access to upload / browse files. Contact your website admin to resolve.</p>
			<?php
			return;
		}

		// @todo test frontend media modal
		if ( ! is_admin() || ! is_user_logged_in() || ( ! current_user_can( 'upload_files' ) && ! current_user_can( 'edit_files' ) ) ) {
			$options[ self::$type . '_uploader' ] = 'plupload';
		}

		$args = compact( array_keys( get_defined_vars() ) );

		PodsForm::field_method( $field_type, 'render_input_script', $args );

		return;

		// @todo: we're short-circuiting for prototyping above. The actions below will need to be woven in somehow.

		// Use plupload if attachment isn't available
		if ( 'attachment' === pods_v( self::$type . '_uploader', $options ) && ( ! is_user_logged_in() || ( ! current_user_can( 'upload_files' ) && ! current_user_can( 'edit_files' ) ) ) ) {
			$field_type = 'plupload';
		} elseif ( 'plupload' === pods_v( self::$type . '_uploader', $options ) ) {
			$field_type = 'plupload';
		} elseif ( 'attachment' === pods_v( self::$type . '_uploader', $options ) ) {
			if ( ! pods_version_check( 'wp', '3.5' ) || ! is_admin() ) // @todo test frontend media modal
			{
				$field_type = 'attachment';
			} else {
				$field_type = 'media';
			}
		} else {
			// Support custom File Uploader integration
			do_action( 'pods_form_ui_field_' . self::$type . '_uploader_' . pods_v( self::$type . '_uploader', $options ), $name, $value, $options, $pod, $id );
			do_action( 'pods_form_ui_field_' . self::$type . '_uploader', pods_v( self::$type . '_uploader', $options ), $name, $value, $options, $pod, $id );

			return;
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function regex( $value = null, $name = null, $options = null, $pod = null, $id = null ) {

		return false;

	}

	/**
	 * {@inheritdoc}
	 */
	public function validate( $value, $name = null, $options = null, $fields = null, $pod = null, $id = null, $params = null ) {

		// @todo Check file size
		// @todo Check file extensions

		return true;

	}

	/**
	 * {@inheritdoc}
	 */
	public function pre_save( $value, $id = null, $name = null, $options = null, $fields = null, $pod = null, $params = null ) {

		return $value;

	}

	/**
	 * {@inheritdoc}
	 */
	public function save( $value, $id = null, $name = null, $options = null, $fields = null, $pod = null, $params = null ) {

		if ( empty( self::$api ) ) {
			self::$api = pods_api();
		}

		// Handle File title saving.
		foreach ( $value as $id ) {
			$title = false;

			if ( is_array( $id ) ) {
				if ( isset( $id['title'] ) && 0 < strlen( trim( $id['title'] ) ) ) {
					$title = trim( $id['title'] );
				}

				if ( isset( $id['id'] ) ) {
					$id = (int) $id['id'];
				} else {
					$id = 0;
				}
			}

			if ( empty( $id ) ) {
				continue;
			}

			$attachment_data = array();

			// Update the title if set.
			if ( false !== $title && 1 === (int) pods_v( self::$type . '_edit_title', $options, 0 ) ) {
				$attachment_data['post_title'] = $title;
			}

			// Update attachment parent if it's not set yet and we're updating a post.
			if ( ! empty( $params->id ) && ! empty( $pod['type'] ) && 'post_type' === $pod['type'] ) {
				$attachment = get_post( $id );

				if ( isset( $attachment->post_parent ) && 0 === (int) $attachment->post_parent ) {
					$attachment_data['post_parent'] = (int) $params->id;
				}
			}

			// Update the attachment if it the data array is not still empty.
			if ( ! empty( $attachment_data ) ) {
				$attachment_data['ID'] = $id;

				self::$api->save_wp_object( 'media', $attachment_data );
			}
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function ui( $id, $value, $name = null, $options = null, $fields = null, $pod = null ) {

		if ( empty( $value ) ) {
			return;
		}

		if ( ! empty( $value ) && isset( $value['ID'] ) ) {
			$value = array( $value );
		}

		$image_size = apply_filters( 'pods_form_ui_field_' . self::$type . '_ui_image_size', 'thumbnail', $id, $value, $name, $options, $pod );

		return $this->images( $id, $value, $name, $options, $pod, $image_size );

	}

	/**
	 * Return image(s) markup
	 *
	 * @param int    $id
	 * @param mixed  $value
	 * @param string $name
	 * @param array  $options
	 * @param array  $pod
	 * @param string $image_size
	 *
	 * @return string
	 * @since 2.3
	 */
	public function images( $id, $value, $name = null, $options = null, $pod = null, $image_size = null ) {

		$images = '';

		if ( empty( $value ) || ! is_array( $value ) ) {
			return $images;
		}

		foreach ( $value as $v ) {
			$images .= pods_image( $v, $image_size );
		}

		return $images;

	}

	/**
	 * Data callback for Image Sizes
	 *
	 * @param string       $name    The name of the field
	 * @param string|array $value   The value of the field
	 * @param array        $options Field options
	 * @param array        $pod     Pod data
	 * @param int          $id      Item ID
	 *
	 * @return array
	 *
	 * @since 2.3
	 */
	public function data_image_sizes( $name = null, $value = null, $options = null, $pod = null, $id = null ) {

		$data = array();

		$image_sizes = get_intermediate_image_sizes();

		foreach ( $image_sizes as $image_size ) {
			$data[ $image_size ] = ucwords( str_replace( '-', ' ', $image_size ) );
		}

		return apply_filters( 'pods_form_ui_field_pick_' . __FUNCTION__, $data, $name, $value, $options, $pod, $id );

	}

	/**
	 * Create a WP Gallery from the passed values (need to be attachments)
	 *
	 * @since  2.7
	 *
	 * @param  string|array $value   The value(s)
	 * @param  array        $options The field options
	 *
	 * @return string
	 */
	public function do_wp_gallery( $value, $options ) {

		$args = array();

		if ( ! empty( $options[ self::$type . '_wp_gallery_columns' ] ) ) {
			$args['columns'] = absint( $options[ self::$type . '_wp_gallery_columns' ] );
		}

		if ( ! empty( $options[ self::$type . '_wp_gallery_random_sort' ] ) ) {
			$args['orderby'] = 'rand';
		}

		if ( ! empty( $options[ self::$type . '_wp_gallery_link' ] ) ) {
			$args['link'] = $options[ self::$type . '_wp_gallery_link' ];
		}

		if ( ! empty( $options[ self::$type . '_wp_gallery_size' ] ) ) {
			$args['size'] = $options[ self::$type . '_wp_gallery_size' ];
		}

		if ( isset( $value['ID'] ) ) {
			$args['ids'] = $value['ID'];
		} else {
			$images = array();

			foreach ( $value as $v ) {
				if ( ! is_array( $v ) ) {
					$images[] = (int) $v;
				} elseif ( isset( $v['ID'] ) ) {
					$images[] = (int) $v['ID'];
				}
			}

			$args['ids'] = implode( ',', $images );
		}

		if ( is_callable( 'gallery_shortcode' ) ) {
			return gallery_shortcode( $args );
		} else {
			$shortcode = '[gallery';

			foreach ( $args as $key => $value ) {
				$shortcode .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}

			$shortcode .= ']';

			return do_shortcode( $shortcode );
		}

	}

	/**
	 * Handle file row output for uploaders
	 *
	 * @param array  $attributes
	 * @param int    $limit
	 * @param bool   $editable
	 * @param int    $id
	 * @param string $icon
	 * @param string $name
	 *
	 * @return string
	 * @since 2.0
	 *
	 * @deprecated 2.7
	 */
	public function markup( $attributes, $limit = 1, $editable = true, $id = null, $icon = null, $name = null, $linked = false, $link = null ) {

		_doing_it_wrong( 'PodsField_File::markup', esc_html__( 'This method has been deprecated and will be removed from Pods 3.0', 'pods' ), '2.7' );

		// Preserve current file type.
		$field_type = PodsForm::$field_type;

		ob_start();

		if ( empty( $id ) ) {
			$id = '{{id}}';
		}

		if ( empty( $icon ) ) {
			$icon = '{{icon}}';
		} else {
			$icon = esc_url( $icon );
		}

		if ( empty( $name ) ) {
			$name = '{{name}}';
		}

		if ( empty( $link ) ) {
			$link = '{{link}}';
		}

		$editable = (boolean) $editable;
		$linked   = (boolean) $linked;
		?>
		<li class="pods-file hidden" id="pods-file-<?php echo esc_attr( $id ); ?>">
			<?php echo PodsForm::field( $attributes['name'] . '[' . $id . '][id]', $id, 'hidden' ); ?>

			<ul class="pods-file-meta media-item">
				<?php if ( 1 != $limit ) { ?>
					<li class="pods-file-col pods-file-handle">Handle</li>
				<?php } ?>

				<li class="pods-file-col pods-file-icon">
					<img class="pinkynail" src="<?php echo $icon; ?>" alt="Icon" />
				</li>

				<li class="pods-file-col pods-file-name">
					<?php
					if ( $editable ) {
						echo PodsForm::field( $attributes['name'] . '[' . $id . '][title]', $name, 'text' );
					} else {
						echo( empty( $name ) ? '{{name}}' : $name );
					}
					?>
				</li>

				<li class="pods-file-col pods-file-actions">
					<ul>
						<li class="pods-file-col pods-file-delete"><a href="#delete">Delete</a></li>
						<?php
						if ( $linked ) {
							?>
							<li class="pods-file-col pods-file-download">
								<a href="<?php echo esc_url( $link ); ?>" target="_blank">Download</a></li>
							<?php
						}
						?>
					</ul>
				</li>
			</ul>
		</li>
		<?php
		PodsForm::$field_type = $field_type;

		return ob_get_clean();

	}

	/**
	 * Handle AJAX plupload calls.
	 *
	 * @since 2.3
	 */
	public function admin_ajax_upload() {

		pods_session_start();

		// Sanitize input
		$params = pods_unslash( (array) $_POST );

		foreach ( $params as $key => $value ) {
			if ( 'action' === $key ) {
				continue;
			}

			unset( $params[ $key ] );

			$params[ str_replace( '_podsfix_', '', $key ) ] = $value;
		}

		$params = (object) $params;

		$methods = array(
			'upload',
		);

		if ( ! isset( $params->method ) || ! in_array( $params->method, $methods, true ) || ! isset( $params->pod ) || ! isset( $params->field ) || ! isset( $params->uri ) || empty( $params->uri ) ) {
			pods_error( 'Invalid AJAX request', PodsInit::$admin );
		} elseif ( ! empty( $params->pod ) && empty( $params->field ) ) {
			pods_error( 'Invalid AJAX request', PodsInit::$admin );
		} elseif ( empty( $params->pod ) && ! current_user_can( 'upload_files' ) ) {
			pods_error( 'Invalid AJAX request', PodsInit::$admin );
		}

		// Flash often fails to send cookies with the POST or upload, so we need to pass it in GET or POST instead
		if ( is_ssl() && empty( $_COOKIE[ SECURE_AUTH_COOKIE ] ) && ! empty( $_REQUEST['auth_cookie'] ) ) {
			$_COOKIE[ SECURE_AUTH_COOKIE ] = $_REQUEST['auth_cookie'];
		} elseif ( empty( $_COOKIE[ AUTH_COOKIE ] ) && ! empty( $_REQUEST['auth_cookie'] ) ) {
			$_COOKIE[ AUTH_COOKIE ] = $_REQUEST['auth_cookie'];
		}

		if ( empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) && ! empty( $_REQUEST['logged_in_cookie'] ) ) {
			$_COOKIE[ LOGGED_IN_COOKIE ] = $_REQUEST['logged_in_cookie'];
		}

		global $current_user;
		unset( $current_user );

		/**
		 * Access Checking
		 */
		$upload_disabled = false;

		if ( defined( 'PODS_DISABLE_FILE_UPLOAD' ) && true === PODS_DISABLE_FILE_UPLOAD ) {
			$upload_disabled = true;
		} elseif ( defined( 'PODS_UPLOAD_REQUIRE_LOGIN' ) && is_bool( PODS_UPLOAD_REQUIRE_LOGIN ) && true === PODS_UPLOAD_REQUIRE_LOGIN && ! is_user_logged_in() ) {
			$upload_disabled = true;
		} elseif ( defined( 'PODS_UPLOAD_REQUIRE_LOGIN' ) && ! is_bool( PODS_UPLOAD_REQUIRE_LOGIN ) && ( ! is_user_logged_in() || ! current_user_can( PODS_UPLOAD_REQUIRE_LOGIN ) ) ) {
			$upload_disabled = true;
		}

		$uid = @session_id();

		if ( is_user_logged_in() ) {
			$uid = 'user_' . get_current_user_id();
		}

		$nonce_check = 'pods_upload_' . (int) $params->pod . '_' . $uid . '_' . $params->uri . '_' . (int) $params->field;

		if ( true === $upload_disabled || ! isset( $params->_wpnonce ) || false === wp_verify_nonce( $params->_wpnonce, $nonce_check ) ) {
			pods_error( __( 'Unauthorized request', 'pods' ), PodsInit::$admin );
		}

		$pod   = array();
		$field = array(
			'type'    => 'file',
			'options' => array()
		);

		if ( empty( self::$api ) ) {
			self::$api = pods_api();
		}

		self::$api->display_errors = false;

		if ( ! empty( $params->pod ) ) {
			$pod   = self::$api->load_pod( array( 'id' => (int) $params->pod ) );
			$field = self::$api->load_field( array( 'id' => (int) $params->field ) );

			if ( empty( $pod ) || empty( $field ) || $pod['id'] != $field['pod_id'] || ! isset( $pod['fields'][ $field['name'] ] ) ) {
				pods_error( __( 'Invalid field request', 'pods' ), PodsInit::$admin );
			}

			if ( ! in_array( $field['type'], PodsForm::file_field_types(), true ) ) {
				pods_error( __( 'Invalid field', 'pods' ), PodsInit::$admin );
			}
		}

		$method = $params->method;

		// Cleaning up $params
		unset( $params->action );
		unset( $params->method );
		unset( $params->_wpnonce );

		$params->post_id = (int) pods_v( 'post_id', $params, 0 );

		/**
		 * Upload a new file (advanced - returns URL and ID)
		 */
		if ( 'upload' === $method ) {
			$file = $_FILES['Filedata'];

			$limit_size = pods_v( $field['type'] . '_restrict_filesize', $field['options'] );

			if ( ! empty( $limit_size ) ) {
				if ( false !== stripos( $limit_size, 'MB' ) ) {
					$limit_size = (float) trim( str_ireplace( 'MB', '', $limit_size ) );
					$limit_size = $limit_size * 1025 * 1025; // convert to KB to B
				} elseif ( false !== stripos( $limit_size, 'KB' ) ) {
					$limit_size = (float) trim( str_ireplace( 'KB', '', $limit_size ) );
					$limit_size = $limit_size * 1025 * 1025; // convert to B
				} elseif ( false !== stripos( $limit_size, 'GB' ) ) {
					$limit_size = (float) trim( str_ireplace( 'GB', '', $limit_size ) );
					$limit_size = $limit_size * 1025 * 1025 * 1025; // convert to MB to KB to B
				} elseif ( false !== stripos( $limit_size, 'B' ) ) {
					$limit_size = (float) trim( str_ireplace( 'B', '', $limit_size ) );
				} else {
					$limit_size = wp_max_upload_size();
				}

				if ( 0 < $limit_size && $limit_size < $file['size'] ) {
					$error = __( 'File size too large, max size is %s', 'pods' );
					$error = sprintf( $error, pods_v( $field['type'] . '_restrict_filesize', $field['options'] ) );

					pods_error( '<div style="color:#FF0000">Error: ' . $error . '</div>' );
				}
			}

			$limit_file_type = pods_v( $field['type'] . '_type', $field['options'], 'images' );

			if ( 'images' === $limit_file_type ) {
				$limit_types = 'jpg,jpeg,png,gif';
			} elseif ( 'video' === $limit_file_type ) {
				$limit_types = 'mpg,mov,flv,mp4';
			} elseif ( 'audio' === $limit_file_type ) {
				$limit_types = 'mp3,m4a,wav,wma';
			} elseif ( 'text' === $limit_file_type ) {
				$limit_types = 'txt,rtx,csv,tsv';
			} elseif ( 'any' === $limit_file_type ) {
				$limit_types = '';
			} else {
				$limit_types = pods_v( $field['type'] . '_allowed_extensions', $field['options'], '', true );
			}

			$limit_types = trim( str_replace( array( ' ', '.', "\n", "\t", ';' ), array(
				'',
				',',
				',',
				','
			), $limit_types ), ',' );

			if ( pods_version_check( 'wp', '3.5' ) ) {
				$mime_types = wp_get_mime_types();

				if ( in_array( $limit_file_type, array( 'images', 'audio', 'video' ), true ) ) {
					$new_limit_types = array();

					foreach ( $mime_types as $type => $mime ) {
						if ( 0 === strpos( $mime, $limit_file_type ) ) {
							$type = explode( '|', $type );

							$new_limit_types = array_merge( $new_limit_types, $type );
						}
					}

					if ( ! empty( $new_limit_types ) ) {
						$limit_types = implode( ',', $new_limit_types );
					}
				} elseif ( 'any' != $limit_file_type ) {
					$new_limit_types = array();

					$limit_types = explode( ',', $limit_types );

					foreach ( $limit_types as $k => $limit_type ) {
						$found = false;

						foreach ( $mime_types as $type => $mime ) {
							if ( 0 === strpos( $mime, $limit_type ) ) {
								$type = explode( '|', $type );

								foreach ( $type as $t ) {
									if ( ! in_array( $t, $new_limit_types, true ) ) {
										$new_limit_types[] = $t;
									}
								}

								$found = true;
							}
						}

						if ( ! $found ) {
							$new_limit_types[] = $limit_type;
						}
					}

					if ( ! empty( $new_limit_types ) ) {
						$limit_types = implode( ',', $new_limit_types );
					}
				}
			}

			$limit_types = explode( ',', $limit_types );

			$limit_types = array_filter( array_unique( $limit_types ) );

			if ( ! empty( $limit_types ) ) {
				$ok = false;

				foreach ( $limit_types as $limit_type ) {
					$limit_type = '.' . trim( $limit_type, ' .' );

					$pos = ( strlen( $file['name'] ) - strlen( $limit_type ) );

					if ( $pos === stripos( $file['name'], $limit_type ) ) {
						$ok = true;

						break;
					}
				}

				if ( false === $ok ) {
					$error = __( 'File type not allowed, please use one of the following: %s', 'pods' );
					$error = sprintf( $error, '.' . implode( ', .', $limit_types ) );

					pods_error( '<div style="color:#FF0000">Error: ' . $error . '</div>' );
				}
			}

			$custom_handler = apply_filters( 'pods_upload_handle', null, 'Filedata', $params->post_id, $params, $field );

			if ( null === $custom_handler ) {
				$attachment_id = media_handle_upload( 'Filedata', $params->post_id );

				if ( is_object( $attachment_id ) ) {
					$errors = array();

					foreach ( $attachment_id->errors['upload_error'] as $error_code => $error_message ) {
						$errors[] = '[' . $error_code . '] ' . $error_message;
					}

					pods_error( '<div style="color:#FF0000">Error: ' . implode( '</div><div>', $errors ) . '</div>' );
				} else {
					$attachment = get_post( $attachment_id, ARRAY_A );

					$attachment['filename'] = basename( $attachment['guid'] );

					$thumb = wp_get_attachment_image_src( $attachment['ID'], 'thumbnail', true );

					$attachment['thumbnail'] = '';

					if ( ! empty( $thumb[0] ) ) {
						$attachment['thumbnail'] = $thumb[0];
					}

					$attachment['link']      = get_permalink( $attachment['ID'] );
					$attachment['edit_link'] = get_edit_post_link( $attachment['ID'] );
					$attachment['download']  = wp_get_attachment_url( $attachment['ID'] );

					$attachment = apply_filters( 'pods_upload_attachment', $attachment, $params->post_id );

					wp_send_json( $attachment );
				}
			}
		}

		die(); // KBAI!

	}

}
