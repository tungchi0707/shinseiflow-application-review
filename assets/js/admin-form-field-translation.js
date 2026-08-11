(function(){
                var tcarmFormFieldAiTranslate = window.tcarmFormFieldAiTranslate || {};
                var tcarmFormFieldAiI18n = tcarmFormFieldAiTranslate.i18n || window.tcarmAdminI18n || {};
                var tcarmFormFieldBaseLanguage = tcarmFormFieldAiTranslate.baseLanguage || 'en';
                var tcarmFormFieldTargetLanguages = Array.isArray(tcarmFormFieldAiTranslate.targetLanguages) ? tcarmFormFieldAiTranslate.targetLanguages : [];

                function t(key, fallback){
                    return typeof tcarmFormFieldAiI18n[key] === 'string' && tcarmFormFieldAiI18n[key] !== '' ? tcarmFormFieldAiI18n[key] : fallback;
                }

                function setTcarmFormFieldAiMessage(message, type){
                    var messageEl = document.getElementById('tcarm-form-field-ai-translate-message');
                    if (!messageEl) return;
                    messageEl.textContent = message || '';
                    messageEl.className = 'tcarm-ai-translate-message' + (type ? ' is-' + type : '');
                }

                function getTcarmActiveFormFieldLang(card){
                    var activeTab = card.querySelector('.tcarm-form-field-lang-tab.is-active');
                    return activeTab ? activeTab.getAttribute('data-tcarm-form-field-lang') : tcarmFormFieldBaseLanguage;
                }

                function updateTcarmFormFieldAiButton(card){
                    var action = document.getElementById('tcarm-form-field-ai-translate-action');
                    var button = document.getElementById('tcarm-form-field-ai-translate-button');
                    if (!action || !button) return;
                    var lang = getTcarmActiveFormFieldLang(card);
                    var show = tcarmFormFieldTargetLanguages.indexOf(lang) !== -1;
                    action.hidden = !show;
                    button.disabled = !show;
                    if (!show) {
                        setTcarmFormFieldAiMessage('', '');
                    }
                }

                function initTcarmFormFieldAiTranslate(card){
                    var button = document.getElementById('tcarm-form-field-ai-translate-button');
                    var spinner = document.getElementById('tcarm-form-field-ai-translate-spinner');
                    if (!button) return;
                    updateTcarmFormFieldAiButton(card);
                    button.addEventListener('click', function(){
                        var targetLang = getTcarmActiveFormFieldLang(card);
                        if (!targetLang || tcarmFormFieldTargetLanguages.indexOf(targetLang) === -1) {
                            setTcarmFormFieldAiMessage(t('aiSelectTargetLanguage', 'Please select the target language.'), 'error');
                            return;
                        }

                        var source = {};
                        var emptyTargetCount = 0;
                        card.querySelectorAll('[data-tcarm-field-language-input="1"][data-tcarm-field-language="' + targetLang + '"]').forEach(function(targetInput){
                            var key = targetInput.getAttribute('data-tcarm-field-translation-key');
                            if (!key || (targetInput.value || '').trim() !== '') {
                                return;
                            }
                            emptyTargetCount++;
                            var sourceInput = null;
                            card.querySelectorAll('[data-tcarm-field-language-input="1"][data-tcarm-field-language="' + tcarmFormFieldBaseLanguage + '"]').forEach(function(input){
                                if (!sourceInput && input.getAttribute('data-tcarm-field-translation-key') === key) {
                                    sourceInput = input;
                                }
                            });
                            var sourceValue = sourceInput ? (sourceInput.value || '').trim() : '';
                            if (sourceValue === '') {
                                return;
                            }
                            source[key] = sourceValue;
                        });

                        if (!emptyTargetCount) {
                            setTcarmFormFieldAiMessage(t('aiNoEmptyTargets', 'There are no empty fields to translate.'), 'error');
                            return;
                        }
                        if (Object.keys(source).length === 0) {
                            setTcarmFormFieldAiMessage(t('aiSourceEmpty', 'The source language fields are empty. Enter content in the base language before translating.'), 'error');
                            return;
                        }

                        button.disabled = true;
                        if (spinner) spinner.classList.add('is-active');
                        setTcarmFormFieldAiMessage(t('aiTranslating', 'Translating...'), 'loading');

                        var params = new URLSearchParams();
                        params.append('action', 'tcarm_ai_translate_strings');
                        params.append('nonce', tcarmFormFieldAiTranslate.nonce);
                        params.append('target_lang', targetLang);
                        Object.keys(source).forEach(function(key){
                            params.append('source[' + key + ']', source[key]);
                        });

                        fetch(tcarmFormFieldAiTranslate.ajaxUrl, {
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
                                card.querySelectorAll('[data-tcarm-field-language-input="1"][data-tcarm-field-language="' + targetLang + '"]').forEach(function(input){
                                    if (input.getAttribute('data-tcarm-field-translation-key') === key && (input.value || '').trim() === '') {
                                        input.value = translations[targetLang][key] || '';
                                        input.dispatchEvent(new Event('change', {bubbles:true}));
                                        filled++;
                                    }
                                });
                            });
                            if (filled > 0) {
                                setTcarmFormFieldAiMessage(t('aiFilledCurrentLanguage', 'Translations were inserted into empty fields in the current language tab. Please review before saving.'), 'success');
                            } else {
                                setTcarmFormFieldAiMessage(t('aiNoFillableTargets', 'There were no empty fields available for translation.'), 'error');
                            }
                        }).catch(function(error){
                            setTcarmFormFieldAiMessage(error.message || t('aiFailed', 'AI translation failed.'), 'error');
                        }).finally(function(){
                            button.disabled = false;
                            updateTcarmFormFieldAiButton(card);
                            if (spinner) spinner.classList.remove('is-active');
                        });
                    });
                }

                var cards = document.querySelectorAll('.tcarm-form-settings-form');
                cards.forEach(function(card){
                    initTcarmFormFieldAiTranslate(card);
                    card.addEventListener('click', function(event){
                        var tab = event.target.closest('.tcarm-form-field-lang-tab');
                        if (!tab) { return; }
                        event.preventDefault();
                        var lang = tab.getAttribute('data-tcarm-form-field-lang');
                        card.querySelectorAll('.tcarm-form-field-lang-tab').forEach(function(item){
                            var active = item === tab;
                            item.classList.toggle('is-active', active);
                            item.setAttribute('aria-selected', active ? 'true' : 'false');
                        });
                        card.querySelectorAll('.tcarm-form-field-lang-panel').forEach(function(panel){
                            panel.classList.toggle('is-active', panel.getAttribute('data-tcarm-form-field-panel') === lang);
                        });
                        updateTcarmFormFieldAiButton(card);
                    });
                });

                function updateTcarmConsentRequiredState(group){
                    var showCheckbox = group.querySelector('.tcarm-consent-show-checkbox-input');
                    var required = group.querySelector('.tcarm-consent-required-input');
                    if (!showCheckbox || !required) return;
                    if (!showCheckbox.checked) {
                        required.checked = false;
                        required.disabled = true;
                    } else {
                        required.disabled = false;
                    }
                    var requiredLabel = required.closest('.tcarm-consent-required');
                    if (requiredLabel) {
                        requiredLabel.classList.toggle('is-disabled', required.disabled);
                    }
                }

                document.querySelectorAll('.tcarm-consent-group').forEach(function(group){
                    updateTcarmConsentRequiredState(group);
                    var showCheckbox = group.querySelector('.tcarm-consent-show-checkbox-input');
                    if (showCheckbox) {
                        showCheckbox.addEventListener('change', function(){
                            updateTcarmConsentRequiredState(group);
                        });
                    }
                });
            })();
