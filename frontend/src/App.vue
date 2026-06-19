<template>
  <div class="max-w-4xl mx-auto px-4 py-8">
    <header class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Balance Transfer</h1>
    </header>

    <div v-if="notification" :class="notificationClass" class="mb-6 px-4 py-3 rounded relative">
      <span>{{ notification.message }}</span>
      <button @click="notification = null" class="absolute top-0 right-0 px-4 py-3">
        &times;
      </button>
    </div>

    <router-view @notify="showNotification" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const notification = ref(null)

const notificationClass = computed(() => {
  if (!notification.value) return ''
  const classes = {
    success: 'bg-green-100 border border-green-400 text-green-700',
    error: 'bg-red-100 border border-red-400 text-red-700',
    warning: 'bg-yellow-100 border border-yellow-400 text-yellow-700',
  }
  return classes[notification.value.type] || classes.success
})

function showNotification(msg) {
  notification.value = msg
  if (msg.type === 'success') {
    setTimeout(() => { notification.value = null }, 5000)
  }
}
</script>
