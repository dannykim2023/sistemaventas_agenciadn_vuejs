<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { dashboard } from '@/routes'

const props = defineProps<{
  customers: Array<{ id: number; name: string; document_number: string | null }>
  sales: Array<{
    id: number
    series: string
    number: number | string
    issue_date: string
    total: number
    paid: number
    balance: number
    customer: { id: number; name: string }
  }>
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Pagos / Ingresos', href: '/payments' },
  { title: 'Nuevo ingreso', href: '/payments/create' },
]

function today() {
  return new Date().toISOString().substring(0, 10)
}

const form = useForm({
  payment_date: today(),
  amount: null as number | null,
  method: '',
  reference: '',
  notes: '',
  currency: 'PEN',          // solo para la UI, el back lo puede ignorar

  associate_sale: true,
  customer_id: null as number | null,
  sale_id: null as number | null,
})

/**
 * Ventas filtradas según cliente seleccionado
 * (si no eliges cliente se muestran todas las que tienen saldo)
 */
const filteredSales = computed(() => {
  if (!form.associate_sale) return []
  if (!form.customer_id) return props.sales
  return props.sales.filter(s => s.customer.id === form.customer_id)
})

/**
 * Total pendiente del cliente seleccionado
 */
const totalPendingCustomer = computed(() => {
  if (!form.customer_id) return 0
  return props.sales
    .filter(s => s.customer.id === form.customer_id)
    .reduce((sum, s) => sum + Number(s.balance || 0), 0)
})

/**
 * Cuando eliges una venta desde la tabla, se rellenan
 * cliente, venta y monto con el saldo pendiente.
 */
function useSale(s: any) {
  form.customer_id = s.customer.id
  form.sale_id = s.id
  form.amount = Number(s.balance) // número, no string
}

