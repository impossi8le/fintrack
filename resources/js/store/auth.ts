import { defineStore } from 'pinia'
import axios from 'axios'
import type { BalanceResponse, Transaction } from '../types/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || '',
    balance: null as number | null,
    transactions: [] as Transaction[],
    isLoadingBalance: false,
    isLoadingTransactions: false
  }),
  getters: {
    isLoggedIn: (state) => !!state.token
  },
  actions: {
    async login(email: string, password: string) {
      try {
        const res = await axios.post('/api/login', {
          email,
          password
        }, {
          headers: { 'Content-Type': 'application/json' }
        })
        this.token = res.data.token
        localStorage.setItem('token', this.token)
        // console.log('localStorage', localStorage);
        
      } catch (error: any) {
        if (error.response?.status === 422) {
          console.error('Ошибка валидации:', error.response.data.errors)
        }
        throw error
      }
    },
    async register(name: string, email: string, password: string) {
      const res = await axios.post('/api/register', {
        name,
        email,
        password
      }, {
        headers: { 'Content-Type': 'application/json' }
      })
      this.token = res.data.token
      localStorage.setItem('token', this.token)
    },
    logout() {
      this.token = ''
      localStorage.removeItem('token')
    },
    async fetchBalance(force = false) {
      if (this.balance !== null && !force) return
      this.isLoadingBalance = true
      try {
        const res = await axios.get<BalanceResponse>('/api/balance', {
          headers: { Authorization: `Bearer ${this.token}` }
        })
        this.balance = res.data.amount
      } finally {
        this.isLoadingBalance = false
      }
    },
    async fetchTransactions(force = false) {
      if (this.transactions.length && !force) return
      this.isLoadingTransactions = true
      try {
        const res = await axios.get<{ data: Transaction[] }>('/api/transactions', {
          headers: { Authorization: `Bearer ${this.token}` }
        })
        this.transactions = res.data.data
      } finally {
        this.isLoadingTransactions = false
      }
    }
  }
})
