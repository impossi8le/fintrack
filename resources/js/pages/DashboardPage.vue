<template>
  <div class="container mt-4">
    <h3>Ваш баланс:</h3>
    <div v-if="auth.isLoadingBalance" class="spinner-border spinner-border-sm"></div>
    <div v-else>{{ auth.balance }} ₽</div>

    <button class="btn btn-outline-secondary mt-2" @click="refresh">Обновить</button>

    <!-- Тестовое пополнение -->
    <div class="mt-4">
      <h5>Тестовое пополнение</h5>
      <div class="input-group">
        <input
          v-model.number="topUpAmount"
          type="number"
          step="0.01"
          class="form-control"
          placeholder="Сумма"
        />
        <button class="btn btn-success" @click="topUp" :disabled="isToppingUp">
          <span v-if="isToppingUp" class="spinner-border spinner-border-sm"></span>
          <span v-else>Пополнить</span>
        </button>
      </div>
    </div>

    <h5 class="mt-4">Последние операции</h5>
    <div v-if="auth.isLoadingTransactions" class="text-muted">Загрузка...</div>
    <ul v-else>
      <li v-for="tx in auth.transactions" :key="tx.id">
        {{ tx.type }} — {{ tx.amount }} ₽ — {{ tx.description }}
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const topUpAmount = ref<number>(150.5) // дефолтная сумма
const isToppingUp = ref(false)

let intervalId: number | null = null
let lastHiddenTime: number | null = null

const refresh = () => {
  auth.fetchBalance(true)
  auth.fetchTransactions(true)
}

const startAutoRefresh = () => {
  refresh()
  intervalId = window.setInterval(refresh, 5 * 60 * 1000) // каждые 5 минут
}

const stopAutoRefresh = () => {
  if (intervalId) {
    clearInterval(intervalId)
    intervalId = null
  }
}

const handleVisibilityChange = () => {
  if (document.hidden) {
    lastHiddenTime = Date.now()
  } else {
    const awayTime = lastHiddenTime ? Date.now() - lastHiddenTime : 0
    if (awayTime > 60 * 1000) {
      refresh()
    }
  }
}

const topUp = async () => {
  try {
    isToppingUp.value = true
    await axios.post(
      '/api/balance/operate',
      {
        type: 'credit',
        amount: topUpAmount.value,
        description: 'Пополнение'
      },
      {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('token')}`,
          Accept: 'application/json'
        }
      }
    )
    refresh()
    alert('Пополнение отправлено')
  } catch (error) {
    console.error(error)
    alert('Ошибка при пополнении')
  } finally {
    isToppingUp.value = false
  }
}

onMounted(() => {
  startAutoRefresh()
  document.addEventListener('visibilitychange', handleVisibilityChange)
})

onBeforeUnmount(() => {
  stopAutoRefresh()
  document.removeEventListener('visibilitychange', handleVisibilityChange)
})
</script>
