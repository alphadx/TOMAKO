(function () {
    var root = document.getElementById('ts-dashboard-root');
    if (!root) {
        return;
    }

    var refreshUrl = root.getAttribute('data-refresh-url');
    var refreshInterval = parseInt(root.getAttribute('data-refresh-interval') || '60000', 10);

    if (!refreshUrl || refreshInterval <= 0) {
        return;
    }

    function formatCurrency(value) {
        try {
            return new Intl.NumberFormat('es-CL', {
                style: 'currency',
                currency: 'CLP',
                maximumFractionDigits: 0,
            }).format(Number(value || 0));
        } catch (e) {
            return '$' + Number(value || 0).toLocaleString('es-CL');
        }
    }

    function updateKpis(payload) {
        if (!payload || typeof payload !== 'object') {
            return;
        }

        Object.keys(payload).forEach(function (key) {
            var holder = root.querySelector('[data-kpi="' + key + '"]');
            if (!holder) {
                return;
            }

            var valueNode = holder.querySelector('.ts-kpi-value');
            if (!valueNode) {
                return;
            }

            var format = holder.getAttribute('data-kpi-format');
            var value = payload[key];

            if (format === 'currency') {
                valueNode.textContent = formatCurrency(value);
                return;
            }

            valueNode.textContent = String(Number(value || 0));
        });
    }

    function refreshAll() {
        fetch(refreshUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (json) {
                if (!json || json.success !== true || !json.data) {
                    return;
                }
                updateKpis(json.data);
            })
            .catch(function () {
                // Silent fail: dashboard must remain usable even if refresh fails.
            });
    }

    window.setInterval(refreshAll, refreshInterval);
})();
