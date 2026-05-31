(function () {
    var badge = document.getElementById('notif-badge');
    if (!badge) {
        return;
    }

    var url = badge.getAttribute('data-count-url');
    if (!url) {
        return;
    }

    function refreshBadge() {
        fetch(url)
            .then(function (response) { return response.json(); })
            .then(function (data) {
                var count = Number(data.count || 0);
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : String(count);
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }
            })
            .catch(function () {
                // Silenciar errores de red para no interrumpir UX.
            });
    }

    setInterval(refreshBadge, 60000);
}());
