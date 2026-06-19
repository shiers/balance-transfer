<template>
  <div>
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Customers</h2>

    <div v-if="loading" class="text-gray-500">Loading...</div>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full bg-white rounded-lg shadow">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">#</th>
            <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Name</th>
            <th class="px-6 py-3 text-right text-sm font-medium text-gray-600">Balance</th>
            <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="(customer, index) in customers" :key="customer.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm text-gray-700">{{ index + 1 }}</td>
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ customer.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-700 text-right">${{ formatBalance(customer.balance) }}</td>
            <td class="px-6 py-4">
              <router-link
                :to="{ name: 'transfer', params: { id: customer.id } }"
                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
              >
                ↔ Transfer
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="transfers.length > 0" class="mt-10">
      <h2 class="text-2xl font-semibold text-gray-800 mb-4">Transfers</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg shadow">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Date</th>
              <th class="px-6 py-3 text-right text-sm font-medium text-gray-600">Amount</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Sender</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Recipient</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="transfer in transfers" :key="transfer.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 text-sm text-gray-700">{{ formatDate(transfer.date) }}</td>
              <td class="px-6 py-4 text-sm text-gray-700 text-right">${{ formatBalance(transfer.amount) }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ transfer.customerFrom }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ transfer.customerTo }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const customers = ref([])
const transfers = ref([])
const loading = ref(true)

function formatBalance(value) {
  return parseFloat(value).toFixed(2)
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleString()
}

onMounted(async () => {
  try {
    const [customersRes, transfersRes] = await Promise.all([
      api.getCustomers(),
      api.getTransfers(),
    ])
    customers.value = customersRes.data
    transfers.value = transfersRes.data
  } catch (error) {
    console.error('Failed to load data:', error)
  } finally {
    loading.value = false
  }
})
</script>
