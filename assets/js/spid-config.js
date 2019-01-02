(function ($) {

    'use strict';

    Drupal.behaviors.spid_config = {
        attach: function (context) {
            $('[data-drupal-selector="edit-sp-metadata-attributes-fiscalnumber"]').attr("disabled", true);
            $('[data-drupal-selector="edit-sp-metadata-attributes-email"]').attr("disabled", true);
        }
    };

})(jQuery);
