(function($){
  function updateApplicationNumberRuleRow($row){
    var type = $row.find('[data-tcarm-rule-type]').val();
    $row.find('[data-tcarm-rule-value]').toggle(type === 'fixed' || type === 'symbol');
    $row.find('[data-tcarm-rule-format]').toggle(type === 'date');
    $row.find('[data-tcarm-rule-length]').toggle(type === 'random_letters' || type === 'random_numbers' || type === 'sequence');
    if (type === 'symbol') {
      var $value = $row.find('[data-tcarm-rule-value]');
      if ($value.val() !== '_') {
        $value.val('-');
      }
    }
  }
  function buildApplicationNumberPreview(){
    var value = '';
    $('[data-tcarm-application-number-rule-row]:visible').each(function(){
      var $row = $(this);
      if ($row.find('[data-tcarm-rule-delete]').val() === '1') return;
      var type = $row.find('[data-tcarm-rule-type]').val();
      if (type === 'fixed') {
        value += ($row.find('[data-tcarm-rule-value]').val() || '').replace(/[^A-Za-z0-9_-]/g, '').slice(0, 16);
      } else if (type === 'symbol') {
        value += $row.find('[data-tcarm-rule-value]').val() === '_' ? '_' : '-';
      } else if (type === 'date') {
        var now = new Date();
        var year = String(now.getFullYear());
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var format = $row.find('[data-tcarm-rule-format]').val();
        value += format === 'Y' ? year : (format === 'Ym' ? year + month : year + month + day);
      } else if (type === 'random_letters') {
        value += 'A'.repeat(Math.max(1, Math.min(8, parseInt($row.find('[data-tcarm-rule-length]').val(), 10) || 2)));
      } else if (type === 'random_numbers') {
        value += '1'.repeat(Math.max(1, Math.min(8, parseInt($row.find('[data-tcarm-rule-length]').val(), 10) || 2)));
      } else if (type === 'sequence') {
        value += String(1).padStart(Math.max(1, Math.min(12, parseInt($row.find('[data-tcarm-rule-length]').val(), 10) || 6)), '0');
      }
    });
    $('[data-tcarm-application-number-preview]').text(value && value.length <= 32 ? value : 'APP-' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '-000001');
  }
  function refreshApplicationNumberRules(){
    $('[data-tcarm-application-number-rule-row]').each(function(){ updateApplicationNumberRuleRow($(this)); });
    buildApplicationNumberPreview();
  }
  $(document).on('change input', '[data-tcarm-application-number-rule-row] input, [data-tcarm-application-number-rule-row] select', refreshApplicationNumberRules);
  $(document).on('click', '[data-tcarm-add-application-number-rule]', function(){
    var tpl = $('#tcarm-application-number-rule-template').html();
    if (!tpl) return;
    var key = 'new_' + Date.now().toString(36) + '_' + Math.floor(Math.random()*9999).toString(36);
    $('[data-tcarm-application-number-rule-list]').append(tpl.replace(/__KEY__/g, key));
    refreshApplicationNumberRules();
  });
  $(document).on('click', '[data-tcarm-remove-application-number-rule]', function(){
    var $row = $(this).closest('[data-tcarm-application-number-rule-row]');
    $row.find('[data-tcarm-rule-delete]').val('1');
    $row.hide();
    refreshApplicationNumberRules();
  });
  $(function(){
    $('[data-tcarm-application-number-rule-list]').sortable({
      items: '[data-tcarm-application-number-rule-row]',
      stop: refreshApplicationNumberRules
    });
    refreshApplicationNumberRules();
  });
})(jQuery);