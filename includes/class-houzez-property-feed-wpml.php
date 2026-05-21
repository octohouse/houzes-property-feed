<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Houzez Property Feed WPML Functions
 */
class Houzez_Property_Feed_WPML {

    private $original_language = '';

	public function __construct() 
    {
        add_action( 'wpml_loaded', array( $this, 'run_wpml_hooks') );
	}

    public function run_wpml_hooks()
    {
        $this->original_language = apply_filters( 'wpml_current_language', null );

        // Do WPML stuff. Should only come in here if WPML is active
        add_filter( 'houzez_property_feed_export_kyero_property_data', array( $this, 'kyero_wpml_bits' ), 10, 3 );
        add_action( 'pre_get_posts', array( $this, 'switch_language' ) );
        add_action( 'houzez_property_feed_export_cron_end', array( $this, 'revert_language' ), 10, 2 );
    }

    public function switch_language()
    {
        if ( defined('HPF_EXPORT') && HPF_EXPORT === true ) 
        {
            do_action( 'wpml_switch_language', apply_filters('wpml_default_language', NULL ) );
        }
    }

    public function revert_language( $instance_id, $export_id )
    {
        do_action( 'wpml_switch_language', $this->original_language );
        remove_action( 'pre_get_posts', array( $this, 'switch_language' ) );
    }

    public function kyero_wpml_bits( $property_xml, $post_id, $export_id )
    {
        $languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );

        if ( !empty($languages) )
        {   
            // URLs 
            unset($property_xml->url);
            
            $url_xml = $property_xml->addChild('url');
            
            foreach ( $languages as $isocode => $language )
            {
                $url_xml->addChild( $isocode, apply_filters( 'wpml_permalink', get_permalink($post_id), $isocode ) );
            }

            // Descriptions
            unset($property_xml->desc);

            $desc_xml = $property_xml->addChild('desc');

            foreach ( $languages as $isocode => $language )
            {
                $language_post_id = apply_filters( 'wpml_object_id', $post_id, 'post', FALSE, $isocode );

                if ( !empty($language_post_id) )
                {
                    $description = get_the_content(null, false, $language_post_id);
                    if ( trim(strip_tags($description)) == '' )
                    {
                        $description = get_the_excerpt($language_post_id);
                    }
                    $description = str_replace("&nbsp;", " ", $description);

                    $description = trim($description);

                    // Replace Gutenberg block comments (start & end tags)
                    $description = preg_replace('/<!--\s*wp:.*?-->/s', '', $description);
                    $description = preg_replace('/<!--\s*\/wp:.*?-->/s', '', $description);

                    // Convert <p> tags to new lines
                    $description = str_replace(array('<p>', '</p>'), "\n", $description);

                    // Convert <br> tags to new lines
                    $description = str_replace(array('<br>', '<br/>', '<br />'), "\n", $description);

                    // Strip remaining HTML tags but keep newlines
                    $description = strip_tags($description);

                    // Trim excess spaces and normalize multiple new lines
                    $description = preg_replace("/\n+/", "\n", trim($description));

                    $desc_xml->addChild( $isocode, htmlspecialchars($description, ENT_QUOTES | ENT_XML1, 'UTF-8') );
                }
            }
        }

        return $property_xml;
    }
}

new Houzez_Property_Feed_WPML();