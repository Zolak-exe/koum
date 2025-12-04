// ========================================
// NEXT DRIVE IMPORT - Script v2.1.0
// Refonte complète avec optimisations
// ========================================

'use strict';

// Helper: safe parse JSON responses
async function safeParseJson(response) {
    if (!response) throw new Error('No response');
    if (!response.ok) {
        let body = '';
        try { body = await response.text(); } catch (_) { body = ''; }
        throw new Error('Server error: ' + response.status + (body ? ' - ' + body : ''));
    }
    let text = '';
    try { text = await response.text(); } catch (_) { throw new Error('Unable to read response'); }
    if (!text || !text.trim()) throw new Error('Empty response body');
    try { return JSON.parse(text); } catch (err) { throw new Error('Invalid JSON: ' + err.message); }
}

// ========== CONFIGURATION ==========
const CONFIG = {
    API_URL: window.location.pathname.includes('/pages/') ? '../api/submit-devis.php' : 'api/submit-devis.php',
    ACCOUNT_API_URL: window.location.pathname.includes('/pages/') ? '../api/account-manager.php' : 'api/account-manager.php',
    ANALYTICS_ID: 'G-XXXXXXXXXX', // À remplacer
    PHONE: '+33123456789',
    EMAIL: 'nextdriveimport@gmail.com'
};

// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', function () {
    initSmoothScroll();
    initFormHandler();
    initMobileMenu();
    initNavbarScroll();
    initLazyLoading();
    initAccessibility();
    trackPageView();
    createStarParticles();
    updateEspaceClientLink();
});

// ========== SMOOTH SCROLL ==========
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');

            if (targetId === '#') return;

            const target = document.querySelector(targetId);
            if (!target) return;

            const offset = 80; // Hauteur navbar
            const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;

            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });

            // Update URL without reload
            history.pushState(null, null, targetId);

            // Focus management for accessibility
            target.setAttribute('tabindex', '-1');
            target.focus();
        });
    });
}

// ========== CLIENT AUTHENTICATION ==========
let clientData = null;

// Helper function to check if user is authenticated via sessionStorage
function isAuthenticated() {
    return sessionStorage.getItem('isLoggedIn') === 'true';
}

// Helper function to hydrate clientData from sessionStorage
function hydrateClientDataFromSession() {
    if (!isAuthenticated()) return null;

    return {
        id: sessionStorage.getItem('clientId') || null,
        nom: sessionStorage.getItem('userName') || sessionStorage.getItem('userEmail'),
        email: sessionStorage.getItem('userEmail'),
        telephone: sessionStorage.getItem('userPhone') || '',
        role: sessionStorage.getItem('userRole') || 'client'
    };
}

async function checkClientSession() {
    const sessionData = hydrateClientDataFromSession();
    if (sessionData) {
        console.log('✅ Session found in sessionStorage, hydrating clientData');
        clientData = sessionData;
        showDevisForm();
        return;
    }

    // Fallback: check PHP session (legacy auth flow)
    try {
        const response = await fetch(CONFIG.ACCOUNT_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'check_session' })
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.warn('Response is not JSON, skipping session check');
            return;
        }

        const result = await safeParseJson(response);

        if (result.authenticated) {
            clientData = result.client;
            showDevisForm();
        }
    } catch (error) {
        console.debug('Session check: user not authenticated');
    }
}

// ========== DEVIS ACCESS CONTROL ==========
function checkDevisAccess() {
    const devisBlurOverlay = document.getElementById('devisBlurOverlay');
    const devisContent = document.getElementById('devisContent');

    if (!devisBlurOverlay || !devisContent) return;

    // Vérifier si l'utilisateur est connecté via sessionStorage
    const isLoggedIn = sessionStorage.getItem('isLoggedIn') === 'true';

    console.log('🔒 Devis Access Check:', isLoggedIn ? 'Logged in' : 'Not logged in');

    if (isLoggedIn) {
        devisBlurOverlay.classList.add('hidden');
        devisContent.style.filter = 'none';
        devisContent.style.pointerEvents = 'auto';
    } else {
        devisBlurOverlay.classList.remove('hidden');
        devisContent.style.filter = 'blur(8px)';
        devisContent.style.pointerEvents = 'none';
    }
}

