<?php

class Frm_optimize_helper {

    private $tables;

    public function __construct()
    {

        // Prepare settings
        $settings = (new Frm_optimizer_settings())->getSettings();
        $this->tables = $settings['tables'];

    }


    public function getEntryCount() {

        return [
            'default' => $this->getDefaultEntryCount(),
            'archive' => $this->getArchiveEntryCount()
        ];

    }

    public function getDefaultEntryCount() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}frm_items");
    }

    public function getArchiveEntryCount() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}frm_items_archive");
    }

    public function getEntriesForArchive( $filter = [] ) {

        global $wpdb;
        $items_table = $this->tables['frm_items_default'];
        $metas_table = $this->tables['frm_item_metas_default'];

        /* Filters start */
        $where = [];

        if( !empty( $filter['status'] ) ) {

            $statuses = implode( "','", array_map( 'esc_sql', $filter['status'] ) );
            $statuses = "'".$statuses."'";

            $where[] = "
            EXISTS (
                SELECT 1 FROM {$wpdb->prefix}frm_item_metas m
                WHERE m.item_id = i.id
                AND (
                    (m.field_id = 886 AND m.meta_value IN ($statuses)) OR
                    (m.field_id = 720 AND m.meta_value IN ($statuses)) OR
                    (m.field_id = 862 AND m.meta_value IN ($statuses)) OR
                    (m.field_id = 868 AND m.meta_value IN ($statuses))
                )
            )";
        }

        if( !empty( $filter['period'] ) ) {
            $where[] = "i.created_at < NOW() - INTERVAL ".$filter['period']." MONTH";
        }
        /* Filters end */

        // Prepare and send request
        $old_ids = $wpdb->get_col("
            SELECT i.id 
            FROM $items_table i
            WHERE 1=1 AND
            ".implode( ' AND ', $where )."
        ");

        return $old_ids;

    }

}   