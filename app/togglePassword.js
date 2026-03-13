// Verificamos que el script carga
console.log("El archivo JS se ha cargado correctamente");

document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.querySelector('#togglePassword');
    const passwordInput = document.querySelector('#clave');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            // Verificamos que el clic funciona
            console.log("Clic detectado");
            
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🕶️';
        });
    } else {
        console.error("No se encontró el input o el icono. Revisa los IDs en tu HTML.");
    }
});