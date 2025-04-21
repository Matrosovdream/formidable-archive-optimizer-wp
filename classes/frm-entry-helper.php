<?php

class Frm_optimize_helper {

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

}   