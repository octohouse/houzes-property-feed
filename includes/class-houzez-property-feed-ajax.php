<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Houzez Property Feed Ajax Functions
 */
class Houzez_Property_Feed_Ajax {

	public function __construct() 
    {
        add_action( "wp_ajax_houzez_property_feed_fetch_xml_nodes", array( $this, "fetch_xml_nodes" ) );

        add_action( "wp_ajax_houzez_property_feed_fetch_csv_fields", array( $this, "fetch_csv_fields" ) );

        add_action( "wp_ajax_houzez_property_feed_draw_automatic_imports_table", array( $this, "draw_automatic_imports_table" ) );

        add_action( "wp_ajax_houzez_property_feed_get_running_status", array( $this, "get_running_status" ) );

        add_action( "wp_ajax_houzez_property_feed_import_properties_batch", array( $this, "import_properties_batch" ) );
        add_action( "wp_ajax_nopriv_houzez_property_feed_import_properties_batch", array( $this, "import_properties_batch" ) );

        add_action( "wp_ajax_houzez_property_feed_import_import", array( $this, "import_import" ) );

        add_action( 'wp_ajax_houzez_property_feed_test_property_import_details', array( $this, 'test_property_import_details' ) );
	}

    public function fetch_xml_nodes()
    {
        header( 'Content-Type: application/json; charset=utf-8' );

        if ( !wp_verify_nonce( sanitize_text_field(wp_unslash($_GET['ajax_nonce'])), "hpf_ajax_nonce" ) ) 
        {
            $return = array(
                'success' => false,
                'error' => __( 'Invalid nonce provided', 'houzezpropertyfeed' )
            );
            echo wp_json_encode($return);
            die();
        } 

        // nonce ok. Let's get the XML

        $contents = '';

        $args = array( 'timeout' => 120, 'sslverify' => false );
        $args = apply_filters( 'houzez_property_feed_xml_request_args', $args, sanitize_url($_GET['url']) );
        $response = wp_remote_get( sanitize_url($_GET['url']), $args );
        if ( !is_wp_error($response) && is_array( $response ) ) 
        {
            $contents = $response['body'];
        }
        else
        {
            $error = __( 'Failed to obtain XML. Dump of response as follows', 'houzezpropertyfeed' ) . ': ' . print_r($response, TRUE);
            if ( is_wp_error($response) )
            {
                $error = $response->get_error_message();
            }
            $return = array(
                'success' => false,
                'error' => $error
            );
            echo wp_json_encode($return);
            die();
        }

        $xml = simplexml_load_string($contents);

        if ($xml !== FALSE)
        {
            $node_names = houzez_property_feed_get_all_node_names($xml, array_merge(array(''), $xml->getNamespaces(true)));
            $node_names = array_unique($node_names);

            $return = array(
                'success' => true,
                'nodes' => $node_names
            );
            echo wp_json_encode($return);
            die();
        }
        else
        {
            // Failed to parse XML
            $return = array(
                'success' => false,
                'error' => __( 'Failed to parse XML file', 'houzezpropertyfeed' ) . ': ' . print_r($contents, TRUE)
            );
            echo wp_json_encode($return);
            die();
        }

        wp_die();
    }

