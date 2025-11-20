<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
const props = defineProps<{ quotation:any }>()
</script>

<template>
  <Head :title="`Cotización ${props.quotation.number}`" />
  <div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Cotización {{ props.quotation.number }}</h1>
      <Link href="/quotations" class="underline">Volver</Link>
    </div>

    <div class="grid md:grid-cols-3 gap-3">
      <div class="border rounded p-3">
        <h3 class="font-semibold mb-2">Cliente</h3>
        <div>{{ props.quotation.customer?.name }}</div>
        <div class="text-sm text-gray-600">{{ props.quotation.customer?.document_type }} {{ props.quotation.customer?.document_number }}</div>
        <div class="text-sm text-gray-600">{{ props.quotation.customer?.email }}</div>
      </div>
      <div class="border rounded p-3">
        <h3 class="font-semibold mb-2">Datos</h3>
        <div>Fecha: {{ props.quotation.issue_date }}</div>
        <div>Vence: {{ props.quotation.valid_until ?? '—' }}</div>
        <div>Moneda: {{ props.quotation.currency }}</div>
        <div>Estado: {{ props.quotation.status }}</div>
      </div>
      <div class="border rounded p-3">
        <h3 class="font-semibold mb-2">Totales</h3>
        <div>Subtotal: S/ {{ Number(props.quotation.subtotal).toFixed(2) }}</div>
        <div>IGV: S/ {{ Number(props.quotation.tax_total).toFixed(2) }}</div>
        <div class="font-semibold">Total: S/ {{ Number(props.quotation.total).toFixed(2) }}</div>
      </div>
    </div>

    <div class="border rounded overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="bg-gray-50">
          <th class="p-2 text-left">Descripción</th><th>Cant</th><th>Und</th><th>P.Unit</th><th>Desc</th><th>IGV</th><th class="text-right">Importe</th>
        </tr></thead>
        <tbody>
          <tr v-for="it in props.quotation.items" :key="it.id" class="border-t">
            <td class="p-2">{{ it.description }}</td>
            <td class="p-2">{{ it.quantity }}</td>
            <td class="p-2">{{ it.unit }}</td>
            <td class="p-2">S/ {{ Number(it.unit_price).toFixed(2) }}</td>
            <td class="p-2">{{ it.discount_pct }}%</td>
            <td class="p-2">{{ (Number(it.tax_pct)*100).toFixed(0) }}%</td>
            <td class="p-2 text-right">S/ {{ Number(it.line_total).toFixed(2) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="grid md:grid-cols-2 gap-3">
      <div class="border rounded p-3">
        <h3 class="font-semibold mb-2">Notas</h3>
        <p class="text-sm whitespace-pre-line">{{ props.quotation.notes || '—' }}</p>
      </div>
      <div class="border rounded p-3">
        <h3 class="font-semibold mb-2">Términos</h3>
        <p class="text-sm whitespace-pre-line">{{ props.quotation.terms || '—' }}</p>
      </div>
    </div>
  </div>
</template>
