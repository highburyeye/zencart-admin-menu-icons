/* Copyright 2026 Tony Cradwick. SPDX-License-Identifier: GPL-2.0-only */
(function () {
    'use strict';
    if (window.AdminMenuIcons) return;
    const api = window.AdminMenuIcons = {};

    function ready() {
        const dataElement = document.getElementById('ami-menu-data');
        if (!dataElement) return;
        let data;
        try { data = JSON.parse(dataElement.textContent); } catch (_) { return; }
        const ignore = new Set(data.ignoreParams || []);
        function signature(href) {
            const url = new URL(href, document.baseURI);
            ignore.forEach(key => url.searchParams.delete(key));
            url.searchParams.sort();
            return url.origin + url.pathname + '?' + url.searchParams.toString();
        }
        const byHref = new Map();
        data.entries.forEach(entry => {
            const key = signature(entry.href);
            if (!byHref.has(key)) byHref.set(key, []);
            byHref.get(key).push(entry);
        });
        // Only registered dropdown entries: never top-level headings or upper navigation links.
        document.querySelectorAll('.navbar-adm1-collapse .dropdown-menu > li > a').forEach(anchor => {
            const candidates = byHref.get(signature(anchor.href));
            if (!candidates || candidates.length === 0) return;
            const entry = candidates.shift();
            anchor.classList.add('ami-menu-entry');
            anchor.classList.toggle('ami-is-favourite', entry.favourite);
            anchor.dataset.amiKey = entry.key;
            const slot = document.createElement('span');
            slot.className = 'ami-icon-slot';
            slot.setAttribute('aria-hidden', 'true');
            if (entry.icon.kind === 'fa' && /^[a-z0-9-]+$/.test(entry.icon.value)) {
                const icon = document.createElement('i');
                icon.className = 'fa-solid fa-' + entry.icon.value;
                slot.appendChild(icon);
            } else if (entry.icon.kind === 'image') {
                const src = data.images[entry.icon.value];
                if (typeof src === 'string' && /^data:image\/png;base64,[A-Za-z0-9+/=]+$/.test(src)) {
                    const image = document.createElement('img');
                    image.src = src; image.alt = ''; image.width = 16; image.height = 16;
                    slot.appendChild(image);
                }
            }
            const text = document.createElement('span');
            text.className = 'ami-menu-text';
            while (anchor.firstChild) text.appendChild(anchor.firstChild);
            anchor.append(slot, text);
        });
        api.setFavourite = function (key, favourite) {
            document.querySelectorAll('.ami-menu-entry').forEach(anchor => {
                if (anchor.dataset.amiKey === key) anchor.classList.toggle('ami-is-favourite', favourite);
            });
        };
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
    else ready();
}());
