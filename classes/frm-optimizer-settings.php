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
            $fields[ $form_id ] = $settings[ $form_id ] ?? [];
        }

        return $fields;

    }

    public function getFieldsMapped()
    {

        /*
        key => form_id,
        value => [
            'short_field_name' => field_id,
        ]
        */

        return [
            2 => [
                "status" => 886,
                "usdot" => 964
            ],
            7 => [
                "status" => 720,
                "usdot" => 86
            ],
            9 => [
                "status" => 862,
                "usdot" => 121
            ],
            11 => [
                "status" => 868,
                "usdot" => 697
            ],

        ];

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

}