(function(){
  var i18n = window.tcarmFrontendValidationI18n || {};
  function t(key, fallback){ return typeof i18n[key] === 'string' && i18n[key] !== '' ? i18n[key] : fallback; }
  function ready(fn){ if(document.readyState !== 'loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
  function hasValue(el){
    if (!el) return false;
    if (el.getAttribute('data-tcarm-validate') === 'checkbox_group') return checkboxGroup(el).some(function(item){ return item.checked; });
    if (el.type === 'checkbox') return el.checked;
    if (el.type === 'radio') return radioGroup(el).some(function(item){ return item.checked; });
    return String(el.value || '').trim() !== '';
  }
  function radioGroup(el){
    if (!el || !el.form || !el.name) return el ? [el] : [];
    return Array.prototype.filter.call(el.form.querySelectorAll('input[type="radio"]'), function(item){
      return item.name === el.name;
    });
  }
  function checkboxGroup(el){
    if (!el || !el.form || !el.name) return el ? [el] : [];
    return Array.prototype.filter.call(el.form.querySelectorAll('input[type="checkbox"]'), function(item){
      return item.name === el.name && item.getAttribute('data-tcarm-validate') === 'checkbox_group';
    });
  }
  function isValidEmail(value){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value); }
  function isValidUrl(value){
    try { var u = new URL(value); return u.protocol === 'http:' || u.protocol === 'https:'; } catch(e) { return false; }
  }
  function isValidTel(value){ return /^[0-9+()\-\s]+$/.test(value) && /[0-9]/.test(value); }
  function messageFor(el){
    var type = el.getAttribute('data-tcarm-validate') || el.type || 'text';
    if ((el.required || el.getAttribute('data-tcarm-required') === '1') && !hasValue(el)) {
      if (type === 'checkbox_group') return t('requiredCheckboxGroup', 'Please select at least one option.');
      if (type === 'radio') return t('requiredRadioGroup', 'Please select an option.');
      if (el.type === 'checkbox') return t('requiredCheckbox', 'Please select this checkbox.');
      return t('requiredField', 'Please complete this required field.');
    }
    var value = String(el.value || '').trim();
    if (!value) return '';
    if (type === 'email' && !isValidEmail(value)) return t('invalidEmail', 'Please enter a valid email address.');
    if (type === 'url' && !isValidUrl(value)) return t('invalidUrl', 'Please enter a valid URL.');
    if (type === 'tel' && !isValidTel(value)) return t('invalidPhone', 'Please enter a valid phone number.');
    return '';
  }
  function setError(el, msg){
    var field = el.closest('.tcarm-field') || el.parentNode;
    if (!field) return;
    var box = field.querySelector('.tcarm-field-error');
    if (!box) {
      box = document.createElement('div');
      box.className = 'tcarm-field-error';
      field.appendChild(box);
    }
    box.textContent = msg;
    field.classList.toggle('has-error', !!msg);
    var controls = el.type === 'radio' ? radioGroup(el) : (el.getAttribute('data-tcarm-validate') === 'checkbox_group' ? checkboxGroup(el) : [el]);
    controls.forEach(function(control){ control.setAttribute('aria-invalid', msg ? 'true' : 'false'); });
  }
  function validate(el){ var msg = messageFor(el); setError(el, msg); return !msg; }
  ready(function(){
    document.querySelectorAll('.tcarm-form').forEach(function(form){
      var candidates = form.querySelectorAll('[data-tcarm-validate], input[required], textarea[required], select[required]');
      var fields = [];
      var radioNames = Object.create(null);
      var checkboxGroupNames = Object.create(null);
      candidates.forEach(function(el){
        if (el.type === 'radio') {
          if (radioNames[el.name]) return;
          radioNames[el.name] = true;
        }
        if (el.getAttribute('data-tcarm-validate') === 'checkbox_group') {
          if (checkboxGroupNames[el.name]) return;
          checkboxGroupNames[el.name] = true;
        }
        fields.push(el);
      });
      fields.forEach(function(el){
        var controls = el.type === 'radio' ? radioGroup(el) : (el.getAttribute('data-tcarm-validate') === 'checkbox_group' ? checkboxGroup(el) : [el]);
        controls.forEach(function(control){
          ['input','change','blur'].forEach(function(ev){ control.addEventListener(ev, function(){ validate(el); }); });
        });
      });
      form.addEventListener('submit', function(e){
        var ok = true, first = null;
        fields.forEach(function(el){ if (!validate(el)) { ok = false; if (!first) first = el; } });
        if (!ok) {
          e.preventDefault();
          if (first) first.focus({preventScroll:false});
        }
      });
    });
  });
})();
