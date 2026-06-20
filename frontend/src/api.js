import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
  },
})

export default {
  getCustomers() {
    return api.get('/customers')
  },

  getCustomer(id) {
    return api.get(`/customers/${id}`)
  },

  getTransfers() {
    return api.get('/transfers')
  },

  createTransfer(senderId, recipientId, amount) {
    return api.post('/transfers', { senderId, recipientId, amount })
  },
}
