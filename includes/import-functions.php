<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function houzez_property_feed_get_import_settings_from_id( $import_id )
{
    $options = get_option( 'houzez_property_feed' , array() );
    $imports = ( isset($options['imports']) && is_array($options['imports']) && !empty($options['imports']) ) ? $options['imports'] : array();

    if ( isset($imports[$import_id]) )
    {
        return $imports[$import_id];
    }

    return false;
}

function houzez_property_feed_convert_old_field_mapping_to_new( $field_mapping_rules )
{
    $old_style = false;

    foreach ( $field_mapping_rules as $rule )
    {
        if ( isset($rule['field']) )
        {
            $old_style = true;
            break;
        }
    }

    if ( $old_style === true )
    {
        // need to convert
        $new_field_mapping_rules = array();
        foreach ( $field_mapping_rules as $rule )
        {
            if ( $rule['result'] == '{field_value}' )
            {
                $rule['result'] = '{' . $rule['field'] . '}';
            }

            $new_field_mapping_rules[] = array(
                'houzez_field' => $rule['houzez_field'],
                'result' => $rule['result'],
                'rules' => array(
                    array(
                        'field' => $rule['field'],
                        'equal' => $rule['equal']
                    )
                )
            );
        }
        $field_mapping_rules = $new_field_mapping_rules;
    }

    return $field_mapping_rules;
}