// ========== ESPACE CLIENT LINK DYNAMIC ==========
function updateEspaceClientLink() {
    const clientSpaceLink = document.getElementById('clientSpaceLink');
    const clientSpaceLinkMobile = document.querySelector('#mobileMenu a[href="pages/client.html"]');

    // Vérifier si l'utilisateur est connecté via sessionStorage
    const isLoggedIn = sessionStorage.getItem('isLoggedIn') === 'true';

    console.log('🔗 Espace Client Link Update:', isLoggedIn ? 'Logged in' : 'Not logged in');

    if (isLoggedIn) {
        if (clientSpaceLink) {
            clientSpaceLink.href = 'pages/client.html';
        }
        if (clientSpaceLinkMobile) {
            clientSpaceLinkMobile.href = 'pages/client.html';
        }
    } else {
        if (clientSpaceLink) {
            clientSpaceLink.href = 'pages/login.html';
        }
        if (clientSpaceLinkMobile) {
            clientSpaceLinkMobile.href = 'pages/login.html';
        }
    }
}

function initAuthHandler() {
    const authForm = document.getElementById('authForm');
    if (!authForm) return;

    authForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        const errorDiv = document.getElementById('authError');

        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ Vérification...';
        errorDiv.classList.add('hidden');

        try {
            const formData = new FormData(this);
            const payload = {
                action: 'register',
                nom: formData.get('nom'),
                email: formData.get('email'),
                telephone: formData.get('telephone')
            };
            const response = await fetch(CONFIG.ACCOUNT_API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                clientData = {
                    nom: payload.nom,
                    email: payload.email,
                    telephone: payload.telephone
                };
                showDevisForm();
                showNotification('✅ Compte créé avec succès !');
            } else {
                // Try login if registration failed (account exists)
                if (response.status === 409) {
                    const loginPayload = {
                        action: 'login',
                        email: payload.email,
                        telephone: payload.telephone
                    };

                    const loginResponse = await fetch(CONFIG.ACCOUNT_API_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(loginPayload)
                    });

                    const loginResult = await safeParseJson(loginResponse);

                    if (loginResponse.ok && loginResult.success) {
                        clientData = loginResult.client;
                        showDevisForm();
                        showNotification('✅ Connexion réussie !');
                    } else {
                        errorDiv.textContent = loginResult.message || 'Email ou téléphone incorrect';
                        errorDiv.classList.remove('hidden');
                    }
                } else {
                    errorDiv.textContent = result.message || 'Erreur lors de la connexion';
                    errorDiv.classList.remove('hidden');
                }
            }
        } catch (error) {
            console.error('Auth error:', error);
            errorDiv.textContent = 'Erreur de connexion au serveur';
            errorDiv.classList.remove('hidden');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

function showDevisForm() {
    document.getElementById('authSection').style.display = 'none';
    document.getElementById('devisForm').classList.remove('hidden');

    if (clientData) {
        const clientNameEl = document.getElementById('clientName');
        if (clientNameEl) {
            clientNameEl.textContent = clientData.nom || clientData.email;
        }
    }
}

window.logoutClient = async function () {
    try {
        await fetch(CONFIG.ACCOUNT_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        });

        clientData = null;
        sessionStorage.clear();

        document.getElementById('authSection').style.display = 'block';
        document.getElementById('devisForm').classList.add('hidden');
        document.getElementById('authForm').reset();

        checkDevisAccess();
        updateEspaceClientLink();

        showNotification('Déconnexion réussie');
    } catch (error) {
        console.error('Logout error:', error);
        sessionStorage.clear();
        clientData = null;
    }
};

