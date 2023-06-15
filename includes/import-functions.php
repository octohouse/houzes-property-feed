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

function convert_old_field_mapping_to_new( $field_mapping_rules )
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