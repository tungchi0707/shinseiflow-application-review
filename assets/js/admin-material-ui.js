(function($){
  function pickIcon(title){
    var rules = [
      ['\u57fa\u672c\u8a2d\u5b9a','dashicons-admin-tools'],
      ['\u901a\u77e5','dashicons-email-alt2'],
      ['\u30e1\u30fc\u30eb','dashicons-email'],
      ['\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8','dashicons-media-text'],
      ['\u9001\u4fe1','dashicons-email-alt'],
      ['\u6642\u9593','dashicons-clock'],
      ['\u5be9\u67fb','dashicons-yes-alt'],
      ['\u7533\u8acb','dashicons-clipboard'],
      ['\u30d5\u30a9\u30fc\u30e0','dashicons-text-page'],
      ['\u4e00\u89a7','dashicons-list-view'],
      ['\u524a\u9664','dashicons-trash'],
      ['\u30bb\u30ad\u30e5\u30ea\u30c6\u30a3','dashicons-shield-alt'],
      ['Turnstile','dashicons-lock'],
      ['\u30a8\u30e9\u30fc','dashicons-warning'],
      ['\u30c0\u30a6\u30f3\u30ed\u30fc\u30c9','dashicons-download'],
      ['\u30e1\u30c3\u30bb\u30fc\u30b8','dashicons-feedback'],
      ['\u30d5\u30a1\u30a4\u30eb','dashicons-paperclip'],
      ['\u516c\u958b','dashicons-megaphone'],
      ['\u30ab\u30e9\u30fc','dashicons-art'],
      ['\u95a2\u9023','dashicons-admin-links']
    ];
    for (var i=0;i<rules.length;i++) {
      if (title.indexOf(rules[i][0]) !== -1) return rules[i][1];
    }
    return 'dashicons-admin-generic';
  }
  function normalizePanelHeaders(){
    $('.tcarm-admin-page .tcarm-panel-header').each(function(){
      var $header = $(this);
      if ($header.children('.tcarm-panel-title-block').length) return;
      var $title = $header.children('h2').first();
      if (!$title.length) return;
      var $desc = $header.children('p').first();
      var $block = $('<div class="tcarm-panel-title-block"></div>');
      $title.before($block);
      $block.append($title);
      if ($desc.length) {
        $block.append($desc);
      }
    });
  }
  function initMaterialInspiredHeaders(){
    normalizePanelHeaders();
    $('.tcarm-admin-page .tcarm-panel-header h2').each(function(){
      var $title = $(this);
      if ($title.data('tcarmMaterialIconReady')) return;
      $title.data('tcarmMaterialIconReady', 1);
      var text = $.trim($title.clone().children().remove().end().text());
      var icon = pickIcon(text);
      if ($title.children('.tcarm-card-title-icon').length) return;
      $title.prepend('<span class="tcarm-card-title-icon dashicons '+icon+'" aria-hidden="true"></span>');
    });
  }
  $(function(){ initMaterialInspiredHeaders(); });
})(jQuery);
