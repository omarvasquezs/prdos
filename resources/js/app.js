import './bootstrap';
import * as bootstrap from 'bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './components/App.vue';

// Importar layouts y componentes globales
import AdminLayout from './layouts/AdminLayout.vue';

const app = createApp(App);
const pinia = createPinia();

// Make bootstrap globally available
window.bootstrap = bootstrap;

// Registrar componentes globalmente
app.component('AdminLayout', AdminLayout);

app.use(pinia);
app.use(router);

if (document.getElementById('app')) {
    app.mount('#app');
}

// Back to top button functionality
document.addEventListener('scroll', () => {
    const backToTopButton = document.querySelector('.back-to-top');
    if (backToTopButton && window.scrollY > 200) {
        backToTopButton.classList.add('show');
    } else if (backToTopButton) {
        backToTopButton.classList.remove('show');
    }
});
