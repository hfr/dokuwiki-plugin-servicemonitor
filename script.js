(function () {
    'use strict';

    window.updateServiceStatus = function () {
        const elements = document.querySelectorAll(
            '.servicemonitor-status[data-service]'
        );

        if (!elements.length) {
            console.warn('[service-monitor] Keine Status-Elemente gefunden.');
            return;
        }

        const url = DOKU_BASE +
            'lib/exe/ajax.php?call=plugin_servicemonitor_status';

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store'
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            return response.json();
        })
        .then(function (data) {
            if (!data.success || !Array.isArray(data.services)) {
                throw new Error('Ungültige AJAX-Antwort');
            }

            const statusMap = new Map();

            data.services.forEach(function (service) {
                statusMap.set(service.name, service.status);
            });

            elements.forEach(function (element) {
                const name = element.dataset.service;
                const status = statusMap.get(name) || 'unknown';

                element.classList.remove(
                    'status-active',
                    'status-inactive',
                    'status-unknown'
                );

                if (status === 'active') {
                    element.textContent = 'aktiv';
                    element.classList.add('status-active');
                } else if (status === 'inactive') {
                    element.textContent = 'inaktiv';
                    element.classList.add('status-inactive');
                } else {
                    element.textContent = status;
                    element.classList.add('status-unknown');
                }
            });

        })
        .catch(function (error) {
            console.error('[service-monitor] AJAX-Fehler:', error);

            elements.forEach(function (element) {
                element.textContent = 'Fehler';
                element.classList.add('status-unknown');
            });
        });
    };
})();

