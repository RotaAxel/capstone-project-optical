<template>
  <header class="topbar">
    <!-- Nav links -->
    <nav class="topbar-nav">
      <RouterLink
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        class="top-nav-link"
        :class="{ active: isActive(item.to) }"
      >
        {{ item.label }}
      </RouterLink>
    </nav>

    <!-- Action slot for page-specific buttons -->
    <div class="topbar-actions">
      <slot name="actions" />
    </div>
  </header>
</template>

<script setup>
defineProps({
  navItems: { type: Array, default: () => [] },
  isActive: { type: Function, default: () => false },
})
</script>

<style scoped>
.topbar {
  height: var(--topbar-h);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32px;
  border-bottom: 1px solid var(--border);
  background: var(--white);
  flex-shrink: 0;
  gap: 16px;
}

.topbar-nav {
  display: flex;
  align-items: center;
  gap: 4px;
}

.top-nav-link {
  padding: 8px 16px;
  font-size: 14px;
  font-weight: 600;
  color: var(--slate);
  text-decoration: none;
  border-radius: 8px;
  position: relative;
  transition: color var(--duration) var(--ease);
}

.top-nav-link::after {
  content: '';
  position: absolute;
  bottom: -1px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 3px;
  background: var(--teal);
  border-radius: 2px 2px 0 0;
  transition: width var(--duration) var(--ease);
}

.top-nav-link:hover { color: var(--navy); }
.top-nav-link.active { color: var(--navy); font-weight: 700; }
.top-nav-link.active::after { width: calc(100% - 16px); }

.topbar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: auto;
}
</style>
