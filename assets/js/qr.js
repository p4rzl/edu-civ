document.addEventListener('DOMContentLoaded', () => {
    const startButton = document.getElementById('startScan');
    const codeInput = document.getElementById('productCode');
    const qrReaderElement = document.getElementById('qr-reader');
    const scanModal = document.getElementById('scanModal');

    if (!startButton || !codeInput || !qrReaderElement || !scanModal || typeof Html5Qrcode === 'undefined') {
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

    startButton.addEventListener('click', async () => {
        try {
            scanModal.classList.add('open');
            scanModal.setAttribute('aria-hidden', 'false');
            await startScan();
        } catch (error) {
            startButton.textContent = 'Camera non disponibile';
        }
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
