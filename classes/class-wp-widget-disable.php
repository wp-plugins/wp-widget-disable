<?php
/**
 * Holds the main plugin class.
 *
 * @package WP_Widget_Disable
 */

/**
 * Class WP_Widget_Disable
 */
class WP_Widget_Disable {
	/**
	 * Plugin version.
	 */
	const VERSION = '1.5.0';

	/**
	 * Sidebar widgets option key.
	 *
	 * Stores all the disabled sidebar widgets as an array.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $sidebar_widgets_option = 'rplus_wp_widget_disable_sidebar_option';

	/**
	 * Available sidebar widgets.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $sidebar_widgets = array();

	/**
	 * Dashboard widgets option key.
	 *
	 * Stores all the disabled sidebar widgets as an array.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $dashboard_widgets_option = 'rplus_wp_widget_disable_dashboard_option';

	/**
	 * Adds hooks.
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Get and disable the sidebar widgets.
		add_action( 'widgets_init', array( $this, 'set_default_sidebar_widgets' ), 100 );
		add_action( 'widgets_init', array( $this, 'disable_sidebar_widgets' ), 100 );

		// Get and disable the dashboard widgets.
		add_action( 'wp_dashboard_setup', array( $this, 'disable_dashboard_widgets' ), 100 );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( $this->get_path() . 'wp-widget-disable.php' );
		add_action( 'plugin_action_links_' . $plugin_basename, array( $this, 'plugin_action_links' ) );

		add_action( 'admin_footer_text', array( $this, 'admin_footer_text' ) );
	}

	/**
	 * Returns the URL to the plugin directory
	 *
	 * @return string The URL to the plugin directory.
	 */
	protected function get_url() {
		return plugin_dir_url( __DIR__ );
	}

	/**
	 * Returns the path to the plugin directory.
	 *
	 * @return string The absolute path to the plugin directory.
	 */
	protected function get_path() {
		return plugin_dir_path( __DIR__ );
	}

	/**
	 * Initializes the plugin, registers textdomain, etc.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wp-widget-disable', false, basename( $this->get_path() ) . '/languages' );
	}

	/**
	 * Register the administration menu for this plugin.
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {
		add_theme_page(
			__( 'Disable Sidebar and Dashboard Widgets', 'wp-widget-disable' ),
			__( 'Disable Widgets', 'wp-widget-disable' ),
			'edit_theme_options',
			'wp-widget-disable',
			function () {
				include( trailingslashit( $this->get_path() ) . 'views/admin.php' );
			}
		);
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $links Plugin action links.
	 *
	 * @return array
	 */
	public function plugin_action_links( array $links ) {
		return array_merge(
			array(
				'settings' => sprintf(
					'<a href="%s">%s</a>',
					esc_url( add_query_arg(
						array( 'page' => 'wp-widget-disable' ),
						admin_url( 'themes.php' )
					) ),
					__( 'Settings', 'wp-widget-disable' )
				),
			),
			$links
		);
	}

	/**
	 * Add admin footer text to plugins page.
	 *
	 * @since    1.0.0
	 *
	 * @param  string $text Default admin footer text.
	 *
	 * @return string
	 */
	public function admin_footer_text( $text ) {
		$screen = get_current_screen();

		if ( 'appearance_page_wp-widget-disable' === $screen->base || 'wp-widget-disable' === $screen->base ) {
			/* translators: %s: required+ */
			$text = sprintf( __( 'WP Widget Disable is brought to you by %s. We &hearts; WordPress.', 'wp-widget-disable' ), '<a href="http://required.ch">required+</a>' );
		}

		return $text;
	}

	/**
	 * Set default sidebar widgets.
	 *
	 * Set an array with all the available sidebar widgets
	 * if we can get them.
	 *
	 * @since 1.0.0
	 */
	public function set_default_sidebar_widgets() {
		if ( ! empty( $GLOBALS['wp_widget_factory'] ) ) {
			/**
			 * Filters the available sidebar widgets.
			 *
			 * @param array $widgets The globally available sidebar widgets.
			 */
			$this->sidebar_widgets = apply_filters( 'wp_widget_disable_default_sidebar_widgets', $GLOBALS['wp_widget_factory']->widgets );
		}
	}

	/**
	 * Disable Sidebar Widgets.
	 *
	 * Gets the list of disabled sidebar widgets and disables
	 * them for you in WordPress.
	 *
	 * @since 1.0.0
	 */
	public function disable_sidebar_widgets() {
		$widgets = (array) get_option( $this->sidebar_widgets_option, array() );
		if ( ! empty( $widgets ) ) {
			foreach ( array_keys( $widgets ) as $widget_class ) {
				unregister_widget( $widget_class );
			}
		}
	}

