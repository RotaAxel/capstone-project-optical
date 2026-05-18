<template>
  <div class="login-container">
    <!-- Background -->
    <div class="login-bg"/>

    <!-- Content -->
    <div class="login-content">
      <!-- Logo & Branding -->
      <div class="logo-section">
        <img src="/Logo.png" alt="Acebedo Optical Clinic" class="logo-image" />
        <h1 class="logo-title">ACEBEDO</h1>
        <p class="logo-subtitle">Optical Clinic</p>
      </div>

      <!-- Login Card -->
      <div class="login-card">
        <div class="card-header">
          <h2 class="card-title">Sign In</h2>
          <p class="card-subtitle">Access your clinic management system</p>
        </div>

        <form @submit.prevent="handleLogin" class="login-form">
          <!-- Email Field -->
          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <div class="input-wrapper">
              <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
              </svg>
              <input 
                v-model="form.email" 
                type="email" 
                placeholder="admin@acebedo.com" 
                class="form-input"
                required 
                autocomplete="email"
                @blur="validateEmail"
              />
            </div>
            <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
          </div>

          <!-- Password Field -->
          <div class="form-group">
            <label class="form-label">Password *</label>
            <div class="input-wrapper">
              <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              <input 
                v-model="form.password" 
                :type="showPassword ? 'text' : 'password'" 
                placeholder="••••••••" 
                class="form-input"
                required 
                autocomplete="current-password"
                @blur="validatePassword"
              />
              <button 
                type="button" 
                @click="showPassword = !showPassword" 
                class="password-toggle"
                :title="showPassword ? 'Hide password' : 'Show password'"
              >
                <svg v-if="!showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>
            <span v-if="errors.password" class="error-message">{{ errors.password }}</span>
          </div>

          <!-- Error Alert -->
          <div v-if="error" class="alert alert-error">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>{{ error }}</span>
          </div>

          <!-- Login Button -->
          <button type="submit" class="btn-login" :disabled="loading">
            <svg v-if="loading" class="btn-spinner" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span>{{ loading ? 'Signing In...' : 'Sign In' }}</span>
          </button>
        </form>

        <!-- Demo Credentials -->
        <div class="demo-section">
          <p class="demo-title">Demo Accounts</p>
          <div class="demo-list">
            <div class="demo-item">
              <span class="demo-label">Admin:</span>
              <span class="demo-value">admin@acebedo.com</span>
            </div>
            <div class="demo-item">
              <span class="demo-label">Receptionist:</span>
              <span class="demo-value">reception@acebedo.com</span>
            </div>
            <div class="demo-item">
              <span class="demo-label">Optometrist:</span>
              <span class="demo-value">optometrist@acebedo.com</span>
            </div>
            <div class="demo-item">
              <span class="demo-label">Password:</span>
              <span class="demo-value">password</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <p class="login-footer">&copy; 2026 Acebedo Optical Clinic. All rights reserved.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { ValidationRules } from '@/utils/validation'
import { handleError } from '@/utils/errorHandler'

const router = useRouter()
const auth = useAuthStore()

const form = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })
const loading = ref(false)
const error = ref('')
const showPassword = ref(false)

// Validation functions
const validateEmail = () => {
  if (!form.email) {
    errors.email = 'Email is required'
  } else if (!ValidationRules.isValidEmail(form.email)) {
    errors.email = 'Please enter a valid email address'
  } else {
    errors.email = ''
  }
}

const validatePassword = () => {
  if (!form.password) {
    errors.password = 'Password is required'
  } else if (form.password.length < 6) {
    errors.password = 'Password must be at least 6 characters'
  } else {
    errors.password = ''
  }
}

