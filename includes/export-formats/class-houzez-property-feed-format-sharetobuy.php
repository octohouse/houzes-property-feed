<?php
/**
 * Class for managing the export process of a ShareToBuy file
 *
 * @package WordPress
 */
if ( class_exists( 'Houzez_Property_Feed_Process' ) ) {

class Houzez_Property_Feed_Format_ShareToBuy extends Houzez_Property_Feed_Process {

	public function __construct()
	{
		$this->is_import = false;

		add_action( 'save_post', array( $this, 'send_realtime_feed_request' ), 99 );

        add_filter( 'houzez_before_submit_property', array( $this, 'remove_save_post_hook' ) );
        add_filter( 'houzez_before_update_property', array( $this, 'remove_save_post_hook' ) );

        add_action( 'houzez_after_property_submit', array( $this, 'send_realtime_feed_request' ), 99 );
        add_action( 'houzez_after_property_update', array( $this, 'send_realtime_feed_request' ), 99 );

        add_action( 'houzez_property_feed_push_all', array( $this, 'push_all_properties' ) );
	}

    public function remove_save_post_hook($new_property)
    {
        remove_action( 'save_post', array( $this, 'send_realtime_feed_request' ), 99 );
        return $new_property;
    }

    private function delete_old_logs()
    {
        global $wpdb;

        $keep_logs_days = (string)apply_filters( 'houzez_property_feed_keep_logs_days', '1' );

        // Revert back to 1 days if anything other than numbers has been passed
        // This prevent SQL injection and errors
        if ( !preg_match("/^\d+$/", $keep_logs_days) )
        {
            $keep_logs_days = '1';
        }

        // Delete logs older than 1 days
        $wpdb->query( "DELETE FROM " . $wpdb->prefix . "houzez_property_feed_export_logs_instance WHERE start_date < DATE_SUB(NOW(), INTERVAL " . $keep_logs_days . " DAY)" );
        $wpdb->query( "DELETE FROM " . $wpdb->prefix . "houzez_property_feed_export_logs_instance_log WHERE log_date < DATE_SUB(NOW(), INTERVAL " . $keep_logs_days . " DAY)" );
    }

    public function send_realtime_feed_request( $post_id ) 
    {
        global $wpdb;

        if ( $post_id == null )
            return;

        if ( get_post_type($post_id) != 'property' )  
            return; 

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
          return;

        // If this is just a revision, don't make request.
        if ( wp_is_post_revision( $post_id ) )
            return;

        if ( get_post_status( $post_id ) == 'auto-draft' )
            return;

        $this->delete_old_logs();

        global $post;  

        if ( empty( $post ) )
            $post = get_post($post_id);

        $options = get_option( 'houzez_property_feed', array() );
        $exports = ( isset($options['exports']) && is_array($options['exports']) && !empty($options['exports']) ) ? $options['exports'] : array();

        if ( is_array($exports) && !empty($exports) )
        {
            // remove any non-cron formats
            foreach ( $exports as $export_id => $export_settings  )
            {
                $format = houzez_property_feed_get_format_from_export_id( $export_id );
                if ( $export_settings['format'] != 'sharetobuy' )
                {
                    //remove non-sharetobuy exports from being processed
                    unset($exports[$export_id]);
                }
            }
        }

        if ( apply_filters( 'houzez_property_feed_pro_active', false ) === true )
        {
            
        }
        else
        {
            // ensure only one export if pro not active
            foreach ( $exports as $export_id => $export_settings )
            {
                if ( !isset($export_settings['running']) || ( isset($export_settings['running']) && $export_settings['running'] !== true ) )
                {
                    continue;
                }

                if ( isset($export_settings['deleted']) && $export_settings['deleted'] === true )
                {
                    continue;
                }

                $exports = array( $export_id => $export_settings );
                break;
            }
        }

        foreach ( $exports as $export_id => $export_settings )
        {
            $this->export_id = $export_id;

            if ( !isset($export_settings['running']) || ( isset($export_settings['running']) && $export_settings['running'] !== true ) )
            {
                continue;
            }

            if ( isset($export_settings['deleted']) && $export_settings['deleted'] === true )
            {
                continue;
            }

            // decide if we need to do a SEND or REMOVE
            $property_send_request_send = false;

            // Get property
            $args = array(
                'post_type' => 'property',
                'nopaging' => true,
                'p' => $post_id,
                'post_status' => 'publish',
            );

            $meta_query = array();
            $tax_query = array();

            $args['meta_query'] = $meta_query;
            $args['tax_query'] = $tax_query;

            $args = apply_filters( 'houzez_property_feed_export_property_args', $args, $this->export_id );
            $args = apply_filters( 'houzez_property_feed_export_sharetobuy_property_args', $args, $this->export_id );

            $property_query = new WP_Query( $args );

            if ($property_query->have_posts())
            {
                while ($property_query->have_posts())
                {
                    $property_query->the_post();

                    // log instance start
                    $current_date = new DateTimeImmutable( 'now', new DateTimeZone('UTC') );
                    $current_date = $current_date->format("Y-m-d H:i:s");

                    $wpdb->insert( 
                        $wpdb->prefix . "houzez_property_feed_export_logs_instance", 
                        array(
                            'export_id' => $export_id,
                            'start_date' => $current_date
                        )
                    );
                    $this->instance_id = $wpdb->insert_id;

                    $department = $this->get_department( $post->ID );

                    $property_send_request_send = true;

                    $ok_to_send = true;
                    $limit = apply_filters( "houzez_property_feed_property_limit", 25 );
                    if ( $limit !== false )
                    {
                        // TO DO: Check number of active properties. Might need to store internally wich ones have been pushed
                        /*if ( count($response['property']) >= $limit )
                        {
                            $this->log_error($limit . ' or more properties already found to be active. You\'ll need to remove properties first before being able to send this one. <a href="https://houzezpropertyfeed.com/pricing" target="_blank">Upgrade to PRO</a> to export more', '', $post->ID);
                            $ok_to_send = false;
                        }*/
                    }

                    if ( $ok_to_send )
                    {
                        $success = $this->create_send_property_request( $post->ID );

                        /*if ($success === FALSE)
                        {
                            add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99, 2 );
                        }*/
                    }

                    // log instance end
                    $current_date = new DateTimeImmutable( 'now', new DateTimeZone('UTC') );
                    $current_date = $current_date->format("Y-m-d H:i:s");

                    $wpdb->update( 
                        $wpdb->prefix . "houzez_property_feed_export_logs_instance", 
                        array( 
                            'end_date' => $current_date
                        ),
                        array( 'id' => $this->instance_id )
                    );

                    $this->instance_id = null;
                }
            }

            wp_reset_postdata();

            if ( !$property_send_request_send )
            {
                // send request not sent. Must need to remove it
                $success = $this->create_remove_property_request( $post_id );

                /*if ($success === FALSE)
                {
                    add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99, 2 );
                }*/
            }
        }
    }

    public function create_send_property_request( $post_id, $force = false )
    {
        $export_settings = houzez_property_feed_get_export_settings_from_id( $this->export_id );

        $department = $this->get_department( $post_id );

        $request_data = array();

        $request_data['label'] = get_post_meta( $post_id, 'fave_property_id', TRUE );
        //$request_data['development_id'] = '';

        $address_fields = array();
        $address_taxonomies = array( 'property_state', 'property_city', 'property_area' );
        foreach ( $address_taxonomies as $address_taxonomy )
        {
            $terms = get_the_terms( $post_id, $address_taxonomy );
            $term_ids_to_use = array();
            if ( !is_wp_error($terms) && !empty($terms) )
            {
                foreach ( $terms as $term )
                {
                    $address_fields[] = $term->name;
                    break;
                }
            }
        }
        $request_data['postcode'] = get_post_meta( $post_id, 'fave_property_zip', true );
        $request_data['address'] = get_post_meta( $post_id, 'fave_property_address', TRUE );
        //$request_data['address_two'] = '';
        $request_data['city'] = isset($address_fields[0]) ? $address_fields[0] : '';
        //$request_data['borough_id'] = '';
        //$request_data['region_id'] = '';

        $fave_property_location = get_post_meta($post_id, 'fave_property_location', true);
        $explode_fave_property_location = explode(",", $fave_property_location);
        $lat = '';
        $lng = '';
        if ( count($explode_fave_property_location) >= 2 )
        {
            $lat = $explode_fave_property_location[0];
            $lng = $explode_fave_property_location[1];
        }
        $request_data['latitude'] = $lat;
        $request_data['longitude'] = $lng;

        //$request_data['scheme_type'] = '';
        //$request_data['affordable_homes_programme'] = '';

        $availability = $this->get_export_mapped_value($post_id, 'property_status');
        $request_data['offer_status'] = ( ( $availability != '' ) ? $availability : '1' );

        $type = $this->get_export_mapped_value($post_id, 'property_type');
        $explode_type = explode("-", $type);
        $property_type = '';
        $property_subtype = '';
        if ( count($explode_type) == 2 )
        {
            $property_type = $explode_type[0];
            $property_subtype = $explode_type[1];
        }
        $request_data['property_type'] = $property_type;
        $request_data['property_subtype_id'] = $property_subtype;

        $request_data['price'] = get_post_meta( $post_id, 'fave_property_price', TRUE );

        /*$request_data['example_share'] = '';
        $request_data['min_share_percentage'] = '';
        $request_data['max_share_percentage'] = '';
        $request_data['min_deposit_override'] = '';
        $request_data['rent_amount_or_percentage'] = '';
        $request_data['rent'] = '';
        $request_data['rent_percentage'] = '';
        $request_data['rent_is_adaptive'] = '';
        $request_data['discount_percentage'] = '';
        $request_data['service_fee'] = '';*/

        $request_data['summary'] = strip_tags(get_the_excerpt($post_id));
        $full_description = get_the_content($post_id);
        if ( trim(strip_tags($full_description)) == '' )
        {
            $full_description = nl2br(get_the_excerpt());
        }
        $request_data['description'] = $full_description;

        $features = array();
        $term_list = wp_get_post_terms($post_id, 'property_feature', array("fields" => "all"));
        if ( !is_wp_error($term_list) && is_array($term_list) && !empty($term_list) )
        {
            foreach ( $term_list as $term )
            {
                $features[] = $term->name;
            }
        }
        $request_data['key_features'] = implode(", ", $features);

        //$request_data['floor'] = '';
        $request_data['bedrooms'] = get_post_meta( $post_id, 'fave_property_bedrooms', TRUE );

        /*$request_data['accessibility_type'] = '';
        $request_data['property_eligibility'] = '';
        $request_data['sales_phone'] = '';
        $request_data['sales_email'] = '';
        $request_data['responder_email_id'] = '';
        $request_data['video_enable'] = '';
        $request_data['youtube_video_id'] = '';
        $request_data['vimeo_video_id'] = '';
        $request_data['open_day_summary'] = '';
        $request_data['open_day_date'] = '';*/
        
        /*$request_data['material_information'] = '';
        $request_data['parking'] = '';
        $request_data['pets'] = '';
        $request_data['virtual_tour'] = '';
        $request_data['walkly_tour_url'] = '';
        $request_data['tenure_type_id'] = '';
        $request_data['lease_length'] = '';
        $request_data['council_tax_band_id'] = '';
        $request_data['total_rooms'] = '';
        $request_data['rooms_details'] = '';
        $request_data['furnished_id'] = '';
        $request_data['washing_machine'] = '';
        $request_data['dishwasher'] = '';
        $request_data['fridge_freezer'] = '';
        $request_data['parking'] = '';
        $request_data['parking_type_id'] = '';
        $request_data['garden'] = '';
        $request_data['outside_space_id'] = '';
        $request_data['year_property_was_built'] = '';
        $request_data['unit_size'] = '';
        $request_data['communal_lift'] = '';
        $request_data['accessible_measures'] = '';
        $request_data['accessible_measures_ids'] = array();
        $request_data['flooded_within_last_five_years'] = '';
        $request_data['flooded_within_last_five_years_details'] = '';
        $request_data['listed_property'] = '';
        $request_data['heating_type_ids'] = array();
        $request_data['sewerage_supply_type_id'] = '';
        $request_data['water_supply_type_id'] = '';
        $request_data['electricity_supply_type_id'] = '';
        $request_data['broadband_type_id'] = '';*/

        $media = array();
        $attachment_ids = get_post_meta( $post_id, 'fave_property_images' );
        if ( is_array($attachment_ids) && !empty($attachment_ids) )
        {
            foreach ( $attachment_ids as $attachment_id )
            {
                if ( !wp_attachment_is_image($attachment_id) )
                {
                    continue;
                }

                $url = wp_get_attachment_image_src( $attachment_id, 'full' );
                if ( $url !== FALSE )
                {
                    $media[] = array(
                        'type' => 'image',
                        'url' => $url[0]
                    );
                }
            }
        }
        $request_data['media'] = $media;

        $attachments = array();
        $floorplans = get_post_meta( $post_id, 'floor_plans', true );
        if ( is_array($floorplans) && !empty($floorplans) )
        {
            foreach ($floorplans as $floorplan)
            {
                $url = ( isset($floorplan['fave_plan_image']) ? $floorplan['fave_plan_image'] : '' );
                if ( !empty($url) )
                {
                    $attachments[] = array(
                        'type' => 'floorplan',
                        'url' => $url
                    );
                }
            }
        }
        $attachment_ids = get_post_meta( $post->ID, 'fave_attachments' );
        if ( is_array($attachment_ids) && !empty($attachment_ids) )
        {
            foreach ( $attachment_ids as $attachment_id )
            {
                $url = wp_get_attachment_url( $attachment_id );
                if ($url !== FALSE)
                {
                    $attachments[] = array(
                        'type' => 'brochure',
                        'url' => $url
                    );
                }
            }
        }
        $request_data['attachments'] = $attachments;

        //$request_data['faqs'] = array();

        $request_data = apply_filters( 'houzez_property_feed_export_property_data', $request_data, $post_id, $this->export_id );
        $request_data = apply_filters( 'houzez_property_feed_export_sharetobuy_property_data', $request_data, $post_id, $this->export_id );

        $do_request = true;
        /*if ( $force === false && isset($export_settings['only_send_if_different']) && $export_settings['only_send_if_different'] == 'yes' )
        {
            $previous_hash = get_post_meta( $post_id, '_sharetobuy_sha1_' . $export_settings['environment'] . '_' . $this->export_id, TRUE );

            $request_data_to_check = $request_data;

            if ( $previous_hash == sha1(json_encode($request_data_to_check)) )
            {
                // Matches the data sent last time. Don't send again
                $do_request = false;
            }
        }*/

        if ( $do_request )
        {
            $endpoint = '/api/V2/housing/property';

            $stb_id = get_post_meta( $post_id, '_sharetobuy_' . $export_settings['environment'] . '_' . $this->export_id . '_id', true );
            if ( !empty($stb_id) )
            {
                // Pushed to this environment before. Use ID
                $endpoint .= '/' . $stb_id;
            }
            $response = $this->do_curl_request( $request_data, $export_settings['environment'], $endpoint, 'POST', $post_id );

            if ( $response !== FALSE )
            {
                $request_data_to_check = $request_data;

                // Request was successful
                // Save the SHA-1 hash so we know for next time whether to push it again or not
                update_post_meta( $post_id, '_sharetobuy_sha1_' . $export_settings['environment'] . '_' . $this->export_id, sha1(json_encode($request_data_to_check)) );

                update_post_meta( $post_id, '_sharetobuy_' . $export_settings['environment'] . '_' . $this->export_id . '_id', $response['property']['property_id'] );
                
            }
        }
        else
        {
            $request = true;
        }
    }

    public function create_remove_property_request( $post_id )
    {
        global $wpdb;
        
        $export_settings = houzez_property_feed_get_export_settings_from_id( $this->export_id );

        $department = $this->get_department( $post_id );

        $response = true;

        $stb_id = get_post_meta( $post_id, '_sharetobuy_' . $export_settings['environment'] . '_' . $this->export_id . '_id', true );

        if ( empty($stb_id) )
        {
            // Not previously sent so no need to remove
            return false;
        }

        $request_data = array();

        // log instance start
        $current_date = new DateTimeImmutable( 'now', new DateTimeZone('UTC') );
        $current_date = $current_date->format("Y-m-d H:i:s");

        $wpdb->insert( 
            $wpdb->prefix . "houzez_property_feed_export_logs_instance", 
            array(
                'export_id' => $this->export_id,
                'start_date' => $current_date
            )
        );
        $this->instance_id = $wpdb->insert_id;

        $response = $this->do_curl_request( $request_data, $export_settings['environment'], '/api/V2/housing/property/' . $stb_id, 'DELETE', $post_id );

        if ( $response !== FALSE )
        {
            $request_data_to_check = $request_data;

            // Request was successful
            // Save the SHA-1 hash so we know for next time whether to push it again or not
            update_post_meta( $post_id, '_sharetobuy_sha1_' . $export_settings['environment'] . '_' . $this->export_id, sha1(json_encode($request_data_to_check)) );
        
            update_post_meta( $post_id, '_sharetobuy_' . $export_settings['environment'] . '_' . $this->export_id . '_id', '' );
        }

        // log instance end
        $current_date = new DateTimeImmutable( 'now', new DateTimeZone('UTC') );
        $current_date = $current_date->format("Y-m-d H:i:s");

        $wpdb->update( 
            $wpdb->prefix . "houzez_property_feed_export_logs_instance", 
            array( 
                'end_date' => $current_date
            ),
            array( 'id' => $this->instance_id )
        );

        $this->instance_id = null;

        return $response;
    }

    public function do_curl_request( $request_data, $environment, $api_url, $method = 'POST', $post_id, $log_success = true, $log_errors = true ) 
    {
        $export_settings = houzez_property_feed_get_export_settings_from_id( $this->export_id );

        if ( apply_filters( 'houzez_property_feed_export_sharetobuy_perform_request', true ) !== true )
        {
            if ($log_errors && !empty($this->instance_id)) $this->log_error("Disabling request due to houzez_property_feed_export_sharetobuy_perform_request filter", '', $post_id);
            return false;
        }

        $ch = curl_init();

        $api_base_url = 'https://api.sandbox.sharetobuy.com';
        if ( $environment == 'live' )
        {
            $api_base_url = 'https://api2.sharetobuy.com';
        }

        $signature = hash_hmac(
            'sha256',
            http_build_query(['apiKey' => $export_settings['api_key']]),
            $export_settings['private_key']
        );

        $full_api_url = $api_base_url . $api_url . '?apiKey=' . $export_settings['api_key'] . '&sig=' . $signature;

        $response = wp_remote_request($full_api_url, array(
            'method' => $method,
            'headers' => array('Content-Type' => 'multipart/form-data'),
            'body' => $request_data
        ));

        if ( is_wp_error( $response ) ) 
        {
            $error_message = $response->get_error_message();

            if ($log_errors && !empty($this->instance_id)) $this->log_error("Error sending " . $method . " request to " . $full_api_url . ": " . $error_message, '', $post_id);

            return false;
        }

        $body = wp_remote_retrieve_body( $response );

        if ( empty( $body ) ) 
        {
            if ($log_errors && !empty($this->instance_id)) $this->log_error('Empty response body', '', $post_id);

            return false;
        }

        $data = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) 
        {
            if ($log_errors && !empty($this->instance_id)) $this->log_error('JSON decode error: ' . json_last_error_msg() . '. Response: ' . $body, '', $post_id);

            return false;
        }

        if ( isset( $data['result'] ) ) 
        {
            if ( $data['result'] === true )
            {
                if ($log_success && !empty($this->instance_id)) 
                { 
                    $this->log($method . " request successful to " . $full_api_url, '', $post_id);
                    $this->log("Response: " . print_r($body, true), '', $post_id); 
                }

                return $data;
            }
            else
            {
                if ($log_errors && !empty($this->instance_id)) $this->log_error('Error returned when making ' . $method . ' request to ' . $full_api_url . '. Response: ' . $body, '', $post_id);

                return false;
            }
        }
        else
        {
            if ($log_errors && !empty($this->instance_id)) $this->log_error('Missing result in body. Response: ' . $body, '', $post_id);

            return false;
        }

        return $response;
    }

    private function get_department( $post_id )
    {
        $department = 'sales';

        $options = get_option( 'houzez_property_feed' , array() );
        $sales_statuses = ( isset($options['sales_statuses']) && is_array($options['sales_statuses']) && !empty($options['sales_statuses']) ) ? $options['sales_statuses'] : array();
        $lettings_statuses = ( isset($options['lettings_statuses']) && is_array($options['lettings_statuses']) && !empty($options['lettings_statuses']) ) ? $options['lettings_statuses'] : array();

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

        return $department;
    }

    public function push_all_properties()
    {
        global $wpdb, $post;

        $export_id = !empty($_GET['export_id']) ? (int)$_GET['export_id'] : '';
        $this->export_id = $export_id;

        // Check this export_id is a ShareToBuy feed
        $export_settings = houzez_property_feed_get_export_settings_from_id( $this->export_id );

        if ( !isset($export_settings['format']) || $export_settings['format'] !== 'sharetobuy' )
        {
            return;
        }

        $this->delete_old_logs();

        // log instance start
        $current_date = new DateTimeImmutable( 'now', new DateTimeZone('UTC') );
        $current_date = $current_date->format("Y-m-d H:i:s");

        $wpdb->insert( 
            $wpdb->prefix . "houzez_property_feed_export_logs_instance", 
            array(
                'export_id' => $export_id,
                'start_date' => $current_date
            )
        );
        $this->instance_id = $wpdb->insert_id;

        $this->log("Pushing all properties");

        // Get properties
        $args = array(
            'post_type' => 'property',
            'nopaging' => true,
            'post_status' => 'publish',
        );

        $meta_query = array();
        $tax_query = array();

        $args['meta_query'] = $meta_query;
        $args['tax_query'] = $tax_query;

        $args = apply_filters( 'houzez_property_feed_export_property_args', $args, $this->export_id );
        $args = apply_filters( 'houzez_property_feed_export_sharetobuy_property_args', $args, $this->export_id );

        $property_query = new WP_Query( $args );

        $this->log("Found " . $property_query->found_posts . " active properties");

        if ($property_query->have_posts())
        {
            while ($property_query->have_posts())
            {
                $property_query->the_post();

                $ok_to_send = true;

                // TO DO: Check limit

                if ( $ok_to_send )
                {
                    $success = $this->create_send_property_request( $post->ID, true );
                }
            }
        }

        wp_reset_postdata();

        $this->log("Finished pushing all active properties");

        // log instance end
        $current_date = new DateTimeImmutable( 'now', new DateTimeZone('UTC') );
        $current_date = $current_date->format("Y-m-d H:i:s");

        $wpdb->update( 
            $wpdb->prefix . "houzez_property_feed_export_logs_instance", 
            array( 
                'end_date' => $current_date
            ),
            array( 'id' => $this->instance_id )
        );
    }
}

}

new Houzez_Property_Feed_Format_ShareToBuy();