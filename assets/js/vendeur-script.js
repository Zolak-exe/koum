let allClients = [];
let filteredClients = [];
let currentUserId = null;

// Vérifier l'authentification au chargement
window.addEventListener('load', async function () {
    console.log('Page chargée, démarrage...');

    try {
        // Utiliser account-manager pour vérifier la session et récupérer l'ID user
        const response = await fetch('../api/account-manager.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'check_session' })
        });
        
        if (response.ok) {
            const result = await response.json();
            if (!result.authenticated || result.user.role !== 'vendeur') {
                console.log('Non autorisé, redirection...');
                window.location.href = '../pages/login.html';
                return;
            }
            currentUserId = result.user.id;
            console.log('Session OK, Vendeur ID:', currentUserId);
        } else {
            window.location.href = '../pages/login.html';
            return;
        }
    } catch (error) {
        console.error('Erreur auth:', error);
        window.location.href = '../pages/login.html';
        return;
    }

    loadData();
});

async function loadData() {
    console.log('Chargement des données...');
    const tableBody = document.getElementById('tableBody');
    if (tableBody) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-8">
                    <div class="text-gray-400">
                        <div class="animate-spin inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full mb-4"></div>
                        <div>Chargement des données...</div>
                    </div>
                </td>
            </tr>
        `;
    }

    try {
        const response = await fetch('../api/get-clients.php', {
            method: 'GET',
            headers: { 'Cache-Control': 'no-cache' }
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        allClients = Array.isArray(data) ? data : [];
        filteredClients = [...allClients];
        
        updateStats();
        renderTable();

    } catch (error) {
        console.error('Erreur chargement:', error);
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-red-500 py-4">Erreur: ${error.message}</td></tr>`;
        }
    }
}

function updateStats() {
    const stats = {
        total: allClients.length,
        claimed: allClients.filter(c => c.claimed_by === currentUserId).length,
        unclaimed: allClients.filter(c => !c.claimed_by).length
    };

    const updateElement = (id, value) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    };

    updateElement('totalRequests', stats.total);
    updateElement('claimedRequests', stats.claimed);
    updateElement('unclaimedRequests', stats.unclaimed);
}

function renderTable() {
    const tableBody = document.getElementById('tableBody');
    if (!tableBody) return;

    if (filteredClients.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-gray-400">Aucune donnée disponible</td></tr>`;
        return;
    }

    const rows = filteredClients.map(client => {
        const id = client.id || 'N/A';
        const date = new Date(client.created_at || Date.now()).toLocaleDateString('fr-FR');
        const nom = client.user_name || client.nom || 'Non renseigné';
        const marque = client.marque || client.vehicule?.marque || '';
        const modele = client.modele || client.vehicule?.modele || '';
        const budget = client.budget || client.vehicule?.budget || 0;
        const statut = client.statut || 'nouveau';
        
        const isClaimedByMe = client.claimed_by === currentUserId;
        const isUnclaimed = !client.claimed_by;

        let actionButton = '';
        if (isUnclaimed) {
            actionButton = `
                <button onclick="claimDevis('${id}')" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition">
                    ✋ Prendre en charge
                </button>`;
        } else if (isClaimedByMe) {
            actionButton = `
                <span class="text-green-400 text-sm font-bold mr-2">✓ Mon dossier</span>
                <button onclick="viewDevis('${id}')" class="text-gray-400 hover:text-white mr-2" title="Voir détails">👁️</button>
                <button onclick="openChat('${id}')" class="text-blue-400 hover:text-blue-300 mr-2" title="Ouvrir le chat">💬</button>
                <button onclick="unclaimDevis('${id}')" class="text-red-400 hover:text-red-300" title="Libérer le dossier">❌</button>
            `;
        }

        // Status dropdown only if claimed by me
        let statusDisplay = '';
        if (isClaimedByMe) {
            statusDisplay = `
                <select class="bg-gray-700 text-white px-2 py-1 rounded text-sm" 
                        onchange="updateStatus('${id}', this.value)">
                    <option value="nouveau" ${statut === 'nouveau' ? 'selected' : ''}>Nouveau</option>
                    <option value="en_cours" ${statut === 'en_cours' ? 'selected' : ''}>En cours</option>
                    <option value="devis_envoye" ${statut === 'devis_envoye' ? 'selected' : ''}>Devis envoyé</option>
                    <option value="termine" ${statut === 'termine' ? 'selected' : ''}>Terminé</option>
                    <option value="annule" ${statut === 'annule' ? 'selected' : ''}>Annulé</option>
                </select>
            `;
        } else {
            statusDisplay = `<span class="text-gray-400 text-sm">${statut}</span>`;
        }

        return `
            <tr class="${isClaimedByMe ? 'bg-gray-800/50' : ''}">
                <td class="font-mono text-sm text-gray-400">#${String(id).slice(-6)}</td>
                <td>${date}</td>
                <td><div class="font-medium text-white">${nom}</div></td>
                <td><div class="text-amber-400">${marque} ${modele}</div></td>
                <td class="font-bold text-green-400">${Number(budget).toLocaleString('fr-FR')}€</td>
                <td>${statusDisplay}</td>
                <td>${actionButton}</td>
            </tr>
        `;
    }).join('');

    tableBody.innerHTML = rows;
}

async function claimDevis(devisId) {
    if (!confirm('Voulez-vous prendre en charge ce devis ?')) return;

    try {
        const response = await fetch('../api/devis-manager.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'claim', devis_id: devisId })
        });

        const result = await response.json();
        if (result.success) {
            // Update local data
            const client = allClients.find(c => c.id === devisId);
            if (client) {
                client.claimed_by = currentUserId;
                updateStats();
                renderTable();
            }
            alert('Devis pris en charge !');
        } else {
            alert('Erreur: ' + result.message);
        }
    } catch (error) {
        console.error('Erreur claim:', error);
        alert('Erreur lors de la prise en charge');
    }
}

async function unclaimDevis(devisId) {
    if (!confirm('Voulez-vous vraiment libérer ce dossier ? Il sera de nouveau disponible pour tous les vendeurs.')) {
        return;
    }

    try {
        const response = await fetch('../api/devis-manager.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'unclaim', devis_id: devisId })
        });

        const result = await response.json();
        if (result.success) {
            // Update local data
            const client = allClients.find(c => c.id === devisId);
            if (client) {
                client.claimed_by = null;
                updateStats();
                renderTable();
            }
            alert('Dossier libéré avec succès !');
        } else {
            alert('Erreur: ' + result.message);
        }
    } catch (error) {
        console.error('Erreur unclaim:', error);
        alert('Erreur lors de la libération du dossier');
    }
}

async function updateStatus(devisId, newStatus) {
    try {
        const response = await fetch('../api/devis-manager.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'update_status', 
                devis_id: devisId, 
                statut: newStatus 
            })
        });

        const result = await response.json();
        if (result.success) {
            const client = allClients.find(c => c.id === devisId);
            if (client) client.statut = newStatus;
        } else {
            alert('Erreur: ' + result.message);
            loadData(); // Reload to reset UI
        }
    } catch (error) {
        console.error('Erreur status:', error);
        alert('Erreur lors de la mise à jour');
    }
}

function viewDevis(id) {
    // Reuse admin view logic or simple alert for now
    alert('Détails du devis ' + id);
}

function openChat(devisId) {
    ChatComponent.init(devisId, 'vendeur', currentUserId);
}

function logout() {
    fetch('../api/account-manager.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'logout' })
    }).then(() => window.location.href = '../pages/login.html');
}

function refreshData() {
    loadData();
}
