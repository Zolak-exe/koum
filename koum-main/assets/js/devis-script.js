// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    
    // Récupérer les données du véhicule depuis sessionStorage
    const vehiculeData = JSON.parse(sessionStorage.getItem('vehiculeData'));

    if (!vehiculeData) {
        console.error('Données du véhicule introuvables');
        alert('Erreur: Veuillez d\'abord remplir le formulaire de recherche de véhicule.');
        window.location.href = 'index.html#devis';
        return;
    }

    const contactForm = document.getElementById('contactForm');
    
    if (!contactForm) {
        console.error('Formulaire contactForm introuvable');
        return;
    }

    contactForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Envoi en cours...';

        const data = {
            id: Date.now(),
            timestamp: new Date().toISOString(),
            client: {
                prenom: document.getElementById('prenom').value,
                nom: document.getElementById('nom').value,
                email: document.getElementById('email').value,
                telephone: document.getElementById('telephone').value
            },
            vehicule: vehiculeData,
            statut: "nouveau"
        };

        try {
            const response = await fetch('save_client.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                sessionStorage.removeItem('vehiculeData');
                window.location.href = 'index.html?success=true#devis';
            } else {
                throw new Error(result.message || 'Erreur lors de l\'enregistrement');
            }
        } catch (error) {
            console.error('Erreur complète:', error);
            alert('Erreur lors de l\'envoi: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Recevoir mon devis gratuit';
        }
    });

});