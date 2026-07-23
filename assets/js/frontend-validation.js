(function(){
  function ready(fn){ if(document.readyState !== 'loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
  function hasValue(el){
    if (!el) return false;
    if (el.type === 'checkbox') return el.checked;
    return String(el.value || '').trim() !== '';
  }
  function isValidEmail(value){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value); }
  function isValidUrl(value){
    try { var u = new URL(value); return u.protocol === 'http:' || u.protocol === 'https:'; } catch(e) { return false; }
  }
  function isValidTel(value){ return /^[0-9+()\-\s]+$/.test(value) && /[0-9]/.test(value); }
  function messageFor(el){
    var type = el.getAttribute('data-tcarm-validate') || el.type || 'text';
    if (el.required && !hasValue(el)) {
      if (el.type === 'checkbox') return '\u540c\u610f\u304c\u5fc5\u8981\u3067\u3059\u3002';
      return '\u5fc5\u9808\u9805\u76ee\u3092\u5165\u529b\u3057\u3066\u304f\u3060\u3055\u3044\u3002';
    }
    var value = String(el.value || '').trim();
    if (!value) return '';
    if (type === 'email' && !isValidEmail(value)) return '\u30e1\u30fc\u30eb\u30a2\u30c9\u30ec\u30b9\u306e\u5f62\u5f0f\u3067\u5165\u529b\u3057\u3066\u304f\u3060\u3055\u3044\u3002';
    if (type === 'url' && !isValidUrl(value)) return 'URL\u306e\u5f62\u5f0f\u3067\u5165\u529b\u3057\u3066\u304f\u3060\u3055\u3044\u3002';
    if (type === 'tel' && !isValidTel(value)) return '\u96fb\u8a71\u756a\u53f7\u306f\u534a\u89d2\u6570\u5b57\u3001\u8a18\u53f7\u3001\u30b9\u30da\u30fc\u30b9\u3067\u5165\u529b\u3057\u3066\u304f\u3060\u3055\u3044\u3002';
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
    el.setAttribute('aria-invalid', msg ? 'true' : 'false');
  }
  function validate(el){ var msg = messageFor(el); setError(el, msg); return !msg; }
  ready(function(){
    document.querySelectorAll('.tcarm-form').forEach(function(form){
      var fields = form.querySelectorAll('[data-tcarm-validate], input[required], textarea[required], select[required]');
      fields.forEach(function(el){
        ['input','change','blur'].forEach(function(ev){ el.addEventListener(ev, function(){ validate(el); }); });
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
