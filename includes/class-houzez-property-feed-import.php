<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Houzez Property Feed Import Functions
 */
class Houzez_Property_Feed_Import {

	public function __construct() {

        add_action( 'admin_init', array( $this, 'check_not_multiple_if_no_pro') );

        add_action( 'admin_init', array( $this, 'save_import_settings') );

        add_action( 'admin_init', array( $this, 'toggle_import_running_status') );

        add_action( 'admin_init', array( $this, 'delete_import') );

        add_action( "houzez_property_feed_property_imported", array( $this, 'perform_field_mapping' ), 1, 3 );
        add_action( 'houzez_property_feed_property_imported', array( $this, 'set_generic_houzez_property_data'), 1, 3 );

        add_action( 'add_meta_boxes', array( $this, 'import_data_meta_box') );

	}

    public function check_not_multiple_if_no_pro()
    {
        if ( isset($_GET['action']) && $_GET['action'] == 'addimport' )
        {
            if ( apply_filters( 'houzez_property_feed_pro_active', false ) !== true ) 
            {
                $options = get_option( 'houzez_property_feed', array() );
                $imports = ( isset($options['imports']) && is_array($options['imports']) && !empty($options['imports']) ) ? $options['imports'] : array();

                foreach ( $imports as $key => $import )
                {
                    if ( $imports[$key]['deleted'] && $imports[$key]['deleted'] === true )
                    {
                        unset( $imports[$key] );
                    }
                }

                if ( count($imports) >=1 )
                {
                    wp_redirect( admin_url( 'admin.php?page=houzez-property-feed&hpferrormessage=' . urlencode(__( 'Maximum number of imports reached. Upgrade to PRO if wanting to benfit from multiple imports and more', 'houzezpropertyfeed' ) ) ) );
                    die();
                }
            }
        }
    }

