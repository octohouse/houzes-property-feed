<?php
/**
 * Class for managing the export process of a thinkSPAIN XML file
 *
 * @package WordPress
 */
if ( class_exists( 'Houzez_Property_Feed_Process' ) ) {

class Houzez_Property_Feed_Format_Thinkspain extends Houzez_Property_Feed_Process {

	public function __construct( $instance_id = '', $export_id = '' )
	{
		$this->instance_id = $instance_id;
		$this->export_id = $export_id;
		$this->is_import = false;

		if ( $this->instance_id != '' && isset($_GET['custom_property_export_cron']) )
	    {
	    	$current_user = wp_get_current_user();

	    	$this->log("Executed manually by " . ( ( isset($current_user->display_name) ) ? $current_user->display_name : '' ) );
	    }
	}

	public function export()
	{
		global $wpdb, $post;

        $this->log("Starting export");

		$export_settings = houzez_property_feed_get_export_settings_from_id( $this->export_id );

		$options = get_option( 'houzez_property_feed' , array() );
		$sales_statuses = ( isset($options['sales_statuses']) && is_array($options['sales_statuses']) && !empty($options['sales_statuses']) ) ? $options['sales_statuses'] : array();
		$lettings_statuses = ( isset($options['lettings_statuses']) && is_array($options['lettings_statuses']) && !empty($options['lettings_statuses']) ) ? $options['lettings_statuses'] : array();

        $wp_upload_dir = wp_upload_dir();
        if( $wp_upload_dir['error'] !== FALSE )
        {
            $this->log_error("Unable to create uploads folder. Please check permissions");
            return false;
        }
        else
        {
            $uploads_dir = $wp_upload_dir['basedir'] . '/houzez_property_feed_export/';

            if ( ! @file_exists($uploads_dir) )
            {
                if ( ! @mkdir($uploads_dir) )
                {
                    $this->log_error("Unable to create directory " . $uploads_dir);
                    return false;
                }
            }
            else
            {
                if ( ! @is_writeable($uploads_dir) )
                {
                    $this->log_error("Directory " . $uploads_dir . " isn't writeable");
                    return false;
                }
            }
        }

        // Get properties
        // Don't send if no properties
        $args = array(
            'post_type' => 'property',
            'post_status' => 'publish',
            'fields' => 'ids',
        );

        $limit = apply_filters( "houzez_property_feed_property_limit", 25 );
        $additional_message = '';
        if ( $limit !== false )
        {
            $additional_message = '. <a href="https://houzezpropertyfeed.com/pricing" target="_blank">Upgrade to PRO</a> to import unlimited properties';
            $this->log( 'Exporting up to ' . $limit . ' properties' . $additional_message );
            $args['posts_per_page'] = $limit;
        }
        else
        {
            $args['nopaging'] = true;
        }

        $args = apply_filters( 'houzez_property_feed_export_property_args', $args, $this->export_id );
        $args = apply_filters( 'houzez_property_feed_export_thinkspain_property_args', $args, $this->export_id );

        $properties_query = new WP_Query( $args );
        $num_properties = $properties_query->found_posts;

        $xml = new SimpleXMLExtendedHpf("<?xml version=\"1.0\" encoding=\"utf-8\"?><root></root>");

        $thinkspain_xml = $xml->addChild('thinkspain');
        $thinkspain_xml->addChild('import_version');
        $thinkspain_xml->import_version = '1.16';

        $agent_xml = $xml->addChild('agent');
        $agent_xml->addChild('name');
        $agent_xml->name = get_bloginfo('name');

        $allowed_extensions = apply_filters( 'houzez_property_feed_thinkspain_export_allowed_file_extensions', array('gif', 'jpeg', 'jpg', 'png') );

        if ( $properties_query->have_posts() )
        {
            $this->log( "Beginning to iterate through properties" );

            while ( $properties_query->have_posts() )
            {
                $properties_query->the_post();

                $post_id = get_the_ID();

                $this->log("Doing property", '', $post_id);

                $department = 'sales';
                $status_terms = get_the_terms( $post_id, 'property_status' );
                if ( !is_wp_error($status_terms) && !empty($status_terms) )
                {
                	foreach ( $status_terms as $term )
                	{
                		if ( in_array($term->term_id, $sales_statuses) )
                		{
                			$department = 'sales';
                		}
                		elseif ( in_array($term->term_id, $lettings_statuses) )
                		{
                			$department = 'lettings';
                		}
                	}
                }

                $property_xml = $xml->addChild('property');

                $property_xml->addChild('last_amended_date', get_the_modified_date('Y-m-d H:i:s', $post_id));
                $property_xml->addChild('unique_id', $post_id);
                $property_xml->addChild('agent_ref', (get_post_meta($post_id, 'fave_property_id', true) != '' ? get_post_meta($post_id, 'fave_property_id', true) : $post_id));

                $raw_price = get_post_meta($post_id, 'fave_property_price', true);
                $currency = 'EUR';

                if ( fave_option('multi_currency') == '1' )
                {
                    $default_multi_currency = fave_option('default_multi_currency');
                    if ( !empty($default_multi_currency) && strlen($default_multi_currency) == 3 )
                    {
                        $currency = strtoupper($default_multi_currency);
                    }
                    $prop_currency = get_post_meta($post_id, 'fave_currency', true);
                    if ( !empty($prop_currency) && strlen($prop_currency) == 3 )
                    {
                        $currency = strtoupper($prop_currency);
                    }
                }
                else
                {
                    $symbol = fave_option('currency_symbol');
                    switch ($symbol)
                    {
                        case "£": $currency = 'GBP'; break;
                        case "$": $currency = 'USD'; break;
                        default:  $currency = 'EUR';
                    }
                }

                $euro_price = $raw_price;

                if ( strtoupper($currency) !== 'EUR' )
                {
                    $euro_price = apply_filters('houzez_property_feed_thinkspain_convert_to_euro', $raw_price, $currency, $post_id);
                }

                $property_xml->addChild('euro_price', is_numeric($euro_price) ? $euro_price : '');
                $property_xml->addChild('currency', strtoupper($currency));

                $sale_type = ($department === 'lettings') ? 'rent' : 'sale';
                $property_xml->addChild('sale_type', $sale_type);

                $property_type = $this->get_export_mapped_value($post_id, 'property_type');
                $property_xml->addChild('property_type', $property_type);

                $street_full = get_post_meta($post_id, 'fave_property_address', true);
                if ( empty($street_full) )
                {
                    $street_full = get_post_meta($post_id, 'fave_property_map_street', true);
                }

                $street_name = $street_full;
                $street_number = '';

                if ( preg_match('/^\s*(\d+[A-Za-z\-\/]?)\s+(.*)$/', (string)$street_full, $m) )
                {
                    $street_number = $m[1];
                    $street_name = $m[2];
                }

                $property_xml->addChild('street_name', htmlspecialchars($street_name, ENT_QUOTES | ENT_XML1, 'UTF-8'));
                $property_xml->addChild('street_number', htmlspecialchars($street_number, ENT_QUOTES | ENT_XML1, 'UTF-8'));

                $address_fields = array();
                foreach ( array('property_state','property_city','property_area') as $tax )
                {
                    $terms = get_the_terms($post_id, $tax);
                    if ( !is_wp_error($terms) && !empty($terms) )
                    {
                        $address_fields[] = $terms[0]->name;
                    }
                }

                $province = isset($address_fields[0]) ? $address_fields[0] : '';
                $town = isset($address_fields[1]) ? $address_fields[1] : ( $province ?: '' );

                $property_xml->addChild('town', htmlspecialchars($town, ENT_QUOTES | ENT_XML1, 'UTF-8'));
                $property_xml->addChild('province', htmlspecialchars($province, ENT_QUOTES | ENT_XML1, 'UTF-8'));

                $postcode = get_post_meta($post_id, 'fave_property_zip', true);
                $property_xml->addChild('postcode', htmlspecialchars($postcode, ENT_QUOTES | ENT_XML1, 'UTF-8'));

                $fave_property_location = get_post_meta($post_id, 'fave_property_location', true);
                $explode_fave_property_location = explode(",", $fave_property_location);
                $lat = '';
                $lng = '';
                if ( count($explode_fave_property_location) >= 2 )
                {
                    $lat = $explode_fave_property_location[0];
                    $lng = $explode_fave_property_location[1];
                }

                if ( $lat !== '' && $lng !== '' )
                {
                    $location_xml = $property_xml->addChild('location');
                    $location_xml->addChild('latitude', $lat);
                    $location_xml->addChild('longitude', $lng);
                }

                $property_xml->addChild('url', get_permalink($post_id));

                $description = get_the_content(null, false, $post_id);
                if ( trim(strip_tags($description)) == '' )
                {
                    $description = get_the_excerpt($post_id);
                }
                $description = str_replace("&nbsp;", " ", $description);
                $description = trim($description);
                $description = preg_replace('/<!--\s*wp:.*?-->/s', '', $description);
                $description = preg_replace('/<!--\s*\/wp:.*?-->/s', '', $description);

                $desc_xml = $property_xml->addChild('description');
                $desc_xml->addChild('en', htmlspecialchars($description, ENT_QUOTES | ENT_XML1, 'UTF-8'));

                $attachment_ids = get_post_meta($post_id, 'fave_property_images');
                if ( !empty($attachment_ids) )
                {
                    $attachment_ids = array_slice($attachment_ids, 0, 50);
                    $images_xml = $property_xml->addChild('images');

                    $allowed_extensions = apply_filters('houzez_property_feed_thinkspain_export_allowed_file_extensions', array('gif','jpeg','jpg','png'));
                    $i = 0;

                    foreach ( $attachment_ids as $aid )
                    {
                        if ( !wp_attachment_is_image($aid) )
                        {
                            continue;
                        }

                        $image_url = wp_get_attachment_url($aid);
                        $base = explode('?', $image_url)[0];
                        $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));

                        if ( !in_array($ext, $allowed_extensions, true) )
                        {
                            $this->log("Skipping image (extension not allowed): " . $image_url, '', $post_id);
                            continue;
                        }

                        $i++;
                        $photo_xml = $images_xml->addChild('photo');
                        $photo_xml->addAttribute('id', $i);
                        $photo_xml->addChild('url', $image_url);
                    }
                }

                $property_xml->addChild('media');

                $bedrooms = get_post_meta($post_id, 'fave_property_bedrooms', true);
                if ( $bedrooms !== '' )
                {
                    $property_xml->addChild('bedrooms', $bedrooms);
                }

                $bathrooms = get_post_meta($post_id, 'fave_property_bathrooms', true);
                if ( $bathrooms !== '' )
                {
                    $property_xml->addChild('bathrooms', $bathrooms);
                }

                $term_list = wp_get_post_terms($post_id, 'property_feature', array('fields' => 'all'));
                if ( !is_wp_error($term_list) && !empty($term_list) )
                {
                    $features_xml = $property_xml->addChild('features');
                    foreach ( $term_list as $term )
                    {
                        $features_xml->addChild('feature', htmlspecialchars($term->name, ENT_QUOTES | ENT_XML1, 'UTF-8'));
                    }
                }

                $property_xml = apply_filters( 'houzez_property_feed_export_property_data', $property_xml, $post_id, $this->export_id );
                $property_xml = apply_filters( 'houzez_property_feed_export_thinkspain_property_data', $property_xml, $post_id, $this->export_id );

                $this->log("Property written to thinkSPAIN XML file", '', $post_id);
            }
        }

        $xml = $xml->asXML();

        // Write XML string to file
        $filename = $this->export_id . '.xml';
        $filename = apply_filters( 'houzez_property_feed_export_thinkspain_url_filename', $filename, $this->export_id );
        $handle = fopen($uploads_dir . $filename, 'w+');
        fwrite($handle, $xml);
        fclose($handle);

        $this->log('XML updated: <a href="' . $wp_upload_dir['baseurl'] . '/houzez_property_feed_export/' . $this->export_id . '.xml" target="_blank">View generated XML</a>');

        return true;
	}

}

}