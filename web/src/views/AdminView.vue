<script setup lang="ts"></script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <section class="mx-auto max-w-6xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h1 class="text-2xl font-semibold text-slate-900">Admin Area</h1>
      <p class="mt-2 text-sm text-slate-600">Use the sections below to manage maps, seasons, and club members.</p>
      <nav class="mt-4 flex gap-3">
        <RouterLink to="/admin/maps" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Manage Maps</RouterLink>
        <RouterLink to="/admin/seasons" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Manage Seasons</RouterLink>
        <RouterLink to="/admin/clubs" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Manage Clubs</RouterLink>
      </nav>
    </section>
  </main>
</template>
