<?php

class Frm_optimizer_settings
{

    private $settings;
    private $prefix;

    public function __construct()
    {

        global $wpdb;

        $this->prefix = $wpdb->prefix;

    }

    public function getSettings()
    {

        return [
            'tables' => $this->getTables(),
            'fields' => $this->getFields(),
            'fieldsMapped' => $this->getFieldsMapped(),
            'enabledForms' => $this->getEnabledForms(),
            'entryStatuses' => $this->getEntryStatuses(),
        ];

    }

    public function getTables()
    {

        return [
            'frm_items_default' => "{$this->prefix}frm_items",
            'frm_item_metas_default' => "{$this->prefix}frm_item_metas",
            'frm_items_archive' => "{$this->prefix}frm_items_archive",
            'frm_item_metas_archive' => "{$this->prefix}frm_item_metas_archive",
            'frm_forms' => "{$this->prefix}frm_forms",
        ];

    }

    public function getFields() {

        $forms = $this->getFrmForms();
        $settings = get_option('frm_optimizer_form_fields', []);
        
        // Match form and field IDs
        $fields = [];
        foreach ($forms as $form) {
            $form_id = $form['id'];
            $fields[ $form_id ] = $settings[ $form_id ]['field_ids'] ?? [];
        }

        return $fields;

    }

    public function getFieldsMapped()
    {

        $settings = get_option('frm_optimizer_form_fields', []);

        $fields = [];
        foreach ($settings as $form_id => $form_settings) {
            $fields[ $form_id ] = [
                'status' => $form_settings['status'] ?? null,
                'usdot' => $form_settings['dot'] ?? null,
                'email' => $form_settings['email'] ?? null,
            ];
        }

        return $fields;

    }

    public function getEnabledForms()
    {
        $enabled_forms = get_option('frm_optimizer_enabled_forms', []);
        return $enabled_forms;
    }

    public function getFrmForms()
    {
        global $wpdb;
        $table = $this->getTables()['frm_forms'];
        return $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
    }

    public function getEntryStatuses() {

        return get_option('frm_optimizer_statuses', []);

    }

}