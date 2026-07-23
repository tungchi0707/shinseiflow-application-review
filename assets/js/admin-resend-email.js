(function(){
            function t(key, fallback){
                var i18n = window.tcarmAdminI18n || {};
                return typeof i18n[key] === 'string' && i18n[key] !== '' ? i18n[key] : fallback;
            }
            function initTcarmResendEmail(){
                var form = document.getElementById('tcarm-resend-email-form');
                var select = document.getElementById('tcarm-resend-email-type');
                var recipientText = document.getElementById('tcarm-resend-email-recipient');
                var openButton = document.getElementById('tcarm-open-resend-email-modal');
                var modal = document.getElementById('tcarm-resend-email-confirm-modal');
                var confirmType = document.getElementById('tcarm-resend-email-confirm-type');
                var confirmRecipient = document.getElementById('tcarm-resend-email-confirm-recipient');
                var submitButton = document.getElementById('tcarm-confirm-resend-email-submit');
                function selectedOption(){ return select && select.options.length ? select.options[select.selectedIndex] : null; }
                function updateRecipient(){
                    var option = selectedOption();
                    var recipient = option ? option.getAttribute('data-recipient-label') : t('dash', '—');
                    if(recipientText){ recipientText.textContent = t('recipientPrefix', 'Recipient: ') + recipient; }
                }
                function showModal(){ if(modal){ modal.style.display='block'; modal.setAttribute('aria-hidden','false'); } }
                function hideModal(){ if(modal){ modal.style.display='none'; modal.setAttribute('aria-hidden','true'); } }
                if(select){ select.addEventListener('change', updateRecipient); updateRecipient(); }
                if(openButton){
                    openButton.addEventListener('click', function(){
                        var option = selectedOption();
                        if(!option){ return; }
                        if(confirmType){ confirmType.textContent = option.getAttribute('data-mail-label') || option.textContent; }
                        if(confirmRecipient){ confirmRecipient.textContent = option.getAttribute('data-recipient-label') || t('dash', '—'); }
                        showModal();
                    });
                }
                if(submitButton && form){ submitButton.addEventListener('click', function(){ form.submit(); }); }
                document.querySelectorAll('[data-tcarm-modal-close="resend-email"]').forEach(function(el){ el.addEventListener('click', hideModal); });
            }
            if(document.readyState === 'loading'){
                document.addEventListener('DOMContentLoaded', initTcarmResendEmail);
            }else{
                initTcarmResendEmail();
            }
        })();
