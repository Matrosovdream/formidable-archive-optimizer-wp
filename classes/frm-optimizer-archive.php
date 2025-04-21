<?php

class Frm_optimizer_archive {

    public function createArchiveTables() {

        global $wpdb;
    
        // Archive tables creation
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}frm_items_archive LIKE {$wpdb->prefix}frm_items;");
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}frm_item_metas_archive LIKE {$wpdb->prefix}frm_item_metas;");

    }
    
    
    public function archiveEntries() {

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
    
    public function restoreEntries() {

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

}