<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists('WP_List_Table') )
{
   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Houzez Property Feed Automatic Imports Table Functions
 */
class Houzez_Property_Feed_Admin_Automatic_Imports_Table extends WP_List_Table {

	public function __construct( $args = array() ) 
    {
        parent::__construct( array(
            'singular'=> 'Import',
            'plural' => 'Imports',
            'ajax'   => false // We won't support Ajax for this table, ye
        ) );
	}

    public function extra_tablenav( $which ) 
    {
        
    }

    public function get_columns() 
    {
        return array(
            'col_import_format' =>__('Format', 'houzezpropertyfeed' ),
            'col_import_details' =>__( 'Details', 'houzezpropertyfeed' ),
            'col_import_frequency' =>__( 'Frequency', 'houzezpropertyfeed' ),
            'col_import_last_ran' =>__( 'Last Ran', 'houzezpropertyfeed' ),
            'col_import_next_due' =>__( 'Next Due To Run', 'houzezpropertyfeed' ),
        );
    }

    public function column_default( $item, $column_name )
    {
        switch( $column_name ) 
        {
            case 'col_import_format':
            case 'col_import_details':
            case 'col_import_frequency':
            case 'col_import_last_ran':
            case 'col_import_next_due':
                return $item[ $column_name ];
                break;
            default:
                return print_r( $item, true ) ;
        }
    }

    public function print_column_headers($with_id = true) {
        list($columns, $hidden, $sortable) = $this->get_column_info();

        $current_orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : '';
        $current_order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : '';

        foreach ($columns as $column_key => $column_display_name) 
        {
            $class = ['manage-column', "column-$column_key"];

            $aria_sort = 'none';

            $redirect_url = 'admin.php?page=houzez-property-feed-import';

            if ( isset($_REQUEST['hpf_filter']) && !empty($_REQUEST['hpf_filter']) )
            {
                $redirect_url .= '&hpf_filter=' . sanitize_text_field($_REQUEST['hpf_filter']);
                if ( isset($_REQUEST['hpf_filter_format']) && !empty($_REQUEST['hpf_filter_format']) )
                {
                    $redirect_url .= '&hpf_filter_format=' . sanitize_text_field($_REQUEST['hpf_filter_format']);
                }
            }

            if ( isset($sortable[$column_key]) ) 
            {
                list($orderby, $asc_first) = $sortable[$column_key];
                $order = ($current_orderby === $orderby) ? ($current_order === 'asc' ? 'desc' : 'asc') : ($asc_first ? 'asc' : 'desc');
                $class[] = 'sortable';
                if ( $current_orderby === $orderby ) 
                {
                    $class[] = 'sorted';
                    $class[] = $current_order;
                    $aria_sort = ($current_order === 'asc') ? 'ascending' : 'descending';
                }
                $redirect_url .= "&orderby=$orderby&order=$order";
            }

            $class = join(' ', $class);
            echo '<th scope="col" id="' . esc_attr($column_key) . '" class="' . esc_attr($class) . '" aria-sort="' . esc_attr($aria_sort) . '" abbr="' . esc_attr($column_display_name) . '">';
            if ( isset($sortable[$column_key]) ) 
            {
                echo '<a href="' . esc_url($redirect_url) . '"><span>' . $column_display_name . '</span><span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span></a>';
            }
            else
            {
                echo '<span>' . $column_display_name . '</span>';
            }
            echo '</th>';
        }
    }

    public function get_sortable_columns() 
    {
        return array(
            'col_import_format' => array('format', false) , // 'last_ran' is the key used in the data array
            'col_import_last_ran' => array('last_ran', false),  // 'last_ran' is the key used in the data array
            'col_import_next_due' => array('next_due', false)  // 'last_ran' is the key used in the data array
        );
    }

    public function no_items() 
    {
        echo __( 'No imports found.', 'houzezpropertyfeed' );
    }

    public function prepare_items() 
    {
        global $wpdb;

        $pro_active = apply_filters( 'houzez_property_feed_pro_active', false );

        $columns = $this->get_columns(); 
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $per_page = 10000;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        $this->_column_headers = array($columns, $hidden, $sortable);

        $options = get_option( 'houzez_property_feed' , array() );
        $imports = ( isset($options['imports']) && is_array($options['imports']) && !empty($options['imports']) ) ? $options['imports'] : array();

        $this->items = array();

        foreach ( $imports as $key => $import )
        {
            if ( isset($imports[$key]['deleted']) && $imports[$key]['deleted'] === true )
            {
                unset( $imports[$key] );
            }
        }

        if ( isset($_GET['hpf_filter']) )
        {
            switch ( sanitize_text_field($_GET['hpf_filter']) )
            {
                case "active":
                {
                    foreach ( $imports as $key => $import )
                    {
                        if ( isset($import['running']) && $import['running'] === true )
                        {
                            
                        }
                        else
                        {
                            unset( $imports[$key] );
                        }
                    }
                    break;
                }
                case "inactive":
                {
                    foreach ( $imports as $key => $import )
                    {
                        if ( isset($import['running']) && $import['running'] === true )
                        {
                            unset( $imports[$key] );
                        }
                    }
                    break;
                }
                case "format":
                {
                    if ( isset($_GET['hpf_filter_format']) )
                    {
                        foreach ( $imports as $key => $import )
                        {
                            if ( isset($import['format']) && $import['format'] === sanitize_text_field($_GET['hpf_filter_format']) )
                            {
                                
                            }
                            else
                            {
                                unset( $imports[$key] );
                            }
                        }
                    }
                    break;
                }
                case "running":
                {
                    foreach ( $imports as $key => $import )
                    {
                        $running_now = false;

                        if ( isset($import['running']) && $import['running'] === true )
                        {
                            $row = $wpdb->get_row( $wpdb->prepare("
                                SELECT 
                                    start_date, end_date, status_date
                                FROM 
                                    " .$wpdb->prefix . "houzez_property_feed_logs_instance
                                WHERE 
                                    import_id = %d
                                ORDER BY start_date DESC LIMIT 1
                            ", $key), ARRAY_A);
                            if ( null !== $row )
                            {
                                if ($row['start_date'] <= $row['end_date'])
                                {

                                }
                                elseif ($row['end_date'] == '0000-00-00 00:00:00')
                                {
                                    if ( ( ( time() - strtotime($row['status_date']) ) / 60 ) < 5 )
                                    {
                                        $running_now = true;
                                    }
                                }
                            }
                        }
                        
                        if ( !$running_now )
                        {
                            unset( $imports[$key] );
                        }
                    }
                    break;
                }
            }
        }

        $frequencies = houzez_property_feed_get_import_frequencies();

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
                        $queued_media[$media_queue_count->import_id] = $media_queue_count->queued_media_count;
                    }
                }
            }
        }

        $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : '';
        $order = (!empty($_REQUEST['order']) && in_array(strtolower($_REQUEST['order']), array('asc', 'desc')) ) ? sanitize_text_field($_REQUEST['order']) : '';

        $hpf_filter = (!empty($_REQUEST['hpf_filter'])) ? sanitize_text_field($_REQUEST['hpf_filter']) : '';
        $hpf_filter_format = (!empty($_REQUEST['hpf_filter_format'])) ? sanitize_text_field($_REQUEST['hpf_filter_format']) : '';

        $import_running_now = false;
        $five_minutes_ago = gmdate( 'Y-m-d H:i:s', time() - 5 * 60 );
        $recent_rows = $wpdb->get_results( $wpdb->prepare("
            SELECT 
                import_id
            FROM 
                {$wpdb->prefix}houzez_property_feed_logs_instance
            WHERE
                end_date = '0000-00-00 00:00:00' 
                AND 
                status_date >= %s
        ", $five_minutes_ago), ARRAY_A );
        if ( !empty($recent_rows) )
        {
            foreach ( $recent_rows as $recent_row )
            {
                $import_id = $recent_row['import_id'];

                if ( isset($imports[$import_id]['running']) && $imports[$import_id]['running'] === true )
                {
                    $import_running_now = true;
                }
            }
        }

        foreach ( $imports as $key => $import )
        {
            // ensure frequency is not a PRO one if PRO not enabled
            if ( $pro_active !== true )
            {
                if ( isset($frequencies[$import['frequency']]['pro']) && $frequencies[$import['frequency']]['pro'] === true )
                {
                    $import['frequency'] = 'daily';
                }
            }

            $format = houzez_property_feed_get_import_format( $import['format'] );

            if ( $pro_active === true )
            {
                if ( isset($format['background_mode']) && $format['background_mode'] === true )
                {
                    if ( isset($import['background_mode']) && $import['background_mode'] == 'yes' )
                    {
                        $queued_properties[$key] = 0;

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
                            ", (int)$key)
                        );
                        if ( count($queued_properties_query) > 0 )
                        {
                            $queued_properties[$key] = count($queued_properties_query);
                        }
                    }
                }
            }

            $details = '';
            if ( isset($format['fields']) && !empty($format['fields']) )
            {
                foreach ( $format['fields'] as $field )
                {
                    if ( isset($field['type']) && $field['type'] != 'hidden' && $field['type'] != 'html' )
                    {
                        $value = ( ( isset($import[$field['id']]) && !empty($import[$field['id']]) ) ? $import[$field['id']] : '' );
                        if ( $field['id'] == 'xml_url' || $field['id'] == 'csv_url' || $field['id'] == 'url' )
                        {
                            $value = '<a href="' . $value . '" target="_blank">' . $value . '</a>';
                        }
                        if ( $field['type'] == 'multiselect' && is_array($value) && !empty($value) )
                        {
                            $details .= '<strong>' . $field['label'] . '</strong>: ' . implode(", ", $value) . '<br>';
                        }
                        else
                        {
                            $details .= '<strong>' . $field['label'] . '</strong>: ' . ( $value != '' ? $value : '-' ) .  '<br>';
                        }
                    }
                }
            }
            if ( isset($format['export_enquiries']) && $format['export_enquiries'] === true )
            {
                $value = 'No';
                if ( isset($import['export_enquiries_enabled']) && $import['export_enquiries_enabled'] == 'yes' )
                {
                    $value = 'Yes';
                }
                $details .= '<strong>' . esc_html( __( 'Export Enquiries', 'houzezpropertyfeed' ) ) . '</strong>: ' . esc_html($value) . '<br>';
            }
            if ( $pro_active === true )
            {
                if ( isset($import['limit']) && !empty((int)$import['limit']) && is_numeric($import['limit']) )
                {
                    $details .= '<strong>' . __( 'Limit Properties', 'houzezpropertyfeed' ) . '</strong>: ' . esc_html(number_format((int)$import['limit']) . ' ' . __( 'properties', 'houzezpropertyfeed' ) ) . '<br>';
                }
                if ( isset($import['limit_images']) && !empty((int)$import['limit_images']) && is_numeric($import['limit_images']) )
                {
                    $details .= '<strong>' . __( 'Limit Images', 'houzezpropertyfeed' ) . '</strong>: ' . esc_html(number_format((int)$import['limit_images']) . ' ' . __( 'per property', 'houzezpropertyfeed' ) ) . '<br>';
                }
                if ( 
                    isset($format['background_mode']) && $format['background_mode'] === true &&
                    isset($import['background_mode']) && $import['background_mode'] === 'yes' 
                )
                {
                    $details .= '<strong>' . __( 'Background Mode', 'houzezpropertyfeed' ) . '</strong>: ' . esc_html('Yes') . '<span class="queued-properties" data-import-id="' . esc_attr($key) . '">' . ( ( isset($queued_properties[$key]) && !empty($queued_properties[$key]) ) ? ' (' . esc_html($queued_properties[$key]) . ' queued properties)' : '' ) . '<span><br>';
                }
                if ( isset($options['media_processing']) && $options['media_processing'] === 'background' )
                {
                    if ( isset($queued_media[$key]) && !empty($queued_media[$key]) )
                    {
                        $details .= '<strong>' . __( 'Queued Media Items', 'houzezpropertyfeed' ) . '</strong>: <a href="' . esc_url(admin_url('admin.php?page=houzez-property-feed-import&action=queuedmedia&import_id=' . (int)$key)) . '"><span class="queued-media-items" data-import-id="' . esc_attr($key) . '">' . esc_html($queued_media[$key]) . '<span></a><br>';
                    }
                }
            }
            
            $running = false;
            $last_ran = '';
            $last_ran_for_sorting = '';
            if ( isset($import['running']) && $import['running'] === true )
            {
                $running = true;

                // Last ran
                $row = $wpdb->get_row( $wpdb->prepare("
                    SELECT 
                        start_date, end_date, status, status_date, media
                    FROM 
                        " .$wpdb->prefix . "houzez_property_feed_logs_instance
                    WHERE 
                        import_id = %d
                    ORDER BY start_date DESC LIMIT 1
                ", $key), ARRAY_A);
                if ( null !== $row )
                {
                    if ($row['start_date'] <= $row['end_date'])
                    {
                        $last_ran .= get_date_from_gmt( $row['start_date'], "jS F Y H:i" );
                    }
                    elseif ($row['end_date'] == '0000-00-00 00:00:00')
                    {
                        $status = '';
                        if ( $row['media'] != '1' && isset($row['status']) && !empty($row['status']) && isset($row['status_date']) && $row['status_date'] != '0000-00-00 00:00:00' )
                        {
                            if ( ( ( time() - strtotime($row['status_date']) ) / 60 ) < 5 )
                            {
                                $decoded_status = json_decode($row['status'], true);
                                if ( isset($decoded_status['status']) && $decoded_status['status'] == 'importing' )
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
                                elseif ( isset($decoded_status['status']) && $decoded_status['status'] == 'parsing' )
                                {
                                    $status = '<br>Parsing properties';
                                }
                            }
                            else
                            {
                                $status = '<br>Failed to complete<br>Will resume automatically shortly';
                            }
                        }
                        $last_ran .= 'Started at ' . get_date_from_gmt( $row['start_date'], "jS F Y H:i" ) . '<br><span class="running-now" data-import-id="' . $key . '">Running now...</span><span class="running-now-status" data-import-id="' . $key . '">' . $status . '</span>';
                    }
                    $last_ran_for_sorting = $row['start_date'];
                }
                else
                {
                    $last_ran .= '-';
                }
            }

            // Next due
            $next_due_display = '';
            $next_due_for_sorting = '';
            if ( isset($import['running']) && $import['running'] === true && $import['format'] != 'rtdf' )
            {
                $next_due = wp_next_scheduled( 'houzezpropertyfeedcronhook' );

                if ( $next_due == FALSE )
                {
                    $next_due_display .= 'Whoops. WordPress doesn\'t have the import scheduled. A quick fix to this is to deactivate, then re-activate the plugin.';
                }
                else
                {
                    $last_start_date = '2020-01-01 00:00:00';
                    $row = $wpdb->get_row( $wpdb->prepare("
                        SELECT 
                            start_date
                        FROM 
                            " .$wpdb->prefix . "houzez_property_feed_logs_instance
                        WHERE
                            import_id = %d
                        ORDER BY start_date DESC LIMIT 1
                    ", $key), ARRAY_A);
                    if ( null !== $row )
                    {
                        $last_start_date = $row['start_date'];   
                    }
                    $last_start_date = strtotime($last_start_date);

                    $got_next_due = false;
                    $j = 0;
                    
                    while ( $got_next_due === false )
                    {
                        if ( $j > 500 )
                        {
                            break;
                        }
                        switch ($import['frequency'])
                        {
                            case "every_fifteen_minutes":
                            {
                                if ( ( ($next_due - $last_start_date) / 60 / 60 ) >= 0.25 )
                                {
                                    $got_next_due = $next_due;
                                }
                                break;
                            }
                            case "hourly":
                            {
                                if ( ( ($next_due - $last_start_date) / 60 / 60 ) >= 1 )
                                {
                                    $got_next_due = $next_due;
                                }
                                break;
                            }
                            case "twicedaily":
                            {
                                if ( ( ($next_due - $last_start_date) / 60 / 60 ) >= 12 )
                                {
                                    $got_next_due = $next_due;
                                }
                                break;
                            }
                            case "exact_hours":
                            {
                                $exact_hours = array();
                                if ( isset($import['exact_hours']) && is_array($import['exact_hours']) && !empty($import['exact_hours']) )
                                {
                                    $exact_hours = $import['exact_hours'];
                                    sort($exact_hours, SORT_NUMERIC); 

                                    $current_date = new DateTimeImmutable( '@' . $next_due );
                                    $current_date_new = $current_date->setTimezone(wp_timezone());
                                    $current_hour = $current_date_new->format('H');

                                    if ( !empty($exact_hours) )
                                    {
                                        foreach ( $exact_hours as $hour_to_execute )
                                        {
                                            $hour_to_execute = explode(":", $hour_to_execute);
                                            $hour_to_execute = $hour_to_execute[0];
                                            
                                            $hour_to_execute = str_pad($hour_to_execute, 2, '0', STR_PAD_LEFT);
                                            $timezone_offset = $current_date->getOffset();

                                            // Check today
                                            if ( $current_hour >= $hour_to_execute )
                                            {
                                                // in timezone
                                                $date_to_check = new DateTimeImmutable( $current_date->format('Y-m-d') . ' ' . $hour_to_execute . ':00:00', wp_timezone() );
                                                $date_to_check = $date_to_check->getTimestamp();

                                                $last_start_date_to_check = new DateTimeImmutable( '@' . $last_start_date );
                                                $last_start_date_to_check = $last_start_date_to_check->format('Y-m-d H') . ':00:00';
                                                $last_start_date = strtotime($last_start_date_to_check);

                                                if ( 
                                                    ( $next_due >= $date_to_check ) && 
                                                    ( $last_start_date < $date_to_check )
                                                )
                                                {
                                                    $got_next_due = $next_due;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                                break;
                            }
                            default: // daily
                            {
                                if ( ( ($next_due - $last_start_date) / 60 / 60 ) >= 24 )
                                {
                                    $got_next_due = $next_due;
                                }
                            }
                        }
                        $next_due = $next_due + 300;
                        ++$j;
                    }

                    if ( $got_next_due !== false )
                    {
                        $next_due_for_sorting = $got_next_due;

                        $got_next_due = new DateTimeImmutable( '@' . $got_next_due );
                        $got_next_due_new = $got_next_due->setTimezone(wp_timezone());

                        $current_date = current_datetime();

                        $tomorrows_date = new DateTime( 'now', wp_timezone() );
                        $tomorrows_date->modify('+1 day');

                        if ( $got_next_due_new->format("Y-m-d") == $current_date->format("Y-m-d") )
                        {
                            $next_due_display .= 'Today at ' . $got_next_due_new->format("H:i");
                        }
                        elseif ( $got_next_due_new->format("Y-m-d") == $tomorrows_date->format("Y-m-d") )
                        {
                            $next_due_display .= 'Tomorrow at ' . $got_next_due_new->format("H:i");
                        }
                        else
                        {
                            // should never get to this case
                            $next_due_display .= $got_next_due_new->format("H:i jS F");
                        }
                    }
                }
            }
            else
            {
                $next_due_display .= '-';
            }

            $frequency = ( isset($import['frequency']) ? ucwords(str_replace("_", " ", $import['frequency'])) : '-' );
            if ( isset($import['frequency']) && $import['frequency'] == 'exact_hours' )
            {
                if ( isset($import['exact_hours']) && is_array($import['exact_hours']) && !empty($import['exact_hours']) )
                {
                    $frequency .= ': ' . implode(", ", $import['exact_hours']);
                }
                else
                {
                    $frequency .= ': ' . __( 'None specified', 'houzezpropertyfeed' );
                }
            }

            $actions = array();
            $actions[] = '<span class="edit">' . ( 
                !$running ? 
                '<a href="' . esc_url(admin_url('admin.php?page=houzez-property-feed-import&action=startimport&import_id=' . (int)$key . '&orderby=' . $orderby . '&order=' . $order . '&hpf_filter=' . $hpf_filter . '&hpf_filter_format=' . $hpf_filter_format)) . '" aria-label="' . esc_attr( __( 'Start Import', 'houzezpropertyfeed' ) ) . '">' . esc_html( __( 'Start Import', 'houzezpropertyfeed' ) ) . '</a>' : 
                '<a href="' . esc_url(admin_url('admin.php?page=houzez-property-feed-import&action=pauseimport&import_id=' . (int)$key . '&orderby=' . $orderby . '&order=' . $order . '&hpf_filter=' . $hpf_filter . '&hpf_filter_format=' . $hpf_filter_format)) . '" aria-label="' . esc_attr( __( 'Pause Import', 'houzezpropertyfeed' ) ) . '">' . esc_html( __( 'Pause Import', 'houzezpropertyfeed' ) ) . '</a>' 
            ) . '</span>';
            if ( $running ) 
            { 
                $nonce = wp_create_nonce('houzez_property_feed_import');
                $actions[] = '<span class="edit"><a href="' . admin_url('admin.php?page=houzez-property-feed-import&custom_property_import_cron=houzezpropertyfeedcronhook&orderby=' . $orderby . '&order=' . $order . '&hpf_filter=' . $hpf_filter . '&hpf_filter_format=' . $hpf_filter_format . '&import_id=' . (int)$key . '&_wpnonce=' . $nonce) . '" class="link-manually-execute-import"  onclick="hpf_click_run_now();" aria-label="' . esc_attr( __( 'Run Now', 'houzezpropertyfeed' ) ) . '"' . ( $import_running_now === true ? ' style="pointer-events:none;"' : '' ) . '>' . esc_html( $import_running_now === true ? __( 'Processing...', 'houzezpropertyfeed' ) : __( 'Run Now', 'houzezpropertyfeed' ) ) . '</a></span>'; 
            }
            $actions[] = '<span class="edit"><a href="' . esc_url(admin_url('admin.php?page=houzez-property-feed-import&tab=logs&import_id=' . (int)$key)) . '" aria-label="' . esc_attr( __( 'View Logs', 'houzezpropertyfeed' ) ) . '">' . esc_html( __( 'Logs', 'houzezpropertyfeed' ) ) . '</a></span>';
            $actions[] = '<span class="edit"><a href="' . esc_url(admin_url('admin.php?page=houzez-property-feed-import&action=editimport&import_id=' . (int)$key)) . '" aria-label="' . esc_attr( __( 'Edit Import', 'houzezpropertyfeed' ) ) . '">' . esc_html( __( 'Edit', 'houzezpropertyfeed' ) ) . '</a></span>';
            $actions[] = '<span class="edit"><a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=houzez-property-feed-import&action=cloneimport&import_id=' . (int)$key . '&orderby=' . $orderby . '&order=' . $order), 'clone-import' )) . '" aria-label="' . esc_attr( __( 'Clone Import', 'houzezpropertyfeed' ) ) . '">' . esc_html( __( 'Clone', 'houzezpropertyfeed' ) ) . '</a></span>';
            if ( !$running ) { $actions[] = '<span class="trash"><a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=houzez-property-feed-import&action=deleteimport&import_id=' . (int)$key . '&orderby=' . $orderby . '&order=' . $order), 'delete-import' )) . '" class="submitdelete" aria-label="' . esc_attr( __( 'Delete Import', 'houzezpropertyfeed' ) ) . '">' . esc_html( __( 'Delete', 'houzezpropertyfeed' ) ) . '</a></span>'; }

            $this->items[] = array(
                'col_import_format' => '
                    <strong><a href="' . esc_url(admin_url('admin.php?page=houzez-property-feed-import&action=editimport&import_id=' . (int)$key)) . '" aria-label="' . esc_attr( __( 'Edit Import', 'houzezpropertyfeed' ) ) . '">' . ( $running ? '<span class="icon-running-status icon-running"></span>' : '<span class="icon-running-status icon-not-running"></span>' ) . ' ' . ( isset($format['name']) ? $format['name'] : '-' )  . ( isset($import['custom_name']) && !empty($import['custom_name']) ? ' (' . $import['custom_name'] . ')' : '' ) . '</a></strong>
                    <div class="row-actions">
                        ' . implode(" | ", $actions) . '
                    </div>',
                'col_import_details' => $details,
                'col_import_frequency' => $frequency,
                'col_import_last_ran' => $last_ran,
                'col_import_next_due' => $next_due_display,
                'format' => ( isset($format['name']) ? $format['name'] : '' ),
                'last_ran' => $last_ran_for_sorting,
                'next_due' => $next_due_for_sorting
            );
        }

        if ( !empty($orderby) && !empty($order) )
        {
            usort($this->items, function($a, $b) use ($orderby, $order) {
                if ($order === 'asc') {
                    return strcasecmp($a[$orderby], $b[$orderby]);
                } else {
                    return strcasecmp($b[$orderby], $a[$orderby]);
                }
            });
        }

        $this->set_pagination_args(
            array(
                'total_items' => count($imports),
                'per_page'    => $per_page,
            )
        );
        
    }

    public function display() {
        $singular = $this->_args['singular'];

        $this->screen->render_screen_reader_content( 'heading_list' );
        ?>
<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
    <thead>
    <tr>
        <?php $this->print_column_headers(); ?>
    </tr>
    </thead>

    <tbody id="the-list"
        <?php
        if ( $singular ) {
            echo ' data-wp-lists="list:' . esc_attr($singular) . '"';
        }
        ?>
        >
        <?php $this->display_rows_or_placeholder(); ?>
    </tbody>

</table>
        <?php
    }

    protected function get_table_classes() {
        $mode = get_user_setting( 'posts_list_mode', 'list' );

        $mode_class = esc_attr( 'table-view-' . $mode );

        return array( 'widefat', 'striped', $mode_class, esc_attr($this->_args['plural']) );
    }

}