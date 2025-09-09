<template>
  <div class="container mt-5">
    <h2>Вход</h2>
    <form @submit.prevent="handleLogin">
      <input v-model="email" type="email" class="form-control mb-2" placeholder="Email" />
      <input v-model="password" type="password" class="form-control mb-2" placeholder="Пароль" />
      <button class="btn btn-primary">Войти</button>
    </form>
    <p class="mt-3">Ещё не зарегистрированы? <router-link to="/register">Регистрация</router-link></p>
  </div>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../store/auth'
import { useRouter } from 'vue-router'

const email = ref('')
const password = ref('')
const auth = useAuthStore()
const router = useRouter()

const handleLogin = async () => {
  await auth.login(email.value, password.value)
  router.push('/dashboard')
}
</script>
