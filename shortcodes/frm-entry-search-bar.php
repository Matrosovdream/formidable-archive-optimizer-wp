<?php

add_shortcode('frm_entry_search_bar', function ( $args ) {

    // Get args
    $args = shortcode_atts([
        'search-url' => ''
    ], $args);

    // Add TailwindCSS
    $output = '<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">';

    ob_start();
    ?>

        <!-- Filter Form -->
        <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6" action="<?php echo esc_url($args['search-url']); ?>">

            <input type="text" name="order_num" placeholder="Order #" value="" class="border rounded p-2" />
            <input type="text" name="common_search" placeholder="Common Search..." value="" class="border rounded p-2" />

            <button type="submit" class="w-1/3 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Filter
            </button>

        </form>

    <?php
    $output .= ob_get_clean();
    return $output;
});
