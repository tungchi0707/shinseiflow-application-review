(function($){
  function uniqueKey(prefix){
    return prefix + '_' + Date.now().toString(36) + '_' + Math.floor(Math.random() * 9999).toString(36);
  }
  function updateEmptyState(){
    var $list = $('[data-tcarm-category-color-list]');
    var hasCards = $list.find('[data-tcarm-category-color-card]').length > 0;
    $('[data-tcarm-category-color-empty]').toggleClass('is-hidden', hasCards);
  }
  $(document).on('click', '[data-tcarm-add-category-color]', function(){
    var tpl = $('#tcarm-category-color-template').html();
    if (!tpl) return;
    var categoryKey = uniqueKey('category');
    $('[data-tcarm-category-color-list]').append(tpl.replace(/__CATEGORY_KEY__/g, categoryKey).replace(/__COLOR_KEY__/g, uniqueKey('color')));
    updateEmptyState();
  });
  $(document).on('click', '[data-tcarm-remove-category-color]', function(){
    $(this).closest('[data-tcarm-category-color-card]').remove();
    updateEmptyState();
  });
  $(document).on('click', '[data-tcarm-add-color-rule]', function(){
    var tpl = $('#tcarm-color-rule-template').html();
    if (!tpl) return;
    var $card = $(this).closest('[data-tcarm-category-color-card]');
    var firstName = $card.find('input[name^="tcarm_category_color_rules["]').first().attr('name') || '';
    var match = firstName.match(/^tcarm_category_color_rules\[([^\]]+)\]/);
    if (!match) return;
    $card.find('[data-tcarm-color-rule-list]').append(tpl.replace(/__CATEGORY_KEY__/g, match[1]).replace(/__COLOR_KEY__/g, uniqueKey('color')));
  });
  $(document).on('click', '[data-tcarm-remove-color-rule]', function(){
    $(this).closest('[data-tcarm-color-rule-card]').remove();
  });
  $(updateEmptyState);
})(jQuery);