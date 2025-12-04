// Create particles
function createParticles() {
    const container = document.getElementById('particles');
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

// Login Form Handler
document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const loginBtn = document.getElementById('loginBtn');

    loginBtn.disabled = true;
    loginBtn.innerHTML = '<span class="relative z-10">🔄 Vérification...</span>';
    errorMessage.classList.add('hidden');

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
            sessionStorage.setItem('userRole', result.account?.role || 'admin');
            if (result.account?.id) {
                sessionStorage.setItem('clientId', result.account.id);
            }
            
            console.log('✅ Session créée:', {
                isLoggedIn: sessionStorage.getItem('isLoggedIn'),
                userName: sessionStorage.getItem('userName'),
                userEmail: sessionStorage.getItem('userEmail'),
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
