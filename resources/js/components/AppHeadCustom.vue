<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
  title?: string
  description?: string
  showSearch?: boolean
  searchPlaceholder?: string
}>()

const search = ref('')
</script>

<template>
  <!-- Barra pegada arriba, estilo Alegra -->
  <header
    class="sticky top-0 z-20 w-full border-b bg-[#F4F5FB]"
  >
    <!-- Contenedor centrado -->
    <div
      class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-3
             md:flex-row md:items-center md:justify-between md:px-6"
    >
      <!-- Izquierda: título + descripción -->
      <div>
        <h1 class="text-xl font-semibold tracking-tight md:text-2xl">
          {{ props.title || '' }}
        </h1>
        <p
          v-if="props.description"
          class="mt-0.5 text-xs text-muted-foreground md:text-sm"
        >
          {{ props.description }}
        </p>
      </div>

      <!-- Derecha: buscador + acciones -->
      <div class="flex flex-col gap-2 md:flex-row md:items-center">
        <!-- Buscador tipo Alegra -->
        <div
          v-if="props.showSearch"
          class="relative w-full md:w-80"
        >
          <span
            class="pointer-events-none absolute left-4 top-2.5 text-muted-foreground/60"
          >
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
            :placeholder="props.searchPlaceholder ?? 'Buscar…'"
            class="w-full rounded-full border bg-white px-10 py-2 text-sm shadow-sm
                   focus-visible:outline-none focus-visible:ring-2
                   focus-visible:ring-primary focus-visible:border-transparent"
            type="text"
          />
        </div>

        <!-- Botones / acciones a la derecha -->
        <div class="flex items-center justify-end gap-2">
          <slot name="actions" />
        </div>
      </div>
    </div>
  </header>
</template>
