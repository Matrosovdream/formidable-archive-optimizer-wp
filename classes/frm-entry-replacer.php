<?php

class Frm_entry_replacer
{

    private $settings;
    private $table_items_archive;
    private $table_item_metas_archive;

    public function __construct()
    {

        // Prepare settings
        $settings = (new Frm_optimizer_settings())->getSettings();
        $this->settings = $settings;

        $tables = $settings['tables'];
        $this->table_items_archive = $tables['frm_items_archive'];
        $this->table_item_metas_archive = $tables['frm_item_metas_archive'];

        // Include filters/hooks
        $this->addHooks();

    }

    public function addHooks()
    {

        // Replace entry data from archive if the original on is empty
        add_filter('frm_get_entry', array($this, 'getEntryArchive'), 10, 2);

    }

    public function getEntryArchive($entry = null, $entry_id = null)
    {

        // If it's not empty then don't go to archive 
        if ($entry) { return $entry; }

        // Get entry data
        $entry = $this->getEntry($entry_id);

        return $entry;
    }

    public function getEntry($entry_id)
    {

        global $wpdb;

        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_items_archive} WHERE id = %d",
            $entry_id
        ));

        // Set entry meta
        if ($entry) {
            $entry = $this->setEntryMeta($entry);
        }

        return $entry;

    }

    private function setEntryMeta( $entry )
    {

        global $wpdb;

        $metas = FrmDb::get_results(
            $this->table_item_metas_archive . ' m LEFT JOIN ' . $wpdb->prefix . 'frm_fields f ON m.field_id=f.id',
            array(
                'item_id' => $entry->id,
                'field_id !' => 0,
            ),
            'field_id, meta_value, field_key, item_id, f.type'
        );

        return $this->processEntryMeta( $metas, $entry );

    }

    private function processEntryMeta( $metas, $entry ) {

        $field_ids = [];
        
        foreach ($metas as $meta_val) {
            FrmFieldsHelper::prepare_field_value($meta_val->meta_value, $meta_val->type);

            $field_ids[] = $meta_val->field_id;

            if ($meta_val->item_id == $entry->id) {
                $entry->metas[$meta_val->field_id] = $meta_val->meta_value;
            }

            // include sub entries in an array
            if (!isset($entry->metas[$meta_val->field_id])) {
                $entry->metas[$meta_val->field_id] = array();
            }

            unset($meta_val);
        }

        // Extract fields info
        $fields = $this->getFieldsInfo($field_ids, $entry->form_id);

        // Go through each field and set the value
        foreach ($fields as $field_id=>$field) {

            // Set the field value
            if (isset($entry->metas[$field->id])) {
                $fields[$field->id]->value = $entry->metas[$field->id];
            }

        }
        $entry->fields = $fields;

        return $entry;

    }

    private function getFieldsInfo($field_ids, $form_id)
    {

        global $wpdb;

        $fieldsRaw = $wpdb->get_results(
            "SELECT id, name, type FROM {$wpdb->prefix}frm_fields WHERE id IN (".implode(',', $field_ids).")"  
        );

        $fields = [];
        foreach ($fieldsRaw as $field) {
            $fields[$field->id] = $field;
        }

        return $this->filterFields($fields, $form_id);

    }

    private function filterFields($fields, $form_id)
    {

        // Filter by $this->settings['fields']
        foreach( $this->settings['fields'][$form_id] as $field_id ) {
            if (isset($fields[$field_id])) {
                $filtered_fields[$field_id] = $fields[$field_id];
            }
        }

        return $filtered_fields;

    }

}

new Frm_entry_replacer();