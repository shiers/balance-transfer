<template>
  <div>
    <div class="mb-6">
      <router-link to="/" class="text-blue-600 hover:text-blue-800 text-sm">
        ← Return to Main Screen
      </router-link>
    </div>

    <div v-if="loading" class="text-gray-500">Loading...</div>

    <div v-else class="bg-white rounded-lg shadow p-6 max-w-lg">
      <h2 class="text-2xl font-semibold text-gray-800 mb-2">
        {{ customer.name }}'s Money Transfer
      </h2>
      <p class="text-gray-600 mb-6">
        Balance: <span class="font-medium">${{ formatBalance(customer.balance) }}</span>
      </p>

      <form @submit.prevent="submitTransfer" class="space-y-4">
        <div>
          <label for="recipient" class="block text-sm font-medium text-gray-700 mb-1">
            Transfer to
          </label>
          <select
            id="recipient"
            v-model="recipientId"
            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          >
            <option value="" disabled>Select a recipient</option>
            <option
              v-for="c in recipients"
              :key="c.id"
              :value="c.id"
            >
              {{ c.name }}
            </option>
          </select>
        </div>

        <div>
          <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
            Amount
          </label>
          <input
            id="amount"
            type="number"
            v-model.number="amount"
            min="0"
            step="0.01"
            :max="maxTransfer"
            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          />
          <p class="text-xs text-gray-500 mt-1">
            Maximum transfer: ${{ formatBalance(maxTransfer) }}
          </p>
        </div>

        <button
          type="submit"
          :disabled="submitting"
          class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          {{ submitting ? 'Processing...' : 'Submit Transfer' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api'

const emit = defineEmits(['notify'])
const route = useRoute()
const router = useRouter()

const customer = ref({})
const customers = ref([])
const recipientId = ref('')
const amount = ref(null)
const loading = ref(true)
const submitting = ref(false)

const recipients = computed(() => {
  return customers.value.filter(c => c.id !== customer.value.id)
})

const maxTransfer = computed(() => {
  const balance = parseFloat(customer.value.balance) || 0
  return Math.min(balance, 500)
})

function formatBalance(value) {
  return parseFloat(value).toFixed(2)
}

async function submitTransfer() {
  submitting.value = true
  try {
    const response = await api.createTransfer(
      customer.value.id,
      recipientId.value,
      amount.value
    )
    emit('notify', { type: 'success', message: response.data.message })
    router.push('/')
  } catch (error) {
    const msg = error.response?.data?.message || 'An error occurred'
    emit('notify', { type: 'error', message: msg })
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const id = parseInt(route.params.id)
    const [customerRes, customersRes] = await Promise.all([
      api.getCustomer(id),
      api.getCustomers(),
    ])
    customer.value = customerRes.data
    customers.value = customersRes.data
  } catch (error) {
    emit('notify', { type: 'error', message: 'Failed to load customer data' })
    router.push('/')
  } finally {
    loading.value = false
  }
})
</script>