function houzez_property_feed_get_fields_for_field_mapping()
{
    $houzez_fields = array(
        // Post Fields
        'post_title' => array( 'type' => 'post_field', 'label' => __( 'Post Title', 'houzez' ) ),
        'post_excerpt' => array( 'type' => 'post_field', 'label' => __( 'Post Excerpt / Summary Description', 'houzez' ) ),
        'post_content' => array( 'type' => 'post_field', 'label' => __( 'Post Content / Full Description', 'houzez' ) ),
        'post_status' => array( 'type' => 'post_field', 'label' => __( 'Post Status', 'houzez' ), 'options' => array( 'publish' => __( 'Publish', 'houzezpropertyfeed' ), 'private' => __( 'Private', 'houzezpropertyfeed' ), 'draft' => __( 'Draft', 'houzezpropertyfeed' ) ) ),
        'post_name' => array( 'type' => 'post_field', 'label' => __( 'Post URL / Permalink', 'houzez' ) ),
        // Houzez Fields
        'fave_property_sec_price' => array( 'type' => 'meta', 'label' => __( 'Second Price (Optional)', 'houzez' ) ),
        'fave_property_price_prefix' => array( 'type' => 'meta', 'label' => __( 'Price Prefix', 'houzez' ) ),
        'fave_property_price_postfix' => array( 'type' => 'meta', 'label' => __( 'Price Postfix', 'houzez' ) ),
        'fave_property_size' => array( 'type' => 'meta', 'label' => __( 'Area Size', 'houzez' ) ),
        'fave_property_size_prefix' => array( 'type' => 'meta', 'label' => __( 'Area Size Postfix', 'houzez' ) ),
        'fave_property_bedrooms' => array( 'type' => 'meta', 'label' => __( 'Bedrooms', 'houzez' ) ),
        'fave_property_rooms' => array( 'type' => 'meta', 'label' => __( 'Rooms', 'houzez' ) ),
        'fave_property_bathrooms' => array( 'type' => 'meta', 'label' => __( 'Bathrooms', 'houzez' ) ),
        'fave_property_garage' => array( 'type' => 'meta','label' =>  __( 'Garages', 'houzez' ) ),
        'fave_property_garage_size' => array( 'type' => 'meta', 'label' => __( 'Garage Size', 'houzez' ) ),
        'fave_property_year' => array( 'type' => 'meta', 'label' => __( 'Year Built', 'houzez' ) ),
        'fave_property_id' => array( 'type' => 'meta', 'label' => __( 'Property ID / Reference Number', 'houzez' ) ),
        'fave_property_address' => array( 'type' => 'meta', 'label' => __( 'Street Address', 'houzez' ) ),
        'fave_property_zip' => array( 'type' => 'meta', 'label' => __( 'Zip/Postal Code', 'houzez' ) ),
        'fave_property_map' => array( 'type' => 'meta', 'label' => __( 'Show Map', 'houzez' ), 'options' => array( 0 => 0, 1 => 1 ) ),
        'fave_property_map_street_view' => array( 'type' => 'meta', 'label' => __( 'Show Street View', 'houzez' ), 'options' => array( 'hide' => 'Hide', 'show' => 'Show' ) ),
        'fave_property_map_address' => array( 'type' => 'meta', 'label' => __( 'Map Address', 'houzez' ) ),
        'houzez_geolocation_lat' => array( 'type' => 'meta', 'label' => __( 'Latitude', 'houzez' ) ),
        'houzez_geolocation_long' => array( 'type' => 'meta', 'label' => __( 'Longitude', 'houzez' ) ),
        'fave_property_location' => array( 'type' => 'meta', 'label' => __( 'Location', 'houzez' ) . ' (format: lat,lng,zoom)' ),
        'fave_featured' => array( 'type' => 'meta', 'label' => __( 'Featured', 'houzez' ), 'options' => array( 0 => 0, 1 => 1 ) ),
        'fave_property_disclaimer' => array( 'type' => 'meta', 'label' => __( 'Disclaimer', 'houzez' ) ),
        'fave_video_url' => array( 'type' => 'meta', 'label' => __( 'Video URL', 'houzez' ) ),
        'fave_virtual_tour' => array( 'type' => 'meta', 'label' => __( '360° Virtual Tour', 'houzez' ) ),
        'fave_energy_class' => array( 'type' => 'meta', 'label' => __( 'Energy Class', 'houzez' ) ),
        'fave_energy_global_index' => array( 'type' => 'meta', 'label' => __( 'Global Energy Performance Index', 'houzez' ) ),
        'fave_renewable_energy_global_index' => array( 'type' => 'meta', 'label' => __( 'Renewable energy performance index', 'houzez' ) ),
        'fave_energy_performance' => array( 'type' => 'meta', 'label' => __( 'Energy performance of the building', 'houzez' ) ),
        'fave_epc_current_rating' => array( 'type' => 'meta', 'label' => __( 'EPC Current Rating', 'houzez' ) ),
        'fave_epc_potential_rating' => array( 'type' => 'meta', 'label' => __( 'EPC Potential Rating', 'houzez' ) ),
        'fave_property_land' => array( 'type' => 'meta', 'label' => __( 'Land Area', 'houzez' ) ),
        'fave_property_land_postfix' => array( 'type' => 'meta', 'label' => __( 'Land Area Size Postfix', 'houzez' ) ),
        'fave_property_price' => array( 'type' => 'meta', 'label' => __( 'Sale or Rent Price', 'houzez' ) ),
        'fave_single_top_area' => array( 'type' => 'meta', 'label' => __( 'Property Top Type', 'houzez' ), 'options' => array(
            'global' => esc_html__( 'Global', 'houzez' ),
            'v1' => esc_html__( 'Version 1', 'houzez' ),
            'v2' => esc_html__( 'Version 2', 'houzez' ),
            'v3' => esc_html__( 'Version 3', 'houzez' ),
            'v4' => esc_html__( 'Version 4', 'houzez' ),
            'v5' => esc_html__( 'Version 5', 'houzez' ),
            'v6' => esc_html__( 'Version 6', 'houzez' ),
            'v7' => esc_html__( 'Version 7', 'houzez' )
        ) ),
        'fave_single_content_area' => array( 'type' => 'meta', 'label' => __( 'Property Content Layout', 'houzez' ), 'options' => array(
            'global' => esc_html__( 'Global', 'houzez' ),
            'simple' => esc_html__( 'Default', 'houzez' ),
            'tabs'   => esc_html__( 'Tabs', 'houzez' ),
            'tabs-vertical' => esc_html__( 'Tabs Vertical', 'houzez' ),
            'v2' => esc_html__( 'Luxury Homes', 'houzez' ),
            'minimal' => esc_html__( 'Minimal', 'houzez' ),
            'boxed' => esc_html__( 'Boxed', 'houzez' )
        ) ),
    );

    if ( fave_option('multi_currency') == 1 )
    {
        $options = array();
        if ( class_exists('Houzez_Currencies') )
        {
            $form_fields = Houzez_Currencies::get_form_fields();

            if ($form_fields )
            {
                foreach ( $form_fields as $data ) 
                { 
                    $options[$data->currency_code] = $data->currency_name . ' (' . $data->currency_code . ')';
                }
            }
        }
        $houzez_fields['fave_currency'] = array( 'type' => 'meta', 'label' => __( 'Currency', 'houzez' ), 'options' => $options );
    }
    
    // Contact agent related fields
    $fave_agent_display_options = array(
        'author_info' => __( 'Author / WordPress User', 'houzezpropertyfeed' )
    );

    $houzez_ptype_settings = get_option('houzez_ptype_settings', array() );

    if ( !isset($houzez_ptype_settings['houzez_agents_post']) || ( isset($houzez_ptype_settings['houzez_agents_post']) && $houzez_ptype_settings['houzez_agents_post'] != 'disabled' ) )
    {
        $fave_agent_display_options['agent_info'] = __( 'Houzez Agent', 'houzezpropertyfeed' );
    }
    if ( !isset($houzez_ptype_settings['houzez_agencies_post']) || ( isset($houzez_ptype_settings['houzez_agencies_post']) && $houzez_ptype_settings['houzez_agencies_post'] != 'disabled' ) )
    {
        $fave_agent_display_options['agency_info'] = __( 'Houzez Agency', 'houzezpropertyfeed' );
    }

    $fave_agent_display_options['none'] = __( 'Do Not Display', 'houzezpropertyfeed' );

    $houzez_fields['fave_agent_display_option'] = array( 'type' => 'meta', 'label' => __( 'Agent Display Option', 'houzez' ), 'options' => $fave_agent_display_options );

    // user/agent/agency fields
    $wp_users = array();

    $users = get_users( array( 'orderby' => 'name' ) );
    foreach ( $users as $user ) 
    {
        $wp_users[$user->ID] = $user->display_name;
    }

    $houzez_fields['post_author'] = array( 'type' => 'post_field', 'label' => __( 'Author / WordPress User', 'houzez' ), 'options' => $wp_users );

    if ( !isset($houzez_ptype_settings['houzez_agents_post']) || ( isset($houzez_ptype_settings['houzez_agents_post']) && $houzez_ptype_settings['houzez_agents_post'] != 'disabled' ) )
    {
        $houzez_agents = array();

        $houzez_agents['auto'] = __( 'Automatically match based on name', 'houzezpropertyfeed' );

        $args = array(
            'post_type' => 'houzez_agent',
            'nopaging' => true
        );

        $agent_query = new WP_Query( $args );

        if ( $agent_query->have_posts() )
        {
            while ( $agent_query->have_posts() )
            {
                $agent_query->the_post();

                $houzez_agents[get_the_ID()] = get_the_title();
            }
        }
        wp_reset_postdata();

        $houzez_fields['fave_agents'] = array( 'type' => 'meta', 'label' => __( 'Agent Name', 'houzez' ), 'options' => $houzez_agents );
    }

    if ( !isset($houzez_ptype_settings['houzez_agencies_post']) || ( isset($houzez_ptype_settings['houzez_agencies_post']) && $houzez_ptype_settings['houzez_agencies_post'] != 'disabled' ) )
    {
        $houzez_agencies = array();

        $houzez_agencies['auto'] = __( 'Automatically match based on name', 'houzezpropertyfeed' );

        $args = array(
            'post_type' => 'houzez_agency',
            'nopaging' => true
        );

        $agency_query = new WP_Query( $args );

        if ( $agency_query->have_posts() )
        {
            while ( $agency_query->have_posts() )
            {
                $agency_query->the_post();

                $houzez_agencies[get_the_ID()] = get_the_title();
            }
        }
        wp_reset_postdata();

        $houzez_fields['fave_property_agency'] = array( 'type' => 'meta', 'label' => __( 'Agency Name', 'houzez' ), 'options' => $houzez_agencies );
    }

    // add any fields from field builder
    $houzez_fields_builder = new Houzez_Fields_Builder();
    $houzez_fields_built = $houzez_fields_builder::get_form_fields();

    if ( $houzez_fields_built !== FALSE && is_array($houzez_fields_built) && !empty($houzez_fields_built) )
    {
        foreach ( $houzez_fields_built as $field_build )
        {
            $houzez_fields['fave_' . $field_build->field_id] = array( 'type' => 'meta', 'label' => __( $field_build->label, 'houzez' ), 'custom_field' => true, 'field_type' => $field_build->type );
        }
    }

    $taxonomies = array(
        'property_type' => array( 'type' => 'taxonomy', 'label' => __( 'Property Type', 'houzez' ) ),
        'property_status' => array( 'type' => 'taxonomy', 'label' => __( 'Status', 'houzez' ) ),
        'property_label' => array( 'type' => 'taxonomy', 'label' => __( 'Labels', 'houzez' ), 'delimited' => true ),
        'property_feature' => array( 'type' => 'taxonomy', 'label' => __( 'Property Features', 'houzez' ), 'delimited' => true ),
    );

    for ( $i = 0; $i < apply_filters( 'houzez_property_feed_field_mapping_label_count', 10 ); ++$i )
    {
        $taxonomies['property_label[' . $i . ']'] = array( 'type' => 'taxonomy', 'label' => __( 'Label', 'houzez' ) . ' ' . ( $i + 1 ) );
    }

    for ( $i = 0; $i < apply_filters( 'houzez_property_feed_field_mapping_feature_count', 10 ); ++$i )
    {
        $taxonomies['property_feature[' . $i . ']'] = array( 'type' => 'taxonomy', 'label' => __( 'Property Feature', 'houzez' ) . ' ' . ( $i + 1 ) );
    }

    $houzez_tax_settings = get_option('houzez_tax_settings', array() );
    if ( !isset($houzez_tax_settings['property_city']) || ( isset($houzez_tax_settings['property_city']) && $houzez_tax_settings['property_city'] != 'disabled' ) )
    {
        $taxonomies['property_city'] = array( 'type' => 'taxonomy', 'label' => __( 'City', 'houzez' ) );
    }
    if ( !isset($houzez_tax_settings['property_area']) || ( isset($houzez_tax_settings['property_area']) && $houzez_tax_settings['property_area'] != 'disabled' ) )
    {
        $taxonomies['property_area'] = array( 'type' => 'taxonomy', 'label' => __( 'Area', 'houzez' ) );
    }
    if ( !isset($houzez_tax_settings['property_state']) || ( isset($houzez_tax_settings['property_state']) && $houzez_tax_settings['property_state'] != 'disabled' ) )
    {
        $taxonomies['property_state'] = array( 'type' => 'taxonomy', 'label' => __( 'County / State', 'houzez' ) );
    }
    if ( !isset($houzez_tax_settings['property_country']) || ( isset($houzez_tax_settings['property_country']) && $houzez_tax_settings['property_country'] != 'disabled' ) )
    {
        $taxonomies['property_country'] = array( 'type' => 'taxonomy', 'label' => __( 'Country', 'houzez' ) );
    }

    $houzez_fields = array_merge($houzez_fields, $taxonomies);

    // ACF
    if ( function_exists('acf_get_field_groups') ) 
    {
        $field_groups = acf_get_field_groups(['post_type' => 'property']);

        foreach ( $field_groups as $group ) 
        {
            // Get all fields for this field group
            $group_fields = acf_get_fields($group['key']);

            if ( $group_fields ) 
            {
                foreach ( $group_fields as $field )
                {
                    if ( in_array($field['type'], ['text', 'number', 'email', 'textarea', 'url']) ) 
                    {
                        $houzez_fields[$field['name']] = array( 
                            'type' => 'meta', 
                            'label' => __( $field['label'], 'houzez' ) . ' (Added via ACF)', 
                            'acf' => true
                        );
                    }
                    elseif ( in_array($field['type'], ['select', 'radio']) )
                    {
                        $houzez_fields[$field['name']] = array(
                            'type'    => 'meta',
                            'label'   => __( $field['label'], 'houzez' ) . ' (Added via ACF)',
                            'options' => $field['choices'],
                            'acf' => true
                        );
                    }
                }
            }
        }
    }

    $houzez_fields = apply_filters( 'houzez_property_feed_field_mapping_houzez_fields', $houzez_fields );

    $houzez_fields = houzez_property_feed_array_msort( $houzez_fields, array( 'label' => SORT_ASC ) );

    return $houzez_fields;
}

