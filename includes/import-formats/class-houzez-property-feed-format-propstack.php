<?php
/**
 * Class for managing the import process of a Propstack JSON file
 *
 * @package WordPress
 */
if ( class_exists( 'Houzez_Property_Feed_Process' ) ) {

class Houzez_Property_Feed_Format_Propstack extends Houzez_Property_Feed_Process {

	public function __construct( $instance_id = '', $import_id = '' )
	{
		$this->instance_id = $instance_id;
		$this->import_id = $import_id;

		if ( $this->instance_id != '' && isset($_GET['custom_property_import_cron']) )
	    {
	    	$current_user = wp_get_current_user();

	    	$this->log("Executed manually by " . ( ( isset($current_user->display_name) ) ? $current_user->display_name : '' ), '', 0, '', false );
	    }
	}

	public function parse()
	{
		$this->properties = array(); // Reset properties in the event we're importing multiple files

		$import_settings = houzez_property_feed_get_import_settings_from_id( $this->import_id );

		$pro_active = apply_filters( 'houzez_property_feed_pro_active', false );

		$current_page = 1;
		$more_properties = true;

		$statuses = ( isset($import_settings['statuses']) && !empty($import_settings['statuses']) && is_array($import_settings['statuses']) ) ? $import_settings['statuses'] : array( 'Vermarktung' );

		$limit = apply_filters( "houzez_property_feed_property_limit", 25 );
        if ( $limit !== false )
        {
        
        }
        else
        {
            // using pro, but check for limit setting
            if ( 
                $pro_active === true &&
                isset($import_settings['limit']) && 
                !empty((int)$import_settings['limit']) && 
                is_numeric($import_settings['limit'])
            )
            {
                $limit = (int)$import_settings['limit'];
            }
        }

		while ( $more_properties && $current_page < 99 )
		{			
			$this->log("Parsing properties on page " . $current_page);

			$url = 'https://api.propstack.de/v1/units?expand=1&per=50&page=' . $current_page;
			// api_key=' . $import_settings['api_key'] . '&

			$response = wp_remote_request(
				$url,
				array(
					'method' => 'GET',
					'timeout' => 360,
					'headers' => array(
						'X-API-KEY' => $import_settings['api_key']
					)
				)
			);

			if ( is_wp_error( $response ) )
			{
				$this->log_error( 'Response: ' . $response->get_error_message() );

				return false;
			}

			if ( wp_remote_retrieve_response_code($response) !== 200 )
		    {
	            $this->log_error( wp_remote_retrieve_response_code($response) . ' response received when requesting properties. Error message: ' . wp_remote_retrieve_response_message($response) );
	            return false;
	        }

			$json = json_decode( $response['body'], TRUE );

			if ( $json !== FALSE )
			{
				if ( !empty($json) )
				{
					foreach ($json as $property)
					{
						if ( $limit !== FALSE && count($this->properties) >= $limit )
		                {
		                    return true;
		                }
                
						if ( isset($property['property_status']['name']) && in_array($property['property_status']['name'], $statuses) )
						{
							$this->properties[] = $property;
						}
					}
				}
				else
				{
					$more_properties = false;
				}

				++$current_page;
			}
			else
			{
				// Failed to parse JSON
				$this->log_error( 'Failed to parse JSON.' );

				return false;
			}
		}

		if ( empty($this->properties) )
		{
			$this->log_error( 'No properties found. We\'re not going to continue as this could likely be wrong and all properties will get removed if we continue.' );

			return false;
		}

		return true;
	}

	public function import()
	{
		global $wpdb;

		$imported_ref_key = ( ( $this->import_id != '' ) ? '_imported_ref_' . $this->import_id : '_imported_ref' );
		$imported_ref_key = apply_filters( 'houzez_property_feed_property_imported_ref_key', $imported_ref_key, $this->import_id );

		$import_settings = houzez_property_feed_get_import_settings_from_id( $this->import_id );

		$pro_active = apply_filters( 'houzez_property_feed_pro_active', false );

		$this->import_start();

		do_action( "houzez_property_feed_pre_import_properties", $this->properties, $this->import_id );
        do_action( "houzez_property_feed_pre_import_properties_propstack", $this->properties, $this->import_id );

        $this->properties = apply_filters( "houzez_property_feed_properties_due_import", $this->properties, $this->import_id );
        $this->properties = apply_filters( "houzez_property_feed_properties_due_import_propstack", $this->properties, $this->import_id );

        $limit = apply_filters( "houzez_property_feed_property_limit", 25 );
        $additional_message = '';
        if ( $limit !== false )
        {
        	$this->properties = array_slice( $this->properties, 0, $limit );
        	$additional_message = '. <a href="https://houzezpropertyfeed.com/pricing" target="_blank">Upgrade to PRO</a> to import unlimited properties';
        }
        else
        {
        	// using pro, but check for limit setting
        	if ( isset($import_settings['limit']) && !empty((int)$import_settings['limit']) && is_numeric($import_settings['limit']) )
        	{
        		$this->properties = array_slice( $this->properties, 0, (int)$import_settings['limit'] );
        		$additional_message = '. Limited to ' . number_format((int)$import_settings['limit']) . ' properties due to advanced setting in <a href="' . admin_url('admin.php?page=houzez-property-feed-import&action=editimport&import_id=' . $this->import_id) . '">import settings</a>.';
        	}
        }

		$this->log( 'Beginning to loop through ' . count($this->properties) . ' properties' . $additional_message );

		$start_at_property = get_option( 'houzez_property_feed_property_' . $this->import_id );

		$property_row = 1;
		foreach ( $this->properties as $property )
		{
			if ( !empty($start_at_property) )
			{
				// we need to start on a certain property
				if ( $property['id'] == $start_at_property )
				{
					// we found the property. We'll continue for this property onwards
					$this->log( 'Previous import failed to complete. Continuing from property ' . $property_row . ' with ID ' . $property['id'] );
					$start_at_property = false;
				}
				else
				{
					++$property_row;
					continue;
				}
			}

			update_option( 'houzez_property_feed_property_' . $this->import_id, $property['id'], false );
			
			$this->log( 'Importing property ' . $property_row . ' with reference ' . $property['id'], $property['id'], 0, '', false );

			$this->ping(array('status' => 'importing', 'property' => $property_row, 'total' => count($this->properties)));

			$inserted_updated = false;

			$args = array(
	            'post_type' => 'property',
	            'posts_per_page' => 1,
	            'post_status' => 'any',
	            'meta_query' => array(
	            	array(
		            	'key' => $imported_ref_key,
		            	'value' => $property['id']
		            )
	            )
	        );
	        $property_query = new WP_Query($args);

	        $display_address = $property['title']['value'];
			if ( empty($display_address) )
			{
				$display_address = $property['short_address'];
			}

			$post_content = isset($property['long_description_note']['value']) && !empty($property['long_description_note']['value']) ? $property['long_description_note']['value'] : '';
	        
	        if ($property_query->have_posts())
	        {
	        	// We've imported this property before
	            while ($property_query->have_posts())
	            {
	                $property_query->the_post();

	                $post_id = get_the_ID();

	                $this->log( 'This property has been imported before. Updating it', $property['id'], $post_id );

	                $my_post = array(
				    	'ID'          	 => $post_id,
				    	'post_title'     => wp_strip_all_tags( $display_address ),
				    	'post_excerpt'   => isset($property['description_note']['value']) && !empty($property['description_note']['value']) ? $property['description_note']['value'] : '',
				    	'post_content' 	 => $post_content,
				    	'post_status'    => 'publish',
				  	);

				  	$my_post = apply_filters( 'houzez_property_feed_update_postarr', $my_post, $property, $this->import_id, $post_id );

				 	// Update the post into the database
				    $post_id = wp_update_post( $my_post, true );

				    if ( is_wp_error( $post_id ) ) 
					{
						$this->log_error( 'Failed to update post. The error was as follows: ' . $post_id->get_error_message(), $property['id'] );
					}
					else
					{
						$inserted_updated = 'updated';
					}
	            }
	        }
	        else
	        {
	        	$this->log( 'This property hasn\'t been imported before. Inserting it', $property['id'] );

	        	// We've not imported this property before
				$postdata = array(
					'post_excerpt'   => isset($property['description_note']['value']) && !empty($property['description_note']['value']) ? $property['description_note']['value'] : '',
					'post_content' 	 => $post_content,
					'post_title'     => wp_strip_all_tags( $display_address ),
					'post_status'    => 'publish',
					'post_type'      => 'property',
					'comment_status' => 'closed',
				);

				$postdata = apply_filters( 'houzez_property_feed_insert_postarr', $postdata, $property, $this->import_id );

				$post_id = wp_insert_post( $postdata, true );

				if ( is_wp_error( $post_id ) ) 
				{
					$this->log_error( 'Failed to insert post. The error was as follows: ' . $post_id->get_error_message(), $property['id'] );
				}
				else
				{
					$inserted_updated = 'inserted';
				}
			}
			$property_query->reset_postdata();

			if ( $inserted_updated !== false )
			{
				// Inserted property ok. Continue

				if ( $inserted_updated == 'updated' )
				{
					// Get all meta data so we can compare before and after to see what's changed
					$metadata_before = get_metadata('post', $post_id, '', true);

					// Get all taxonomy/term data
					$taxonomy_terms_before = array();
					$taxonomy_names = get_post_taxonomies( $post_id );
					foreach ( $taxonomy_names as $taxonomy_name )
					{
						$taxonomy_terms_before[$taxonomy_name] = wp_get_post_terms( $post_id, $taxonomy_name, array('fields' => 'ids') );
					}
				}

				$this->log( 'Successfully ' . $inserted_updated . ' post', $property['id'], $post_id );

				update_post_meta( $post_id, $imported_ref_key, $property['id'] );

				update_post_meta( $post_id, '_property_import_data', json_encode($property, JSON_PRETTY_PRINT) );

				$department = 'residential-sales';
				if ( isset($property['marketing_type']) && $property['marketing_type'] == 'RENT' ) { $department = 'residential-lettings'; }

				$poa = false;
				if ( 
					( isset($property["price_on_inquiry"]["value"]) && $property["price_on_inquiry"]["value"] === true )
				)
				{
					$poa = true;
				}

				if ( $poa === true ) 
                {
                    update_post_meta( $post_id, 'fave_property_price', 'POA');
                    update_post_meta( $post_id, 'fave_property_price_postfix', '' );
                }
                else
                {
            		$price = '';
            		if ( isset($property['price']['value']) && !empty($property['price']['value']) )
            		{
                		$price = round(preg_replace("/[^0-9.]/", '', $property['price']['value']));
                	}
                    update_post_meta( $post_id, 'fave_property_price_prefix', '' );
                    update_post_meta( $post_id, 'fave_property_price', $price );
                    update_post_meta( $post_id, 'fave_property_price_postfix', '' );
                }

                if ( isset($property['usable_floor_space']['value']) && !empty($property['usable_floor_space']['value']) )
                {
                	update_post_meta( $post_id, 'fave_property_size', $property['usable_floor_space']['value'] );
                	update_post_meta( $post_id, 'fave_property_size_prefix', 'Sq M' );
                }

                update_post_meta( $post_id, 'fave_property_bedrooms', ( ( isset($property['number_of_bed_rooms']['value']) ) ? $property['number_of_bed_rooms']['value'] : '' ) );
	            update_post_meta( $post_id, 'fave_property_bathrooms', ( ( isset($property['number_of_bath_rooms']['value']) ) ? $property['number_of_bath_rooms']['value'] : '' ) );
	            update_post_meta( $post_id, 'fave_property_rooms', '' );
	            update_post_meta( $post_id, 'fave_property_garage', '' );
	            update_post_meta( $post_id, 'fave_property_id', $property['id'] );

	            $address_parts = array();
	            if ( isset($property['street']) && $property['street'] != '' )
	            {
	                $address_parts[] = $property['street'];
	            }
	            if ( isset($property['district']['value']) && $property['district']['value'] != '' )
	            {
	                $address_parts[] = $property['district']['town'];
	            }
	            if ( isset($property['city']) && $property['city'] != '' )
	            {
	                $address_parts[] = $property['city'];
	            }
	            if ( isset($property['region']) && $property['region'] != '' )
	            {
	                $address_parts[] = $property['region'];
	            }
	            if ( isset($property['zip_code']) && $property['zip_code'] != '' )
	            {
	                $address_parts[] = $property['zip_code'];
	            }
	            $address_parts = array_unique($address_parts);

	            update_post_meta( $post_id, 'fave_property_map', '1' );
	            update_post_meta( $post_id, 'fave_property_map_address', implode(", ", $address_parts) );
	            $lat = '';
	            $lng = '';
	            if ( isset($property['lat']) && !empty($property['lat']) )
	            {
	                update_post_meta( $post_id, 'houzez_geolocation_lat', $property['lat'] );
	                $lat = $property['lat'];
	            }
	            if ( isset($property['lng']) && !empty($property['lng']) )
	            {
	                update_post_meta( $post_id, 'houzez_geolocation_long', $property['lng'] );
	                $lng = $property['lng'];
	            }
	            update_post_meta( $post_id, 'fave_property_location', $lat . "," . $lng . ",14" );
	            update_post_meta( $post_id, 'fave_property_country', 'DE' );

	            update_post_meta( $post_id, 'fave_property_address', implode(", ", $address_parts) );
	            update_post_meta( $post_id, 'fave_property_zip', ( ( isset($property['zip_code']) ) ? $property['zip_code'] : '' ) );

	            $featured = '0';
	            update_post_meta( $post_id, 'fave_featured', $featured );
	            update_post_meta( $post_id, 'fave_agent_display_option', ( isset($import_settings['agent_display_option']) ? $import_settings['agent_display_option'] : 'none' ) );

	            if ( 
	            	isset($import_settings['agent_display_option']) && 
	            	isset($import_settings['agent_display_option_rules']) && 
	            	is_array($import_settings['agent_display_option_rules']) && 
	            	!empty($import_settings['agent_display_option_rules']) 
	            )
	            {
		            switch ( $import_settings['agent_display_option'] )
		            {
		            	case "author_info":
		            	{
		            		foreach ( $import_settings['agent_display_option_rules'] as $rule )
		            		{
		            			$value_in_feed_to_check = '';
		            			switch ( $rule['field'] )
		            			{
		            				case "Broker ID":
		            				{
		            					$value_in_feed_to_check = $property['broker']['id'];
		            					break;
		            				}
		            				case "Broker Name":
		            				{
		            					$value_in_feed_to_check = $property['broker']['name'];
		            					break;
		            				}
		            			}

		            			if ( $value_in_feed_to_check == $rule['equal'] || $rule['equal'] == '*' )
		            			{
		            				// set post author
		            				$my_post = array(
								    	'ID'          	 => $post_id,
								    	'post_author'    => $rule['result'],
								  	);

								 	// Update the post into the database
								    wp_update_post( $my_post, true );

		            				break; // Rule matched. Lets not do anymore
		            			}
		            		}
		            		break;
		            	}
		            	case "agent_info":
		            	{
		            		foreach ( $import_settings['agent_display_option_rules'] as $rule )
		            		{
		            			$value_in_feed_to_check = '';
		            			switch ( $rule['field'] )
		            			{
		            				case "Broker ID":
		            				{
		            					$value_in_feed_to_check = $property['broker']['id'];
		            					break;
		            				}
		            				case "Broker Name":
		            				{
		            					$value_in_feed_to_check = $property['broker']['name'];
		            					break;
		            				}
		            			}

		            			if ( $value_in_feed_to_check == $rule['equal'] || $rule['equal'] == '*' )
		            			{
		            				update_post_meta( $post_id, 'fave_agents', $rule['result'] );
		            				break; // Rule matched. Lets not do anymore
		            			}
		            		}
		            		break;
		            	}
		            	case "agency_info":
		            	{
		            		foreach ( $import_settings['agent_display_option_rules'] as $rule )
		            		{
		            			$value_in_feed_to_check = '';
		            			switch ( $rule['field'] )
		            			{
		            				case "Broker ID":
		            				{
		            					$value_in_feed_to_check = $property['broker']['id'];
		            					break;
		            				}
		            				case "Broker Name":
		            				{
		            					$value_in_feed_to_check = $property['broker']['name'];
		            					break;
		            				}
		            			}

		            			if ( $value_in_feed_to_check == $rule['equal'] || $rule['equal'] == '*' )
		            			{
		            				update_post_meta( $post_id, 'fave_property_agency', $rule['result'] );
		            				break; // Rule matched. Lets not do anymore
		            			}
		            		}
		            		break;
		            	}
		            }
	        	}

				$mappings = ( isset($import_settings['mappings']) && is_array($import_settings['mappings']) && !empty($import_settings['mappings']) ) ? $import_settings['mappings'] : array();

				// status taxonomies
				$mapping_name = 'lettings_status';
				if ( $department == 'residential-sales' )
				{
					$mapping_name = 'sales_status';
				}

				$taxonomy_mappings = ( isset($mappings[$mapping_name]) && is_array($mappings[$mapping_name]) && !empty($mappings[$mapping_name]) ) ? $mappings[$mapping_name] : array();

				$status_field = str_replace('residential-', '', str_replace('sales', 'sale', $department));

				if ( isset($property['property_status']['name']) && !empty($property['property_status']['name']) )
				{
					if ( isset($taxonomy_mappings[$property['property_status']['name']]) && !empty($taxonomy_mappings[$property['property_status']['name']]) )
					{
						wp_set_object_terms( $post_id, (int)$taxonomy_mappings[$property['property_status']['name']], "property_status" );
					}
					else
					{
						$this->log( 'Received status of ' . $property['property_status']['name'] . ' that isn\'t mapped in the import settings', $property['id'], $post_id );

						$import_settings = $this->add_missing_mapping( $mappings, $mapping_name, $property['property_status']['name'], $this->import_id );
					}
				}

				// property type taxonomies
				$taxonomy_mappings = ( isset($mappings['property_type']) && is_array($mappings['property_type']) && !empty($mappings['property_type']) ) ? $mappings['property_type'] : array();

				if ( isset($property['rs_type']) && !empty($property['rs_type']) )
				{
					if ( isset($taxonomy_mappings[$property['rs_type']]) && !empty($taxonomy_mappings[$property['rs_type']]) )
					{
						wp_set_object_terms( $post_id, (int)$taxonomy_mappings[$property['rs_type']], "property_type" );
					}
					else
					{
						$this->log( 'Received property type of ' . $property['rs_type'] . ' that isn\'t mapped in the import settings', $property['id'], $post_id );

						$import_settings = $this->add_missing_mapping( $mappings, 'property_type', $property['rs_type'], $this->import_id );
					}
				}

				// Location taxonomies
				$create_location_taxonomy_terms = isset( $import_settings['create_location_taxonomy_terms'] ) ? $import_settings['create_location_taxonomy_terms'] : false;

				$houzez_tax_settings = get_option('houzez_tax_settings', array() );
				
				$location_taxonomies = array();
				if ( !isset($houzez_tax_settings['property_city']) || ( isset($houzez_tax_settings['property_city']) && $houzez_tax_settings['property_city'] != 'disabled' ) )
				{
					$location_taxonomies[] = 'property_city';
				}
				if ( !isset($houzez_tax_settings['property_area']) || ( isset($houzez_tax_settings['property_area']) && $houzez_tax_settings['property_area'] != 'disabled' ) )
				{
					$location_taxonomies[] = 'property_area';
				}
				if ( !isset($houzez_tax_settings['property_state']) || ( isset($houzez_tax_settings['property_state']) && $houzez_tax_settings['property_state'] != 'disabled' ) )
				{
					$location_taxonomies[] = 'property_state';
				}

				foreach ( $location_taxonomies as $location_taxonomy )
				{
					$address_field_to_use = isset( $import_settings[$location_taxonomy . '_address_field'] ) ? $import_settings[$location_taxonomy . '_address_field'] : '';
					if ( !empty($address_field_to_use) )
					{
						$location_term_ids = array();
						if ( isset($property['address'][$address_field_to_use]) && !empty($property['address'][$address_field_to_use]) )
		            	{
		            		$term = term_exists( trim($property['address'][$address_field_to_use]), $location_taxonomy);
							if ( $term !== 0 && $term !== null && isset($term['term_id']) )
							{
								$location_term_ids[] = (int)$term['term_id'];
							}
							else
							{
								if ( $create_location_taxonomy_terms === true )
								{
									$term = wp_insert_term( trim($property['address'][$address_field_to_use]), $location_taxonomy );
									if ( is_array($term) && isset($term['term_id']) )
									{
										$location_term_ids[] = (int)$term['term_id'];
									}
								}
							}
		            	}
		            	if ( !empty($location_term_ids) )
						{
							wp_set_object_terms( $post_id, $location_term_ids, $location_taxonomy );
						}
						else
						{
							wp_delete_object_term_relationships( $post_id, $location_taxonomy );
						}
					}
				}

				// Images
				if ( 
					apply_filters('houzez_property_feed_images_stored_as_urls', false, $post_id, $property, $this->import_id) === true ||
					apply_filters('houzez_property_feed_images_stored_as_urls_propstack', false, $post_id, $property, $this->import_id) === true
				)
				{
					$urls = array();

					if (isset($property['images']) && !empty($property['images']))
					{
						foreach ($property['images'] as $image)
						{
							if ( $pro_active === true )
							{
								if ( 
									isset($import_settings['limit_images']) && 
									!empty((int)$import_settings['limit_images']) && 
									is_numeric($import_settings['limit_images']) &&
									count($urls) >= $import_settings['limit_images']
								)
					        	{
					        		break;
					        	}
					        }

					        $size = apply_filters( 'houzez_property_feed_propstack_image_size', 'big' ); // false, 'big', 'medium'
					        $url = $image['url'];
					        if ( $size !== false && isset($image[$size . '_url']) )
					        {
					        	$url = $image[$size . '_url'];
					        }
							
							if ( 
								(
									substr( strtolower($url), 0, 2 ) == '//' || 
									substr( strtolower($url), 0, 4 ) == 'http'
								)
								&&
								$image['is_floorplan'] == false
								&&
								$image['is_private'] == false
							)
							{
								$urls[] = array(
									'url' => $url
								);
							}
						}
					}

					update_post_meta( $post_id, 'image_urls', $urls );
					update_post_meta( $post_id, 'images_stored_as_urls', true );

					$this->log( 'Imported ' . count($urls) . ' photo URLs', $property['id'], $post_id );
				}
				else
				{
					$media_ids = array();
					$new = 0;
					$existing = 0;
					$deleted = 0;
					$image_i = 0;
					$queued = 0;
					$previous_media_ids = get_post_meta( $post_id, 'fave_property_images' );

					$start_at_image_i = false;
					$previous_import_media_ids = get_option( 'houzez_property_feed_property_image_media_ids_' . $this->import_id );

					if ( !empty($previous_import_media_ids) )
					{
						// an import stopped previously whilst doing images. Check if it was this post
						$explode_previous_import_media_ids = explode("|", $previous_import_media_ids);
						if ( $explode_previous_import_media_ids[0] == $post_id )
						{
							// yes it was this property. now loop through the media already imported to ensure it's not imported again
							if ( isset($explode_previous_import_media_ids[1]) && !empty($explode_previous_import_media_ids[1]) )
							{
								$media_ids = explode(",", $explode_previous_import_media_ids[1]);
								$start_at_image_i = count($media_ids);

								$this->log( 'Imported ' . count($media_ids) . ' images before failing in the previous import. Continuing from here', $property['id'], $post_id );
							}
						}
					}

					if (isset($property['images']) && !empty($property['images']))
					{
						foreach ($property['images'] as $image)
						{
							if ( $pro_active === true )
							{
								if ( 
									isset($import_settings['limit_images']) && 
									!empty((int)$import_settings['limit_images']) && 
									is_numeric($import_settings['limit_images']) &&
									(count($media_ids) + $queued) >= $import_settings['limit_images']
								)
					        	{
					        		break;
					        	}
					        }
				        
							$size = apply_filters( 'houzez_property_feed_propstack_image_size', 'big' ); // false, 'big', 'medium'
					        $url = $image['url'];
					        if ( $size !== false && isset($image[$size . '_url']) )
					        {
					        	$url = $image[$size . '_url'];
					        }

							if ( 
								(
									substr( strtolower($url), 0, 2 ) == '//' || 
									substr( strtolower($url), 0, 4 ) == 'http'
								)
								&&
								$image['is_floorplan'] == false
								&&
								$image['is_private'] == false
							)
							{
								if ( $start_at_image_i !== false )
								{
									// we need to start at a specific image
									if ( $image_i < $start_at_image_i )
									{
										++$existing;
										++$image_i;
										continue;
									}
								}

								// This is a URL
								$description = ( (isset($image['title'])) ? $image['title'] : '' );
							    
								$filename = basename( $url );

								// Check, based on the URL, whether we have previously imported this media
								$imported_previously = false;
								$imported_previously_id = '';
								if ( is_array($previous_media_ids) && !empty($previous_media_ids) )
								{
									foreach ( $previous_media_ids as $previous_media_id )
									{
										if ( 
											get_post_meta( $previous_media_id, '_imported_url', TRUE ) == $url
										)
										{
											$imported_previously = true;
											$imported_previously_id = $previous_media_id;
											break;
										}
									}
								}

								if ($imported_previously)
								{
									$media_ids[] = $imported_previously_id;

									if ( $description != '' )
									{
										$my_post = array(
									    	'ID'          	 => $imported_previously_id,
									    	'post_title'     => $description,
									    );

									 	// Update the post into the database
									    wp_update_post( $my_post );
									}

									if ( $image_i == 0 ) set_post_thumbnail( $post_id, $imported_previously_id );

									++$existing;

									++$image_i;

									update_option( 'houzez_property_feed_property_image_media_ids_' . $this->import_id, $post_id . '|' . implode(",", $media_ids), false );
								}
								else
								{
									if ( apply_filters( 'houzez_property_feed_import_media', true, $this->import_id, $post_id, $property['id'], $url, $url, $description, 'image', $image_i, '' ) === true )
									{
										$this->ping();
										
										$tmp = download_url( $url );

									    $file_array = array(
									        'name' => $filename,
									        'tmp_name' => $tmp
									    );

									    // Check for download errors
									    if ( is_wp_error( $tmp ) ) 
									    {
									        $this->log_error( 'An error occurred whilst importing ' . $url . '. The error was as follows: ' . $tmp->get_error_message(), $property['id'], $post_id );
									    }
									    else
									    {
										    $id = media_handle_sideload( $file_array, $post_id, $description );

										    // Check for handle sideload errors.
										    if ( is_wp_error( $id ) ) 
										    {
										        @unlink( $file_array['tmp_name'] );
										        
										        $this->log_error( 'ERROR: An error occurred whilst importing ' . $url . '. The error was as follows: ' . $id->get_error_message(), $property['id'], $post_id );
										    }
										    else
										    {
										    	$media_ids[] = $id;

										    	update_post_meta( $id, '_imported_url', $url);

										    	if ( $image_i == 0 ) set_post_thumbnail( $post_id, $id );

										    	++$new;

										    	++$image_i;

										    	update_option( 'houzez_property_feed_property_image_media_ids_' . $this->import_id, $post_id . '|' . implode(",", $media_ids), false );
										    }
										}
									}
									else
									{
										++$queued;
										++$image_i;
									}
								}
							}
						}
					}
					if ( $media_ids != $previous_media_ids )
					{
						delete_post_meta( $post_id, 'fave_property_images' );
						foreach ( $media_ids as $media_id )
						{
							add_post_meta( $post_id, 'fave_property_images', $media_id );
						}
					}

					update_post_meta( $post_id, 'images_stored_as_urls', false );

					// Loop through $previous_media_ids, check each one exists in $media_ids, and if it doesn't then delete
					if ( is_array($previous_media_ids) && !empty($previous_media_ids) )
					{
						foreach ( $previous_media_ids as $previous_media_id )
						{
							if ( !in_array($previous_media_id, $media_ids) )
							{
								if ( wp_delete_attachment( $previous_media_id, TRUE ) !== FALSE )
								{
									++$deleted;
								}
							}
						}
					}

					$this->log( 'Imported ' . count($media_ids) . ' photos (' . $new . ' new, ' . $existing . ' existing, ' . $deleted . ' deleted)', $property['id'], $post_id );
					if ( $queued > 0 ) 
					{
						$this->log( $queued . ' photos added to download queue', $property['id'], $post_id );
					}
					
					update_option( 'houzez_property_feed_property_image_media_ids_' . $this->import_id, '', false );
				}

				// Floorplans
				$floorplans = array();

				if (isset($property['images']) && !empty($property['images']))
				{
					foreach ($property['images'] as $image)
					{
						if ( 
							isset($image['url']) && $image['url'] != ''
							&&
							(
								substr( strtolower($image['url']), 0, 2 ) == '//' || 
								substr( strtolower($image['url']), 0, 4 ) == 'http'
							)
							&&
							$image['is_floorplan'] == true
							&&
							$image['is_private'] == false
						)
						{
							$description = ( ( isset($image['title']) && !empty($image['title']) ) ? $image['title'] : __( 'Floorplan', 'houzezpropertyfeed' ) );

							$floorplans[] = array( 
								"fave_plan_title" => $description, 
								"fave_plan_image" => $image['url']
							);
						}
					}
				}

				if ( !empty($floorplans) )
				{
	                update_post_meta( $post_id, 'floor_plans', $floorplans );
	                update_post_meta( $post_id, 'fave_floor_plans_enable', 'enable' );
	            }
	            else
	            {
	            	update_post_meta( $post_id, 'fave_floor_plans_enable', 'disable' );
	            }

				$this->log( 'Imported ' . count($floorplans) . ' floorplans', $property['id'], $post_id );

				do_action( "houzez_property_feed_property_imported", $post_id, $property, $this->import_id, $this->instance_id );
				do_action( "houzez_property_feed_property_imported_propstack", $post_id, $property, $this->import_id, $this->instance_id );

				$post = get_post( $post_id );
				do_action( "save_post_property", $post_id, $post, false );
				do_action( "save_post", $post_id, $post, false );

				if ( $inserted_updated == 'updated' )
				{
					$this->compare_meta_and_taxonomy_data( $post_id, $property['id'], $metadata_before, $taxonomy_terms_before );
				}
			}

			++$property_row;

		} // end foreach property

		do_action( "houzez_property_feed_post_import_properties_propstack", $this->import_id );

		$this->import_end();
	}

	public function remove_old_properties()
	{
		global $wpdb, $post;

		if ( !empty($this->properties) )
		{
			$import_refs = array();
			foreach ($this->properties as $property)
			{
				$import_refs[] = $property['id'];
			}

			$this->do_remove_old_properties( $import_refs );

			unset($import_refs);
		}
	}
}

}