<script setup lang="ts">
import { router, Head } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import BaseTable from '@/components/table/BaseTable.vue'

/* ==============================
   PROPS
   ============================== */
const props = defineProps<{
  sales: {
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
    status?: 'all' | 'issued' | 'paid' | 'cancelled' | null
  }
}>()

const openSalePdf = (id: number | string) => {
  window.open(`/sales/${id}/pdf`, '_blank', 'noopener')
  // si usas Ziggy:
  // window.open(route('sales.pdf', id), '_blank', 'noopener')
}


/* ==============================
   HELPERS: dinero y estado
   ============================== */

// Formatea dinero según moneda de la venta
function formatMoney(value: number | string, currency?: string) {
  const num = Number(value || 0)
  const cur = currency || 'PEN'

  try {
    return new Intl.NumberFormat('es-PE', {
      style: 'currency',
      currency: cur,
      minimumFractionDigits: 2,
    }).format(num)
  } catch {
    // fallback simple si por algo falla Intl
    const symbol = cur === 'USD' ? '$' : 'S/'
    return `${symbol} ${num.toFixed(2)}`
  }
}

// Traduce el estado a español
function statusLabel(status?: string | null) {
  switch (status) {
    case 'issued':
      return 'Emitida'
    case 'partial':
      return 'Parcial'
    case 'paid':
      return 'Pagada'
    case 'cancelled':
      return 'Anulada'
    default:
      return status || '—'
  }
}



/* ==============================
   UI BASICS
   ============================== */
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Ventas', href: '/sales' },
]

const search = ref(props.filters?.search ?? '')
const statusTab = ref<'all' | 'pending' | 'issued' | 'paid' | 'cancelled'>(
  (props.filters?.status as any) ?? 'all'
)


watch(statusTab, fetchList)

/* ==============================
   LISTADO / FILTRO
   ============================== */
function fetchList() {
  router.get(
    '/sales',
    {
      search: search.value || undefined,
      status: statusTab.value !== 'all' ? statusTab.value : undefined,
    },
    { preserveState: true, replace: true }
  )
}

// Se usa cuando el BaseTable dispara @filter
function handleFilter(term: string) {
  search.value = term
  fetchList()
}

// Paginación del BaseTable
function goToPage(page: number) {
  router.get(
    '/sales',
    {
      page,
      search: search.value || undefined,
      status: statusTab.value !== 'all' ? statusTab.value : undefined,
    },
    { preserveState: true, replace: true }
  )
}

/* ==============================
   CONFIG TABLA (COLUMNAS + ACCIONES)
   ============================== */
const columns = [
  { key: 'document', label: 'Documento' },
  { key: 'customer_name', label: 'Cliente' },
  { key: 'issue_date', label: 'Fecha emisión' },
  { key: 'total', label: 'Total' },
  { key: 'paid', label: 'Pagado' },
  { key: 'balance', label: 'Saldo' },
  { key: 'status', label: 'Estado' },
  { key: 'pdf',    label: 'Contrato PDF' }, // 👈 nuevo
]

const rowActions = [
  { key: 'view', label: 'Ver detalle' },
  { key: 'edit', label: 'Editar' },
  // luego puedes agregar "delete", "anular", etc.
]

// viene del menú de acciones (⋮) del BaseTable
function onRowAction(payload: { action: string; row: any }) {
  const { action, row } = payload

  if (action === 'view') {
    router.get(`/sales/${row.id}`)
  }

  if (action === 'edit') {
    router.get(`/sales/${row.id}/edit`)
  }
  if (action === 'pdf') {
    // Abre en nueva pestaña la ruta del PDF
    window.open(`/sales/${row.id}/pdf`, '_blank', 'noopener')
  }
}
</script>

