/**
 * System Health Check
 * Vérifie que tous les composants du site fonctionnent correctement
 */

(function () {
    'use strict';

    const healthCheck = {
        results: [],

        // Vérifier le chargement de Tailwind
        checkTailwind: function () {
            const hasTailwind = typeof tailwind !== 'undefined' ||
                document.querySelector('script[src*="tailwindcss"]') !== null;
            this.results.push({
                name: 'Tailwind CSS',
                status: hasTailwind ? 'OK' : 'FAIL',
                message: hasTailwind ? 'Chargé' : 'Non chargé'
            });
        },

        // Vérifier l'authentification
        checkAuth: function () {
            const hasAuthManager = typeof checkAuthStatus === 'function';
            const isLoggedIn = sessionStorage.getItem('isLoggedIn') === 'true';
            this.results.push({
                name: 'Auth Manager',
                status: hasAuthManager ? 'OK' : 'FAIL',
                message: hasAuthManager ?
                    (isLoggedIn ? 'Utilisateur connecté' : 'Utilisateur non connecté') :
                    'Non chargé'
            });
        },

        // Vérifier les scripts essentiels
        checkScripts: function () {
            const scripts = [
                { name: 'console-manager.js', loaded: typeof NextDriveLogger !== 'undefined' },
                { name: 'auth-manager.js', loaded: typeof window.authManager !== 'undefined' },
                { name: 'script.js', loaded: typeof initAuthHandler !== 'undefined' }
            ];

            scripts.forEach(script => {
                this.results.push({
                    name: script.name,
                    status: script.loaded ? 'OK' : 'WARN',
                    message: script.loaded ? 'Chargé' : 'Non chargé (peut être normal)'
                });
            });
        },

        // Vérifier la connexion réseau
        checkNetwork: async function () {
            if (!navigator.onLine) {
                this.results.push({
                    name: 'Connexion réseau',
                    status: 'FAIL',
                    message: 'Hors ligne'
                });
                return;
            }

            try {
                const controller = new AbortController();
                const timeout = setTimeout(() => controller.abort(), 3000);

                // Déterminer le chemin correct de l'API
                const apiPath = window.location.pathname.includes('/pages/') ? '../api/account-manager.php' : 'api/account-manager.php';

                const response = await fetch(apiPath, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'check_session' }),
                    signal: controller.signal
                });

                clearTimeout(timeout);

                this.results.push({
                    name: 'Connexion serveur',
                    status: response.ok ? 'OK' : 'WARN',
                    message: response.ok ? 'Serveur accessible' : `Status ${response.status}`
                });
            } catch (error) {
                this.results.push({
                    name: 'Connexion serveur',
                    status: 'WARN',
                    message: error.name === 'AbortError' ? 'Timeout' : 'Erreur réseau'
                });
            }
        },

        // Vérifier le stockage local
        checkStorage: function () {
            try {
                const testKey = '__test__';
                sessionStorage.setItem(testKey, '1');
                sessionStorage.removeItem(testKey);
                this.results.push({
                    name: 'SessionStorage',
                    status: 'OK',
                    message: 'Fonctionnel'
                });
            } catch (e) {
                this.results.push({
                    name: 'SessionStorage',
                    status: 'FAIL',
                    message: 'Non disponible'
                });
            }
        },

        // Afficher le rapport
        displayReport: function () {
            console.group('🏥 HEALTH CHECK - NEXT DRIVE IMPORT');

            this.results.forEach(result => {
                const icon = result.status === 'OK' ? '✅' : result.status === 'WARN' ? '⚠️' : '❌';
                const color = result.status === 'OK' ? '#10b981' : result.status === 'WARN' ? '#f59e0b' : '#ef4444';

                console.log(
                    `%c${icon} ${result.name}:%c ${result.message}`,
                    `font-weight: bold; color: ${color}`,
                    'color: #6b7280'
                );
            });

            const failures = this.results.filter(r => r.status === 'FAIL').length;
            const warnings = this.results.filter(r => r.status === 'WARN').length;
            const success = this.results.filter(r => r.status === 'OK').length;

            console.log('\n' + '='.repeat(50));
            console.log(
                `%c📊 Résumé: ${success} OK | ${warnings} WARN | ${failures} FAIL`,
                'font-weight: bold; font-size: 14px'
            );

            if (failures === 0 && warnings === 0) {
                console.log('%c🎉 Tous les systèmes sont opérationnels !', 'color: #10b981; font-weight: bold; font-size: 16px');
            } else if (failures > 0) {
                console.log('%c⚠️ Certains systèmes nécessitent attention', 'color: #ef4444; font-weight: bold');
            }

            console.groupEnd();
        },

        // Lancer tous les tests
        runAll: async function () {
            this.results = [];
            this.checkTailwind();
            this.checkAuth();
            this.checkScripts();
            this.checkStorage();
            await this.checkNetwork();
            this.displayReport();
        }
    };

    // Exposer globalement
    window.healthCheck = healthCheck;

    // Exécuter automatiquement au chargement (uniquement en dev)
    if (window.location.hostname === 'localhost' ||
        window.location.hostname.includes('127.0.0.1') ||
        window.location.search.includes('debug=true')) {

        window.addEventListener('load', function () {
            setTimeout(() => {
                healthCheck.runAll();
            }, 1000);
        });
    }

    // Commande manuelle dans la console
    console.log('%cℹ️ Pour lancer un diagnostic: healthCheck.runAll()', 'color: #3b82f6; font-style: italic');

})();
