jQuery(document).ready(function ($) {

    function runAjax(action, btnSelector, msgSelector, archivePeriod) {
        const $btn = $(btnSelector);
        const $msg = $(msgSelector);

        $msg.html('<span class="spinner is-active"></span> Processing...');
        $btn.prop('disabled', true);

        $.post(frm_optimizer.ajax_url, {
            action: action,
            nonce: frm_optimizer.nonce,
            archivePeriod: archivePeriod
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
        const archivePeriod = $('#archive-period').val(); // Get the value of the archive-period input field
        runAjax('frm_archive_entries', '#fo-archive-btn', '#fo-archive-msg', archivePeriod);

        setTimeout(function () {
            location.reload();
        }, 1000);
    });

    $('#fo-restore-btn').on('click', function (e) {
        e.preventDefault();
        runAjax('frm_restore_entries', '#fo-restore-btn', '#fo-restore-msg');

        setTimeout(function () {
            location.reload();
        }, 1000);
    });

    $('#fo-form-settings').on('submit', function (e) {
        e.preventDefault();
        const formData = {};
        $('#fo-forms-table tbody tr').each(function () {
            const formId = $(this).data('form-id');
            const field_ids = $(this).find('.field-ids').val().trim().split(',').map(s => s.trim()).filter(Boolean);
            const status = $(this).find('.field-status').val();
            const dot = $(this).find('.field-dot').val();
            const email = $(this).find('.field-email').val();
            formData[formId] = { field_ids, status, dot, email };
        });

        $.post(frm_optimizer.ajax_url, {
            action: 'frm_save_form_field_settings',
            nonce: frm_optimizer.nonce,
            data: formData
        }, function (res) {
            $('#fo-settings-msg').text(res.data.message).css('color', res.success ? 'green' : 'red');
            if (res.success) location.reload();
        });
    });

    $('#fo-enabled-forms-form').on('submit', function (e) {
        e.preventDefault();
        const selectedForms = [];
        $('#fo-enabled-forms-form select[name="enabled_forms[]"] option:selected').each(function () {
            selectedForms.push($(this).val());
        });

        $.post(frm_optimizer.ajax_url, {
            action: 'frm_save_enabled_forms',
            nonce: frm_optimizer.nonce,
            forms: selectedForms
        }, function (res) {
            $('#fo-enabled-msg').text(res.data.message).css('color', res.success ? 'green' : 'red');
            if (res.success) location.reload();
        });
    });

    $('#fo-statuses-form').on('submit', function (e) {
        e.preventDefault();
        const statuses = $('#fo-statuses').val().trim().split(',').map(s => s.trim()).filter(Boolean);
        $.post(frm_optimizer.ajax_url, {
            action: 'frm_save_statuses',
            nonce: frm_optimizer.nonce,
            statuses: statuses
        }, function (res) {
            $('#fo-statuses-msg').text(res.data.message).css('color', res.success ? 'green' : 'red');
            if (res.success) location.reload();
        });
    });

    // Save Full Search Enabled Forms
    $('#fo-enabled-forms-form-search').on('submit', function (e) {
        e.preventDefault();
        const selected = $(this).find('select').val();

        $.post(frm_optimizer.ajax_url, {
            action: 'frm_save_enabled_forms_search',
            nonce: frm_optimizer.nonce,
            forms: selected
        }, function (response) {
            $('#fo-enabled-msg-search').text(response.data.message).fadeIn().delay(2000).fadeOut();
        });
    });

    // Save Full Search Form Field Settings
    $('#fo-form-settings-search').on('submit', function (e) {
        e.preventDefault();
        const data = {};

        $('#fo-form-settings-search tbody tr').each(function () {
            const row = $(this);
            const formId = row.data('form-id');
            data[formId] = {
                field_ids: row.find('.field-ids').val().split(',').map(i => i.trim()),
                status: row.find('.field-status').val(),
                dot: row.find('.field-dot').val(),
                email: row.find('.field-email').val()
            };
        });

        $.post(frm_optimizer.ajax_url, {
            action: 'frm_save_form_field_settings_search',
            nonce: frm_optimizer.nonce,
            data: data
        }, function (response) {
            $('#fo-settings-msg-search').text(response.data.message).fadeIn().delay(2000).fadeOut();
        });
    });
    

});