// ========== FORM HANDLER ==========
function initFormHandler() {
    const devisForm = document.getElementById('devisForm');
    if (!devisForm) return;

    // Check for success parameter
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'true') {
        showSuccessMessage();
    }

    // Form submission
    devisForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        if (!isAuthenticated()) {
            showNotification('❌ Veuillez vous connecter d\'abord', 'error');
            return;
        }

        if (!clientData) {
            clientData = hydrateClientDataFromSession();
        }

        if (!clientData) {
            showNotification('❌ Erreur de session, veuillez vous reconnecter', 'error');
            return;
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        // Disable button and show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="emoji">⏳</span> Envoi en cours...';

        try {
            const formData = new FormData(this);

            // Construct vehicle data
            const vehiculeData = {
                marque: formData.get('marque'),
                modele: formData.get('modele'),
                budget: parseFloat(formData.get('budget')),
                annee_minimum: formData.get('annee_minimum') ? parseInt(formData.get('annee_minimum')) : null,
                kilometrage_max: formData.get('kilometrage_max') ? parseInt(formData.get('kilometrage_max')) : null,
                options: formData.get('options') || '',
                commentaires: formData.get('commentaires') || ''
            };

            // Prepare payload with client data
            const payload = {
                nom: clientData.nom,
                email: clientData.email,
                telephone: clientData.telephone || '',
                vehicule: vehiculeData,
                rgpd_consent: formData.get('rgpd') === 'on',
                source: 'website',
                timestamp: new Date().toISOString()
            };

            // Send to backend
            const response = await fetch(CONFIG.API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                // Track conversion
                trackConversion('devis_submit', {
                    marque: vehiculeData.marque,
                    budget: vehiculeData.budget
                });

                // Show success message
                showSuccessMessage();

                // Reset form
                this.reset();

                showNotification('✅ Demande envoyée avec succès !');

                // Scroll to success message
                setTimeout(() => {
                    const successMsg = document.getElementById('successMessage');
                    if (successMsg) {
                        successMsg.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }, 300);
            } else {
                throw new Error('Erreur serveur');
            }

        } catch (error) {
            console.error('Error:', error);
            showNotification('❌ Erreur lors de l\'envoi. Veuillez réessayer ou nous contacter par email.', 'error');

            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    // Real-time validation
    const emailInput = devisForm.querySelector('#email');
    if (emailInput) {
        emailInput.addEventListener('blur', function () {
            validateEmail(this);
        });
    }

    const phoneInput = devisForm.querySelector('#telephone');
    if (phoneInput) {
        phoneInput.addEventListener('blur', function () {
            validatePhone(this);
        });
    }
}

// ========== VALIDATION HELPERS ==========
function validateEmail(input) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!regex.test(input.value)) {
        input.classList.add('border-red-500');
        showFieldError(input, 'Email invalide');
        return false;
    }
    input.classList.remove('border-red-500');
    removeFieldError(input);
    return true;
}

function validatePhone(input) {
    const regex = /^(\+33|0)[1-9](\d{2}){4}$/;
    const cleanPhone = input.value.replace(/\s/g, '');
    if (!regex.test(cleanPhone)) {
        input.classList.add('border-red-500');
        showFieldError(input, 'Numéro invalide (ex: 06 12 34 56 78)');
        return false;
    }
    input.classList.remove('border-red-500');
    removeFieldError(input);
    return true;
}

function showFieldError(input, message) {
    removeFieldError(input);
    const error = document.createElement('div');
    error.className = 'text-red-500 text-sm mt-1 field-error';
    error.textContent = message;
    input.parentElement.appendChild(error);
}

function removeFieldError(input) {
    const error = input.parentElement.querySelector('.field-error');
    if (error) error.remove();
}

// ========== SUCCESS MESSAGE ==========
function showSuccessMessage() {
    const form = document.getElementById('devisForm');
    const successMsg = document.getElementById('successMessage');

    if (form && successMsg) {
        form.style.display = 'none';
        successMsg.classList.remove('hidden');

        // Scroll to success message
        successMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// ========== NOTIFICATIONS ==========
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    const bgColor = type === 'success'
        ? 'bg-gradient-to-r from-green-600 to-emerald-600'
        : 'bg-gradient-to-r from-red-600 to-rose-600';

    notification.className = `fixed top-6 right-6 ${bgColor} text-white px-6 py-4 rounded-xl shadow-2xl z-50 font-semibold animate-fade-in`;
    notification.setAttribute('role', 'alert');
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.transition = 'opacity 0.5s, transform 0.5s';
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-20px)';
        setTimeout(() => notification.remove(), 500);
    }, 4000);
}

// ========== MOBILE MENU ==========
function initMobileMenu() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');

    if (!mobileMenuBtn || !mobileMenu) return;

    mobileMenuBtn.addEventListener('click', () => {
        const isOpen = !mobileMenu.classList.contains('hidden');

        if (isOpen) {
            mobileMenu.classList.add('hidden');
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
        } else {
            mobileMenu.classList.remove('hidden');
            mobileMenuBtn.setAttribute('aria-expanded', 'true');
        }
    });

    // Close menu on link click
    mobileMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
        });
    });

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
        }
    });
}

