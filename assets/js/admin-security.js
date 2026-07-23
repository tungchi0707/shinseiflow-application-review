(function ($) {
  'use strict';

  function updateTurnstileSettingsState($toggle) {
    var enabled = $toggle.is(':checked');
    var $form = $toggle.closest('.tcarm-admin-security-page');
    var $dependentSettings = $form.find('[data-tcarm-turnstile-dependent-settings]');
    var $state = $form.find('.tcarm-switch-state');

    $dependentSettings
      .toggleClass('is-disabled', !enabled)
      .attr('aria-disabled', enabled ? 'false' : 'true');

    $dependentSettings
      .find('input, select, textarea, button')
      .prop('disabled', !enabled);

    if ($state.length) {
      $state.text(enabled ? $state.data('tcarm-switch-on') : $state.data('tcarm-switch-off'));
    }
  }

  $(function () {
    var $toggle = $('#tcarm_turnstile_enabled');

    if (!$toggle.length) {
      return;
    }

    updateTurnstileSettingsState($toggle);

    $toggle.on('change', function () {
      updateTurnstileSettingsState($(this));
    });
  });
})(jQuery);
