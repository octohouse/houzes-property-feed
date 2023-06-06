<?php

function get_import_settings_from_id( $import_id )
{
    $options = get_option( 'houzez_property_feed' , array() );
    $imports = ( isset($options['imports']) && is_array($options['imports']) && !empty($options['imports']) ) ? $options['imports'] : array();

    if ( isset($imports[$import_id]) )
    {
        return $imports[$import_id];
    }

    return false;
}