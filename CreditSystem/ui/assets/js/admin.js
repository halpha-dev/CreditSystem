(function ($) {
    'use strict';

    const CSAdmin = {

        init: function () {
            // #region agent log
            fetch('http://127.0.0.1:7242/ingest/f7623918-4d6a-43f1-9869-2b3a9f833441', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    location: 'ui/assets/js/admin.js:init',
                    message: 'CSAdmin init',
                    data: {
                        hasCsAdminConfig: (typeof cs_admin !== 'undefined')
                    },
                    runId: 'assets-pre-fix',
                    hypothesisId: 'H0',
                    timestamp: Date.now()
                })
            }).catch(function () { });
            // #endregion

            this.bindConfirmActions();
            this.bindStatusToggle();
            this.bindBulkActions();
            this.bindModal();
            this.bindSearchFilter();
            this.bindTableSort();
            this.bindAutoRefreshWidgets();
        },

        /* ===============================
           Confirm Delete / Dangerous Actions
        ================================= */
        bindConfirmActions: function () {
            $(document).on('click', '.cs-confirm', function (e) {
                const message = $(this).data('confirm') || 'آیا مطمئن هستید؟';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        },

        /* ===============================
           Ajax Status Toggle (KYC, Credit, Code, Installment)
        ================================= */
        bindStatusToggle: function () {
            $(document).on('click', '.cs-toggle-status', function (e) {
                e.preventDefault();

                const btn = $(this);
                const id = btn.data('id');
                const type = btn.data('type');
                const status = btn.data('status');

                // #region agent log
                fetch('http://127.0.0.1:7242/ingest/f7623918-4d6a-43f1-9869-2b3a9f833441', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        location: 'ui/assets/js/admin.js:bindStatusToggle',
                        message: 'Toggle status clicked',
                        data: {
                            type: type,
                            status: status
                        },
                        runId: 'assets-pre-fix',
                        hypothesisId: 'H1',
                        timestamp: Date.now()
                    })
                }).catch(function () { });
                // #endregion

                btn.prop('disabled', true);

                $.post(cs_admin.ajax_url, {
                    action: 'cs_toggle_status',
                    id: id,
                    type: type,
                    status: status,
                    _wpnonce: cs_admin.nonce
                }, function (response) {

                    btn.prop('disabled', false);

                    // #region agent log
                    fetch('http://127.0.0.1:7242/ingest/f7623918-4d6a-43f1-9869-2b3a9f833441', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            location: 'ui/assets/js/admin.js:bindStatusToggle',
                            message: 'Toggle status response',
                            data: {
                                success: !!response.success
                            },
                            runId: 'assets-pre-fix',
                            hypothesisId: 'H1',
                            timestamp: Date.now()
                        })
                    }).catch(function () { });
                    // #endregion

                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'خطا در انجام عملیات');
                    }

                });
            });
        },

        /* ===============================
           Bulk Actions
        ================================= */
        bindBulkActions: function () {

            $('#cs-select-all').on('change', function () {
                $('.cs-row-checkbox').prop('checked', this.checked);
            });

            $('#cs-bulk-apply').on('click', function (e) {
                e.preventDefault();

                const action = $('#cs-bulk-action').val();
                if (!action) {
                    alert('یک عملیات انتخاب کنید.');
                    return;
                }

                const selected = [];
                $('.cs-row-checkbox:checked').each(function () {
                    selected.push($(this).val());
                });

                if (selected.length === 0) {
                    alert('هیچ موردی انتخاب نشده.');
                    return;
                }

                if (!confirm('آیا از انجام عملیات گروهی مطمئن هستید؟')) {
                    return;
                }

                // #region agent log
                fetch('http://127.0.0.1:7242/ingest/f7623918-4d6a-43f1-9869-2b3a9f833441', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        location: 'ui/assets/js/admin.js:bindBulkActions',
                        message: 'Bulk action requested',
                        data: {
                            action: action,
                            count: selected.length
                        },
                        runId: 'assets-pre-fix',
                        hypothesisId: 'H2',
                        timestamp: Date.now()
                    })
                }).catch(function () { });
                // #endregion

                $.post(cs_admin.ajax_url, {
                    action: 'cs_bulk_action',
                    ids: selected,
                    bulk_action: action,
                    _wpnonce: cs_admin.nonce
                }, function (response) {

                    // #region agent log
                    fetch('http://127.0.0.1:7242/ingest/f7623918-4d6a-43f1-9869-2b3a9f833441', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            location: 'ui/assets/js/admin.js:bindBulkActions',
                            message: 'Bulk action response',
                            data: {
                                success: !!response.success,
                                count: selected.length
                            },
                            runId: 'assets-pre-fix',
                            hypothesisId: 'H2',
                            timestamp: Date.now()
                        })
                    }).catch(function () { });
                    // #endregion

                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'خطا در عملیات گروهی');
                    }

                });

            });
        },

        /* ===============================
           Modal
        ================================= */
        bindModal: function () {

            $(document).on('click', '[data-cs-modal]', function () {
                const target = $(this).data('cs-modal');
                $('#' + target).fadeIn(200);
            });

            $(document).on('click', '.cs-modal-close', function () {
                $(this).closest('.cs-modal').fadeOut(200);
            });

            $(document).on('click', '.cs-modal', function (e) {
                if ($(e.target).hasClass('cs-modal')) {
                    $(this).fadeOut(200);
                }
            });

        },

        /* ===============================
           Table Search Filter
        ================================= */
        bindSearchFilter: function () {

            $('#cs-table-search').on('keyup', function () {
                const value = $(this).val().toLowerCase();

                $('.cs-table tbody tr').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

        },

        /* ===============================
           Simple Table Sort
        ================================= */
        bindTableSort: function () {

            $('.cs-table th[data-sort]').on('click', function () {

                const index = $(this).index();

                // #region agent log
                fetch('http://127.0.0.1:7242/ingest/f7623918-4d6a-43f1-9869-2b3a9f833441', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        location: 'ui/assets/js/admin.js:bindTableSort',
                        message: 'Table sort header clicked',
                        data: {
                            columnIndex: index
                        },
                        runId: 'assets-pre-fix',
                        hypothesisId: 'H3',
                        timestamp: Date.now()
                    })
                }).catch(function () { });
                // #endregion

                const table = $(this).parents('table').eq(0);
                const rows = table.find('tr:gt(0)').toArray().sort(CSAdmin.comparer(index));

                this.asc = !this.asc;

                if (!this.asc) {
                    rows.reverse();
                }

                for (let i = 0; i < rows.length; i++) {
                    table.append(rows[i]);
                }

            });

        },

        comparer: function (index) {
            return function (a, b) {
                const valA = CSAdmin.getCellValue(a, index);
                const valB = CSAdmin.getCellValue(b, index);
                return $.isNumeric(valA) && $.isNumeric(valB)
                    ? valA - valB
                    : valA.localeCompare(valB);
            };
        },

        getCellValue: function (tr, index) {
            return $(tr).children('td').eq(index).text();
        },

        /* ===============================
           Auto Refresh Dashboard Widgets
        ================================= */
        bindAutoRefreshWidgets: function () {

            if (!$('.cs-widget[data-refresh]').length) {
                return;
            }

            setInterval(function () {

                $('.cs-widget[data-refresh]').each(function () {

                    const widget = $(this);
                    const widgetType = widget.data('refresh');

                    $.post(cs_admin.ajax_url, {
                        action: 'cs_refresh_widget',
                        widget: widgetType,
                        _wpnonce: cs_admin.nonce
                    }, function (response) {

                        if (response.success) {
                            widget.html(response.data.html);
                        }

                    });

                });

            }, 60000); // هر 60 ثانیه

        }

    };

    $(document).ready(function () {
        CSAdmin.init();
    });

})(jQuery);