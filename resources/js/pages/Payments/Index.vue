<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import BaseTable from '@/components/table/BaseTable.vue'
import type { BreadcrumbItem } from '@/types'
import { dashboard } from '@/routes'

const props = defineProps<{
  payments: {
    data: any[]
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  filters: {
    search?: string
  }
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Pagos / Ingresos', href: '/payments' },
]

const search = ref(props.filters?.search ?? '')

const columns = [
  { key: 'date', label: 'Fecha' },
  { key: 'customer', label: 'Contacto / Cliente' },
  { key: 'sale', label: 'Venta asociada' },
  { key: 'amount', label: 'Valor' },
  { key: 'method', label: 'Método' },
]

const actions: { key: string; label: string; variant?: 'normal' | 'danger' }[] = [
  { key: 'edit', label: 'Editar', variant: 'normal' },
  { key: 'delete', label: 'Eliminar', variant: 'danger' },
]


function fetchList(page?: number) {
  router.get(
    '/payments',
    {
      page: page ?? undefined,
      search: search.value || undefined,
    },
    { preserveState: true, replace: true }
  )
}

function handleFilter(term: string) {
  search.value = term
  fetchList()
}

function goToPage(page: number) {
  fetchList(page)
}

function onRowAction({ action, row }: { action: string; row: any }) {
  if (action === 'edit') {
    router.get(`/payments/${row.id}/edit`)
  }

  if (action === 'delete') {
    if (!confirm('¿Eliminar este pago?')) return

    router.delete(`/payments/${row.id}`, {
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <Head title="Pagos / Ingresos" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 md:p-6 space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold">Pagos / Ingresos</h1>
          <p class="text-sm text-muted-foreground">
            Registra los pagos recibidos y vincúlalos a tus ventas.
          </p>
        </div>
        <button
          class="px-4 py-2 rounded-lg bg-primary text-white text-sm"
          type="button"
          @click="router.get('/payments/create')"
        >
          + Nuevo ingreso
        </button>
      </div>

      <div class="bg-white rounded-xl shadow-sm border">
        <BaseTable
          :columns="columns"
          :rows="props.payments.data"
          :pagination="{
            currentPage: props.payments.current_page,
            lastPage: props.payments.last_page,
            perPage: props.payments.per_page,
            total: props.payments.total,
          }"
          :actions="actions"
          search-placeholder="Buscar por cliente o referencia"
          @filter="handleFilter"
          @change-page="goToPage"
          @action="onRowAction"
        >
          <template #cell-date="{ row }">
            {{ row.payment_date }}
          </template>

          <template #cell-customer="{ row }">
            {{ row.customer?.name || '—' }}
          </template>

          <template #cell-sale="{ row }">
            <span v-if="row.sale">
              {{ row.sale.series }}-{{ row.sale.number }}
            </span>
            <span v-else class="text-xs text-muted-foreground">
              Sin venta
            </span>
          </template>

          <template #cell-amount="{ row }">
            <span class="font-medium">
              S/ {{ Number(row.amount || 0).toFixed(2) }}
            </span>
          </template>

          <template #cell-method="{ row }">
            {{ row.method || '—' }}
          </template>
        </BaseTable>
      </div>
    </div>
  </AppLayout>
</template>
