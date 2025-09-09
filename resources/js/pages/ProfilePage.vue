<template>
  <div class="container mt-4">
    <h3>Профиль</h3>
    <p v-if="loading">Загрузка...</p>
    <div v-else>
      <p><strong>Email:</strong> {{ email }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../store/auth'

const email = ref('')
const loading = ref(true)
const auth = useAuthStore()

onMounted(async () => {
  try {
    const res = await axios.get('/api/user', {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    email.value = res.data.email
  } finally {
    loading.value = false
  }
})
</script>
