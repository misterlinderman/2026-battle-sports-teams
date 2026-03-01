/**
 * Battle Sports Platform — Roster management interface.
 *
 * Fetches teams/roster via REST API, renders editable table,
 * supports Add Player, Delete, and CSV import.
 *
 * Requires bspData.nonce and bspData.apiUrl from wp_localize_script.
 */
(function () {
    'use strict';

    const API = {
        getHeaders() {
            return {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.bspData?.nonce || '',
            };
        },

        async getTeams() {
            const res = await fetch(`${window.bspData.apiUrl}/teams`, {
                method: 'GET',
                headers: this.getHeaders(),
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Failed to fetch teams');
            return res.json();
        },

        async getRoster(teamId) {
            const res = await fetch(`${window.bspData.apiUrl}/teams/${teamId}/roster`, {
                method: 'GET',
                headers: this.getHeaders(),
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Failed to fetch roster');
            return res.json();
        },

        async createPlayer(teamId, player) {
            const res = await fetch(`${window.bspData.apiUrl}/teams/${teamId}/roster`, {
                method: 'POST',
                headers: this.getHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({
                    player_name: player.player_name,
                    player_number: player.player_number || '',
                    jersey_size: player.jersey_size || '',
                    short_size: player.short_size || '',
                }),
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || 'Failed to add player');
            }
            return res.json();
        },

        async deletePlayer(teamId, playerId) {
            const res = await fetch(`${window.bspData.apiUrl}/teams/${teamId}/roster/${playerId}`, {
                method: 'DELETE',
                headers: this.getHeaders(),
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Failed to delete player');
            return res.json();
        },

        async importRoster(teamId, players) {
            const res = await fetch(`${window.bspData.apiUrl}/teams/${teamId}/roster/import`, {
                method: 'POST',
                headers: this.getHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({ players }),
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || 'Failed to import roster');
            }
            return res.json();
        },
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function renderTeamSelector(teams, selectedId, onSelect) {
        const html = [
            '<select class="bsp-roster-manager__team-select" id="bsp-roster-team-select">',
            '<option value="">— Select a team —</option>',
            ...teams.map((t) =>
                `<option value="${escapeHtml(String(t.id))}" ${t.id === selectedId ? 'selected' : ''}>${escapeHtml(t.team_name || t.org_name)}</option>`
            ),
            '</select>',
        ].join('');
        return html;
    }

    function renderRosterTable(roster, teamId, onDelete) {
        const rows = roster.map(
            (p) => `
            <tr class="bsp-roster-manager__row" data-player-id="${escapeHtml(String(p.id))}">
                <td class="bsp-roster-manager__cell">${escapeHtml(p.player_name)}</td>
                <td class="bsp-roster-manager__cell">${escapeHtml(p.player_number)}</td>
                <td class="bsp-roster-manager__cell">${escapeHtml(p.jersey_size)}</td>
                <td class="bsp-roster-manager__cell">${escapeHtml(p.short_size)}</td>
                <td class="bsp-roster-manager__cell bsp-roster-manager__cell--actions">
                    <button type="button" class="bsp-roster-manager__btn bsp-roster-manager__btn--delete" data-player-id="${escapeHtml(String(p.id))}" aria-label="Delete player">Delete</button>
                </td>
            </tr>`
        );
        return `
            <table class="bsp-roster-manager__table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Number</th>
                        <th>Jersey Size</th>
                        <th>Short Size</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    ${rows.join('')}
                </tbody>
            </table>`;
    }

    function renderAddFormRow(teamId, onSubmit) {
        const form = document.createElement('tr');
        form.className = 'bsp-roster-manager__add-form';
        form.innerHTML = `
            <td class="bsp-roster-manager__cell">
                <input type="text" class="bsp-roster-manager__input bsp-roster-manager__input--name" placeholder="Player name" required>
            </td>
            <td class="bsp-roster-manager__cell">
                <input type="text" class="bsp-roster-manager__input bsp-roster-manager__input--number" placeholder="#" maxlength="10">
            </td>
            <td class="bsp-roster-manager__cell">
                <input type="text" class="bsp-roster-manager__input bsp-roster-manager__input--jersey" placeholder="Jersey size">
            </td>
            <td class="bsp-roster-manager__cell">
                <input type="text" class="bsp-roster-manager__input bsp-roster-manager__input--short" placeholder="Short size">
            </td>
            <td class="bsp-roster-manager__cell bsp-roster-manager__cell--actions">
                <button type="button" class="bsp-roster-manager__btn bsp-roster-manager__btn--save">Save</button>
                <button type="button" class="bsp-roster-manager__btn bsp-roster-manager__btn--cancel">Cancel</button>
            </td>`;
        const saveBtn = form.querySelector('.bsp-roster-manager__btn--save');
        const cancelBtn = form.querySelector('.bsp-roster-manager__btn--cancel');
        saveBtn.addEventListener('click', () => {
            const name = form.querySelector('.bsp-roster-manager__input--name').value.trim();
            if (!name) return;
            onSubmit({
                player_name: name,
                player_number: form.querySelector('.bsp-roster-manager__input--number').value.trim(),
                jersey_size: form.querySelector('.bsp-roster-manager__input--jersey').value.trim(),
                short_size: form.querySelector('.bsp-roster-manager__input--short').value.trim(),
            });
        });
        cancelBtn.addEventListener('click', () => form.remove());
        return form;
    }

    function parseCSV(text) {
        const lines = text.trim().split(/\r?\n/);
        if (lines.length === 0) return [];
        const headerLine = lines[0];
        const headers = headerLine.split(',').map((h) => h.trim().toLowerCase().replace(/\s+/g, '_'));
        const players = [];
        const colMap = {
            name: 'player_name',
            player_name: 'player_name',
            number: 'player_number',
            player_number: 'player_number',
            jersey_size: 'jersey_size',
            short_size: 'short_size',
        };

        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split(',').map((v) => v.trim());
            const row = { player_name: '', player_number: '', jersey_size: '', short_size: '' };
            headers.forEach((h, idx) => {
                const key = colMap[h] || h;
                if (key && row.hasOwnProperty(key)) row[key] = values[idx] ?? '';
            });
            if (String(row.player_name).trim()) {
                players.push({
                    player_name: String(row.player_name).trim(),
                    player_number: String(row.player_number || '').trim(),
                    jersey_size: String(row.jersey_size || '').trim(),
                    short_size: String(row.short_size || '').trim(),
                });
            }
        }
        return players;
    }

    function init(el) {
        if (!window.bspData?.nonce || !window.bspData?.apiUrl) {
            console.error('bspData.nonce and bspData.apiUrl are required.');
            return;
        }

        const teamSelect = el.querySelector('.bsp-roster-manager__team-select');
        const rosterContainer = el.querySelector('.bsp-roster-manager__roster');
        const addBtn = el.querySelector('.bsp-roster-manager__add-btn');
        const importBtn = el.querySelector('.bsp-roster-manager__import-btn');
        const importInput = el.querySelector('.bsp-roster-manager__import-input');
        const messageEl = el.querySelector('.bsp-roster-manager__message');
        const tableWrap = el.querySelector('.bsp-roster-manager__table-wrap');

        if (!teamSelect || !addBtn || !tableWrap) return;

        let teams = [];
        let currentTeamId = null;
        let currentRoster = [];

        function showMessage(msg, isError = false) {
            if (!messageEl) return;
            messageEl.textContent = msg;
            messageEl.className = 'bsp-roster-manager__message' + (isError ? ' bsp-roster-manager__message--error' : '');
            messageEl.hidden = false;
        }

        function hideMessage() {
            if (messageEl) messageEl.hidden = true;
        }

        async function loadTeams() {
            try {
                teams = await API.getTeams();
                teamSelect.innerHTML =
                    '<option value="">— Select a team —</option>' +
                    teams.map((t) => `<option value="${escapeHtml(String(t.id))}">${escapeHtml(t.team_name || t.org_name)}</option>`).join('');
            } catch (e) {
                showMessage('Could not load teams.', true);
            }
        }

        async function loadRoster(teamId) {
            if (!teamId) {
                const tbody = tableWrap?.querySelector('tbody');
                if (tbody) tbody.innerHTML = '';
                return;
            }
            try {
                currentRoster = await API.getRoster(teamId);
                const tbody = tableWrap?.querySelector('tbody');
                if (tbody) {
                    tbody.innerHTML = currentRoster
                        .map(
                            (p) => `
                        <tr class="bsp-roster-manager__row" data-player-id="${escapeHtml(String(p.id))}">
                            <td class="bsp-roster-manager__cell">${escapeHtml(p.player_name)}</td>
                            <td class="bsp-roster-manager__cell">${escapeHtml(p.player_number)}</td>
                            <td class="bsp-roster-manager__cell">${escapeHtml(p.jersey_size)}</td>
                            <td class="bsp-roster-manager__cell">${escapeHtml(p.short_size)}</td>
                            <td class="bsp-roster-manager__cell bsp-roster-manager__cell--actions">
                                <button type="button" class="bsp-roster-manager__btn bsp-roster-manager__btn--delete" data-player-id="${escapeHtml(String(p.id))}" aria-label="Delete player">Delete</button>
                            </td>
                        </tr>`
                        )
                        .join('');
                    tbody.querySelectorAll('.bsp-roster-manager__btn--delete').forEach((btn) => {
                        btn.addEventListener('click', () => handleDelete(teamId, parseInt(btn.dataset.playerId, 10)));
                    });
                }
            } catch (e) {
                showMessage('Could not load roster.', true);
            }
        }

        function handleDelete(teamId, playerId) {
            if (!confirm('Remove this player from the roster?')) return;
            API.deletePlayer(teamId, playerId)
                .then(() => {
                    showMessage('Player removed.');
                    loadRoster(teamId);
                })
                .catch((e) => showMessage(e.message || 'Delete failed.', true));
        }

        function showAddForm() {
            const existingForm = el.querySelector('.bsp-roster-manager__add-form');
            if (existingForm) return;
            const tbody = tableWrap?.querySelector('tbody');
            if (!tbody) return;
            const formRow = renderAddFormRow(currentTeamId, async (player) => {
                try {
                    await API.createPlayer(currentTeamId, player);
                    formRow.remove();
                    showMessage('Player added.');
                    loadRoster(currentTeamId);
                } catch (e) {
                    showMessage(e.message || 'Could not add player.', true);
                }
            });
            tbody.appendChild(formRow);
        }

        teamSelect.addEventListener('change', () => {
            const id = teamSelect.value ? parseInt(teamSelect.value, 10) : null;
            currentTeamId = id;
            hideMessage();
            if (id) loadRoster(id);
            else {
                rosterContainer.innerHTML = '';
                if (tableWrap) tableWrap.innerHTML = '';
            }
        });

        addBtn.addEventListener('click', () => {
            if (!currentTeamId) {
                showMessage('Select a team first.', true);
                return;
            }
            showAddForm();
        });

        if (importBtn && importInput) {
            importBtn.addEventListener('click', () => importInput.click());
            importInput.addEventListener('change', async (e) => {
                const file = e.target.files?.[0];
                e.target.value = '';
                if (!file || !currentTeamId) {
                    if (!currentTeamId) showMessage('Select a team first.', true);
                    return;
                }
                try {
                    const text = await file.text();
                    const players = parseCSV(text);
                    if (players.length === 0) {
                        showMessage('No valid players found in CSV.', true);
                        return;
                    }
                    const result = await API.importRoster(currentTeamId, players);
                    const errMsg = result.errors?.length ? ` ${result.errors.length} row(s) had errors.` : '';
                    showMessage(`Imported ${result.imported} player(s).${errMsg}`);
                    loadRoster(currentTeamId);
                } catch (err) {
                    showMessage(err.message || 'Import failed.', true);
                }
            });
        }

        loadTeams();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const managers = document.querySelectorAll('.bsp-roster-manager');
        managers.forEach((el) => init(el));
    });
})();
