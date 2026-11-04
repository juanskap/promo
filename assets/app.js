/* Utilidades del cliente: copiar, escanear QR y PWA. */
(function () {
    'use strict';

    /* Copiar texto -------------------------------------------------- */
    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', async function () {
            try {
                await navigator.clipboard.writeText(button.getAttribute('data-copy') || '');
                const original = button.textContent;
                button.textContent = '¡Copiado!';
                setTimeout(function () { button.textContent = original; }, 1600);
            } catch (error) {
                window.prompt('Copia este texto:', button.getAttribute('data-copy') || '');
            }
        });
    });

    document.querySelectorAll('[data-copy-target]').forEach(function (button) {
        button.addEventListener('click', async function () {
            const input = document.getElementById(button.getAttribute('data-copy-target'));
            if (!input) return;
            input.select();
            try {
                await navigator.clipboard.writeText(input.value);
            } catch (error) {
                document.execCommand('copy');
            }
            const original = button.textContent;
            button.textContent = '¡Copiado!';
            setTimeout(function () { button.textContent = original; }, 1600);
        });
    });

    /* Escáner de QR para el mostrador ------------------------------- */
    let qrScanner = null;
    let scanned = false;

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function goWithScan(text) {
        if (scanned) return;
        scanned = true;

        let code = '';
        try {
            const url = new URL(text, window.location.href);
            code = url.searchParams.get('code') || '';
        } catch (error) {
            code = '';
        }

        if (!code) {
            const match = String(text).match(/[A-Z0-9]{6,12}/i);
            if (match) code = match[0];
        }

        if (qrScanner) {
            qrScanner.stop().catch(function () {}).finally(function () { qrScanner.clear(); });
        }

        if (code) {
            window.location.href = 'index.php?code=' + encodeURIComponent(code);
        } else {
            window.alert('Ese QR no pertenece a una tarjeta.');
            scanned = false;
        }
    }

    document.querySelectorAll('[data-scan]').forEach(function (button) {
        button.addEventListener('click', async function () {
            const box = document.getElementById('scanner');
            if (!box) return;

            if (!box.hidden && qrScanner) {
                box.hidden = true;
                try { await qrScanner.stop(); } catch (error) {}
                return;
            }

            box.hidden = false;
            box.textContent = 'Preparando cámara…';
            scanned = false;

            try {
                if (!window.Html5Qrcode) {
                    await loadScript('https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js');
                }
                if (!window.Html5Qrcode) {
                    throw new Error('sin librería');
                }

                box.textContent = '';
                qrScanner = new Html5Qrcode('scanner');
                await qrScanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 230, height: 230 } },
                    goWithScan
                );
            } catch (error) {
                box.textContent = 'No se pudo abrir la cámara. Usa el celular, la cédula o el código del cliente.';
            }
        });
    });

    /* PWA ------------------------------------------------------------ */
    if ('serviceWorker' in navigator && (location.protocol === 'https:' || /^(localhost|127\.0\.0\.1)$/.test(location.hostname))) {
        window.addEventListener('load', function () {
            if (!window.APP_SW) return;
            navigator.serviceWorker.register(window.APP_SW).catch(function () {});
        });
    }
})();
