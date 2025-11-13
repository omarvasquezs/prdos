import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Interceptor para manejar errores de sesión expirada
window.axios.interceptors.response.use(
    response => response,
    error => {
        // Si la respuesta es 401 y contiene el flag de sesión expirada
        if (error.response && error.response.status === 401) {
            if (error.response.data && error.response.data.expired) {
                // Mostrar alerta
                alert('Su sesión ha expirado. Por favor, inicie sesión nuevamente.');
                
                // Redirigir al login
                window.location.href = '/login';
            }
        }
        
        return Promise.reject(error);
    }
);