// ========== NAVBAR SCROLL EFFECT ==========
function initNavbarScroll() {
    const nav = document.querySelector('nav');
    if (!nav) return;

    let lastScroll = 0;

    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;

        if (currentScroll > 100) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }

        lastScroll = currentScroll;
    });
}
// Base de données des véhicules
const vehiclesData = {
    nissan: {
        title: "Nissan 350Z",
        image: "images/350z.jpg",
        description: "Voiture de sport emblématique avec son moteur V6 3.5L atmosphérique. Design intemporel et plaisir de conduite garanti.",
        priceFR: "20 000 €",
        priceImport: "8 000 €",
        savings: "12 000 €",
        specs: [
            { label: "Moteur", value: "V6 3.5L 280ch" },
            { label: "Transmission", value: "Manuelle 6 vitesses" },
            { label: "0-100 km/h", value: "5.9 secondes" },
            { label: "Année", value: "2003-2009" },
            { label: "Carburant", value: "Essence" }
        ]
    },
    bmw: {
        title: "BMW M3 F80",
        image: "images/m3_f50.webp",
        description: "La berline sportive ultime avec son moteur 6 cylindres en ligne biturbo. Performance, luxe et polyvalence au quotidien.",
        priceFR: "50 000 €",
        priceImport: "40 000 €",
        savings: "10 000 €",
        specs: [
            { label: "Moteur", value: "6 cylindres 3.0L Biturbo 431ch" },
            { label: "Transmission", value: "Manuelle ou DCT 7 vitesses" },
            { label: "0-100 km/h", value: "4.1 secondes" },
            { label: "Année", value: "2014-2018" },
            { label: "Carburant", value: "Essence" }
        ]
    },
    ford: {
        title: "Ford Focus RS",
        image: "images/focusRS.webp",
        description: "Hot hatch ultime avec moteur 4 cylindres turbo et transmission intégrale. Performance accessible et polyvalente.",
        priceFR: "35 000 €",
        priceImport: "18 000 €",
        savings: "17 000 €",
        specs: [
            { label: "Moteur", value: "4 cylindres 2.3L Turbo 350ch" },
            { label: "Transmission", value: "Manuelle 6 vitesses" },
            { label: "0-100 km/h", value: "4.7 secondes" },
            { label: "Année", value: "2016-2018" },
            { label: "Carburant", value: "Essence" }
        ]
    }
};

// Ouvrir le modal
function openVehicleModal(vehicleId) {
    const vehicle = vehiclesData[vehicleId];
    if (!vehicle) return;

    // Remplir les données
    document.getElementById('modalTitle').textContent = vehicle.title;
    document.getElementById('modalImage').src = vehicle.image;
    document.getElementById('modalImage').alt = vehicle.title;
    document.getElementById('modalDescription').innerHTML = `<p>${vehicle.description}</p>`;
    document.getElementById('modalPriceFR').textContent = vehicle.priceFR;
    document.getElementById('modalPriceImport').textContent = vehicle.priceImport;
    document.getElementById('modalSavings').textContent = vehicle.savings;

    // Remplir les specs
    const specsHTML = vehicle.specs.map(spec => `
        <div class="flex justify-between items-center py-3 border-b border-gray-800">
            <span class="text-gray-400">${spec.label}</span>
            <span class="font-bold text-white">${spec.value}</span>
        </div>
    `).join('');
    document.getElementById('modalSpecs').innerHTML = specsHTML;

    // Afficher le modal
    document.getElementById('vehicleModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Track l'ouverture
    if (typeof trackConversion === 'function') {
        trackConversion('vehicle_detail_view', { vehicle: vehicle.title });
    }
}

// Fermer le modal
function closeVehicleModal() {
    document.getElementById('vehicleModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Fermer avec Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeVehicleModal();
    }
});

// Fermer en cliquant sur le fond
document.getElementById('vehicleModal')?.addEventListener('click', function (e) {
    if (e.target.id === 'vehicleModal') {
        closeVehicleModal();
    }
});

// ========== FAQ TOGGLE ==========
window.toggleFAQ = function (button) {
    const answer = button.nextElementSibling;
    const icon = button.querySelector('svg');
    const isOpen = !answer.classList.contains('hidden');

    if (isOpen) {
        answer.classList.add('hidden');
        icon.classList.remove('rotate-180');
        button.setAttribute('aria-expanded', 'false');
    } else {
        answer.classList.remove('hidden');
        icon.classList.add('rotate-180');
        button.setAttribute('aria-expanded', 'true');
    }
};

// ========== LAZY LOADING ==========
function initLazyLoading() {
    const images = document.querySelectorAll('img[loading="lazy"]');

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        images.forEach(img => img.classList.add('loaded'));
    }
}

// ========== ACCESSIBILITY ==========
function initAccessibility() {
    // Add ARIA labels where needed
    const buttons = document.querySelectorAll('button:not([aria-label])');
    buttons.forEach(btn => {
        if (!btn.getAttribute('aria-label') && btn.textContent) {
            btn.setAttribute('aria-label', btn.textContent.trim());
        }
    });

    // Improve focus visibility
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Tab') {
            document.body.classList.add('keyboard-navigation');
        }
    });

    document.addEventListener('mousedown', () => {
        document.body.classList.remove('keyboard-navigation');
    });
}

// ========== PLUIE D'ÉTOILES ==========
function createStarParticles() {
    const container = document.getElementById('particles');
    if (!container) return;

    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 15 + 's';
        particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
        container.appendChild(particle);
    }
}

