<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function houzez_property_feed_get_import_frequencies()
{
    $frequencies = array(
        'daily' => array(
            'name' => __( 'Daily', 'houzezpropertyfeed' ),
            'pro' => false
        ),
         'twicedaily' => array(
            'name' => __( 'Twice Daily', 'houzezpropertyfeed' ),
            'pro' => true
        ),
        'hourly' => array(
            'name' => __( 'Hourly', 'houzezpropertyfeed' ),
            'pro' => true
        ),
        'every_fifteen_minutes' => array(
            'name' => __( 'Every Fifteen Minutes', 'houzezpropertyfeed' ),
            'pro' => true
        ),
        'exact_hours' => array(
            'name' => __( 'Exact Hours', 'houzezpropertyfeed' ),
            'pro' => true
        )
    );

    $frequencies = apply_filters( 'houzez_property_feed_import_frequencies', $frequencies );

    return $frequencies;
}

function houzez_property_feed_get_import_frequency( $key )
{
    $frequencies = houzez_property_feed_get_import_frequencies();
    
    return $frequencies[$key];
}

function houzez_property_feed_get_export_frequencies()
{
    $frequencies = array(
        'daily' => array(
            'name' => __( 'Daily', 'houzezpropertyfeed' ),
            'pro' => false
        ),
         'twicedaily' => array(
            'name' => __( 'Twice Daily', 'houzezpropertyfeed' ),
            'pro' => true
        ),
        'hourly' => array(
            'name' => __( 'Hourly', 'houzezpropertyfeed' ),
            'pro' => true
        ),
    );

    $frequencies = apply_filters( 'houzez_property_feed_export_frequencies', $frequencies );

    return $frequencies;
}

function houzez_property_feed_get_export_frequency( $key )
{
    $frequencies = houzez_property_feed_get_export_frequencies();
    
    return $frequencies[$key];
}