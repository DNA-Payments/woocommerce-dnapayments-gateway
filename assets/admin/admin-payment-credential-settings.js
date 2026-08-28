/**
 * DNA Payments — admin settings helper.
 *
 * Marks the API credential fields as required depending on the "Enable Test Mode"
 * checkbox, using the browser's native HTML5 `required` validation plus a red
 * asterisk on the field label:
 *   - Test mode ON  -> Test Client ID / Test Client secret / Test Terminal ID required.
 *   - Test mode OFF -> LIVE Client ID / LIVE Secret / LIVE Terminal ID required.
 *
 * Loaded only on WooCommerce > Settings > Payments > DNA Payments.
 */
(function ($) {
    'use strict';

    var PREFIX = 'woocommerce_dnapayments_';
    var TEST_FIELDS = ['test_client_id', 'test_client_secret', 'test_terminal'];
    var LIVE_FIELDS = ['client_id', 'client_secret', 'terminal'];

    function $field(key) {
        return $('#' + PREFIX + key);
    }

    function $label(key) {
        return $('label[for="' + PREFIX + key + '"]');
    }

    function setRequired(key, on) {
        var $el = $field(key);
        if (!$el.length) {
            return;
        }

        var $lbl = $label(key);

        if (on) {
            $el.attr('required', 'required').attr('aria-required', 'true');
            if ($lbl.length && $lbl.find('.dna-required').length === 0) {
                $lbl.append(' <span class="dna-required" style="color:#d63638" aria-hidden="true">*</span>');
            }
        } else {
            $el.removeAttr('required').removeAttr('aria-required');
            $lbl.find('.dna-required').remove();
        }
    }

    function apply() {
        var testOn = $field('is_test_mode').is(':checked');

        TEST_FIELDS.forEach(function (key) {
            setRequired(key, testOn);
        });
        LIVE_FIELDS.forEach(function (key) {
            setRequired(key, !testOn);
        });
    }

    $(function () {
        // Bail if we are not on the DNA Payments settings form.
        if (!$field('is_test_mode').length) {
            return;
        }

        apply();
        $field('is_test_mode').on('change', apply);
    });
})(jQuery);
