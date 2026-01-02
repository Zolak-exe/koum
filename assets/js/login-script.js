// Create particles
function createParticles() {
    const container = document.getElementById('particles');
    if (!container) return;
    for (let i = 0; i < 30; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 15 + 's';
        particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
        container.appendChild(particle);
    }
}
createParticles();

// Check if already logged in
if (sessionStorage.getItem('isLoggedIn') === 'true') {
    const role = sessionStorage.getItem('userRole');
    if (role === 'client') window.location.href = 'client.html';
    else if (role === 'admin') window.location.href = 'admin.html';
    else if (role === 'vendeur') window.location.href = 'vendeur.html';
}

// Login Form Handler
document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    // Support both ID names for compatibility
    const usernameInput = document.getElementById('username') || document.getElementById('identifier');
    const username = usernameInput ? usernameInput.value : '';
    const password = document.getElementById('password').value;
    
    const errorMessage = document.getElementById('errorMessage') || document.getElementById('errorAlert');
    const errorText = document.getElementById('errorText');
    const loginBtn = document.getElementById('loginBtn') || document.querySelector('button[type="submit"]');

    if (!username) {
        console.error('Username input not found');
        return;
    }

    loginBtn.disabled = true;
    loginBtn.innerHTML = '<span class="relative z-10">🔄 Vérification...</span>';
    if (errorMessage) errorMessage.classList.add('hidden');

    try {
        const response = await fetch('../api/account-manager.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'login',
                email: username,
                password: password
            })
        });

        if (!response.ok) {
            throw new Error('Erreur de connexion au serveur');
        }

        const result = await response.json();

        if (response.ok && result.success) {
            loginBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            loginBtn.innerHTML = '<span class="relative z-10">✓ Connexion réussie !</span>';

            sessionStorage.setItem('isLoggedIn', 'true');
            sessionStorage.setItem('userName', result.account?.nom || result.account?.email || username);
            sessionStorage.setItem('userEmail', result.account?.email || username);
            sessionStorage.setItem('userPhone', result.account?.telephone || '');
            sessionStorage.setItem('userRole', result.account?.role || 'admin');
            if (result.account?.id) {
                sessionStorage.setItem('clientId', result.account.id);
            }
            
            console.log('✅ Session créée:', {
                isLoggedIn: sessionStorage.getItem('isLoggedIn'),
                userName: sessionStorage.getItem('userName'),
                userEmail: sessionStorage.getItem('userEmail'),
                userPhone: sessionStorage.getItem('userPhone'),
                userRole: sessionStorage.getItem('userRole'),
                clientId: sessionStorage.getItem('clientId')
            });

            setTimeout(() => {
                if (result.account?.role === 'client') {
                    window.location.href = 'client.html';
                } else {
                    window.location.href = 'admin.html';
                }
            }, 800);
        }else {
            errorText.textContent = result.message || 'Identifiant ou mot de passe incorrect';

            if (result.attempts_remaining !== undefined) {
                errorText.textContent += ` (${result.attempts_remaining} tentative(s) restante(s))`;
            }

            errorMessage.classList.remove('hidden');
            errorMessage.style.animation = 'shake 0.5s';
            setTimeout(() => { errorMessage.style.animation = ''; }, 500);

            loginBtn.disabled = false;
            loginBtn.innerHTML = '<span class="relative z-10">Se connecter</span>';

            document.getElementById('password').value = '';
            document.getElementById('password').focus();
        }
    } catch (error) {
        console.error('Erreur:', error);
        errorText.textContent = 'Erreur de connexion au serveur';
        errorMessage.classList.remove('hidden');
        loginBtn.disabled = false;
        loginBtn.innerHTML = '<span class="relative z-10">Se connecter</span>';
    }
});
