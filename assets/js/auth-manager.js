/**
 * Gestionnaire d'authentification global
 * À inclure sur toutes les pages du site
 */

// Fonction pour vérifier le statut d'authentification
function checkAuthStatus() {
    const isLoggedIn = sessionStorage.getItem('isLoggedIn') === 'true';
    const userName = sessionStorage.getItem('userName');
    const userRole = sessionStorage.getItem('userRole');

    // Éléments à afficher/masquer
    const loggedOutElements = document.querySelectorAll('.auth-only-logged-out');
    const loggedInElements = document.querySelectorAll('.auth-only-logged-in');
    
    // Éléments spécifiques aux rôles
    const adminElements = document.querySelectorAll('.auth-role-admin');
    const clientElements = document.querySelectorAll('.auth-role-client');
    const vendeurElements = document.querySelectorAll('.auth-role-vendeur');

    // Masquer tous les éléments spécifiques aux rôles par défaut
    adminElements.forEach(el => {
        el.classList.add('hidden');
        if (el.style) el.style.display = 'none';
    });
    clientElements.forEach(el => {
        el.classList.add('hidden');
        if (el.style) el.style.display = 'none';
    });
    vendeurElements.forEach(el => {
        el.classList.add('hidden');
        if (el.style) el.style.display = 'none';
    });

    if (isLoggedIn) {
        // Masquer les liens de connexion/inscription
        loggedOutElements.forEach(el => {
            el.style.display = 'none';
        });

        // Afficher les liens espace client/déconnexion génériques
        loggedInElements.forEach(el => {
            el.classList.remove('hidden');
            if (el.style) el.style.display = '';
        });

        // Afficher les éléments spécifiques au rôle
        if (userRole === 'admin') {
            adminElements.forEach(el => {
                el.classList.remove('hidden');
                if (el.style) el.style.display = '';
            });
        } else if (userRole === 'client') {
            clientElements.forEach(el => {
                el.classList.remove('hidden');
                if (el.style) el.style.display = '';
            });
        } else if (userRole === 'vendeur') {
            vendeurElements.forEach(el => {
                el.classList.remove('hidden');
                if (el.style) el.style.display = '';
            });
        }

        console.log('✅ Utilisateur connecté:', userName, 'Rôle:', userRole);
    } else {
        // Afficher les liens de connexion/inscription
        loggedOutElements.forEach(el => {
            el.style.display = '';
        });

        // Masquer les liens espace client/déconnexion
        loggedInElements.forEach(el => {
            el.classList.add('hidden');
            if (el.style) el.style.display = 'none';
        });

        console.log('❌ Utilisateur non connecté');
    }

    return isLoggedIn;
}

// Fonction de déconnexion globale
function logout() {
    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        // Supprimer les données de session
        sessionStorage.removeItem('isLoggedIn');
        sessionStorage.removeItem('userName');
        sessionStorage.removeItem('userEmail');
        sessionStorage.removeItem('clientId');
        sessionStorage.removeItem('userRole');

        // Faire une requête au serveur pour détruire la session PHP
        const apiPath = window.location.pathname.includes('/pages/') ? '../api/account-manager.php' : 'api/account-manager.php';
        fetch(apiPath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        }).then(() => {
            // Rafraîchir l'affichage
            checkAuthStatus();

            // Rediriger vers l'accueil
            window.location.href = window.location.pathname.includes('/pages/') ? '../index.html' : 'index.html';
        }).catch(error => {
            console.error('Erreur lors de la déconnexion:', error);
            // Même en cas d'erreur, on déconnecte côté client
            checkAuthStatus();
            window.location.href = window.location.pathname.includes('/pages/') ? '../index.html' : 'index.html';
        });
    }
}

// Fonction pour obtenir les informations utilisateur
function getUserInfo() {
    if (sessionStorage.getItem('isLoggedIn') === 'true') {
        return {
            isLoggedIn: true,
            userName: sessionStorage.getItem('userName'),
            userEmail: sessionStorage.getItem('userEmail'),
            clientId: sessionStorage.getItem('clientId')
        };
    }
    return {
        isLoggedIn: false
    };
}

// Fonction pour rediriger vers login si non connecté
function requireAuth(redirectTo = 'login.html') {
    if (sessionStorage.getItem('isLoggedIn') !== 'true') {
        // Sauvegarder l'URL actuelle pour revenir après login
        sessionStorage.setItem('redirectAfterLogin', window.location.href);
        window.location.href = redirectTo;
        return false;
    }
    return true;
}

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', function () {
    checkAuthStatus();
});

// Vérifier quand la page devient visible (retour d'un autre onglet)
document.addEventListener('visibilitychange', function () {
    if (!document.hidden) {
        checkAuthStatus();
    }
});

// Vérifier quand le stockage change (connexion depuis un autre onglet)
window.addEventListener('storage', function (e) {
    if (e.key === 'isLoggedIn') {
        checkAuthStatus();
    }
});

// Exporter les fonctions pour usage global
window.authManager = {
    checkAuthStatus,
    logout,
    getUserInfo,
    requireAuth
};