    public function save_import_settings()
    {
        if ( !isset($_POST['save_import_settings']) )
        {
            return;
        }

        if ( !isset($_POST['_wpnonce']) || ( isset($_POST['_wpnonce']) && !wp_verify_nonce( $_POST['_wpnonce'], 'save-import-settings' ) ) ) 
        {
            die( __( "Failed security check", 'houzezpropertyfeed' ) );
        }

        // ready to save
        $import_id = !empty($_POST['import_id']) ? (int)$_POST['import_id'] : time();

        $options = get_option( 'houzez_property_feed' , array() );
        if ( !is_array($options) ) { $options = array(); }
        if ( !is_array($options['imports']) ) { $options['imports'] = array(); }
        if ( !is_array($options['imports'][$import_id]) ) { $options['imports'][$import_id] = array(); }

        $format = sanitize_text_field($_POST['format']);

        $running = ( isset($_POST['running']) && sanitize_text_field($_POST['running']) == 'yes' ) ? true : false;

        $agent_display_option = ( isset($_POST['agent_display_option']) ) ? sanitize_text_field($_POST['agent_display_option']) : 'author_info';

        $import_options = array(
            'running' => $running,
            'format' => $format,
            'frequency' => sanitize_text_field($_POST['frequency']), // might want to validate this is not a pro frequency
            'create_location_taxonomy_terms' => ( isset($_POST['create_location_taxonomy_terms']) && sanitize_text_field($_POST['create_location_taxonomy_terms']) == 'yes' ) ? true : false,
            'property_city_address_field' => ( isset($_POST['property_city_address_field']) ) ? sanitize_text_field($_POST['property_city_address_field']) : true,
            'property_area_address_field' => ( isset($_POST['property_area_address_field']) ) ? sanitize_text_field($_POST['property_area_address_field']) : true,
            'property_state_address_field' => ( isset($_POST['property_state_address_field']) ) ? sanitize_text_field($_POST['property_state_address_field']) : true,
            'agent_display_option' => $agent_display_option,
        );

        $rules = array();
        switch ( $agent_display_option )
        {
            case "author_info":
            case "agent_info":
            case "agency_info":
            {
                if ( 
                    isset($_POST[$agent_display_option . '_rules_field']) && 
                    is_array($_POST[$agent_display_option . '_rules_field']) && 
                    count($_POST[$agent_display_option . '_rules_field']) > 1 // more than 1 to ignore template
                )
                {
                    $rule_i = 0;
                    foreach ( $_POST[$agent_display_option . '_rules_field'] as $j => $field )
                    {
                        if ( $rule_i > 0 )
                        {
                            if ( 
                                !empty($field) && 
                                !empty($_POST[$agent_display_option . '_rules_equal'][$j]) && 
                                !empty($_POST[$agent_display_option . '_rules_result'][$j]) 
                            )
                            {
                                $rules[] = array(
                                    'field' => $field,
                                    'equal' => sanitize_text_field($_POST[$agent_display_option . '_rules_equal'][$j]),
                                    'result' => sanitize_text_field($_POST[$agent_display_option . '_rules_result'][$j]),
                                );
                            }
                        }

                        ++$rule_i;
                    }
                }
                break;
            }
        }
        $import_options['agent_display_option_rules'] = $rules;

        $rules = array();
        if ( 
            isset($_POST['field_mapping_rules_field']) && 
            is_array($_POST['field_mapping_rules_field']) && 
            count($_POST['field_mapping_rules_field']) > 1 // more than 1 to ignore template
        )
        {
            $rule_i = 0;
            foreach ( $_POST['field_mapping_rules_field'] as $j => $field )
            {
                if ( $rule_i > 0 )
                {
                    if ( 
                        !empty($field) && 
                        !empty($_POST['field_mapping_rules_equal'][$j]) && 
                        !empty($_POST['field_mapping_rules_houzez_field'][$j]) &&
                        !empty($_POST['field_mapping_rules_result'][$j]) 
                    )
                    {
                        $rules[] = array(
                            'field' => $field,
                            'equal' => sanitize_text_field($_POST['field_mapping_rules_equal'][$j]),
                            'houzez_field' => sanitize_text_field($_POST['field_mapping_rules_houzez_field'][$j]),
                            'result' => sanitize_text_field($_POST['field_mapping_rules_result'][$j]),
                        );
                    }
                }

                ++$rule_i;
            }
        }
        $import_options['field_mapping_rules'] = $rules;

        // Save core format fields (API Key, XML URL etc)
        $formats = get_houzez_property_feed_formats();
        if ( isset($formats[$format]) )
        {
            if ( isset($formats[$format]['fields']) && !empty($formats[$format]['fields']) )
            {
                foreach ( $formats[$format]['fields'] as $field )
                {   
                    $field_value = '';
                    if ( isset($_POST[$format . '_' . $field['id']]) && !empty($_POST[$format . '_' . $field['id']]) )
                    {
                        $field_value = sanitize_text_field($_POST[$format . '_' . $field['id']]);
                    }
                    $import_options[$field['id']] = $field_value;
                }
            }
        }

        $import_mappings = array();

        if ( isset($_POST['taxonomy_mapping']) && is_array($_POST['taxonomy_mapping']) && !empty($_POST['taxonomy_mapping']) )
        {
            foreach ( $_POST['taxonomy_mapping'] as $taxonomy => $mappings )
            {
                $taxonomy = sanitize_text_field($taxonomy);

                $import_mappings[$taxonomy] = array();

                if ( is_array($mappings) && !empty($mappings) )
                {
                    foreach ( $mappings as $crm_value => $term_id )
                    {
                        if ( !empty((int)$term_id) )
                        {
                            $import_mappings[$taxonomy][$crm_value] = (int)$term_id;
                        }
                    }
                }

                if ( isset($_POST['custom_mapping'][$taxonomy]) )
                {
                    foreach ( $_POST['custom_mapping'][$taxonomy] as $key => $custom_mapping )
                    {
                        if ( trim($custom_mapping) != '' )
                        {
                            if ( isset($_POST['custom_mapping_value'][$taxonomy][$key]) && trim($_POST['custom_mapping_value'][$taxonomy][$key]) != '' )
                            {
                                $import_mappings[$taxonomy][$custom_mapping] = $_POST['custom_mapping_value'][$taxonomy][$key];
                            }
                        }
                    }
                }
            }
        }

        $import_options['mappings'] = $import_mappings;

        $options['imports'][$import_id] = $import_options;

        update_option( 'houzez_property_feed', $options );

        wp_redirect( admin_url( 'admin.php?page=houzez-property-feed&hpfsuccessmessage=' . __( 'Import details saved', 'houzezpropertyfeed' ) ) );
        die();
    }

