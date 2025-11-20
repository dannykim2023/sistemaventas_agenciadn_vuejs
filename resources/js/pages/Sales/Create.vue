<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import SaleForm from '@/components/sales-ui/SaleForm.vue'
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
  customers: Array<{ id: number; name: string; document_number: string | null; phone?: string | null }>
  quotation?: any | null
  quotations?: any[]
  products: Array<{ id: number; name: string; unit: string; price: number; tax_pct: number }> // 👈 nuevo
  defaultSeries: string      // 👈 NUEVO
  nextNumber: string   
}>()


const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Ventas', href: '/sales' },
  { title: 'Nueva', href: '/sales/create' },
]


const quotationSearch = ref('')
const showQuotationDropdown = ref(false)

const filteredQuotations = computed(() => {
  if (!props.quotations) return []
  const term = quotationSearch.value.toLowerCase()
  if (!term) return props.quotations

  return props.quotations.filter((q: any) => {
    const num = (q.number || '').toString().toLowerCase()
    const cli = (q.customer?.name || '').toLowerCase()
    return num.includes(term) || cli.includes(term)
  })
})

// cuando el usuario elige una cotización del buscador
function pickQuotation(q: any) {
  showQuotationDropdown.value = false
  // Reutilizamos el flujo que ya tenías:
  // recargamos /sales/create?quotation_id=ID
  router.get('/sales/create', { quotation_id: q.id })
}

</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <SaleForm
      :customers="props.customers"
      :quotation="props.quotation"
      :quotations="props.quotations"
      :products="props.products"
      :default-series="props.defaultSeries"
      :next-number="props.nextNumber" 
      
   />

  </AppLayout>

  
</template>
