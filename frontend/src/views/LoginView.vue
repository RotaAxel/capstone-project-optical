<template>
  <div class="login-page">

    <!-- ── Left Brand Panel ───────────────────────────────── -->
    <div class="brand-panel">
      <div class="brand-inner">
        <!-- Logo -->
        <div class="brand-logo-wrap">
          <img src="/Logo.png" alt="Acebedo Optical" class="brand-logo" />
        </div>
        <h1 class="brand-name">ACEBEDO</h1>
        <p class="brand-tagline">Optical Clinic</p>

        <div class="brand-divider"></div>

        <p class="brand-desc">
          Integrated clinic management for patient records, inventory, prescriptions, and predictive analytics.
        </p>

        <!-- Feature pills -->
        <div class="brand-features">
          <span class="feat-pill">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            Patient Records
          </span>
          <span class="feat-pill">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
            Prescriptions
          </span>
          <span class="feat-pill">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            Inventory
          </span>
          <span class="feat-pill">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4"/></svg>
            Analytics
          </span>
        </div>
      </div>

      <!-- Decorative circles -->
      <div class="deco deco-1"></div>
      <div class="deco deco-2"></div>
      <div class="deco deco-3"></div>
    </div>

    <!-- ── Right Form Panel ───────────────────────────────── -->
    <div class="form-panel">
      <div class="form-inner">

        <!-- Header -->
        <div class="form-head">
          <h2 class="form-title">Welcome back</h2>
          <p class="form-sub">Sign in to your account to continue</p>
        </div>

        <form @submit.prevent="handleLogin" class="login-form">

          <!-- Email -->
          <div class="fg">
            <label class="fl">Email Address</label>
            <div class="input-wrap">
              <svg class="fi-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
              </svg>
              <input v-model="form.email" type="email" placeholder="you@acebedo.com"
                class="fi" :class="{ 'fi-error': errors.email }"
                required autocomplete="email" @blur="validateEmail" />
            </div>
            <span v-if="errors.email" class="err">{{ errors.email }}</span>
          </div>

          <!-- Password -->
          <div class="fg">
            <label class="fl">Password</label>
            <div class="input-wrap">
              <svg class="fi-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              <input v-model="form.password" :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••" class="fi" :class="{ 'fi-error': errors.password }"
                required autocomplete="current-password" @blur="validatePassword" />
              <button type="button" @click="showPassword = !showPassword" class="eye-btn"
                :title="showPassword ? 'Hide' : 'Show'">
                <svg v-if="!showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>
            <span v-if="errors.password" class="err">{{ errors.password }}</span>
          </div>

          <!-- Login error -->
          <div v-if="error" class="login-error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            {{ error }}
          </div>

          <button type="submit" class="btn-login" :disabled="loading">
            <svg v-if="loading" class="spin" width="16" height="16" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ loading ? 'Signing in…' : 'Sign In' }}
          </button>
        </form>

        <!-- Demo credentials -->
        <div class="demo-box">
          <p class="demo-heading">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            Demo Accounts — click to fill
          </p>
          <div class="demo-grid">
            <button v-for="d in demoAccounts" :key="d.email" type="button"
              @click="fillDemo(d)" class="demo-btn"
              :class="{ active: form.email === d.email }">
              <span class="demo-role">{{ d.role }}</span>
              <span class="demo-email">{{ d.email }}</span>
            </button>
          </div>
          <p class="demo-pw">Password for all: <code>password</code></p>
        </div>

        <p class="form-footer">&copy; 2026 Acebedo Optical Clinic</p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { ValidationRules } from '@/utils/validation'

const router = useRouter()
const auth   = useAuthStore()

const form         = reactive({ email: '', password: '' })
const errors       = reactive({ email: '', password: '' })
const loading      = ref(false)
const error        = ref('')
const showPassword = ref(false)

const demoAccounts = [
  { role: 'Admin',           email: 'admin@acebedo.com' },
  { role: 'Receptionist',    email: 'reception@acebedo.com' },
  { role: 'Optometrist',     email: 'optometrist@acebedo.com' },
  { role: 'Inventory Staff', email: 'inventory@acebedo.com' },
]

function fillDemo(d) {
  form.email    = d.email
  form.password = 'password'
  errors.email    = ''
  errors.password = ''
  error.value   = ''
}

function validateEmail() {
  if (!form.email) {
    errors.email = 'Email is required'
  } else if (!ValidationRules.isValidEmail(form.email)) {
    errors.email = 'Please enter a valid email address'
  } else {
    errors.email = ''
  }
}

function validatePassword() {
  if (!form.password) {
    errors.password = 'Password is required'
  } else if (form.password.length < 8) {
    errors.password = 'Password must be at least 8 characters'
  } else {
    errors.password = ''
  }
}

