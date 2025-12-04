// ========================================
// NEXT DRIVE IMPORT - Chat Instantané Client
// ========================================

'use strict';

let chatPollingInterval = null;
let lastMessageCount = 0;

// Déterminer le chemin de l'API en fonction de l'emplacement du fichier
const CHAT_API_PATH = window.location.pathname.includes('/pages/') ? '../api/chat.php' : 'api/chat.php';

// ========== INIT CHAT ==========
function initChat() {
    const chatWidget = createChatWidget();
    document.body.appendChild(chatWidget);

    // Polling pour les nouveaux messages
    startChatPolling();

    // Charger les messages initiaux
    loadMessages();
}

// Helper: safe parse JSON responses (throws descriptive errors)
async function parseJsonResponse(response) {
    if (!response) throw new Error('No response received');

    if (!response.ok) {
        let body = '';
        try { body = await response.text(); } catch (_) { body = ''; }
        throw new Error('Erreur serveur: ' + response.status + (body ? (' - ' + body) : ''));
    }

    let text = '';
    try { text = await response.text(); } catch (err) { throw new Error('Impossible de lire la réponse'); }

    if (!text || !text.trim()) throw new Error('Réponse vide du serveur');

    try {
        return JSON.parse(text);
    } catch (err) {
        throw new Error('Réponse JSON invalide: ' + err.message);
    }
}

// ========== CREATE CHAT WIDGET ==========
function createChatWidget() {
    const widget = document.createElement('div');
    widget.id = 'chatWidget';
    widget.className = 'fixed bottom-6 right-6 z-50';
    widget.innerHTML = `
        <!-- Bouton Chat -->
        <button id="chatToggle" class="bg-gradient-to-r from-primary to-secondary hover:from-orange-600 hover:to-yellow-600 text-white rounded-full w-16 h-16 flex items-center justify-center shadow-2xl transition-all transform hover:scale-110 relative">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            <span id="chatBadge" class="hidden absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">
                0
            </span>
        </button>
        
        <!-- Chat Window -->
        <div id="chatWindow" class="hidden absolute bottom-20 right-0 bg-gray-900 border-2 border-gray-800 rounded-2xl shadow-2xl w-96 max-h-[600px] flex flex-col">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary to-secondary p-4 rounded-t-2xl flex justify-between items-center">
                <div>
                    <h3 class="text-white font-bold text-lg">💬 Chat Support</h3>
                    <p class="text-white/80 text-xs">Réponse instantanée</p>
                </div>
                <button id="chatClose" class="text-white hover:bg-white/20 rounded-full p-2 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Messages -->
            <div id="chatMessages" class="flex-1 p-4 overflow-y-auto bg-gray-800 min-h-[300px] max-h-[400px]">
                <div class="text-center text-gray-400 text-sm py-8">
                    <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <p>Aucun message pour le moment</p>
                    <p class="text-xs mt-2">Envoyez un message pour démarrer la conversation</p>
                </div>
            </div>
            
            <!-- Input -->
            <div class="p-4 bg-gray-900 rounded-b-2xl border-t border-gray-800">
                <form id="chatForm" class="flex gap-2">
                    <input 
                        type="text" 
                        id="chatInput" 
                        placeholder="Votre message..." 
                        class="flex-1 bg-gray-800 text-white border border-gray-700 rounded-lg px-4 py-3 focus:border-primary focus:outline-none"
                        required
                        autocomplete="off"
                    >
                    <button type="submit" class="bg-primary hover:bg-orange-600 text-white rounded-lg px-4 py-3 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    `;

    // Event listeners
    setTimeout(() => {
        document.getElementById('chatToggle').addEventListener('click', toggleChat);
        document.getElementById('chatClose').addEventListener('click', toggleChat);
        document.getElementById('chatForm').addEventListener('submit', sendMessage);
    }, 100);

    return widget;
}

// ========== TOGGLE CHAT ==========
function toggleChat() {
    const chatWindow = document.getElementById('chatWindow');
    const isHidden = chatWindow.classList.contains('hidden');

    if (isHidden) {
        chatWindow.classList.remove('hidden');
        document.getElementById('chatInput').focus();
        markMessagesAsRead();
    } else {
        chatWindow.classList.add('hidden');
    }
}

