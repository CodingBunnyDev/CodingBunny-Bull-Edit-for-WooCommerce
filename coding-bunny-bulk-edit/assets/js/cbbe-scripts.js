document.addEventListener('DOMContentLoaded', function () {

	/* ==========================================================================
	UTILS
	========================================================================== */

	function qs(sel, ctx) { ctx = ctx || document; return ctx.querySelector(sel); }
	function qsa(sel, ctx) { ctx = ctx || document; return Array.prototype.slice.call(ctx.querySelectorAll(sel)); }

	var ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : (window.ajaxurl || (window.location.origin + '/wp-admin/admin-ajax.php'));
	var cbbeAjaxLocal = (typeof cbbeAjax !== 'undefined') ? cbbeAjax : (window.cbbeAjax || {});

	qsa('.main-product').forEach(function (row) {
		var productName = qs('.product-name', row);
		var toggleIcon = qs('.toggle-icon', row);

		if (productName && toggleIcon) {
			productName.addEventListener('click', function () {
				var productId = row.getAttribute('data-product-id');
				var variations = qsa('.cbbe-variation[data-parent-id="' + productId + '"]');
				var hasVisible = variations.some(function (v) {
					return v.style.display === '' || v.style.display === 'table-row' || getComputedStyle(v).display !== 'none';
				});

				variations.forEach(function (v) { v.style.display = hasVisible ? 'none' : ''; });
				toggleIcon.textContent = hasVisible ? '+' : '-';
			});
		}
	});

	function initializeVariationListeners(productId) {
		var variations = qsa('.cbbe-variation[data-parent-id="' + productId + '"]');

		variations.forEach(function (variation) {
			qsa('input[data-product-id], select[data-product-id], textarea[data-product-id]', variation).forEach(function (field) {
				var currentValue = field.type === 'checkbox' ? field.checked : field.value;
				field.setAttribute('data-original-value', currentValue);
			});
		});
	}

	var selectAllCheckbox = qs('#select-all');

	function getAllProductCheckboxes() {
		return qsa('input[name="selected_products[]"], input.product-checkbox');
	}

	function updateSelectAllState() {
		if (!selectAllCheckbox) { return; }
		var boxes = getAllProductCheckboxes();
		if (boxes.length === 0) {
			selectAllCheckbox.checked = false;
			selectAllCheckbox.indeterminate = false;
			return;
		}
		var checked = boxes.filter(function (cb) { return cb.checked; }).length;
		selectAllCheckbox.checked = (checked === boxes.length);
		selectAllCheckbox.indeterminate = (checked > 0 && checked < boxes.length);
	}

	if (selectAllCheckbox) {
		selectAllCheckbox.addEventListener('change', function (e) {
			getAllProductCheckboxes().forEach(function (cb) { cb.checked = e.target.checked; });
		});
	}

	getAllProductCheckboxes().forEach(function (cb) {
		cb.addEventListener('change', updateSelectAllState);
	});

	function toggleDisplay(buttonClass) {
		qsa(buttonClass).forEach(function (button) {
			button.addEventListener('click', function () {
				var list = this.nextElementSibling;
				if (!list) { return; }

				var currentlyVisible = getComputedStyle(list).display !== 'none';
				list.style.display = currentlyVisible ? 'none' : 'block';
			});
		});
	}

	toggleDisplay('.toggle-categories');
	toggleDisplay('.toggle-tags');
	toggleDisplay('.toggle-description');
	toggleDisplay('.toggle-short-description');
	toggleDisplay('.toggle-purchase-note');
	toggleDisplay('.toggle-brands');
	toggleDisplay('.toggle-custom-taxonomy');
	toggleDisplay('.toggle-upsells');
	toggleDisplay('.toggle-cross-sells');

	qsa('.toggle-bulk-categories, .toggle-bulk-tags, .toggle-bulk-brands, .toggle-attribute, .toggle-bulk-upsells, .toggle-bulk-cross-sells, .toggle-bulk-custom-taxonomy')
	.forEach(function (button) {
		button.addEventListener('click', function () {
			var selector = button.getAttribute('data-target') || '';
			var target = selector ? qs(selector) : null;
			if (!target) { return; }

			var currentlyVisible = getComputedStyle(target).display !== 'none';
			target.style.display = currentlyVisible ? 'none' : 'block';
		});
	});

	var toggleVariationsButton = qs('#toggle-variations');

	if (toggleVariationsButton) {
		var variationsVisible = false;

		toggleVariationsButton.addEventListener('click', function () {
			var variations = qsa('.cbbe-variation');
			var icon = qs('.dashicons', toggleVariationsButton);

			variationsVisible = !variationsVisible;
			variations.forEach(function (v) { v.style.display = variationsVisible ? '' : 'none'; });

			if (icon) {
				if (variationsVisible) {
					icon.classList.remove('dashicons-visibility');
					icon.classList.add('dashicons-hidden');
					toggleVariationsButton.setAttribute('title', 'Hide product variations');
				} else {
					icon.classList.remove('dashicons-hidden');
					icon.classList.add('dashicons-visibility');
					toggleVariationsButton.setAttribute('title', 'Show product variations');
				}
			}
		});
	}

	qsa('input, select, textarea').forEach(function (element) {
		element.dataset.originalValue = element.value;
		element.addEventListener('change', function () {
			if (element.value !== element.dataset.originalValue) {
				element.classList.add('modified-field');
			} else {
				element.classList.remove('modified-field');
			}
		});
	});

	var resetButton = qs('#cbbe-filter-reset');

	if (resetButton) {
		resetButton.addEventListener('click', function () {
			var form = qs('#cbbe-filter-form');
			if (!form) { return; }

			qsa('input[type="text"], input[type="number"], input[type="date"], input[type="search"], select', form)
			.forEach(function (el) { el.value = ''; });

			qsa('select', form).forEach(function (el) { el.selectedIndex = 0; });

			qsa('input[type="hidden"]', form).forEach(function (el) {
				if (el.name !== 'page') { el.value = ''; }
			});

			updateSelectAllState();
		});
	}

	document.addEventListener('click', function (e) {
		var lock = e.target.closest('.cbbe-lock');
		if (!lock) { return; }

		var container = lock.closest('.cbbe-name-container');
		var input = container ? qs('input[type="text"]', container) : null;
		if (!input) { return; }

		var isReadOnly = input.hasAttribute('readonly');

		if (isReadOnly) {
			input.removeAttribute('readonly');
			lock.classList.remove('dashicons-lock');
			lock.classList.add('dashicons-unlock');
			lock.title = 'Unlock field';
		} else {
			input.setAttribute('readonly', 'readonly');
			lock.classList.remove('dashicons-unlock');
			lock.classList.add('dashicons-lock');
			lock.title = 'Lock field';
		}
	});

	document.addEventListener('submit', function (e) {
		if (!e.target.matches('form')) { return; }

		qsa('tr[data-product-id]', e.target).forEach(function (tr) {
			var productId = tr.getAttribute('data-product-id');

			var upsellsSelect = qs('select[name="upsells[' + productId + '][]"]', tr);
			if (upsellsSelect) {
				var val = Array.prototype.slice.call(upsellsSelect.selectedOptions).map(function (opt) { return opt.value; });
				if (val.length === 0 && !qs('input[name="upsells[' + productId + '][]"][type="hidden"]', tr)) {
					var input = document.createElement('input');
					input.type = 'hidden';
					input.name = 'upsells[' + productId + '][]';
					input.value = '';
					tr.appendChild(input);
				}
			}

			var crossSellsSelect = qs('select[name="cross_sells[' + productId + '][]"]', tr);
			if (crossSellsSelect) {
				var val2 = Array.prototype.slice.call(crossSellsSelect.selectedOptions).map(function (opt) { return opt.value; });
				if (val2.length === 0 && !qs('input[name="cross_sells[' + productId + '][]"][type="hidden"]', tr)) {
					var input2 = document.createElement('input');
					input2.type = 'hidden';
					input2.name = 'cross_sells[' + productId + '][]';
					input2.value = '';
					tr.appendChild(input2);
				}
			}
		});

		var bulkUpsells = qs('select[name="bulk_upsells[]"]');
		if (bulkUpsells && bulkUpsells.selectedOptions.length === 0 && !qs('input[name="bulk_upsells[]"][type="hidden"]')) {
			var hidden = document.createElement('input');
			hidden.type = 'hidden';
			hidden.name = 'bulk_upsells[]';
			hidden.value = '';
			bulkUpsells.parentNode.insertBefore(hidden, bulkUpsells.nextSibling);
		}

		var bulkCrossSells = qs('select[name="bulk_cross_sells[]"]');
		if (bulkCrossSells && bulkCrossSells.selectedOptions.length === 0 && !qs('input[name="bulk_cross_sells[]"][type="hidden"]')) {
			var hidden2 = document.createElement('input');
			hidden2.type = 'hidden';
			hidden2.name = 'bulk_cross_sells[]';
			hidden2.value = '';
			bulkCrossSells.parentNode.insertBefore(hidden2, bulkCrossSells.nextSibling);
		}
	});

	if (window.Sortable) {
		var colList = document.getElementById('column-order');

		if (colList) {
			Sortable.create(colList, {
				animation: 150,
				handle: '.dashicons-move',
				onEnd: function () {
					Array.prototype.forEach.call(document.querySelectorAll('#column-order li'), function (element) {
						var input = element.querySelector('input[name="order[]"]');
						if (input) {
							input.value = element.dataset.column;
						}
					});
				}
			});
		}

		var bulkColList = document.getElementById('bulk-column-order');

		if (bulkColList) {
			Sortable.create(bulkColList, {
				animation: 150,
				handle: '.dashicons-move',
				onEnd: function () {
					Array.prototype.forEach.call(document.querySelectorAll('#bulk-column-order li'), function (element) {
						var input = element.querySelector('input[name="bulk_order[]"]');
						if (input) {
							input.value = element.dataset.bulkfield;
						}
					});
				}
			});
		}
	}

	var bulkForm = qs('#cbbe-bulk-form');
	var selectedProductsInput = qs('#selected_products_input');

	if (bulkForm && selectedProductsInput) {
		bulkForm.addEventListener('submit', function (e) {
			var selectedProducts = selectedProductsInput.value;
			if (!selectedProducts || selectedProducts === '') {
				e.preventDefault();
				alert('Please select at least one product to edit.');
				return false;
			}

			var productIds = selectedProducts.split(',');
			selectedProductsInput.remove();

			productIds.forEach(function (productId) {
				var input = document.createElement('input');
				input.type = 'hidden';
				input.name = 'selected_products[]';
				input.value = productId;
				bulkForm.appendChild(input);
			});

			return true;
		});
	}

	(function () {
		var bulkDeleteBtn = qs('#cbbe-bulk-delete-btn');
		var bulkDuplicateBtn = qs('#cbbe-bulk-duplicate-btn');

		function getSelectedProductIds() {
			var boxes = Array.prototype.slice.call(document.querySelectorAll('input[name="selected_products[]"]:checked, input.product-checkbox:checked'));
			return boxes.map(function (cb) { return cb.value; });
		}

		function safeFetchJson(formData) {
			return fetch(ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData
			})
			.then(function (r) {
				return r.json().catch(function () {
					return r.text().then(function (t) {
						throw new Error('Invalid server response: ' + (t ? t.substring(0, 200) : 'empty'));
					});
				});
			});
		}

		function performBulkDelete(ids) {
			if (!ids) { return Promise.reject(new Error('No product ids')); }
			var productIds = Array.isArray(ids) ? ids : [ids];
			if (productIds.length === 0) { return Promise.reject(new Error('No product ids')); }

			if (!confirm('Are you sure you want to delete the selected product(s)? This action cannot be undone.')) {
				return Promise.reject(new Error('User cancelled'));
			}

			var formData = new FormData();
			formData.append('action', 'cbbe_bulk_delete');
			formData.append('nonce', (cbbeAjaxLocal && cbbeAjaxLocal.bulkDeleteNonce) ? cbbeAjaxLocal.bulkDeleteNonce : '');
			productIds.forEach(function (id) { formData.append('product_ids[]', id); });

			return safeFetchJson(formData).then(function (data) {
				if (data && data.success) {
					productIds.forEach(function (id) {
						var row = document.querySelector('tr[data-product-id="' + id + '"]');
						if (row) { row.remove(); }
					});
					updateSelectAllState();
				}
				return data;
			});
		}

		window.cbbePerformBulkDelete = performBulkDelete;

		if (bulkDeleteBtn && !bulkDeleteBtn.dataset.cbbeBound) {
			bulkDeleteBtn.dataset.cbbeBound = '1';
			bulkDeleteBtn.addEventListener('click', function () {
				var selected = getSelectedProductIds();
				if (!selected.length) {
					alert('Please select at least one product to delete.');
					return;
				}
				bulkDeleteBtn.disabled = true;
				performBulkDelete(selected)
				.then(function (data) {
					bulkDeleteBtn.disabled = false;
					if (data && data.success) {
						alert((data.data && data.data.message) ? data.data.message : 'Products deleted successfully.');
					} else {
						alert((data && data.data && data.data.message) ? data.data.message : 'Error deleting products.');
					}
				})
				.catch(function (err) {
					bulkDeleteBtn.disabled = false;
					if (err && err.message !== 'User cancelled') {
						console.error('Bulk delete error:', err);
						alert('Request error: ' + (err.message || err));
					}
				});
			});
		}

		if (bulkDuplicateBtn && !bulkDuplicateBtn.dataset.cbbeBound) {
			bulkDuplicateBtn.dataset.cbbeBound = '1';
			bulkDuplicateBtn.addEventListener('click', function () {
				var selected = getSelectedProductIds();
				if (!selected.length) {
					alert('Please select at least one product to duplicate.');
					return;
				}
				if (!confirm('Duplicate selected products?')) { return; }

				bulkDuplicateBtn.disabled = true;

				var formData = new FormData();
				formData.append('action', 'cbbe_bulk_duplicate');
				formData.append('nonce', (cbbeAjaxLocal && cbbeAjaxLocal.bulkDuplicateNonce) ? cbbeAjaxLocal.bulkDuplicateNonce : '');
				selected.forEach(function (id) { formData.append('product_ids[]', id); });

				safeFetchJson(formData)
				.then(function (data) {
					bulkDuplicateBtn.disabled = false;
					if (data && data.success) {
						alert((data.data && data.data.message) ? data.data.message : 'Products duplicated successfully.');
						if (data.data && data.data.reload) { location.reload(); }
					} else {
						alert((data && data.data && data.data.message) ? data.data.message : 'Error duplicating products.');
					}
				})
				.catch(function (err) {
					console.error('Fetch error (bulk duplicate):', err);
					bulkDuplicateBtn.disabled = false;
					alert('Request error: ' + (err.message || err));
				});
			});
		}
	})();

	(function () {
		var splitBtnAll = document.querySelector('#cbbe-split-variations-btn');
		var splitNonce = (cbbeAjaxLocal && cbbeAjaxLocal.splitVariationsNonce) ? cbbeAjaxLocal.splitVariationsNonce : '';

		function getSelectedParent() {
			var sel = Array.prototype.slice.call(document.querySelectorAll('input[name="selected_products[]"]:checked')).map(function (cb) { return cb.value; });
			return sel.length ? sel[0] : null;
		}

		function parseResponseSafe(response) {
			return response.text().then(function (text) {
				var parsed = null;
				try { parsed = JSON.parse(text); } catch (e) { parsed = null; }
				return { ok: response.ok, status: response.status, raw: text, json: parsed };
			});
		}

		function safeFetchJson(formData) {
			return fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData }).then(parseResponseSafe);
		}

		function performSplitVariationsForParent(parentId) {
			if (!parentId) { return Promise.reject(new Error('No parent id')); }
			if (!confirm('Split variations into separate products?')) { return Promise.reject(new Error('User cancelled')); }

			var formData = new FormData();
			formData.append('action', 'cbbe_split_variations');
			formData.append('nonce', splitNonce);
			formData.append('parent_id', parentId);

			return safeFetchJson(formData);
		}

		window.cbbeSplitVariations = performSplitVariationsForParent;

		if (splitBtnAll) {
			splitBtnAll.addEventListener('click', function () {
				var parentId = getSelectedParent();
				if (!parentId) { alert('Please select a parent product to split.'); return; }
				splitBtnAll.disabled = true;
				performSplitVariationsForParent(parentId).then(function (res) {
					if (!res.ok) {
						console.error('Split: non-2xx response:', res.status, res.raw);
						var msg = res.json && res.json.data && res.json.data.message ? res.json.data.message : 'Server error: ' + res.status;
						alert(msg); return;
					}
					if (res.json && res.json.success) {
						alert(res.json.data && res.json.data.message ? res.json.data.message : 'Split completed.');
						if (res.json.data && res.json.data.reload) { location.reload(); }
					} else {
						if (res.json && res.json.data && res.json.data.message) { alert(res.json.data.message); } else { console.error('Split: invalid JSON or failure:', res.raw); alert('Operation failed. See console/network for details.'); }
					}
				}).catch(function (err) {
					console.error('Split error:', err); if (err.message !== 'User cancelled') { alert('Request error: ' + (err.message || err)); }
				}).finally(function () { splitBtnAll.disabled = false; });
			});
		}

		document.addEventListener('click', function (e) {
			var el = e.target.closest('.cbbe-split-variations');
			if (!el) { return; }
			var parentId = el.getAttribute('data-parent-id');
			if (!parentId) { return; }
			el.disabled = true;
			performSplitVariationsForParent(parentId).then(function (res) {
				if (!res.ok) {
					console.error('Split row: non-2xx:', res.status, res.raw);
					var msg = res.json && res.json.data && res.json.data.message ? res.json.data.message : 'Server error: ' + res.status;
					alert(msg); return;
				}
				if (res.json && res.json.success) {
					alert(res.json.data && res.json.data.message ? res.json.data.message : 'Split completed.');
					if (res.json.data && res.json.data.reload) { location.reload(); }
				} else {
					if (res.json && res.json.data && res.json.data.message) { alert(res.json.data.message); } else { console.error('Split row: invalid JSON or failure:', res.raw); alert('Operation failed. See console/network for details.'); }
				}
			}).catch(function (err) {
				console.error('Split row fetch error:', err); alert('Request error: ' + (err.message || err));
			}).finally(function () { el.disabled = false; });
		});
	})();

	(function () {
		var urlParams = new URLSearchParams(window.location.search);
		var productCreated = urlParams.get('product_created');

		if (productCreated) {
			var productRow = qs('tr.main-product[data-product-id="' + productCreated + '"]');

			if (productRow) {
				setTimeout(function () {
					productRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
					productRow.style.backgroundColor = '#d4edda';

					setTimeout(function () {
						productRow.style.transition = 'background-color 2s';
						productRow.style.backgroundColor = '';
					}, 2000);
				}, 500);

				var productName = qs('.product-name', productRow);
				var toggleIcon = qs('.toggle-icon', productRow);

				if (productName && toggleIcon) {
					setTimeout(function () {
						productName.click();
					}, 600);
				}
			}
		}
	})();

	(function () {
		function setProductNameTooltip(input) {
			if (!input) return;
			input.title = input.value || '';
			input.addEventListener('input', function () {
				input.title = input.value || '';
			});
		}

		function initProductNameTooltips() {
			document.querySelectorAll('.cbbe-name-container input[type="text"]').forEach(function (input) {
				setProductNameTooltip(input);
			});
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', initProductNameTooltips);
		} else {
			initProductNameTooltips();
		}

		var observer = new MutationObserver(function () {
			initProductNameTooltips();
		});
		observer.observe(document.body, { childList: true, subtree: true });
	})();

	/* ==========================================================================
	MODALS
	========================================================================== */

	function setupModal(openBtnId, closeBtnId, modalId, additionalCloseClass) {
		var openBtn = qs(openBtnId);
		var closeBtn = closeBtnId ? qs(closeBtnId) : null;
		var modal = qs(modalId);

		if (openBtn && modal) {
			openBtn.addEventListener('click', function () {
				modal.style.display = 'flex';
				modal.style.opacity = 0;

				setTimeout(function () {
					modal.style.transition = 'opacity 150ms';
					modal.style.opacity = 1;
				}, 10);
			});
		}

		if (closeBtn && modal) {
			closeBtn.addEventListener('click', function () {
				closeModal(modal);
			});
		}

		if (additionalCloseClass) {
			qsa(additionalCloseClass).forEach(function (btn) {
				btn.addEventListener('click', function () {
					var parentModal = btn.closest('.cbbe-modal');
					if (parentModal) { closeModal(parentModal); }
				});
			});
		}

		if (modal) {
			modal.addEventListener('click', function (e) {
				if (e.target === modal) { closeModal(modal); }
			});
		}
	}

	function closeModal(modal) {
		if (!modal) { return; }

		modal.style.transition = 'opacity 150ms';
		modal.style.opacity = 0;

		setTimeout(function () {
			modal.style.display = 'none';
		}, 150);
	}

	setupModal('#open-filter-modal', '#close-filter-modal', '#cbbe-filter-modal');

	(function () {
		var openBulkModalBtn = qs('#open-bulk-modal');
		var closeBulkModalBtn = qs('#close-bulk-modal');
		var bulkModal = qs('#cbbe-bulk-modal');
		var selectedProductsInputLocal = qs('#selected_products_input');

		if (openBulkModalBtn && bulkModal) {
			openBulkModalBtn.addEventListener('click', function () {
				var selectedProducts = [];

				qsa('input[name="selected_products[]"]:checked').forEach(function (checkbox) {
					selectedProducts.push(checkbox.value);
				});

				if (selectedProducts.length === 0) {
					alert('Please select at least one product to edit.');
					return;
				}

				if (selectedProductsInputLocal) {
					selectedProductsInputLocal.value = selectedProducts.join(',');
				}

				bulkModal.style.display = 'flex';
				bulkModal.style.opacity = 0;

				setTimeout(function () {
					bulkModal.style.transition = 'opacity 150ms';
					bulkModal.style.opacity = 1;
				}, 10);
			});
		}

		if (closeBulkModalBtn && bulkModal) {
			closeBulkModalBtn.addEventListener('click', function () {
				closeModal(bulkModal);
			});
		}

		if (bulkModal) {
			bulkModal.addEventListener('click', function (e) {
				if (e.target === bulkModal) {
					closeModal(bulkModal);
				}
			});
		}
	})();

	setupModal('#open-columns-modal', '#close-columns-modal', '#manage-columns-modal', '.cbbe-modal-cancel');

	setupModal('#open-bulk-fields-modal', '#close-bulk-fields-modal', '#manage-bulk-fields-modal', '.cbbe-modal-cancel');

	qsa('.cbbe-close').forEach(function (closeBtn) {
		closeBtn.addEventListener('click', function () {
			var modal = closeBtn.closest('.cbbe-modal');
			if (modal) { closeModal(modal); }
		});
	});

	qsa('.upload_image_button').forEach(function (button) {
		button.addEventListener('click', function (e) {
			e.preventDefault();
			var input = button.parentNode.querySelector('input');
			var img = button.parentNode.querySelector('img');
			var current_image_id = input ? input.value : null;

			var frame = wp.media({
				title: (window.cbbeL10n && window.cbbeL10n.imageTitle) ? window.cbbeL10n.imageTitle : 'Select or upload image',
				button: { text: (window.cbbeL10n && window.cbbeL10n.imageButton) ? window.cbbeL10n.imageButton : 'Use this image' },
				multiple: false
			});

			frame.on('open', function () {
				var selection = frame.state().get('selection');
				if (current_image_id) {
					var attachment = wp.media.attachment(current_image_id);
					attachment.fetch();
					selection.add(attachment ? [attachment] : []);
				}
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				if (img) { img.setAttribute('src', attachment.url); }
				if (input) {
					input.value = attachment.id;
					input.dispatchEvent(new Event('change', { bubbles: true }));
				}
			});

			frame.open();
		});
	});

	qsa('.upload_gallery_button').forEach(function (button) {
		button.addEventListener('click', function (e) {
			e.preventDefault();
			var galleryInput = button.parentNode.querySelector('input[name^="gallery"]');
			var galleryContainer = button.parentNode;
			var current_gallery_ids = galleryInput && galleryInput.value.trim() !== '' ? galleryInput.value.split(',') : [];

			var attachments = current_gallery_ids.map(function (id) {
				var att = wp.media.attachment(id);
				att.fetch();
				return att;
			});

			var frame = wp.media({
				title: (window.cbbeL10n && window.cbbeL10n.galleryTitle) ? window.cbbeL10n.galleryTitle : 'Select or upload images',
				button: { text: (window.cbbeL10n && window.cbbeL10n.galleryButton) ? window.cbbeL10n.galleryButton : 'Use these images' },
				multiple: true
			});

			frame.on('open', function () {
				var selection = frame.state().get('selection');
				attachments.forEach(function (att) { selection.add(att); });
			});

			frame.on('select', function () {
				var selected_attachments = frame.state().get('selection').toJSON();
				var gallery_ids = [];

				if (galleryContainer) {
					qsa('img', galleryContainer).forEach(function (existingImg) { existingImg.remove(); });
				}

				selected_attachments.forEach(function (attachment) {
					if (galleryContainer) {
						var imgTag = document.createElement('img');
						imgTag.src = attachment.url;
						imgTag.className = 'product-image';
						galleryContainer.insertBefore(imgTag, button);
					}
					gallery_ids.push(attachment.id);
				});

				if (galleryInput) {
					galleryInput.value = gallery_ids.join(',');
					galleryInput.dispatchEvent(new Event('change', { bubbles: true }));
				}
			});

			frame.open();
		});
	});

	(function () {
		var addProductBtn = qs('#cbbe-add-product-btn');
		var addProductModal = qs('#cbbe-add-product-modal');
		var closeAddProductBtn = qs('#close-add-product-modal');
		var cancelAddProductBtn = qs('#cbbe-cancel-add-product');
		var addProductForm = qs('#cbbe-add-product-form');
		var productTypeSelect = qs('#cbbe-product-type');
		var variableOptions = qs('#cbbe-variable-options');

		if (addProductBtn && addProductModal) {
			addProductBtn.addEventListener('click', function () {
				addProductModal.style.display = 'flex';
				addProductModal.style.opacity = 0;

				setTimeout(function () {
					addProductModal.style.transition = 'opacity 150ms';
					addProductModal.style.opacity = 1;
				}, 10);
			});
		}

		function closeAddProductModal() {
			if (!addProductModal) { return; }

			addProductModal.style.transition = 'opacity 150ms';
			addProductModal.style.opacity = 0;

			setTimeout(function () {
				addProductModal.style.display = 'none';
				if (addProductForm) { addProductForm.reset(); }
				if (variableOptions) { variableOptions.style.display = 'none'; }
				var message = qs('#cbbe-add-product-message');
				if (message) { message.innerHTML = ''; }
				qsa('.cbbe-attribute-toggle').forEach(function (cb) { cb.checked = false; });
				qsa('.cbbe-attribute-terms').forEach(function (div) { div.style.display = 'none'; });
			}, 150);
		}

		if (closeAddProductBtn && addProductModal) {
			closeAddProductBtn.addEventListener('click', function () {
				closeAddProductModal();
			});
		}

		if (cancelAddProductBtn && addProductModal) {
			cancelAddProductBtn.addEventListener('click', function () {
				closeAddProductModal();
			});
		}

		if (addProductModal) {
			addProductModal.addEventListener('click', function (e) {
				if (e.target === addProductModal) { closeAddProductModal(); }
			});
		}

		if (productTypeSelect && variableOptions) {
			productTypeSelect.addEventListener('change', function () {
				variableOptions.style.display = this.value === 'variable' ? 'block' : 'none';
			});
		}

		qsa('.cbbe-attribute-toggle').forEach(function (checkbox) {
			checkbox.addEventListener('change', function () {
				var attribute = this.getAttribute('data-attribute');
				var termsDiv = qs('.cbbe-attribute-terms[data-attribute="' + attribute + '"]');

				if (termsDiv) {
					termsDiv.style.display = this.checked ? 'block' : 'none';

					if (!this.checked) {
						qsa('input[type="checkbox"]', termsDiv).forEach(function (cb) { cb.checked = false; });
					}
				}
			});
		});

		if (addProductForm) {
			addProductForm.addEventListener('submit', function (e) {
				e.preventDefault();

				var submitBtn = qs('#cbbe-submit-add-product');
				var messageDiv = qs('#cbbe-add-product-message');

				if (!submitBtn || !messageDiv) { return; }

				var productType = qs('#cbbe-product-type').value;

				if (productType === 'variable') {
					var hasSelectedAttributes = Array.prototype.slice.call(qsa('.cbbe-attribute-toggle')).some(function (cb) { return cb.checked; });

					if (!hasSelectedAttributes) {
						messageDiv.innerHTML = '<div class="notice notice-error"><p>Please select at least one attribute for variable products.</p></div>';
						return;
					}

					var hasTerms = false;

					qsa('.cbbe-attribute-toggle:checked').forEach(function (attrCb) {
						var attribute = attrCb.getAttribute('data-attribute');
						var termsDiv = qs('.cbbe-attribute-terms[data-attribute="' + attribute + '"]');

						if (termsDiv) {
							var selectedTerms = qsa('input[type="checkbox"]:checked', termsDiv);
							if (selectedTerms.length > 0) { hasTerms = true; }
						}
					});

					if (!hasTerms) {
						messageDiv.innerHTML = '<div class="notice notice-error"><p>Please select at least one term for each selected attribute.</p></div>';
						return;
					}
				}

				submitBtn.disabled = true;
				submitBtn.textContent = 'Creating...';
				messageDiv.innerHTML = '<div class="notice notice-info"><p>Creating product, please wait...</p></div>';

				var formData = new FormData(this);
				formData.append('action', 'cbbe_add_product');

				fetch(ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData
				})
				.then(function (response) {
					return response.text().then(function (text) {
						try {
							return JSON.parse(text);
						} catch (e) {
							console.error('JSON parse error:', e);
							throw new Error('Invalid server response');
						}
					});
				})
				.then(function (data) {
					submitBtn.disabled = false;
					submitBtn.textContent = 'Create product';

					if (data.success) {
						messageDiv.innerHTML = '<div class="notice notice-success"><p>' + data.data.message + '</p></div>';

						setTimeout(function () {
							closeAddProductModal();
							if (data.data && data.data.redirect_url) {
								window.location.href = data.data.redirect_url;
							} else {
								location.reload();
							}
						}, 1500);
					} else {
						var errorMsg = data.data && data.data.message ? data.data.message : 'Unknown error';
						messageDiv.innerHTML = '<div class="notice notice-error"><p>' + errorMsg + '</p></div>';
					}
				})
				.catch(function (error) {
					console.error('Fetch error:', error);
					submitBtn.disabled = false;
					submitBtn.textContent = 'Create product';
					messageDiv.innerHTML = '<div class="notice notice-error"><p>An error occurred: ' + error.message + '</p></div>';
				});
			});
		}
	})();

	(function () {
		var openCustomFieldsModalBtn = qs('#open-custom-fields-modal');
		var closeCustomFieldsModalBtn = qs('#close-custom-fields-modal');
		var cancelCustomFieldsBtn = qs('#cancel-custom-fields');
		var customFieldsModal = qs('#cbbe-custom-fields-modal');
		var customFieldsForm = qs('#custom-fields-form');
		var existingContainerSelector = '#cbbe-existing-custom-fields-container';
		var messageSelector = '#cbbe-custom-fields-message';

		function showMessage(type, text) {
			var msgEl = qs(messageSelector);
			if (!msgEl) { return; }
			msgEl.innerHTML = '<div class="notice notice-' + type + '"><p>' + text + '</p></div>';
		}
		function clearMessage() {
			var msgEl = qs(messageSelector);
			if (!msgEl) { return; }
			msgEl.innerHTML = '';
		}

		function closeCustomFieldsModal() {
			if (!customFieldsModal) { return; }
			customFieldsModal.style.transition = 'opacity 150ms';
			customFieldsModal.style.opacity = 0;
			setTimeout(function () {
				customFieldsModal.style.display = 'none';
				location.reload();
			}, 150);
		}

		if (openCustomFieldsModalBtn && customFieldsModal) {
			openCustomFieldsModalBtn.addEventListener('click', function () {
				reloadCustomFieldsTable();
				customFieldsModal.style.display = 'flex';
				customFieldsModal.style.opacity = 0;
				setTimeout(function () {
					customFieldsModal.style.transition = 'opacity 150ms';
					customFieldsModal.style.opacity = 1;
				}, 10);
			});
		}
		if (closeCustomFieldsModalBtn && customFieldsModal) {
			closeCustomFieldsModalBtn.addEventListener('click', closeCustomFieldsModal);
		}
		if (cancelCustomFieldsBtn && customFieldsModal) {
			cancelCustomFieldsBtn.addEventListener('click', closeCustomFieldsModal);
		}
		if (customFieldsModal) {
			customFieldsModal.addEventListener('click', function (e) {
				if (e.target === customFieldsModal) { closeCustomFieldsModal(); }
			});
		}

		var addCustomFieldBtn = qs('#cbbe-add-custom-field');
		if (addCustomFieldBtn) {
			addCustomFieldBtn.addEventListener('click', function () {
				var container = qs('#custom-fields-container');
				if (!container) { return; }
				var row = container.querySelector('.custom-field-row:last-child');
				if (!row) { return; }

				var slugEl = row.querySelector('.custom-field-metakey');
				var labelEl = row.querySelector('.custom-field-label');
				var typeEl = row.querySelector('.custom-field-type');
				var taxEl = row.querySelector('.custom-field-taxonomy');

				var slug = slugEl ? slugEl.value.trim() : '';
				var label = labelEl ? labelEl.value.trim() : '';
				var type = typeEl ? typeEl.value : '';
				var taxonomy = (taxEl && taxEl.style.display !== 'none') ? taxEl.value : '';

				if (!slug) {
					showMessage('error', 'Please enter a meta key before adding.');
					if (slugEl) { slugEl.focus(); }
					return;
				}

				var fd = new FormData();
				fd.append('action', 'cbbe_save_custom_fields_ajax');
				fd.append('custom_field_slugs[]', slug);
				fd.append('custom_field_labels[]', label);
				fd.append('custom_field_types[]', type);
				fd.append('custom_field_taxonomies[]', taxonomy);

				var nonceField = qs('[name="cbbe_custom_fields_nonce"]', customFieldsForm);
				if (nonceField) { fd.append('nonce', nonceField.value); }

				addCustomFieldBtn.disabled = true;
				showMessage('info', 'Adding custom field...');

				fetch(ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: fd
				})
				.then(function (r) { return r.json().catch(function () { return { success: false, data: { message: 'Invalid server response' } }; }); })
				.then(function (data) {
					addCustomFieldBtn.disabled = false;
					if (data && data.success) {
						if (slugEl) { slugEl.value = ''; }
						if (labelEl) { labelEl.value = ''; }
						if (typeEl) { typeEl.selectedIndex = 0; }
						if (taxEl) { taxEl.selectedIndex = 0; taxEl.style.display = 'none'; }

						showMessage('success', (data.data && data.data.message) ? data.data.message : 'Custom field added.');

						reloadCustomFieldsTable();

						setTimeout(clearMessage, 3000);
					} else {
						var err = data && data.data && data.data.message ? data.data.message : 'Error adding custom field.';
						showMessage('error', err);
					}
				})
				.catch(function (err) {
					console.error('Error adding custom field:', err);
					addCustomFieldBtn.disabled = false;
					showMessage('error', 'Request error while adding custom field.');
				});
			});
		}

		document.addEventListener('change', function (e) {
			var target = e.target;
			if (!target || !target.classList) { return; }

			if (target.classList.contains('custom-field-selector')) {
				var row = target.closest('.custom-field-row');
				if (!row) { return; }

				var selectedKey = (typeof target.value === 'string') ? target.value.trim() : '';

				if (!selectedKey && target.options && typeof target.selectedIndex === 'number') {
					try {
						var opt = target.options[target.selectedIndex];
						if (opt && opt.text) {
							var m = opt.text.match(/\(([^)]+)\)\s*$/);
							if (m && m[1]) { selectedKey = m[1].trim(); }
						}
					} catch (err) {
						/* ignore */
					}
				}

				var metakeyInput = row.querySelector('.custom-field-metakey');
				var labelInput = row.querySelector('.custom-field-label');

				if (metakeyInput && selectedKey) { metakeyInput.value = selectedKey; }

				if (labelInput) {
					if (typeof cbbeDetectedFields !== 'undefined' && cbbeDetectedFields[selectedKey]) {
						labelInput.value = cbbeDetectedFields[selectedKey];
					} else {
						try {
							var optText = '';
							if (target.options && typeof target.selectedIndex === 'number' && target.options[target.selectedIndex]) {
								optText = target.options[target.selectedIndex].text || '';
							}
							optText = optText.replace(/\s*\([^)]+\)\s*$/, '').trim();
							if (optText) { labelInput.value = optText; }
						} catch (err) {
							/* ignore */
						}
					}
				}
			}
		});

		document.addEventListener('click', function (e) {
			var remBtn = e.target.closest('.cf-remove-btn');
			if (!remBtn) { return; }

			var slug = remBtn.getAttribute('data-slug');
			if (!slug) { return; }

			if (!confirm('Are you sure you want to remove this custom field?')) { return; }

			var fd = new FormData();
			fd.append('action', 'cbbe_remove_custom_field_ajax');
			fd.append('field_slug', slug);

			var nonceEl = qs('[name="cbbe_remove_custom_field_nonce"]', customFieldsForm) || qs('[name="cbbe_remove_custom_field_nonce"]');
			if (nonceEl) { fd.append('nonce', nonceEl.value); }

			showMessage('info', 'Removing custom field...');
			remBtn.disabled = true;

			fetch(ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: fd
			})
			.then(function (r) { return r.json().catch(function () { return { success: false, data: { message: 'Invalid server response' } }; }); })
			.then(function (data) {
				remBtn.disabled = false;
				if (data && data.success) {
					showMessage('success', (data.data && data.data.message) ? data.data.message : 'Custom field removed.');
					reloadCustomFieldsTable();
					setTimeout(clearMessage, 3000);
				} else {
					var err = data && data.data && data.data.message ? data.data.message : 'Error removing custom field.';
					showMessage('error', err);
				}
			})
			.catch(function (err) {
				console.error('Error removing custom field:', err);
				remBtn.disabled = false;
				showMessage('error', 'Request error while removing custom field.');
			});
		});

		qsa('.cf-edit-form').forEach(function (form) { form.style.display = 'none'; });

		document.addEventListener('click', function (e) {
			var editBtn = e.target.closest('.cf-edit-btn');
			if (editBtn) {
				var tr = editBtn.closest('tr');
				if (!tr) { return; }
				qsa('.cf-label-view, .cf-type-view, .cf-taxonomy-view, .cf-action-view', tr).forEach(function (el) { el.style.display = 'none'; });
				var editForm = qs('.cf-edit-form', tr);
				if (editForm) { editForm.style.display = ''; }
				return;
			}

			var applyBtn = e.target.closest('.cf-apply-edit-btn');
			if (applyBtn) {
				var tr2 = applyBtn.closest('tr');
				if (!tr2) { return; }
				var form = qs('.cf-edit-form', tr2);
				if (!form) { return; }
				var newLabel = (form.querySelector('[name="edit_label"]') || {}).value || '';
				var newType  = (form.querySelector('[name="edit_type"]') || {}).value || '';
				var newTax   = (form.querySelector('[name="edit_taxonomy"]') || {}).value || '';
				var labelView = qs('.cf-label-view', tr2);
				var typeView  = qs('.cf-type-view', tr2);
				var taxView   = qs('.cf-taxonomy-view', tr2);
				if (labelView) { labelView.textContent = newLabel; }
				if (typeView) { typeView.textContent = (newType === 'bool') ? 'True/False' : (newType ? newType.charAt(0).toUpperCase() + newType.slice(1) : ''); }
				if (taxView) { taxView.textContent = newTax || ''; }
				tr2.dataset.cbbeEdited = '1';
				form.style.display = 'none';
				qsa('.cf-label-view, .cf-type-view, .cf-taxonomy-view, .cf-action-view', tr2).forEach(function (el) { el.style.display = ''; });
				return;
			}

			var cancelBtn = e.target.closest('.cf-cancel-edit');
			if (cancelBtn) {
				var tr3 = cancelBtn.closest('tr');
				if (!tr3) { return; }
				var form3 = qs('.cf-edit-form', tr3);
				if (form3) { form3.style.display = 'none'; }
				qsa('.cf-label-view, .cf-type-view, .cf-taxonomy-view, .cf-action-view', tr3).forEach(function (el) { el.style.display = ''; });
				return;
			}
		});

		document.addEventListener('submit', function (e) {
			if (!e.target || !e.target.classList || !e.target.classList.contains('cf-edit-form')) { return; }
			e.preventDefault();
			var form = e.target;
			var tr = form.closest('tr');
			if (!tr) { return; }
			var newLabel = (form.querySelector('[name="edit_label"]') || {}).value || '';
			var newType  = (form.querySelector('[name="edit_type"]') || {}).value || '';
			var newTax   = (form.querySelector('[name="edit_taxonomy"]') || {}).value || '';
			var labelView = qs('.cf-label-view', tr);
			var typeView  = qs('.cf-type-view', tr);
			var taxView   = qs('.cf-taxonomy-view', tr);
			if (labelView) { labelView.textContent = newLabel; }
			if (typeView) { typeView.textContent = (newType === 'bool') ? 'True/False' : (newType ? newType.charAt(0).toUpperCase() + newType.slice(1) : ''); }
			if (taxView) { taxView.textContent = newTax || ''; }
			tr.dataset.cbbeEdited = '1';
			form.style.display = 'none';
			qsa('.cf-label-view, .cf-type-view, .cf-taxonomy-view, .cf-action-view', tr).forEach(function (el) { el.style.display = ''; });
		});

		if (customFieldsForm) {
			customFieldsForm.addEventListener('submit', function (e) {
				e.preventDefault();
				var submitBtn = qs('button[type="submit"], button[name="cbbe_save_custom_fields"]', customFieldsForm);
				if (!submitBtn) { return; }
				submitBtn.disabled = true;
				var originalText = submitBtn.textContent;
				submitBtn.textContent = (window.cbbeL10n && cbbeL10n.saving) ? cbbeL10n.saving : 'Saving...';
				showMessage('info', (window.cbbeL10n && cbbeL10n.saving_custom_fields) ? cbbeL10n.saving_custom_fields : 'Saving custom fields...');

				var editedRows = Array.prototype.slice.call(qsa('tr[data-cf-slug]')).filter(function (tr) { return tr.dataset.cbbeEdited === '1'; });

				var editPromises = editedRows.map(function (tr) {
					var form = qs('.cf-edit-form', tr);
					if (!form) { return Promise.resolve({ success: false, data: { message: 'Invalid edit form' } }); }
					var fd = new FormData();
					fd.append('action', 'cbbe_edit_custom_field_ajax');
					fd.append('edit_slug', form.querySelector('[name="edit_slug"]') ? form.querySelector('[name="edit_slug"]').value : '');
					fd.append('edit_label', form.querySelector('[name="edit_label"]') ? form.querySelector('[name="edit_label"]').value : '');
					fd.append('edit_type', form.querySelector('[name="edit_type"]') ? form.querySelector('[name="edit_type"]').value : '');
					if (form.querySelector('[name="edit_taxonomy"]')) { fd.append('edit_taxonomy', form.querySelector('[name="edit_taxonomy"]').value); }
					var nonceEl = form.querySelector('[name="cbbe_edit_custom_field_nonce"]');
					if (nonceEl) { fd.append('nonce', nonceEl.value); }
					return fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
					.then(function (r) { return r.json().catch(function () { return { success: false, data: { message: 'Invalid server response' } }; }); })
					.catch(function (err) { console.error('Edit fetch error:', err); return { success: false, data: { message: err.message || 'Request failed' } }; });
				});

				Promise.all(editPromises).then(function (results) {
					var failed = results.find(function (res) { return !res || !res.success; });
					if (failed) {
						var msg = (failed && failed.data && failed.data.message) ? failed.data.message : 'Error updating custom fields.';
						showMessage('error', msg);
						submitBtn.disabled = false;
						submitBtn.textContent = originalText;
						return;
					}

					var fd2 = new FormData(customFieldsForm);
					fd2.append('action', 'cbbe_save_custom_fields_ajax');
					var nonceField = qs('[name="cbbe_custom_fields_nonce"]', customFieldsForm);
					if (nonceField) { fd2.append('nonce', nonceField.value); }

					fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd2 })
					.then(function (r) { return r.json().catch(function () { return { success: false, data: { message: 'Invalid server response' } }; }); })
					.then(function (data) {
						submitBtn.disabled = false;
						submitBtn.textContent = originalText;
						if (data && data.success) {
							showMessage('success', (data.data && data.data.message) ? data.data.message : 'Saved.');
							customFieldsForm.reset();
							reloadCustomFieldsTable();
							setTimeout(clearMessage, 3000);
						} else {
							var err = (data && data.data && data.data.message) ? data.data.message : 'Error saving custom fields.';
							showMessage('error', err);
						}
					})
					.catch(function (err) {
						console.error('Error saving custom fields:', err);
						submitBtn.disabled = false;
						submitBtn.textContent = originalText;
						showMessage('error', 'An error occurred while saving.');
					});
				}).catch(function (err) {
					console.error('Error processing edits:', err);
					submitBtn.disabled = false;
					submitBtn.textContent = originalText;
					showMessage('error', 'An error occurred while updating edits.');
				});
			});
		}

		window.reloadCustomFieldsTable = function reloadCustomFieldsTable() {
			var container = qs(existingContainerSelector);
			if (!container) { return; }
			var fd = new FormData();
			fd.append('action', 'cbbe_reload_custom_fields_table');
			var nonceField = qs('[name="cbbe_reload_custom_fields_nonce"]');
			if (nonceField) { fd.append('nonce', nonceField.value); }
			fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
			.then(function (r) { return r.json().catch(function () { return null; }); })
			.then(function (data) {
				if (data && data.success && data.data && data.data.html) {
					container.innerHTML = data.data.html;
				}
			})
			.catch(function (err) { console.error('Error reloading custom fields table:', err); });
		};
	})();

	/* ==========================================================================
	FILTERS
	========================================================================== */

	(function () {
		var getEls = function () {
			return {
				termWrap: qs('#cbbe-product-term-wrap'),
				termSelect: qs('#product_term'),
				nonceEl: qs('#cbbe_filter_terms_nonce'),
			};
		};

		function resetTerms(termSelect) {
			if (!termSelect) { return; }
			termSelect.innerHTML = '<option value="">' + ((window.cbbeL10n && window.cbbeL10n.all) ? window.cbbeL10n.all : 'All') + '</option>';
		}

		function setLoading(termSelect, isLoading) {
			if (!termSelect) { return; }
			termSelect.disabled = isLoading;
			if (isLoading) {
				termSelect.innerHTML = '<option value="">' + ((window.cbbeL10n && window.cbbeL10n.loading) ? window.cbbeL10n.loading : 'Loading...') + '</option>';
			}
		}

		function fetchTermsForAttribute(attribute) {
			var els = getEls();
			if (!els.termWrap || !els.termSelect || !els.nonceEl) {
				return;
			}

			if (!attribute) {
				resetTerms(els.termSelect);
				els.termWrap.style.display = 'none';
				return;
			}

			els.termWrap.style.display = '';
			setLoading(els.termSelect, true);

			var data = new URLSearchParams();
			data.append('action', 'cbbe_get_attribute_terms');
			data.append('attribute', attribute);
			data.append('nonce', els.nonceEl.value);

			fetch(ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: data.toString(),
			})
			.then(function (r) { return r.json(); })
			.then(function (response) {
				resetTerms(els.termSelect);
				if (!response || !response.success || !response.data || !Array.isArray(response.data.terms)) {
					return;
				}
				response.data.terms.forEach(function (t) {
					var opt = document.createElement('option');
					opt.value = t.slug;
					opt.textContent = t.name;
					els.termSelect.appendChild(opt);
				});
			})
			.catch(function () {
				resetTerms(els.termSelect);
			})
			.finally(function () {
				if (els.termSelect) { els.termSelect.disabled = false; }
			});
		}

		document.addEventListener('change', function (e) {
			if (!e || !e.target) { return; }
			if (e.target.id === 'product_attribute') {
				fetchTermsForAttribute(e.target.value);
			}
		});

		document.addEventListener('click', function (e) {
			if (e.target && e.target.closest && e.target.closest('#open-filter-modal')) {
				var attrEl = qs('#product_attribute');
				if (attrEl && attrEl.value) {
					setTimeout(function () {
						fetchTermsForAttribute(attrEl.value);
					}, 50);
				}
			}
		});

		(function onInit() {
			var attrEl = qs('#product_attribute');
			if (attrEl && attrEl.value) {
				fetchTermsForAttribute(attrEl.value);
			}
		})();
	})();

});