    public function toggle_import_running_status()
    {
        if ( isset($_GET['action']) && in_array($_GET['action'], array("startimport", "pauseimport")) && isset($_GET['import_id']) )
        {
            $import_id = !empty($_GET['import_id']) ? (int)$_GET['import_id'] : '';

            if ( empty($import_id) )
            {
                wp_redirect( admin_url( 'admin.php?page=houzez-property-feed&hpferrormessage=' . __( 'No import passed', 'houzezpropertyfeed' ) ) );
                die();
            }

            $options = get_option( 'houzez_property_feed', array() );
            
            if ( !isset($options['imports'][$import_id]) )
            {
                wp_redirect( admin_url( 'admin.php?page=houzez-property-feed&hpferrormessage=' . __( 'Import not found', 'houzezpropertyfeed' ) ) );
                die();
            }

            switch ( sanitize_text_field($_GET['action']) )
            {
                case "startimport":
                {   
                    // Check one imports not already active if not using pro
                    if ( apply_filters( 'houzez_property_feed_pro_active', false ) !== true ) 
                    {
                        foreach ( $options['imports'] as $import )
                        {
                            if ( ( !isset($import['deleted']) || ( isset($import['deleted']) && $import['deleted'] !== true ) ) && isset($import['running']) && $import['running'] === true )
                            {
                                wp_redirect( admin_url( 'admin.php?page=houzez-property-feed&hpferrormessage=' . urlencode(__( 'Maximum number of running imports reached. Upgrade to PRO if wanting to benfit from multiple imports and more', 'houzezpropertyfeed' ) ) ) );
                                die();
                            }
                        }
                    }

                    $options['imports'][$import_id]['running'] = true;

                    update_option( 'houzez_property_feed', $options );

                    wp_redirect( admin_url( 'admin.php?page=houzez-property-feed&hpfsuccessmessage=' . __( 'Import started', 'houzezpropertyfeed' ) ) );
                    die();

                    break;

                }
                case "pauseimport":
                {
                    $options['imports'][$import_id]['running'] = false;

                    update_option( 'houzez_property_feed', $options );

                    wp_redirect( admin_url( 'admin.php?page=houzez-property-feed&hpfsuccessmessage=' . __( 'Import paused', 'houzezpropertyfeed' ) ) );
                    die();

                    break;

                }
            }
        }
    }

    public function delete_import()
    {
        if ( isset($_GET['action']) && $_GET['action'] == 'deleteimport' && isset($_GET['import_id']) )
        {
            $import_id = !empty($_GET['import_id']) ? (int)$_GET['import_id'] : '';

            if ( empty($import_id) )
            {
                wp_redirect( admin_url( 'admin.php?page=houzez-property-feed&hpferrormessage=' . __( 'No import passed', 'houzezpropertyfeed' ) ) );
                die();
            }

            $options = get_option( 'houzez_property_feed', array() );
            
            if ( !isset($options['imports'][$import_id]) )
            {
                wp_redirect( admin_url( 'admin.php?page=houzez-property-feed&hpferrormessage=' . __( 'Import not found', 'houzezpropertyfeed' ) ) );
                die();
            }

            $options['imports'][$import_id]['running'] = false;
            $options['imports'][$import_id]['deleted'] = true;

            update_option( 'houzez_property_feed', $options );

            wp_redirect( admin_url( 'admin.php?page=houzez-property-feed&hpfsuccessmessage=' . __( 'Import deleted successfully', 'houzezpropertyfeed' ) ) );
            die();
        }
    }

