document.addEventListener('DOMContentLoaded', () => {
    if (typeof QRCode === 'undefined') {
        return;
    }

    const miniNodes = document.querySelectorAll('.qr-mini');
    miniNodes.forEach((node) => {
        const text = node.getAttribute('data-qr-text');
        if (!text) {
            return;
        }
        node.innerHTML = '';
        new QRCode(node, {
            text,
            width: 64,
            height: 64,
        });
    });

    const modal = document.getElementById('qrModal');
    const modalCanvas = document.getElementById('qrModalCanvas');
    const closeButton = document.getElementById('closeQrModal');

    const openModal = (text) => {
        if (!modal || !modalCanvas) {
            return;
        }

        modalCanvas.innerHTML = '';
        new QRCode(modalCanvas, {
            text,
            width: 280,
            height: 280,
        });

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = () => {
        if (!modal) {
            return;
        }
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    };

    document.querySelectorAll('.qr-enlarge').forEach((button) => {
        button.addEventListener('click', () => {
            const text = button.getAttribute('data-qr-text');
            if (text) {
                openModal(text);
            }
        });
    });

    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }
});
