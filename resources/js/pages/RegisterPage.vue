<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../store/auth'
import { useRouter } from 'vue-router'

const name = ref('')
const email = ref('')
const password = ref('')
const auth = useAuthStore()
const router = useRouter()

const handleRegister = async () => {
  try {
    await auth.register(name.value, email.value, password.value)
    router.push('/dashboard')
  } catch (error: any) {
    console.log(error.response.data) // Laravel вернёт ошибки валидации
  }
}
</script>

<template>
  <div class="container mt-5">
    <h2>Регистрация</h2>
    <form @submit.prevent="handleRegister">
      <input v-model="name" type="text" placeholder="Имя" class="form-control mb-2" />
      <input v-model="email" type="email" placeholder="Email" class="form-control mb-2" />
      <input v-model="password" type="password" placeholder="Пароль" class="form-control mb-2" />
      <button class="btn btn-success">Зарегистрироваться</button>
    </form>
  </div>
</template>
