document.addEventListener('DOMContentLoaded', () => {
    const mapNode = document.getElementById('map');
    const buoys = window.BUOYS_DATA || [];

    if (!mapNode || typeof L === 'undefined' || !Array.isArray(buoys)) {
        return;
    }

    const map = L.map('map').setView([41.1171, 16.8719], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    buoys.forEach((b) => {
        const marker = L.marker([Number(b.lat), Number(b.lng)]).addTo(map);
        marker.bindPopup(
            `<strong>${b.name}</strong><br>${b.zone}<br>Stato: ${b.status}<br>Ultimo update: ${b.last_update}`
        );
    });
});
