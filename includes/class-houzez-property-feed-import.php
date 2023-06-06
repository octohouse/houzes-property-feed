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

        add_action( 'houzez_property_feed_property_imported', array( $this, 'set_generic_houzez_property_data'), 1, 3 );

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
                            if ( !empty($field) && !empty($_POST[$agent_display_option . '_rules_equal'][$j]) && !empty($_POST[$agent_display_option . '_rules_result'][$j]) )
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
                $import_mappings[sanitize_text_field($taxonomy)] = array();

                if ( is_array($mappings) && !empty($mappings) )
                {
                    foreach ( $mappings as $crm_value => $term_id )
                    {
                        if ( !empty((int)$term_id) )
                        {
                            $import_mappings[sanitize_text_field($taxonomy)][$crm_value] = (int)$term_id;
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

    public function set_generic_houzez_property_data( $post_id, $property, $import_id )
    {
        add_post_meta( $post_id, 'fave_loggedintoview', '0', TRUE );
        add_post_meta( $post_id, 'fave_single_content_area', 'global', TRUE );
        add_post_meta( $post_id, 'fave_single_top_area', 'global', TRUE );
        add_post_meta( $post_id, 'fave_prop_homeslider', 'no', TRUE );
    }
}

new Houzez_Property_Feed_Import();