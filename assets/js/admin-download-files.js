(function($){
  function t(key, fallback){
    var i18n = window.tcarmAdminI18n || {};
    return typeof i18n[key] === 'string' && i18n[key] !== '' ? i18n[key] : fallback;
  }
  function escHtml(value){
    return $('<div>').text(value || '').html();
  }
  function escAttr(value){
    return escHtml(value).replace(/"/g, '&quot;');
  }
  function renumberDownloadFiles(){
    $('[data-tcarm-download-list] [data-tcarm-download-row]:visible').each(function(index){
      $(this).find('.tcarm-download-sort').val((index + 1) * 10);
    });
  }
  $(document).on('click', '[data-tcarm-add-download-file]', function(){
    var tpl = $('#tcarm-download-file-template').html();
    if (!tpl) return;
    var key = 'new_' + Date.now().toString(36) + '_' + Math.floor(Math.random()*9999).toString(36);
    $('[data-tcarm-download-list]').append(tpl.replace(/__KEY__/g, key));
    renumberDownloadFiles();
  });
  $(document).on('click', '[data-tcarm-remove-download-file]', function(){
    var $row = $(this).closest('[data-tcarm-download-row]');
    $row.find('.tcarm-download-delete').val('1');
    $row.addClass('is-deleted').hide();
    renumberDownloadFiles();
  });
  $(document).on('click', '[data-tcarm-clear-download-file]', function(){
    var $row = $(this).closest('[data-tcarm-download-row]');
    $row.find('.tcarm-download-attachment-id').val('0');
    $row.find('.tcarm-download-file-url').val('');
    $row.find('[data-tcarm-attachment-label]').text('-');
  });
  $(document).on('click', '[data-tcarm-select-download-file]', function(e){
    e.preventDefault();
    var $row = $(this).closest('[data-tcarm-download-row]');
    if (typeof wp === 'undefined' || !wp.media) return;
    var frame = wp.media({ title: t('downloadMediaTitle', 'Select download file'), button: { text: t('downloadMediaButton', 'Use this file') }, multiple: false });
    frame.on('select', function(){
      var attachment = frame.state().get('selection').first().toJSON();
      $row.find('.tcarm-download-attachment-id').val(attachment.id || 0);
      $row.find('.tcarm-download-file-url').val(attachment.url || '');
      $row.find('[data-tcarm-attachment-label]').text(attachment.id || '-');
      var $label = $row.find('input[name$="[label]"]');
      if (!$label.val()) {
        $label.val(attachment.title || attachment.filename || '');
      }
    });
    frame.open();
  });
  function toggleDropdownSettings($scope){
    $scope = $scope && $scope.length ? $scope : $(document);
    var $cards = $scope.hasClass && $scope.hasClass('tcarm-mini-field-card') ? $scope : $scope.find('.tcarm-mini-field-card');
    $cards.each(function(){
      var $card = $(this);
      var type = $card.find('select[name$="[type]"]').val();
      var isDropdown = type === 'dropdown';
      $card.toggleClass('is-dropdown', isDropdown);
      $card.find('> .tcarm-dropdown-settings').toggle(isDropdown);
    });
  }
  function updateConsentRequiredState($scope){
    $scope = $scope && $scope.length ? $scope : $(document);
    var $groups = $scope.hasClass && $scope.hasClass('tcarm-consent-group') ? $scope : $scope.find('.tcarm-consent-group');
    $groups.each(function(){
      var $group = $(this);
      var $show = $group.find('.tcarm-consent-show-checkbox-input');
      var $required = $group.find('.tcarm-consent-required-input');
      if (!$show.length || !$required.length) return;
      if (!$show.prop('checked')) {
        $required.prop('checked', false).prop('disabled', true);
      } else {
        $required.prop('disabled', false);
      }
      $required.closest('.tcarm-consent-required').toggleClass('is-disabled', $required.prop('disabled'));
    });
  }
  function dropdownChoiceRow(name, index){
    return '<div class="tcarm-dropdown-choice-row"><input type="text" name="'+name+'[choices]['+index+'][label]" value="" placeholder="'+escAttr(t('displayName', 'Display name'))+'"><input type="text" name="'+name+'[choices]['+index+'][value]" value="" placeholder="'+escAttr(t('savedValue', 'Saved value'))+'"><button type="button" class="button tcarm-remove-dropdown-choice">'+escHtml(t('remove', 'Delete'))+'</button></div>';
  }
  $(function(){
    $('[data-tcarm-download-list]').sortable({
      items: '[data-tcarm-download-row]:not(.is-deleted)',
      handle: '.tcarm-download-file-grid',
      stop: renumberDownloadFiles
    });
  });
})(jQuery);
