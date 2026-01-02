// ========================================
// NEXT DRIVE IMPORT - Nouveau flux devis
// Permet la demande de devis SANS compte
// Propose la création de compte APRÈS le devis
// ========================================

'use strict';

let submittedDevisData = null;

// ========== FUNCTIONS GLOBALES ==========
window.showAccountCreation = function () {
    document.getElementById('accountCreationProposal').style.display = 'none';
    document.getElementById('accountCreationForm').classList.remove('hidden');

    if (submittedDevisData) {
        document.getElementById('account_nom').value = submittedDevisData.nom;
        document.getElementById('account_email').value = submittedDevisData.email;
        document.getElementById('account_telephone').value = submittedDevisData.telephone;
    }
};

window.skipAccountCreation = function () {
    document.getElementById('successMessage').classList.add('hidden');
    document.getElementById('accountCreationForm').classList.add('hidden');
    document.getElementById('devisForm').reset();

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// ========== INIT ==========
document.addEventListener('DOMContentLoaded', function () {
    initDevisForm();
    initAccountCreationForm();
});

function initDevisForm() {
    const devisForm = document.getElementById('devisForm');
    if (!devisForm) return;

    // Pré-remplir si connecté
    if (sessionStorage.getItem('isLoggedIn') === 'true') {
        const nomInput = document.getElementById('nom');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('telephone');

        if (nomInput) nomInput.value = sessionStorage.getItem('userName') || '';
        if (emailInput) emailInput.value = sessionStorage.getItem('userEmail') || '';
        if (phoneInput) phoneInput.value = sessionStorage.getItem('userPhone') || '';
    }

    devisForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ Envoi en cours...';

        try {
            const formData = new FormData(this);

            // Construction des données
            const devisData = {
                nom: formData.get('nom'),
                email: formData.get('email'),
                telephone: formData.get('telephone'),
                vehicule: {
                    marque: formData.get('marque'),
                    modele: formData.get('modele'),
                    budget: parseFloat(formData.get('budget')),
                    annee_minimum: formData.get('annee_minimum') ? parseInt(formData.get('annee_minimum')) : null,
                    kilometrage_max: formData.get('kilometrage_max') ? parseInt(formData.get('kilometrage_max')) : null,
                    options: formData.get('options') || '',
                    commentaires: formData.get('commentaires') || ''
                },
                rgpd_consent: formData.get('rgpd') === 'on',
                source: 'website',
                timestamp: new Date().toISOString(),
                has_account: false // Par défaut, pas de compte
            };

            // Envoyer à l'API
            const apiPath = window.location.pathname.includes('/pages/') ? '../api/submit-devis.php' : 'api/submit-devis.php';
            const response = await fetch(apiPath, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(devisData)
            });

            if (!response.ok) {
                // Try to read body for diagnostics
                let bodyText = '';
                try { bodyText = await response.text(); } catch (_) { bodyText = ''; }
                throw new Error('Erreur serveur: ' + response.status + (bodyText ? ' - ' + bodyText : ''));
            }

            // Defensive parse: ensure response body is not empty and contains valid JSON
            let result = null;
            try {
                const text = await response.text();
                if (!text || !text.trim()) {
                    throw new Error('Empty response body');
                }
                result = JSON.parse(text);
            } catch (err) {
                throw new Error('Réponse API invalide ou vide: ' + err.message);
            }

            if (result.success) {
                // Stocker les données pour la création de compte
                submittedDevisData = {
                    nom: devisData.nom,
                    email: devisData.email,
                    telephone: devisData.telephone
                };

                // Cacher le formulaire et afficher le succès
                devisForm.classList.add('hidden');
                document.getElementById('successMessage').classList.remove('hidden');

                // Scroll vers le message de succès
                setTimeout(() => {
                    document.getElementById('successMessage').scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }, 300);

                // Reset form
                devisForm.reset();

            } else {
                throw new Error(result.message || 'Erreur lors de l\'envoi');
            }

        } catch (error) {
            console.error('Erreur devis:', error);
            alert('❌ Erreur lors de l\'envoi: ' + error.message + '\\n\\nVeuillez réessayer ou nous contacter directement par email.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

function initAccountCreationForm() {
    const createAccountForm = document.getElementById('createAccountForm');
    if (!createAccountForm) return;

    createAccountForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ Création en cours...';
        try {
            const apiPath = window.location.pathname.includes('/pages/') ? '../api/account-manager.php' : 'api/account-manager.php';

            // Générer un username à partir de l'email car requis par le backend
            const email = document.getElementById('account_email').value;
            const username = email.split('@')[0] + '_' + Math.floor(Math.random() * 1000);

            const response = await fetch(apiPath, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'register',
                    nom: document.getElementById('account_nom').value,
                    email: email,
                    telephone: document.getElementById('account_telephone').value,
                    username: username
                })
            });

            // Defensive parse for account-manager response
            let result = null;
            try {
                if (!response.ok) {
                    let bodyText = '';
                    try { bodyText = await response.text(); } catch (_) { bodyText = ''; }
                    throw new Error('Erreur serveur: ' + response.status + (bodyText ? ' - ' + bodyText : ''));
                }

                const text = await response.text();
                if (!text || !text.trim()) {
                    throw new Error('Empty response body');
                }
                result = JSON.parse(text);
            } catch (err) {
                throw new Error('Réponse API invalide ou vide: ' + err.message);
            }

            if (result && result.success) {
                // Enregistrer la session
                sessionStorage.setItem('isLoggedIn', 'true');
                sessionStorage.setItem('userName', result.account.nom);
                sessionStorage.setItem('userEmail', result.account.email);
                sessionStorage.setItem('clientId', result.account.id);
                sessionStorage.setItem('userRole', result.account.role || 'client');

                // Afficher message de succès
                const successDiv = document.createElement('div');
                successDiv.className = 'bg-green-900/30 border-2 border-green-500 rounded-xl p-6 text-center mb-4';
                successDiv.innerHTML = `
                    <div class="text-5xl mb-3">🎉</div>
                    <h4 class="text-xl font-bold mb-2">Compte créé avec succès !</h4>
                    <p class="text-gray-300 text-sm">Redirection vers votre espace client...</p>
                `;
                createAccountForm.insertAdjacentElement('beforebegin', successDiv);
                createAccountForm.style.display = 'none';

                // Redirection après 2 secondes
                setTimeout(() => {
                    window.location.href = 'pages/client.html';
                }, 2000);

            } else {
                throw new Error(result.message || 'Erreur lors de la création du compte');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}
