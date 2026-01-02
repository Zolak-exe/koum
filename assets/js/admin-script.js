let allClients = [];
let filteredClients = [];

// Vérifier l'authentification au chargement
window.addEventListener('load', async function () {
    console.log('Page chargée, démarrage...');

    // Skip PHP session check for development if needed
    const skipAuth = false; // Mettre à true pour tester sans auth

    if (!skipAuth) {
        try {
            const response = await fetch('../api/check_session.php');
            if (response.ok) {
                const result = await response.json();
                if (!result.logged_in) {
                    console.log('Non connecté, redirection...');
                    window.location.href = '../pages/login.html';
                    return;
                }
                console.log('Session OK');
            }
        } catch (error) {
            console.warn('Pas de vérification PHP, mode développement');
        }
    }

    loadData();
});

async function loadData() {
    console.log('Chargement des données...');

    // Afficher un message de chargement
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
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            }
        });

        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const contentType = response.headers.get('content-type');
        console.log('Content-Type:', contentType);

        // Lire le texte brut d'abord
        const text = await response.text();
        console.log('Taille du fichier:', text.length, 'caractères');
        console.log('Aperçu du contenu:', text.substring(0, 100));

        // Si fichier vide, initialiser avec tableau vide
        if (!text || text.trim() === '') {
            console.log('Fichier vide, initialisation...');
            allClients = [];
            filteredClients = [];
            updateStats();
            renderTable();
            return;
        }

        // Parser le JSON
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('Erreur de parsing JSON:', parseError);
            console.log('Contenu problématique:', text);
            throw new Error('Format JSON invalide: ' + parseError.message);
        }

        console.log('Type de données:', typeof data);
        console.log('Données parsées:', data);

        // Gérer différents formats possibles
        if (Array.isArray(data)) {
            allClients = data;
        } else if (data && typeof data === 'object') {
            // Si c'est un objet, essayer de trouver le tableau
            if (data.clients && Array.isArray(data.clients)) {
                allClients = data.clients;
            } else if (data.data && Array.isArray(data.data)) {
                allClients = data.data;
            } else {
                // Convertir l'objet en tableau
                allClients = Object.values(data);
            }
        } else {
            throw new Error('Format de données non reconnu');
        }

        console.log('Nombre de clients chargés:', allClients.length);

        // Valider la structure des données
        if (allClients.length > 0) {
            const firstClient = allClients[0];
            console.log('Structure du premier client:', firstClient);
        }

        filteredClients = [...allClients];
        updateStats();
        renderTable();

    } catch (error) {
        console.error('Erreur complète:', error);
        console.error('Stack trace:', error.stack);

        // Afficher un message d'erreur détaillé
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-8">
                        <div class="text-red-400 mb-4">
                            <div class="text-xl font-bold mb-2">⚠️ Erreur de chargement</div>
                            <div class="text-sm text-gray-400 mb-2">${error.message}</div>
                            <div class="text-xs text-gray-500 mb-4">
                                Vérifiez que le fichier clients.json existe et est accessible
                            </div>
                        </div>
                        <button onclick="testJsonFile()" class="btn btn-primary mr-2">
                            🔍 Tester le fichier
                        </button>
                        <button onclick="initializeJsonFile()" class="btn btn-secondary mr-2">
                            📝 Initialiser le fichier
                        </button>
                        <button onclick="loadData()" class="btn btn-primary">
                            🔄 Réessayer
                        </button>
                    </td>
                </tr>
            `;
        }
    }
}

// Fonction de test du fichier JSON
async function testJsonFile() {
    console.log('=== Test de l\'API clients ===');

    try {
        const getResponse = await fetch('../api/get-clients.php');
        const text = await getResponse.text();
        console.log('Taille de la réponse:', text.length, 'octets');

        try {
            const data = JSON.parse(text);
            console.log('JSON valide:', '✅');
            console.log('Type de données:', Array.isArray(data) ? 'Tableau' : 'Objet');
            console.log('Nombre d\'éléments:', Array.isArray(data) ? data.length : Object.keys(data).length);

            alert('✅ API clients fonctionnelle!\n\n' +
                'Taille: ' + text.length + ' octets\n' +
                'Type: ' + (Array.isArray(data) ? 'Tableau' : 'Objet') + '\n' +
                'Éléments: ' + (Array.isArray(data) ? data.length : Object.keys(data).length));
        } catch (e) {
            console.log('JSON invalide:', '❌', e.message);
            alert('❌ Réponse JSON invalide!\n\n' + e.message);
        }
    } catch (error) {
        console.error('Erreur test:', error);
        alert('❌ Erreur lors du test:\n\n' + error.message);
    }
}

// Fonction pour initialiser le fichier JSON
async function initializeJsonFile() {
    if (!confirm('Voulez-vous créer/réinitialiser le fichier clients.json?\nCela créera un fichier vide.')) {
        return;
    }

    try {
        const response = await fetch('init.php');
        if (response.ok) {
            const result = await response.text();
            alert('Fichier initialisé!\n\nRéponse du serveur:\n' + result);
            loadData();
        } else {
            alert('Erreur lors de l\'initialisation: ' + response.status);
        }
    } catch (error) {
        alert('Le fichier init.php n\'est pas disponible.\n\nCréez manuellement un fichier clients.json avec le contenu: []');
    }
}

// Fonction pour mettre à jour les statistiques
function updateStats() {
    const stats = {
        total: allClients.length,
        nouveau: allClients.filter(c => c.statut === 'nouveau').length,
        enCours: allClients.filter(c => c.statut === 'en_cours').length,
        devisEnvoye: allClients.filter(c => c.statut === 'devis_envoye').length,
        termine: allClients.filter(c => c.statut === 'termine').length,
        annule: allClients.filter(c => c.statut === 'annule').length
    };

    // Mettre à jour l'affichage avec les bons IDs de votre HTML
    const updateElement = (id, value) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    };

    updateElement('totalRequests', stats.total);
    updateElement('newRequests', stats.nouveau);
    updateElement('inProgressRequests', stats.enCours);
    updateElement('completedRequests', stats.termine);

    console.log('Statistiques mises à jour:', stats);
}

// ========== FONCTION RENDERTABLE CORRIGÉE ==========
function renderTable() {
    const tableBody = document.getElementById('tableBody');
    if (!tableBody) return;

    if (filteredClients.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-8 text-gray-400">
                    <div class="text-lg mb-2">Aucune donnée disponible</div>
                    <div class="text-sm">Les nouvelles demandes apparaîtront ici</div>
                </td>
            </tr>
        `;
        return;
    }

    // Générer les lignes du tableau adaptées à votre structure JSON
    const rows = filteredClients.map(client => {
        // Gérer les différentes structures possibles
        const id = client.id || 'N/A';
        const date = client.created_at ? new Date(client.created_at).toLocaleDateString('fr-FR') :
            client.timestamp ? new Date(client.timestamp).toLocaleDateString('fr-FR') :
                'Date invalide';

        // Extraire les informations (supporte structure SQL et JSON legacy)
        const nom = client.user_name || client.nom || '';
        const email = client.user_email || client.email || '';
        const telephone = client.telephone || '';

        // Extraire les informations véhicule (supporte structure SQL et JSON legacy)
        const marque = client.marque || client.vehicule?.marque || '';
        const modele = client.modele || client.vehicule?.modele || '';
        const budget = client.budget || client.vehicule?.budget || 0;

        // Statut
        const statut = client.statut || 'nouveau';
        const statusClass = statut === 'nouveau' ? 'badge-nouveau' :
            statut === 'en_cours' ? 'badge-en-cours' :
                statut === 'devis_envoye' ? 'badge-devis-envoye' :
                    statut === 'annule' ? 'badge-annule' :
                        'badge-termine';

        // Vérifier si le client a un compte
        const hasAccount = client.has_account || client.user_id || false;
        const accountBadge = hasAccount
            ? '<span class="text-xs bg-green-600 text-white px-2 py-1 rounded ml-2" title="Possède un compte">✓ Compte</span>'
            : '<span class="text-xs bg-gray-600 text-gray-300 px-2 py-1 rounded ml-2" title="Pas de compte">⚠ Sans compte</span>';

        return `
            <tr>
                <td class="font-mono text-sm text-gray-400">#${String(id).slice(-6)}</td>
                <td>${date}</td>
                <td>
                    <div class="text-white font-medium">
                        ${nom}
                        ${accountBadge}
                    </div>
                    <div class="text-xs text-gray-400">${email || 'Email non renseigné'}</div>
                </td>
                <td>
                    <div class="text-sm">${telephone || 'Non renseigné'}</div>
                </td>
                <td>
                    <div class="text-amber-400">${marque} ${modele}</div>
                    <div class="text-xs text-gray-400">Budget: ${Number(budget).toLocaleString('fr-FR')}€</div>
                </td>
                <td class="font-bold text-green-400">
                    ${Number(budget).toLocaleString('fr-FR')}€
                </td>
                <td>
                    <select class="status-select bg-gray-700 text-white px-3 py-1 rounded ${statusClass}" 
                            onchange="updateStatusQuick('${id}', this.value)">
                        <option value="nouveau" ${statut === 'nouveau' ? 'selected' : ''}>Nouveau</option>
                        <option value="en_cours" ${statut === 'en_cours' ? 'selected' : ''}>En cours</option>
                        <option value="devis_envoye" ${statut === 'devis_envoye' ? 'selected' : ''}>Devis envoyé</option>
                        <option value="termine" ${statut === 'termine' ? 'selected' : ''}>Terminé</option>
                        <option value="annule" ${statut === 'annule' ? 'selected' : ''}>Annulé</option>
                    </select>
                </td>
                <td>
                    <div style="display:flex;gap:8px;">
                        <button onclick="viewDevisFullscreen('${id}')" 
                                class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded transition"
                                title="Voir le devis">
                            👁️ Voir
                        </button>
                        <button onclick="showClientDetails('${id}')" 
                                class="btn btn-sm bg-orange-600 hover:bg-orange-700 text-white px-3 py-2 rounded transition"
                                title="Modifier le devis">
                            ✏️ Modifier
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    tableBody.innerHTML = rows;
    console.log('Tableau rendu avec', filteredClients.length, 'lignes');
}

// ========== FONCTIONS MANQUANTES ==========

// Fonction pour appliquer les filtres
function applyFilters() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';

    filteredClients = allClients.filter(client => {
        // Filtre par statut
        if (statusFilter && client.statut !== statusFilter) {
            return false;
        }

        // Filtre par recherche
        if (searchTerm) {
            const nom = (client.user_name || client.nom || '').toLowerCase();
            const email = (client.user_email || client.email || '').toLowerCase();
            const telephone = (client.telephone || '').toLowerCase();
            const marque = (client.marque || client.vehicule?.marque || '').toLowerCase();
            const modele = (client.modele || client.vehicule?.modele || '').toLowerCase();

            return nom.includes(searchTerm) ||
                email.includes(searchTerm) ||
                telephone.includes(searchTerm) ||
                marque.includes(searchTerm) ||
                modele.includes(searchTerm);
        }

        return true;
    });

    renderTable();
}

// Fonction de filtrage du tableau (appelée depuis HTML)
function filterTable() {
    applyFilters();
}

// ========== FONCTIONS DE GESTION DES CLIENTS ==========

// Fonction pour afficher les détails d'un client
function showClientDetails(clientId) {
    const client = allClients.find(c => String(c.id) === String(clientId));
    if (!client) {
        showNotification('❌ Client non trouvé', 'error');
        return;
    }

    // Créer le modal de modification
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-gray-800 rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="bg-gray-900 p-6 rounded-t-lg border-b border-gray-700 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-white">Modifier le devis</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white text-2xl">✕</button>
            </div>
            
            <div class="p-6">
                <form id="editClientForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-gray-400 mb-2">Nom</label>
                            <input type="text" name="nom" value="${client.user_name || client.nom || ''}" 
                                   class="w-full bg-gray-700 text-white px-3 py-2 rounded border border-gray-600">
                        </div>
                        <div>
                            <label class="block text-gray-400 mb-2">Email</label>
                            <input type="email" name="email" value="${client.user_email || client.email || ''}" 
                                   class="w-full bg-gray-700 text-white px-3 py-2 rounded border border-gray-600">
                        </div>
                        <div>
                            <label class="block text-gray-400 mb-2">Téléphone</label>
                            <input type="tel" name="telephone" value="${client.telephone || ''}" 
                                   class="w-full bg-gray-700 text-white px-3 py-2 rounded border border-gray-600">
                        </div>
                        <div>
                            <label class="block text-gray-400 mb-2">Marque</label>
                            <input type="text" name="marque" value="${client.marque || client.vehicule?.marque || ''}" 
                                   class="w-full bg-gray-700 text-white px-3 py-2 rounded border border-gray-600">
                        </div>
                        <div>
                            <label class="block text-gray-400 mb-2">Modèle</label>
                            <input type="text" name="modele" value="${client.modele || client.vehicule?.modele || ''}" 
                                   class="w-full bg-gray-700 text-white px-3 py-2 rounded border border-gray-600">
                        </div>
                        <div>
                            <label class="block text-gray-400 mb-2">Budget (€)</label>
                            <input type="number" name="budget" value="${client.budget || client.vehicule?.budget || 0}" 
                                   class="w-full bg-gray-700 text-white px-3 py-2 rounded border border-gray-600">
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-400 mb-2">Année minimum</label>
                        <input type="number" name="annee_minimum" value="${client.annee_minimum || client.vehicule?.annee_minimum || ''}" 
                               class="w-full bg-gray-700 text-white px-3 py-2 rounded border border-gray-600">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-400 mb-2">Kilométrage maximum</label>
                        <input type="number" name="kilometrage_max" value="${client.kilometrage_max || client.vehicule?.kilometrage_max || ''}" 
                               class="w-full bg-gray-700 text-white px-3 py-2 rounded border border-gray-600">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-400 mb-2">Options & Commentaires</label>
                        <textarea name="commentaires" rows="3" 
                                  class="w-full bg-gray-700 text-white px-3 py-2 rounded border border-gray-600">${client.commentaires || client.vehicule?.commentaires || ''}</textarea>
                    </div>
                    
                    <div class="flex gap-2 justify-end">
                        <button type="button" onclick="closeModal()" 
                                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition">
                            Annuler
                        </button>
                        <button type="submit" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition">
                            💾 Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Gérer la soumission du formulaire
    document.getElementById('editClientForm').addEventListener('submit', function (e) {
        e.preventDefault();
        updateClientData(clientId, new FormData(this));
    });

    // Gérer la fermeture
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Stocker la référence du modal
    modal._closeFunction = closeModal;
}

// Fonction pour fermer le modal
function closeModal() {
    const modal = document.querySelector('.fixed.inset-0.bg-black');
    if (modal) {
        modal.remove();
    }
}

// Fonction pour mettre à jour les données client
function updateClientData(clientId, formData) {
    const client = allClients.find(c => String(c.id) === String(clientId));
    if (!client) return;

    // Mettre à jour les données
    client.nom = formData.get('nom') || client.nom;
    client.email = formData.get('email') || client.email;
    client.telephone = formData.get('telephone') || client.telephone;

    if (!client.vehicule) client.vehicule = {};
    client.vehicule.marque = formData.get('marque') || client.vehicule.marque;
    client.vehicule.modele = formData.get('modele') || client.vehicule.modele;
    client.vehicule.budget = Number(formData.get('budget')) || client.vehicule.budget;
    client.vehicule.annee_minimum = formData.get('annee_minimum') || client.vehicule.annee_minimum;
    client.vehicule.kilometrage_max = formData.get('kilometrage_max') || client.vehicule.kilometrage_max;
    client.vehicule.commentaires = formData.get('commentaires') || client.vehicule.commentaires;

    // Sauvegarder
    const clientsData = JSON.parse(localStorage.getItem('clients') || '[]');
    const clientIndex = clientsData.findIndex(c => String(c.id) === String(clientId));

    if (clientIndex !== -1) {
        clientsData[clientIndex] = client;
        localStorage.setItem('clients', JSON.stringify(clientsData));

        showNotification('✅ Modifications sauvegardées', 'success');
        closeModal();
        renderTable();
    }
}

// ========== FONCTION viewDevisFullscreen ADAPTÉE ==========
function viewDevisFullscreen(clientId) {
    const client = allClients.find(c => String(c.id) === String(clientId));
    if (!client) {
        console.error('Client non trouvé:', clientId);
        showNotification('❌ Devis non trouvé', 'error');
        return;
    }

    // Extraire toutes les données (supporte structure SQL et JSON legacy)
    const nom = client.user_name || client.nom || 'Non renseigné';
    const email = client.user_email || client.email || 'Non renseigné';
    const telephone = client.telephone || 'Non renseigné';

    const marque = client.marque || client.vehicule?.marque || 'Non renseigné';
    const modele = client.modele || client.vehicule?.modele || 'Non renseigné';
    const budget = client.budget || client.vehicule?.budget || 0;
    const anneeMin = client.annee_minimum || client.vehicule?.annee_minimum || 'Non spécifié';
    const kmMax = client.kilometrage_max || client.vehicule?.kilometrage_max || 'Non spécifié';
    const options = client.options || client.vehicule?.options || 'Aucune option spécifiée';
    const commentaires = client.commentaires || client.vehicule?.commentaires || 'Aucun commentaire';

    // Créer le modal plein écran
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-gray-800 rounded-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <!-- En-tête -->
            <div class="bg-gray-900 p-6 rounded-t-lg border-b border-gray-700 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-white">Devis Client</h2>
                    <p class="text-gray-400">ID: #${String(client.id).slice(-6)} • ${new Date(client.created_at || client.timestamp).toLocaleDateString('fr-FR')}</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="printDevis('${clientId}')" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">
                        🖨️ Imprimer
                    </button>
                    <button onclick="closeDevisModal()" 
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition">
                        ✕ Fermer
                    </button>
                </div>
            </div>

            <!-- Contenu -->
            <div class="p-6">
                <!-- Informations Client -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-700 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-white mb-3">👤 Informations Client</h3>
                        <div class="space-y-2">
                            <p><span class="text-gray-400">Nom:</span> <span class="text-white">${nom}</span></p>
                            <p><span class="text-gray-400">Email:</span> <span class="text-white">${email}</span></p>
                            <p><span class="text-gray-400">Téléphone:</span> <span class="text-white">${telephone}</span></p>
                            <p><span class="text-gray-400">Consentement RGPD:</span> <span class="text-white">${client.rgpd_consent ? '✅ Oui' : '❌ Non'}</span></p>
                        </div>
                    </div>

                    <!-- Informations Véhicule -->
                    <div class="bg-gray-700 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-white mb-3">🚗 Véhicule Recherché</h3>
                        <div class="space-y-2">
                            <p><span class="text-gray-400">Marque/Modèle:</span> <span class="text-white">${marque} ${modele}</span></p>
                            <p><span class="text-gray-400">Budget:</span> <span class="text-green-400 font-bold">${Number(budget).toLocaleString('fr-FR')}€</span></p>
                            <p><span class="text-gray-400">Année min:</span> <span class="text-white">${anneeMin}</span></p>
                            <p><span class="text-gray-400">Km max:</span> <span class="text-white">${kmMax} km</span></p>
                        </div>
                    </div>
                </div>

                <!-- Options et Commentaires -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-700 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-white mb-3">⚙️ Options Demandées</h3>
                        <p class="text-gray-300">${options}</p>
                    </div>
                    <div class="bg-gray-700 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-white mb-3">💬 Commentaires</h3>
                        <p class="text-gray-300">${commentaires}</p>
                    </div>
                </div>

                <!-- Métadonnées -->
                <div class="bg-gray-700 p-4 rounded-lg mb-6">
                    <h3 class="text-lg font-semibold text-white mb-3">📊 Métadonnées</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <p><span class="text-gray-400">Source:</span> <span class="text-white">${client.source || 'Non spécifié'}</span></p>
                        <p><span class="text-gray-400">IP:</span> <span class="text-white">${client.metadata?.ip_address || 'Non disponible'}</span></p>
                        <p><span class="text-gray-400">Navigateur:</span> <span class="text-white">${client.metadata?.user_agent ? client.metadata.user_agent.substring(0, 50) + '...' : 'Non disponible'}</span></p>
                        <p><span class="text-gray-400">Dernière mise à jour:</span> <span class="text-white">${new Date(client.updated_at || client.created_at).toLocaleString('fr-FR')}</span></p>
                    </div>
                </div>

                <!-- Notes Administrateur -->
                <div class="bg-gray-700 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-white mb-3">📝 Notes & Commentaires</h3>
                    <textarea id="adminNotes" 
                              class="w-full bg-gray-600 text-white p-3 rounded border border-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                              rows="4"
                              placeholder="Ajoutez des notes ou commentaires sur ce devis...">${client.admin_notes || client.adminNotes || ''}</textarea>
                    <button onclick="saveAdminNotes('${clientId}')" 
                            class="mt-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition">
                        💾 Sauvegarder les notes
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Empêcher la fermeture en cliquant à l'extérieur
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeDevisModal();
        }
    });

    // Gérer la touche Echap
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            closeDevisModal();
        }
    };
    document.addEventListener('keydown', handleEscape);
    modal._escapeHandler = handleEscape;
}

// Fonction pour fermer le modal
function closeDevisModal() {
    const modal = document.querySelector('.fixed.inset-0.bg-black');
    if (modal) {
        document.removeEventListener('keydown', modal._escapeHandler);
        modal.remove();
    }
}

// Fonction pour sauvegarder les notes administrateur
async function saveAdminNotes(clientId) {
    const client = allClients.find(c => String(c.id) === String(clientId));
    if (!client) return;

    const notesTextarea = document.getElementById('adminNotes');
    const notes = notesTextarea.value;
    const btn = notesTextarea.nextElementSibling;
    
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Sauvegarde...';

    try {
        const response = await fetch('../api/devis-manager.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_notes',
                devis_id: clientId,
                notes: notes
            })
        });

        const result = await response.json();

        if (result.success) {
            // Mettre à jour localement
            client.admin_notes = notes;
            client.adminNotes = notes; // Compatibilité

            showNotification('✅ Notes sauvegardées sur le serveur', 'success');
        } else {
            throw new Error(result.message || 'Erreur serveur');
        }
    } catch (error) {
        console.error('Erreur sauvegarde notes:', error);
        showNotification('❌ Erreur: ' + error.message, 'error');
        
        // Fallback localStorage
        client.adminNotes = notes;
        const clientsData = JSON.parse(localStorage.getItem('clients') || '[]');
        const clientIndex = clientsData.findIndex(c => String(c.id) === String(clientId));
        if (clientIndex !== -1) {
            clientsData[clientIndex].adminNotes = notes;
            localStorage.setItem('clients', JSON.stringify(clientsData));
        }
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
}

// ========== FONCTION printDevis ADAPTÉE ==========
function printDevis(clientId) {
    const client = allClients.find(c => String(c.id) === String(clientId));
    if (!client) return;

    const printWindow = window.open('', '_blank');
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Devis - ${client.nom}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                .section { margin-bottom: 25px; }
                .section h3 { background: #f0f0f0; padding: 10px; margin: 0 0 10px 0; border-radius: 4px; }
                .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
                .note { margin-top: 30px; font-style: italic; color: #666; border-top: 1px solid #ddd; padding-top: 15px; }
                .info-item { margin-bottom: 8px; }
                .info-label { font-weight: bold; color: #555; }
                @media print {
                    body { margin: 15px; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>NEXT DRIVE IMPORT - Devis Véhicule</h1>
                <p>ID: #${String(client.id).slice(-6)} • Date: ${new Date(client.created_at || client.timestamp).toLocaleDateString('fr-FR')}</p>
            </div>

            <div class="grid">
                <div class="section">
                    <h3>Informations Client</h3>
                    <div class="info-item"><span class="info-label">Nom:</span> ${client.nom || 'Non renseigné'}</div>
                    <div class="info-item"><span class="info-label">Email:</span> ${client.email || 'Non renseigné'}</div>
                    <div class="info-item"><span class="info-label">Téléphone:</span> ${client.telephone || 'Non renseigné'}</div>
                    <div class="info-item"><span class="info-label">RGPD:</span> ${client.rgpd_consent ? 'Accepté' : 'Non accepté'}</div>
                </div>

                <div class="section">
                    <h3>Véhicule Recherché</h3>
                    <div class="info-item"><span class="info-label">Marque/Modèle:</span> ${client.vehicule?.marque || 'Non renseigné'} ${client.vehicule?.modele || ''}</div>
                    <div class="info-item"><span class="info-label">Budget:</span> ${Number(client.vehicule?.budget || 0).toLocaleString('fr-FR')}€</div>
                    <div class="info-item"><span class="info-label">Année minimum:</span> ${client.vehicule?.annee_minimum || 'Non spécifié'}</div>
                    <div class="info-item"><span class="info-label">Kilométrage max:</span> ${client.vehicule?.kilometrage_max || 'Non spécifié'} km</div>
                </div>
            </div>

            <div class="grid">
                <div class="section">
                    <h3>Options Demandées</h3>
                    <p>${client.vehicule?.options || 'Aucune option spécifiée'}</p>
                </div>
                
                <div class="section">
                    <h3>Commentaires</h3>
                    <p>${client.vehicule?.commentaires || 'Aucun commentaire'}</p>
                </div>
            </div>

            ${client.admin_notes || client.adminNotes ? `
            <div class="section">
                <h3>Notes Administrateur</h3>
                <p>${client.admin_notes || client.adminNotes}</p>
            </div>
            ` : ''}

            <div class="section">
                <h3>Informations Techniques</h3>
                <div class="info-item"><span class="info-label">Source:</span> ${client.source || 'Non spécifié'}</div>
                <div class="info-item"><span class="info-label">Statut:</span> ${client.statut || 'nouveau'}</div>
                <div class="info-item"><span class="info-label">Dernière mise à jour:</span> ${new Date(client.updated_at || client.created_at).toLocaleString('fr-FR')}</div>
            </div>

            <div class="note">
                Document généré le ${new Date().toLocaleDateString('fr-FR')} à ${new Date().toLocaleTimeString('fr-FR')} • NEXT DRIVE IMPORT
            </div>
        </body>
        </html>
    `;

    printWindow.document.write(printContent);
    printWindow.document.close();

    printWindow.onload = function () {
        printWindow.print();
    };
}

// ========== FONCTIONS DE GESTION DES STATUTS ==========

// Fonction pour mettre à jour le statut rapidement
function updateStatusQuick(clientId, newStatus) {
    const client = allClients.find(c => String(c.id) === String(clientId));
    if (!client) {
        showNotification('❌ Client non trouvé', 'error');
        return;
    }

    // Mettre à jour localement
    client.statut = newStatus;

    // Sauvegarder dans localStorage
    const clientsData = JSON.parse(localStorage.getItem('clients') || '[]');
    const clientIndex = clientsData.findIndex(c => String(c.id) === String(clientId));

    if (clientIndex !== -1) {
        clientsData[clientIndex].statut = newStatus;
        localStorage.setItem('clients', JSON.stringify(clientsData));

        showNotification('✅ Statut mis à jour', 'success');

        // Mettre à jour les filtres et le rendu
        allClients = clientsData;
        applyFilters();
        updateStats();
    }
}

// ========== FONCTIONS UTILITAIRES ==========

// Fonction de rafraîchissement
function refreshData() {
    console.log('Rafraîchissement des données...');
    showNotification('Actualisation en cours...', 'info');
    loadData();
}

// Fonction de déconnexion
function logout() {
    if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
        // Détruire la session
        const apiPath = window.location.pathname.includes('/pages/') ? '../api/account-manager.php' : 'api/account-manager.php';
        fetch(apiPath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        })
            .then(() => {
                sessionStorage.clear();
                window.location.href = window.location.pathname.includes('/pages/') ? 'login.html' : 'pages/login.html';
            })
            .catch(() => {
                // Si pas de PHP, redirection simple
                sessionStorage.clear();
                localStorage.clear();
                window.location.href = 'login.html';
            });
    }
}

