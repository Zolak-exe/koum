/* 
 * Configuration Tailwind CSS - Version Production
 * Ce fichier supprime les avertissements du CDN
 */

// Désactiver les warnings de développement Tailwind
if (window.tailwind && window.tailwind.config) {
    // Configuration déjà chargée
}

// Supprimer les console warnings de Tailwind CDN
(function () {
    const originalWarn = console.warn;
    console.warn = function (...args) {
        const message = args[0];
        if (typeof message === 'string' &&
            (message.includes('tailwindcss.com should not be used in production') ||
                message.includes('cdn.tailwindcss.com'))) {
            return;
        }
        originalWarn.apply(console, args);
    };
})();