	/**
	 * Disable dashboard widgets.
	 *
	 * Gets the list of disabled dashboard widgets and
	 * disables them for you in WordPress.
	 *
	 * @since 1.0.0
	 */
	public function disable_dashboard_widgets() {
		$widgets = (array) get_option( $this->dashboard_widgets_option );
		if ( ! $widgets ) {
			return;
		}
		foreach ( $widgets as $widget_id => $meta_box ) {
			if ( 'dashboard_welcome_panel' === $widget_id ) {
				remove_action( 'welcome_panel', 'wp_welcome_panel' );
			} else {
				remove_meta_box( $widget_id, 'dashboard', $meta_box );
			}
		}
	}

	/**
	 * Sanitize sidebar widgets user input.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input Sidebar widgets to disable.
	 *
	 * @return array
	 */
	public function sanitize_sidebar_widgets( $input ) {
		// Create our array for storing the validated options.
		$output = array();
		if ( empty( $input ) ) {
			$message = __( 'All sidebar widgets are enabled again.', 'wp-widget-disable' );
		} else {
			// Loop through each of the incoming options.
			foreach ( array_keys( $input ) as $key ) {
				// Check to see if the current option has a value. If so, process it.
				if ( isset( $input[ $key ] ) ) {
					// Strip all HTML and PHP tags and properly handle quoted strings.
					$output[ $key ] = strip_tags( stripslashes( $input[ $key ] ) );
				}
			}

			if ( 1 === count( $output ) ) {
				$message = __( 'Settings saved. One sidebar widget disabled.', 'wp-widget-disable' );
			} else {
				$message = sprintf(
					/* translators: %d: number of disabled widgets */
					_n(
						'Settings saved. %d sidebar widget disabled.',
						'Settings saved. %d sidebar widgets disabled.',
						count( $output ),
						'wp-widget-disable'
					),
					count( $output )
				);
			}
		}
		add_settings_error(
			'wp-widget-disable',
			esc_attr( 'settings_updated' ),
			$message,
			'updated'
		);

		return $output;
	}

	/**
	 * Sanitize dashboard widgets user input.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input Dashboards widgets to disable.
	 *
	 * @return array
	 */
	public function sanitize_dashboard_widgets( $input ) {
		// Create our array for storing the validated options.
		$output = array();
		if ( empty( $input ) ) {
			$message = __( 'All dashboard widgets are enabled again.', 'wp-widget-disable' );
		} else {
			// Loop through each of the incoming options.
			foreach ( array_keys( $input ) as $key ) {
				// Check to see if the current option has a value. If so, process it.
				if ( isset( $input[ $key ] ) ) {
					// Strip all HTML and PHP tags and properly handle quoted strings.
					$output[ $key ] = strip_tags( stripslashes( $input[ $key ] ) );
				}
			}

			if ( 1 === count( $output ) ) {
				$message = __( 'Settings saved. One dashboard widget disabled.', 'wp-widget-disable' );
			} else {
				$message = sprintf(
					/* translators: %d: number of disabled widgets */
					_n(
						'Settings saved. %d dashboard widget disabled.',
						'Settings saved. %d dashboard widgets disabled.',
						count( $output ),
						'wp-widget-disable'
					),
					count( $output )
				);
			}
		}
		add_settings_error(
			'wp-widget-disable',
			esc_attr( 'settings_updated' ),
			$message,
			'updated'
		);

		return apply_filters( 'rplus_wp_widget_disable_validate_dashboard_widgets', $output, $input );
	}

