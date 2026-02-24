/* =========================================
   CS User Dashboard JS
========================================= */

(function ($) {

    "use strict";

    /* =========================================
       Auto Close Alerts
    ========================================= */

    function autoCloseAlerts() {
        $('.cs-alert').each(function () {
            let alertBox = $(this);

            setTimeout(function () {
                alertBox.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 5000);
        });
    }

    /* =========================================
       Confirm Actions
    ========================================= */

    function bindConfirmActions() {
        $(document).on('click', '[data-confirm]', function (e) {

            let message = $(this).data('confirm');

            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    }

    /* =========================================
       Table Row Highlight on Hover
    ========================================= */

    function enhanceTables() {
        $('.cs-table tbody tr').hover(
            function () {
                $(this).css('background', '#f9fbff');
            },
            function () {
                $(this).css('background', '');
            }
        );
    }

    /* =========================================
       Credit Code Input Enhancement
    ========================================= */

    function creditCodeInputHandler() {

        let input = $('.cs-credit-code-input');

        if (!input.length) return;

        input.on('input', function () {
            this.value = this.value.toUpperCase();
        });

        $('.cs-credit-code-form').on('submit', function () {

            let code = input.val().trim();

            if (code.length < 4) {
                alert('کد وارد شده معتبر نیست.');
                return false;
            }

        });
    }

    /* =========================================
       Notification AJAX Mark as Read (Ready)
    ========================================= */

    function notificationAjaxReady() {

        $(document).on('click', '.cs-mark-read-ajax', function (e) {
            e.preventDefault();

            let btn = $(this);
            let id = btn.data('id');

            if (!id || typeof cs_ajax === 'undefined') return;

            $.post(cs_ajax.ajax_url, {
                action: 'cs_mark_notification_read',
                notification_id: id
            }, function (response) {

                if (response.success) {
                    btn.closest('.cs-notification-item')
                        .removeClass('cs-unread');

                    btn.remove();
                }

            });

        });

    }

    /* =========================================
       Smooth Scroll to Top
    ========================================= */

    function scrollToTopButton() {

        let btn = $('<div class="cs-scroll-top">↑</div>');
        $('body').append(btn);

        btn.css({
            position: 'fixed',
            bottom: '25px',
            right: '25px',
            width: '40px',
            height: '40px',
            background: '#1e73be',
            color: '#fff',
            'text-align': 'center',
            'line-height': '40px',
            'border-radius': '50%',
            cursor: 'pointer',
            display: 'none',
            'z-index': '999'
        });

        $(window).scroll(function () {
            if ($(this).scrollTop() > 300) {
                btn.fadeIn();
            } else {
                btn.fadeOut();
            }
        });

        btn.on('click', function () {
            $('html, body').animate({ scrollTop: 0 }, 400);
        });
    }

    /* =========================================
       Initialize
    ========================================= */

    $(document).ready(function () {

        autoCloseAlerts();
        bindConfirmActions();
        enhanceTables();
        creditCodeInputHandler();
        notificationAjaxReady();
        scrollToTopButton();

    });

})(jQuery);
