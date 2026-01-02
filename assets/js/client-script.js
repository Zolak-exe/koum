// ========================================
// NEXT DRIVE IMPORT - Client Dashboard
// ========================================

'use strict';

let currentClient = null;

// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', function () {
    checkAuthSession();
    initParticles();
});

// ========== CHECK AUTH SESSION ==========
async function checkAuthSession() {
    try {
        const response = await fetch('../api/account-manager.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'check_session' })
        });

        // Defensive parsing
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) throw new Error('Response not JSON');
        const result = await response.json();

        if (result.authenticated && result.user) {
            currentClient = result.user;
            showDashboard();
        } else {
            showLogin();
        }
    } catch (error) {
        console.error('Session check error:', error);
        showLogin();
    }
}

// ========== SHOW/HIDE SECTIONS ==========
function showLogin() {
    const loginSection = document.getElementById('loginSection');
    const dashboardSection = document.getElementById('dashboardSection');

    if (loginSection) loginSection.classList.remove('hidden');
    if (dashboardSection) dashboardSection.classList.add('hidden');
}

function showDashboard() {
    const loginSection = document.getElementById('loginSection');
    const dashboardSection = document.getElementById('dashboardSection');

    if (loginSection) loginSection.classList.add('hidden');
    if (dashboardSection) dashboardSection.classList.remove('hidden');

    if (currentClient) {
        // Update client name displays
        const nameElements = document.querySelectorAll('#clientName, #dashboardName');
        nameElements.forEach(el => {
            el.textContent = currentClient.nom || currentClient.email;
        });

        // Load client requests
        loadClientRequests();
    }
}

// ========== LOAD CLIENT REQUESTS ==========
async function loadClientRequests() {
    if (!currentClient) return;

    try {
        const response = await fetch('../api/get-clients.php');

        // Defensive parsing
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) throw new Error('Response not JSON');
        const clients = await response.json();

        if (!Array.isArray(clients)) {
            console.error('Format de réponse invalide:', clients);
            throw new Error('La réponse du serveur n\'est pas une liste valide');
        }

        // L'API filtre déjà les résultats pour l'utilisateur connecté
        // On utilise directement les données reçues
        const clientRequests = clients;

        displayRequests(clientRequests);
        updateStats(clientRequests);
    } catch (error) {
        console.error('Error loading requests:', error);
        document.getElementById('requestsList').innerHTML = `
            <div class="text-center text-red-400 py-8">
                Erreur lors du chargement des demandes
            </div>
        `;
    }
}

