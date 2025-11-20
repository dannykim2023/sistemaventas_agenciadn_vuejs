<script setup lang="ts">
/* =========================================================
   IMPORTS
   - Componentes de layout y tabla
   - Rutas, tipos y helpers de Inertia
   ========================================================= */
import AppLayout from '@/layouts/AppLayout.vue'
import BaseTable from '@/components/table/BaseTable.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

/* =========================================================
   PROPS DESDE EL CONTROLADOR (Laravel -> Inertia)
   - quotations: paginación con data de cotizaciones
   - customers: lista para el modal de edición
   - filters: filtros actuales (ej. search)
   ========================================================= */
const props = defineProps<{
  quotations: {
    data: any[]
    current_page: number
    last_page: number
    total: number
    links: any[]
  }
  customers: Array<{ id: number; name: string }>
  filters: { search?: string }
}>()

/* =========================================================
   CONFIGURACIÓN DE LA TABLA (COLUMNAS Y ACCIONES)
   - columns: qué columnas muestra BaseTable
   - rowActions: menú de acciones del botón ⋮
   ========================================================= */
const columns = [
  { key: 'number',      label: 'Correlativo' },
  { key: 'customer',    label: 'Cliente' },
  { key: 'issue_date',  label: 'Creación' },
  { key: 'valid_until', label: 'Vencimiento' },
  { key: 'total',       label: 'Total',   align: 'right' as const },
  { key: 'status',      label: 'Estado' },
]

// Acciones que se muestran en el menú de cada fila (⋮)
const rowActions = [
  { key: 'view',   label: 'Ver' },
  { key: 'edit',   label: 'Editar' },
  { key: 'pdf',    label: 'PDF' },
  { key: 'delete', label: 'Eliminar', variant: 'danger' as const },
  { key: 'to-sale', label: 'Convertir en venta' }, // generar venta desde cotización
]

/**
 * Maneja las acciones emitidas por BaseTable
 * - view  -> abre modal de ver
 * - edit  -> redirige a /quotations/{id}/edit
 * - pdf   -> abre pdf en nueva pestaña
 * - delete-> abre modal de confirmación
 * 
 * 
 * 
 */



function onRowAction(payload: { action: string; row: any }) {
  const { action, row } = payload

  if (action === 'view') {
    openView(row)
    return
  }

  // EDITAR: redirigir directamente a la página de edición
  if (action === 'edit') {
    router.get(`/quotations/${row.id}/edit`)
    return
  }

  if (action === 'pdf') {
    window.open(`/quotations/${row.id}/pdf`, '_blank')
    return
  }

  if (action === 'delete') {
    openDelete(row)
    return
  }

  if (action === 'to-sale') {
    router.get('/sales/create', { quotation_id: row.id })
    // o con route():
    // router.get(route('sales.create'), { quotation_id: row.id })
  }
}

/**
 * Cambio de página desde la paginación de BaseTable
 * - Envia page + search al backend
 */
function goToPage(page: number) {
  router.get(
    '/quotations',
    {
      page,
      search: search.value || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
    }
  )
}

/* =========================================================
   MIGAS DE PAN (breadcrumbs)
   ========================================================= */
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Cotizaciones', href: '/quotations' },
]

/* =========================================================
   FILTRO: BÚSQUEDA POR CLIENTE
   - search: estado local del input
   - fetchList: recarga la página aplicando el filtro
   ========================================================= */
const search = ref(props.filters?.search ?? '')

const fetchList = () => {
  router.get(
    '/quotations',
    { search: search.value || undefined },
    {
      preserveState: true,
      replace: true,
    }
  )
}

/* =========================================================
   ESTADO UI (MODALES)
   - showView / showEdit / showDelete: booleanos
   - current: cotización seleccionada
   ========================================================= */
const showView   = ref(false)
const showEdit   = ref(false)
const showDelete = ref(false)
const current    = ref<any | null>(null)

/* =========================================================
   FORMULARIO DE EDICIÓN (SOLO CABECERA DE COTIZACIÓN)
   ========================================================= */