// ========== COOKIE MANAGEMENT ==========
window.acceptCookies = function () {
    localStorage.setItem('cookieConsent', 'accepted');
    document.getElementById('cookieBanner').classList.add('hidden');

    // Initialize tracking
    initAnalytics();

    showNotification('✅ Préférences enregistrées');
};

window.refuseCookies = function () {
    localStorage.setItem('cookieConsent', 'refused');
    document.getElementById('cookieBanner').classList.add('hidden');

    showNotification('Préférences enregistrées');
};

function showCookieBanner() {
    const consent = localStorage.getItem('cookieConsent');
    const banner = document.getElementById('cookieBanner');

    if (!consent && banner) {
        banner.classList.remove('hidden');
    }
}

// Show cookie banner on load
window.addEventListener('load', showCookieBanner);

// ========== ANALYTICS & TRACKING ==========
function initAnalytics() {
    const consent = localStorage.getItem('cookieConsent');
    if (consent !== 'accepted') return;

    // Google Analytics 4 initialization
    if (typeof gtag !== 'undefined') {
        gtag('consent', 'update', {
            'analytics_storage': 'granted'
        });
    }
}

function trackPageView() {
    const consent = localStorage.getItem('cookieConsent');
    if (consent !== 'accepted') return;

    if (typeof gtag !== 'undefined') {
        gtag('event', 'page_view', {
            page_title: document.title,
            page_location: window.location.href,
            page_path: window.location.pathname
        });
    }
}

function trackConversion(eventName, params = {}) {
    const consent = localStorage.getItem('cookieConsent');
    if (consent !== 'accepted') return;

    if (typeof gtag !== 'undefined') {
        gtag('event', eventName, params);
    }

    console.log('Conversion tracked:', eventName, params);
}

// Track CTA clicks
document.addEventListener('click', (e) => {
    const target = e.target.closest('a[href*="#devis"], button[type="submit"]');
    if (target) {
        trackConversion('cta_click', {
            cta_text: target.textContent.trim(),
            cta_location: target.closest('section')?.id || 'unknown'
        });
    }
});

// Track phone clicks
document.querySelectorAll('a[href^="tel:"]').forEach(link => {
    link.addEventListener('click', () => {
        trackConversion('phone_click', {
            phone_number: link.href.replace('tel:', '')
        });
    });
});

// Track email clicks
document.querySelectorAll('a[href^="mailto:"]').forEach(link => {
    link.addEventListener('click', () => {
        trackConversion('email_click', {
            email: link.href.replace('mailto:', '')
        });
    });
});

// ========== PERFORMANCE MONITORING ==========
window.addEventListener('load', () => {
    // Measure Core Web Vitals
    if ('PerformanceObserver' in window) {
        // LCP - Largest Contentful Paint
        const lcpObserver = new PerformanceObserver((list) => {
            const entries = list.getEntries();
            const lastEntry = entries[entries.length - 1];
            console.log('LCP:', lastEntry.renderTime || lastEntry.loadTime);
        });
        lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });

        // FID - First Input Delay
        const fidObserver = new PerformanceObserver((list) => {
            const entries = list.getEntries();
            entries.forEach(entry => {
                console.log('FID:', entry.processingStart - entry.startTime);
            });
        });
        fidObserver.observe({ entryTypes: ['first-input'] });

        // CLS - Cumulative Layout Shift
        let clsScore = 0;
        const clsObserver = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (!entry.hadRecentInput) {
                    clsScore += entry.value;
                    console.log('CLS:', clsScore);
                }
            }
        });
        clsObserver.observe({ entryTypes: ['layout-shift'] });
    }
});

// ========== INTERSECTION OBSERVER FOR ANIMATIONS ==========
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const sectionObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe all sections
document.querySelectorAll('section').forEach(section => {
    section.style.opacity = '0';
    section.style.transform = 'translateY(30px)';
    section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    sectionObserver.observe(section);
});

// ========== ERROR HANDLING ==========
window.addEventListener('error', (e) => {
    console.error('Global error:', e.error);

    // Log to error tracking service (e.g., Sentry)
    // Sentry.captureException(e.error);
});

window.addEventListener('unhandledrejection', (e) => {
    console.error('Unhandled promise rejection:', e.reason);

    // Log to error tracking service
    // Sentry.captureException(e.reason);
});

// ========== UTILITY FUNCTIONS ==========
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function () {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ========== EXPORT FOR TESTING ==========
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        validateEmail,
        validatePhone,
        trackConversion
    };
}

console.log('✅ NextDrive Import - Script v2.1.0 chargé avec succès');
