<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Houzez Property Feed Settings Functions
 */
class Houzez_Property_Feed_Settings {

	public function __construct() {

        add_action( 'admin_init', array( $this, 'save_settings') );

        add_action( 'admin_init', array( $this, 'check_for_export') );

	}

    public function save_settings()
    {
        if ( !isset($_POST['save_hpf_settings']) )
        {
            return;
        }

        if ( !isset($_POST['_wpnonce']) || ( isset($_POST['_wpnonce']) && !wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'save-hpf-settings' ) ) ) 
        {
            die( __( "Failed security check", 'houzezpropertyfeed' ) );
        }

        $options = get_option( 'houzez_property_feed' , array() );
        if ( !is_array($options) ) { $options = array(); }

        if ( isset($_GET['page']) && sanitize_text_field($_GET['page']) == 'houzez-property-feed-import' )
        {
            $new_options = array(
                'email_reports' => ( ( isset($_POST['email_reports']) && $_POST['email_reports'] == 'yes' ) ? true : false ),
                'email_reports_to' => ( ( isset($_POST['email_reports_to']) && sanitize_email($_POST['email_reports_to']) ) ? sanitize_email(wp_unslash($_POST['email_reports_to'])) : '' ),
                'remove_action' => ( ( isset($_POST['remove_action']) && in_array($_POST['remove_action'], array( '', 'nothing', 'remove_all_media', 'delete' )) ) ? sanitize_text_field(wp_unslash($_POST['remove_action'])) : '' ),
                'media_processing' => ( ( isset($_POST['media_processing']) && in_array($_POST['media_processing'], array( '', 'background' )) ) ? sanitize_text_field(wp_unslash($_POST['media_processing'])) : '' ),
                'hide_properties_with_no_images' => ( ( isset($_POST['hide_properties_with_no_images']) && $_POST['hide_properties_with_no_images'] == 'yes' ) ? true : false ),
            );
        }

        if ( isset($_GET['page']) && sanitize_text_field($_GET['page']) == 'houzez-property-feed-export' )
        {
            $new_options = array(
                'sales_statuses' => ( ( isset($_POST['sales_statuses']) && !empty($_POST['sales_statuses']) ) ? hpf_clean( $_POST['sales_statuses'] ) : array() ),
                'lettings_statuses' => ( ( isset($_POST['lettings_statuses']) && !empty($_POST['lettings_statuses']) ) ? hpf_clean( $_POST['lettings_statuses'] ) : array() ),
                'property_selection' => ( ( isset($_POST['property_selection']) && in_array($_POST['property_selection'], array( '', 'individual', 'per_export' )) ) ? sanitize_text_field(wp_unslash($_POST['property_selection'])) : '' ),
            );
        }

        $options = array_merge( $options, $new_options );

        update_option( 'houzez_property_feed', $options );

        wp_redirect( admin_url( 'admin.php?page=' . ( isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : 'houzez-property-feed-import' ) . '&tab=settings&hpfsuccessmessage=' . base64_encode(__( 'Settings saved', 'houzezpropertyfeed' ) ) ) );
        die();
    }

    public function check_for_export()
    {
        if ( !isset($_GET['export']) || empty($_GET['export']) )
        {
            return;
        }

        if ( !isset($_GET['_wpnonce']) || ( isset($_GET['_wpnonce']) && !wp_verify_nonce( sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'export-import' ) ) ) 
        {
            die( esc_html(__( "Failed security check", 'houzezpropertyfeed' ) ) );
        }

        $options = get_option( 'houzez_property_feed', array() );
        $imports = ( isset($options['imports']) && is_array($options['imports']) && !empty($options['imports']) ) ? $options['imports'] : array();

        // Remove deleted imports
        foreach ( $imports as $key => $import ) {
            if ( isset($import['deleted']) && $import['deleted'] === true ) {
                unset( $imports[$key] );
            }
        }

        if ( !isset($imports[(int)$_GET['export']]) )
        {
            die( esc_html(__( "Import not found", 'houzezpropertyfeed' ) ) );
        }

        $import = $imports[(int)$_GET['export']];

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
                            $term = get_term_by( 'id', $hid, $taxonomy_name );
                            if ( !empty($term) )
                            {
                                $import['mappings'][$field][$crm_id] = $term->name;
                            }
                        }
                    }
                }
            }
        }

        if ( !empty($import['agent_display_option_rules']) )
        {
            foreach ( $import['agent_display_option_rules'] as $i => $rule )
            {
                if ( isset($rule['result']) && !empty($rule['result']) )
                {
                    $import['agent_display_option_rules'][$i]['result'] = get_the_title((int)$rule['result']);
                }
            }
        }

        unset($import['running']);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="hpf-import.json"');
        echo json_encode($import);
        exit;
    }    
}

new Houzez_Property_Feed_Settings();