<script setup lang="ts">
import { discordRedirectUrl } from '@/lib/api'

const redirectUrl = discordRedirectUrl()
</script>

<template>
  <main class="auth-layout">
    <section class="auth-card">
      <h1>Trackmania Club Seasons</h1>
      <p>Sign in with Discord to access your dashboard.</p>
      <a class="login-btn" :href="redirectUrl">Continue with Discord</a>
    </section>
  </main>
</template>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 1.5rem;
  background: radial-gradient(circle at top left, #e9f4ff, #f6fafc 45%, #ffffff 100%);
}

.auth-card {
  width: min(420px, 100%);
  padding: 2rem;
  border-radius: 16px;
  border: 1px solid #d8e5f2;
  background: #ffffff;
  box-shadow: 0 16px 32px rgba(19, 45, 84, 0.08);
}

h1 {
  margin: 0 0 0.75rem;
  font-size: 1.5rem;
  line-height: 1.2;
}

p {
  margin: 0 0 1.25rem;
  color: #4e637a;
}

.login-btn {
  display: inline-block;
  padding: 0.7rem 1rem;
  border-radius: 10px;
  background: #2f6feb;
  color: #ffffff;
  text-decoration: none;
  font-weight: 600;
}

.login-btn:hover {
  background: #255bd4;
}
</style>
