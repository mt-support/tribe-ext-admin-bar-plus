<?php
/**
 * Plugin Name:       The Events Calendar Extension: Admin Bar Plus
 * Plugin URI:        https://theeventscalendar.com/extensions/---the-extension-article-url---/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-admin-bar-plus
 * Description:       [Extension Description]
 * Version:           0.9.0
 * Extension Class:   Tribe\Extensions\AdminBarPlus\Main
 * Author:            Modern Tribe, Inc.
 * Author URI:        http://m.tri.be/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tribe-ext-admin-bar-plus
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

namespace Tribe\Extensions\AdminBarPlus;

use Tribe__Autoloader;
use Tribe__Dependency;
use Tribe__Extension;

/**
 * Define Constants
 */

if ( ! defined( __NAMESPACE__ . '\NS' ) ) {
	define( __NAMESPACE__ . '\NS', __NAMESPACE__ . '\\' );
}

if ( ! defined( NS . 'PLUGIN_TEXT_DOMAIN' ) ) {
	// `Tribe\Extensions\AdminBarPlus\PLUGIN_TEXT_DOMAIN` is defined
	define( NS . 'PLUGIN_TEXT_DOMAIN', 'tribe-ext-admin-bar-plus' );
}

// Do not load unless Tribe Common is fully loaded and our class does not yet exist.
if (
	class_exists( 'Tribe__Extension' )
	&& ! class_exists( NS . 'Main' )
) {
	/**
	 * Extension main class, class begins loading on init() function.
	 */
	class Main extends Tribe__Extension {

		/**
		 * @var Tribe__Autoloader
		 */
		private $class_loader;

		/**
		 * @var Settings
		 */
		private $settings;

		/**
		 * Custom options prefix (without trailing underscore).
		 *
		 * Should leave blank unless you want to set it to something custom, such as if migrated from old extension.
		 */
		private $opts_prefix = '';

		/**
		 * Is Events Calendar PRO active. If yes, we will add some extra functionality.
		 *
		 * @return bool
		 */
		public $ecp_active = false;

		/**
		 * Is Event Tickets active. If yes, we will add some extra functionality.
		 *
		 * @return bool
		 */
		public $et_active = false;

		/**
		 * Is Filter Bar active. If yes, we will add some extra functionality.
		 *
		 * @return bool
		 */
		public $fb_active = false;

		/**
		 * Setup the Extension's properties.
		 *
		 * This always executes even if the required plugins are not present.
		 */
		public function construct() {
			// Dependency requirements and class properties can be defined here.

			/**
			 * Examples:
			 * All these version numbers are the ones on or after November 16, 2016, but you could remove the version
			 * number, as it's an optional parameter. Know that your extension code will not run at all (we won't even
			 * get this far) if you are not running The Events Calendar 4.3.3+ or Event Tickets 4.3.3+, as that is where
			 * the Tribe__Extension class exists, which is what we are extending.
			 *
			 * If using `tribe()`, such as with `Tribe__Dependency`, require TEC/ET version 4.4+ (January 9, 2017).
			 */
			// $this->add_required_plugin( 'Tribe__Tickets__Main', '4.4' );
			// $this->add_required_plugin( 'Tribe__Tickets_Plus__Main', '4.3.3' );
			// $this->add_required_plugin( 'Tribe__Events__Main', '4.4' );
			// $this->add_required_plugin( 'Tribe__Events__Pro__Main', '4.3.3' );
			// $this->add_required_plugin( 'Tribe__Events__Community__Main', '4.3.2' );
			// $this->add_required_plugin( 'Tribe__Events__Community__Tickets__Main', '4.3.2' );
			// $this->add_required_plugin( 'Tribe__Events__Filterbar__View', '4.3.3' );
			// $this->add_required_plugin( 'Tribe__Events__Tickets__Eventbrite__Main', '4.3.2' );
			// $this->add_required_plugin( 'Tribe_APM', '4.4' );

			// Conditionally-require Events Calendar PRO. If it is active, run an extra bit of code.
			add_action( 'tribe_plugins_loaded', [ $this, 'detect_tribe_plugins' ], 0 );
		}

		/**
		 * Check required plugins after all Tribe plugins have loaded.
		 *
		 * Useful for conditionally-requiring a Tribe plugin, whether to add extra functionality
		 * or require a certain version but only if it is active.
		 */
		public function detect_tribe_plugins() {
			/** @var Tribe__Dependency $dep */
			$dep = tribe( Tribe__Dependency::class );

			if ( $dep->is_plugin_active( 'Tribe__Events__Pro__Main' ) ) {
				$this->add_required_plugin( 'Tribe__Events__Pro__Main' );
				$this->ecp_active = true;
			}
			if ( $dep->is_plugin_active( 'Tribe__Tickets__Main' ) ) {
				$this->add_required_plugin( 'Tribe__Tickets__Main' );
				$this->et_active = true;
			}
			if ( $dep->is_plugin_active( 'Tribe__Events__Filterbar__View' ) ) {
				$this->add_required_plugin( 'Tribe__Events__Filterbar__View' );
				$this->fb_active = true;
			}
		}

		/**
		 * Get Settings instance.
		 *
		 * @return Settings
		 */
		private function get_settings() {
			if ( empty( $this->settings ) ) {
				$this->settings = new Settings( $this->opts_prefix );
			}

			return $this->settings;
		}

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {
			// Load plugin textdomain
			// Don't forget to generate the 'languages/tribe-ext-admin-bar-plus.pot' file
			load_plugin_textdomain( PLUGIN_TEXT_DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages/' );

			if ( ! $this->php_version_check() ) {
				return;
			}

			$this->class_loader();

			$this->get_settings();

			// TODO: Just a test. Remove this.
			//$this->testing_hello_world();

			// Insert filter and action hooks here
			//add_filter( 'thing_we_are_filtering', [ $this, 'my_custom_function' ] );

			add_action('admin_bar_menu', [ $this, 'add_toolbar_items' ], 100);
		}

		/**
		 * Check if we have a sufficient version of PHP. Admin notice if we don't and user should see it.
		 *
		 * @return bool
		 */
		private function php_version_check() {
			$php_required_version = '5.6';

			if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) {
				if (
					is_admin()
					&& current_user_can( 'activate_plugins' )
				) {
					$message = '<p>';
					$message .= sprintf( __( '%s requires PHP version %s or newer to work. Please contact your website host and inquire about updating PHP.', PLUGIN_TEXT_DOMAIN ), $this->get_name(), $php_required_version );
					$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );
					$message .= '</p>';
					tribe_notice( PLUGIN_TEXT_DOMAIN . '-php-version', $message, [ 'type' => 'error' ] );
				}

				return false;
			}

			return true;
		}

		/**
		 * Use Tribe Autoloader for all class files within this namespace in the 'src' directory.
		 *
		 * TODO: Delete this method and its usage throughout this file if there is no `src` directory, such as if there are no settings being added to the admin UI.
		 *
		 * @return Tribe__Autoloader
		 */
		public function class_loader() {
			if ( empty( $this->class_loader ) ) {
				$this->class_loader = new Tribe__Autoloader;
				$this->class_loader->set_dir_separator( '\\' );
				$this->class_loader->register_prefix(
					NS,
					__DIR__ . DIRECTORY_SEPARATOR . 'src'
				);
			}

			$this->class_loader->register_autoloader();

			return $this->class_loader;
		}

		/**
		 * TODO: Testing Hello World. Delete this for your new extension.
		 */
		public function testing_hello_world() {
			$message = sprintf( '<p>Hello World from %s. Make sure to remove this in your own new extension.</p>', '<strong>' . $this->get_name() . '</strong>' );

			$message .= sprintf( '<p><strong>Bonus!</strong> Get one of our own custom option values: %s</p><p><em>See the code to learn more.</em></p>', $this->get_one_custom_option() );

			tribe_notice( PLUGIN_TEXT_DOMAIN . '-hello-world', $message, [ 'type' => 'info' ] );
		}

		/**
		 * Demonstration of getting this extension's `a_setting` option value.
		 *
		 * TODO: Rework or remove this.
		 *
		 * @return mixed
		 */
		public function get_one_custom_option() {
			$settings = $this->get_settings();

			return $settings->get_option( 'a_setting', 'https://theeventscalendar.com/' );
		}

		/**
		 * Get all of this extension's options.
		 *
		 * @return array
		 */
		public function get_all_options() {
			$settings = $this->get_settings();

			return $settings->get_all_options();
		}

		/**
		 * Include a docblock for every class method and property.
		 */
		public function my_custom_function() {
			// do your custom stuff
		}

		function add_toolbar_items( $admin_bar ) {
			$admin_bar->add_menu( array(
				'id'    => 'tribe-events-settings-general',
				'parent' => 'tribe-events-settings',
				'title' => 'General',
				'href'  => 'edit.php?page=tribe-common&tab=general&post_type=tribe_events',
				'meta'  => array(
					'title' => __('General'),
					'class' => 'my_menu_item_class'
				),
			));
			$admin_bar->add_menu( array(
				'id'    => 'tribe-events-settings-display',
				'parent' => 'tribe-events-settings',
				'title' => 'Display',
				'href'  => 'edit.php?page=tribe-common&tab=display&post_type=tribe_events',
				'meta'  => array(
					'title' => __('Display'),
					'class' => 'my_menu_item_class'
				),
			));

			// Inject Event Tickets settings
			if ( $this->et_active ) {
				$admin_bar->add_menu( array(
					'id'    => 'tribe-events-settings-tickets',
					'parent' => 'tribe-events-settings',
					'title' => 'Tickets (ET)',
					'href'  => 'edit.php?page=tribe-common&tab=event-tickets&post_type=tribe_events',
					'meta'  => array(
						'title' => __('Tickets (ET)'),
						'class' => 'my_menu_item_class'
					),
				));
			}

//			// Inject Filter Bar settings
//			if ( $this->fb_active ) {
//				$admin_bar->add_menu( array(
//					'id'    => 'tribe-events-settings-filters',
//					'parent' => 'tribe-events-settings',
//					'title' => 'Filters (FB)',
//					'href'  => 'edit.php?page=tribe-common&tab=event-tickets&post_type=tribe_events',
//					'meta'  => array(
//						'title' => __('Filters (FB)'),
//						'class' => 'my_menu_item_class'
//					),
//				));
//			}

			// Inject Events Calendar PRO settings
			if ( $this->ecp_active ) {
				$admin_bar->add_menu( array(
					'id'    => 'tribe-events-settings-default-content',
					'parent' => 'tribe-events-settings',
					'title' => 'Default Content (ECP)',
					'href'  => 'edit.php?page=tribe-common&tab=defaults&post_type=tribe_events',
					'meta'  => array(
						'title' => __('Default Content (ECP)'),
						'class' => 'my_menu_item_class'
					),
				));
				$admin_bar->add_menu( array(
					'id'    => 'tribe-events-settings-additional-fields',
					'parent' => 'tribe-events-settings',
					'title' => 'Additional Fields (ECP)',
					'href'  => 'edit.php?page=tribe-common&tab=additional-fields&post_type=tribe_events',
					'meta'  => array(
						'title' => __('Additional Fields (ECP)'),
						'class' => 'my_menu_item_class'
					),
				));
			}

			$admin_bar->add_menu( array(
				'id'    => 'tribe-events-settings-licenses',
				'parent' => 'tribe-events-settings',
				'title' => 'Licenses',
				'href'  => 'edit.php?page=tribe-common&tab=licenses&post_type=tribe_events',
				'meta'  => array(
					'title' => __('Licenses'),
					'class' => 'my_menu_item_class'
				),
			));
			$admin_bar->add_menu( array(
				'id'    => 'tribe-events-settings-apis',
				'parent' => 'tribe-events-settings',
				'title' => 'APIs',
				'href'  => 'edit.php?page=tribe-common&tab=addons&post_type=tribe_events',
				'meta'  => array(
					'title' => __('APIs'),
					'class' => 'my_menu_item_class'
				),
			));
			$admin_bar->add_menu( array(
				'id'    => 'tribe-events-settings-imports',
				'parent' => 'tribe-events-settings',
				'title' => 'Imports',
				'href'  => 'edit.php?page=tribe-common&tab=imports&post_type=tribe_events',
				'meta'  => array(
					'title' => __('Imports'),
					'class' => 'my_menu_item_class'
				),
			));
/*			$admin_bar->add_menu( array(
				'id'    => 'tribe-events-settings-upgrade',
				'parent' => 'tribe-events-settings-display',
				'title' => 'Display',
				'href'  => 'edit.php?page=tribe-common&tab=display&post_type=tribe_events',
				'meta'  => array(
					'title' => __('Display'),
					'class' => 'my_menu_item_class'
				),
			));*/
		}

	} // end class
} // end if class_exists check
