<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
  title: string
  description?: string
  showSearch?: boolean
  searchPlaceholder?: string
}>()

// Por ahora solo visual
const search = ref('')
</script>

<template>
  <header class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <!-- Título + descripción -->
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">
        {{ title }}
      </h1>
      <p
        v-if="description"
        class="mt-1 text-sm text-muted-foreground"
      >
        {{ description }}
      </p>
    </div>

    <!-- Buscador + acciones -->
    <div class="flex flex-col gap-2 md:flex-row md:items-center">
      <!-- Buscador tipo Alegra -->
      <div
        v-if="showSearch"
        class="relative w-full md:w-96"
      >
        <span class="pointer-events-none absolute left-3 top-2.5 text-muted-foreground/60">
          <svg
            class="h-4 w-4"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
          >
            <circle cx="11" cy="11" r="7" stroke-width="2" />
            <path d="m21 21-4.3-4.3" stroke-width="2" />
          </svg>
        </span>

        <input
          v-model="search"
          :placeholder="searchPlaceholder ?? 'Buscar…'"
          class="w-full rounded-full border bg-white px-9 py-2 text-sm shadow-sm
                 focus-visible:outline-none focus-visible:ring-2
                 focus-visible:ring-primary focus-visible:border-transparent"
          type="text"
        />
      </div>

      <!-- Botones a la derecha -->
      <div class="flex items-center justify-end gap-2">
        <!-- Aquí entran los botones que metas desde la página -->
        <slot name="actions" />
      </div>
    </div>
  </header>
</template>