function submit() {
  form.post('/payments', {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head title="Nuevo ingreso" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 md:p-6 space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold">Nuevo ingreso</h1>
          <p class="text-sm text-muted-foreground">
            Registra un pago recibido y, si deseas, asócialo a una venta existente.
          </p>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            class="px-3 py-2 text-sm rounded-lg border"
            @click="router.get('/payments')"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="px-4 py-2 text-sm rounded-lg bg-primary text-white"
            :disabled="form.processing"
            @click="submit"
          >
            Guardar ingreso
          </button>
        </div>
      </div>

      <div
        v-if="Object.keys(form.errors).length"
        class="mb-3 p-2 rounded bg-red-50 text-[11px] text-red-600"
      >
        Hay errores en el formulario. Revisa los campos marcados.
      </div>

      <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
        <!-- Información general -->
        <div class="px-8 pt-6 pb-4 border-b grid md:grid-cols-2 gap-6">
          <div class="space-y-3">
            <div>
              <label class="block text-xs font-medium mb-1">Fecha *</label>
              <input
                type="date"
                v-model="form.payment_date"
                class="w-full border rounded-lg px-2 py-2 text-sm"
              />
              <p v-if="form.errors.payment_date" class="text-[11px] text-red-500 mt-1">
                {{ form.errors.payment_date }}
              </p>
            </div>

            <div>
              <label class="block text-xs font-medium mb-1">Método de pago</label>
              <select
                v-model="form.method"
                class="w-full border rounded-lg px-2 py-2 text-sm"
              >
                <option value="">Seleccione…</option>
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="tarjeta_credito">Tarjeta de crédito</option>
                <option value="tarjeta_debito">Tarjeta de débito</option>
                <option value="yape_plin">Yape / Plin</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium mb-1">Moneda</label>
              <select
                v-model="form.currency"
                class="w-full border rounded-lg px-2 py-2 text-sm"
              >
                <option value="PEN">PEN - Soles</option>
                <option value="USD">USD - Dólares</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium mb-1">Referencia</label>
              <input
                type="text"
                v-model="form.reference"
                class="w-full border rounded-lg px-2 py-2 text-sm"
                placeholder="N° operación, voucher…"
              />
            </div>
          </div>

          <div class="space-y-3">
            <div>
              <label class="block text-xs font-medium mb-1">Valor del ingreso *</label>
              <input
                type="number"
                step="0.01"
                v-model.number="form.amount"
                class="w-full border rounded-lg px-2 py-2 text-sm text-right"
                placeholder="0.00"
              />
              <p v-if="form.errors.amount" class="text-[11px] text-red-500 mt-1">
                {{ form.errors.amount }}
              </p>
            </div>

            <div>
              <label class="block text-xs font-medium mb-1">Notas del recibo</label>
              <textarea
                v-model="form.notes"
                rows="4"
                class="w-full border rounded-lg px-3 py-2 text-sm"
                placeholder="Notas internas sobre este ingreso..."
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Tipo de transacción / asociación a venta -->
        <div class="px-8 py-4 border-b bg-slate-50/60">
          <p class="text-sm font-semibold mb-2">
            ¿Asociar este ingreso a una venta existente?
          </p>
          <p class="text-xs text-muted-foreground mb-3">
            Puedes registrar un ingreso sin necesidad de que esté asociado a una venta.
          </p>

          <div class="flex items-center gap-6 mb-4">
            <label class="flex items-center gap-2 text-sm">
              <input
                type="radio"
                class="h-4 w-4"
                :value="true"
                v-model="form.associate_sale"
              />
              <span>Sí</span>
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input
                type="radio"
                class="h-4 w-4"
                :value="false"
                v-model="form.associate_sale"
              />
              <span>No</span>
            </label>
          </div>

          <div v-if="form.associate_sale" class="grid md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium mb-1">Contacto / Cliente</label>
              <select
                v-model="form.customer_id"
                class="w-full border rounded-lg px-2 py-2 text-sm"
              >
                <option :value="null">Seleccionar…</option>
                <option v-for="c in props.customers" :key="c.id" :value="c.id">
                  {{ c.name }}
                </option>
              </select>

              <p class="text-[11px] text-muted-foreground mt-1">
                Pendiente por cobrar a este cliente:
                <span class="font-semibold text-red-600">
                  S/ {{ totalPendingCustomer.toFixed(2) }}
                </span>
              </p>
            </div>

            <div>
              <label class="block text-xs font-medium mb-1">Venta a asociar</label>
              <select
                v-model="form.sale_id"
                class="w-full border rounded-lg px-2 py-2 text-sm"
              >
                <option :value="null">Seleccionar venta con saldo…</option>
                <option v-for="s in filteredSales" :key="s.id" :value="s.id">
                  {{ s.series }}-{{ s.number }} · {{ s.customer.name }} ·
                  saldo S/ {{ Number(s.balance).toFixed(2) }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de ventas pendientes -->
      <div class="px-6 py-4 border-t mt-2 bg-white rounded-xl shadow-sm border">
        <h2 class="text-sm font-semibold mb-2">Ventas pendientes</h2>

        <div v-if="filteredSales.length" class="overflow-x-auto">
          <table class="w-full text-xs md:text-sm border">
            <thead class="bg-slate-50">
              <tr>
                <th class="p-2">Correlativo</th>
                <th class="p-2 text-right">Total</th>
                <th class="p-2 text-right">Cobrado</th>
                <th class="p-2 text-right">Por cobrar</th>
                <th class="p-2 text-center">Acción</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="s in filteredSales" :key="s.id" class="border-t">
                <td class="p-2 font-mono">
                  {{ s.series }}-{{ String(s.number).padStart(6, '0') }}
                </td>
                <td class="p-2 text-right">S/ {{ Number(s.total).toFixed(2) }}</td>
                <td class="p-2 text-right text-emerald-600">
                  S/ {{ Number(s.paid).toFixed(2) }}
                </td>
                <td class="p-2 text-right text-red-600">
                  S/ {{ Number(s.balance).toFixed(2) }}
                </td>
                <td class="p-2 text-center">
                  <button
                    type="button"
                    @click="useSale(s)"
                    class="px-2 py-1 border rounded-md text-xs"
                  >
                    Usar esta venta
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <p v-else class="text-xs text-muted-foreground mt-2">
          Este cliente no tiene ventas pendientes por cobrar.
        </p>
      </div>
    </div>
  </AppLayout>
</template>