    public function fetch_csv_fields()
    {
        header( 'Content-Type: application/json; charset=utf-8' );

        if ( !wp_verify_nonce( sanitize_text_field(wp_unslash($_GET['ajax_nonce'])), "hpf_ajax_nonce" ) ) 
        {
            $return = array(
                'success' => false,
                'error' => __( 'Invalid nonce provided', 'houzezpropertyfeed' )
            );
            echo wp_json_encode($return);
            die();
        } 

        // nonce ok. Let's get the XML

        $contents = '';

        $args = array( 'timeout' => 120, 'sslverify' => false );
        $args = apply_filters( 'houzez_property_feed_csv_request_args', $args, sanitize_url($_GET['url']) );
        $response = wp_remote_get( sanitize_url($_GET['url']), $args );
        if ( !is_wp_error($response) && is_array( $response ) ) 
        {
            $contents = $response['body'];
        }
        else
        {
            $error = __( 'Failed to obtain CSV. Dump of response as follows', 'houzezpropertyfeed' ) . ': ' . print_r($response, TRUE);
            if ( is_wp_error($response) )
            {
                $error = $response->get_error_message();
            }
            $return = array(
                'success' => false,
                'error' => $error
            );
            echo wp_json_encode($return);
            die();
        }

        $encoding = mb_detect_encoding($contents, 'UTF-8, ISO-8859-1', true);
        if ( $encoding !== 'UTF-8' )
        {
            $contents = mb_convert_encoding($contents, 'UTF-8', $encoding);
        }

        $lines = explode( "\n", $contents );
        $headers = str_getcsv( array_shift( $lines ), ( isset($_GET['delimiter']) ? sanitize_text_field($_GET['delimiter']) : ',' ) );

        $return = array(
            'success' => true,
            'fields' => $headers
        );
        echo wp_json_encode($return);

        wp_die();
    }

    public function draw_automatic_imports_table()
    {
        if ( !wp_verify_nonce( sanitize_text_field(wp_unslash($_GET['ajax_nonce'])), "hpf_ajax_nonce" ) ) 
        {
            echo 'Failed to verify nonce. Please reload the page';
            die();
        }

        include( dirname(HOUZEZ_PROPERTY_FEED_PLUGIN_FILE) . '/includes/class-houzez-property-feed-admin-automatic-imports-table.php' );

        $automatic_imports_table = new Houzez_Property_Feed_Admin_Automatic_Imports_Table();
        $automatic_imports_table->prepare_items();

        echo $automatic_imports_table->display();

        wp_die();
    }

