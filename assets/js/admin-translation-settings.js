(function(){
            var tcarmAiTranslate = window.tcarmAiTranslate || {};
            var tcarmAiI18n = tcarmAiTranslate.i18n || window.tcarmAdminI18n || {};
            var tcarmBaseLanguage = tcarmAiTranslate.baseLanguage || 'en';
            var tcarmTargetLanguages = Array.isArray(tcarmAiTranslate.targetLanguages) ? tcarmAiTranslate.targetLanguages : [];

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
                return activeTab ? activeTab.getAttribute('data-tcarm-translation-lang') : tcarmBaseLanguage;
            }

            function isTcarmBaseLanguageUnsaved(){
                var select = document.getElementById('tcarm-base-language-select');
                return !!select && select.value !== tcarmBaseLanguage;
            }

            function syncTcarmBaseLanguageControls(){
                var select = document.getElementById('tcarm-base-language-select');
                if (!select) return;
                var hiddenEnabled = document.getElementById('tcarm-base-language-enabled');
                if (hiddenEnabled) hiddenEnabled.value = select.value;
                document.querySelectorAll('[data-tcarm-enabled-language]').forEach(function(checkbox){
                    var isSelectedBase = checkbox.getAttribute('data-tcarm-enabled-language') === select.value;
                    checkbox.disabled = isSelectedBase;
                    if (isSelectedBase) checkbox.checked = true;
                });
            }

            function updateTcarmAiTranslateButton(){
                var action = document.getElementById('tcarm-ai-translate-action');
                var button = document.getElementById('tcarm-ai-translate-button');
                if (!action || !button) return;
                var lang = getTcarmActiveTranslationLang();
                var show = tcarmTargetLanguages.indexOf(lang) !== -1;
                action.hidden = !show;
                button.disabled = !show || isTcarmBaseLanguageUnsaved();
                if (isTcarmBaseLanguageUnsaved()) {
                    setTcarmAiMessage(t('aiBaseLanguageUnsaved', 'Save the base language setting before using AI translation.'), 'error');
                } else if (!show) {
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
                    if (isTcarmBaseLanguageUnsaved()) {
                        setTcarmAiMessage(t('aiBaseLanguageUnsaved', 'Save the base language setting before using AI translation.'), 'error');
                        return;
                    }
                    if (!targetLang || tcarmTargetLanguages.indexOf(targetLang) === -1) {
                        setTcarmAiMessage(t('aiSelectTargetLanguage', 'Please select the target language.'), 'error');
                        return;
                    }

                    var source = {};
                    var emptyTargetCount = 0;
                    var emptySourceCount = 0;
                    document.querySelectorAll('[data-tcarm-translation-input="1"][data-tcarm-translation-lang="' + targetLang + '"]').forEach(function(targetInput){
                        var key = targetInput.getAttribute('data-tcarm-translation-key');
                        if (!key || (targetInput.value || '').trim() !== '') {
                            return;
                        }
                        emptyTargetCount++;
                        var sourceInput = null;
                        document.querySelectorAll('[data-tcarm-translation-input="1"][data-tcarm-translation-lang="' + tcarmBaseLanguage + '"]').forEach(function(input){
                            if (!sourceInput && input.getAttribute('data-tcarm-translation-key') === key) {
                                sourceInput = input;
                            }
                        });
                        var sourceValue = sourceInput ? (sourceInput.value || '').trim() : '';
                        if (sourceValue === '') {
                            emptySourceCount++;
                            return;
                        }
                        source[key] = sourceValue;
                    });

                    if (!emptyTargetCount) {
                        setTcarmAiMessage(t('aiNoEmptyTargets', 'There are no empty fields to translate.'), 'error');
                        return;
                    }
                    if (emptySourceCount > 0) {
                        setTcarmAiMessage(t('aiSourceEmpty', 'The source language fields are empty. Enter content in the base language before translating.'), 'error');
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
            function initTcarmBaseLanguageControls(){
                var select = document.getElementById('tcarm-base-language-select');
                if (!select) return;
                syncTcarmBaseLanguageControls();
                select.addEventListener('change', function(){
                    syncTcarmBaseLanguageControls();
                    updateTcarmAiTranslateButton();
                });
            }
            function initTcarmTranslationPage(){
                initTcarmBaseLanguageControls();
                initTcarmTranslationTabs();
                initTcarmAiTranslate();
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initTcarmTranslationPage);
            } else {
                initTcarmTranslationPage();
            }
        })();
