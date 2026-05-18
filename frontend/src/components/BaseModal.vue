<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="modal-overlay" @mousedown.self="close">
        <div class="modal-box" :style="{ maxWidth: width }">
          <div class="modal-header">
            <h3 class="modal-title">{{ title }}</h3>
            <button class="modal-close" @click="close">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
          <div class="modal-body"><slot /></div>
          <div class="modal-footer">
            <slot name="footer">
              <button class="btn btn-ghost" @click="close">Cancel</button>
              <button class="btn btn-teal" @click="$emit('confirm')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                {{ confirmLabel }}
              </button>
            </slot>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  modelValue:   { type: Boolean, default: false },
  title:        { type: String,  default: 'Modal' },
  confirmLabel: { type: String,  default: 'Save' },
  width:        { type: String,  default: '540px' },
})
const emit = defineEmits(['update:modelValue', 'confirm'])
const close = () => emit('update:modelValue', false)
</script>

<style scoped>
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(26,39,68,0.5);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999; padding: 20px;
  backdrop-filter: blur(4px);
}
.modal-box {
  background: #fff; border-radius: 18px;
  box-shadow: 0 24px 64px rgba(26,39,68,0.22);
  width: 100%; max-height: 90vh;
  display: flex; flex-direction: column; overflow: hidden;
}
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 22px 26px 16px; border-bottom: 1px solid var(--border); flex-shrink: 0;
}
.modal-title { font-size: 17px; font-weight: 800; color: var(--navy); }
.modal-close {
  width: 32px; height: 32px; border-radius: 50%;
  border: 1.5px solid var(--border); background: #fff;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: var(--slate); transition: all var(--duration) var(--ease);
}
.modal-close:hover { background: #FEE2E2; border-color: var(--danger); color: var(--danger); }
.modal-body { padding: 22px 26px; overflow-y: auto; flex: 1; }
.modal-footer {
  display: flex; align-items: center; justify-content: flex-end; gap: 10px;
  padding: 16px 26px; border-top: 1px solid var(--border); flex-shrink: 0; background: var(--bg);
}
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active .modal-box, .modal-leave-active .modal-box { transition: transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal-box { transform: translateY(-14px) scale(0.97); }
.modal-leave-to .modal-box   { transform: translateY(8px); opacity: 0; }
</style>