document.addEventListener('DOMContentLoaded', function() {
    // Verificar si hay un mensaje de sesión expirada en la URL
    if (window.location.href.indexOf('expired=true') > -1) {
        window.location.href = '/login?expired=true';
    }
    
    // Detectar cuando el navegador usa el botón de atrás
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            // La página fue restaurada desde el caché del navegador (botón atrás)
            // Hacer una solicitud AJAX para verificar si la sesión sigue activa
            fetch('/login', {
                method: 'HEAD',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function(response) {
                if (response.redirected || response.status === 401) {
                    // La sesión ha expirado, redirigir al login
                    window.location.href = '/login?expired=true';
                }
            }).catch(function(error) {
                console.error('Error verificando sesión:', error);
            });
        }
    });
}); 