/**
 * Battle Sports Platform — Artwork queue (designer-facing).
 *
 * Fetches artwork via REST API, tabbed interface, claim/upload proof/mark complete,
 * 60s polling. Uses bspData from wp_localize_script.
 */
(function () {
    'use strict';

    const POLL_INTERVAL_MS = 60000;
    const TAB_HASH_PREFIX = '#tab-';

    const STATUS_LABELS = {
        submitted: 'Submitted',
        in_queue: 'In Queue',
        in_progress: 'In Progress',
        proof_sent: 'Proof Sent',
        revision_requested: 'Awaiting Revision',
        approved: 'Approved',
        complete: 'Complete',
    };

    const STATUS_CLASSES = {
        submitted: 'bsp-artwork-queue__badge--submitted',
        in_queue: 'bsp-artwork-queue__badge--in-queue',
        in_progress: 'bsp-artwork-queue__badge--in-progress',
        proof_sent: 'bsp-artwork-queue__badge--proof-sent',
        revision_requested: 'bsp-artwork-queue__badge--awaiting-revision',
        approved: 'bsp-artwork-queue__badge--approved',
        complete: 'bsp-artwork-queue__badge--complete',
    };

    const API = {
        getHeaders(json = true) {
            const h = {
                'X-WP-Nonce': window.bspData?.nonce || '',
                credentials: 'same-origin',
            };
            if (json) h['Content-Type'] = 'application/json';
            return h;
        },

        async getArtwork(params = {}) {
            const q = new URLSearchParams();
            if (params.status) q.set('status', params.status);
            if (params.designer_id) q.set('designer_id', String(params.designer_id));
            if (params.unassigned) q.set('unassigned', '1');
            if (params.date_from) q.set('date_from', params.date_from);
            if (params.date_to) q.set('date_to', params.date_to);
            if (params.page) q.set('page', String(params.page));
            if (params.per_page) q.set('per_page', String(params.per_page));

            const url = `${window.bspData.apiUrl}/artwork${q.toString() ? '?' + q.toString() : ''}`;
            const res = await fetch(url, {
                method: 'GET',
                headers: { 'X-WP-Nonce': window.bspData?.nonce || '', credentials: 'same-origin' },
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || 'Failed to fetch artwork');
            }
            return res.json();
        },

        async patchStatus(artworkId, status, notes = '') {
            const res = await fetch(`${window.bspData.apiUrl}/artwork/${artworkId}/status`, {
                method: 'PATCH',
                headers: this.getHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({ status, notes }),
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || 'Failed to update status');
            }
            return res.json();
        },

        async uploadProof(artworkId, file) {
            const formData = new FormData();
            formData.append('file', file);

            const res = await fetch(`${window.bspData.apiUrl}/artwork/${artworkId}/proof`, {
                method: 'POST',
                headers: { 'X-WP-Nonce': window.bspData?.nonce || '' },
                credentials: 'same-origin',
                body: formData,
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || 'Failed to upload proof');
            }
            return res.json();
        },
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function statusBadge(status) {
        const label = STATUS_LABELS[status] || status;
        const cls = STATUS_CLASSES[status] || '';
        return `<span class="bsp-artwork-queue__badge ${cls}">${escapeHtml(label)}</span>`;
    }

    function formatDate(str) {
        if (!str) return '—';
        try {
            const d = new Date(str);
            return isNaN(d.getTime()) ? str : d.toLocaleDateString();
        } catch (_) {
            return str;
        }
    }

    function actionButtons(item, currentUserId) {
        const assignId = item.assigned_designer_id ? parseInt(item.assigned_designer_id, 10) : 0;
        const isAssignedToMe = assignId === currentUserId;
        const canClaim = item.status === 'in_queue' && !assignId && currentUserId > 0;
        const canUploadProof = (item.status === 'in_progress' || item.status === 'revision_requested') && (isAssignedToMe || currentUserId <= 0);
        const canMarkComplete = item.status === 'approved';

        const btns = [];
        if (canClaim) {
            btns.push(`<button type="button" class="bsp-artwork-queue__btn bsp-artwork-queue__btn--claim" data-id="${escapeHtml(String(item.id))}">Claim</button>`);
        }
        if (canUploadProof) {
            btns.push(`<button type="button" class="bsp-artwork-queue__btn bsp-artwork-queue__btn--upload-proof" data-id="${escapeHtml(String(item.id))}" data-order-ref="${escapeHtml(String(item.order_ref || ''))}">Upload Proof</button>`);
        }
        if (canMarkComplete) {
            btns.push(`<button type="button" class="bsp-artwork-queue__btn bsp-artwork-queue__btn--complete" data-id="${escapeHtml(String(item.id))}">Mark Complete</button>`);
        }
        if (btns.length === 0) return '—';
        return btns.join(' ');
    }

    function renderTableRows(items, currentUserId) {
        return items.map((item) => {
            const teamName = item.team_name || item.org_name || '—';
            const assigned = item.assigned_display_name ? escapeHtml(item.assigned_display_name) : '—';
            const actions = actionButtons(item, currentUserId);
            return `
                <tr class="bsp-artwork-queue__row" data-id="${escapeHtml(String(item.id))}">
                    <td class="bsp-artwork-queue__cell">${escapeHtml(item.order_ref || '')}</td>
                    <td class="bsp-artwork-queue__cell">${escapeHtml(teamName)}</td>
                    <td class="bsp-artwork-queue__cell">${escapeHtml(item.product_type || '')}</td>
                    <td class="bsp-artwork-queue__cell">${formatDate(item.submitted_at)}</td>
                    <td class="bsp-artwork-queue__cell">${statusBadge(item.status)}</td>
                    <td class="bsp-artwork-queue__cell">${assigned}</td>
                    <td class="bsp-artwork-queue__cell bsp-artwork-queue__cell--actions">${actions}</td>
                </tr>`;
        }).join('');
    }

    function init(el) {
        if (!window.bspData?.nonce || !window.bspData?.apiUrl) {
            console.error('bspData.nonce and bspData.apiUrl are required.');
            return;
        }

        const currentUserId = parseInt(window.bspData.currentUserId || '0', 10);
        const tabs = el.querySelectorAll('.bsp-artwork-queue__tab');
        const assignmentSelect = el.querySelector('#bsp-artwork-assignment');
        const dateFrom = el.querySelector('#bsp-artwork-date-from');
        const dateTo = el.querySelector('#bsp-artwork-date-to');
        const messageEl = el.querySelector('.bsp-artwork-queue__message');
        const tbody = el.querySelector('.bsp-artwork-queue__table tbody');
        const emptyEl = el.querySelector('.bsp-artwork-queue__empty');
        const modal = el.querySelector('#bsp-artwork-upload-modal');
        const uploadForm = el.querySelector('#bsp-artwork-upload-form');
        const uploadFileInput = el.querySelector('#bsp-artwork-proof-file');
        const uploadIdInput = el.querySelector('#bsp-artwork-upload-id');
        const modalOrderRef = modal?.querySelector('.bsp-artwork-queue__modal-order-ref');
        const modalCancelBtn = modal?.querySelector('.bsp-artwork-queue__btn--cancel');

        let currentTab = '';
        let pollTimer = null;

        function getFilters() {
            const assignment = assignmentSelect?.value || '';
            let designerId = null;
            let unassigned = false;
            if (assignment === 'me' && currentUserId > 0) {
                designerId = currentUserId;
            } else if (assignment === 'unassigned') {
                unassigned = true;
            }

            const params = {
                status: currentTab || undefined,
                page: 1,
                per_page: 50,
            };
            if (designerId) params.designer_id = designerId;
            if (unassigned) params.unassigned = true;
            if (dateFrom?.value) params.date_from = dateFrom.value;
            if (dateTo?.value) params.date_to = dateTo.value;

            return params;
        }

        function showMessage(msg, isError = false) {
            if (!messageEl) return;
            messageEl.textContent = msg;
            messageEl.className = 'bsp-artwork-queue__message' + (isError ? ' bsp-artwork-queue__message--error' : '');
            messageEl.hidden = false;
        }

        function hideMessage() {
            if (messageEl) messageEl.hidden = true;
        }

        function syncHash() {
            const hash = currentTab ? `${TAB_HASH_PREFIX}${currentTab}` : TAB_HASH_PREFIX.slice(0, -1);
            if (window.location.hash !== hash) {
                window.history.replaceState(null, '', window.location.pathname + (hash ? hash : ''));
            }
        }

        function parseHash() {
            const hash = window.location.hash || '';
            if (hash.startsWith(TAB_HASH_PREFIX)) {
                return hash.slice(TAB_HASH_PREFIX.length) || '';
            }
            return '';
        }

        function setActiveTab(tabValue) {
            currentTab = tabValue;
            tabs.forEach((t) => {
                const isActive = (t.dataset.tab || '') === (tabValue || '');
                t.classList.toggle('bsp-artwork-queue__tab--active', isActive);
                t.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            syncHash();
        }

        async function loadQueue() {
            try {
                const params = getFilters();
                const data = await API.getArtwork(params);
                if (tbody) {
                    tbody.innerHTML = renderTableRows(data.items || [], currentUserId);
                }
                if (emptyEl) {
                    emptyEl.hidden = (data.items?.length || 0) > 0;
                }
                hideMessage();

                el.querySelectorAll('.bsp-artwork-queue__btn--claim').forEach((btn) => {
                    btn.addEventListener('click', () => handleClaim(parseInt(btn.dataset.id, 10)));
                });
                el.querySelectorAll('.bsp-artwork-queue__btn--upload-proof').forEach((btn) => {
                    btn.addEventListener('click', () => openUploadModal(parseInt(btn.dataset.id, 10), btn.dataset.orderRef || ''));
                });
                el.querySelectorAll('.bsp-artwork-queue__btn--complete').forEach((btn) => {
                    btn.addEventListener('click', () => handleMarkComplete(parseInt(btn.dataset.id, 10)));
                });
            } catch (e) {
                showMessage(e.message || 'Could not load artwork queue.', true);
                if (tbody) tbody.innerHTML = '';
                if (emptyEl) emptyEl.hidden = true;
            }
        }

        async function handleClaim(id) {
            try {
                await API.patchStatus(id, 'in_progress');
                showMessage('Claimed successfully.');
                loadQueue();
            } catch (e) {
                showMessage(e.message || 'Could not claim.', true);
            }
        }

        async function handleMarkComplete(id) {
            try {
                await API.patchStatus(id, 'complete');
                showMessage('Marked complete.');
                loadQueue();
            } catch (e) {
                showMessage(e.message || 'Could not mark complete.', true);
            }
        }

        function openUploadModal(id, orderRef) {
            if (!modal || !uploadIdInput || !uploadFileInput) return;
            uploadIdInput.value = String(id);
            modalOrderRef.textContent = orderRef ? `Order: ${orderRef}` : '';
            uploadFileInput.value = '';
            modal.hidden = false;
            uploadFileInput.focus();
        }

        function closeUploadModal() {
            if (modal) modal.hidden = true;
        }

        uploadForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = parseInt(uploadIdInput?.value || '0', 10);
            const file = uploadFileInput?.files?.[0];
            if (!id || !file) {
                showMessage('Please select a file.', true);
                return;
            }
            try {
                await API.uploadProof(id, file);
                showMessage('Proof uploaded successfully.');
                closeUploadModal();
                loadQueue();
            } catch (err) {
                showMessage(err.message || 'Upload failed.', true);
            }
        });

        modalCancelBtn?.addEventListener('click', closeUploadModal);
        modal?.querySelector('.bsp-artwork-queue__modal-backdrop')?.addEventListener('click', closeUploadModal);

        tabs.forEach((t) => {
            t.addEventListener('click', () => {
                setActiveTab(t.dataset.tab || '');
                loadQueue();
            });
        });

        assignmentSelect?.addEventListener('change', loadQueue);
        dateFrom?.addEventListener('change', loadQueue);
        dateTo?.addEventListener('change', loadQueue);

        window.addEventListener('hashchange', () => {
            const tab = parseHash();
            if (tab !== currentTab) {
                setActiveTab(tab);
                loadQueue();
            }
        });

        function startPolling() {
            if (pollTimer) clearInterval(pollTimer);
            pollTimer = setInterval(loadQueue, POLL_INTERVAL_MS);
        }

        setActiveTab(parseHash() || currentTab);
        loadQueue();
        startPolling();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const queues = document.querySelectorAll('.bsp-artwork-queue');
        queues.forEach((el) => init(el));
    });
})();
