import { reactive } from 'vue'
import api from './api'

const store = reactive({
  customers: [],
  transfers: [],
  loaded: false,
  loading: false,
})

export async function loadData(forceRefresh = false) {
  if (store.loaded && !forceRefresh) return store
  if (store.loading) return store

  store.loading = true
  try {
    const [customersRes, transfersRes] = await Promise.all([
      api.getCustomers(),
      api.getTransfers(),
    ])
    store.customers = customersRes.data
    store.transfers = transfersRes.data
    store.loaded = true
  } catch (error) {
    console.error('Failed to load data:', error)
  } finally {
    store.loading = false
  }
  return store
}

export function getCustomer(id) {
  return store.customers.find(c => c.id === id)
}

export function refreshData() {
  return loadData(true)
}

export default store
