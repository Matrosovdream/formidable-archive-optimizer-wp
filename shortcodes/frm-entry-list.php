<?php

add_shortcode('frm_entry_list', function () {
    global $wpdb;

    // Add TailwindCSS
    $output = '<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">';

    // Filters
    $filters = [
        'form_id' => isset($_GET['form_id']) ? (int) $_GET['form_id'] : '',
        'order_num' => sanitize_text_field($_GET['order_num'] ?? ''),
        'common_search' => sanitize_text_field($_GET['common_search'] ?? ''),
    ];

    // Pagination
    $page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
    $per_page = 30;
    $offset = ($page - 1) * $per_page;

    // Get entries
    $archiver = new Frm_optimizer_archive();
    $result = $archiver->getEntries($filters, ['per_page' => $per_page, 'offset' => $offset]);
    $entries = $result['entries'] ?? [];
    $total = $result['total'] ?? 0;
    $total_pages = $result['total_pages'] ?? 0;
    $current_page = $result['current_page'] ?? 1;
    
    // Get forms
    $forms = $archiver->getFrmForms();

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

            <input type="text" name="order_num" placeholder="Order #" value="<?php echo esc_attr($filters['order_num']); ?>" class="border rounded p-2" />
            <input type="text" name="common_search" placeholder="Common Search..." value="<?php echo esc_attr($filters['common_search']); ?>" class="border rounded p-2" />

            <div class="flex justify-end md:col-span-3">
                <button type="submit" class="w-1/3 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Filter
                </button>
            </div>
        </form>

        <!-- Bulk Actions Form -->
        <form method="post">
            <?php wp_nonce_field('ffao_bulk_action'); ?>

            <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
                <div class="flex items-center gap-2">
                    <!--
                    <select name="ffao_action" class="border p-2 rounded">
                        <option value="">Bulk Actions</option>
                        <option value="restore">Restore</option>
                    </select>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Apply
                    </button>
                    -->
                </div>
                <div class="text-base font-medium text-gray-800">
                    Total records: <strong><?php echo number_format($total); ?></strong>
                </div>
            </div>

            <!-- Entries Table -->
            <div class="overflow-x-auto bg-white shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2"><input type="checkbox" id="select-all"></th>
                            <th class="px-4 py-2 text-left" style="width: 10%;">Order #</th>
                            <th class="px-4 py-2 text-left" style="width: 15%;">Form</th>
                            <th class="px-4 py-2 text-left" style="width: 15%;">Created at</th>
                            <th class="px-4 py-2 text-left">Link</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($entries): foreach ($entries as $entry): ?>
                            <tr>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" name="selected_ids[]" value="<?php echo esc_attr($entry->id); ?>">
                                </td>
                                <td class="px-4 py-2"><?php echo esc_html($entry->id ?: '-'); ?></td>
                                <td class="px-4 py-2"><?php echo $forms[$entry->form_id]['name'] ?? ''; ?></td>
                                <td class="px-4 py-2">
                                    <?php echo esc_html(date('Y-m-d', strtotime($entry->created_at))); ?>
                                </td>
                                <td class="px-4 py-2">
                                    Link
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
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center mt-6 space-x-2">
                <?php
                for ($i = 1; $i <= $total_pages; $i++) {
                    if (
                        $i <= 3 ||
                        ($i >= $current_page - 2 && $i <= $current_page + 2) ||
                        $i > $total_pages - 3
                    ) {
                        $active = ($i == $current_page) ? 'bg-blue-600 text-white' : 'bg-gray-200';
                        echo '<a href="' . esc_url(add_query_arg('paged', $i)) . '" class="px-4 py-2 ' . $active . ' rounded">' . $i . '</a>';
                    } elseif (
                        ($i == 4 && $current_page > 6) ||
                        ($i == $total_pages - 3 && $current_page < $total_pages - 5)
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
            document.querySelectorAll('input[name="selected_ids[]"]').forEach(cb => cb.checked = e.target.checked);
        });
    </script>

    <?php
    $output .= ob_get_clean();
    return $output;
});