	/**
	 * Register the settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting(
			$this->sidebar_widgets_option,
			$this->sidebar_widgets_option,
			array( $this, 'sanitize_sidebar_widgets' )
		);

		add_settings_section(
			'widget_disable_widget_section',
			__( 'Disable Sidebar Widgets', 'wp-widget-disable' ),
			function () {
				echo '<p>';
				_e( 'Choose the sidebar widgets you would like to disable. Note that developers can still display widgets using PHP.', 'wp-widget-disable' );
				echo '</p>';
			},
			$this->sidebar_widgets_option
		);

		add_settings_field(
			'sidebar_widgets',
			__( 'Sidebar Widgets', 'wp-widget-disable' ),
			array( $this, 'render_sidebar_checkboxes' ),
			$this->sidebar_widgets_option,
			'widget_disable_widget_section'
		);

		register_setting(
			$this->dashboard_widgets_option,
			$this->dashboard_widgets_option,
			array( $this, 'sanitize_dashboard_widgets' )
		);

		add_settings_section(
			'widget_disable_dashboard_section',
			__( 'Disable Dashboard Widgets', 'wp-widget-disable' ),
			function () {
				echo '<p>';
				_e( 'Choose the dashboard widgets you would like to disable.', 'wp-widget-disable' );
				echo '</p>';
			},
			$this->dashboard_widgets_option
		);

		add_settings_field(
			'dashboard_widgets',
			__( 'Dashboard Widgets', 'wp-widget-disable' ),
			array( $this, 'render_dashboard_checkboxes' ),
			$this->dashboard_widgets_option,
			'widget_disable_dashboard_section'
		);
	}

	/**
	 * Render setting fields
	 *
	 * @since 1.0.0
	 */
	public function render_sidebar_checkboxes() {
		$widgets = $this->sidebar_widgets;
		if ( ! $widgets ) {
			_e( 'Oops, we could not retrieve the sidebar widgets! Maybe there is another plugin already managing them?', 'wp-widget-disable' );
		}
		$options = (array) get_option( $this->sidebar_widgets_option );
		?>
		<p>
			<input type="checkbox" id="wp_widget_disable_select_all"/>
			<label for="wp_widget_disable_select_all"><?php _e( 'Select all', 'wp-widget-disable' ); ?></label>
		</p>
		<?php
		foreach ( $widgets as $id => $widget_object ) {
			printf(
				'<p><input type="checkbox" id="%1$s" name="%2$s" value="disabled" %3$s><label for="%1$s">%4$s</label></p>',
				esc_attr( $id ),
				esc_attr( $this->sidebar_widgets_option ) . '[' . esc_attr( $id ) . ']',
				checked( array_key_exists( $id, $options ), true, false ),
				sprintf(
					/* translators: 1: widget name, 2: widget class */
					__( '%1$s (%2$s)', 'wp-widget-disable' ),
					esc_html( $widget_object->name ),
					'<code>' . esc_html( $id ) . '</code>'
				)
			);
		}
	}

	/**
	 * Render setting fields.
	 *
	 * @since 1.0.0
	 */
	public function render_dashboard_checkboxes() {
		global $wp_meta_boxes;
		if ( ! is_array( $wp_meta_boxes['dashboard'] ) ) {
			require_once( ABSPATH . '/wp-admin/includes/dashboard.php' );
			set_current_screen( 'dashboard' );
			remove_action( 'wp_dashboard_setup', array( $this, 'disable_dashboard_widgets' ), 100 );
			wp_dashboard_setup();
			add_action( 'wp_dashboard_setup', array( $this, 'disable_dashboard_widgets' ), 100 );
			set_current_screen( 'wp-widget-disable' );
		}
		if ( isset( $wp_meta_boxes['dashboard'][0] ) ) {
			unset( $wp_meta_boxes['dashboard'][0] );
		}
		$options = (array) get_option( $this->dashboard_widgets_option );
		?>
		<p>
			<input type="checkbox" id="wp_widget_disable_select_all"/>
			<label for="wp_widget_disable_select_all"><?php _e( 'Select all', 'wp-widget-disable' ); ?></label>
		</p>
		<p>
			<input type="checkbox" id="dashboard_welcome_panel"
			       name="rplus_wp_widget_disable_dashboard_option[dashboard_welcome_panel]"
			       value="normal"
				<?php checked( 'dashboard_welcome_panel', ( array_key_exists( 'dashboard_welcome_panel', $options ) ? 'dashboard_welcome_panel' : false ) ); ?>>
			<label for="dashboard_welcome_panel">
			<label for="dashboard_welcome_panel">
				<?php
				/* translators: %s: welcome_panel */
				printf( __( 'Welcome panel (%s)', 'wp-widget-disable' ), '<code>welcome_panel</code>' );
				?>
			</label>
		</p>
		<?php
		foreach ( $wp_meta_boxes['dashboard'] as $context => $priority ) {
			foreach ( $priority as $data ) {
				foreach ( $data as $id => $widget ) {
					printf(
						'<p><input type="checkbox" id="%1$s" name="%2$s" value="%3$s" %4$s><label for="%1$s">%5$s</label></p>',
						esc_attr( $id ),
						esc_attr( $this->dashboard_widgets_option ) . '[' . esc_attr( $id ) . ']',
						esc_attr( $context ),
						checked( array_key_exists( $id, $options ), true, false ),
						sprintf(
							__( '%1$s (%2$s)', 'wp-widget-disable' ),
							esc_html( wp_strip_all_tags( $widget['title'] ) ),
							'<code>' . esc_html( $id ) . '</code>'
						)
					);
				}
			}
		}
	}
}
