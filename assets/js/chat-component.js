// Chat Component
const ChatComponent = {
    pollInterval: null,
    currentDevisId: null,
    currentUserRole: null, // 'admin', 'vendeur', 'client'
    currentUserId: null,

    init: function(devisId, userRole, userId) {
        this.currentDevisId = devisId;
        this.currentUserRole = userRole;
        this.currentUserId = userId;
        this.renderModal();
        this.startPolling();
    },

    renderModal: function() {
        // Remove existing modal if any
        const existing = document.getElementById('chatModal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'chatModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-gray-800 rounded-lg w-full max-w-lg h-[600px] flex flex-col shadow-2xl border border-gray-700">
                <!-- Header -->
                <div class="bg-gray-900 p-4 rounded-t-lg border-b border-gray-700 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-white">💬 Discussion en direct</h3>
                        <p class="text-xs text-gray-400">Devis #${String(this.currentDevisId).slice(-6)}</p>
                    </div>
                    <button onclick="ChatComponent.close()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
                </div>

                <!-- Messages Area -->
                <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-800">
                    <div class="text-center text-gray-500 mt-4">Chargement de la conversation...</div>
                </div>

                <!-- Input Area -->
                <div class="p-4 bg-gray-900 border-t border-gray-700">
                    <form onsubmit="ChatComponent.sendMessage(event)" class="flex gap-2">
                        <input type="text" id="messageInput" 
                               class="flex-1 bg-gray-700 text-white px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Votre message..." autocomplete="off">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">
                            Envoyer
                        </button>
                    </form>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        this.loadMessages();
    },

    close: function() {
        const modal = document.getElementById('chatModal');
        if (modal) modal.remove();
        if (this.pollInterval) clearInterval(this.pollInterval);
    },

    startPolling: function() {
        if (this.pollInterval) clearInterval(this.pollInterval);
        this.pollInterval = setInterval(() => this.loadMessages(), 3000);
    },

    loadMessages: async function() {
        try {
            const response = await fetch(`../api/chat-manager.php?action=get_messages&devis_id=${this.currentDevisId}`);
            const data = await response.json();

            if (data.success) {
                this.displayMessages(data.messages);
            }
        } catch (error) {
            console.error('Erreur chat:', error);
        }
    },

    displayMessages: function(messages) {
        const container = document.getElementById('chatMessages');
        if (!container) return;

        if (messages.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 mt-4">Aucun message. Commencez la discussion !</div>';
            return;
        }

        const html = messages.map(msg => {
            const isMe = msg.sender_id === this.currentUserId;
            const date = new Date(msg.created_at).toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'});
            
            // Determine sender label
            let senderLabel = msg.sender_name || 'Utilisateur';
            if (msg.sender_role === 'admin') senderLabel += ' (Admin)';
            else if (msg.sender_role === 'vendeur') senderLabel += ' (Vendeur)';

            return `
                <div class="flex flex-col ${isMe ? 'items-end' : 'items-start'}">
                    <div class="max-w-[80%] ${isMe ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-200'} rounded-lg px-4 py-2 shadow">
                        <div class="text-xs ${isMe ? 'text-blue-200' : 'text-gray-400'} mb-1 font-bold">
                            ${isMe ? 'Moi' : senderLabel}
                        </div>
                        <p class="text-sm break-words">${this.escapeHtml(msg.message)}</p>
                    </div>
                    <span class="text-xs text-gray-500 mt-1">${date}</span>
                </div>
            `;
        }).join('');

        // Only update if content changed to avoid scroll jumping (simple check)
        if (container.innerHTML !== html) {
            container.innerHTML = html;
            container.scrollTop = container.scrollHeight;
        }
    },

    sendMessage: async function(e) {
        e.preventDefault();
        const input = document.getElementById('messageInput');
        const message = input.value.trim();

        if (!message) return;

        // Optimistic UI update could go here

        try {
            const response = await fetch('../api/chat-manager.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'send_message',
                    devis_id: this.currentDevisId,
                    message: message
                })
            });

            const result = await response.json();
            if (result.success) {
                input.value = '';
                this.loadMessages();
            } else {
                alert('Erreur: ' + result.message);
            }
        } catch (error) {
            console.error('Erreur envoi:', error);
        }
    },

    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};
