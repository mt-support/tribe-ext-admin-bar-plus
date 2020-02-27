<?php
/**
 * Plugin Name:       The Events Calendar Extension: Admin Bar Plus
 * Plugin URI:        https://theeventscalendar.com/extensions/tribe-ext-admin-bar-plus/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-admin-bar-plus
 * Description:       The extension will add quick links to the different settings pages to the admin bar menu of The Events Calendar.
 * Version:           1.0.0
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
		 * Setup the Extension's properties.
		 *
		 * This always executes even if the required plugins are not present.
		 */
		public function construct() {
			$this->add_required_plugin( 'Tribe__Events__Main' );

			// Conditionally-require Events Calendar PRO or Event Tickets. If it is active, run an extra bit of code.
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

			add_action( 'admin_bar_menu', [ $this, 'add_toolbar_items' ], 100 );
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
		 * Add our custom menu items, as applicable.
		 *
		 * @param \WP_Admin_Bar $admin_bar
		 */
		function add_toolbar_items( $admin_bar ) {
			$admin_bar->add_menu(
				[
					'id'     => 'tribe-events-settings-general',
					'parent' => 'tribe-events-settings',
					'title'  => __( 'General', 'tribe-ext-admin-bar-plus' ),
					'href'   => 'edit.php?page=tribe-common&tab=general&post_type=tribe_events',
					'meta'   => [
						'title' => __( 'General', 'tribe-ext-admin-bar-plus' ),
						'class' => 'my_menu_item_class',
					],
				]
			);
			$admin_bar->add_menu(
				[
					'id'     => 'tribe-events-settings-display',
					'parent' => 'tribe-events-settings',
					'title'  => __( 'Display', 'tribe-ext-admin-bar-plus' ),
					'href'   => 'edit.php?page=tribe-common&tab=display&post_type=tribe_events',
					'meta'   => [
						'title' => __( 'Display', 'tribe-ext-admin-bar-plus' ),
						'class' => 'my_menu_item_class',
					],
				]
			);

			// Inject Event Tickets settings
			if ( $this->et_active ) {
				$admin_bar->add_menu(
					[
						'id'     => 'tribe-events-settings-tickets',
						'parent' => 'tribe-events-settings',
						'title'  => __( 'Tickets', 'tribe-ext-admin-bar-plus' ),
						'href'   => 'edit.php?page=tribe-common&tab=event-tickets&post_type=tribe_events',
						'meta'   => [
							'title' => __( 'Tickets', 'tribe-ext-admin-bar-plus' ),
							'class' => 'my_menu_item_class',
						],
					]
				);
			}

			// Inject Events Calendar PRO settings
			if ( $this->ecp_active ) {
				$admin_bar->add_menu(
					[
						'id'     => 'tribe-events-settings-default-content',
						'parent' => 'tribe-events-settings',
						'title'  => __( 'Default Content', 'tribe-ext-admin-bar-plus' ),
						'href'   => 'edit.php?page=tribe-common&tab=defaults&post_type=tribe_events',
						'meta'   => [
							'title' => __( 'Default Content', 'tribe-ext-admin-bar-plus' ),
							'class' => 'my_menu_item_class',
						],
					]
				);
				$admin_bar->add_menu(
					[
						'id'     => 'tribe-events-settings-additional-fields',
						'parent' => 'tribe-events-settings',
						'title'  => __( 'Additional Fields', 'tribe-ext-admin-bar-plus' ),
						'href'   => 'edit.php?page=tribe-common&tab=additional-fields&post_type=tribe_events',
						'meta'   => [
							'title' => __( 'Additional Fields', 'tribe-ext-admin-bar-plus' ),
							'class' => 'my_menu_item_class',
						],
					]
				);
			}

			$admin_bar->add_menu(
				[
					'id'     => 'tribe-events-settings-licenses',
					'parent' => 'tribe-events-settings',
					'title'  => __( 'Licenses', 'tribe-ext-admin-bar-plus' ),
					'href'   => 'edit.php?page=tribe-common&tab=licenses&post_type=tribe_events',
					'meta'   => [
						'title' => __( 'Licenses', 'tribe-ext-admin-bar-plus' ),
						'class' => 'my_menu_item_class',
					],
				]
			);
			$admin_bar->add_menu(
				[
					'id'     => 'tribe-events-settings-apis',
					'parent' => 'tribe-events-settings',
					'title'  => __( 'APIs', 'tribe-ext-admin-bar-plus' ),
					'href'   => 'edit.php?page=tribe-common&tab=addons&post_type=tribe_events',
					'meta'   => [
						'title' => __( 'APIs', 'tribe-ext-admin-bar-plus' ),
						'class' => 'my_menu_item_class',
					],
				]
			);
			$admin_bar->add_menu(
				[
					'id'     => 'tribe-events-settings-imports',
					'parent' => 'tribe-events-settings',
					'title'  => __( 'Imports', 'tribe-ext-admin-bar-plus' ),
					'href'   => 'edit.php?page=tribe-common&tab=imports&post_type=tribe_events',
					'meta'   => [
						'title' => __( 'Imports', 'tribe-ext-admin-bar-plus' ),
						'class' => 'my_menu_item_class',
					],
				]
			);
		}

	} // end class
} // end if class_exists check