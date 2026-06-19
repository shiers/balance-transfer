import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Transfer from '../views/Transfer.vue'

const routes = [
  { path: '/', name: 'home', component: Home },
  { path: '/transfer/:id', name: 'transfer', component: Transfer },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
