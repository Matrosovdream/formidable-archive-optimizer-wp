<?php

class Frm_optimizer_archive
{

    private $settings;
    private $tables;
    public $archive_period; // In months
    private $entryReplacer;

    public function __construct()
    {

        // Prepare settings
        $settings = (new Frm_optimizer_settings())->getSettings();
        $this->tables = $settings['tables'];

        // Archive period
        $this->archive_period = FRM_ARCHIVE_PERIOD ?? 6; // 6 months

        // Include entry replacer
        $this->entryReplacer = new Frm_entry_replacer();

        // Include settings
        $this->settings = new Frm_optimizer_settings();

    }

    public function createArchiveTables()
    {

        global $wpdb;

        // Archive tables creation
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$this->tables['frm_items_archive']} LIKE {$this->tables['frm_items_default']};");
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$this->tables['frm_item_metas_archive']} LIKE {$this->tables['frm_item_metas_default']};");

    }


    public function archiveEntries( $period = null )
    {

        global $wpdb;
        $items_table = $this->tables['frm_items_default'];
        $metas_table = $this->tables['frm_item_metas_default'];
        $items_archive = $this->tables['frm_items_archive'];
        $metas_archive = $this->tables['frm_item_metas_archive'];
        $period = $period ?? $period :: $this->archive_period;
        $entryStatuses = $this->settings->getEntryStatuses();

        // If no statuses are set then return
        if (empty($entryStatuses)) {
            return 0;
        }

        // Get old item IDs
        $old_ids = (new Frm_optimize_helper())->getEntriesForArchive([
            'status' => $entryStatuses,
            'period' => $period
        ]);

        if (empty($old_ids))
            return 0;

        $ids_in = implode(',', array_map('intval', $old_ids));

        // Insert into archive
        $wpdb->query("INSERT IGNORE INTO $items_archive SELECT * FROM $items_table WHERE id IN ($ids_in)");
        $wpdb->query("INSERT IGNORE INTO $metas_archive SELECT * FROM $metas_table WHERE item_id IN ($ids_in)");

        // Delete from original
        $wpdb->query("DELETE FROM $metas_table WHERE item_id IN ($ids_in)");
        $wpdb->query("DELETE FROM $items_table WHERE id IN ($ids_in)");

        return count($old_ids);

    }

    public function restoreEntries(array $entry_ids)
    {

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

    public function restoreAllEntries()
    {

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

    public function getEntries($filters = [], array $paginate)
    {

        $per_page = $paginate['per_page'] ?? 10;
        $offset = $paginate['offset'] ?? 0;

        global $wpdb;

        $items_archive = $this->tables['frm_items_archive'];
        $metas_archive = $this->tables['frm_item_metas_archive'];

        // Build WHERE
        $where = "WHERE 1=1";
        $params = [];

        // Filter by enabled forms
        $enabled_forms = (new Frm_optimizer_settings())->getEnabledForms();
        $where .= " AND i.form_id IN (" . implode(',', array_map('intval', $enabled_forms)) . ")";

        //print_r($params); die();

         // Filter by order number
        if (!empty($filters['order_num'])) {
            $where .= " AND i.id LIKE %s";
            $params[] = $wpdb->esc_like($filters['order_num']);
        }

        // Search in all metas
        if (!empty($filters['common_search'])) {
            $common = '%' . $wpdb->esc_like($filters['common_search']) . '%';
            $where .= " AND EXISTS (
                SELECT 1 FROM {$metas_archive} m_common
                WHERE m_common.item_id = i.id
                AND m_common.meta_value LIKE %s
            )";
            $params[] = $common;
        }

        // Count total
        $count_sql = "
            SELECT COUNT(DISTINCT i.id)
            FROM $items_archive i
            $where
        ";

        $total = $wpdb->get_var($wpdb->prepare($count_sql, ...$params));

        // Fetch entries
        $sql = "
            SELECT 
                i.id,
                i.form_id,
                i.created_at
            FROM $items_archive i
            $where
            GROUP BY i.id
            ORDER BY i.id DESC
            LIMIT %d OFFSET %d
        ";

        $query_params = array_merge($params, [$per_page, $offset]);
        $entries = $wpdb->get_results($wpdb->prepare($sql, ...$query_params), ARRAY_A);

        // Get details for entries
        foreach ($entries as $key => $entry) {
            $entries[$key] = $this->entryReplacer->getEntry($entry['id']);
        }

        return [
            'entries' => $entries,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
            'current_page' => $offset / $per_page + 1
        ];

    }

    public function getFrmForms() {

        global $wpdb;

        $forms_table = $this->tables['frm_forms'];

        $formsRaw = $wpdb->get_results(
            "SELECT id, name FROM {$forms_table} WHERE status = 'published' ORDER BY name ASC",
            ARRAY_A
        );
        $forms = [];
        foreach ($formsRaw as $form) {
            if ($form['name'] == '') {
                continue;
            }

            $forms[$form['id']] = $form;
        }

        // Get enabled forms
        $enabled_forms = (new Frm_optimizer_settings())->getEnabledForms();

        // Filter forms
        foreach ($forms as $form_id => $form) {
            if (!in_array($form_id, $enabled_forms)) {
                unset($forms[$form_id]);
            }
        }

        return $forms;

    }

}