<script setup lang="ts">
import { router, Head } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import CustomerModal from '@/components/CustomerModal.vue'
import BaseTable from '@/components/table/BaseTable.vue' // 👈 usamos la tabla reutilizable

/* ==============================
   PROPS
   ============================== */
const props = defineProps<{
  customers: {
    data: any[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: Array<{ url: string | null; label: string; active: boolean }>
  }
  filters: {
    search?: string
    type?: 'all' | 'client' | 'supplier'
  }
  
}>()

function handleFilter(term: string) {
  search.value = term
  fetchList()
}

/* ==============================
   UI BASICS
   ============================== */
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Contactos', href: '/customers' },
]

const search = ref(props.filters?.search ?? '')
const tab = ref<'all' | 'client' | 'supplier'>(props.filters?.type ?? 'all')
watch(tab, fetchList)

/* ==============================
   LISTADO / FILTRO
   ============================== */
function fetchList() {
  router.get(
    '/customers',
    {
      search: search.value || undefined,
      type: tab.value !== 'all' ? tab.value : undefined,
    },
    { preserveState: true, replace: true }
  )
}

function onEnter(e: KeyboardEvent) {
  if (e.key === 'Enter') fetchList()
}

/* ==============================
   CONFIG TABLA (COLUMNAS + ACCIONES)
   ============================== */
const columns = [
  { key: 'name', label: 'Nombre' },
  { key: 'document', label: 'Identificación' },
  { key: 'email', label: 'Correo' },
  { key: 'phone', label: 'Teléfono' },
]

const rowActions = [
  { key: 'view', label: 'Ver' },
  { key: 'edit', label: 'Editar' },
  { key: 'delete', label: 'Eliminar', variant: 'danger' as const },
]

// viene del menú de acciones (⋮) del BaseTable
function onRowAction(payload: { action: string; row: any }) {
  const { action, row } = payload

  if (action === 'view') openView(row)
  if (action === 'edit') openEdit(row)
  if (action === 'delete') openDelete(row)
}

// paginación de BaseTable
function goToPage(page: number) {
  router.get(
    '/customers',
    {
      page,
      search: search.value || undefined,
      type: tab.value !== 'all' ? tab.value : undefined,
    },
    { preserveState: true, replace: true }
  )
}

/* ==============================
   MODALES (reutilizable + otros)
   ============================== */
const showCustomerModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')

// modales ver / eliminar
const showView = ref(false)
const showDelete = ref(false)
const current = ref<any | null>(null)

/* ==============================
   ABRIR MODAL REUTILIZABLE
   ============================== */
function openCreate() {
  modalMode.value = 'create'
  current.value = null
  showCustomerModal.value = true
}

function openEdit(c: any) {
  current.value = c
  modalMode.value = 'edit'
  showCustomerModal.value = true
}

/* ==============================
   VER / ELIMINAR
   ============================== */
function openView(c: any) {
  current.value = c
  showView.value = true
}

function openDelete(c: any) {
  current.value = c
  showDelete.value = true
}