const formEdit = ref({
  id: 0,
  customer_id: null as number | null,
  issue_date: '',
  valid_until: '',
  currency: 'PEN',
  exchange_rate: null as number | null,
  tax_included: true,
  igv_rate: 0.18,
  notes: '',
  terms: '',
  processing: false,
  errors: {} as Record<string, string>,
})

/**
 * Llena el formulario de edición con los datos de una cotización
 */
function hydrateEdit(q: any) {
  formEdit.value.id            = q.id
  formEdit.value.customer_id   = q.customer_id ?? q.customer?.id ?? null
  formEdit.value.issue_date    = q.issue_date
  formEdit.value.valid_until   = q.valid_until
  formEdit.value.currency      = q.currency || 'PEN'
  formEdit.value.exchange_rate = q.exchange_rate
  formEdit.value.tax_included  = !!q.tax_included
  formEdit.value.igv_rate      = Number(q.igv_rate ?? 0.18)
  formEdit.value.notes         = q.notes || ''
  formEdit.value.terms         = q.terms || ''
  formEdit.value.errors        = {}
}

/* =========================================================
   ABRIR / CERRAR MODALES
   ========================================================= */
function openView(q: any)   {
  current.value = q
  hydrateEdit(q)
  showView.value = true
}

function openEdit(q: any)   {
  current.value = q
  hydrateEdit(q)
  showEdit.value = true
}

function openDelete(q: any) {
  current.value = q
  showDelete.value = true
}

/* =========================================================
   ACCIONES CRUD (EDITAR / ELIMINAR)
   ========================================================= */
function submitEdit() {
  if (!formEdit.value.id) return

  formEdit.value.processing = true

  router.put(
    `/quotations/${formEdit.value.id}`,
    {
      customer_id:   formEdit.value.customer_id,
      issue_date:    formEdit.value.issue_date,
      valid_until:   formEdit.value.valid_until,
      currency:      formEdit.value.currency,
      exchange_rate: formEdit.value.exchange_rate,
      tax_included:  formEdit.value.tax_included,
      igv_rate:      formEdit.value.igv_rate,
      notes:         formEdit.value.notes,
      terms:         formEdit.value.terms,
    },
    {
      onError: (errs: any) => { formEdit.value.errors = errs || {} },
      onFinish: ()      => { formEdit.value.processing = false },
      onSuccess: () => {
        showEdit.value = false
        router.reload({ only: ['quotations'] })
      },
    }
  )
}

