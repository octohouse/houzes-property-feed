<?php

function get_houzez_property_feed_formats()
{
    $formats = array(
        '10ninety' => array(
            'name' => __( '10ninety', 'houzezpropertyfeed' ),
            'fields' => array(
                array(
                    'id' => 'xml_url',
                    'label' => __( 'XML URL', 'houzezpropertyfeed' ),
                    'type' => 'text',
                    'placeholder' => 'https://',
                )
            ),
            'address_fields' => array( 'ADDRESS_2', 'ADDRESS_3', 'TOWN', 'ADDRESS_4', 'COUNTY' ),
            'taxonomy_values' => array(
                'sales_status' => array(
                    '0' => 'Available',
                    '1' => 'SSTC',
                    '2' => 'SSTCM (Scotland only)',
                    '3' => 'Under Offer',
                    '6' => 'Sold',
                ),
                'lettings_status' => array(
                    '0' => 'Available',
                    '4' => 'Reserved',
                    '5' => 'Let Agreed',
                    '7' => 'Let',
                ),
                'property_type' => array(
                    '0' => 'Not Specified',
                    '1' => 'Terraced',
                    '2' => 'End of Terrace',
                    '3' => 'Semi-Detached ',
                    '4' => 'Detached',
                    '5' => 'Mews',
                    '6' => 'Cluster House',
                    '7' => 'Ground Flat',
                    '8' => 'Flat',
                    '9' => 'Studio',
                    '10' => 'Ground Maisonette',
                    '11' => 'Maisonette',
                    '12' => 'Bungalow',
                    '13' => 'Terraced Bungalow',
                    '14' => 'Semi-Detached Bungalow',
                    '15' => 'Detached Bungalow',
                    '16' => 'Mobile Home',
                    '17' => 'Hotel',
                    '18' => 'Guest House',
                    '20' => 'Land',
                    '21' => 'Link Detached House',
                    '22' => 'Town House',
                    '23' => 'Cottage',
                    '24' => 'Chalet',
                    '27' => 'Villa',
                    '28' => 'Apartment',
                    '29' => 'Penthouse',
                    '30' => 'Finca',
                    '43' => 'Barn Conversion',
                    '44' => 'Serviced Apartments',
                    '45' => 'Parking',
                    '46' => 'Sheltered Housing',
                    '47' => 'Retirement Property',
                    '48' => 'House Share',
                    '49' => 'Flat Share',
                    '51' => 'Garages',
                    '52' => 'Farm House',
                    '53' => 'Equestrian',
                    '56' => 'Duplex',
                    '59' => 'Triplex',
                    '68' => 'Barn',
                    '95' => 'Village House',
                    '107' => 'Farm Land',
                    '110' => 'Plot',
                    '113' => 'Country House',
                    '116' => 'Stone House',
                    '117' => 'Caravan',
                    '118' => 'Lodge',
                    '120' => 'Manor House',
                    '121' => 'Stately Home',
                    '125' => 'Off-Plan',
                    '128' => 'Semi-detached Villa',
                    '131' => 'Detached Villa',
                    '142' => 'Hotel Room',
                    '143' => 'Block of Apartments',
                    '144' => 'Private Halls',
                    '253' => 'Commercial Property',
                )
            ),
            'contact_information_fields' => array(
                'BRANCH_ID',
            ),
            'help_url' => 'https://houzezpropertyfeed.com/documentation/creating-an-import/10ninety/'
        ),
        'acquaint' => array(
            'name' => __( 'Acquaint', 'houzezpropertyfeed' ),
            'fields' => array(
                array(
                    'id' => 'xml_url',
                    'label' => __( 'XML URL', 'houzezpropertyfeed' ),
                    'type' => 'text',
                    'placeholder' => 'https://',
                )
            ),
            'address_fields' => array( 'locality', 'town', 'region', 'area' ),
            'taxonomy_values' => array(
                'sales_status' => array(
                    'Available' => 'Available',
                    'Sold STC' => 'Sold STC',
                    'Under Offer' => 'Under Offer',
                    'Sold' => 'Sold',
                ),
                'lettings_status' => array(
                    'Available' => 'Available',
                    'Under Offer' => 'Under Offer',
                    'Let' => 'Let',
                ),
                'property_type' => array(
                    'House' => 'House',
                    'Detached' => 'Detached',
                    'Semi-Detached' => 'Semi-Detached',
                    'Terrace' => 'Terrace',
                    'End Terrace' => 'End Terrace',
                    'Flat' => 'Flat',
                    'Apartment' => 'Apartment',
                    'Studio' => 'Studio',
                    'Maisonette' => 'Maisonette',
                    'Bungalow' => 'Bungalow',
                    'Garage' => 'Garage',
                )
            ),
            'contact_information_fields' => array(
                'username',
            ),
            'help_url' => 'https://houzezpropertyfeed.com/documentation/creating-an-import/acquaint/'
        ),
        'apex27' => array(
            'name' => __( 'Apex27', 'houzezpropertyfeed' ),
            'fields' => array(
                array(
                    'id' => 'xml_url',
                    'label' => __( 'XML URL', 'houzezpropertyfeed' ),
                    'type' => 'text',
                    'placeholder' => 'https://',
                )
            ),
            'address_fields' => array( 'Address2', 'Address3', 'Address4', 'City', 'County' ),
            'taxonomy_values' => array(
                'sales_status' => array(
                    'Available' => 'Available',
                    'Under Offer' => 'Under Offer',
                    'SSTC' => 'SSTC',
                ),
                'lettings_status' => array(
                    'Available' => 'Available',
                    'Let Agreed' => 'Let Agreed',
                ),
                'property_type' => array(
                    'Detached House' => 'Detached House',
                    'Semi-detached House' => 'Semi-detached House',
                    'Detached Bungalow' => 'Detached Bungalow',
                    'Semi-detached Bungalow' => 'Semi-detached Bungalow',
                    'Apartment / Flat' => 'Apartment / Flat',
                )
            ),
            'contact_information_fields' => array(
                'Branch Name',
            ),
            'help_url' => 'https://houzezpropertyfeed.com/documentation/creating-an-import/apex27/'
        ),
        'loop' => array(
            'name' => __( 'Loop', 'houzezpropertyfeed' ),
            'fields' => array(
                array(
                    'id' => 'api_key',
                    'label' => __( 'API Key', 'houzezpropertyfeed' ),
                    'type' => 'text',
                )
            ),
            'address_fields' => array( 'Locality', 'Town', 'County' ),
            'taxonomy_values' => array(
                'sales_status' => array(
                    'forSale' => 'forSale',
                    'underOffer' => 'underOffer',
                    'exchanged' => 'exchanged',
                    'completed' => 'completed',
                ),
                'lettings_status' => array(
                    'toLet' => 'toLet',
                    'let' => 'let',
                ),
                'property_type' => array(
                    'terraced' => 'terraced',
                    'endOfTerrace' => 'endOfTerrace',
                    'semiDetached' => 'semiDetached',
                    'detached' => 'detached',
                    'linkDetachedHouse' => 'linkDetachedHouse',
                    'mewsHouse' => 'mewsHouse',
                    'townHouse' => 'townHouse',
                    'countryHouse' => 'countryHouse',
                    'clusterHouse' => 'clusterHouse',
                    'flat' => 'flat',
                    'apartment' => 'apartment',
                    'penthouse' => 'penthouse',
                    'groundFloorFlat' => 'groundFloorFlat',
                    'maisonette' => 'maisonette',
                    'blockOfFlats' => 'blockOfFlats',
                    'studio' => 'studio',
                    'bungalow' => 'bungalow',
                    'terracedBungalow' => 'terracedBungalow',
                    'semiDetachedBungalow' => 'semiDetachedBungalow',
                    'cottage' => 'cottage',
                    'farmOrBarn' => 'farmOrBarn',
                    'mobileOrStatic' => 'mobileOrStatic',
                    'land' => 'land',
                )
            ),
            'contact_information_fields' => array(
                'api_key',
                'creatingAgentId',
            ),
            'help_url' => 'https://houzezpropertyfeed.com/documentation/creating-an-import/loop/'
        ),
        'street' => array(
            'name' => __( 'Street', 'houzezpropertyfeed' ),
            'fields' => array(
                array(
                    'id' => 'api_key',
                    'label' => __( 'API Key', 'houzezpropertyfeed' ),
                    'type' => 'text',
                ),
                array(
                    'id' => 'base_url',
                    'label' => __( 'API Base URL', 'houzezpropertyfeed' ),
                    'type' => 'text',
                    'default' => 'https://street.co.uk',
                )
            ),
            'address_fields' => array( 'town', 'line_2', 'line_3' ),
            'taxonomy_values' => array(
                'sales_status' => array(
                    'For Sale' => 'For Sale',
                    'Under Offer' => 'Under Offer',
                    'Sold STC' => 'Sold STC',
                    'For Sale and To Let' => 'For Sale and To Let',
                ),
                'lettings_status' => array(
                    'To Let' => 'To Let',
                    'Let Agreed' => 'Let Agreed',
                    'For Sale and To Let' => 'For Sale and To Let',
                ),
                'property_type' => array(
                    'Detached House' => 'Detached House',
                    'Semi-Detached House' => 'Semi-Detached House',
                    'Terraced House' => 'Terraced House',
                    'Bungalow' => 'Bungalow',
                    'Flat / Apartment' => 'Flat / Apartment',
                    'Other' => 'Other',
                )
            ),
            'contact_information_fields' => array(
                'branch_uuid',
            ),
            'help_url' => 'https://houzezpropertyfeed.com/documentation/creating-an-import/street/'
        ),
    );

    $formats = apply_filters( 'houzez_property_feed_import_formats', $formats );

    return $formats;
}

function get_houzez_property_feed_format( $key )
{
    $formats = get_houzez_property_feed_formats();
    
    return isset($formats[$key]) ? $formats[$key] : false;
}

function get_format_from_import_id( $import_id )
{
    $formats = get_houzez_property_feed_formats();

    $options = get_option( 'houzez_property_feed' , array() );
    $imports = ( isset($options['imports']) && is_array($options['imports']) && !empty($options['imports']) ) ? $options['imports'] : array();

    if ( isset($imports[$import_id]) )
    {
        $format = $imports[$import_id]['format'];

        return get_houzez_property_feed_format( $format );
    }
    
    return false;
}