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
        } else {
            // Get enabled forms
            $enabled_forms = $this->getDefaultForms( true );
            $enabled_forms_ids = array_map(function ($form) {
                return $form['id'];
            }, $enabled_forms);

            if (!empty($enabled_forms)) {
                $where .= " AND i.form_id IN (" . implode(',', array_map('intval', $enabled_forms_ids)) . ")";
            } else {
                $where .= " AND i.form_id IN (0)"; // No forms enabled, so no entries to show
            }
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

            $entry = FrmEntry::getOne($entry['id'], true);

            // Add extra params
            $entry->url = $this->prepareEntryUrl($entry);

            // List of default form fields
            $field_ids = $this->getDefaultFieldListForm( $entry->form_id );

            // Prepare entry meta
            if( !empty($field_ids) ) {
                $entry->fields = $this->prepareEntryFields(
                    $entry,
                    $field_ids,
                );
            } else {
                $entry->fields = [];
            }
            
            $entries[$key] = $entry;

        }

        return [
            'entries' => $entries,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
            'current_page' => $offset / $per_page + 1
        ];

    }

    public function getDefaultFieldListForm( $form_id ) {
        
        return get_option('frm_optimizer_form_fields_search')[ $form_id ]['field_ids'] ?? [];

    }

    public function getDefaultEntryMeta($entry_id)
    {
        global $wpdb;

        $metas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tables['frm_item_metas_default']} WHERE item_id = %d",
            $entry_id
        ));

        return $metas;

    }

    public function prepareEntryFields($entry, $field_ids=[]) {

        $metas = $entry->metas ?? [];
        $meta_ids = [];
        
        // Extract ids
        foreach ($metas as $id=>$value) {
            $meta_ids[] = $id;
        }

        // Extract fields info
        $fields = $this->getFrmFields( ['field_id' => ( !empty($field_ids)) ? $field_ids : $meta_ids ] );

        // Prepare fields
        foreach( $fields as $key=>$field ) {
            $fields[$key]['value'] = $metas[$key];
        }

        return $fields;

    }

    public function prepareEntryUrl(object $entry)
    {

        // Let's take from option
        $settings = get_option('frm_optimizer_form_fields_search');

        // Replace variables
        $variables = [
            'form_id' => $entry->id,
        ];

        // Retrieve Url
        $url = $settings[ $entry->form_id ]['url'] ?? '';

        // Make replacement
        foreach ($variables as $key => $value) {
            $url = str_replace('{'. $key .'}', $value, $url);
        }

        return $url;

        /*
        return add_query_arg([
            'page' => 'formidable-entries',
            'frm_action' => 'show',
            'id' => $entry_id
        ], admin_url('admin.php'));
        */

    }

    public function getFrmFields( array $filter = [] ) {

        global $wpdb;

        $sql = "SELECT id, name, type FROM {$wpdb->prefix}frm_fields";

        /* Filters start */
        $params = [];

        // By field_id
        if( !empty($filter['field_id']) ) {
            $params[] = "id IN (".implode(',', $filter['field_id']).")";
        } 

        /* Filters end */

        // Prepare WHERE tail
        if( !empty($params) ) {
            $sql .= " WHERE ".implode(" AND ", $params);
        }

        // Make query
        $fieldsRaw = $wpdb->get_results( $sql, ARRAY_A );

        // Process response
        $fields = [];
        foreach ($fieldsRaw as $field) {
            $fields[$field['id']] = $field;
        }

        return $fields;

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

    public function getDefaultForms( $filter_by_options = false )
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

        // Filter by get_option frm_optimizer_enabled_forms_search
        if ($filter_by_options) {
            $enabled_forms = get_option('frm_optimizer_enabled_forms_search', []);
            if (!empty($enabled_forms)) {
                $forms = array_filter($forms, function ($form) use ($enabled_forms) {
                    return in_array($form['id'], $enabled_forms);
                });
            }
        }

        // Sort by name
        usort($forms, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $forms;
    }

}