function hpf_determine_number_separators($number) 
{
    $decimalSeparator = '';
    $thousandSeparator = '';

    // Count occurrences
    $commaCount = substr_count($number, ',');
    $periodCount = substr_count($number, '.');

    // Logic to determine separators
    if ($commaCount > 0 && $periodCount > 0) {
        // Both symbols are present, determine based on position
        if (strrpos($number, ',') > strrpos($number, '.')) {
            $decimalSeparator = ',';
            $thousandSeparator = '.';
        } else {
            $decimalSeparator = '.';
            $thousandSeparator = ',';
        }
    } elseif ($commaCount > 0) {
        // Only commas are present
        if (($commaCount == 1 && strlen($number) - strrpos($number, ',') > 3) || ($commaCount > 1 && hpf_is_thousands_grouping($number, ','))) {
            // Single comma or valid thousands grouping
            $thousandSeparator = ',';
            $decimalSeparator = '.';
        } else {
            // Comma likely used as decimal separator
            $decimalSeparator = ',';
            $thousandSeparator = '.';
        }
    } elseif ($periodCount > 0) {
        // Only periods are present
        if (($periodCount == 1 && strlen($number) - strrpos($number, '.') > 3) || ($periodCount > 1 && hpf_is_thousands_grouping($number, '.'))) {
            // Single period or valid thousands grouping
            $thousandSeparator = '.';
            $decimalSeparator = ',';
        } else {
            // Period likely used as decimal separator
            $decimalSeparator = '.';
            $thousandSeparator = ',';
        }
    } else {
        // No separators found, default to common usage
        $decimalSeparator = '.';
        $thousandSeparator = ',';
    }

    return ['decimal' => $decimalSeparator, 'thousand' => $thousandSeparator];
}

