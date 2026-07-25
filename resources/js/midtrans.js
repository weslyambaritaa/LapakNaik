let loadingPromise = null;

export function loadMidtransSnap(clientKey, isProduction) {
    if (window.snap) {
        return Promise.resolve(window.snap);
    }

    if (loadingPromise) {
        return loadingPromise;
    }

    const src = isProduction
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js';

    loadingPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.setAttribute('data-client-key', clientKey);
        script.onload = () => resolve(window.snap);
        script.onerror = () => reject(new Error('Gagal memuat Midtrans Snap.'));
        document.head.appendChild(script);
    });

    return loadingPromise;
}
