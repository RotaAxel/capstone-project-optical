<template>
  <ul v-if="visible && items.length" class="sug-dropdown" role="listbox">
    <li
      v-for="(item, i) in items"
      :key="item.id ?? i"
      role="option"
      :aria-selected="i === activeIndex"
      :class="['sug-item', { 'sug-item-active': i === activeIndex }]"
      @mousedown.prevent="$emit('pick', item)"
      @mouseenter="$emit('hover', i)"
    >
      <span class="sug-label">{{ item.label }}</span>
      <span v-if="item.sub" class="sug-sub">{{ item.sub }}</span>
    </li>
  </ul>
</template>

<script setup>
defineProps({
  items: { type: Array, default: () => [] },
  activeIndex: { type: Number, default: -1 },
  visible: { type: Boolean, default: false },
})
defineEmits(['pick', 'hover'])
</script>

<style scoped>
.sug-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  z-index: 50;
  margin: 0;
  padding: 6px;
  list-style: none;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  box-shadow: 0 10px 28px rgba(0, 0, 0, .12);
  max-height: 260px;
  overflow-y: auto;
}
.sug-item {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 7px;
  cursor: pointer;
  font-size: 13px;
  color: #1f2937;
}
.sug-item:hover,
.sug-item-active {
  background: #f3f4f6;
}
.sug-label {
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sug-sub {
  font-size: 11.5px;
  color: #9ca3af;
  white-space: nowrap;
  flex-shrink: 0;
}
</style>
