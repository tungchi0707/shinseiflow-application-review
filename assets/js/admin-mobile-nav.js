(function(){
  function t(key, fallback){
    var i18n = window.tcarmAdminI18n || {};
    return typeof i18n[key] === 'string' && i18n[key] !== '' ? i18n[key] : fallback;
  }
  function initMobileNav(){
    var nav = document.querySelector('.tcarm-admin-page .tcarm-nav-tabs');
    if (!nav || nav.classList.contains('is-mobile-nav-ready')) {
      return;
    }
    var active = nav.querySelector('.nav-tab-active') || nav.querySelector('[aria-current="page"]') || nav.querySelector('.nav-tab');
    if (!active) {
      return;
    }
    if (!nav.id) {
      nav.id = 'tcarm-admin-mobile-nav';
    }
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'button tcarm-mobile-nav-toggle';
    button.setAttribute('aria-controls', nav.id);
    button.setAttribute('aria-expanded', 'false');
    button.textContent = (active.textContent || '').trim() || t('menu', 'Menu');
    nav.parentNode.insertBefore(button, nav);
    nav.classList.add('is-mobile-nav-ready', 'is-collapsed');
    button.addEventListener('click', function(){
      var isOpen = nav.classList.toggle('is-open');
      nav.classList.toggle('is-collapsed', !isOpen);
      button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileNav);
  } else {
    initMobileNav();
  }
})();