// ========== DISPLAY REQUESTS ==========
function displayRequests(requests) {
    const container = document.getElementById('requestsList');

    if (requests.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-400 py-12">
                <div class="text-6xl mb-4">📋</div>
                <h3 class="text-xl font-bold mb-2">Aucune demande</h3>
                <p class="mb-6">Vous n'avez pas encore fait de demande de devis</p>
                <a href="index.html#devis" class="btn btn-primary inline-block">
                    Faire une demande
                </a>
            </div>
        `;
        return;
    }

    container.innerHTML = requests.map(request => {
        const statusColors = {
            'nouveau': 'bg-blue-900/30 border-blue-500 text-blue-400',
            'en_cours': 'bg-amber-900/30 border-amber-500 text-amber-400',
            'devis_envoye': 'bg-purple-900/30 border-purple-500 text-purple-400',
            'termine': 'bg-green-900/30 border-green-500 text-green-400',
            'annule': 'bg-red-900/30 border-red-500 text-red-400'
        };

        const statusLabels = {
            'nouveau': 'Nouveau',
            'en_cours': 'En cours',
            'devis_envoye': 'Devis envoyé',
            'termine': 'Terminé',
            'annule': 'Annulé'
        };

        const status = request.statut || 'nouveau';
        const statusClass = statusColors[status] || statusColors['nouveau'];
        const statusLabel = statusLabels[status] || 'Nouveau';

        return `
            <div class="bg-dark p-6 rounded-xl border border-gray-800 hover:border-primary transition">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold mb-1">
                            ${request.marque || request.vehicule?.marque || 'N/A'} ${request.modele || request.vehicule?.modele || ''}
                        </h3>
                        <p class="text-gray-400 text-sm">
                            Demandé le ${formatDate(request.created_at || request.date_creation || new Date())}
                        </p>
                    </div>
                    <span class="px-4 py-2 rounded-lg border ${statusClass} text-sm font-semibold">
                        ${statusLabel}
                    </span>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-gray-400 text-sm">Budget maximum</p>
                        <p class="font-bold text-lg">${formatCurrency(request.budget || request.vehicule?.budget)}</p>
                    </div>
                    ${(request.annee_minimum || request.vehicule?.annee_minimum) ? `
                    <div>
                        <p class="text-gray-400 text-sm">Année minimum</p>
                        <p class="font-bold text-lg">${request.annee_minimum || request.vehicule?.annee_minimum}</p>
                    </div>
                    ` : ''}
                </div>
                
                ${request.notes_admin ? `
                <div class="bg-gray-900 p-4 rounded-lg mb-4">
                    <p class="text-sm text-gray-400 mb-1">Message de l'équipe :</p>
                    <p class="text-white">${escapeHtml(request.notes_admin)}</p>
                </div>
                ` : ''}
                
                <div class="flex gap-3">
                    ${status === 'devis_envoye' || status === 'termine' ? `
                    <button onclick="downloadQuote('${request.id}')" class="btn btn-primary flex-1">
                        📥 Télécharger le devis
                    </button>
                    ` : ''}
                    <button onclick="openChat('${request.id}', '${escapeHtml(request.vehicule?.marque || 'Demande')}')" 
                       class="btn btn-secondary flex-1 text-center">
                        💬 Chat
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// ========== UPDATE STATS ==========
function updateStats(requests) {
    const statsActive = requests.filter(r =>
        r.statut === 'nouveau' || r.statut === 'en_cours'
    ).length;

    const statsQuotes = requests.filter(r =>
        r.statut === 'devis_envoye'
    ).length;

    const statsCompleted = requests.filter(r =>
        r.statut === 'termine'
    ).length;

    document.getElementById('statsActive').textContent = statsActive;
    document.getElementById('statsQuotes').textContent = statsQuotes;
    document.getElementById('statsCompleted').textContent = statsCompleted;
}

// ========== LOGOUT ==========
window.logout = async function () {
    try {
        await fetch('../api/account-manager.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        });

        currentClient = null;

        sessionStorage.clear();

        console.log('✅ Session effacée - Redirection vers la page d\'accueil');

        window.location.href = '../index.html';
    } catch (error) {
        console.error('Logout error:', error);
        sessionStorage.clear();
        window.location.href = '../index.html';
    }
};

// ========== CHAT ==========
window.openChat = function(devisId, title) {
    if (typeof ChatComponent === 'undefined') {
        console.error('ChatComponent not loaded');
        alert('Le système de chat est indisponible pour le moment.');
        return;
    }
    const clientId = sessionStorage.getItem('clientId');
    if (!clientId) {
        console.error('Client ID not found in session');
        alert('Erreur: Impossible d\'identifier l\'utilisateur.');
        return;
    }
    ChatComponent.init(devisId, 'client', clientId);
};

// ========== DOWNLOAD QUOTE ==========
window.downloadQuote = function (requestId) {
    alert('Fonctionnalité de téléchargement à implémenter');
};

// ========== UTILITY FUNCTIONS ==========
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function formatCurrency(amount) {
    if (!amount) return 'N/A';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0
    }).format(amount);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function initParticles() {
    const particlesContainer = document.getElementById('particles');
    if (!particlesContainer) return;

    for (let i = 0; i < 30; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
        particle.style.animationDelay = Math.random() * 5 + 's';
        particlesContainer.appendChild(particle);
    }
}

console.log('✅ Client Dashboard - Script chargé');