// ========== SEND MESSAGE ==========
async function sendMessage(e) {
    e.preventDefault();

    const input = document.getElementById('chatInput');
    const message = input.value.trim();

    if (!message) return;

    const userData = {
        user_id: sessionStorage.getItem('clientId'),
        user_email: sessionStorage.getItem('userEmail'),
        user_name: sessionStorage.getItem('userName')
    };

    if (!userData.user_email) {
        alert('Vous devez être connecté pour envoyer des messages');
        return;
    }

    try {
        const response = await fetch(CHAT_API_PATH, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'send_message',
                message: message,
                user_id: userData.user_id,
                user_email: userData.user_email,
                user_name: userData.user_name,
                is_admin: false
            })
        });

        const result = await parseJsonResponse(response);

        if (result.success) {
            input.value = '';
            loadMessages();
        } else {
            throw new Error(result.message);
        }

    } catch (error) {
        console.error('Error sending message:', error);
        alert('Erreur lors de l\'envoi du message');
    }
}

// ========== LOAD MESSAGES ==========
async function loadMessages() {
    const userData = {
        user_id: sessionStorage.getItem('clientId'),
        user_email: sessionStorage.getItem('userEmail')
    };

    if (!userData.user_email) return;

    try {
        const response = await fetch(CHAT_API_PATH, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_messages',
                user_id: userData.user_id,
                user_email: userData.user_email,
                is_admin: false
            })
        });

        const result = await parseJsonResponse(response);

        if (result.success) {
            displayMessages(result.messages);
            updateUnreadCount(result.messages);
        }

    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

// ========== DISPLAY MESSAGES ==========
function displayMessages(messages) {
    const container = document.getElementById('chatMessages');

    if (!messages || messages.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-400 text-sm py-8">
                <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <p>Aucun message pour le moment</p>
                <p class="text-xs mt-2">Envoyez un message pour démarrer la conversation</p>
            </div>
        `;
        return;
    }

    const messagesHTML = messages.map(msg => {
        const isAdmin = msg.is_admin;
        const time = new Date(msg.timestamp).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

        return `
            <div class="mb-4 ${isAdmin ? 'text-left' : 'text-right'}">
                <div class="inline-block max-w-[80%]">
                    <div class="${isAdmin ? 'bg-gray-700' : 'bg-primary'} rounded-2xl px-4 py-3 ${isAdmin ? 'rounded-tl-none' : 'rounded-tr-none'}">
                        ${isAdmin ? '<div class="text-xs text-gray-400 mb-1 font-semibold">Support NEXT DRIVE IMPORT</div>' : ''}
                        <div class="text-white text-sm">${msg.message}</div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1 px-2">${time}</div>
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = messagesHTML;
    container.scrollTop = container.scrollHeight;
}

// ========== UPDATE UNREAD COUNT ==========
function updateUnreadCount(messages) {
    const unreadMessages = messages.filter(msg => msg.is_admin && !msg.read);
    const count = unreadMessages.length;

    const badge = document.getElementById('chatBadge');
    if (count > 0) {
        badge.textContent = count;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

// ========== MARK MESSAGES AS READ ==========
async function markMessagesAsRead() {
    const userData = {
        user_id: sessionStorage.getItem('clientId'),
        user_email: sessionStorage.getItem('userEmail')
    };

    if (!userData.user_email) return;

    try {
        // Récupérer les messages non lus
        const response = await fetch(CHAT_API_PATH, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_messages',
                user_id: userData.user_id,
                user_email: userData.user_email,
                is_admin: false
            })
        });

        const result = await parseJsonResponse(response);

        if (result.success) {
            const unreadIds = result.messages
                .filter(msg => msg.is_admin && !msg.read)
                .map(msg => msg.id);

            if (unreadIds.length > 0) {
                await fetch(CHAT_API_PATH, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'mark_as_read',
                        message_ids: unreadIds
                    })
                });
            }
        }

    } catch (error) {
        console.error('Error marking messages as read:', error);
    }
}

// ========== START POLLING ==========
function startChatPolling() {
    // Poll toutes les 5 secondes
    chatPollingInterval = setInterval(() => {
        loadMessages();
    }, 5000);
}

// ========== STOP POLLING ==========
function stopChatPolling() {
    if (chatPollingInterval) {
        clearInterval(chatPollingInterval);
        chatPollingInterval = null;
    }
}

// ========== INIT ON PAGE LOAD ==========
document.addEventListener('DOMContentLoaded', function () {
    // Vérifier si l'utilisateur est connecté
    const isLoggedIn = sessionStorage.getItem('isLoggedIn') === 'true';

    if (isLoggedIn) {
        initChat();
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    stopChatPolling();
});
