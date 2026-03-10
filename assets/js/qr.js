document.addEventListener('DOMContentLoaded', () => {
    const startButton = document.getElementById('startScan');
    const codeInput = document.getElementById('productCode');
    const qrReaderElement = document.getElementById('qr-reader');

    if (!startButton || !codeInput || !qrReaderElement || typeof Html5Qrcode === 'undefined') {
        return;
    }

    let qrReader = null;

    startButton.addEventListener('click', async () => {
        try {
            if (!qrReader) {
                qrReader = new Html5Qrcode('qr-reader');
            }

            await qrReader.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                (decodedText) => {
                    codeInput.value = decodedText.trim();
                    qrReader
                        .stop()
                        .then(() => {
                            window.location.href = `product.php?code=${encodeURIComponent(decodedText.trim())}`;
                        })
                        .catch(() => {});
                }
            );

            startButton.disabled = true;
            startButton.textContent = 'Scansione attiva';
        } catch (error) {
            startButton.textContent = 'Camera non disponibile';
        }
    });
});