// Fonction pour exporter en Excel
function exportToExcel() {
    if (allClients.length === 0) {
        showNotification('Aucune donnée à exporter', 'error');
        return;
    }

    // Créer le CSV
    const headers = ['ID', 'Date', 'Nom', 'Email', 'Téléphone', 'Marque', 'Modèle', 'Budget', 'Statut', 'Année min', 'Km max'];
    const rows = allClients.map(client => [
        client.id,
        new Date(client.created_at || Date.now()).toLocaleDateString('fr-FR'),
        client.nom || '',
        client.email || '',
        client.telephone || '',
        client.vehicule?.marque || '',
        client.vehicule?.modele || '',
        client.vehicule?.budget || '',
        client.statut || 'nouveau',
        client.vehicule?.annee_minimum || '',
        client.vehicule?.kilometrage_max || ''
    ]);

    // Convertir en CSV
    const csvContent = [
        headers.join(';'),
        ...rows.map(row => row.join(';'))
    ].join('\n');

    // Télécharger
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `export_clients_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();

    showNotification('Export réussi', 'success');
}

// Fonction de notification
function showNotification(message, type = 'success') {
    // Créer ou récupérer le conteneur de notifications
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;';
        document.body.appendChild(container);
    }

    // Créer la notification
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        background: ${type === 'error' ? '#ef4444' : type === 'info' ? '#3b82f6' : '#10b981'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 10px;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    `;
    notification.textContent = message;

    container.appendChild(notification);

    // Supprimer après 3 secondes
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Ajouter les styles CSS pour les animations
if (!document.getElementById('admin-animations')) {
    const style = document.createElement('style');
    style.id = 'admin-animations';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .modal-overlay {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .badge-nouveau {
            background-color: #3b82f6;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-en-cours {
            background-color: #f59e0b;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-termine {
            background-color: #10b981;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-devis-envoye {
            background-color: #8b5cf6;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-annule {
            background-color: #ef4444;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-select.badge-nouveau {
            background-color: #3b82f6 !important;
        }
        .status-select.badge-en-cours {
            background-color: #f59e0b !important;
        }
        .status-select.badge-termine {
            background-color: #10b981 !important;
        }
        .status-select.badge-devis-envoye {
            background-color: #8b5cf6 !important;
        }
        .status-select.badge-annule {
            background-color: #ef4444 !important;
        }
    `;
    document.head.appendChild(style);
}
// Fonction pour sauvegarder les données dans le fichier JSON
async function saveToJsonFile() {
    try {
        const response = await fetch('../api/save_clients.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(allClients)
        });

        if (response.ok) {
            console.log('✅ Données sauvegardées dans le fichier JSON');
            return true;
        } else {
            throw new Error('Erreur lors de la sauvegarde');
        }
    } catch (error) {
        console.error('❌ Erreur sauvegarde JSON:', error);
        // Fallback: sauvegarde dans localStorage
        localStorage.setItem('clients_backup', JSON.stringify(allClients));
        return false;
    }
}

// Fonction pour mettre à jour le statut rapidement (MODIFIÉE - SQL VERSION)
async function updateStatusQuick(clientId, newStatus) {
    const client = allClients.find(c => String(c.id) === String(clientId));
    if (!client) {
        showNotification('❌ Client non trouvé', 'error');
        return;
    }

    const oldStatus = client.statut;

    try {
        // Appel API pour mise à jour SQL
        const response = await fetch('../api/devis-manager.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update_status',
                devis_id: clientId,
                statut: newStatus
            })
        });

        const result = await response.json();

        if (result.success) {
            // Mettre à jour localement
            client.statut = newStatus;
            client.updated_at = new Date().toISOString().replace('T', ' ').substring(0, 19);
            
            showNotification('✅ Statut mis à jour', 'success');

            // Mettre à jour l'affichage
            updateStats();

            // Si un filtre de statut est actif, réappliquer les filtres
            const currentStatusFilter = document.getElementById('statusFilter').value;
            if (currentStatusFilter && currentStatusFilter !== 'all') {
                applyFilters();
            }
        } else {
            throw new Error(result.message || 'Erreur API');
        }

    } catch (error) {
        console.error('Erreur mise à jour statut:', error);
        // Revert en cas d'erreur
        client.statut = oldStatus;
        showNotification('❌ Erreur lors de la mise à jour: ' + error.message, 'error');
    }
}
