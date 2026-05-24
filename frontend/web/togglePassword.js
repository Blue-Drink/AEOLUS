document.addEventListener('DOMContentLoaded', function() {
    
    // Función genérica para alternar visibilidad
    function setupPasswordToggle(buttonId, inputId) {
        const toggleBtn = document.querySelector(buttonId);
        const inputField = document.querySelector(inputId);

        if (toggleBtn && inputField) {
            toggleBtn.addEventListener('click', function() {
                const isPassword = inputField.getAttribute('type') === 'password';
                
                // Cambiar tipo de input
                inputField.setAttribute('type', isPassword ? 'text' : 'password');
                
                // Cambiar el icono/texto
                this.textContent = isPassword ? '🕶️' : '👁️';
                
                console.log(`Cambiado estado de ${inputId}`);
            });
        } else {
            console.error(`No se encontró el botón ${buttonId} o el input ${inputId}`);
        }
    }

    // Aplicamos la lógica a ambos pares de IDs
    setupPasswordToggle('#togglePassword', '#clave');
    setupPasswordToggle('#togglePasswordConfirmation', '#confirmar_clave');
});