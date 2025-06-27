<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists('WP_List_Table') )
{
   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Houzez Property Feed Admin Queued Media Table Functions
 */
class Houzez_Property_Feed_Admin_Queued_Media_Table extends WP_List_Table {

	public function __construct( $args = array() ) 
    {
        parent::__construct( array(
            'singular'=> 'Queued Media',
            'plural' => 'Queued Media',
            'ajax'   => false // We won't support Ajax for this table, ye
        ) );
	}

    public function extra_tablenav( $which ) 
    {
        /*if ( $which == "top" )
        {
            //The code that goes before the table is here
            echo"Hello, I'm before the table";
        }
        if ( $which == "bottom" )
        {
            //The code that goes after the table is there
            echo"Hi, I'm after the table";
        }*/
    }

    public function get_columns() 
    {
        return array(
            'col_media_url' =>__('URL', 'houzezpropertyfeed' ),
            'col_media_property' =>__( 'Property', 'houzezpropertyfeed' ),
            'col_media_type' =>__( 'Type', 'houzezpropertyfeed' ),
        );
    }

    public function column_default( $item, $column_name )
    {
        switch( $column_name ) 
        {
            case 'col_media_url':
            {
                $return = '<a href="' . esc_url($item->media_compare_url) . '" target="_blank">' . esc_url($item->media_compare_url) . '</a>';

                return $return;
            }
            case 'col_media_property':
            {
                if ( empty($item->post_id) )
                {
                    return '-';
                }

                $title = get_the_title($item->post_id);
                if ( empty($title) )
                {
                    $title = '(no title)';
                }

                return '<a href="' . esc_url(get_edit_post_link($item->post_id)) . '" target="_blank">' . esc_html($title) . '</a>';
            }
            case 'col_media_type':
            {
                return $item->media_type;
            }
            default:
                return print_r( $item, true ) ;
        }
    }

    public function prepare_items() 
    {
        global $wpdb;

        $columns = $this->get_columns(); 
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $per_page = 100000;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        $this->_column_headers = array($columns, $hidden, $sortable);

        $query = $wpdb->prepare("SELECT
            GROUP_CONCAT(`id`) as `ids`,
            `import_id`,
            `post_id`,
            `crm_id`,
            `media_type`,
            `media_order`,
            SUBSTRING_INDEX(GROUP_CONCAT(`media_location` ORDER BY `media_modified` DESC SEPARATOR '~|~'), '~|~', 1 ) as `media_location`,
            SUBSTRING_INDEX(GROUP_CONCAT(`media_description` ORDER BY `media_modified` DESC SEPARATOR '~|~'), '~|~', 1 ) as `media_description`,
            SUBSTRING_INDEX(GROUP_CONCAT(`media_compare_url` ORDER BY `media_modified` DESC SEPARATOR '~|~'), '~|~', 1 ) as `media_compare_url`,
            MAX(`media_modified`) as `media_modified`,
            MAX(`attachment_id`) as `attachment_id`
        FROM
            " . $wpdb->prefix . "houzez_property_feed_media_queue
        WHERE
            `import_id` = %d
        GROUP BY
            post_id,
            media_type,
            media_order
        ORDER BY
            post_id,
            media_type,
            media_order", (int)$_GET['import_id']);

        $this->items = $wpdb->get_results($query);
        $totalitems = count($this->items);

        $this->set_pagination_args(
            array(
                'total_items' => $totalitems,
                'per_page'    => $per_page,
            )
        );
        
    }

    public function display() {
        $singular = $this->_args['singular'];

        $this->screen->render_screen_reader_content( 'heading_list' );
        ?>
<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
    <thead>
    <tr>
        <?php $this->print_column_headers(); ?>
    </tr>
    </thead>

    <tbody id="the-list"
        <?php
        if ( $singular ) {
            echo ' data-wp-lists="list:' . esc_attr($singular) . '"';
        }
        ?>
        >
        <?php $this->display_rows_or_placeholder(); ?>
    </tbody>

</table>
        <?php
    }

    protected function get_table_classes() {
        $mode = get_user_setting( 'posts_list_mode', 'list' );

        $mode_class = esc_attr( 'table-view-' . $mode );

        return array( 'widefat', 'striped', $mode_class, esc_attr($this->_args['plural']) );
    }

}