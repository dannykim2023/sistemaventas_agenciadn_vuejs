<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import SaleForm from '@/components/sales-ui/SaleForm.vue'
import type { BreadcrumbItem } from '@/types'
import { dashboard } from '@/routes'

const props = defineProps<{
  sale: any
  customers: Array<{ id: number; name: string; document_number: string | null; phone?: string | null }>
  products: Array<{ id: number; name: string; unit: string; price: number; tax_pct: number }>
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Ventas', href: '/sales' },
  {
    title: `Editar venta ${props.sale.series}-${props.sale.number}`,
    href: `/sales/${props.sale.id}/edit`,
  },
]
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <SaleForm
      :customers="props.customers"
      :quotation="null"
      :quotations="[]"
      :products="props.products"
      :default-series="props.sale.series"
      :next-number="props.sale.number"
      :sale="props.sale"
    />
  </AppLayout>
</template>
