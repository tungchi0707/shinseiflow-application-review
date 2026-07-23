(function($){
  function t(key, fallback){
    var i18n = window.tcarmAdminI18n || {};
    return typeof i18n[key] === 'string' && i18n[key] !== '' ? i18n[key] : fallback;
  }
  function initPanelHeaderTooltips(){
    $('.tcarm-admin-page .tcarm-panel-header').each(function(){
      var $header = $(this);
      if ($header.data('tcarmTooltipReady')) return;
      var $title = $header.find('h2').first();
      if (!$title.length) return;

      var $description = $title.siblings('p').first();
      if (!$description.length) {
        $description = $header.children('p').first();
      }
      if (!$description.length) {
        $description = $header.find('> div > p').first();
      }
      if (!$description.length) return;

      var text = $.trim($description.text());
      if (!text) return;

      $header.data('tcarmTooltipReady', 1).addClass('tcarm-panel-header-has-tooltip');
      $header.closest('.tcarm-panel, .tcarm-card-panel, .tcarm-admin-card, .tcarm-admin-application-section').addClass('tcarm-panel-has-tooltip');
      $description.addClass('tcarm-panel-description-source').attr('aria-hidden', 'true');

      if ($title.find('.tcarm-card-info-button').length) return;
      var $button = $('<button type="button" class="tcarm-card-info-button"></button>')
        .attr('aria-label', t('showDescription', 'Show description'))
        .attr('data-tooltip', text)
        .attr('title', text)
        .append('<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>');
      $title.append($button);
    });
  }

  $(function(){
    initPanelHeaderTooltips();
  });

  $(document).on('click', '.tcarm-card-info-button', function(e){
    e.preventDefault();
    e.stopPropagation();
    var $button = $(this);
    $('.tcarm-card-info-button').not($button).removeClass('is-open');
    $button.toggleClass('is-open');
  });

  $(document).on('click', function(){
    $('.tcarm-card-info-button').removeClass('is-open');
  });

  $(document).on('keydown', function(e){
    if (e.key === 'Escape') {
      $('.tcarm-card-info-button').removeClass('is-open');
    }
  });
})(jQuery);
