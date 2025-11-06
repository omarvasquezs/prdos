<template>
  <div class="login-container" id="login">
    <!-- Fullscreen Loading Overlay -->
    <div v-if="authenticating" class="fullscreen-loading-overlay">
      <div class="loading-content">
        <div class="loading-spinner"></div>
        <h3 class="loading-text">Iniciando sesión...</h3>
        <p class="loading-subtext">Por favor espera un momento</p>
      </div>
    </div>
    
    <form @submit.prevent="handleSubmit" class="login-form">
      <div class="text-center mb-4">
        <h1 class="app-title">POLLERIA P'RDOS</h1>
        <p class="app-version">v1.0.0</p>
      </div>

      <div class="form-group mb-3">
        <input
          id="username"
          v-model="username"
          @input="onUsernameInput"
          type="text"
          class="form-control form-control-touch username-input"
          placeholder="USUARIO"
          required
          autocomplete="username"
          :disabled="isLoading"
          aria-label="Usuario"
        />
      </div>

      <div class="form-group mb-4">
        <input
          id="password"
          v-model="password"
          type="password"
          class="form-control form-control-touch"
          placeholder="CONTRASEÑA"
          required
          autocomplete="current-password"
          :disabled="isLoading"
          aria-label="Contraseña"
        />
      </div>

      <div class="form-check mb-4 touch-remember">
        <label for="remember" class="form-check-label d-flex align-items-center">
          <input
            id="remember"
            v-model="remember"
            type="checkbox"
            class="form-check-input me-3"
            :disabled="isLoading"
            aria-label="Recordarme"
          />
          <span class="fs-5">Recordarme</span>
        </label>
      </div>

      <button 
        type="submit" 
        class="btn btn-primary w-100 login-button" 
        :disabled="isLoading || authenticating"
        :class="{ 'loading': authenticating }"
      >
        <span v-if="!isLoading && !authenticating">ACCEDER</span>
        <span v-else-if="isLoading && !authenticating">CARGANDO...</span>
        <span v-else class="button-loading">
          <i class="fas fa-spinner fa-spin"></i>
          INICIANDO SESIÓN...
        </span>
      </button>

      <div v-if="errorMessage" class="alert alert-danger mt-3 mb-0" role="alert">
        {{ errorMessage }}
      </div>
    </form>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'LoginForm',
  data() {
    return {
      username: '',
      password: '',
      remember: false,
      errorMessage: '',
      isLoading: false,
      authenticating: false
    }
  },
  methods: {
    onUsernameInput(e) {
      // Force username to uppercase while typing
      this.username = e.target.value.toUpperCase();
    },
    async handleSubmit() {
      this.isLoading = true;
      this.errorMessage = '';

      try {
        // First, get CSRF cookie
        await axios.get('/sanctum/csrf-cookie');
        
        // Show fullscreen loading before making login request
        this.authenticating = true;
        
        // Realizar login
        const response = await axios.post('/login', {
          username: this.username,
          password: this.password,
          remember: this.remember
        });

        // If backend indicates a redirect (single role or selection), follow it.
        const redirect = response.data?.redirect || '/dashboard';
        
        // Keep the spinner visible during navigation
        window.location.href = redirect;
      } catch (error) {
        // Hide fullscreen loading on error
        this.authenticating = false;
        
        if (error.response?.status === 422) {
          // Error de validación
          const errors = error.response.data.errors;
          this.errorMessage = errors.username 
            ? errors.username[0] 
            : 'Las credenciales proporcionadas no coinciden con nuestros registros.';
        } else {
          this.errorMessage = error.response?.data?.message || 'Error al iniciar sesión. Por favor, intenta de nuevo.';
        }
      } finally {
        this.isLoading = false;
      }
      // Note: No finally block for authenticating - we want the spinner to stay visible until page navigation
    }
  },
  async mounted() {
    try {
      // Get CSRF cookie for future requests
      await axios.get('/sanctum/csrf-cookie');
    } catch (error) {
      console.log('Could not get CSRF cookie');
    }
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.login-form {
  background: white;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 450px;
}

.app-title {
  font-size: 2rem;
  font-weight: 800;
  color: #667eea;
  margin-bottom: 0.25rem;
  text-transform: uppercase;
}

.app-version {
  font-size: 0.875rem;
  color: #6c757d;
  margin-bottom: 0;
}

.login-form h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #333;
}

.form-label {
  font-weight: 500;
  color: #495057;
  margin-bottom: 0.5rem;
}

.form-control {
  padding: 0.75rem;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

/* Touch-optimized inputs */
.form-control-touch {
  padding: 1.25rem;
  font-size: 1.125rem;
  border-radius: 10px;
}

/* Placeholders always uppercase */
.form-control::placeholder {
  text-transform: uppercase;
}

/* Ensure username displays uppercase while typing */
.username-input {
  text-transform: uppercase;
}

.form-control:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-control:disabled {
  background-color: #e9ecef;
  cursor: not-allowed;
}

.form-check-input {
  cursor: pointer;
}

.form-check-input:checked {
  background-color: #667eea;
  border-color: #667eea;
}

.login-button {
  padding: 1.25rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 10px;
  font-size: 1.125rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  transition: all 0.2s ease;
}

.login-button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
}

.login-button:active:not(:disabled) {
  transform: translateY(0);
}

.login-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.alert-danger {
  background-color: #f8d7da;
  border-color: #f5c6cb;
  color: #721c24;
  border-radius: 6px;
  padding: 0.75rem 1rem;
}

@media (max-width: 576px) {
  .login-form {
    padding: 2rem 1.5rem;
  }

  .app-title {
    font-size: 1.6rem;
  }

  .login-form h2 {
    font-size: 1.25rem;
  }
}

/* Larger touch area for remember checkbox */
.touch-remember .form-check-input {
  width: 1.5rem;
  height: 1.5rem;
}

/* Slightly wider form for kiosk */
@media (min-width: 992px) {
  .login-form {
    max-width: 520px;
    padding: 3rem;
  }
}

/* Fullscreen Loading Overlay */
.fullscreen-loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  opacity: 0;
  animation: fadeIn 0.3s ease-out forwards;
}

@keyframes fadeIn {
  to {
    opacity: 1;
  }
}

.loading-content {
  text-align: center;
  color: white;
}

.loading-spinner {
  width: 60px;
  height: 60px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 30px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 10px;
  color: white;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.loading-subtext {
  font-size: 1.1rem;
  font-weight: 400;
  color: rgba(255, 255, 255, 0.9);
  margin: 0;
}

/* Button loading state */
.login-button.loading {
  opacity: 0.8;
  cursor: wait;
}

.button-loading {
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: center;
}

.button-loading i {
  font-size: 1rem;
}
</style>
