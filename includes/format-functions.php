<?php

function get_houzez_property_feed_formats()
{
    $curl_warning = !function_exists('curl_version') ? __( 'cURL must be enabled in order to use this format', 'houzezpropertyfeed' ) : '';
    $simplexml_warning = !class_exists('SimpleXMLElement') ? __( 'SimpleXML must be enabled in order to use this format', 'houzezpropertyfeed' ) : '';

    $uploads_dir = wp_upload_dir();
    if( $uploads_dir['error'] === FALSE )
    {
        $uploads_dir = $uploads_dir['basedir'] . '/houzez_property_feed_import/';
    }

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
            'help_url' => 'https://houzezpropertyfeed.com/documentation/managing-imports/formats/10ninety/',
            'warnings' => array_filter( array( $simplexml_warning ) ),
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
            'help_url' => 'https://houzezpropertyfeed.com/documentation/managing-imports/formats/acquaint/',
            'warnings' => array_filter( array( $simplexml_warning ) ),
        ),
        'agentos' => array(
            'name' => __( 'agentOS', 'houzezpropertyfeed' ),
            'fields' => array(
                array(
                    'id' => 'api_key',
                    'label' => __( 'API Key', 'houzezpropertyfeed' ),
                    'type' => 'text',
                ),
                array(
                    'id' => 'short_name',
                    'label' => __( 'Short Name', 'houzezpropertyfeed' ),
                    'type' => 'text',
                ),
            ),
            'address_fields' => array( 'Address2', 'Address3', 'Address4' ),
            'taxonomy_values' => array(
                'sales_status' => array(
                    'For Sale' => 'For Sale',
                    'Under Offer' => 'Under Offer',
                ),
                'lettings_status' => array(
                    'To Let' => 'To Let',
                    'Let Agreed' => 'Let Agreed',
                ),
                'property_type' => array(
                    'House' => 'House',
                    'DetachedHouse' => 'DetachedHouse',
                    'SemiDetachedHouse' => 'SemiDetachedHouse',
                    'TerracedHouse' => 'TerracedHouse',
                    'EndTerraceHouse' => 'EndTerraceHouse',
                    'Cottage' => 'Cottage',
                    'Bungalow' => 'Bungalow',
                    'FlatApartment' => 'FlatApartment',
                    'HouseFlatShare' => 'HouseFlatShare',
                )
            ),
            'contact_information_fields' => array(
                'BranchOID',
            ),
            'help_url' => 'https://houzezpropertyfeed.com/documentation/managing-imports/formats/agentos/',
            'warnings' => array( __( 'AgentOS are very strict on the number of requests made per minute. As it takes so many individual requests to obtain the data we require, we\'ve had to add pauses to prevent you hitting this throttling limit. As a result, imports from AgentOS may take a while and therefore you\'ll likely need to increase the timeout limit on your server.', 'houzezpropertyfeed' ) ),
        ),
        'alto' => array(
            'name' => __( 'Alto by Vebra', 'houzezpropertyfeed' ),
            'fields' => array(
                array(
                    'id' => 'datafeed_id',
                    'label' => __( 'Datafeed ID', 'houzezpropertyfeed' ),
                    'type' => 'text',
                ),
                array(
                    'id' => 'username',
                    'label' => __( 'Username', 'houzezpropertyfeed' ),
                    'type' => 'text',
                ),
                array(
                    'id' => 'password',
                    'label' => __( 'Password', 'houzezpropertyfeed' ),
                    'type' => 'text',
                ),
            ),
            'address_fields' => array( 'locality', 'town', 'county' ),
            'taxonomy_values' => array(
                'sales_status' => array(
                     '0' => 'For Sale',
                    '1' => 'Under Offer',
                    '2' => 'Sold',
                    '3' => 'SSTC',
                    '4' => 'For Sale By Auction',
                    '5' => 'Reserved',
                    '6' => 'New Instruction',
                    '7' => 'Just on Market',
                    '8' => 'Price Reduction',
                    '9' => 'Keen to Sell',
                    '10' => 'No Chain',
                    '11' => 'Vendor will pay stamp duty',
                    '12' => 'Offers in the region of',
                    '13' => 'Guide Price',
                    '200' => 'For Sale',
                    '201' => 'Under Offer',
                    '202' => 'Sold',
                    '203' => 'SSTC',
                ),
                'lettings_status' => array(
                    '0' => 'To Let',
                    '1' => 'Let',
                    '2' => 'Under Offer',
                    '3' => 'Reserved',
                    '4' => 'Let Agreed',
                    '100' => 'To Let',
                    '101' => 'Let',
                    '102' => 'Under Offer',
                    '103' => 'Reserved',
                    '104' => 'Let Agreed',
                    '200' => 'To Let',
                    '214' => 'Let',
                ),
                'property_type' => array(
                    'House' => 'House',
                    'Flat' => 'Flat',
                )
            ),
            'contact_information_fields' => array(
                'firmid',
                'branchid',
            ),
            'help_url' => 'https://houzezpropertyfeed.com/documentation/managing-imports/formats/alto/',
            'warnings' => array_filter( array( $curl_warning, $simplexml_warning ) ),
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
            'help_url' => 'https://houzezpropertyfeed.com/documentation/managing-imports/formats/apex27/',
            'warnings' => array_filter( array( $simplexml_warning ) ),
        ),
        'bdp' => array(
            'name' => __( 'BDP', 'houzezpropertyfeed' ),
            'fields' => array(
                array(
                    'id' => 'api_key',
                    'label' => __( 'API Key', 'houzezpropertyfeed' ),
                    'type' => 'text',
                ),
                array(
                    'id' => 'secret',
                    'label' => __( 'Secret', 'houzezpropertyfeed' ),
                    'type' => 'text',
                ),
                array(
                    'id' => 'account_id',
                    'label' => __( 'Account ID', 'houzezpropertyfeed' ),
                    'type' => 'text',
                ),
            ),
            'address_fields' => array( 'addrL1', 'addrL2', 'addrL3', 'town' ),
            'taxonomy_values' => array(
                'sales_status' => array(
                    'Available' => 'Available',
                    'Under Offer' => 'Under Offer',
                ),
                'lettings_status' => array(
                    'Available' => 'Available',
                ),
                'property_type' => array(
                    'Bungalow' => 'Bungalow',
                    'Detached Bungalow' => 'Detached Bungalow',
                    'House' => 'House',
                    'Detached' => 'Detached',
                    'Semi-Detached' => 'Semi-Detached',
                    'Terraced' => 'Terraced',
                    'Townhouse' => 'Townhouse',
                    'Flat / Apartment' => 'Flat / Apartment',
                )
            ),
            'contact_information_fields' => array(
                'firmName',
                'branch_id',
                'branchName',
            ),
            'help_url' => 'https://houzezpropertyfeed.com/documentation/managing-imports/formats/bdp/',
            'warnings' => array_filter( array( $curl_warning ) ),
        ),
        'blm_local' => array(
            'name' => __( 'BLM - Local Directory', 'houzezpropertyfeed' ),
            'fields' => array(
                array(
                    'id' => 'local_directory',
                    'label' => __( 'Local Directory', 'houzezpropertyfeed' ),
                    'type' => 'text',
                    'default' => $uploads_dir,
                    'tooltip' => __( 'The full server path to where the BLM files will be received into', 'houzezpropertyfeed' ),
                ),
            ),
            'address_fields' => array( 'ADDRESS_2', 'ADDRESS_3', 'ADDRESS_4', 'TOWN', 'COUNTY' ),
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
                    '62' => 'Longere',
                    '65' => 'Gite',
                    '68' => 'Barn',
                    '71' => 'Trulli',
                    '74' => 'Mill',
                    '77' => 'Ruins',
                    '89' => 'Trulli',
                    '92' => 'Castle',
                    '95' => 'Village House',
                    '101' => 'Cave House',
                    '104' => 'Cortijo',
                    '107' => 'Farm Land',
                    '110' => 'Plot',
                    '113' => 'Country House',
                    '116' => 'Stone House',
                    '117' => 'Caravan',
                    '118' => 'Lodge',
                    '119' => 'Log Cabin',
                    '120' => 'Manor House',
                    '121' => 'Stately Home',
                    '125' => 'Off-Plan',
                    '128' => 'Semi-detached Villa',
                    '131' => 'Detached Villa',
                    '140' => 'Riad',
                    '141' => 'House Boat',
                    '142' => 'Hotel Room',
                    '143' => 'Block of Apartments',
                    '144' => 'Private Halls',
                    '253' => 'Commercial Property',
                )
            ),
            'contact_information_fields' => array(
                'BRANCH_ID',
            ),
            'help_url' => 'https://houzezpropertyfeed.com/documentation/managing-imports/formats/blm/',
        ),
        'dezrez_rezi' => array(
            'name' => __( 'Dezrez Rezi', 'houzezpropertyfeed' ),
            'fields' => array(
                array(
                    'id' => 'api_key',
                    'label' => __( 'API Key', 'houzezpropertyfeed' ),
                    'type' => 'text',
                ),
                array(
                    'id' => 'branch_ids',
                    'label' => __( 'Branch ID(s)', 'houzezpropertyfeed' ),
                    'type' => 'text',
                    'tooltip' => __( 'A comma-delimited list of Dezrez branch IDs. Leave blank to import properties for all branches', 'houzezpropertyfeed' ),
                ),
                array(
                    'id' => 'tags',
                    'label' => __( 'Tag(s)', 'houzezpropertyfeed' ),
                    'type' => 'text',
                    'tooltip' => __( 'A comma-delimited list of agent defined tags within Dezrez. Leave blank if not wanting to filter properties by tag', 'houzezpropertyfeed' ),
                )
            ),
            'address_fields' => array( 'Locality', 'Town', 'County' ),
            'taxonomy_values' => array(
                'sales_status' => array(
                    'Reduced' => 'Reduced',
                    'OnMarket' => 'OnMarket',
                    'UnderOffer' => 'UnderOffer',
                    'OfferAccepted' => 'OfferAccepted',
                ),
                'lettings_status' => array(
                    'Reduced' => 'Reduced',
                    'OnMarket' => 'OnMarket',
                    'UnderOffer' => 'UnderOffer',
                    'OfferAccepted' => 'OfferAccepted',
                ),
                'property_type' => array(
                    'TerracedHouse' => 'TerracedHouse',
                    'EndTerraceHouse' => 'EndTerraceHouse',
                    'MidTerraceHouse' => 'MidTerraceHouse',
                    'SemiDetachedHouse' => 'SemiDetachedHouse',
                    'DetachedHouse' => 'DetachedHouse',
                    'RemoteDetachedHouse' => 'RemoteDetachedHouse',
                    'EndLinkHouse' => 'EndLinkHouse',
                    'MidLinkHouse' => 'MidLinkHouse',
                    'Flat' => 'Flat',
                    'Apartment' => 'Apartment',
                    'TerracedBungalow' => 'TerracedBungalow',
                    'EndTerraceBungalow' => 'EndTerraceBungalow',
                    'MidTerraceBungalow' => 'MidTerraceBungalow',
                    'SemiDetachedBungalow' => 'SemiDetachedBungalow',
                    'DetachedBungalow' => 'DetachedBungalow',
                    'RemoteDetachedBungalow' => 'RemoteDetachedBungalow',
                    'EndLinkBungalow' => 'EndLinkBungalow',
                    'MidLinkBungalow' => 'MidLinkBungalow',
                    'Cottage' => 'Cottage',
                    'TerracedCottage' => 'TerracedCottage',
                    'EndTerraceCottage' => 'EndTerraceCottage',
                    'MidTerraceCottage' => 'MidTerraceCottage',
                    'SemiDetachedCottage' => 'SemiDetachedCottage',
                    'DetachedCottage' => 'DetachedCottage',
                    'RemoteDetachedCottage' => 'RemoteDetachedCottage',
                    'TerracedTownHouse' => 'TerracedTownHouse',
                    'EndTerraceTownHouse' => 'EndTerraceTownHouse',
                    'MidTerraceTownHouse' => 'MidTerraceTownHouse',
                    'SemiDetachedTownHouse' => 'SemiDetachedTownHouse',
                    'DetachedTownHouse' => 'DetachedTownHouse',
                    'DetachedCountryHouse' => 'DetachedCountryHouse',
                    'NorthWingCountryHouse' => 'NorthWingCountryHouse',
                    'SouthWingCountryHouse' => 'SouthWingCountryHouse',
                    'EastWingCountryHouse' => 'EastWingCountryHouse',
                    'WestWingCountryHouse' => 'WestWingCountryHouse',
                    'TerracedChalet' => 'TerracedChalet',
                    'EndTerraceChalet' => 'EndTerraceChalet',
                    'MidTerraceChalet' => 'MidTerraceChalet',
                    'SemiDetachedChalet' => 'SemiDetachedChalet',
                    'DetachedChalet' => 'DetachedChalet',
                    'DetachedBarnConversion' => 'DetachedBarnConversion',
                    'RemoteDetachedBarnConversion' => 'RemoteDetachedBarnConversion',
                    'MewsStyleBarnConversion' => 'MewsStyleBarnConversion',
                    'GroundFloorPurposeBuiltFlat' => 'GroundFloorPurposeBuiltFlat',
                    'FirstFloorPurposeBuiltFlat' => 'FirstFloorPurposeBuiltFlat',
                    'GroundFloorConvertedFlat' => 'GroundFloorConvertedFlat',
                    'FirstFloorConvertedFlat' => 'FirstFloorConvertedFlat',
                    'SecondAndFloorConvertedFlat' => 'SecondAndFloorConvertedFlat',
                    'GroundAndFirstFloorMaisonette' => 'GroundAndFirstFloorMaisonette',
                    'FirstandSecondFloorMaisonette' => 'FirstandSecondFloorMaisonette',
                    'PenthouseApartment' => 'PenthouseApartment',
                    'DuplexApartment' => 'DuplexApartment',
                    'Mansion' => 'Mansion',
                    'QType' => 'QType',
                    'TType' => 'TType',
                    'Cluster' => 'Cluster',
                    'BuildingPlot' => 'BuildingPlot',
                    'ApartmentLowDensity' => 'ApartmentLowDensity',
                    'ApartmentStudio' => 'ApartmentStudio',
                    'Business' => 'Business',
                    'CornerTownhouse' => 'CornerTownhouse',
                    'VillaDetached' => 'VillaDetached',
                    'VillaLinkdetached' => 'VillaLinkdetached',
                    'VillaSemidetached' => 'VillaSemidetached',
                    'VillageHouse' => 'VillageHouse',
                    'LinkDetached' => 'LinkDetached',
                    'Studio' => 'Studio',
                    'Maisonette' => 'Maisonette',
                    'Shell' => 'Shell',
                    'Commercial' => 'Commercial',
                    'RetirementFlat' => 'RetirementFlat',
                    'Bedsit' => 'Bedsit',
                    'ParkHome' => 'ParkHome',
                    'ParkHomeMobileHome' => 'ParkHomeMobileHome',
                    'CommercialLand' => 'CommercialLand',
                    'Land' => 'Land',
                    'FarmLand' => 'FarmLand',
                )
            ),
            'contact_information_fields' => array(
                'Branch ID',
                'Branch Name',
            ),
            'help_url' => 'https://houzezpropertyfeed.com/documentation/managing-imports/formats/dezrez-rezi/'
        ),
        'domus' => array(
            'name' => __( 'Domus', 'houzezpropertyfeed' ),
            'fields' => array(
                array(
                    'id' => 'xml_url',
                    'label' => __( 'XML URL', 'houzezpropertyfeed' ),
                    'type' => 'text',
                    'placeholder' => 'https://{your-site}.domus.net/site/go/api/',
                )
            ),
            'address_fields' => array( 'locality', 'town', 'county' ),
            'taxonomy_values' => array(
                'sales_status' => array(
                    'Available' => 'Available',
                    'Under Offer' => 'Under Offer',
                    'Sold Subject to Contract' => 'Sold Subject to Contract',
                ),
                'lettings_status' => array(
                    'Available' => 'Available',
                    'Let Subject to Contract' => 'Let Subject to Contract',
                ),
                'property_type' => array(
                    'Detached' => 'Detached',
                    'Semi-Detached' => 'Semi-Detached',
                    'End Terraced' => 'End Terraced',
                    'Flat' => 'Flat',
                    'Studio' => 'Studio',
                )
            ),
            'contact_information_fields' => array(
                'branchID',
            ),
            'help_url' => 'https://houzezpropertyfeed.com/documentation/managing-imports/formats/domus/',
            'warnings' => array_filter( array( $simplexml_warning ) ),
        ),
        'expertagent' => array(
            'name' => __( 'Expert Agent', 'houzezpropertyfeed' ),
            'fields' => array(
                array(
                    'id' => 'ftp_host',
                    'label' => __( 'FTP Host', 'houzezpropertyfeed' ),
                    'type' => 'text',
                    'default' => 'ftp.expertagent.co.uk',
                ),
                array(
                    'id' => 'ftp_user',
                    'label' => __( 'FTP Username', 'houzezpropertyfeed' ),
                    'type' => 'text',
                    'placeholder' => '',
                ),
                array(
                    'id' => 'ftp_pass',
                    'label' => __( 'FTP Password', 'houzezpropertyfeed' ),
                    'type' => 'text',
                    'placeholder' => '',
                ),
                array(
                    'id' => 'ftp_passive',
                    'label' => __( 'Use FTP Passive Mode', 'houzezpropertyfeed' ),
                    'type' => 'checkbox',
                ),
                array(
                    'id' => 'xml_filename',
                    'label' => __( 'XML File Name', 'houzezpropertyfeed' ),
                    'type' => 'text',
                    'default' => 'properties.xml',
                ),
            ),
            'address_fields' => array( 'district', 'town', 'county' ),
            'taxonomy_values' => array(
                'sales_status' => array(
                    'On Market' => 'On Market',
                    'Sold STC' => 'Sold STC',
                    'Under Offer' => 'Under Offer',
                    'Exchanged' => 'Exchanged',
                ),
                'lettings_status' => array(
                    'On Market' => 'On Market',
                    'Available to Let' => 'Available to Let',
                    'Let' => 'Let',
                    'Let STC' => 'Let STC',
                ),
                'property_type' => array(
                    'House - Detached' => 'House - Detached',
                    'House - Semi Detached' => 'House - Semi Detached',
                    'House - Terraced' => 'House - Terraced',
                    'House - End of Terrace' => 'House - End of Terrace',
                    'Flat - Lower Ground Floor Flat' => 'Flat - Lower Ground Floor Flat',
                    'Flat - Ground Floor Flat' => 'Flat - Ground Floor Flat',
                    'Flat - Upper Floor Flat' => 'Flat - Upper Floor Flat',
                    'Bungalow - Detached' => 'Bungalow - Detached',
                    'Bungalow - Semi Detached' => 'Bungalow - Semi Detached',
                    'Bungalow - Terraced' => 'Bungalow - Terraced',
                    'Bungalow - End of Terrace' => 'Bungalow - End of Terrace',
                )
            ),
            'contact_information_fields' => array(
                'branch',
            ),
            'help_url' => 'https://houzezpropertyfeed.com/documentation/managing-imports/formats/expert-agent/',
            'warnings' => array_filter( array( $simplexml_warning ) ),
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
            'help_url' => 'https://houzezpropertyfeed.com/documentation/managing-imports/formats/loop/'
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
            'help_url' => 'https://houzezpropertyfeed.com/documentation/managing-imports/formats/street/'
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