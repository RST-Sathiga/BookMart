(function () {
    const beaconUrl = document.body.dataset.adminLogoutUrl;
    if (!beaconUrl) {
        return;
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a');
        if (!link) {
            return;
        }

        const href = link.getAttribute('href') || '';

        if (link.classList.contains('leave-admin') || href.includes('logout')) {
            return;
        }

        if (!href.startsWith('http') && !href.startsWith('//')) {
            sessionStorage.setItem('bookmartAdminNav', '1');
        }
    });

    window.addEventListener('pagehide', function () {
        if (sessionStorage.getItem('bookmartAdminNav') === '1') {
            sessionStorage.removeItem('bookmartAdminNav');
            return;
        }

        if (navigator.sendBeacon) {
            navigator.sendBeacon(beaconUrl);
        }
    });
})();
