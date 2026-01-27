<?php
/**
 * Plugin Name: Houzez Property Feed
 * Plugin Uri: https://houzezpropertyfeed.com
 * Description: Automatically import properties to Houzez from estate agency CRMs and export to portals
 * Version: 2.5.40
 * Author: PropertyHive
 * Author URI: https://wp-property-hive.com
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Houzez_Property_Feed' ) ) :

final class Houzez_Property_Feed {

    /**
     * @var string
     */
    public $version = '2.5.40';

    /**
     * @var Houzez Property Feed The single instance of the class
     */
    protected static $_instance = null;
    
    /**
     * Main Houzez Property Feed Instance
     *
     * Ensures only one instance of Houzez Property Feed is loaded or can be loaded.
     *
     * @static
     * @return Houzez Property Feed - Main instance
     */
    public static function instance() 
    {
        if ( is_null( self::$_instance ) ) 
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {

        // Define constants
        $this->define_constants();

        // Include required files
        $this->includes();

    }

    /**
     * Define Houzez_Property_Feed Constants
     */
    private function define_constants() 
    {
        define( 'HOUZEZ_PROPERTY_FEED_PLUGIN_FILE', __FILE__ );
        define( 'HOUZEZ_PROPERTY_FEED_VERSION', $this->version );
    }

    private function includes()
    {
        include_once( 'includes/class-houzez-property-feed-install.php' );
        include_once( 'includes/class-houzez-property-feed-cron.php' );
        include_once( 'includes/class-houzez-property-feed-redirect.php' );
        include_once( 'includes/class-houzez-property-feed-license.php' );
        include_once( 'includes/class-houzez-property-feed-import.php' );
        include_once( 'includes/class-houzez-property-feed-export-enquiries.php' );
        include_once( 'includes/class-houzez-property-feed-export.php' );
        include_once( 'includes/class-houzez-property-feed-settings.php' );
        include_once( 'includes/class-houzez-property-feed-ajax.php' );
        include_once( 'includes/class-houzez-property-feed-wpml.php' );

        include_once( 'includes/import-functions.php' );
        include_once( 'includes/export-functions.php' );
        include_once( 'includes/format-functions.php' );
        include_once( 'includes/frequency-functions.php' );
        include_once( 'includes/xml-functions.php' );
        include_once( 'includes/array-functions.php' );
        include_once( 'includes/country-functions.php' );

        include_once( 'includes/class-houzez-property-feed-process.php' );

        include_once( 'includes/export-formats/class-houzez-property-feed-format-rtdf.php' );
        include_once( 'includes/export-formats/class-houzez-property-feed-format-zoopla.php' );

        if ( version_compare(PHP_VERSION, '8.0', '>=') ) 
        {
            include_once( 'lib/jsonpath/JSONPath.php' );
            include_once( 'lib/jsonpath/JSONPathException.php' );
            include_once( 'lib/jsonpath/JSONPathLexer.php' );
            include_once( 'lib/jsonpath/JSONPathToken.php' );
            include_once( 'lib/jsonpath/AccessHelper.php' );
            include_once( 'lib/jsonpath/Filters/AbstractFilter.php' );
            include_once( 'lib/jsonpath/Filters/IndexesFilter.php' );
            include_once( 'lib/jsonpath/Filters/IndexFilter.php' );
            include_once( 'lib/jsonpath/Filters/QueryMatchFilter.php' );
            include_once( 'lib/jsonpath/Filters/QueryResultFilter.php' );
            include_once( 'lib/jsonpath/Filters/RecursiveFilter.php' );
            include_once( 'lib/jsonpath/Filters/SliceFilter.php' );
        }

        if ( is_admin() ) 
        {
            include_once( 'includes/class-houzez-property-feed-admin.php' );
        }
    }

}

endif;

/**
 * Returns the main instance of Houzez_Property_Feed to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return Houzez_Property_Feed
 */
function HPF() {
    return Houzez_Property_Feed::instance();
}

HPF();