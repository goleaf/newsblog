// Lazy QR code generator with error correction and download support
// Usage: window.generateQRCode('#qr-container', window.location.href, { errorCorrectionLevel: 'M' })

window.generateQRCode = async (selector, text, options = {}) => {
    try {
        const container = document.querySelector(selector);
        if (!container) { return; }
        const mod = await import('qrcode');
        const toCanvas = mod.toCanvas || mod.default?.toCanvas;
        if (!toCanvas) { 
            container.innerHTML = '<div class="text-sm text-gray-600">QR module missing</div>';
            return; 
        }
        const canvas = document.createElement('canvas');
        await toCanvas(canvas, text, {
            errorCorrectionLevel: options.errorCorrectionLevel || 'M',
            width: options.width || 256,
            margin: options.margin ?? 1,
            color: options.color || { dark: '#000000', light: '#FFFFFF' },
        });
        container.innerHTML = '';
        container.appendChild(canvas);
    } catch (e) {
        console.error('QR generation failed', e);
        const container = document.querySelector(selector);
        if (container) {
            container.innerHTML = '<div class="text-sm text-red-600">QR generation failed</div>';
        }
    }
};



