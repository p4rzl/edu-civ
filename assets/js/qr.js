document.addEventListener('DOMContentLoaded', () => {
    const startButtons = document.querySelectorAll('[data-scan-open]');
    const codeInput = document.getElementById('productCode');
    const qrReaderElement = document.getElementById('qr-reader');
    const scanModal = document.getElementById('scanModal');

    if (!startButtons.length || !codeInput || !qrReaderElement || !scanModal || typeof Html5Qrcode === 'undefined') {
        return;
    }

    let qrReader = null;
    let scanning = false;

    const stopScan = async () => {
        if (scanning && qrReader) {
            await qrReader.stop();
            scanning = false;
        }
    };

    const startScan = async () => {
        if (!qrReader) {
            qrReader = new Html5Qrcode('qr-reader');
        }

        await qrReader.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 220, height: 220 } },
            (decodedText) => {
                codeInput.value = decodedText.trim();
                stopScan()
                    .then(() => {
                        scanModal.classList.remove('open');
                        scanModal.setAttribute('aria-hidden', 'true');
                        window.location.href = `product.php?code=${encodeURIComponent(decodedText.trim())}`;
                    })
                    .catch(() => {});
            }
        );
        scanning = true;
    };

    const openScanner = async () => {
        try {
            scanModal.classList.add('open');
            scanModal.setAttribute('aria-hidden', 'false');
            await startScan();
        } catch (error) {
            startButtons.forEach((button) => {
                if (button instanceof HTMLButtonElement) {
                    button.disabled = true;
                }
            });
        }
    };

    startButtons.forEach((button) => {
        button.addEventListener('click', openScanner);
    });

    scanModal.addEventListener('modal:closed', () => {
        stopScan().catch(() => {});
    });

    scanModal.addEventListener('click', (event) => {
        const target = event.target;
        if (target instanceof Element && target.id === 'scanModal') {
            scanModal.classList.remove('open');
            scanModal.setAttribute('aria-hidden', 'true');
            stopScan().catch(() => {});
        }
    });
});
