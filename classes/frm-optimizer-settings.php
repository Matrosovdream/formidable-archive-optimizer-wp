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

        return [
            2 => [886, 964, 1013, 214, 215, 216, 168, 169],
            7 => [720, 86]
        ];

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

}