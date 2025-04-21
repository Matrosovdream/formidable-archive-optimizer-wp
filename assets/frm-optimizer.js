jQuery(document).ready(function ($) {
    function runAjax(action, btnSelector, msgSelector) {
        const $btn = $(btnSelector);
        const $msg = $(msgSelector);

        $msg.html('<span class="spinner is-active"></span> Processing...');
        $btn.prop('disabled', true);

        $.post(frm_optimizer.ajax_url, {
            action: action,
            nonce: frm_optimizer.nonce
        }, function (response) {
            if (response.success) {
                $msg.html('<span class="success-msg">' + response.data.message + '</span>');
            } else {
                $msg.html('<span class="error-msg">Something went wrong</span>');
            }
            $btn.prop('disabled', false);
        });
    }

    $('#fo-archive-btn').on('click', function (e) {
        e.preventDefault();
        runAjax('frm_archive_entries', '#fo-archive-btn', '#fo-archive-msg');
    });

    $('#fo-restore-btn').on('click', function (e) {
        e.preventDefault();
        runAjax('frm_restore_entries', '#fo-restore-btn', '#fo-restore-msg');
    });
});