function submitDelete() {
  if (!current.value) return

  router.delete(`/quotations/${current.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDelete.value = false
      current.value = null
      router.reload({ only: ['quotations'] })
    },
  })
}
</script>

<template>
  <Head title="Cotizaciones" />

  <!-- Layout general de la app -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 md:p-6 space-y-4">
      <!-- ===================================================
           HEADER SUPERIOR (Título)
           =================================================== -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold">Cotizaciones</h1>
          <p class="text-sm text-muted-foreground">
            Crea y gestiona cotizaciones personalizadas.
          </p>
        </div>

        <!-- Si quieres botones de "Exportar" o "Nueva" puedes añadirlos aquí -->
         <!-- Botones derecha -->
  <div class="flex items-center gap-2">
    <!-- (Opcional) botón Exportar -->
    <!--
    <button
      class="px-3 py-2 rounded-lg border border-gray-200 text-sm hover:bg-gray-50"
    >
      Exportar
    </button>
    -->

    <!-- 👉 Botón NUEVA COTIZACIÓN -->
    <Link
      href="/quotations/create"
      class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium
             hover:bg-primary/90 transition"
    >
      + Nueva cotización
    </Link>
  </div>
      </div>

      <!-- ===================================================
           CARD: BUSCADOR + TABLA (estilo Alegra)
           =================================================== -->
      <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
        <!-- TOOLBAR (BUSCADOR + FILTRAR) -->
        

        <!-- TABLA ESTILO ALEGRA (usa el componente BaseTable) -->
        <BaseTable
          :columns="columns"
          :rows="props.quotations.data"
          :pagination="{
            currentPage: props.quotations.current_page,
            lastPage: props.quotations.last_page,
            perPage: 10,
            total: props.quotations.total,
          }"
          :selectable="true"
          :actions="rowActions"
          @change-page="goToPage"
          @action="onRowAction"
        >
          <!-- Columna CLIENTE (muestra customer.name) -->
          <template #cell-customer="{ row }">
            {{ row.customer?.name || 'Cliente general' }}
          </template>

          <!-- Columna ESTADO con badges de colores -->
          <template #cell-status="{ row }">
            <span
              v-if="row.status === 'draft'"
              class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs"
            >
              Borrador
            </span>
            <span
              v-else-if="row.status === 'sent'"
              class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs"
            >
              Enviado
            </span>
            <span
              v-else-if="row.status === 'accepted'"
              class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs"
            >
              Aceptado
            </span>
            <span
              v-else
              class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs"
            >
              Por cobrar
            </span>
          </template>

          <!-- Columna TOTAL formateada a la derecha -->
          <template #cell-total="{ row }">
            <div class="text-right">
              {{ row.currency === 'USD' ? '$' : 'S/' }}
              {{ Number(row.total ?? 0).toFixed(2) }}
            </div>
          </template>
        </BaseTable>
      </div>
    </div>

    <!-- =====================================================
         MODAL: VER COTIZACIÓN
         ===================================================== -->
    <div
      v-if="showView && current"
      class="fixed inset-0 bg-black/30 grid place-items-center z-50"
      @click.self="showView = false"
    >
      <div
        class="bg-white dark:bg-card w-full max-w-2xl rounded-xl shadow-xl border p-6 space-y-4"
      >
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">
            Cotización {{ current.number }}
          </h3>
          <button
            class="opacity-70 hover:opacity-100"
            @click="showView = false"
          >
            ✕
          </button>
        </div>

        <div class="grid md:grid-cols-2 gap-3">
          <div>
            <div class="text-xs text-muted-foreground">Cliente</div>
            <div class="font-medium">
              {{ current.customer?.name || '—' }}
            </div>
          </div>
          <div>
            <div class="text-xs text-muted-foreground">Estado</div>
            <div>{{ current.status }}</div>
          </div>
          <div>
            <div class="text-xs text-muted-foreground">Fecha</div>
            <div>{{ current.issue_date }}</div>
          </div>
          <div>
            <div class="text-xs text-muted-foreground">Vencimiento</div>
            <div>{{ current.valid_until || '—' }}</div>
          </div>
          <div>
            <div class="text-xs text-muted-foreground">Moneda</div>
            <div>{{ current.currency }}</div>
          </div>
          <div>
            <div class="text-xs text-muted-foreground">Tipo de cambio</div>
            <div>{{ current.exchange_rate ?? '—' }}</div>
          </div>
          <div>
            <div class="text-xs text-muted-foreground">IGV incluido</div>
            <div>{{ current.tax_included ? 'Sí' : 'No' }}</div>
          </div>
          <div>
            <div class="text-xs text-muted-foreground">Tasa IGV</div>
            <div>{{ Number(current.igv_rate ?? 0) * 100 }}%</div>
          </div>
          <div class="md:col-span-2">
            <div class="text-xs text-muted-foreground">Notas</div>
            <div>{{ current.notes || '—' }}</div>
          </div>
          <div class="md:col-span-2">
            <div class="text-xs text-muted-foreground">Términos</div>
            <div>{{ current.terms || '—' }}</div>
          </div>
          <div>
            <div class="text-xs text-muted-foreground">Total</div>
            <div class="font-medium">
              S/ {{ Number(current.total ?? 0).toFixed(2) }}
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t mt-2">
          <button class="px-3 py-2 rounded border" @click="showView = false">
            Cerrar
          </button>
          <button class="px-3 py-2 rounded border" @click="openEdit(current)">
            Editar
          </button>
          <Link
            :href="`/quotations/${current.id}`"
            class="px-3 py-2 rounded border"
          >
            Abrir
          </Link>
        </div>
      </div>
    </div>

    <!-- =====================================================
         MODAL: EDITAR COTIZACIÓN
         ===================================================== -->
    <div
      v-if="showEdit"
      class="fixed inset-0 bg-black/40 grid place-items-center z-50"
      @click.self="showEdit = false"
    >
      <div
        class="bg-white dark:bg-card w-full max-w-2xl rounded-xl shadow-2xl border p-6 space-y-5"
      >
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Editar cotización</h3>
          <button
            class="opacity-70 hover:opacity-100"
            @click="showEdit = false"
          >
            ✕
          </button>
        </div>

        <div class="grid md:grid-cols-2 gap-3">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-1">Cliente *</label>
            <select
              v-model="formEdit.customer_id"
              class="w-full border rounded-lg px-2 py-2"
              :class="{ 'border-red-500': !!formEdit.errors.customer_id }"
            >
              <option :value="null" disabled>Selecciona cliente…</option>
              <option
                v-for="c in props.customers"
                :key="c.id"
                :value="c.id"
              >
                {{ c.name }}
              </option>
            </select>
            <p
              v-if="formEdit.errors.customer_id"
              class="text-xs text-red-600 mt-1"
            >
              {{ formEdit.errors.customer_id }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Fecha *</label>
            <input
              type="date"
              v-model="formEdit.issue_date"
              class="w-full border rounded-lg px-2 py-2"
              :class="{ 'border-red-500': !!formEdit.errors.issue_date }"
            />
            <p
              v-if="formEdit.errors.issue_date"
              class="text-xs text-red-600 mt-1"
            >
              {{ formEdit.errors.issue_date }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Vencimiento</label>
            <input
              type="date"
              v-model="formEdit.valid_until"
              class="w-full border rounded-lg px-2 py-2"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Moneda</label>
            <select
              v-model="formEdit.currency"
              class="w-full border rounded-lg px-2 py-2"
            >
              <option value="PEN">PEN - Soles</option>
              <option value="USD">USD - Dólares</option>
            </select>
          </div>

          <div v-if="formEdit.currency === 'USD'">
            <label class="block text-sm font-medium mb-1">
              Tipo de cambio
            </label>
            <input
              type="number"
              step="0.0001"
              v-model.number="formEdit.exchange_rate"
              class="w-full border rounded-lg px-2 py-2"
              :class="{
                'border-red-500': !!formEdit.errors.exchange_rate,
              }"
            />
            <p
              v-if="formEdit.errors.exchange_rate"
              class="text-xs text-red-600 mt-1"
            >
              {{ formEdit.errors.exchange_rate }}
            </p>
          </div>

          <div class="md:col-span-2 grid grid-cols-2 gap-3">
            <label class="inline-flex items-center gap-2 mt-6">
              <input type="checkbox" v-model="formEdit.tax_included" />
              <span>Precios incluyen IGV</span>
            </label>
            <div>
              <label class="block text-sm font-medium mb-1">Tasa IGV</label>
              <input
                type="number"
                step="0.01"
                min="0"
                max="1"
                v-model.number="formEdit.igv_rate"
                class="w-full border rounded-lg px-2 py-2"
              />
            </div>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-1">Notas</label>
            <textarea
              v-model="formEdit.notes"
              rows="3"
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-1">Términos</label>
            <textarea
              v-model="formEdit.terms"
              rows="3"
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t mt-1">
          <button class="px-3 py-2 rounded border" @click="showEdit = false">
            Cancelar
          </button>
          <button
            class="px-4 py-2 rounded bg-primary text-white"
            :disabled="formEdit.processing"
            @click="submitEdit"
          >
            {{ formEdit.processing ? 'Actualizando…' : 'Actualizar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- =====================================================
         MODAL: ELIMINAR COTIZACIÓN
         ===================================================== -->
    <div
      v-if="showDelete"
      class="fixed inset-0 bg-black/40 grid place-items-center z-50"
      @click.self="showDelete = false"
    >
      <div
        class="bg-white dark:bg-card w-full max-w-md rounded-xl shadow-xl border p-6 space-y-4"
      >
        <h3 class="text-lg font-semibold">Eliminar cotización</h3>
        <p>
          ¿Seguro que deseas eliminar
          <b>{{ current?.number }}</b>? Esta acción no se puede deshacer.
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
