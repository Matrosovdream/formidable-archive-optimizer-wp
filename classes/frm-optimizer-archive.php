<?php

class Frm_optimizer_archive {

    private $settings;
    private $tables;
    public $archive_period; // In months

    public function __construct()
    {

        // Prepare settings
        $settings = (new Frm_optimizer_settings())->getSettings();
        $this->tables = $settings['tables'];

        // Archive period
        $this->archive_period = 6; // 6 months

    }

    public function createArchiveTables() {

        global $wpdb;
    
        // Archive tables creation
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$this->tables['frm_items_archive']} LIKE {$this->tables['frm_items_default']};");
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$this->tables['frm_item_metas_archive']} LIKE {$this->tables['frm_item_metas_default']};");

    }
    
    
    public function archiveEntries() {

        global $wpdb;
        $items_table = $this->tables['frm_items_default'];
        $metas_table = $this->tables['frm_item_metas_default'];
        $items_archive = $this->tables['frm_items_archive'];
        $metas_archive = $this->tables['frm_item_metas_archive'];
        $period = $this->archive_period;
    
        // Get old item IDs
        $old_ids = $wpdb->get_col("
            SELECT id FROM $items_table
            WHERE created_at < NOW() - INTERVAL $period MONTH
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

    public function restoreEntries( array $entry_ids ) {

        global $wpdb;
        $items_table = $this->tables['frm_items_default'];
        $metas_table = $this->tables['frm_item_metas_default'];
        $items_archive = $this->tables['frm_items_archive'];
        $metas_archive = $this->tables['frm_item_metas_archive'];

        // Prepare entry IDs for SQL
        $entry_ids = implode(',', array_map('intval', $entry_ids));

        $wpdb->query("INSERT IGNORE INTO $items_table SELECT * FROM $items_archive WHERE id IN ($entry_ids)");
        $wpdb->query("INSERT IGNORE INTO $metas_table SELECT * FROM $metas_archive WHERE item_id IN ($entry_ids)");
        $wpdb->query("DELETE FROM $metas_archive WHERE item_id IN ($entry_ids)");
        $wpdb->query("DELETE FROM $items_archive WHERE id IN ($entry_ids)");

        return true;

    }
    
    public function restoreAllEntries() {

        global $wpdb;
        $items_table = $this->tables['frm_items_default'];
        $metas_table = $this->tables['frm_item_metas_default'];
        $items_archive = $this->tables['frm_items_archive'];
        $metas_archive = $this->tables['frm_item_metas_archive'];
    
        // Insert back to original tables
        $wpdb->query("INSERT IGNORE INTO $items_table SELECT * FROM $items_archive");
        $wpdb->query("INSERT IGNORE INTO $metas_table SELECT * FROM $metas_archive");
    
        // Now delete from archive
        $wpdb->query("DELETE FROM $metas_archive");
        $wpdb->query("DELETE FROM $items_archive");
    
        return true;

    }

}