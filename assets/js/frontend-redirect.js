(function(){
  var settings = window.tcarmFrontendRedirect || {};
  if (settings.url) {
    window.location.replace(settings.url);
  }
})();
