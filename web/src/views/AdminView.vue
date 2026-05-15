<script setup lang="ts"></script>

<template>
  <main class="admin-layout">
    <section class="card">
      <h1>Admin Area</h1>
      <p>Use the sections below to manage maps and seasons.</p>
      <nav class="links">
        <RouterLink to="/admin/maps">Manage Maps</RouterLink>
        <RouterLink to="/admin/seasons">Manage Seasons</RouterLink>
      </nav>
    </section>
  </main>
</template>

<style scoped>
.admin-layout {
  min-height: 100vh;
  padding: 2rem;
  background: #f3f8fd;
}

.card {
  max-width: 560px;
  margin: 0 auto;
  padding: 1.5rem;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #d8e5f2;
}

.links {
  margin-top: 1rem;
  display: flex;
  gap: 1rem;
}
</style>
