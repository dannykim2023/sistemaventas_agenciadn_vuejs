<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import QuotationForm from '@/components/QuotationForm.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'

const props = defineProps<{
  customers: any[]
  products: any[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Cotizaciones', href: '/quotations' },
  { title: 'Nueva', href: '/quotations/create' },
]
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <QuotationForm
      :customers="props.customers"
      :products="props.products"
    />
  </AppLayout>
</template>
