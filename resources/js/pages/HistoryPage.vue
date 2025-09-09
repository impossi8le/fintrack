<template>
  <div class="container mt-4">
    <h3>История операций</h3>
    <div v-if="auth.isLoadingTransactions" class="text-muted">Загрузка...</div>
    <ul v-else>
      <li v-for="tx in auth.transactions" :key="tx.id">
        {{ tx.created_at }} — {{ tx.type }} — {{ tx.amount }} ₽ — {{ tx.description }}
      </li>
    </ul>
    <button class="btn btn-outline-secondary mt-2" @click="refresh">Обновить</button>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()

onMounted(() => {
  auth.fetchTransactions()
})

const refresh = () => {
  auth.fetchTransactions(true)
}
</script>
