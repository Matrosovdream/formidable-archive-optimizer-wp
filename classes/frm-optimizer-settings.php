<?php

class Frm_optimizer_settings {

    private $settings;
    private $prefix;

    public function __construct() {

        global $wpdb;

        $this->prefix = $wpdb->prefix;

    }

    public function getSettings() {

        return [
            'tables' => $this->getTables()
        ];

    }

    private function getTables() {

        return [
            'frm_items_default' => "{$this->prefix}frm_items",
            'frm_item_metas_default' => "{$this->prefix}frm_item_metas",
            'frm_items_archive' => "{$this->prefix}frm_items_archive",
            'frm_item_metas_archive' => "{$this->prefix}frm_item_metas_archive",
        ];

    }

}