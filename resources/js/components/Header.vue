<template>
  <nav class="navbar navbar-expand-lg navbar-light bg-light px-4">
    <router-link class="navbar-brand" to="/">FinTrack</router-link>
    <ul class="navbar-nav me-auto">
      <li class="nav-item"><router-link class="nav-link" to="/">Главная</router-link></li>
      <li class="nav-item" v-if="auth.isLoggedIn"><router-link class="nav-link" to="/dashboard">Баланс</router-link></li>
      <li class="nav-item" v-if="auth.isLoggedIn"><router-link class="nav-link" to="/profile">Профиль</router-link></li>
      <li class="nav-item" v-if="auth.isLoggedIn"><router-link class="nav-link" to="/history">История</router-link></li>
    </ul>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item" v-if="!auth.isLoggedIn"><router-link class="nav-link" to="/login">Вход</router-link></li>
      <li class="nav-item" v-if="!auth.isLoggedIn"><router-link class="nav-link" to="/register">Регистрация</router-link></li>
      <li class="nav-item" v-if="auth.isLoggedIn"><button class="btn btn-outline-danger" @click="logout">Выйти</button></li>
    </ul>
  </nav>
</template>

<script setup lang="ts">
import { useAuthStore } from '../store/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

const logout = () => {
  auth.logout()
  router.push('/login')
}
</script>
