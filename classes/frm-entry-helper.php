<?php

class Frm_optimize_helper
{

    private $tables;

    public function __construct()
    {

        // Prepare settings
        $settings = (new Frm_optimizer_settings())->getSettings();
        $this->tables = $settings['tables'];

    }


    public function getEntryCount()
    {

        return [
            'default' => $this->getDefaultEntryCount(),
            'archive' => $this->getArchiveEntryCount()
        ];

    }

    public function getDefaultEntryCount()
    {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}frm_items");
    }

    public function getArchiveEntryCount()
    {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}frm_items_archive");
    }

    public function getEntriesForArchive($filter = [])
    {

        global $wpdb;
        $items_table = $this->tables['frm_items_default'];

        $settings = (new Frm_optimizer_settings())->getSettings();
        $fieldsMapped = $settings['fieldsMapped'];

        /* Filters start */
        $where = [];

        if (!empty($filter['status'])) {

            // Extract all status values from $fieldsMapped
            $status_fields = [];
            foreach ($fieldsMapped as $field) {
                $status_fields[] = $field['status'] ?? [];
            }

            // Flatten the array and remove duplicates, statuses
            $statuses = implode("','", array_map('esc_sql', $filter['status']));
            $statuses = "'" . $statuses . "'";

            // Prepare the status values for SQL
            $status_sql = [];
            foreach ($status_fields as $field_id) {
                $status_sql[] = "(m.field_id = $field_id AND m.meta_value IN ($statuses))";
            }


            if (!empty($status_sql)) {

                $where[] = "
                EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}frm_item_metas m
                    WHERE m.item_id = i.id
                    AND (
                        ".implode(' OR ', $status_sql)."
                    )
                )";

            }
        }

        if (!empty($filter['period'])) {
            $where[] = "i.created_at < NOW() - INTERVAL " . $filter['period'] . " MONTH";
        }
        /* Filters end */

        // Prepare and send request
        $old_ids = $wpdb->get_col("
            SELECT i.id 
            FROM $items_table i
            WHERE 1=1 AND
            " . implode(' AND ', $where) . "
        ");

        return $old_ids;

    }

}