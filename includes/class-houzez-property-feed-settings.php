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

	}

    public function save_settings()
    {
        if ( !isset($_POST['save_hpf_settings']) )
        {
            return;
        }

        if ( !isset($_POST['_wpnonce']) || ( isset($_POST['_wpnonce']) && !wp_verify_nonce( $_POST['_wpnonce'], 'save-hpf-settings' ) ) ) 
        {
            die( __( "Failed security check", 'houzezpropertyfeed' ) );
        }

        $options = get_option( 'houzez_property_feed' , array() );
        if ( !is_array($options) ) { $options = array(); }

        $new_options = array(
            'email_reports' => ( ( isset($_POST['email_reports']) && $_POST['email_reports'] == 'yes' ) ? true : false ),
            'email_reports_to' => ( ( isset($_POST['email_reports_to']) && sanitize_email($_POST['email_reports_to']) ) ? sanitize_email($_POST['email_reports_to']) : '' ),
            'remove_action' => ( ( isset($_POST['remove_action']) && in_array($_POST['remove_action'], array( '', 'remove_all_media' )) ) ? sanitize_text_field($_POST['remove_action']) : '' ),
        );

        $options = array_merge( $options, $new_options );

        update_option( 'houzez_property_feed', $options );

        wp_redirect( admin_url( 'admin.php?page=houzez-property-feed&tab=settings&hpfsuccessmessage=' . __( 'Settings saved', 'houzezpropertyfeed' ) ) );
        die();
    }
}

new Houzez_Property_Feed_Settings();