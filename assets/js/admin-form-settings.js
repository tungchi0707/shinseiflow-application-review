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
  function uniqueId(prefix){
    return prefix + '_' + Date.now().toString(36) + '_' + Math.floor(Math.random()*9999).toString(36);
  }
  function renumberFields($scope){
    $scope.find('> .tcarm-section-group-body > .tcarm-mini-field-card').each(function(index){
      $(this).find('.tcarm-mini-sort').val((index + 1) * 10);
    });
  }
  function renumberSections(){
    $('.tcarm-sortable-sections > .tcarm-section-group').each(function(index){
      var $group = $(this);
      var sectionKey = $group.data('section');
      $group.find('> .tcarm-section-group-body > .tcarm-mini-field-card .tcarm-field-section-input').val(sectionKey);
      $group.find('> summary .tcarm-section-sort').val((index + 1) * 10);
      renumberFields($group);
    });
  }
  function renumberConsents(){
    $('.tcarm-consent-group:visible').each(function(index){
      $(this).find('.tcarm-consent-sort').val((index + 1) * 10);
    });
  }
  function initSortables(){
    var $sections = $('.tcarm-sortable-sections');
    if ($sections.length && !$sections.data('tcarm-sortable')) {
      $sections.data('tcarm-sortable', 1).sortable({
        items: '> .tcarm-section-group',
        handle: '.tcarm-section-drag',
        tolerance: 'pointer',
        placeholder: 'tcarm-section-placeholder',
        update: renumberSections
      });
    }
    $('.tcarm-sortable-fields').each(function(){
      var $list = $(this);
      if ($list.data('tcarm-sortable')) return;
      $list.data('tcarm-sortable', 1).sortable({
        items: '> .tcarm-mini-field-card',
        handle: '.tcarm-field-drag',
        tolerance: 'pointer',
        placeholder: 'tcarm-field-placeholder',
        connectWith: '.tcarm-sortable-fields',
        receive: function(event, ui){
          var sectionKey = $(this).closest('.tcarm-section-group').data('section');
          ui.item.find('.tcarm-field-section-input').val(sectionKey);
        },
        update: function(){ renumberSections(); }
      });
    });
    var $consents = $('.tcarm-sortable-consents');
    if ($consents.length && !$consents.data('tcarm-sortable')) {
      $consents.data('tcarm-sortable', 1).sortable({
        items: '> .tcarm-consent-group',
        handle: '.tcarm-consent-drag',
        tolerance: 'pointer',
        placeholder: 'tcarm-section-placeholder',
        update: renumberConsents
      });
    }
  }
  function iconSvg(type){
    if (type === 'edit') {
      return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 19h1.4l9.9-9.9-1.4-1.4L5 17.6V19Zm-2 2v-4.25L16.3 3.45c.2-.2.42-.34.66-.44.24-.1.49-.15.74-.15.27 0 .53.05.78.16.25.11.47.26.66.45l1.41 1.42c.2.19.35.41.45.66.1.25.15.5.15.76s-.05.51-.15.75c-.1.24-.25.46-.45.66L7.25 21H3Zm16.1-14.7-1.4-1.4 1.4 1.4Zm-3.5 2.8-1.4-1.4 1.4 1.4Z"/></svg>';
    }
    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 21c-.55 0-1.02-.2-1.41-.59C5.2 20.02 5 19.55 5 19V6H4V4h5V3h6v1h5v2h-1v13c0 .55-.2 1.02-.59 1.41-.39.39-.86.59-1.41.59H7ZM17 6H7v13h10V6ZM9 17h2V8H9v9Zm4 0h2V8h-2v9Z"/></svg>';
  }
  function fieldCardHtml(sectionKey){
    var id = uniqueId('new');
    var fieldKey = 'field_' + id.replace('new_','');
    var name = 'tcarm_form_fields[' + id + ']';
    return ''+
      '<div class="tcarm-mini-field-card is-enabled" data-field="'+id+'">'+
        '<div class="tcarm-mini-field-title">'+
          '<span class="tcarm-field-drag" title="'+escAttr(t('dragToSort', 'Drag to reorder'))+'" aria-hidden="true">\u2630</span><input class="tcarm-mini-sort" type="hidden" name="'+name+'[sort_order]" value="999">'+
          '<label class="tcarm-switch"><input type="checkbox" name="'+name+'[enabled]" value="1" checked><span></span></label>'+
          '<div class="tcarm-mini-name"><strong>'+escHtml(t('newField', 'New field'))+'</strong><code>'+fieldKey+'</code></div>'+
        '</div>'+
        '<div class="tcarm-mini-field-editor">'+
        '<div class="tcarm-mini-field-controls">'+
          '<input class="tcarm-field-section-input" type="hidden" name="'+name+'[section]" value="'+sectionKey+'">'+
          '<input type="hidden" name="'+name+'[key]" value="'+fieldKey+'">'+
          '<div class="tcarm-mini-field-controls-row tcarm-mini-field-controls-row-main">'+
            '<label class="tcarm-field-control-type">'+escHtml(t('type', 'Type'))+'<select name="'+name+'[type]"><option value="text">'+escHtml(t('textType', 'Text'))+'</option><option value="textarea">'+escHtml(t('textareaType', 'Textarea'))+'</option><option value="email">'+escHtml(t('emailType', 'Email'))+'</option><option value="url">URL</option><option value="tel">'+escHtml(t('phoneType', 'Phone number'))+'</option><option value="date">'+escHtml(t('dateType', 'Date'))+'</option><option value="checkbox_group">'+escHtml(t('checkboxGroupType', 'Checkbox'))+'</option><option value="radio">'+escHtml(t('radioType', 'Radio Button Group'))+'</option><option value="file">'+escHtml(t('fileUploadType', 'File upload'))+'</option><option value="dropdown">'+escHtml(t('dropdownType', 'Dropdown'))+'</option></select></label>'+
            '<label class="tcarm-field-control-label">'+escHtml(t('label', 'Display label'))+'<input type="text" name="'+name+'[label]" value="'+escAttr(t('newField', 'New field'))+'"></label>'+
          '</div>'+
          '<div class="tcarm-mini-field-controls-row tcarm-mini-field-controls-row-placeholder">'+
            '<label class="tcarm-field-control-placeholder">'+escHtml(t('placeholder', 'Placeholder'))+'<input type="text" name="'+name+'[placeholder]" value="" placeholder="'+escAttr(t('placeholderExample', 'Example: Enter placeholder text'))+'"></label>'+
          '</div>'+
          '<div class="tcarm-mini-field-controls-row tcarm-mini-field-controls-row-description">'+
            '<label class="tcarm-field-control-description">'+escHtml(t('description', 'Description'))+'<input type="text" name="'+name+'[description]" value="" placeholder="'+escAttr(t('descriptionExample', 'Example: Enter helper text'))+'"></label>'+
          '</div>'+
        '</div>'+
        '<div class="tcarm-mini-field-flags">'+
          '<label class="tcarm-field-control-required"><input type="checkbox" name="'+name+'[required]" value="1"> '+escHtml(t('required', 'Required'))+'</label>'+
        '</div>'+
        '</div>'+
        '<div class="tcarm-dropdown-settings">'+
          '<div class="tcarm-dropdown-settings-title">'+escHtml(t('dropdownChoices', 'Dropdown Choices'))+'</div>'+
          '<p class="description">'+escHtml(t('dropdownChoiceHelp', 'Set the display labels shown on the frontend and the saved values. A blank first option is shown automatically as the placeholder.'))+'</p>'+
          '<div class="tcarm-dropdown-choice-list">'+
            '<div class="tcarm-dropdown-choice-row"><input type="text" name="'+name+'[choices][0][label]" value="" placeholder="'+escAttr(t('displayName', 'Display name'))+'"><input type="text" name="'+name+'[choices][0][value]" value="" placeholder="'+escAttr(t('savedValue', 'Saved value'))+'"><button type="button" class="button tcarm-remove-dropdown-choice">'+escHtml(t('remove', 'Delete'))+'</button></div>'+
          '</div>'+
          '<button type="button" class="button tcarm-add-dropdown-choice">'+escHtml(t('addChoice', 'Add choice'))+'</button>'+
        '</div>'+
        '<div class="tcarm-row-actions"><input type="hidden" class="tcarm-delete-field-input" name="'+name+'[_delete]" value="0"><button type="button" class="tcarm-icon-button tcarm-delete-field" title="'+escAttr(t('deleteFieldTitle', 'Delete field'))+'" aria-label="'+escAttr(t('deleteFieldTitle', 'Delete field'))+'">'+iconSvg('delete')+'</button></div>'+
      '</div>';
  }
  function sectionHtml(){
    var id = uniqueId('new');
    var sectionKey = 'section_' + id.replace('new_','');
    var name = 'tcarm_form_sections[' + id + ']';
    return ''+
      '<details class="tcarm-section-group" data-section="'+sectionKey+'" open>'+
        '<summary class="tcarm-section-group-summary">'+
          '<div class="tcarm-section-summary-main"><span class="tcarm-section-drag" title="'+escAttr(t('dragToSort', 'Drag to reorder'))+'" aria-hidden="true">\u2630</span><span class="tcarm-section-toggle-mark" aria-hidden="true"></span><label class="tcarm-switch"><input type="checkbox" name="'+name+'[enabled]" value="1" checked><span></span></label><strong class="tcarm-section-title-text">'+escHtml(t('newSection', 'New section'))+'</strong><button type="button" class="tcarm-icon-button tcarm-edit-section" title="'+escAttr(t('editSectionName', 'Edit section name'))+'" aria-label="'+escAttr(t('editSectionName', 'Edit section name'))+'">'+iconSvg('edit')+'</button><code>'+sectionKey+'</code></div>'+
          '<div class="tcarm-section-summary-actions"><input class="tcarm-section-sort" type="hidden" name="'+name+'[sort_order]" value="999"><input type="hidden" name="'+name+'[id]" value="'+sectionKey+'"><input class="tcarm-section-label-input" type="hidden" name="'+name+'[label]" value="'+escAttr(t('newSection', 'New section'))+'"><input type="hidden" class="tcarm-delete-section-input" name="'+name+'[_delete]" value="0"><button type="button" class="tcarm-icon-button tcarm-delete-section" title="'+escAttr(t('deleteSectionTitle', 'Delete section'))+'" aria-label="'+escAttr(t('deleteSectionTitle', 'Delete section'))+'">'+iconSvg('delete')+'</button></div>'+
        '</summary>'+
        '<div class="tcarm-section-group-body tcarm-sortable-fields"><div class="tcarm-empty-section">'+escHtml(t('emptySection', 'This section does not have any fields yet.'))+'</div><button type="button" class="button tcarm-add-field">'+escHtml(t('addField', 'Add field'))+'</button></div>'+
      '</details>';
  }
  function consentHtml(){
    var id = uniqueId('new');
    var consentKey = 'consent_' + id.replace('new_','');
    var name = 'tcarm_settings[consent_items][' + id + ']';
    return ''+
      '<details class="tcarm-section-group tcarm-consent-group" data-consent="'+consentKey+'" open>'+
        '<summary class="tcarm-section-group-summary">'+
          '<div class="tcarm-section-summary-main"><span class="tcarm-consent-drag" title="'+escAttr(t('dragToSort', 'Drag to reorder'))+'" aria-hidden="true">\u2630</span><span class="tcarm-section-toggle-mark" aria-hidden="true"></span><label class="tcarm-switch"><input type="checkbox" name="'+name+'[enabled]" value="1" checked><span></span></label><strong class="tcarm-consent-title-text">'+escHtml(t('newConsent', 'New consent item'))+'</strong><button type="button" class="tcarm-icon-button tcarm-edit-consent" title="'+escAttr(t('editConsentName', 'Edit consent item name'))+'" aria-label="'+escAttr(t('editConsentName', 'Edit consent item name'))+'">'+iconSvg('edit')+'</button><code>'+consentKey+'</code></div>'+
          '<div class="tcarm-section-summary-actions"><input class="tcarm-consent-sort" type="hidden" name="'+name+'[sort_order]" value="999"><input type="hidden" name="'+name+'[id]" value="'+consentKey+'"><input class="tcarm-consent-label-input" type="hidden" name="'+name+'[label]" value="'+escAttr(t('newConsent', 'New consent item'))+'"><input type="hidden" class="tcarm-delete-consent-input" name="'+name+'[_delete]" value="0"><button type="button" class="tcarm-icon-button tcarm-delete-consent" title="'+escAttr(t('deleteConsentTitle', 'Delete consent item'))+'" aria-label="'+escAttr(t('deleteConsentTitle', 'Delete consent item'))+'">'+iconSvg('delete')+'</button></div>'+
        '</summary>'+
        '<div class="tcarm-section-group-body tcarm-consent-body">'+
          '<div class="tcarm-consent-fields-row">'+
            '<label class="tcarm-consent-checkbox-text">'+escHtml(t('checkboxText', 'Checkbox text'))+'<input type="text" name="'+name+'[checkbox_text]" value="'+escAttr(t('consentCheckboxDefault', 'I agree to the terms.'))+'" placeholder="'+escAttr(t('consentCheckboxDefault', 'I agree to the terms.'))+'"></label>'+
            '<label>URL<input type="url" name="'+name+'[link_url]" value="" placeholder="'+escAttr(t('urlOrPathPlaceholder', 'https://... or /privacy/'))+'"></label>'+
            '<label>'+escHtml(t('linkText', 'Link text'))+'<input type="text" name="'+name+'[link_text]" value="" placeholder="'+escAttr(t('linkTextExample', 'Example: Terms of Use'))+'"></label>'+
            '<div class="tcarm-consent-check-options">'+
              '<label class="tcarm-consent-show-checkbox"><input type="checkbox" class="tcarm-consent-show-checkbox-input" name="'+name+'[show_checkbox]" value="1" checked> '+escHtml(t('showConsentCheckbox', 'Show consent checkbox'))+'</label>'+ 
              '<label class="tcarm-consent-required"><input type="checkbox" class="tcarm-consent-required-input" name="'+name+'[required]" value="1" checked> '+escHtml(t('required', 'Required'))+'</label>'+ 
            '</div>'+
          '</div>'+
          '<label class="tcarm-consent-textarea-label">'+escHtml(t('consentBodyLabel', 'Consent text to display'))+'<textarea name="'+name+'[body]" rows="8" placeholder="'+escAttr(t('consentBodyPlaceholder', 'Enter consent text or an explanation. Leave blank if you only use a URL.'))+'"></textarea></label>'+
        '</div>'+
      '</details>';
  }  function openSectionModal($group){
    var current = $group.find('> summary .tcarm-section-label-input').val() || $group.find('> summary .tcarm-section-title-text').text();
    var $modal = $('#tcarm-section-name-modal');
    $modal.data('target', $group).data('target-type', 'section').find('.tcarm-section-name-modal-input').val(current);
    $modal.find('#tcarm-section-name-modal-title').text(t('editSectionName', 'Edit section name'));
    $modal.find('.tcarm-modal-body label').contents().first()[0].textContent = t('sectionName', 'Section name') + ' ';
    $modal.addClass('is-open').attr('aria-hidden', 'false');
    setTimeout(function(){ $modal.find('.tcarm-section-name-modal-input').trigger('focus').select(); }, 20);
  }
  function openConsentModal($group){
    var current = $group.find('> summary .tcarm-consent-label-input').val() || $group.find('> summary .tcarm-consent-title-text').text();
    var $modal = $('#tcarm-section-name-modal');
    $modal.data('target', $group).data('target-type', 'consent').find('.tcarm-section-name-modal-input').val(current);
    $modal.find('#tcarm-section-name-modal-title').text(t('editConsentName', 'Edit consent item name'));
    $modal.find('.tcarm-modal-body label').contents().first()[0].textContent = t('consentName', 'Consent item name') + ' ';
    $modal.addClass('is-open').attr('aria-hidden', 'false');
    setTimeout(function(){ $modal.find('.tcarm-section-name-modal-input').trigger('focus').select(); }, 20);
  }
  function closeSectionModal(){
    var $modal = $('#tcarm-section-name-modal');
    $modal.removeClass('is-open').attr('aria-hidden', 'true').removeData('target').removeData('target-type');
    $modal.find('#tcarm-section-name-modal-title').text(t('editSectionName', 'Edit section name'));
    $modal.find('.tcarm-modal-body label').contents().first()[0].textContent = t('sectionName', 'Section name') + ' ';
  }  function toggleDropdownSettings($scope){
    $scope = $scope && $scope.length ? $scope : $(document);
    var $cards = $scope.hasClass && $scope.hasClass('tcarm-mini-field-card') ? $scope : $scope.find('.tcarm-mini-field-card');
    $cards.each(function(){
      var $card = $(this);
      var type = $card.find('select[name$="[type]"]').val();
      var isChoiceField = type === 'dropdown' || type === 'radio' || type === 'checkbox_group';
      var $settings = $card.find('> .tcarm-dropdown-settings');
      $card.toggleClass('is-dropdown', isChoiceField);
      $settings.toggle(isChoiceField);
      $settings.find('.tcarm-dropdown-settings-title').text(type === 'radio' ? t('radioChoices', 'Radio Button Choices') : (type === 'checkbox_group' ? t('checkboxGroupChoices', 'Checkbox Choices') : t('dropdownChoices', 'Dropdown Choices')));
      $settings.find('> .description').text(type === 'radio' ? t('radioChoiceHelp', 'Set the display labels and saved values for the radio buttons.') : (type === 'checkbox_group' ? t('checkboxGroupChoiceHelp', 'Set the display labels and saved values for the checkboxes.') : t('dropdownChoiceHelp', 'Set the display labels shown on the frontend and the saved values. A blank first option is shown automatically as the placeholder.')));
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
  function nextDropdownChoiceIndex($settings){
    var next = parseInt($settings.attr('data-next-choice-index'), 10);
    if (isNaN(next)) {
      next = 0;
      $settings.find('.tcarm-dropdown-choice-row input[name*="[choices]"]').each(function(){
        var match = String($(this).attr('name') || '').match(/\[choices\]\[(\d+)\]\[(?:label|value)\]$/);
        if (match) next = Math.max(next, parseInt(match[1], 10) + 1);
      });
    }
    $settings.attr('data-next-choice-index', next + 1);
    return next;
  }
  function deleteMarkerBin(){
    var $form = $('.tcarm-form-settings-form').first();
    var $bin = $form.find('.tcarm-delete-marker-bin');
    if (!$bin.length) {
      $bin = $('<div class="tcarm-delete-marker-bin" hidden></div>').appendTo($form);
    }
    return $bin;
  }
  function keepDeleteMarker($input){
    if (!$input || !$input.length) return;
    var name = $input.attr('name');
    if (!name) return;
    $('<input type="hidden">').attr('name', name).val('1').appendTo(deleteMarkerBin());
  }
  function updateEmptySection($group){
    var hasFields = $group.find('.tcarm-mini-field-card').length > 0;
    $group.find('> .tcarm-section-group-body .tcarm-empty-section').toggle(!hasFields);
  }
  $(function(){
    $(document).on('mousedown click', '.tcarm-section-summary-actions, .tcarm-edit-section, .tcarm-delete-section, .tcarm-delete-field, .tcarm-add-field, .tcarm-delete-consent, .tcarm-edit-consent', function(e){
      e.stopPropagation();
    });

    initSortables();
    toggleDropdownSettings($(document));
    updateConsentRequiredState($(document));
    $('.tcarm-form-settings-form').off('submit.tcarmFormOrder').on('submit.tcarmFormOrder', function(){
      renumberSections();
    });
    $(document).on('click', '.tcarm-add-section-main', function(e){
      e.preventDefault();
      var $panel = $('.tcarm-section-add-panel:not(.tcarm-consent-add-panel)');
      $(sectionHtml()).insertBefore($panel);
      initSortables();
      renumberSections();
    });
    $(document).on('click', '.tcarm-add-consent-main', function(e){
      e.preventDefault();
      var $list = $('.tcarm-sortable-consents');
      $list.append(consentHtml());
      initSortables();
      renumberConsents();
      updateConsentRequiredState($list.find('.tcarm-consent-group').last());
    });
    $(document).on('click', '.tcarm-add-field', function(e){
      e.preventDefault();
      var $group = $(this).closest('.tcarm-section-group');
      var sectionKey = $group.data('section');
      var $body = $group.find('> .tcarm-section-group-body');
      $body.find('.tcarm-empty-section').hide();
      $(fieldCardHtml(sectionKey)).insertBefore($(this));
      initSortables();
      renumberFields($group);
      toggleDropdownSettings($body.find('.tcarm-mini-field-card').last());
    });
    $(document).on('change', '.tcarm-mini-field-card select[name$="[type]"]', function(){
      toggleDropdownSettings($(this).closest('.tcarm-mini-field-card'));
    });
    $(document).on('change', '.tcarm-consent-show-checkbox-input', function(){
      updateConsentRequiredState($(this).closest('.tcarm-consent-group'));
    });
    $(document).on('click', '.tcarm-add-dropdown-choice', function(e){
      e.preventDefault();
      var $settings = $(this).closest('.tcarm-dropdown-settings');
      var $card = $(this).closest('.tcarm-mini-field-card');
      var nameAttr = $card.find('input[name$="[label]"]').attr('name') || '';
      var baseName = nameAttr.replace(/\[label\]$/, '');
      var index = nextDropdownChoiceIndex($settings);
      $settings.find('.tcarm-dropdown-choice-list').append(dropdownChoiceRow(baseName, index));
    });
    $(document).on('click', '.tcarm-remove-dropdown-choice', function(e){
      e.preventDefault();
      var $list = $(this).closest('.tcarm-dropdown-choice-list');
      if ($list.find('.tcarm-dropdown-choice-row').length <= 1) {
        $(this).closest('.tcarm-dropdown-choice-row').find('input').val('');
        return;
      }
      $(this).closest('.tcarm-dropdown-choice-row').remove();
    });
    $(document).on('click', '.tcarm-delete-field', function(e){
      e.preventDefault();
      if (!window.confirm(t('deleteFieldConfirm', 'Delete this field?'))) return;
      var $card = $(this).closest('.tcarm-mini-field-card');
      var $group = $card.closest('.tcarm-section-group');
      $card.find('.tcarm-delete-field-input').val('1').each(function(){ keepDeleteMarker($(this)); });
      $card.remove();
      updateEmptySection($group);
      renumberSections();
    });
    $(document).on('click', '.tcarm-delete-section', function(e){
      e.preventDefault();
      e.stopPropagation();
      var $group = $(this).closest('.tcarm-section-group');
      var hasFields = $group.find('.tcarm-mini-field-card').length > 0;
      var message = hasFields ? t('deleteSectionWithFieldsConfirm', 'Fields in this section will also be deleted. Continue?') : t('deleteSectionConfirm', 'Delete this section?');
      if (!window.confirm(message)) return;
      $group.find('.tcarm-delete-field-input').val('1').each(function(){ keepDeleteMarker($(this)); });
      $group.find('.tcarm-delete-section-input').val('1').each(function(){ keepDeleteMarker($(this)); });
      $group.remove();
      renumberSections();
    });
    $(document).on('click', '.tcarm-delete-consent', function(e){
      e.preventDefault();
      e.stopPropagation();
      if (!window.confirm(t('deleteConsentConfirm', 'Delete this consent item?'))) return;
      var $group = $(this).closest('.tcarm-consent-group');
      $group.find('.tcarm-delete-consent-input').val('1').each(function(){ keepDeleteMarker($(this)); });
      $group.remove();
      renumberConsents();
    });
    $(document).on('input', '.tcarm-consent-label-input', function(){
      var value = $.trim($(this).val()) || t('newConsent', 'New consent item');
      $(this).closest('.tcarm-consent-group').find('> summary .tcarm-consent-title-text').text(value);
    });
    $(document).on('click', '.tcarm-edit-section', function(e){
      e.preventDefault();
      e.stopPropagation();
      openSectionModal($(this).closest('.tcarm-section-group'));
    });
    $(document).on('click', '.tcarm-edit-consent', function(e){
      e.preventDefault();
      e.stopPropagation();
      openConsentModal($(this).closest('.tcarm-consent-group'));
    });
    $(document).on('click', '.tcarm-section-name-modal-close, .tcarm-section-name-modal-cancel, .tcarm-modal-backdrop', function(e){
      e.preventDefault();
      closeSectionModal();
    });
    $(document).on('click', '.tcarm-template-tab', function(e){
      e.preventDefault();
      var template = $(this).data('template');
      var $card = $(this).closest('.tcarm-mail-template-card');
      $card.find('.tcarm-template-tab').removeClass('is-active').attr('aria-selected', 'false');
      $(this).addClass('is-active').attr('aria-selected', 'true');
      $card.find('.tcarm-template-panel').removeClass('is-active');
      $card.find('.tcarm-template-panel[data-template-panel="'+template+'"]').addClass('is-active');
    });
    $(document).on('click', '.tcarm-display-tab', function(e){
      e.preventDefault();
      var panel = $(this).data('display-panel');
      var $card = $(this).closest('.tcarm-display-customize-card');
      $card.find('.tcarm-display-tab').removeClass('is-active').attr('aria-selected', 'false');
      $(this).addClass('is-active').attr('aria-selected', 'true');
      $card.find('.tcarm-display-panel').removeClass('is-active');
      $card.find('.tcarm-display-panel[data-display-panel="'+panel+'"]').addClass('is-active');
    });
    $(document).on('click', '.tcarm-lang-tab', function(e){
      e.preventDefault();
      var panel = $(this).data('lang-panel');
      var $card = $(this).closest('.tcarm-lang-page-settings-card');
      $card.find('.tcarm-lang-tab').removeClass('is-active').attr('aria-selected', 'false');
      $(this).addClass('is-active').attr('aria-selected', 'true');
      $card.find('.tcarm-lang-panel').removeClass('is-active');
      $card.find('.tcarm-lang-panel[data-lang-panel="'+panel+'"]').addClass('is-active');
    });

    $(document).on('click', '.tcarm-copy-var', function(e){
      e.preventDefault();
      var text = $(this).data('var') || '';
      var $note = $(this).closest('.tcarm-template-vars').find('.tcarm-copy-var-note');
      function done(){ $note.text(text + ' ' + t('copiedSuffix', 'was copied.')).addClass('is-copied'); }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(function(){ window.prompt(t('copyPrompt', 'Please copy this'), text); done(); });
      } else {
        window.prompt(t('copyPrompt', 'Please copy this'), text);
        done();
      }
    });
    $(document).on('click', '.tcarm-section-name-modal-save', function(e){
      e.preventDefault();
      var $modal = $('#tcarm-section-name-modal');
      var $group = $modal.data('target');
      var value = $.trim($modal.find('.tcarm-section-name-modal-input').val());
      if (!value) {
        window.alert(t('sectionNameRequired', 'Please enter a section name.'));
        return;
      }
      if ($group && $group.length) {
        if ($modal.data('target-type') === 'consent') {
          $group.find('> summary .tcarm-consent-label-input').val(value);
          $group.find('> summary .tcarm-consent-title-text').text(value);
        } else {
          $group.find('> summary .tcarm-section-label-input').val(value);
          $group.find('> summary .tcarm-section-title-text').text(value);
        }
      }
      closeSectionModal();
    });
    $(document).on('keydown', function(e){
      if (e.key === 'Escape') closeSectionModal();
    });
  });
})(jQuery);
