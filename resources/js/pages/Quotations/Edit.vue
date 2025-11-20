<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import QuotationForm from '@/components/QuotationForm.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'

const props = defineProps<{
  quotation: any
  customers: any[]
  products: any[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Cotizaciones', href: '/quotations' },
  {
    title: props.quotation.number
      ? `Cotización ${props.quotation.number}`
      : 'Cotización',
    href: `/quotations/${props.quotation.id}`,
  },
  { title: 'Editar', href: `/quotations/${props.quotation.id}/edit` },
]
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <QuotationForm
      :customers="props.customers"
      :products="props.products"
      :quotation="props.quotation"
      mode="edit"
    />
  </AppLayout>
</template>
