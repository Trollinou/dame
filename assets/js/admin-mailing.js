/**
 * Admin Mailing Interactivity.
 * 
 * Logic for toggling filters, searchable lists, and automatic contact pre-checking.
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Toggling Adherent Methods
    const adMethodRadios = document.querySelectorAll('input[name="dame_adherent_method"]');
    const adGroupWrap = document.querySelector('.dame-adherent-group-wrap');
    const adManualWrap = document.querySelector('.dame-adherent-manual-wrap');

    if (adMethodRadios.length > 0 && adGroupWrap && adManualWrap) {
        adMethodRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'group') {
                    adGroupWrap.classList.remove('dame-hidden');
                    adManualWrap.classList.add('dame-hidden');
                } else {
                    adGroupWrap.classList.add('dame-hidden');
                    adManualWrap.classList.remove('dame-hidden');
                }
            });
        });
    }

    // 2. Toggling Contact Methods with "Magic" Pre-checking
    const contactMethodRadios = document.querySelectorAll('input[name="dame_contact_method"]');
    const contactGroupWrap = document.querySelector('.dame-contact-group-wrap');
    const contactManualWrap = document.querySelector('.dame-contact-manual-wrap');

    if (contactMethodRadios.length > 0 && contactGroupWrap && contactManualWrap) {
        let currentContactMethod = document.querySelector('input[name="dame_contact_method"]:checked').value;

        contactMethodRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                const newMethod = this.value;

                // Confirmation lors du retour au mode Critères depuis Manuel
                if (currentContactMethod === 'manual' && newMethod === 'group') {
                    if (confirm("Souhaitez-vous vraiment revenir à la sélection par critères ? Vos filtres actuels et votre sélection manuelle seront réinitialisés.")) {
                        resetContactCriteria();
                    } else {
                        // Annulation : on restaure le bouton radio Manuel
                        document.querySelector('input[name="dame_contact_method"][value="manual"]').checked = true;
                        return;
                    }
                }

                currentContactMethod = newMethod;

                if (newMethod === 'group') {
                    contactGroupWrap.classList.remove('dame-hidden');
                    contactManualWrap.classList.add('dame-hidden');
                    
                    // Reset manual selection when going back to Criteria
                    contactManualWrap.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                    const manualList = contactManualWrap.querySelector('.dame-checkbox-list');
                    if (manualList) {
                        reorderList(manualList);
                        updateSelectionCount(manualList);
                    }
                } else {
                    contactGroupWrap.classList.add('dame-hidden');
                    contactManualWrap.classList.remove('dame-hidden');

                    // MAGIC: Pre-check based on criteria
                    performContactPrecheck();
                }
            });
        });
    }

    // Listen for changes to checkboxes to trigger reordering and count update
    document.addEventListener('change', function(e) {
        if (e.target.matches('.dame-checkbox-list input[type="checkbox"]')) {
            const listContainer = e.target.closest('.dame-checkbox-list');
            if (listContainer) {
                reorderList(listContainer);
                updateSelectionCount(listContainer);
            }
        }

        // Logic for syncing Region -> Departments
        if (e.target.matches('.dame-region-criteria-list input[type="checkbox"]')) {
            const regionCode = e.target.value;
            const isChecked = e.target.checked;
            const depts = (typeof dameMailingData !== 'undefined' && dameMailingData.regionMapping) ? dameMailingData.regionMapping[regionCode] : [];
            
            const deptList = document.querySelector('.dame-dept-criteria-list .dame-checkbox-list');
            if (!deptList || depts.length === 0) return;

            depts.forEach(code => {
                const deptCheckbox = deptList.querySelector(`input[value="${code}"]`);
                if (deptCheckbox) {
                    deptCheckbox.checked = isChecked;
                }
            });

            // Trigger reorder and count update for departments list
            reorderList(deptList);
            updateSelectionCount(deptList);
        }
    });

    /**
     * Updates the selection counter for a list.
     * @param {HTMLElement} listContainer The .dame-checkbox-list element.
     */
    function updateSelectionCount(listContainer) {
        const wrapper = listContainer.closest('.dame-searchable-list-wrapper');
        if (!wrapper) return;
        const countSpan = wrapper.querySelector('.dame-selection-count');
        if (countSpan) {
            const checkedCount = listContainer.querySelectorAll('input[type="checkbox"]:checked').length;
            countSpan.textContent = checkedCount;
        }
    }

    /**
     * Reorders a list to move checked items to the top.
     * @param {HTMLElement} listContainer The .dame-checkbox-list element.
     */
    function reorderList(listContainer) {
        const labels = Array.from(listContainer.querySelectorAll('label'));
        
        // Sort labels: checked first, then alphabetical
        labels.sort((a, b) => {
            const aChecked = a.querySelector('input').checked;
            const bChecked = b.querySelector('input').checked;
            
            if (aChecked && !bChecked) return -1;
            if (!aChecked && bChecked) return 1;
            
            return a.textContent.trim().localeCompare(b.textContent.trim());
        });

        // Append sorted elements back to container
        labels.forEach(label => listContainer.appendChild(label));
    }

    /**
     * Réinitialise tous les filtres de critères pour les contacts.
     */
    function resetContactCriteria() {
        const typesSelect = document.getElementById('dame_contact_types_select');
        if (typesSelect) {
            Array.from(typesSelect.options).forEach(opt => opt.selected = false);
        }
        const criteriaLists = document.querySelectorAll('.dame-dept-criteria-list, .dame-region-criteria-list');
        criteriaLists.forEach(container => {
            container.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            const list = container.querySelector('.dame-checkbox-list');
            if (list) {
                reorderList(list);
                updateSelectionCount(list);
            }
        });
    }

    /**
     * Scans criteria and checks corresponding manual checkboxes.
     */
    function performContactPrecheck() {
        const typesSelect = document.getElementById('dame_contact_types_select');
        if (!typesSelect || !contactManualWrap) return;

        const selectedTypes = Array.from(typesSelect.selectedOptions).map(opt => opt.value);
        const selectedDepts = Array.from(document.querySelectorAll('.dame-dept-criteria-list input[type="checkbox"]:checked')).map(cb => cb.value);

        const hasTypes = selectedTypes.length > 0;
        const hasDepts = selectedDepts.length > 0;

        const contactCheckboxes = contactManualWrap.querySelectorAll('input[type="checkbox"]');

        contactCheckboxes.forEach(cb => {
            const dept = cb.getAttribute('data-dept') || '';
            const typesAttr = cb.getAttribute('data-types') || '';
            const types = typesAttr.split(',');

            const matchType = types.some(t => selectedTypes.includes(t));
            const matchDept = selectedDepts.includes(dept);

            let shouldCheck = false;

            if (hasTypes && hasDepts) {
                // Intersection du Type et des départements sélectionnés
                shouldCheck = (matchType && matchDept);
            } else if (hasTypes) {
                // Seulement par type
                shouldCheck = matchType;
            } else if (hasDepts) {
                // Seulement par département
                shouldCheck = matchDept;
            }

            if (shouldCheck) {
                cb.checked = true;
            }
        });

        // Trigger reorder after magic pre-check
        const manualList = contactManualWrap.querySelector('.dame-checkbox-list');
        if (manualList) {
            reorderList(manualList);
            updateSelectionCount(manualList);
        }
    }

    // 3. Searchable Lists Logic (Universal)
    const searchInputs = document.querySelectorAll('.dame-list-search');
    const checkboxLists = document.querySelectorAll('.dame-checkbox-list');

    // Initial reorder and count update for all lists on page load
    checkboxLists.forEach(list => {
        reorderList(list);
        updateSelectionCount(list);
    });

    /**
     * Normalizes text by converting to lowercase and removing accents.
     * @param {string} text 
     * @returns {string}
     */
    function normalizeText(text) {
        return text.toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    }

    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const filter = normalizeText(this.value);
            const wrapper = this.closest('.dame-searchable-list-wrapper');
            const list = wrapper ? wrapper.querySelector('.dame-checkbox-list') : null;
            if (!list) return;

            const labels = list.querySelectorAll('label');
            labels.forEach(label => {
                const text = normalizeText(label.textContent);
                if (text.indexOf(filter) > -1) {
                    label.style.display = "block";
                } else {
                    label.style.display = "none";
                }
            });
        });
    });

    // 4. Already sent warning logic
    const messageSelect = document.getElementById('dame_message_to_send');
    const warningDiv = document.getElementById('dame_message_warning');

    if (messageSelect && warningDiv) {
        messageSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const status = selectedOption.getAttribute('data-status');
            const submitBtn = document.querySelector('input[type="submit"]');

            if (status === 'scheduled') {
                warningDiv.style.display = 'block';
                warningDiv.style.color = '#d63638';
                warningDiv.textContent = "Ce message est actuellement en cours d'envoi. Veuillez attendre la fin du traitement.";
                if (submitBtn) submitBtn.disabled = true;
            } else if (status === 'sent') {
                warningDiv.style.display = 'block';
                warningDiv.style.color = '#2271b1';
                warningDiv.textContent = "Ce message a déjà été expédié. Tout nouvel envoi sera incrémental : les personnes l'ayant déjà reçu seront automatiquement ignorées.";
                if (submitBtn) submitBtn.disabled = false;
            } else {
                warningDiv.style.display = 'none';
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }
});