<template>
  <Head title="Ventas" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 md:p-6 space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold">Ventas</h1>
          <p class="text-sm text-muted-foreground">
            Administra tus documentos de venta, pagos y estados de cobranza.
          </p>
        </div>
        <button
        class="px-4 py-2 rounded-lg bg-primary text-white text-sm"
        type="button"
        @click="router.get('/sales/create')"
        >
        + Nueva venta
        </button>
        </div>

      <!-- Tabs de estado -->
      <div class="border-b">
        <nav class="-mb-px flex gap-6">
          <button
            class="py-3 border-b-2"
            :class="
              statusTab === 'all'
                ? 'border-primary text-primary'
                : 'border-transparent text-muted-foreground hover:text-foreground'
            "
            @click="statusTab = 'all'"
          >
            Todas
          </button>
          <button
            class="py-3 border-b-2"
            :class="
              statusTab === 'issued'
                ? 'border-primary text-primary'
                : 'border-transparent text-muted-foreground hover:text-foreground'
            "
            @click="statusTab = 'issued'"
          >
            Emitidas
          </button>

           <!-- Por cobrar -->
          <button
            class="py-3 border-b-2"
            :class="
              statusTab === 'pending'
                ? 'border-primary text-primary'
                : 'border-transparent text-muted-foreground hover:text-foreground'
            "
            @click="statusTab = 'pending'"
          >
            Por cobrar
          </button>

          <button
            class="py-3 border-b-2"
            :class="
              statusTab === 'paid'
                ? 'border-primary text-primary'
                : 'border-transparent text-muted-foreground hover:text-foreground'
            "
            @click="statusTab = 'paid'"
          >
            Pagadas
          </button>
          <button
            class="py-3 border-b-2"
            :class="
              statusTab === 'cancelled'
                ? 'border-primary text-primary'
                : 'border-transparent text-muted-foreground hover:text-foreground'
            "
            @click="statusTab = 'cancelled'"
          >
            Anuladas
          </button>
        </nav>
      </div>

      <!-- Card -->
      <div class="bg-white dark:bg-card rounded-xl shadow-sm border">
        <!-- Tabla reutilizable -->
        <BaseTable
          :columns="columns"
          :rows="props.sales.data"
          :pagination="{
            currentPage: props.sales.current_page,
            lastPage: props.sales.last_page,
            perPage: props.sales.per_page,
            total: props.sales.total,
          }"
          :actions="rowActions"
          search-placeholder="Buscar por número o cliente"
          @filter="handleFilter"
          @change-page="goToPage"
          @action="onRowAction"
          
        >
          <!-- Documento -->
          <template #cell-document="{ row }">
            <div class="font-mono text-xs">
              {{ row.series }}-{{ row.number }}
            </div>
          </template>

          <!-- Cliente -->
          <template #cell-customer_name="{ row }">
            <div class="flex items-center gap-2">
              <div
                class="w-6 h-6 rounded-full bg-primary/10 text-primary grid place-items-center text-xs font-semibold"
              >
                {{ (row.customer?.name || 'C').substring(0, 1).toUpperCase() }}
              </div>
              <span class="font-medium">
                {{ row.customer?.name || '—' }}
              </span>
            </div>
          </template>

          <!-- Fecha emisión -->
          <template #cell-issue_date="{ row }">
            {{ row.issue_date }}
          </template>

          <<!-- Total -->
          <template #cell-total="{ row }">
            {{ formatMoney(row.total, row.currency) }}
          </template>

          <!-- Pagado -->
          <template #cell-paid="{ row }">
            {{ formatMoney(row.payments_sum_amount, row.currency) }}
          </template>

          <!-- Saldo -->
          <template #cell-balance="{ row }">
            {{ formatMoney((row.total || 0) - (row.payments_sum_amount || 0), row.currency) }}
          </template>




          <!-- Estado -->
        <template #cell-status="{ row }">
          <span
            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
            :class="{
              'bg-emerald-50 text-emerald-700': row.status === 'paid',
              'bg-sky-50 text-sky-700': row.status === 'partial',
              'bg-amber-50 text-amber-700': row.status === 'issued',
              'bg-rose-50 text-rose-700': row.status === 'cancelled',
              'bg-gray-100 text-gray-600':
                !['paid', 'partial', 'issued', 'cancelled'].includes(row.status),
            }"
          >
            {{ statusLabel(row.status) }}
          </span>
        </template>
        <template #cell-pdf="{ row }">
      <button
        type="button"
        class="px-2 py-1 text-xs rounded-md border border-primary text-primary hover:bg-primary/5"
        @click="openSalePdf(row.id)"
      >
        Ver PDF
      </button>


    </template>
          
        </BaseTable>
      </div>
    </div>
  </AppLayout>
</template>
