(function() {
    'use strict';

    let modifiedProducts = new Set();
    let isSubmitting = false;

    document.addEventListener('DOMContentLoaded', function() {
        initProductTracker();
    });

    function initProductTracker() {
        document.addEventListener('change', function(e) {
            const target = e.target;
            if (target.matches('input[data-product-id]:not([type="checkbox"]):not([type="radio"]):not([readonly]), select[data-product-id], textarea[data-product-id]')) {
                trackProductChange(target);
            }
            if (target.matches('input[type="checkbox"][data-product-id], input[type="radio"][data-product-id]')) {
                trackProductChange(target);
            }
            if (target.matches('.select2-hidden-accessible[data-product-id]')) {
                trackProductChange(target);
            }
        });

        document.addEventListener('click', function(e) {
            if (e.target.matches('input[type="submit"], button[type="submit"]')) {
                isSubmitting = true;
            }
        }, true);

        const forms = document.querySelectorAll('form[name="cbbe_individual_update_form"], form#cbbe-individual-update, form');
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                isSubmitting = true;
                addModifiedProductsToForm(form);
            });
        });

        addVisualFeedback();
        saveOriginalValues();
    }

    function trackProductChange(element) {
        const productId = element.getAttribute('data-product-id');
        
        if (productId && !isSubmitting) {
            const originalValue = element.getAttribute('data-original-value');
            const currentValue = element.type === 'checkbox' ? element.checked : element.value;
            
            if (typeof originalValue === 'undefined' || originalValue === null) {
                element.setAttribute('data-original-value', currentValue);
                return;
            }
            
            if (originalValue != currentValue) {
                modifiedProducts.add(parseInt(productId));
                markProductRowAsModified(productId);
                
                const event = new CustomEvent('cbbe:productModified', { detail: productId });
                document.dispatchEvent(event);
            } else {
                modifiedProducts.delete(parseInt(productId));
                checkAndRemoveModifiedMark(productId);
            }
        }
    }

    function checkAndRemoveModifiedMark(productId) {
        let hasModifications = false;
        const elements = document.querySelectorAll('[data-product-id="' + productId + '"]');
        
        elements.forEach(function(element) {
            const originalValue = element.getAttribute('data-original-value');
            const currentValue = element.type === 'checkbox' ? element.checked : element.value;
            
            if (typeof originalValue !== 'undefined' && originalValue !== null && originalValue != currentValue) {
                hasModifications = true;
            }
        });
        
        if (!hasModifications) {
            const row = document.querySelector('tr[data-product-id="' + productId + '"]');
            if (row) {
                row.classList.remove('cbbe-modified');
            }
            modifiedProducts.delete(parseInt(productId));
        }
    }

    function addModifiedProductsToForm(form) {
        const existingInputs = form.querySelectorAll('input[name="modified_products[]"]');
        existingInputs.forEach(function(input) {
            input.remove();
        });
        
        if (modifiedProducts.size === 0) {
            return;
        }
        
        modifiedProducts.forEach(function(productId) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'modified_products[]';
            input.value = productId;
            form.appendChild(input);
        });
    }

    function markProductRowAsModified(productId) {
        const row = document.querySelector('tr[data-product-id="' + productId + '"]');
        if (row && !row.classList.contains('cbbe-modified')) {
            row.classList.add('cbbe-modified');
        }
    }

    function addVisualFeedback() {
        if (!document.getElementById('cbbe-tracker-styles')) {
            const style = document.createElement('style');
            style.id = 'cbbe-tracker-styles';
            style.textContent = `
                .cbbe-modified {
                    background-color: #fffbcc !important;
                }
            `;
            document.head.appendChild(style);
        }
    }

    function resetTracker() {
        modifiedProducts.clear();

        const modifiedRows = document.querySelectorAll('.cbbe-modified');
        modifiedRows.forEach(function(row) {
            row.classList.remove('cbbe-modified');
        });

        const elements = document.querySelectorAll('[data-product-id]');
        elements.forEach(function(element) {
            const currentValue = element.type === 'checkbox' ? element.checked : element.value;
            element.setAttribute('data-original-value', currentValue);
        });

        isSubmitting = false;
    }

    function saveOriginalValues() {
        const elements = document.querySelectorAll('[data-product-id]');
        elements.forEach(function(element) {
            const currentValue = element.type === 'checkbox' ? element.checked : element.value;
            element.setAttribute('data-original-value', currentValue);
        });
    }

    window.CBBEProductTracker = {
        getModifiedProducts: function() {
            return Array.from(modifiedProducts);
        },
        getModifiedCount: function() {
            return modifiedProducts.size;
        },
        reset: resetTracker,
        addProduct: function(productId) {
            modifiedProducts.add(parseInt(productId));
            markProductRowAsModified(productId);
        },
        removeProduct: function(productId) {
            modifiedProducts.delete(parseInt(productId));
            checkAndRemoveModifiedMark(productId);
        },
        hasModifiedProducts: function() {
            return modifiedProducts.size > 0;
        },
        setSubmitting: function(value) {
            isSubmitting = value;
        }
    };

    window.addEventListener('beforeunload', function(e) {
        if (isSubmitting) {
            return undefined;
        }
        
        if (modifiedProducts.size > 0) {
            const message = typeof cbbeTrackerConfig !== 'undefined' && cbbeTrackerConfig.i18n 
                ? cbbeTrackerConfig.i18n.unsavedChanges 
                : 'You have unsaved changes. Are you sure you want to leave?';
            
            e.returnValue = message;
            return message;
        }
        
        return undefined;
    });

    document.addEventListener('cbbe:productsSaved', function() {
        resetTracker();
    });

    if (document.querySelector('.updated') || document.querySelector('.notice-success')) {
        setTimeout(function() {
            resetTracker();
        }, 100);
    }

})();