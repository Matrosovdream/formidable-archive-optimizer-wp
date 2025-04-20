<?php
/**
 * Plugin Name: Formidable Archive Optimizer
 * Description: Moves old Formidable Forms entries to archive tables and still allows access by entry ID.
 * Version: 1.0
 * Author: Your Name
 */


add_action('init', 'frm_work');
function frm_work() {

    if( isset( $_GET['create_tables'] ) ) {

        ffao_create_archive_tables();
        die();

    }

    if( isset( $_GET['migrate'] ) ) {
        ffao_archive_old_entries();
        die();
    }

}

register_activation_hook(__FILE__, 'ffao_create_archive_tables');

function ffao_create_archive_tables() {
    global $wpdb;

    // Archive tables creation
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}frm_items_archive LIKE {$wpdb->prefix}frm_items;");
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}frm_item_metas_archive LIKE {$wpdb->prefix}frm_item_metas;");
}

add_action('admin_menu', function () {
    add_submenu_page(
        'tools.php',
        'Formidable Optimizer',
        'Formidable Optimizer',
        'manage_options',
        'ffao-optimize',
        'ffao_render_page'
    );
});

function ffao_render_page() {
    if (isset($_POST['ffao_run']) && check_admin_referer('ffao_optimize')) {
        $count = ffao_archive_old_entries();
        echo "<div class='notice notice-success'><p>Moved $count entries to archive.</p></div>";
    }

    echo '<div class="wrap"><h1>Formidable Archive Optimizer</h1>';
    echo '<form method="POST">';
    wp_nonce_field('ffao_optimize');
    echo '<p>This will move entries older than 6 months to archive.</p>';
    echo '<button class="button button-primary" type="submit" name="ffao_run">Optimize Now</button>';
    echo '</form></div>';
}

function ffao_archive_old_entries() {
    global $wpdb;
    $items_table = "{$wpdb->prefix}frm_items";
    $metas_table = "{$wpdb->prefix}frm_item_metas";
    $items_archive = "{$wpdb->prefix}frm_items_archive";
    $metas_archive = "{$wpdb->prefix}frm_item_metas_archive";

    // Get old item IDs
    $old_ids = $wpdb->get_col("
        SELECT id FROM $items_table
        WHERE created_at < NOW() - INTERVAL 6 MONTH
    ");

    if (empty($old_ids)) return 0;

    $ids_in = implode(',', array_map('intval', $old_ids));

    // Insert into archive
    $wpdb->query("INSERT IGNORE INTO $items_archive SELECT * FROM $items_table WHERE id IN ($ids_in)");
    $wpdb->query("INSERT IGNORE INTO $metas_archive SELECT * FROM $metas_table WHERE item_id IN ($ids_in)");

    // Delete from original
    $wpdb->query("DELETE FROM $metas_table WHERE item_id IN ($ids_in)");
    $wpdb->query("DELETE FROM $items_table WHERE id IN ($ids_in)");

    return count($old_ids);
}


add_filter('frm_get_entry', 'ffao_get_entry_with_archive', 10, 2);
function ffao_get_entry_with_archive($entry=null, $entry_id=null) {

    if( $entry ) { return $entry; }

    global $wpdb;

    // Get entry data
    $entry = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}frm_items_archive WHERE id = %d",
        $entry_id
    ));

    // Get meta
    if( $entry ) {

        $metas = FrmDb::get_results(
            $wpdb->prefix . 'frm_item_metas_archive m LEFT JOIN ' . $wpdb->prefix . 'frm_fields f ON m.field_id=f.id',
            array(
                'item_id'    => $entry->id,
                'field_id !' => 0,
            ),
            'field_id, meta_value, field_key, item_id, f.type'
        );

        // Process meta
        foreach ( $metas as $meta_val ) {
			FrmFieldsHelper::prepare_field_value( $meta_val->meta_value, $meta_val->type );

			if ( $meta_val->item_id == $entry->id ) {
				$entry->metas[ $meta_val->field_id ] = $meta_val->meta_value;

					$entry->metas[ $meta_val->field_key ] = $entry->metas[ $meta_val->field_id ];

				continue;
			}

			// include sub entries in an array
			if ( ! isset( $entry->metas[ $meta_val->field_id ] ) ) {
				$entry->metas[ $meta_val->field_id ] = array();
			}

			$entry->metas[ $meta_val->field_id ][] = $meta_val->meta_value;

			unset( $meta_val );
		}
		unset( $metas );

    }

    

    

    //print_r($entry);
    //die();

    return $entry;
}



// Hook into Formidable to retrieve archived entries

/*
DROP TABLE wp_frm_items_archive;
DROP TABLE wp_frm_item_metas_archive;
DROP TABLE wp_frm_payments_archive;
*/
