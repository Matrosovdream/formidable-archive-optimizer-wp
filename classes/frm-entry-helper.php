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
        $table = $this->tables['frm_items_default'];
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }

    public function getDefaultEntries($filters = [], array $paginate)
    {

        $per_page = $paginate['per_page'] ?? 10;
        $offset = $paginate['offset'] ?? 0;

        global $wpdb;

        $items_default = $this->tables['frm_items_default'];
        $metas_default = $this->tables['frm_item_metas_default'];

        // Build WHERE
        $where = "WHERE 1=1";
        $params = [];

        // Filter by enabled forms
        if (!empty($filters['form_id']) && is_array($filters['form_id'])) {
            $form_ids = implode(',', array_map('intval', $filters['form_id']));
            $where .= " AND i.form_id IN ($form_ids)";
        } elseif (!empty($filters['form_id'])) {
            $where .= " AND i.form_id = %d";
            $params[] = (int) $filters['form_id'];
        }

         // Filter by order number
        if (!empty($filters['order_num'])) {
            $where .= " AND i.id LIKE %s";
            $params[] = $wpdb->esc_like($filters['order_num']);
        }

        // Search in all metas
        if (!empty($filters['common_search'])) {
            $common = '%' . $wpdb->esc_like($filters['common_search']) . '%';
            $where .= " AND EXISTS (
                SELECT 1 FROM {$metas_default} m_common
                WHERE m_common.item_id = i.id
                AND m_common.meta_value LIKE %s
            )";
            $params[] = $common;
        }

        // Count total
        $count_sql = "
            SELECT COUNT(DISTINCT i.id)
            FROM $items_default i
            $where
        ";

        $total = $wpdb->get_var($wpdb->prepare($count_sql, ...$params));

        // Fetch entries
        $sql = "
            SELECT 
                i.id,
                i.form_id,
                i.created_at
            FROM $items_default i
            $where
            GROUP BY i.id
            ORDER BY i.id DESC
            LIMIT %d OFFSET %d
        ";

        $query_params = array_merge($params, [$per_page, $offset]);
        $entries = $wpdb->get_results($wpdb->prepare($sql, ...$query_params), ARRAY_A);

        // Get details for entries
        foreach ($entries as $key => $entry) {
            $entries[$key] = FrmEntry::getOne($entry['id'], true);

            // Add extra params
            $entries[$key]->url = $this->getEntryAdminUrl($entry['id']);
        }

        return [
            'entries' => $entries,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
            'current_page' => $offset / $per_page + 1
        ];

    }

    public function getEntryAdminUrl($entry_id)
    {
        return add_query_arg([
            'page' => 'formidable-entries',
            'frm_action' => 'show',
            'id' => $entry_id
        ], admin_url('admin.php'));

    }


    public function getArchiveEntryCount()
    {
        global $wpdb;
        $table = $this->tables['frm_items_archive'];
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }

    public function getEntriesForArchive($filter = [])
    {

        global $wpdb;
        $items_table = $this->tables['frm_items_default'];
        $items_metas_table = $this->tables['frm_item_metas_default'];

        $settings = (new Frm_optimizer_settings())->getSettings();
        $fieldsMapped = $settings['fieldsMapped'];
        $enabledForms = $settings['enabledForms'];

        /* Filters start */
        $where = [];

        if (!empty($filter['status'])) {

            // Filter by enabled forms
            if (!empty($enabledForms)) {
                $where[] = "i.form_id IN (" . implode(',', array_map('intval', $enabledForms)) . ")";
            } else {
                $where[] = "i.form_id IN (0)"; // No forms enabled, so no entries to archive
            }

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
                if( $field_id == 0 || $field_id == null ) {
                    continue; // Skip if field_id is 0 or null
                }

                $status_sql[] = "(m.field_id = $field_id AND m.meta_value IN ($statuses))";
            }


            if (!empty($status_sql)) {

                $where[] = "
                EXISTS (
                    SELECT 1 FROM $items_metas_table m
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

        $query = "
            SELECT i.id 
            FROM $items_table i
            WHERE 1=1 AND
            " . implode(' AND ', $where) . "
        ";

        // Prepare and send request
        $old_ids = $wpdb->get_col($query);

        return $old_ids;

    }

    public function getDefaultForms()
    {
        $forms = FrmForm::getAll();

        // To array map
        $forms = array_map(function ($form) {
            return [
                'id' => $form->id,
                'name' => $form->name
            ];
        }, $forms);

        // Remove empty forms
        $forms = array_filter($forms, function ($form) {
            return !empty($form['name']);
        });

        // Sort by name
        usort($forms, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $forms;
    }

}