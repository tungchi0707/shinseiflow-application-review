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
  function ensureLightbox(){
    var $box = $('#tcarm-image-lightbox');
    if ($box.length) return $box;
    $box = $('<div class="tcarm-image-lightbox" id="tcarm-image-lightbox" aria-hidden="true" style="display:none;"><div class="tcarm-image-lightbox__dialog" role="dialog" aria-modal="true"><button type="button" class="button tcarm-image-lightbox__close" aria-label="'+escAttr(t('close', 'Close'))+'">&times;</button><img class="tcarm-image-lightbox__image" alt=""><div class="tcarm-image-lightbox__caption"></div></div></div>');
    $('body').append($box);
    return $box;
  }
  function openLightbox(url, caption){
    var $box = ensureLightbox();
    $box.find('.tcarm-image-lightbox__image').attr('src', url || '');
    $box.find('.tcarm-image-lightbox__caption').text(caption || '');
    $box.attr('aria-hidden', 'false').show();
  }
  function closeLightbox(){
    var $box = $('#tcarm-image-lightbox');
    if (!$box.length) return;
    $box.attr('aria-hidden', 'true').hide();
    $box.find('.tcarm-image-lightbox__image').attr('src', '');
  }
  $(document).on('click', '[data-tcarm-image-lightbox="1"]', function(e){
    var href = $(this).attr('href');
    if (!href) return;
    e.preventDefault();
    openLightbox(href, $(this).attr('data-tcarm-lightbox-caption') || $.trim($(this).text()));
  });
  $(document).on('click', '#tcarm-image-lightbox, #tcarm-image-lightbox .tcarm-image-lightbox__close', function(e){
    if ($(e.target).is('#tcarm-image-lightbox') || $(e.target).is('.tcarm-image-lightbox__close')) {
      e.preventDefault();
      closeLightbox();
    }
  });
  $(document).on('keydown', function(e){
    if (e.key === 'Escape') closeLightbox();
  });
})(jQuery);
