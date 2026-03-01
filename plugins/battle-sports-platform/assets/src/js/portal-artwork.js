/**
 * Battle Sports Platform — Customer artwork approve/revision (portal dashboard).
 *
 * Handles Approve and Request Revision buttons for proof_sent items.
 * Uses bspData from wp_localize_script.
 */
(function () {
    'use strict';

    const API = {
        getHeaders() {
            return {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.bspData?.nonce || '',
                credentials: 'same-origin',
            };
        },

        async approve(artworkId) {
            const res = await fetch(`${window.bspData.apiUrl}/artwork/${artworkId}/approve`, {
                method: 'POST',
                headers: this.getHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({}),
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || 'Failed to approve');
            }
            return res.json();
        },

        async requestRevision(artworkId, notes) {
            const res = await fetch(`${window.bspData.apiUrl}/artwork/${artworkId}/revision`, {
                method: 'POST',
                headers: this.getHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({ notes: notes || '' }),
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || 'Failed to request revision');
            }
            return res.json();
        },
    };

    function init() {
        if (!window.bspData?.nonce || !window.bspData?.apiUrl) return;

        document.querySelectorAll('.bsp-portal-artwork__item').forEach((item) => {
            const id = parseInt(item.dataset.artworkId || '0', 10);
            if (!id) return;

            const approveBtn = item.querySelector('.bsp-portal-artwork__btn--approve');
            const revisionBtn = item.querySelector('.bsp-portal-artwork__btn--revision');
            const revisionForm = item.querySelector('.bsp-portal-artwork__revision-form');
            const revisionTextarea = item.querySelector('.bsp-portal-artwork__revision-notes');
            const revisionSubmitBtn = item.querySelector('.bsp-portal-artwork__btn--revision-submit');
            const revisionCancelBtn = item.querySelector('.bsp-portal-artwork__btn--revision-cancel');
            const messageEl = item.querySelector('.bsp-portal-artwork__message');

            function showMessage(msg, isError = false) {
                if (!messageEl) return;
                messageEl.textContent = msg;
                messageEl.className = 'bsp-portal-artwork__message' + (isError ? ' bsp-portal-artwork__message--error' : '');
                messageEl.hidden = false;
            }

            function hideRevisionForm() {
                if (revisionForm) revisionForm.hidden = true;
                if (revisionTextarea) revisionTextarea.value = '';
            }

            approveBtn?.addEventListener('click', async () => {
                if (!confirm('Approve this artwork? It will be marked as approved.')) return;
                try {
                    await API.approve(id);
                    item.remove();
                    const container = document.querySelector('.bsp-portal-artwork');
                    if (container && !container.querySelector('.bsp-portal-artwork__item')) {
                        container.innerHTML = '<p class="bsp-portal__empty">' +
                            (container.dataset.emptyText || 'No artwork pending approval.') + '</p>';
                    }
                } catch (e) {
                    showMessage(e.message || 'Could not approve.', true);
                }
            });

            revisionBtn?.addEventListener('click', () => {
                if (revisionForm) revisionForm.hidden = !revisionForm.hidden;
            });

            revisionCancelBtn?.addEventListener('click', hideRevisionForm);

            revisionSubmitBtn?.addEventListener('click', async () => {
                const notes = revisionTextarea?.value?.trim() || '';
                try {
                    await API.requestRevision(id, notes);
                    item.remove();
                    const container = document.querySelector('.bsp-portal-artwork');
                    if (container && !container.querySelector('.bsp-portal-artwork__item')) {
                        container.innerHTML = '<p class="bsp-portal__empty">' +
                            (container.dataset.emptyText || 'No artwork pending approval.') + '</p>';
                    }
                } catch (e) {
                    showMessage(e.message || 'Could not request revision.', true);
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