async function handleLogin() {
  validateEmail()
  validatePassword()
  if (errors.email || errors.password) return

  error.value   = ''
  loading.value = true
  try {
    await auth.login(form.email, form.password)
    await router.push('/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* ── Layout ──────────────────────────────────────────────── */
.login-page {
  display: flex;
  min-height: 100vh;
  font-family: var(--font-main);
}

/* ── Brand Panel ─────────────────────────────────────────── */
.brand-panel {
  width: 42%;
  min-height: 100vh;
  background: linear-gradient(150deg, #0f2044 0%, #1a3a6e 40%, #0e7490 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  flex-shrink: 0;
}

.brand-inner {
  position: relative;
  z-index: 2;
  padding: 48px 40px;
  text-align: center;
  max-width: 320px;
}

.brand-logo-wrap {
  width: 88px;
  height: 88px;
  border-radius: 22px;
  background: rgba(255,255,255,0.12);
  border: 2px solid rgba(255,255,255,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  backdrop-filter: blur(8px);
}

.brand-logo {
  width: 56px;
  height: 56px;
  object-fit: contain;
  border-radius: 8px;
}

.brand-name {
  font-size: 32px;
  font-weight: 900;
  color: #fff;
  letter-spacing: 4px;
  margin: 0 0 4px;
}

.brand-tagline {
  font-size: 14px;
  font-weight: 600;
  color: rgba(255,255,255,0.65);
  letter-spacing: 1px;
  margin: 0;
}

.brand-divider {
  width: 48px;
  height: 3px;
  background: linear-gradient(90deg, #5bc8c0, #38bdf8);
  border-radius: 2px;
  margin: 24px auto;
}

.brand-desc {
  font-size: 13px;
  color: rgba(255,255,255,0.6);
  line-height: 1.7;
  margin: 0 0 28px;
}

.brand-features {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
}

.feat-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border-radius: 20px;
  background: rgba(255,255,255,0.1);
  border: 1px solid rgba(255,255,255,0.15);
  color: rgba(255,255,255,0.85);
  font-size: 11px;
  font-weight: 600;
  backdrop-filter: blur(4px);
}

/* Decorative circles */
.deco {
  position: absolute;
  border-radius: 50%;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.06);
  pointer-events: none;
}
.deco-1 { width: 320px; height: 320px; top: -80px;  right: -80px; }
.deco-2 { width: 220px; height: 220px; bottom: -50px; left: -60px; }
.deco-3 { width: 140px; height: 140px; bottom: 120px; right: 30px; background: rgba(91,200,192,0.06); }

/* ── Form Panel ──────────────────────────────────────────── */
.form-panel {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  padding: 40px 24px;
  overflow-y: auto;
}

.form-inner {
  width: 100%;
  max-width: 400px;
}

.form-head {
  margin-bottom: 32px;
}

.form-title {
  font-size: 26px;
  font-weight: 800;
  color: var(--navy);
  margin: 0 0 6px;
}

.form-sub {
  font-size: 13px;
  color: var(--muted);
  margin: 0;
}

/* ── Form Controls ───────────────────────────────────────── */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
  margin-bottom: 24px;
}

.fg { display: flex; flex-direction: column; gap: 6px; }
.fl { font-size: 12px; font-weight: 700; color: #374151; }

.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.fi-icon {
  position: absolute;
  left: 13px;
  color: #9ca3af;
  pointer-events: none;
}

.fi {
  width: 100%;
  padding: 11px 42px;
  border: 1.5px solid #e5e7eb;
  border-radius: 11px;
  font-family: var(--font-main);
  font-size: 13px;
  color: var(--navy);
  background: #fff;
  outline: none;
  box-shadow: 0 1px 3px rgba(0,0,0,.04);
  transition: border-color .2s, box-shadow .2s;
  box-sizing: border-box;
}

.fi:focus {
  border-color: var(--teal);
  box-shadow: 0 0 0 3px rgba(91,200,192,.15);
}

.fi::placeholder { color: #c4c9d4; }
.fi-error { border-color: #f87171 !important; }

.eye-btn {
  position: absolute;
  right: 13px;
  background: none;
  border: none;
  cursor: pointer;
  color: #9ca3af;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color .2s;
}
.eye-btn:hover { color: var(--navy); }

.err { font-size: 11px; color: #ef4444; font-weight: 600; }

.login-error {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 11px 14px;
  background: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
  color: #b91c1c;
}

.btn-login {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 12px 20px;
  background: linear-gradient(135deg, var(--teal) 0%, #0891b2 100%);
  color: #fff;
  border: none;
  border-radius: 11px;
  font-family: var(--font-main);
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(91,200,192,.4);
  transition: transform .2s, box-shadow .2s, opacity .2s;
  margin-top: 4px;
}

.btn-login:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 22px rgba(91,200,192,.45);
}

.btn-login:active:not(:disabled) { transform: translateY(0); }
.btn-login:disabled { opacity: .6; cursor: not-allowed; }

.spin { animation: spin 0.9s linear infinite; }

@keyframes spin { to { transform: rotate(360deg); } }

/* ── Demo Box ─────────────────────────────────────────────── */
.demo-box {
  background: #fff;
  border: 1.5px solid #e5e7eb;
  border-radius: 14px;
  padding: 16px 18px;
  margin-bottom: 24px;
}

.demo-heading {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: .6px;
  margin: 0 0 12px;
}

.demo-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-bottom: 12px;
}

.demo-btn {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
  padding: 9px 11px;
  border: 1.5px solid #e5e7eb;
  border-radius: 9px;
  background: #f9fafb;
  cursor: pointer;
  font-family: var(--font-main);
  transition: border-color .2s, background .2s;
  text-align: left;
}

.demo-btn:hover { border-color: var(--teal); background: #f0fdfa; }
.demo-btn.active { border-color: var(--teal); background: #f0fdfa; }

.demo-role  { font-size: 11px; font-weight: 700; color: #374151; }
.demo-email { font-size: 10px; color: #9ca3af; font-family: 'Courier New', monospace; }

.demo-pw {
  font-size: 11px;
  color: #9ca3af;
  margin: 0;
}

.demo-pw code {
  background: #f3f4f6;
  padding: 1px 6px;
  border-radius: 4px;
  font-size: 11px;
  color: #374151;
  font-weight: 700;
}

.form-footer {
  text-align: center;
  font-size: 11px;
  color: #c4c9d4;
  margin: 0;
}

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 768px) {
  .brand-panel { display: none; }
  .form-panel  { background: #fff; }
}
</style>
