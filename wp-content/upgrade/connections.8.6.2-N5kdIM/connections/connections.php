<?php
/**
 * Plugin Name: Connections
 * Plugin URI: http://connections-pro.com/
 * Description: A business directory and address book manager.
 * Version: 8.6.2
 * Author: Steven A. Zahm
 * Author URI: http://connections-pro.com/
 * Text Domain: connections
 * Domain Path: languages
 *
 * Copyright 2016  Steven A. Zahm  ( email : helpdesk@connections-pro.com )
 *
 * Connections is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Connections; if not, see <http://www.gnu.org/licenses/>.
 *
 * @package Connections
 * @category Core
 * @author Steven A. Zahm
 * @version 8.6.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main Connections Class.
 *
 * @since unknown
 */
if ( ! class_exists( 'connectionsLoad' ) ) {

	/**
	 * Class connectionsLoad
	 */
	final class connectionsLoad {

		/**
		 * Stores the instance of this class.
		 *
		 * @access private
		 * @since  0.7.9
		 *
		 * @var connectionsLoad
		 */
		private static $instance;

		/**
		 * @access private
		 * @since  8.5.26
		 *
		 * @var cnAPI
		 */
		private $api;

		/**
		 * @access private
		 * @since  unknown
		 *
		 * @var cnUser
		 */
		public $currentUser;

		/**
		 * @access public
		 * @since  unknown
		 *
		 * @var cnOptions
		 */
		public $options;

		/**
		 * @access public
		 * @since  unknown
		 *
		 * @var cnRetrieve
		 */
		public $retrieve;

		/**
		 * @access public
		 * @since  unknown
		 *
		 * @var cnTerms
		 */
		public $term;

		/**
		 * Stores the page hook values returned from the add_menu_page & add_submenu_page functions
		 *
		 * @access public
		 * @since  unknown
		 *
		 * @var object
		 */
		public $pageHook;

		/**
		 * The Connections Settings API Wrapper class.
		 *
		 * @access public
		 * @since  unknown
		 *
		 * @var cnSettingsAPI
		 */
		public $settings;

		/**
		 * Do the database upgrade.
		 *
		 * @access public
		 * @since  unknown
		 *
		 * @var bool
		 */
		public $dbUpgrade = FALSE;

		/**
		 * Stores the template parts object and any templates activated by the cnTemplateFactory object.
		 *
		 * NOTE: Technically not necessary to load the template parts into this opject but it's required
		 * for backward compatibility for templates expecting to find those methods as part of this object.
		 *
		 * @access public
		 * @since  0.7.6
		 *
		 * @var cnTemplatePart
		 */
		public $template;

		/**
		 * @access public
		 * @since  unknown
		 *
		 * @var cnURL
		 */
		public $url;

		/**
		 * The following vars are being set in the cnEntry and cnRetrieve classes.
		 * @todo Code should be refactor to remove their usage.
		 */
		public $lastQuery;
		public $lastQueryError;
		public $lastInsertID;
		public $resultCount;
		public $resultCountNoLimit;

		/**
		 * A dummy constructor to prevent the class from being loaded more than once.
		 *
		 * @access public
		 * @since 0.7.9
		 */
		public function __construct() { /* Do nothing here */ }

		/**
		 * @access private
		 * @since  unknown
		 * @static
		 *
		 * @return connectionsLoad
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof connectionsLoad ) ) {

				self::$instance = new connectionsLoad;

				require_once plugin_dir_path( __FILE__ ) . 'includes/class.constants.php';
				cnConstants::define( __FILE__ );

				require_once CN_PATH . 'includes/class.dependency.php';
				cnDependency::register();

				/*
				 * cnMetaboxAPI has to load before cnAdminFunction otherwise
				 * the action to save the meta is not added in time to run.
				 */
				cnMetaboxAPI::init();

				// Init the Image API
				cnImage::init();

				// Init email logging of email sent through cnEmail.
				cnLog_Email::init();

				// Init the email template API.
				cnEmail_Template::init();

				// Register the default email templates.
				cnEmail_DefaultTemplates::init();

				// Register the core action/filter hooks.
				self::hooks();

				self::$instance->options     = new cnOptions();
				self::$instance->settings    = cnSettingsAPI::getInstance();
				self::$instance->pageHook    = new stdClass();
				self::$instance->currentUser = new cnUser();
				self::$instance->retrieve    = new cnRetrieve();
				self::$instance->term        = new cnTerms();
				self::$instance->template    = new cnTemplatePart();
				self::$instance->url         = new cnURL();
				self::$instance->api         = new cnAPI();

				// Activation/Deactivation hooks
				register_activation_hook( dirname( __FILE__ ) . '/connections.php', array( __CLASS__, 'activate' ) );
				register_deactivation_hook( dirname( __FILE__ ) . '/connections.php', array( __CLASS__, 'deactivate' ) );

				// @TODO: Create uninstall method to remove options and tables.
				// register_uninstall_hook( dirname(__FILE__) . '/connections.php', array('connectionsLoad', 'uninstall') );

				// Init the options if there is a version change just in case there were any changes.
				if ( version_compare( self::$instance->options->getVersion(), CN_CURRENT_VERSION ) < 0 ) self::$instance->initOptions();
				//self::$instance->options->setDBVersion('0.1.9'); self::$instance->options->saveOptions();

				do_action( 'cn_loaded' );
			}

			return self::$instance;
		}

		private static function hooks() {

			/**
			 * NOTE: Any calls to load_plugin_textdomain should be in a function attached to the `plugins_loaded` action hook.
			 * @link http://ottopress.com/2013/language-packs-101-prepwork/
			 *
			 * NOTE: Any portion of the plugin w/ translatable strings should be bound to the plugins_loaded action hook or later.
			 *
			 * NOTE: Priority set at -1 to allow extensions to use the `connections` text domain. Since extensions are
			 *       generally loaded on the `plugins_loaded` action hook, any strings with the `connections` text
			 *       domain will be merged into it. The purpose is to allow the extensions to use strings known to
			 *       in the core plugin to reuse those strings and benefit if they are already translated.
			 */
			add_action( 'plugins_loaded', array( __CLASS__ , 'loadTextdomain' ), -1 );

			// Include the Template Customizer files.
			add_action( 'plugins_loaded', array( 'cnDependency', 'customizer' ) );

			/*
			 * Register the settings tabs shown on the Settings admin page tabs, sections and fields.
			 */
			add_filter( 'cn_register_settings_tabs', array( 'cnRegisterSettings', 'registerSettingsTabs' ), 10, 1 );
			add_filter( 'cn_register_settings_sections', array( 'cnRegisterSettings', 'registerSettingsSections' ), 10, 1 );
			add_filter( 'cn_register_settings_fields', array( 'cnRegisterSettings', 'registerSettingsFields' ), 10, 1 );

			// cnAdminMenu must load before the cnMetaboxAPI so the admin page hooks are defined.
			add_action( 'admin_menu', array( 'cnAdminMenu' , 'init' ) );

			// Register the core entry metabox and fields.
			add_action( 'cn_metabox', array( 'cnEntryMetabox', 'init' ), 1 );

			// Register the scripts hooks.
			cnScript::hooks();

			// Add actions which purges caches after adding/editing and entry.
			add_action( 'cn_post_process_add-entry', array( 'cnEntry_Action', 'clearCache' ) );
			add_action( 'cn_post_process_update-entry', array( 'cnEntry_Action', 'clearCache' ) );
			add_action( 'cn_process_status', array( 'cnEntry_Action', 'clearCache' ) );
			add_action( 'cn_process_visibility', array( 'cnEntry_Action', 'clearCache' ) );
			add_action( 'cn_process_bulk_delete', array( 'cnEntry_Action', 'clearCache' ) );
			add_action( 'update_option_permalink_structure' , array( 'cnEntry_Action', 'clearCache' ) );

			// Add actions to update the term taxonomy counts when entry status or visibility has been updated via the bulk actions.
			add_action( 'cn_process_status', array( 'cnEntry_Action', 'updateTermCount' ) );
			add_action( 'cn_process_visibility', array( 'cnEntry_Action', 'updateTermCount' ) );

			// Add the "Edit Entry" menu items to the admin bar.
			add_action( 'admin_bar_menu', array( 'cnEntry_Action', 'adminBarMenuItems' ), 90 );

			// Register the shortcode hooks.
			cnShortcode::hooks();

			// Register all valid query variables.
			cnRewrite::hooks();

			/*
			 * Action added in the init hook to allow other plugins time to register there log types.
			 * The priority is set at -1 because the post types and taxonomy are registered in the
			 * init hook at priority 1.
			 */
			add_action( 'init', array( 'cnLog', 'instance' ), -1 );

			// Register the Dashboard metaboxes.
			add_action( 'cn_metabox', array( 'cnDashboardMetabox', 'init' ), 1 );

			// Adds the admin actions and filters.
			add_action( 'admin_init', array( 'cnAdminFunction', 'init' ) );

			/*
			 * Add the filter to update the user settings when the "Apply" button is clicked.
			 * NOTE: This relies on the the Screen Options class by Janis Elsts
			 * NOTE: Set priority 99 so the filter will hopefully run last to help prevent other plugins
			 *       which do not hook into `set-screen-option` properly from breaking Connections.
			 */
			add_filter( 'set-screen-option', array( 'cnAdminFunction', 'managePageLimitSave' ), 99, 3 );

			// Init the class.
			add_action( 'init', array( 'cnSEO', 'hooks' ) );

			// Init the Template Factory API
			// NOTE: The priority can not be >10 otherwise it will break older templates
			// which init'd on `plugins_loaded` at priority 11.
			add_action( 'plugins_loaded', array( 'cnTemplateFactory', 'init' ) );

			// Register the Template Parts API hooks.
			cnTemplatePart::hooks();
			cnTemplate_Compatibility::hooks();

			// Register email log type.
			add_filter( 'cn_email_log_types', array( 'cnSystem_Info', 'registerEmailLogType' ) );

			// Register the log view.
			add_filter( 'cn_log_views', array( 'cnSystem_Info', 'registerLogView' ) );

			// Register the callback to display the email log detail view.
			add_action( 'template_redirect', array( 'cnSystem_Info', 'view' ) );

			// Register the callback to support downloading of vCards
			add_action( 'template_redirect' , array( 'cnvCard', 'download' ) );

			// Geocode the address using Google Geocoding API.
			add_filter( 'cn_set_address', array( 'cnEntry_Action', 'geoCode' ) );
		}

		/**
		 * Load the plugin translation.
		 *
		 * Credit: Adapted from Ninja Forms / Easy Digital Downloads.
		 *
		 * @access private
		 * @since  0.7.9
		 *
		 * @uses apply_filters()
		 * @uses get_locale()
		 * @uses load_textdomain()
		 * @uses load_plugin_textdomain()
		 *
		 * @return void
		 */
		public static function loadTextdomain() {

			// Plugin textdomain. This should match the one set in the plugin header.
			$domain = 'connections';

			// Set filter for plugin's languages directory
			$languagesDirectory = apply_filters( "cn_{$domain}_languages_directory", CN_DIR_NAME . '/languages/' );

			// Traditional WordPress plugin locale filter
			$locale   = apply_filters( 'plugin_locale', get_locale(), $domain );
			$fileName = sprintf( '%1$s-%2$s.mo', $domain, $locale );

			// Setup paths to current locale file
			$local  = $languagesDirectory . $fileName;
			$global = WP_LANG_DIR . "/{$domain}/" . $fileName;

			if ( file_exists( $global ) ) {

				// Look in global `../wp-content/languages/{$domain}/` folder.
				load_textdomain( $domain, $global );

			} elseif ( file_exists( $local ) ) {

				// Look in local `../wp-content/plugins/{plugin-directory}/languages/` folder.
				load_textdomain( $domain, $local );

			} else {

				// Load the default language files
				load_plugin_textdomain( $domain, FALSE, $languagesDirectory );
			}
		}

		/**
		 * During activation this will initiate the options.
		 */
		private function initOptions() {
			$version = $this->options->getVersion();

			switch ( TRUE ) {

				/** @noinspection PhpMissingBreakStatementInspection */
				case ( version_compare( $version, '0.7.3', '<' ) ) :
					/*
					 * Retrieve the settings stored prior to 0.7.3 and migrate them
					 * so they will be accessible in the structure supported by the
					 * Connections WordPress Settings API Wrapper Class.
					 */
					if ( FALSE !== get_option( 'connections_options' ) ) {
						$options = get_option( 'connections_options' );

						if ( FALSE === get_option( 'connections_login' ) ) {
							update_option( 'connections_login' , array(
									'required' => $options['settings']['allow_public'],
									'message' => 'Please login to view the directory.'
								)
							);
						}

						if ( FALSE === get_option( 'connections_visibility' ) ) {
							update_option( 'connections_visibility' , array(
									'allow_public_override' => $options['settings']['allow_public_override'],
									'allow_private_override' => $options['settings']['allow_private_override']
								)
							);
						}

						if ( FALSE === get_option( 'connections_image_thumbnail' ) ) {
							update_option( 'connections_image_thumbnail' , array(
									'quality' => $options['settings']['image']['thumbnail']['quality'],
									'width' => $options['settings']['image']['thumbnail']['x'],
									'height' => $options['settings']['image']['thumbnail']['y'],
									'ratio' => $options['settings']['image']['thumbnail']['crop']
								)
							);
						}
						if ( FALSE === get_option( 'connections_image_medium' ) ) {
							update_option( 'connections_image_medium' , array(
									'quality' => $options['settings']['image']['entry']['quality'],
									'width' => $options['settings']['image']['entry']['x'],
									'height' => $options['settings']['image']['entry']['y'],
									'ratio' => $options['settings']['image']['entry']['crop']
								)
							);
						}

						if ( FALSE === get_option( 'connections_image_large' ) ) {
							update_option( 'connections_image_large' , array(
									'quality' => $options['settings']['image']['profile']['quality'],
									'width' => $options['settings']['image']['profile']['x'],
									'height' => $options['settings']['image']['profile']['y'],
									'ratio' => $options['settings']['image']['profile']['crop']
								)
							);
						}

						if ( FALSE === get_option( 'connections_image_logo' ) ) {
							update_option( 'connections_image_logo' , array(
									'quality' => $options['settings']['image']['logo']['quality'],
									'width' => $options['settings']['image']['logo']['x'],
									'height' => $options['settings']['image']['logo']['y'],
									'ratio' => $options['settings']['image']['logo']['crop']
								)
							);
						}

						if ( FALSE === get_option( 'connections_compatibility' ) ) {
							update_option( 'connections_compatibility' , array(
									'google_maps_api' => $options['settings']['advanced']['load_google_maps_api'],
									'javascript_footer' => $options['settings']['advanced']['load_javascript_footer'] )
							);
						}

						if ( FALSE === get_option( 'connections_debug' ) ) {
							update_option( 'connections_debug' , array( 'debug_messages' => $options['debug'] ) );
						}

						unset( $options );
					}

				/** @noinspection PhpMissingBreakStatementInspection */
				case ( version_compare( $version, '0.7.4', '<' ) ) :
					/*
					 * The option to disable keyword search was added in version 0.7.4. Set this option to be enabled by default.
					 */
					$options = get_option( 'connections_search' );
					$options['keyword_enabled'] = 1;

					update_option( 'connections_search', $options );
					unset( $options );

				/** @noinspection PhpMissingBreakStatementInspection */
				case ( version_compare( $version, '0.8', '<' ) ) :
					/*
					 * The option to disable keyword search was added in version 0.7.4. Set this option to be enabled by default.
					 */
					$options = get_option( 'connections_compatibility' );
					$options['css'] = 1;

					update_option( 'connections_compatibility', $options );
					unset( $options );

					$options = get_option( 'connections_display_results' );
					$options['search_message'] = 1;

					update_option( 'connections_display_results', $options );
					unset( $options );

				/** @noinspection PhpMissingBreakStatementInspection */
				case ( version_compare( $version, '8.5.19', '<' ) ) :

					$options = get_option( 'connections_permalink' );

					$options['district_base'] = 'district';
					$options['county_base']   = 'county';

					update_option( 'connections_permalink', $options );
			}

			if ( NULL === $this->options->getDefaultTemplatesSet() ) $this->options->setDefaultTemplates();

			// Class used for managing role capabilities.
			if ( ! class_exists( 'cnRole' ) ) require_once CN_PATH . 'includes/admin/class.capabilities.php';

			if ( TRUE != $this->options->getCapabilitiesSet() ) {

				cnRole::reset();
				$this->options->defaultCapabilitiesSet( TRUE );
			}

			/**
			 * @todo NOTE: Update BUG!!!
			 *
			 *       If a user updates an old version of Connections while deactivated, when activated the db version will
			 *       be incremented and since the version is incremented to the current version NONE of the db update
			 *       routines will be run.
			 */

			// Increment the version number.
			$this->options->setVersion( CN_CURRENT_VERSION );

			// Save the options
			$this->options->saveOptions();

			/*
			 * This option is added for a check that will force a flush_rewrite() in connectionsLoad::adminInit() once.
			 * Should save the user from having to "save" the permalink settings.
			 */
			update_option( 'connections_flush_rewrite', '1' );
		}

		/**
		 * This is a deprecated helper function left in place until all instances of it are removed from the code base.
		 * This purposefully is blank.
		 *
		 * @access public
		 * @since unknown
		 * @deprecated 0.7.5
		 * @return void
		 */
		public function displayMessages() { /* Do nothing here */ }

		/**
		 * Set a runtime action/error message.
		 * This is a deprecated helper function left in place until all instances of it are removed from the code base.
		 *
		 * @access public
		 * @since unknown
		 * @deprecated 0.7.5
		 *
		 * @param $type    string
		 * @param $message string
		 *
		 * @return void
		 */
		public function setRuntimeMessage( $type, $message ) {
			cnMessage::runtime( $type, $message );
		}

		/**
		 * Store an error code.
		 * This is a deprecated helper function left in place until all instances of it are removed from the code base.
		 *
		 * @access public
		 * @since unknown
		 * @deprecated 0.7.5
		 *
		 * @param  $code string
		 *
		 * @return void
		 */
		public function setErrorMessage( $code ) {
			cnMessage::set( 'error', $code );
		}

		/**
		 * Store a success code.
		 * This is a deprecated helper function left in place until all instances of it are removed from the code base.
		 *
		 * @access public
		 * @since unknown
		 * @deprecated 0.7.5
		 *
		 * @param  $code string
		 *
		 * @return void
		 */
		public function setSuccessMessage( $code ) {
			cnMessage::set( 'success', $code );
		}

		/**
		 * Called when activating Connections via the activation hook.
		 */
		public static function activate() {

			/** @var $connections connectionsLoad */
			global $connections;

			require_once CN_PATH . 'includes/class.schema.php';

			// Create the table structure.
			cnSchema::create();

			// Create the required directories and attempt to make them writable.
			cnFileSystem::mkdirWritable( CN_CACHE_PATH );
			cnFileSystem::mkdirWritable( CN_IMAGE_PATH );
			// cnFileSystem::mkdirWritable( CN_CUSTOM_TEMPLATE_PATH );

			// Add a blank index.php file.
			cnFileSystem::mkIndex( CN_IMAGE_PATH );
			// cnFileSystem::mkIndex( CN_CUSTOM_TEMPLATE_PATH );

			// Add an .htaccess file, create it if one doesn't exist, and add the no indexes option.
			// cnFileSystem::noIndexes( CN_IMAGE_PATH ); // Causes some servers to respond w/ 403 when serving images.
			// cnFileSystem::noIndexes( CN_CUSTOM_TEMPLATE_PATH );

			$connections->initOptions();

			/*
			 * Add the page rewrite rules.
			 */
			add_filter( 'root_rewrite_rules', array( 'cnRewrite', 'addRootRewriteRules' ) );
			add_filter( 'page_rewrite_rules', array( 'cnRewrite', 'addPageRewriteRules' ) );

			// Flush so they are rebuilt.
			flush_rewrite_rules();
		}

		/**
		 * Called when deactivating Connections via the deactivation hook.
		 */
		public static function deactivate() {

			/*
			 * Since we're adding the rewrite rules using a filter, make sure to remove the filter
			 * before flushing, otherwise the rules will not be removed.
			 */
			remove_filter( 'root_rewrite_rules', array( 'cnRewrite', 'addRootRewriteRules' ) );
			remove_filter( 'page_rewrite_rules', array( 'cnRewrite', 'addPageRewriteRules' ) );

			// Flush so they are rebuilt.
			flush_rewrite_rules();
		}
	}

	/**
	 * The main function responsible for returning the Connections instance
	 * to functions everywhere.
	 *
	 * Use this function like you would a global variable, except without needing
	 * to declare the global.
	 *
	 * NOTE: Declaring an instance in the global @var $connections connectionsLoad to provide backward
	 * compatibility with many internal methods, template and extensions that expect it.
	 *
	 * Example: <?php $instance = Connections_Directory(); ?>
	 *
	 * @access public
	 * @since  0.7.9
	 * @global $connections
	 * @return connectionsLoad
	 */
	function Connections_Directory() {
		global $connections;

		$connections = connectionsLoad::instance();
		return $connections;
	}

	// Start Connections.
	Connections_Directory();
}
