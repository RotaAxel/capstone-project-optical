<template>
  <header class="topbar">
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
  background: transparent;
  border-bottom: none;
  flex-shrink: 0;
  gap: 16px;
}

.topbar-nav {
  display: flex;
  align-items: center;
  gap: 0;
  flex: 1;
  justify-content: center;
  min-width: 0;
  overflow-x: auto;
  scrollbar-width: none;
}

.topbar-nav::-webkit-scrollbar {
  display: none;
}

.top-nav-link {
  padding: 10px 18px;
  font-size: 14px;
  font-weight: 500;
  color: var(--slate);
  text-decoration: none;
  border-radius: 0;
  position: relative;
  transition: color var(--duration) var(--ease);
  white-space: nowrap;
  flex-shrink: 0;
}

/* Clean underline only on active */
.top-nav-link::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 18px;
  right: 18px;
  height: 2px;
  background: var(--teal);
  border-radius: 2px 2px 0 0;
  opacity: 0;
  transition: opacity var(--duration) var(--ease);
}

.top-nav-link:hover {
  color: var(--navy);
}

.top-nav-link.active {
  color: var(--navy);
  font-weight: 700;
}

.top-nav-link.active::after {
  opacity: 1;
}

.topbar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

/* Default style for any button dropped into the actions slot */
.topbar-actions :deep(button),
.topbar-actions :deep(a) {
  padding: 8px 18px;
  font-size: 13px;
  font-weight: 700;
  border: none;
  border-radius: 8px;
  background: var(--teal);
  color: #fff;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: background var(--duration) var(--ease), box-shadow var(--duration) var(--ease);
  box-shadow: 0 2px 8px rgba(91, 200, 192, 0.3);
}

.topbar-actions :deep(button):hover,
.topbar-actions :deep(a):hover {
  background: var(--teal-dark);
  box-shadow: 0 4px 12px rgba(91, 200, 192, 0.4);
}

@media (max-width: 900px) {
  .topbar {
    height: auto;
    padding: 12px 16px;
    flex-wrap: wrap;
    justify-content: center;
  }

  .topbar-nav {
    order: 2;
    width: 100%;
    justify-content: flex-start;
    padding-bottom: 4px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.25);
  }

  .top-nav-link {
    padding: 10px 14px;
    font-size: 12px;
  }

  .topbar-actions {
    order: 1;
    width: 100%;
    justify-content: flex-end;
  }
}

@media (max-width: 520px) {
  .topbar {
    padding: 10px 12px;
  }

  .topbar-nav {
    gap: 4px;
  }

  .top-nav-link {
    padding: 10px 10px;
    font-size: 11px;
  }

  .topbar-actions :deep(button),
  .topbar-actions :deep(a) {
    padding: 7px 12px;
    font-size: 11px;
  }
}
</style>