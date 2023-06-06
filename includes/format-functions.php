<?php

function get_houzez_property_feed_formats()
{
    $formats = array(
        'loop' => array(
            'name' => 'Loop',
            'fields' => array(
                array(
                    'id' => 'api_key',
                    'label' => 'API Key',
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
            'name' => 'Street',
            'fields' => array(
                array(
                    'id' => 'api_key',
                    'label' => 'API Key',
                    'type' => 'text',
                ),
                array(
                    'id' => 'base_url',
                    'label' => 'API Base URL',
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