    public function perform_field_mapping( $post_id, $property, $import_id )
    {
        $import_settings = get_import_settings_from_id( $import_id );

        if ( $import_settings === false )
        {
            return false;
        }

        if ( !isset($import_settings['field_mapping_rules']) )
        {
            return false;
        }

        if ( empty($import_settings['field_mapping_rules']) )
        {
            return false;
        }

        foreach ( $import_settings['field_mapping_rules'] as $rule )
        {
            // field
            // equal
            // houzez_field
            // result

            if ( is_object($property) )
            {
                $property = $this->SimpleXML2ArrayWithCDATASupport($property);
            }

            // loop through all fields in data and see if $rule['field'] is found
            if ( is_array($property) )
            {
                $value_to_check = $this->check_array_for_matching_key( $property, $rule['field'] );

                if ( $value_to_check === false )
                {
                    continue;
                }

                // we found a field with this key
                if ( $rule['equal'] != '*' && $value_to_check != $rule['equal'] )
                {
                    continue;
                }

                $result = $rule['result'];
                if ( $rule['result'] == '{field_value}' )
                {
                    $result = $value_to_check;
                }

                // we found a matching field with the required value
                update_post_meta( $post_id, $rule['houzez_field'], $result );
            }

        }
    }

    private function SimpleXML2ArrayWithCDATASupport( $xml )
    {   
        $array = (array)$xml;

        if ( count($array) === 0 ) 
        {
            return (string)$xml;
        }

        foreach ( $array as $key => $value ) 
        {
            if ( !is_object($value) || strpos(get_class($value), 'SimpleXML') === false ) 
            {
                continue;
            }
            $array[$key] = $this->SimpleXML2ArrayWithCDATASupport($value);
        }

        return $array;
    }

    private function check_array_for_matching_key( $array, $looking_for ) 
    {
        foreach ( $array as $key => $value ) 
        {
            if ( !is_numeric($key) && $key == $looking_for )
            {
                return $value;
            }

            if ( is_array($value) && !empty($value) ) 
            {
                $value_to_check = $this->check_array_for_matching_key( $value, $looking_for );
                if ( $value_to_check !== false )
                {
                    return $value_to_check;
                }
            }
        }

        return false;
    }

    public function set_generic_houzez_property_data( $post_id, $property, $import_id )
    {
        add_post_meta( $post_id, 'fave_loggedintoview', '0', TRUE );
        add_post_meta( $post_id, 'fave_single_content_area', 'global', TRUE );
        add_post_meta( $post_id, 'fave_single_top_area', 'global', TRUE );
        add_post_meta( $post_id, 'fave_prop_homeslider', 'no', TRUE );
    }

    public function import_data_meta_box()
    {
        $screen = get_current_screen();
        if ( isset($screen->post_type) && $screen->post_type == 'property' )
        {
            if ( isset($screen->action) && $screen->action == 'add' )
            {

            }
            else
            {
                add_meta_box( 'houzezpropertyfeed-import-data', __( 'Import Data', 'houzezpropertyfeed' ), array( $this, 'output_import_data_meta_box'), 'property', 'advanced', 'low' );
            }
        }
    }

    public function output_import_data_meta_box( $post )
    {
        if ( isset($post->ID) )
        {
            if ( get_post_meta( $post->ID, '_property_import_data', TRUE ) != '' )
            {
                echo '<textarea readonly rows="20" style="width:100%;">' . get_post_meta( $post->ID, '_property_import_data', TRUE )  . '</textarea>';
            }
            else
            {
                echo __( 'No import data to display', 'houzezpropertyfeed' );
            }
        }
    }
}

new Houzez_Property_Feed_Import();