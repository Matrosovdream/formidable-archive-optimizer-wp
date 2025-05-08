<?php

add_shortcode('frm_entry_archived_list', function () {
    global $wpdb;

    // Add TailwindCSS
    $output = '<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">';
    
    // Process Restore Action
    if (!empty($_POST['ffao_action']) && $_POST['ffao_action'] === 'restore' && !empty($_POST['selected_ids'])) {

        // Check nonce for security
        check_admin_referer('ffao_bulk_action');

        // Restore selected entries
        $archiver = new Frm_optimizer_archive();
        $archiver->restoreEntries( $ids = $_POST['selected_ids'] );
        
        echo '<div class="max-w-7xl mx-auto bg-green-200 text-green-800 p-4 rounded mb-4">✅ Restored ' . count($ids) . ' entries successfully.</div>';
    }

    // Filters
    $filters = [
        'form_id' => isset($_GET['form_id']) ? (int) $_GET['form_id'] : '',
        'order_num' => sanitize_text_field($_GET['order_num'] ?? ''),
        'common_search' => sanitize_text_field($_GET['common_search'] ?? ''),
    ];

    // Set page and offset
    $page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
    $per_page = 30;
    $offset = ($page - 1) * $per_page;

    // Get entries
    $archiver = new Frm_optimizer_archive();
    $result = $archiver->getEntries(
        $filters, 
        $paginate = [
            'per_page' => $per_page,
            'offset' => $offset,
        ]
    );
    // Convert into variables
    $entries = $result['entries'] ?? [];
    $total = $result['total'] ?? 0;
    $total_pages = $result['total_pages'] ?? 0;
    $current_page = $result['current_page'] ?? 1;
    
    // Get formidable forms
    $forms = $archiver->getFrmForms();


    if( $_GET['lgg'] ) {
        echo "<pre>";
        print_r($forms); 
        echo "</pre>";
        die();
    }
    
    ob_start();
    ?>

    <div class="max-w-7xl mx-auto p-4">

        <h2 class="text-3xl font-bold mb-6 text-gray-800">Archived Entries</h2>

        <!-- Filter Form -->
        <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <input type="hidden" name="paged" value="1" />

            <select name="form_id" class="border rounded p-2">
                <option value="">All Forms</option>
                <?php foreach ($forms as $form): ?>
                    <option value="<?php echo esc_attr($form['id']); ?>" <?php selected($filters['form_id'], $form['id']); ?>>
                        <?php echo esc_html($form['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="order_num" placeholder="Order #" value="<?php echo esc_attr($filters['order_num']); ?>"
                class="border rounded p-2" />

            <input type="text" name="common_search" placeholder="Common Search..."
                value="<?php echo esc_attr($filters['common_search']); ?>" class="border rounded p-2" />

            <div class="flex justify-end md:col-span-3">
                <button type="submit" class="w-1/3 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Filter
                </button>
            </div>
        </form>

        <!-- Bulk Actions Form -->
        <form method="post">
            <?php wp_nonce_field('ffao_bulk_action'); ?>

            <div class="flex items-center gap-4 mb-4">
                <select name="ffao_action" class="border p-2 rounded">
                    <option value="">Bulk Actions</option>
                    <option value="restore">Restore</option>
                </select>
                <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Apply</button>
            </div>

            <!-- Entries Table -->
            <div class="overflow-x-auto bg-white shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2"><input type="checkbox" id="select-all"></th>
                            <th class="px-4 py-2 text-left" style="width: 10%;">Order #</th>
                            <th class="px-4 py-2 text-left" style="width: 15%;">Form</th>
                            <th class="px-4 py-2 text-left">Fields</th>
                            <th class="px-4 py-2 text-left" style="width: 15%;">Created at</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($entries):
                            foreach ($entries as $entry): ?>
                                <tr>
                                    <td class="px-4 py-2 text-center">
                                        <input type="checkbox" name="selected_ids[]" value="<?php echo esc_attr($entry->id); ?>">
                                    </td>
                                    <td class="px-4 py-2"><?php echo esc_html($entry->id ?: '-'); ?></td>
                                    <td class="px-4 py-2">
                                        <?php echo $forms[ $entry->form_id ]['name'] ?? ''; ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <?php foreach( $entry->fields as $field ) { ?>
                                            <b><?php echo esc_html($field->name); ?>:</b> 
                                            <?php 
                                                if (is_array($field->value)) {
                                                    echo esc_html(implode(', ', $field->value));
                                                } else {
                                                    echo esc_html($field->value);
                                                }
                                            ?> 
                                            <br>
                                        <?php } ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <?php echo esc_html(date('Y-m-d', strtotime($entry->created_at))); ?>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="7" class="px-4 py-2 text-center text-gray-500">No archived entries found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <!-- Pagination -->
        <?php
        $total_pages = ceil($total / $per_page);

        if ($total_pages > 1): ?>
            <div class="flex justify-center mt-6 space-x-2">

                <?php
                $current_page = $page;
                $range = 2; // How many pages to show around the current page
        
                for ($i = 1; $i <= $total_pages; $i++) {
                    if (
                        $i <= 3 || // First 3 pages
                        ($i >= $current_page - $range && $i <= $current_page + $range) || // Around current page
                        $i > $total_pages - 3 // Last 3 pages
                    ) {
                        $active = ($i == $current_page) ? 'bg-blue-600 text-white' : 'bg-gray-200';
                        echo '<a href="' . esc_url(add_query_arg('paged', $i)) . '" class="px-4 py-2 ' . $active . ' rounded">' . $i . '</a>';
                    } elseif (
                        ($i == 4 && $current_page > 6) || // Dot after page 3
                        ($i == $total_pages - 3 && $current_page < $total_pages - 5) // Dot before last 3
                    ) {
                        echo '<span class="px-4 py-2">...</span>';
                    }
                }
                ?>

            </div>
        <?php endif; ?>


    </div>

    <script>
        document.getElementById('select-all')?.addEventListener('click', function (e) {
            document.querySelectorAll('input[name="selected_ids[]"]').forEach(function (checkbox) {
                checkbox.checked = e.target.checked;
            });
        });
    </script>

    <?php
    $output .= ob_get_clean();
    return $output;
});