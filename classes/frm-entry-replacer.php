<?php

class Frm_entry_replacer {

    private $settings;

    public function __construct() {

        // Prepare settings
        $settings = ( new Frm_optimizer_settings() )->getSettings();

        // Include filters/hooks
        $this->addHooks();

    }

    public function addHooks() {

        // Replace entry data from archive if the original on is empty
        add_filter('frm_get_entry', array($this, 'getEntryArchive'), 10, 2);

    }

    public function getEntryArchive($entry=null, $entry_id=null) {

        if( $entry ) { return $entry; }
    
        global $wpdb;
    
        // Get entry data
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}frm_items_archive WHERE id = %d",
            $entry_id
        ));
    
        // Get meta
        if( $entry ) {
    
            $metas = FrmDb::get_results(
                $wpdb->prefix . 'frm_item_metas_archive m LEFT JOIN ' . $wpdb->prefix . 'frm_fields f ON m.field_id=f.id',
                array(
                    'item_id'    => $entry->id,
                    'field_id !' => 0,
                ),
                'field_id, meta_value, field_key, item_id, f.type'
            );
    
            // Process meta
            foreach ( $metas as $meta_val ) {
                FrmFieldsHelper::prepare_field_value( $meta_val->meta_value, $meta_val->type );
    
                if ( $meta_val->item_id == $entry->id ) {
                    $entry->metas[ $meta_val->field_id ] = $meta_val->meta_value;
    
                        $entry->metas[ $meta_val->field_key ] = $entry->metas[ $meta_val->field_id ];
    
                    continue;
                }
    
                // include sub entries in an array
                if ( ! isset( $entry->metas[ $meta_val->field_id ] ) ) {
                    $entry->metas[ $meta_val->field_id ] = array();
                }
    
                $entry->metas[ $meta_val->field_id ][] = $meta_val->meta_value;
    
                unset( $meta_val );
            }
            unset( $metas );
    
        }
    
        //FrmEntry::prepare_entry( $entry );
    
        /*
        echo "<pre>";
        print_r($entry);
        echo "</pre>";
        die();
        */
    
        return $entry;
    }    

}

new Frm_entry_replacer();