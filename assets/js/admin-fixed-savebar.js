(function($){
  function t(key, fallback){
    var i18n = window.tcarmAdminI18n || {};
    return typeof i18n[key] === 'string' && i18n[key] !== '' ? i18n[key] : fallback;
  }
  function escHtml(value){
    return $('<div>').text(value || '').html();
  }
  function findPrimarySaveButton(){
    var $page = $('.tcarm-admin-page').first();
    if (!$page.length) return $();

    var $candidates = $page.find('form .submit input[type="submit"].button-primary, form .submit button[type="submit"].button-primary').filter(function(){
      var $button = $(this);
      var $form = $button.closest('form');
      if (!$button.is(':visible') || $button.prop('disabled')) return false;
      if ($button.closest('.tcarm-detail-action-panel, .tcarm-confirm-modal, .tcarm-applications-table').length) return false;
      if ($form.is('#tcarm-test-mail-form')) return false;
      if ($form.find('input[name="action"][value="tcarm_update_status"], input[name="action"][value="tcarm_bulk_delete_applications"], input[name="action"][value="tcarm_restore_application"], input[name="action"][value="tcarm_resend_email"]').length) return false;
      return true;
    });

    return $candidates.last();
  }

  function buttonLabel($button){
    if (!$button.length) return t('save', 'Save');
    var label = $button.is('input') ? $button.val() : $.trim($button.text());
    return $.trim(label || t('save', 'Save'));
  }

  function ensureSavebar(){
    if ($('#tcarm-admin-fixed-savebar').length) return $('#tcarm-admin-fixed-savebar');
    var $bar = $('<div class="tcarm-admin-fixed-savebar" id="tcarm-admin-fixed-savebar" aria-hidden="true"><div class="tcarm-admin-fixed-savebar__inner"><span class="tcarm-admin-fixed-savebar__text">'+escHtml(t('unsavedChanges', 'Please save your changes.'))+'</span><button type="button" class="button button-primary tcarm-admin-fixed-savebar__button"></button></div></div>');
    $('body').append($bar);
    return $bar;
  }

  function initFixedSavebar(){
    var $target = findPrimarySaveButton();
    if (!$target.length) return;

    var $bar = ensureSavebar();
    var $shortcut = $bar.find('.tcarm-admin-fixed-savebar__button');
    $shortcut.text(buttonLabel($target));
    $('body').addClass('tcarm-has-fixed-savebar');

    $shortcut.off('click.tcarmFixedSavebar').on('click.tcarmFixedSavebar', function(e){
      e.preventDefault();
      var $currentTarget = findPrimarySaveButton();
      if (!$currentTarget.length) return;
      $currentTarget.trigger('click');
    });

    function toggleSavebar(){
      var visible = window.pageYOffset > 80;
      $bar.toggleClass('is-visible', visible).attr('aria-hidden', visible ? 'false' : 'true');
    }

    $(window).off('scroll.tcarmFixedSavebar resize.tcarmFixedSavebar').on('scroll.tcarmFixedSavebar resize.tcarmFixedSavebar', toggleSavebar);
    toggleSavebar();
  }

  $(function(){
    initFixedSavebar();
  });
})(jQuery);
