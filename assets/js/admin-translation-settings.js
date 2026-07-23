(function(){
            var tcarmAiTranslate = window.tcarmAiTranslate || {};
            var tcarmAiI18n = tcarmAiTranslate.i18n || window.tcarmAdminI18n || {};

            function t(key, fallback){
                return typeof tcarmAiI18n[key] === 'string' && tcarmAiI18n[key] !== '' ? tcarmAiI18n[key] : fallback;
            }

            function setTcarmAiMessage(message, type){
                var messageEl = document.getElementById('tcarm-ai-translate-message');
                if (!messageEl) return;
                messageEl.textContent = message || '';
                messageEl.className = 'tcarm-ai-translate-message' + (type ? ' is-' + type : '');
            }

            function getTcarmActiveTranslationLang(){
                var activeTab = document.querySelector('.tcarm-translation-lang-tab.is-active');
                return activeTab ? activeTab.getAttribute('data-tcarm-translation-lang') : 'ja';
            }

            function updateTcarmAiTranslateButton(){
                var action = document.getElementById('tcarm-ai-translate-action');
                var button = document.getElementById('tcarm-ai-translate-button');
                if (!action || !button) return;
                var lang = getTcarmActiveTranslationLang();
                var hasTargets = !!tcarmAiTranslate.hasTargets;
                var show = hasTargets && lang && lang !== 'ja';
                action.hidden = !show;
                button.disabled = !show;
                if (!show) {
                    setTcarmAiMessage('', '');
                }
            }

            function initTcarmAiTranslate(){
                var button = document.getElementById('tcarm-ai-translate-button');
                var spinner = document.getElementById('tcarm-ai-translate-spinner');
                if (!button) return;
                updateTcarmAiTranslateButton();
                button.addEventListener('click', function(){
                    var targetLang = getTcarmActiveTranslationLang();
                    if (!targetLang || targetLang === 'ja') {
                        setTcarmAiMessage(t('aiSelectTargetLanguage', 'Please select the target language.'), 'error');
                        return;
                    }

                    var source = {};
                    document.querySelectorAll('[data-tcarm-translation-input="1"][data-tcarm-translation-lang="' + targetLang + '"]').forEach(function(targetInput){
                        var key = targetInput.getAttribute('data-tcarm-translation-key');
                        if (!key || (targetInput.value || '').trim() !== '') {
                            return;
                        }
                        var sourceInput = null;
                        document.querySelectorAll('[data-tcarm-translation-input="1"][data-tcarm-translation-lang="ja"]').forEach(function(input){
                            if (!sourceInput && input.getAttribute('data-tcarm-translation-key') === key) {
                                sourceInput = input;
                            }
                        });
                        var sourceValue = sourceInput ? (sourceInput.value || '').trim() : (targetInput.getAttribute('data-tcarm-translation-source') || '').trim();
                        if (sourceValue !== '') {
                            source[key] = sourceValue;
                        }
                    });

                    if (!Object.keys(source).length) {
                        setTcarmAiMessage(t('aiNoEmptyTargets', 'There are no empty fields to translate.'), 'error');
                        return;
                    }
                    button.disabled = true;
                    if (spinner) spinner.classList.add('is-active');
                    setTcarmAiMessage(t('aiTranslating', 'Translating...'), 'loading');

                    var params = new URLSearchParams();
                    params.append('action', 'tcarm_ai_translate_strings');
                    params.append('nonce', tcarmAiTranslate.nonce);
                    params.append('target_lang', targetLang);
                    Object.keys(source).forEach(function(key){
                        params.append('source[' + key + ']', source[key]);
                    });

                    fetch(tcarmAiTranslate.ajaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                        body: params.toString()
                    }).then(function(response){
                        return response.json();
                    }).then(function(json){
                        if (!json || !json.success) {
                            throw new Error(json && json.data && json.data.message ? json.data.message : t('aiFailed', 'AI translation failed.'));
                        }
                        var translations = json.data.translations || {};
                        var filled = 0;
                        Object.keys(translations[targetLang] || {}).forEach(function(key){
                            document.querySelectorAll('[data-tcarm-translation-input="1"]').forEach(function(input){
                                if (input.getAttribute('data-tcarm-translation-lang') === targetLang && input.getAttribute('data-tcarm-translation-key') === key && (input.value || '').trim() === '') {
                                    input.value = translations[targetLang][key] || '';
                                    input.dispatchEvent(new Event('change', {bubbles:true}));
                                    filled++;
                                }
                            });
                        });
                        if (filled > 0) {
                            setTcarmAiMessage(t('aiFilledCurrentLanguage', 'Translations were inserted into empty fields in the current language tab. Please review before saving.'), 'success');
                        } else {
                            setTcarmAiMessage(t('aiNoFillableTargets', 'There were no empty fields available for translation.'), 'error');
                        }
                    }).catch(function(error){
                        setTcarmAiMessage(error.message || t('aiFailed', 'AI translation failed.'), 'error');
                    }).finally(function(){
                        button.disabled = false;
                        updateTcarmAiTranslateButton();
                        if (spinner) spinner.classList.remove('is-active');
                    });
                });
            }

            function initTcarmTranslationTabs(){
                var cards = document.querySelectorAll('.tcarm-translation-settings-card');
                cards.forEach(function(card){
                    card.addEventListener('click', function(event){
                        var tab = event.target.closest('.tcarm-translation-lang-tab');
                        if (!tab || !card.contains(tab)) return;
                        event.preventDefault();
                        var lang = tab.getAttribute('data-tcarm-translation-lang');
                        if (!lang) return;
                        card.querySelectorAll('.tcarm-translation-lang-tab').forEach(function(item){
                            item.classList.remove('is-active');
                            item.setAttribute('aria-selected', 'false');
                        });
                        tab.classList.add('is-active');
                        tab.setAttribute('aria-selected', 'true');
                        card.querySelectorAll('.tcarm-translation-lang-panel').forEach(function(panel){
                            panel.classList.toggle('is-active', panel.getAttribute('data-tcarm-translation-panel') === lang);
                        });
                        updateTcarmAiTranslateButton();
                    });
                });
            }
            function initTcarmTranslationPage(){
                initTcarmTranslationTabs();
                initTcarmAiTranslate();
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initTcarmTranslationPage);
            } else {
                initTcarmTranslationPage();
            }
        })();
