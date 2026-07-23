(function(){
            function t(key, fallback){
                var i18n = window.tcarmAdminI18n || {};
                return typeof i18n[key] === 'string' && i18n[key] !== '' ? i18n[key] : fallback;
            }
            function initTcarmApplicationListActions(){
            var deleteModal = document.getElementById('tcarm-delete-confirm-modal');
            var openDelete = document.getElementById('tcarm-open-delete-modal');
            var confirmDelete = document.getElementById('tcarm-confirm-delete-submit');
            var bulkForm = document.getElementById('tcarm-bulk-delete-form');
            var selectAll = document.getElementById('tcarm-select-all-applications');
            var restoreModal = document.getElementById('tcarm-restore-confirm-modal');
            var restoreTargetFormId = '';
            var permanentDeleteModal = document.getElementById('tcarm-permanent-delete-confirm-modal');
            var permanentDeleteTargetFormId = '';
            var bulkPermanentDeleteModal = document.getElementById('tcarm-bulk-permanent-delete-confirm-modal');
            var bulkPermanentDeleteForm = document.getElementById('tcarm-bulk-permanent-delete-form');
            var selectAllDeleted = document.getElementById('tcarm-select-all-deleted-applications');
            var openBulkPermanentDelete = document.getElementById('tcarm-open-bulk-permanent-delete-modal');
            var confirmBulkPermanentDelete = document.getElementById('tcarm-confirm-bulk-permanent-delete-submit');
            function checkedApplicationCount(){
                return document.querySelectorAll('.tcarm-application-checkbox:checked').length;
            }
            function checkedDeletedApplicationCount(){
                return document.querySelectorAll('.tcarm-deleted-application-checkbox:checked').length;
            }
            function showModal(modal){ if(modal){ modal.style.display = 'block'; modal.setAttribute('aria-hidden','false'); } }
            function hideModal(modal){ if(modal){ modal.style.display = 'none'; modal.setAttribute('aria-hidden','true'); } }
            if(selectAll){
                selectAll.addEventListener('change', function(){
                    document.querySelectorAll('.tcarm-application-checkbox').forEach(function(cb){ cb.checked = selectAll.checked; });
                });
            }
            if(openDelete){
                openDelete.addEventListener('click', function(){
                    if(!checkedApplicationCount()){
                        window.alert(t('applicationDeleteNone', 'Please select applications to delete.'));
                        return;
                    }
                    showModal(deleteModal);
                });
            }
            if(confirmDelete && bulkForm){
                confirmDelete.addEventListener('click', function(){ bulkForm.submit(); });
            }
            document.querySelectorAll('[data-tcarm-modal-close="delete"]').forEach(function(el){ el.addEventListener('click', function(){ hideModal(deleteModal); }); });
            document.querySelectorAll('.tcarm-restore-application-button').forEach(function(button){
                button.addEventListener('click', function(){
                    restoreTargetFormId = button.getAttribute('data-form-id') || '';
                    showModal(restoreModal);
                });
            });
            var confirmRestore = document.getElementById('tcarm-confirm-restore-submit');
            if(confirmRestore){
                confirmRestore.addEventListener('click', function(){
                    var form = restoreTargetFormId ? document.getElementById(restoreTargetFormId) : null;
                    if(form){ form.submit(); }
                });
            }
            document.querySelectorAll('[data-tcarm-modal-close="restore"]').forEach(function(el){ el.addEventListener('click', function(){ hideModal(restoreModal); }); });
            document.querySelectorAll('.tcarm-permanent-delete-application-button').forEach(function(button){
                button.addEventListener('click', function(){
                    permanentDeleteTargetFormId = button.getAttribute('data-form-id') || '';
                    showModal(permanentDeleteModal);
                });
            });
            var confirmPermanentDelete = document.getElementById('tcarm-confirm-permanent-delete-submit');
            if(confirmPermanentDelete){
                confirmPermanentDelete.addEventListener('click', function(){
                    var form = permanentDeleteTargetFormId ? document.getElementById(permanentDeleteTargetFormId) : null;
                    if(form){ form.submit(); }
                });
            }
            document.querySelectorAll('[data-tcarm-modal-close="permanent-delete"]').forEach(function(el){ el.addEventListener('click', function(){ hideModal(permanentDeleteModal); }); });
            if(selectAllDeleted){
                selectAllDeleted.addEventListener('change', function(){
                    document.querySelectorAll('.tcarm-deleted-application-checkbox').forEach(function(cb){ cb.checked = selectAllDeleted.checked; });
                });
            }
            if(openBulkPermanentDelete){
                openBulkPermanentDelete.addEventListener('click', function(){
                    if(!checkedDeletedApplicationCount()){
                        window.alert(t('bulkPermanentDeleteNone', 'Please select applications to permanently delete.'));
                        return;
                    }
                    showModal(bulkPermanentDeleteModal);
                });
            }
            if(confirmBulkPermanentDelete && bulkPermanentDeleteForm){
                confirmBulkPermanentDelete.addEventListener('click', function(){ bulkPermanentDeleteForm.submit(); });
            }
            document.querySelectorAll('[data-tcarm-modal-close="bulk-permanent-delete"]').forEach(function(el){ el.addEventListener('click', function(){ hideModal(bulkPermanentDeleteModal); }); });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initTcarmApplicationListActions);
            } else {
                initTcarmApplicationListActions();
            }
        })();
