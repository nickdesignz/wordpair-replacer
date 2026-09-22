<?php
/**
 * Plugin Name:       WordPair Replacer
 * Plugin URI:        https://nickdesignz.de/wordpair-replacer/
 * Description:       Replace words or phrases dynamically in the frontend and style replacements globally or individually with generated CSS.
 * Version:           2.3.5
 * Requires at least: 6.0
 * Tested up to:      6.9
 * Requires PHP:      8.0
 * Author:            NickDesignz
 * Author URI:        https://nickdesignz.de/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wordpair-replacer
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPR_VERSION', '2.3.5' );
define( 'WPR_PLUGIN_FILE', __FILE__ );
define( 'WPR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPR_TEXTDOMAIN', 'wordpair-replacer' );

require_once WPR_PLUGIN_DIR . 'includes/class-wpr-settings.php';
require_once WPR_PLUGIN_DIR . 'includes/class-wpr-i18n.php';
require_once WPR_PLUGIN_DIR . 'includes/class-wpr-activator.php';
require_once WPR_PLUGIN_DIR . 'includes/class-wpr-css.php';
require_once WPR_PLUGIN_DIR . 'includes/class-wpr-replacer.php';
require_once WPR_PLUGIN_DIR . 'admin/class-wpr-admin.php';

register_activation_hook( __FILE__, array( 'WPR_Activator', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		WPR_I18n::init();
		WPR_Activator::maybe_upgrade();

		new WPR_Admin();
		new WPR_Replacer();
	}
);
