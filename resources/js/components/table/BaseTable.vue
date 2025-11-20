<script setup lang="ts">
/* ===================================================================
   BASETABLE — Tabla estilo Alegra con menú de acciones (⋮)
   + buscador y botón "Filtrar" por defecto
   =================================================================== */

import { ref, onMounted, onBeforeUnmount } from 'vue'

interface Column {
  key: string
  label: string
  align?: 'left' | 'right' | 'center'
  width?: string | number
}

interface RowAction {
  key: string
  label: string
  variant?: 'normal' | 'danger'
}

const props = withDefaults(
  defineProps<{
    columns: Column[]
    rows: any[]
    selectable?: boolean
    actions?: RowAction[]
    pagination?: {
      currentPage: number
      lastPage: number
      perPage: number
      total: number
    }

    // 🔍 Opciones del buscador
    searchPlaceholder?: string
    showSearch?: boolean
    showFilterButton?: boolean
  }>(),
  {
    searchPlaceholder: 'Buscar',
    showSearch: true,
    showFilterButton: true,
  }
)

const emit = defineEmits<{
  (e: 'change-page', page: number): void
  (e: 'action', data: { action: string; row: any }): void
  (e: 'search', term: string): void
  (e: 'filter', term: string): void
}>()

/* ====================== BUSCADOR ====================== */

const searchValue = ref('')

function emitSearch() {
  emit('search', searchValue.value)
}

function emitFilter() {
  emit('filter', searchValue.value)
}

/* ====================== PAGINACIÓN ====================== */

function goTo(page: number) {
  const last = props.pagination?.lastPage || 1
  if (page < 1 || page > last) return
  emit('change-page', page)
}

function onPageInput(event: Event) {
  const target = event.target as HTMLInputElement | null
  if (!target) return
  const value = Number(target.value || '1')
  goTo(value)
}

/* ====================== MENÚ DE ACCIONES (⋮) ====================== */
const openRowId = ref<number | null>(null)

function toggleMenu(rowId: number) {
  openRowId.value = openRowId.value === rowId ? null : rowId
}

function handleClickOutside(e: MouseEvent) {
  const target = e.target as HTMLElement | null
  if (!target) return
  if (!target.closest('.action-menu')) {
    openRowId.value = null
  }
}

onMounted(() => {
  window.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  window.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="border rounded-xl overflow-hidden bg-white">
    <!-- ====================== TOOLBAR: BUSCAR + FILTRAR ====================== -->
    <div
      v-if="props.showSearch || props.showFilterButton"
      class="p-4 flex items-center gap-2 border-b bg-white"
    >
      <!-- Input buscar -->
      <div
        v-if="props.showSearch"
        class="relative flex-1"
      >
        <input
          v-model="searchValue"
          @keyup.enter="emitFilter"
          :placeholder="props.searchPlaceholder"
          class="w-full border rounded-lg px-10 py-2 text-sm"
        />
        <!-- icono lupa -->
        <svg
          class="w-4 h-4 absolute left-3 top-2.5 opacity-60"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
        >
          <circle
            cx="11"
            cy="11"
            r="7"
            stroke-width="2"
          />
          <path
            d="m21 21-4.3-4.3"
            stroke-width="2"
          />
        </svg>
      </div>

      <!-- Botón Filtrar (solo diseño, pero emite filter) -->
      <button
        v-if="props.showFilterButton"
        class="px-3 py-2 rounded-lg border text-sm hover:bg-gray-50 flex items-center gap-1"
        @click="emitFilter"
      >
        <svg
          class="w-4 h-4"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
        >
          <path
            d="M4 4h16l-6 7v5l-4 2v-7z"
            stroke-width="2"
            stroke-linejoin="round"
          />
        </svg>
        <span>Filtrar</span>
      </button>
    </div>

    <!-- ====================== TABLA ====================== -->
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <!-- CABECERA -->
        <thead class="bg-gray-50 border-b">
          <tr>
            <!-- Checkbox inicial -->
            <th
              v-if="props.selectable"
              class="p-3 w-10"
            >
              <input
                type="checkbox"
                class="h-4 w-4"
              />
            </th>

            <!-- Columnas -->
            <th
              v-for="col in props.columns"
              :key="col.key"
              class="p-3 text-xs font-semibold text-gray-600"
              :style="{ textAlign: col.align || 'left', width: col.width }"
            >
              {{ col.label }}
            </th>

            <!-- Cabecera de acciones (columna vacía) -->
            <th
              v-if="props.actions?.length"
              class="p-3 w-10"
            />
          </tr>
        </thead>

        <!-- FILAS -->
        <tbody>
          <tr
            v-for="row in props.rows"
            :key="row.id"
            class="border-b hover:bg-gray-100/50 transition"
          >
            <!-- Checkbox por fila -->
            <td
              v-if="props.selectable"
              class="p-3 w-10"
            >
              <input
                type="checkbox"
                class="h-4 w-4"
              />
            </td>

            <!-- Celdas de datos -->
            <td
              v-for="col in props.columns"
              :key="col.key"
              class="p-3"
              :style="{ textAlign: col.align || 'left' }"
            >
              <!-- SLOT personalizado: cell-<key> -->
              <slot
                :name="`cell-${col.key}`"
                :row="row"
              >
                {{ row[col.key] }}
              </slot>
            </td>

            <!-- MENÚ DE ACCIONES (⋮) -->
            <td
              v-if="props.actions?.length"
              class="p-3 text-right overflow-visible"
            >
              <div class="relative inline-block text-left action-menu">
                <!-- BOTÓN DE TRES PUNTOS -->
                <button
                  type="button"
                  class="p-1 rounded-full hover:bg-gray-100"
                  @click.stop="toggleMenu(row.id)"
                >
                  ⋮
                </button>

                <!-- MENÚ DESPLEGABLE -->
                <div
                  v-if="openRowId === row.id"
                  class="absolute right-0 mt-1 bg-white border shadow-lg rounded-md z-10 min-w-[140px]"
                >
                  <button
                    v-for="ac in props.actions"
                    :key="ac.key"
                    class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100"
                    :class="ac.variant === 'danger' ? 'text-red-600' : ''"
                    @click.prevent="
                      emit('action', { action: ac.key, row });
                      openRowId = null;
                    "
                  >
                    {{ ac.label }}
                  </button>
                </div>
              </div>
            </td>
          </tr>

          <!-- Cuando no hay filas -->
          <tr v-if="props.rows.length === 0">
            <td
              :colspan="
                props.columns.length +
                (props.selectable ? 1 : 0) +
                (props.actions?.length ? 1 : 0)
              "
              class="p-6 text-center text-gray-500"
            >
              No hay registros
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ====================== PAGINACIÓN ====================== -->
    <div
      v-if="props.pagination"
      class="flex items-center justify-between p-4 text-sm bg-gray-50"
    >
      <div>
        Ítems por página:
        <span class="font-semibold">{{ props.pagination.perPage }}</span>
      </div>

      <div class="flex items-center gap-2">
        Página:
        <input
          type="number"
          :value="props.pagination.currentPage"
          @input="onPageInput"
          class="w-16 border rounded-md px-2 py-1"
        />
        <span class="text-gray-600">/ {{ props.pagination.lastPage }}</span>

        <button
          class="px-2 py-1 border rounded-md hover:bg-gray-200"
          @click="goTo(props.pagination.currentPage - 1)"
        >
          ‹
        </button>
        <button
          class="px-2 py-1 border rounded-md hover:bg-gray-200"
          @click="goTo(props.pagination.currentPage + 1)"
        >
          ›
        </button>
      </div>
    </div>
  </div>
</template>
