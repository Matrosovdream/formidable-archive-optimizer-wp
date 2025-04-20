<?php
/**
 * Plugin Name: Formidable Archive Optimizer
 * Description: Moves old Formidable Forms entries to archive tables and still allows access by entry ID.
 * Version: 1.0
 * Author: Your Name
 */

// Variables
define('FRM_OPT_URL', __DIR__);

// Include classes
require_once FRM_OPT_URL.'/classes/frm-optimizer-settings.php';
require_once FRM_OPT_URL.'/classes/frm-entry-replacer.php';


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

    if( isset( $_GET['restore'] ) ) {
        ffao_restore_all_archived_entries();
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

function ffao_restore_all_archived_entries() {
    global $wpdb;

    $items_table       = "{$wpdb->prefix}frm_items";
    $metas_table       = "{$wpdb->prefix}frm_item_metas";
    $items_archive     = "{$wpdb->prefix}frm_items_archive";
    $metas_archive     = "{$wpdb->prefix}frm_item_metas_archive";

    // Insert back to original tables
    $wpdb->query("INSERT IGNORE INTO $items_table SELECT * FROM $items_archive");
    $wpdb->query("INSERT IGNORE INTO $metas_table SELECT * FROM $metas_archive");

    // Now delete from archive
    $wpdb->query("DELETE FROM $metas_archive");
    $wpdb->query("DELETE FROM $items_archive");

    return true;
}



// Hook into Formidable to retrieve archived entries

/*
DROP TABLE wp_frm_items_archive;
DROP TABLE wp_frm_item_metas_archive;
DROP TABLE wp_frm_payments_archive;
*/
