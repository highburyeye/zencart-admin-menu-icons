/* Copyright 2026 Tony Cradwick. SPDX-License-Identifier: GPL-2.0-only */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    const root = document.getElementById('ami-settings');
    if (!root) return;
    const rows = Array.from(root.querySelectorAll('.ami-row'));
    const search = document.getElementById('ami-search');
    const group = document.getElementById('ami-group');
    const only = document.getElementById('ami-only-favourites');
    const status = document.getElementById('ami-status');
    function filterRows() {
        const query = search.value.trim().toLocaleLowerCase();
        root.querySelectorAll('.ami-group').forEach(section => {
            let visible = 0;
            section.querySelectorAll('.ami-row').forEach(row => {
                row.hidden = (group.value !== '' && section.dataset.group !== group.value)
                    || (only.checked && row.dataset.favourite !== '1')
                    || !(row.dataset.name + ' ' + row.dataset.key).toLocaleLowerCase().includes(query);
                if (!row.hidden) visible++;
            });
            section.hidden = visible === 0;
        });
        document.getElementById('ami-empty').hidden = rows.some(row => !row.hidden);
    }
    search.addEventListener('input', filterRows);
    group.addEventListener('change', filterRows);
    only.addEventListener('change', filterRows);
    root.querySelectorAll('.ami-favourite-form').forEach(form => {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            const button = form.querySelector('button');
            const row = form.closest('.ami-row');
            if (button.disabled) return;
            button.disabled = true;
            status.textContent = ''; status.className = '';
            try {
                const post = new FormData(form);
                post.set('ami_response', 'json');
                const response = await fetch(form.action, {method: 'POST', body: post, credentials: 'same-origin', headers: {'Accept': 'application/json'}});
                const result = await response.json();
                if (!response.ok || !result.ok) throw new Error(result.error || root.dataset.networkError);
                button.setAttribute('aria-checked', String(result.favourite));
                form.elements.ami_favourite.value = result.favourite ? '0' : '1';
                row.dataset.favourite = result.favourite ? '1' : '0';
                row.querySelector('.ami-entry-name').classList.toggle('ami-is-favourite', result.favourite);
                if (window.AdminMenuIcons && window.AdminMenuIcons.setFavourite) window.AdminMenuIcons.setFavourite(result.key, result.favourite);
                filterRows();
            } catch (error) {
                status.textContent = error instanceof SyntaxError || error instanceof TypeError ? root.dataset.networkError : error.message;
                status.className = 'alert alert-danger';
                status.scrollIntoView({block: 'nearest'});
            } finally { button.disabled = false; }
        });
    });
    const picker = document.getElementById('ami-picker');
    if (!picker) return;
    const pickForm = document.getElementById('ami-pick-form');
    const uploadForm = document.getElementById('ami-upload-form');
    const iconSearch = document.getElementById('ami-icon-search');
    function filterIcons() {
        const query = iconSearch.value.trim().toLocaleLowerCase();
        picker.querySelectorAll('.ami-icon-option[data-kind="fa"]').forEach(button => {
            button.hidden = !button.textContent.toLocaleLowerCase().includes(query);
        });
    }
    root.querySelectorAll('.ami-change').forEach(button => {
        button.addEventListener('click', function () {
            const row = button.closest('.ami-row');
            pickForm.elements.ami_page.value = row.dataset.key;
            uploadForm.elements.ami_page.value = row.dataset.key;
            document.getElementById('ami-picker-entry').textContent = row.dataset.name;
            iconSearch.value = ''; filterIcons();
            picker.showModal();
            iconSearch.focus();
        });
    });
    picker.querySelector('.ami-close').addEventListener('click', () => picker.close());
    picker.addEventListener('click', event => {
        if (event.target !== picker) return;
        const rect = picker.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) picker.close();
    });
    iconSearch.addEventListener('input', filterIcons);
    picker.querySelectorAll('.ami-pick').forEach(button => {
        button.addEventListener('click', function () {
            pickForm.elements.ami_kind.value = button.dataset.kind;
            pickForm.elements.ami_value.value = button.dataset.value;
            pickForm.requestSubmit();
        });
    });
});