    public function get_running_status()
    {
        header( 'Content-Type: application/json; charset=utf-8' );

        if ( !wp_verify_nonce( sanitize_text_field(wp_unslash($_GET['ajax_nonce'])), "hpf_ajax_nonce" ) ) 
        {
            $return = array(
                'success' => false,
                'error' => __( 'Invalid nonce provided', 'houzezpropertyfeed' )
            );
            echo wp_json_encode($return);
            die();
        }

        if ( !isset($_GET['import_ids']) || empty($_GET['import_ids']) )
        {
            $return = array(
                'success' => false,
                'error' => __( 'No import ID(s) passed', 'houzezpropertyfeed' )
            );
            echo wp_json_encode($return);
            die();
        }

        global $wpdb;

        $statuses = array();

        $failed = false;

        $pro_active = apply_filters( 'houzez_property_feed_pro_active', false );

        $options = get_option( 'houzez_property_feed' , array() );

        $queued_media = array();
        $queued_properties = array();
        if ( $pro_active === true )
        {
            if ( isset($options['media_processing']) && $options['media_processing'] === 'background' )
            {
                $media_queue_counts = $wpdb->get_results(
                    "
                    SELECT 
                        `import_id`, 
                        COUNT(DISTINCT `post_id`, `media_type`, `media_order`) AS `queued_media_count` 
                    FROM
                        " . $wpdb->prefix . "houzez_property_feed_media_queue 
                    GROUP BY 
                    `import_id`
                    "
                );
                if ( count($media_queue_counts) > 0 )
                {
                    foreach ( $media_queue_counts as $media_queue_count )
                    {
                        $queued_media[(int)$media_queue_count->import_id] = (int)$media_queue_count->queued_media_count;
                    }
                }
            }
        }

        foreach ( $_GET['import_ids'] as $import_id )
        {
            $import_id = (int)$import_id;

            $import = houzez_property_feed_get_import_settings_from_id( $import_id );
            if ( $import === false )
            {
                continue;
            }
            $format = houzez_property_feed_get_import_format( $import['format'] );
            if ( $format === false )
            {
                continue;
            }

            $status = '';

            $row = $wpdb->get_row( $wpdb->prepare("
                SELECT 
                    end_date, status, status_date, media
                FROM 
                    {$wpdb->prefix}houzez_property_feed_logs_instance
                WHERE 
                    import_id = %d
                ORDER BY start_date DESC 
                LIMIT 1
            ", $import_id), ARRAY_A );
            if ( null !== $row )
            {
                if ( isset($row['end_date']) && $row['end_date'] != '0000-00-00 00:00:00' )
                {
                    $statuses[$import_id] = array( 
                        'status' => 'finished', 
                        'queued_media' => ( isset($queued_media[$import_id]) ? $queued_media[$import_id] : '' ) 
                    );
                    continue;
                }

                if ( isset($row['media']) && $row['media'] == '1' )
                {
                    $status = '<br>Importing media';
                    $statuses[$import_id] = array( 
                        'status' => $status, 
                        'queued_media' => ( isset($queued_media[$import_id]) ? $queued_media[$import_id] : '' ) 
                    );
                    continue;
                }

                if ( isset($row['status_date']) && $row['status_date'] != '0000-00-00 00:00:00' && isset($row['status']) && !empty($row['status']) )
                {
                    $decoded_status = json_decode($row['status'], true);

                    if ( isset($decoded_status['status']) && $decoded_status['status'] == 'importing' )
                    {
                        if ( ( ( time() - strtotime($row['status_date']) ) / 60 ) < 5 )
                        {
                            $property = isset($decoded_status['property']) ? (int)$decoded_status['property'] : 0;
                            $total = isset($decoded_status['total']) ? (int)$decoded_status['total'] : 1; // Default to 1 to avoid division by zero
                            $progress = ($property / $total) * 100;
                            
                            $status = '
                            <br>Importing property ' . $property . '/' . $total . '
                            <div class="progress-bar-container" style="width: 100%; background-color: #f3f3f3; border-radius: 5px; overflow: hidden; margin-top: 5px;">
                                <div class="progress-bar" style="width: ' . $progress . '%; height: 8px; background-color: #4caf50; text-align: center; line-height: 20px;"></div>
                            </div>';
                        }
                        else
                        {
                            $status = '<br>Failed to complete';

                            $failed = $import_id;
                        }
                    }
                    elseif ( isset($decoded_status['status']) && $decoded_status['status'] == 'parsing' )
                    {
                        if ( ( ( time() - strtotime($row['status_date']) ) / 60 ) < 5 )
                        {
                            $status = '<br>Parsing properties';
                        }
                        else
                        {
                            $status = '<br>Failed to complete';

                            $failed = $import_id;
                        }
                    }
                    elseif ( isset($decoded_status['status']) && $decoded_status['status'] == 'removing' )
                    {
                        $status = '<br>Removing properties';
                    }
                    elseif ( isset($decoded_status['status']) && $decoded_status['status'] == 'finished' )
                    {
                        $status = 'finished';
                    }
                }
            }

            if ( $pro_active === true )
            {
                if ( isset($format['background_mode']) && $format['background_mode'] === true )
                {
                    if ( isset($import['background_mode']) && $import['background_mode'] == 'yes' )
                    {
                        $queued_properties[$import_id] = 0;

                        $queued_properties_query = $wpdb->get_results(
                            $wpdb->prepare("
                            SELECT 
                                `id`
                            FROM
                                " . $wpdb->prefix . "houzez_property_feed_property_queue 
                            WHERE
                                `import_id` = %d
                            AND
                                `status` = 'pending'
                            ", (int)$import_id)
                        );
                        if ( count($queued_properties_query) > 0 )
                        {
                            $queued_properties[$import_id] = count($queued_properties_query);
                        }
                    }
                }
            }

            $statuses[$import_id] = array( 
                'status' => $status, 
                'queued_media' => ( isset($queued_media[$import_id]) ? $queued_media[$import_id] : '' ) ,
                'queued_properties' => ( isset($queued_properties[$import_id]) ? $queued_properties[$import_id] : 0 ) 
            );
        }

        if ( $failed !== false )
        {
            $_GET['custom_property_import_cron'] = 'houzezpropertyfeedcronhook';
            $_GET['import_id'] = $failed;

            ob_start();
            do_action('houzezpropertyfeedcronhook');
            ob_end_clean();
        }

        echo wp_json_encode($statuses);

        wp_die();
    }

    public function import_properties_batch()
    {
        global $wpdb;

        $batch_size = (int)apply_filters( 'houzez_property_feed_background_mode_batch_size', 10 );

        $property_queue = $wpdb->get_results(
            $wpdb->prepare("
            SELECT
                *
            FROM
                " . $wpdb->prefix . "houzez_property_feed_property_queue
            WHERE
                `status` = 'pending'
            ORDER BY
                `instance_id`, `date_queued`
            LIMIT %d
            ", $batch_size)
        );

        if ( !empty($property_queue) ) 
        {
            $last_instance_id = false; // Use to track if we're doing a new instance or not

            // Yes, there are queued items. Process $batch size and then fork the process again
            foreach ( $property_queue as $property_queue_row )
            {
                if ( $property_queue_row->instance_id != $last_instance_id )
                {
                    // we've finished once instance and moving onto the next
                    // At this point there should be no processed
                    // and then delete all queue entries
                    if ( $last_instance_id !== false )
                    {
                        // We have done one before. Run import on properties in batch
                        $import_object->import();

                        // Get all processed properties. We should only have processed properties at this point
                        $processed_property_queue = $wpdb->get_results(
                            $wpdb->prepare("
                            SELECT
                                crm_id, import_id
                            FROM
                                " . $wpdb->prefix . "houzez_property_feed_property_queue
                            WHERE
                                `status` = 'processed' AND 
                                `instance_id` = %d
                            ", (int)$last_instance_id)
                        );

                        if ( $processed_property_queue ) 
                        {
                            if ( apply_filters( 'houzez_property_feed_remove_old_properties', true, $processed_property_queue[0]->import_id ) === true )
                            {
                                $import_refs = array();
                                foreach ($processed_property_queue as $processed_property)
                                {
                                    $import_refs[] = $processed_property->crm_id;
                                }

                                $import_object->do_remove_old_properties( $import_refs );

                                unset($import_refs);
                            }
                        }

                        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}houzez_property_feed_property_queue WHERE instance_id = %d", $last_instance_id));

                        // log instance end
                        $current_date = new DateTimeImmutable( 'now', new DateTimeZone('UTC') );
                        $current_date = $current_date->format("Y-m-d H:i:s");

                        $wpdb->update( 
                            $wpdb->prefix . "houzez_property_feed_logs_instance", 
                            array( 
                                'end_date' => $current_date,
                                'status' => wp_json_encode(array('status' => 'finished')),
                                'status_date' => $current_date
                            ),
                            array( 'id' => $last_instance_id )
                        );

                        do_action( 'houzez_property_feed_cron_end', $instance_id, $import_id );
                        do_action( 'houzez_property_feed_import_cron_end', $instance_id, $import_id );
                    }

                    $import_id = (int)$property_queue_row->import_id;

                    if ( isset($_GET['import_ids']) && !empty($_GET['import_ids']) )
                    {
                        $explode_import_ids = explode("|", sanitize_text_field($_GET['import_ids']));

                        $explode_import_ids = array_filter($explode_import_ids, function($value) use ($import_id) {
                            return (int)$value !== $import_id;
                        });

                        $_GET['import_ids'] = implode("|", $explode_import_ids);
                    }

                    $import_settings = houzez_property_feed_get_import_settings_from_id( $import_id );

                    $import_object = hpf_get_import_object_from_format($import_settings['format'], $property_queue_row->instance_id, $import_id);
                    $import_object->background_mode = true;

                    $all_property_queue = $wpdb->get_results(
                        $wpdb->prepare("
                        SELECT
                            id
                        FROM
                            " . $wpdb->prefix . "houzez_property_feed_property_queue
                        WHERE
                            `instance_id` = %d
                        ", (int)$property_queue_row->instance_id)
                    );

                    $import_object->total_properties = count($all_property_queue);

                    $import_object->ping();
                }

                $data = $this->convert_database_data_to_property( $property_queue_row->data );

                $import_object->properties[] = $data;

                $last_instance_id = $property_queue_row->instance_id;
            }

            $import_object->ping();

            $import_object->import();

            // Need to cater for where finished on an exact number that matches the batch size
            $processed_property_queue = $wpdb->get_results(
                $wpdb->prepare("
                SELECT
                    crm_id, import_id
                FROM
                    " . $wpdb->prefix . "houzez_property_feed_property_queue
                WHERE
                    `status` = 'processed' AND 
                    `instance_id` = %d
                ", (int)$last_instance_id)
            );

            $all_property_queue = $wpdb->get_results(
                $wpdb->prepare("
                SELECT
                    crm_id, import_id
                FROM
                    " . $wpdb->prefix . "houzez_property_feed_property_queue
                WHERE
                    `instance_id` = %d
                ", (int)$last_instance_id)
            );

            $import_object->ping();

            // if number of processed matches all queued then we can assume we're done
            if ( count($processed_property_queue) == count($all_property_queue) )
            {
                if ( $processed_property_queue ) 
                {
                    if ( apply_filters( 'houzez_property_feed_remove_old_properties', true, $processed_property_queue[0]->import_id ) === true )
                    {
                        $import_refs = array();
                        foreach ($processed_property_queue as $processed_property)
                        {
                            $import_refs[] = $processed_property->crm_id;
                        }

                        $import_object->do_remove_old_properties( $import_refs );

                        unset($import_refs);
                    }
                }

                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}houzez_property_feed_property_queue WHERE instance_id = %d", $last_instance_id));

                // log instance end
                $current_date = new DateTimeImmutable( 'now', new DateTimeZone('UTC') );
                $current_date = $current_date->format("Y-m-d H:i:s");

                $wpdb->update( 
                    $wpdb->prefix . "houzez_property_feed_logs_instance", 
                    array( 
                        'end_date' => $current_date,
                        'status' => wp_json_encode(array('status' => 'finished')),
                        'status_date' => $current_date
                    ),
                    array( 'id' => $last_instance_id )
                );

                do_action( 'houzez_property_feed_cron_end', $last_instance_id, $import_id );
                do_action( 'houzez_property_feed_import_cron_end', $last_instance_id, $import_id );
            }
        }

        // Check if we need to fire off task again
        $property_queue = $wpdb->get_results(
            "
            SELECT
                id
            FROM
                " . $wpdb->prefix . "houzez_property_feed_property_queue
            WHERE
                `status` = 'pending'
            LIMIT 1
            "
        );

        if ( !empty($property_queue) ) 
        {
            $url = admin_url('admin-ajax.php?action=houzez_property_feed_import_properties_batch&originally_ran_manually=' . ( ( isset($_GET['originally_ran_manually']) && $_GET['originally_ran_manually'] == 'yes' ) ? 'yes' : '' ) . '&import_ids=' . ( isset($_GET['import_ids']) ? rawurlencode(sanitize_text_field($_GET['import_ids'])) : '' ) );

            // Using wget to make a background HTTP request
            $command = "wget -q -O /dev/null \"$url\" > /dev/null 2>&1 &";
            exec($command);
        }
        else
        {
            // No properties queued. Let's just fire the import hook again to make sure we any subsequent imports are ran
            if ( isset($_GET['originally_ran_manually']) && $_GET['originally_ran_manually'] == 'yes' )
            {
                $_GET['custom_property_import_cron'] = 'houzezpropertyfeedcronhook';
            }
            do_action('houzezpropertyfeedcronhook');
        }

        wp_die();
    }

    private function convert_database_data_to_property( $data = '' )
    {
        // Detect JSON (array)
        if ( is_string($data) && json_decode($data, true) !== null ) 
        {
            return json_decode($data, true);
        }
        // Detect XML
        elseif ( is_string($data) && strpos($data, '<?xml') === 0 ) 
        {
            return simplexml_load_string($data);
        }
        // Detect serialized data
        elseif ( @unserialize($data) !== false ) 
        {
            return unserialize($data);
        }

        return $data;
    }

    public function import_import()
    {
        if ( !wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['ajax_nonce'])), "hpf_ajax_nonce" ) ) 
        {
            $return = array(
                'success' => false,
                'error' => __( 'Invalid nonce provided', 'houzezpropertyfeed' )
            );
            echo wp_json_encode($return);
            die();
        }

        if (
            !isset($_FILES['import_file']) ||
            $_FILES['import_file']['error'] !== UPLOAD_ERR_OK
        ) {
            wp_send_json_error('File upload failed');
        }

        $file_tmp = $_FILES['import_file']['tmp_name'];
        $file_contents = file_get_contents($file_tmp);
        $import = json_decode($file_contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) 
        {
            wp_send_json_error('Invalid JSON');
        }
        
        $import['running'] = false;

        // sort out any taxonomy mappings turning back to ids
        if ( !empty($import['mappings']) )
        {
            foreach ( $import['mappings'] as $field => $mappings )
            {
                if ( !empty($mappings) )
                {
                    $taxonomy_name = '';
                    switch ( $field )
                    {
                        case "sales_status":
                        case "lettings_status": { $taxonomy_name = 'property_status'; break; }
                        case "property_type": { $taxonomy_name = 'property_type'; break; }
                    }

                    if ( !empty($taxonomy_name) )
                    {
                        foreach ( $mappings as $crm_id => $hid )
                        {
                            $term = get_term_by( 'name', $hid, $taxonomy_name );
                            if ( !empty($term) )
                            {
                                $import['mappings'][$field][$crm_id] = $term->term_id;
                            }
                        }
                    }
                }
            }
        }

        // sort out any agent/agency turning back to ids
        if ( !empty($import['agent_display_option_rules']) )
        {
            foreach ( $import['agent_display_option_rules'] as $i => $rule )
            {
                if ( isset($rule['result']) && !empty($rule['result']) )
                {
                    $query = new WP_Query(array(
                        'post_type'      => array('houzez_agent', 'houzez_agency'),
                        'title'          => $rule['result'],
                        'posts_per_page' => 1,
                        'post_status'    => 'any',
                        'fields'         => 'ids'
                    ));

                    if ( $query->have_posts() ) {
                        $import['agent_display_option_rules'][$i]['result'] = $query->posts[0];
                    }

                    
                }
            }
        }

        $options = get_option( 'houzez_property_feed', array() );
        $imports = ( isset($options['imports']) && is_array($options['imports']) && !empty($options['imports']) ) ? $options['imports'] : array();

        $imports[time()] = $import;

        $options['imports'] = $imports;

        update_option( 'houzez_property_feed', $options );

        wp_send_json_success(array('url' => admin_url('admin.php?page=houzez-property-feed-import&hpfsuccessmessage=' . base64_encode('Import completed successfully.'))));
    }

    public function test_property_import_details()
    {
        header( 'Content-Type: application/json; charset=utf-8' );

        $format = isset($_POST['format']) ? sanitize_text_field(wp_unslash($_POST['format'])) : '';

        if ( empty($format) )
        {
            $return = array(
                'success' => false,
                'error' => __( 'No format passed', 'houzezpropertyfeed' )
            );
            echo json_encode($return);
            die();
        }

        $import_object = hpf_get_import_object_from_format( $format, '', '' );

        if ( $import_object === false || empty($import_object) )
        {
            $return = array(
                'success' => false,
                'error' => __( 'Couldn\'t create import object', 'houzezpropertyfeed' )
            );
            echo json_encode($return);
            die();
        }

        $parsed = $import_object->parse(true);

        if ( !$parsed )
        {
            $errors = ( isset($import_object->errors) ? $import_object->errors : array() );
            $error = 'Please check they are correct.';
            if ( !empty($errors) )
            {
                $error = $errors[array_key_last($errors)];
                $explode_error = explode(" - ", $error, 2);
                if ( count($explode_error) == 2 )
                {
                    $error = $explode_error[1];
                }
            }

            $return = array(
                'success' => false,
                'error' => 'An error occurred whilst obtaining the properties using the details provided:<br><br>' . nl2br(esc_html($error))
            );
            echo json_encode($return);
            die();
        }
        else
        {
            $return = array(
                'success' => true,
                'properties' => count($import_object->properties)
            );
            echo json_encode($return);
            die();
        }

        wp_die();
    }
}

new Houzez_Property_Feed_Ajax();