function submitDelete() {
  if (!current.value) return
  router.delete(`/customers/${current.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDelete.value = false
      current.value = null
      router.reload({ only: ['customers'] })
    },
  })
}

/* ==============================
   HANDLERS DEL MODAL REUTILIZABLE
   ============================== */
function handleModalClose() {
  showCustomerModal.value = false
}

function handleSaved() {
  showCustomerModal.value = false
  router.reload({ only: ['customers'] })
}

function handleUpdated() {
  showCustomerModal.value = false
  router.reload({ only: ['customers'] })
}
</script>

<template>
  <Head title="Contactos" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 md:p-6 space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold">Contactos</h1>
          <p class="text-sm text-muted-foreground">
            Crea tus clientes, proveedores y demás contactos para asociarlos en tus documentos.
          </p>
        </div>
        <button
          class="px-4 py-2 rounded-lg bg-primary text-white"
          @click="openCreate"
        >
          + Nuevo contacto
        </button>
      </div>

      <!-- Tabs -->
      <div class="border-b">
        <nav class="-mb-px flex gap-6">
          <button
            class="py-3 border-b-2"
            :class="
              tab === 'all'
                ? 'border-primary text-primary'
                : 'border-transparent text-muted-foreground hover:text-foreground'
            "
            @click="tab = 'all'"
          >
            Todos
          </button>
          <button
            class="py-3 border-b-2"
            :class="
              tab === 'client'
                ? 'border-primary text-primary'
                : 'border-transparent text-muted-foreground hover:text-foreground'
            "
            @click="tab = 'client'"
          >
            Clientes
          </button>
          <button
            class="py-3 border-b-2"
            :class="
              tab === 'supplier'
                ? 'border-primary text-primary'
                : 'border-transparent text-muted-foreground hover:text-foreground'
            "
            @click="tab = 'supplier'"
          >
            Proveedores
          </button>
        </nav>
      </div>

      <!-- Card -->
      <div class="bg-white dark:bg-card rounded-xl shadow-sm border">
       

        <!-- Tabla reutilizable -->
        <BaseTable
          :columns="columns"
          :rows="props.customers.data"
          :pagination="{
            currentPage: props.customers.current_page,
            lastPage: props.customers.last_page,
            perPage: props.customers.per_page,
            total: props.customers.total,
          }"
          :actions="rowActions"
          search-placeholder="Buscar por clientes"
          @filter="handleFilter"
        >

      > <!-- Nombre + avatar inicial -->
          <template #cell-name="{ row }">
            <div class="flex items-center gap-2">
              <div
                class="w-6 h-6 rounded-full bg-primary/10 text-primary grid place-items-center text-xs font-semibold"
              >
                {{ (row.name || 'C').substring(0, 1).toUpperCase() }}
              </div>
              <span class="font-medium">{{ row.name }}</span>
            </div>
          </template>

          <!-- Identificación -->
          <template #cell-document="{ row }">
            {{ row.document_type }} {{ row.document_number || '—' }}
          </template>

          <!-- Correo -->
          <template #cell-email="{ row }">
            {{ row.email || '—' }}
          </template>

          <!-- Teléfono -->
          <template #cell-phone="{ row }">
            {{ row.phone || '—' }}
          </template>
        </BaseTable>
      </div>
    </div>

    <!-- ===================== MODAL REUTILIZABLE ===================== -->
    <CustomerModal
      :open="showCustomerModal"
      :mode="modalMode"
      :customer="modalMode === 'edit' ? current : null"
      @close="handleModalClose"
      @saved="handleSaved"
      @updated="handleUpdated"
    />

    <!-- ===================== MODAL VER ===================== -->
    <!-- (igual que lo tenías) -->
    <div
      v-if="showView"
      class="fixed inset-0 bg-black/30 grid place-items-center z-50"
      @click.self="showView = false"
    >
      <div
        class="bg-white dark:bg-card w-full max-w-2xl rounded-xl shadow-xl border p-6 space-y-4"
      >
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Detalle del contacto</h3>
          <button
            class="opacity-70 hover:opacity-100"
            @click="showView = false"
          >
            ✕
          </button>
        </div>

        <div class="grid md:grid-cols-2 gap-3">
          <div>
            <div class="text-xs text-muted-foreground">Tipo</div>
            <div class="font-medium">
              {{ current?.type === 'company' ? 'Empresa' : 'Persona' }}
            </div>
          </div>
          <div>
            <div class="text-xs text-muted-foreground">Identificación</div>
            <div>
              {{ current?.document_type }}
              {{ current?.document_number || '—' }}
            </div>
          </div>
          <div class="md:col-span-2">
            <div class="text-xs text-muted-foreground">
              Razón social / Nombre
            </div>
            <div class="font-medium">{{ current?.name }}</div>
          </div>

          <div>
            <div class="text-xs text-muted-foreground">Correo</div>
            <div>{{ current?.email || '—' }}</div>
          </div>
          <div>
            <div class="text-xs text-muted-foreground">Teléfono</div>
            <div>{{ current?.phone || '—' }}</div>
          </div>

          <div class="md:col-span-2">
            <div class="text-xs text-muted-foreground">Dirección</div>
            <div>{{ current?.address || '—' }}</div>
          </div>

          <div class="md:col-span-2">
            <div class="text-xs text-muted-foreground">Notas</div>
            <div>{{ current?.notes || '—' }}</div>
          </div>
          <div>
            <div class="text-xs text-muted-foreground">Estado</div>
            <div>{{ current?.is_active ? 'Activo' : 'Inactivo' }}</div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t mt-2">
          <button class="px-3 py-2 rounded border" @click="showView = false">
            Cerrar
          </button>
          <button class="px-3 py-2 rounded border" @click="openEdit(current)">
            Editar
          </button>
        </div>
      </div>
    </div>

    <!-- ===================== MODAL ELIMINAR ===================== -->
    <div
      v-if="showDelete"
      class="fixed inset-0 bg-black/40 grid place-items-center z-50"
      @click.self="showDelete = false"
    >
      <div
        class="bg-white dark:bg-card w-full max-w-md rounded-xl shadow-xl border p-6 space-y-4"
      >
        <h3 class="text-lg font-semibold">Eliminar contacto</h3>
        <p>
          ¿Seguro que deseas eliminar <b>{{ current?.name }}</b>? Esta acción
          no se puede deshacer.
        </p>
        <div class="flex justify-end gap-2">
          <button class="px-3 py-2 rounded border" @click="showDelete = false">
            Cancelar
          </button>
          <button
            class="px-4 py-2 rounded bg-red-600 text-white"
            @click="submitDelete"
          >
            Eliminar
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