// Helper function to check for consistent thousands grouping
function hpf_is_thousands_grouping($number, $separator) {
    $parts = explode($separator, $number);

    // Allow the first part to have fewer than 3 digits
    foreach (array_slice($parts, 1, -1) as $part) 
    {
        if (strlen($part) !== 3) return false;
    }

    return true;
}

function hpf_get_import_object_from_format($format, $instance_id, $import_id)
{
    switch ($format)
    {
        case "10ninety":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-10ninety.php';

            $import_object = new Houzez_Property_Feed_Format_10ninety( $instance_id, $import_id );

            break;
        }
        case "acquaint":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-acquaint.php';

            $import_object = new Houzez_Property_Feed_Format_Acquaint( $instance_id, $import_id );

            break;
        }
        case "agentos":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-agentos.php';

            $import_object = new Houzez_Property_Feed_Format_Agentos( $instance_id, $import_id );

            break;
        }
        case "agestanet":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-agestanet.php';

            $import_object = new Houzez_Property_Feed_Format_Agestanet( $instance_id, $import_id );

            break;
        }
        case "alto":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-alto.php';

            $import_object = new Houzez_Property_Feed_Format_Alto( $instance_id, $import_id );

            break;
        }
        case "amplify_syndication":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-amplify-syndication.php';

            $import_object = new Houzez_Property_Feed_Format_Amplify_Syndication( $instance_id, $import_id );

            break;
        }
        case "apex27":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-apex27.php';

            $import_object = new Houzez_Property_Feed_Format_Apex27( $instance_id, $import_id );

            break;
        }
        case "apimo":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-apimo.php';

            $import_object = new Houzez_Property_Feed_Format_Apimo( $instance_id, $import_id );

            break;
        }
        case "bdp":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-bdp.php';

            $import_object = new Houzez_Property_Feed_Format_Bdp( $instance_id, $import_id );

            break;
        }
        case "behomes":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-behomes.php';

            $import_object = new Houzez_Property_Feed_Format_Behomes( $instance_id, $import_id );

            break;
        }
        case "blm_local":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-blm.php';

            $import_object = new Houzez_Property_Feed_Format_Blm( $instance_id, $import_id );

            break;
        }
        case "blm_remote":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-blm.php';

            $import_object = new Houzez_Property_Feed_Format_Blm( $instance_id, $import_id );

            break;
        }
        case "bridge":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-bridge.php';

            $import_object = new Houzez_Property_Feed_Format_Bridge( $instance_id, $import_id );

            break;
        }
        case "casafari":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-casafari.php';

            $import_object = new Houzez_Property_Feed_Format_Casafari( $instance_id, $import_id );

            break;
        }
        case "csv":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-csv.php';

            $import_object = new Houzez_Property_Feed_Format_Csv( $instance_id, $import_id );

            break;
        }
        case "dezrez_rezi":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-dezrez-rezi.php';

            $import_object = new Houzez_Property_Feed_Format_Dezrez_Rezi( $instance_id, $import_id );

            break;
        }
        case "domus":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-domus.php';

            $import_object = new Houzez_Property_Feed_Format_Domus( $instance_id, $import_id );

            break;
        }
        case "ego":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-ego.php';

            $import_object = new Houzez_Property_Feed_Format_Ego( $instance_id, $import_id );

            break;
        }
        case "expertagent":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-expertagent.php';

            $import_object = new Houzez_Property_Feed_Format_Expertagent( $instance_id, $import_id );

            break;
        }
        case "getrix":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-getrix.php';

            $import_object = new Houzez_Property_Feed_Format_Getrix( $instance_id, $import_id );

            break;
        }
        case "gnomen":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-gnomen.php';

            $import_object = new Houzez_Property_Feed_Format_Gnomen( $instance_id, $import_id );

            break;
        }
        case "idealista":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-idealista.php';

            $import_object = new Houzez_Property_Feed_Format_Idealista( $instance_id, $import_id );

            break;
        }
        case "infocasa":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-infocasa.php';

            $import_object = new Houzez_Property_Feed_Format_Infocasa( $instance_id, $import_id );

            break;
        }
        case "inmobalia":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-inmobalia.php';

            $import_object = new Houzez_Property_Feed_Format_Inmobalia( $instance_id, $import_id );

            break;
        }
        case "inmovilla":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-inmovilla.php';

            $import_object = new Houzez_Property_Feed_Format_Inmovilla( $instance_id, $import_id );

            break;
        }
        case "inmoweb":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-inmoweb.php';

            $import_object = new Houzez_Property_Feed_Format_Inmoweb( $instance_id, $import_id );

            break;
        }
        case "jupix":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-jupix.php';

            $import_object = new Houzez_Property_Feed_Format_Jupix( $instance_id, $import_id );

            break;
        }
        case "kato_xml":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-kato-xml.php';

            $import_object = new Houzez_Property_Feed_Format_Kato_Xml( $instance_id, $import_id );

            break;
        }
        case "kyero":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-kyero.php';

            $import_object = new Houzez_Property_Feed_Format_Kyero( $instance_id, $import_id );

            break;
        }
        case "loop":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-loop.php';

            $import_object = new Houzez_Property_Feed_Format_Loop( $instance_id, $import_id );

            break;
        }
        case "mls_grid":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-mls-grid.php';

            $import_object = new Houzez_Property_Feed_Format_Mls_Grid( $instance_id, $import_id );

            break;
        }
        case "mri":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-mri.php';

            $import_object = new Houzez_Property_Feed_Format_Mri( $instance_id, $import_id );

            break;
        }
        case "openimmo_local":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-openimmo.php';

            $import_object = new Houzez_Property_Feed_Format_Openimmo( $instance_id, $import_id );

            break;
        }
        case "pixxi":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-pixxi.php';

            $import_object = new Houzez_Property_Feed_Format_Pixxi( $instance_id, $import_id );

            break;
        }
        case "propconnect":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-propconnect.php';

            $import_object = new Houzez_Property_Feed_Format_Propconnect( $instance_id, $import_id );

            break;
        }
        case "propctrl":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-propctrl.php';

            $import_object = new Houzez_Property_Feed_Format_Propctrl( $instance_id, $import_id );

            break;
        }
        case "property_finder":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-property-finder.php';

            $import_object = new Houzez_Property_Feed_Format_Property_Finder( $instance_id, $import_id );

            break;
        }
        case "propstack":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-propstack.php';

            $import_object = new Houzez_Property_Feed_Format_Propstack( $instance_id, $import_id );

            break;
        }
        case "reapit_foundations":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-reapit-foundations.php';

            $import_object = new Houzez_Property_Feed_Format_Reapit_Foundations( $instance_id, $import_id );

            break;
        }
        case "reaxml_local":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-reaxml.php';

            $import_object = new Houzez_Property_Feed_Format_REAXML( $instance_id, $import_id );

            break;
        }
        case "reaxml_remote":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-reaxml.php';

            $import_object = new Houzez_Property_Feed_Format_REAXML( $instance_id, $import_id );

            break;
        }
        case "remax":
        {
            // includes
            require_once dirname( __FILE__ ) . '/awsv4.php';
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-remax.php';

            $import_object = new Houzez_Property_Feed_Format_Remax( $instance_id, $import_id );

            break;
        }
        case "rentman":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-rentman.php';

            $import_object = new Houzez_Property_Feed_Format_Rentman( $instance_id, $import_id );

            break;
        }
        case "resales_online":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-resales-online.php';

            $import_object = new Houzez_Property_Feed_Format_Resales_Online( $instance_id, $import_id );

            break;
        }
        case "resales_online_api":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-resales-online-api.php';

            $import_object = new Houzez_Property_Feed_Format_Resales_Online_API( $instance_id, $import_id );

            break;
        }
        case "rex":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-rex.php';

            $import_object = new Houzez_Property_Feed_Format_Rex( $instance_id, $import_id );

            break;
        }
        case "sme_professional_json":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-sme-professional-json.php';

            $import_object = new Houzez_Property_Feed_Format_SME_Professional_JSON( $instance_id, $import_id );

            break;
        }
        case "spark":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-spark.php';

            $import_object = new Houzez_Property_Feed_Format_Spark( $instance_id, $import_id );

            break;
        }
        case "street":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-street.php';

            $import_object = new Houzez_Property_Feed_Format_Street( $instance_id, $import_id );

            break;
        }
        case "thinkspain":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-thinkspain.php';

            $import_object = new Houzez_Property_Feed_Format_Thinkspain( $instance_id, $import_id );

            break;
        }
        case "trestle":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-trestle.php';

            $import_object = new Houzez_Property_Feed_Format_Trestle( $instance_id, $import_id );

            break;
        }
        case "vaultea":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-vaultea.php';

            $import_object = new Houzez_Property_Feed_Format_Vaultea( $instance_id, $import_id );

            break;
        }
        case "wp_rest_api_houzez":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-wp-rest-api-houzez.php';

            $import_object = new Houzez_Property_Feed_Format_Wp_Rest_Api_Houzez( $instance_id, $import_id );

            break;
        }
        case "xml":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-xml.php';

            $import_object = new Houzez_Property_Feed_Format_Xml( $instance_id, $import_id );

            break;
        }
        case "xml2u":
        {
            // includes
            require_once dirname( __FILE__ ) . '/import-formats/class-houzez-property-feed-format-xml2u.php';

            $import_object = new Houzez_Property_Feed_Format_Xml2u( $instance_id, $import_id );

            break;
        }
        default:
        {
            $import_object = apply_filters( 'houzez_property_feed_import_object', null, $instance_id, $import_id );
        }
    }

    return $import_object;
}

// Deprecated functions:
function get_import_settings_from_id( $import_id )
{
    return houzez_property_feed_get_import_settings_from_id($import_id);
}