// Form submission
async function handleLogin() {
  // Validate
  validateEmail()
  validatePassword()

  if (errors.email || errors.password) {
    return
  }

  error.value = ''
  loading.value = true

  try {
    console.log('Attempting login with:', form.email)
    
    // Use auth store's login method
    await auth.login(form.email, form.password)
    
    console.log('Login successful, redirecting to dashboard')
    
    // Redirect to dashboard
    await router.push('/dashboard')
    
  } catch (e) {
    console.error('Login error:', e)
    error.value = e.response?.data?.message || e.message || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

/* Background */
.login-bg {
  position: absolute;
  inset: 0;
  background-image: url('/bg.jpg');
  background-size: 100% 100%;
  background-position: center;
  background-repeat: no-repeat;
  background-attachment: fixed;
  z-index: 0;
}

/* Content wrapper */
.login-content {
  position: relative;
  z-index: 10;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  max-width: 420px;
  padding: 20px;
  min-height: 100vh;
  backdrop-filter: blur(2px);
}

/* Logo Section */
.logo-section {
  text-align: center;
  margin-bottom: 36px;
  animation: slideDown 0.6s ease-out;
}

.logo-image {
  width: 64px;
  height: 64px;
  object-fit: contain;
  margin-bottom: 14px;
  animation: zoomIn 0.5s ease-out;
}

.logo-title {
  font-family: var(--font-display);
  font-size: 28px;
  font-weight: 900;
  color: var(--navy);
  letter-spacing: 2px;
  margin-bottom: 4px;
}

.logo-subtitle {
  font-size: 13px;
  color: var(--slate);
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* Login Card */
.login-card {
  width: 100%;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 18px;
  border: 1px solid rgba(226, 232, 240, 0.6);
  box-shadow: 0 20px 60px rgba(26, 39, 68, 0.18);
  padding: 32px;
  animation: slideUp 0.6s ease-out;
}

.card-header {
  margin-bottom: 28px;
  text-align: center;
}

.card-title {
  font-family: var(--font-display);
  font-size: 24px;
  font-weight: 800;
  color: var(--navy);
  margin-bottom: 4px;
}

.card-subtitle {
  font-size: 13px;
  color: var(--muted);
}

/* Form */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-size: 12px;
  font-weight: 700;
  color: var(--navy);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 12px;
  color: var(--muted);
  pointer-events: none;
  flex-shrink: 0;
}

.form-input {
  width: 100%;
  padding: 10px 12px 10px 40px;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  font-family: var(--font-main);
  font-size: 13px;
  color: var(--navy);
  background: #fff;
  outline: none;
  transition: all var(--duration) var(--ease);
  box-sizing: border-box;
}

.form-input:focus {
  border-color: var(--teal);
  box-shadow: 0 0 0 3px rgba(91, 200, 192, 0.12);
}

.form-input::placeholder {
  color: var(--muted);
}

.password-toggle {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--muted);
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--duration) var(--ease);
}

.password-toggle:hover {
  color: var(--slate);
}

.error-message {
  font-size: 11px;
  color: var(--danger);
  font-weight: 600;
}

/* Alert */
.alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
}

.alert-error {
  background: #FEE2E2;
  border: 1px solid #FECACA;
  color: var(--danger);
}

/* Button */
.btn-login {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 20px;
  background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-family: var(--font-main);
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all var(--duration) var(--ease);
  margin-top: 8px;
}

.btn-login:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(91, 200, 192, 0.3);
}

.btn-login:active:not(:disabled) {
  transform: translateY(0);
}

.btn-login:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-spinner {
  width: 14px;
  height: 14px;
  animation: spin 1s linear infinite;
}

/* Demo Section */
.demo-section {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
}

.demo-title {
  font-size: 11px;
  font-weight: 700;
  color: var(--navy);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 10px;
}

.demo-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.demo-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
}

.demo-label {
  color: var(--slate);
  font-weight: 600;
}

.demo-value {
  color: var(--navy);
  font-family: 'Monaco', 'Courier New', monospace;
  font-size: 11px;
  font-weight: 700;
  background: var(--bg);
  padding: 3px 8px;
  border-radius: 4px;
}

/* Footer */
.login-footer {
  margin-top: 24px;
  font-size: 11px;
  color: rgba(255, 255, 255, 0.7);
  text-align: center;
}

/* Animations */
@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes zoomIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Responsive */
@media (max-width: 480px) {
  .login-card {
    padding: 24px;
  }

  .card-title {
    font-size: 20px;
  }

  .form-input {
    padding: 9px 10px 9px 36px;
    font-size: 14px;
  }

  .logo-image {
    width: 56px;
    height: 56px;
  }

  .logo-title {
    font-size: 24px;
  }
}
</style>