/**
 * Console Logger - Gestionnaire de logs propre
 * Supprime les warnings inutiles et organise les logs
 */

(function () {
    'use strict';

    // Sauvegarder les méthodes originales
    const originalConsole = {
        log: console.log.bind(console),
        warn: console.warn.bind(console),
        error: console.error.bind(console),
        info: console.info.bind(console),
        debug: console.debug.bind(console)
    };

    // Liste des warnings à ignorer
    const ignoredWarnings = [
        'cdn.tailwindcss.com should not be used in production',
        'Permissions-Policy',
        'Feature is disabled',
        'browsing-topics',
        'run-ad-auction',
        'join-ad-interest-group',
        'private-state-token',
        'private-aggregation',
        'attribution-reporting'
    ];

    // Filtrer les warnings
    console.warn = function (...args) {
        const message = args.join(' ');

        // Vérifier si le message contient un warning à ignorer
        const shouldIgnore = ignoredWarnings.some(ignored =>
            message.toLowerCase().includes(ignored.toLowerCase())
        );

        if (!shouldIgnore) {
            originalConsole.warn.apply(console, args);
        }
    };

    // Filtrer les erreurs de parsing JSON non critiques
    const originalError = console.error.bind(console);
    console.error = function (...args) {
        const message = args.join(' ');

        // Ignorer les erreurs JSON de check session (normales si pas connecté)
        if (message.includes('Session check') ||
            (message.includes('JSON') && message.includes('check'))) {
            return; // Ne rien afficher
        }

        originalError.apply(console, args);
    };

    // Logger personnalisé pour le projet
    window.NextDriveLogger = {
        success: function (message) {
            originalConsole.log('%c✅ ' + message, 'color: #10b981; font-weight: bold');
        },
        error: function (message) {
            originalConsole.error('%c❌ ' + message, 'color: #ef4444; font-weight: bold');
        },
        warning: function (message) {
            originalConsole.warn('%c⚠️ ' + message, 'color: #f59e0b; font-weight: bold');
        },
        info: function (message) {
            originalConsole.info('%cℹ️ ' + message, 'color: #3b82f6; font-weight: bold');
        },
        debug: function (message) {
            if (window.location.hostname === 'localhost' ||
                window.location.hostname.includes('127.0.0.1')) {
                originalConsole.debug('%c🔍 ' + message, 'color: #8b5cf6; font-weight: bold');
            }
        }
    };

    // Log de démarrage
    originalConsole.log(
        '%c🚗 NEXT DRIVE IMPORT',
        'color: #FF6B35; font-size: 20px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.3)'
    );
    originalConsole.log(
        '%cVersion 2.1.0 - Console Logs Optimized',
        'color: #10b981; font-weight: bold'
    );

})();
