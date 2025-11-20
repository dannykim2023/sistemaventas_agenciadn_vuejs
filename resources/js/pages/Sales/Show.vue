<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { BreadcrumbItem } from '@/types'
import { dashboard } from '@/routes'

const props = defineProps<{
  sale: {
    id: number
    series: string
    number: string | number
    issue_date: string
    due_date: string | null
    payment_term: string | null
    currency: string
    subtotal: number
    tax: number
    total: number
    status: string
    notes: string | null
    paid_amount?: number
    balance?: number
    customer?: {
      name: string
      document_number?: string | null
      phone?: string | null
    } | null
    items: Array<{
      id: number
      description: string
      unit?: string | null
      quantity: number
      unit_price: number
      discount?: number | null
      tax_percent?: number | null
      total: number
    }>
    payments: Array<{
      id: number
      payment_date: string
      amount: number
      method: string | null
      reference: string | null
      notes: string | null
    }>
  }
}>()

/* =========================
   Breadcrumbs
   ========================= */
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Ventas', href: '/sales' },
  {
    title: `Venta ${props.sale.series}-${props.sale.number}`,
    href: `/sales/${props.sale.id}`,
  },
]

/* =========================
   Datos derivados
   ========================= */
const currencySymbol = computed(() =>
  props.sale.currency === 'USD' ? '$' : 'S/'
)

const paidAmount = computed(
  () => Number(props.sale.paid_amount ?? 0)
)

const balance = computed(
  () => Number(props.sale.balance ?? (props.sale.total - paidAmount.value))
)

const hasBalance = computed(() => balance.value > 0.01)

/* =========================
   Modal de pago
   ========================= */
const showPaymentModal = ref(false)

function today() {
  return new Date().toISOString().substring(0, 10)
}

const paymentForm = useForm({
  payment_date: today(),
  amount: hasBalance.value ? balance.value : props.sale.total,
  method: 'efectivo',
  reference: '',
  notes: '',
})

function openPaymentModal() {
  paymentForm.reset()
  paymentForm.payment_date = today()
  paymentForm.amount = hasBalance.value ? balance.value : props.sale.total
  paymentForm.method = 'efectivo'
  showPaymentModal.value = true
}

function closePaymentModal() {
  if (!paymentForm.processing) {
    showPaymentModal.value = false
  }
}

function submitPayment() {
  paymentForm.post(`/sales/${props.sale.id}/payments`, {
    preserveScroll: true,
    onSuccess: () => {
      showPaymentModal.value = false
    },
  })
}

/* =========================
   Eliminar pago
   ========================= */
function deletePayment(paymentId: number) {
  if (!confirm('¿Eliminar este pago? Esta acción no se puede deshacer.')) return

  router.delete(`/sales/${props.sale.id}/payments/${paymentId}`, {
    preserveScroll: true,
  })
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- Barra superior -->
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-xl font-semibold">
          Venta {{ sale.series }}-{{ sale.number }}
        </h1>
        <p class="text-xs text-muted-foreground mt-1">
          Estado:
          <span
            class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
            :class="{
              'bg-emerald-50 text-emerald-700': sale.status === 'paid',
              'bg-amber-50 text-amber-700': sale.status === 'partial',
              'bg-slate-100 text-slate-700': sale.status === 'issued',
            }"
          >
            {{ sale.status }}
          </span>
        </p>
      </div>

      <div class="flex gap-2">
        <Link
          :href="`/sales/${sale.id}/edit`"
          class="px-3 py-2 rounded-lg border bg-white hover:bg-slate-50 text-sm"
        >
          Editar venta
        </Link>
        <Link
          href="/sales"
          class="px-3 py-2 rounded-lg border bg-white hover:bg-slate-50 text-sm"
        >
          Volver a ventas
        </Link>
      </div>
    </div>
    <!-- RESUMEN SUPERIOR AL ESTILO ALEGRA -->
<div class="bg-white rounded-xl shadow-sm border mb-6 px-6 py-4">
  <div class="grid grid-cols-2 md:grid-cols-4 text-center divide-y md:divide-y-0 md:divide-x">

    <!-- Valor total -->
    <div class="py-3 px-4">
      <p class="text-xs text-muted-foreground uppercase mb-1 tracking-wide">
        Valor total
      </p>
      <p class="text-lg font-semibold">
        {{ currencySymbol }} {{ Number(sale.total).toFixed(2) }}
      </p>
    </div>

    <!-- Retenido -->
    <div class="py-3 px-4">
      <p class="text-xs text-muted-foreground uppercase mb-1 tracking-wide">
        Retenido
      </p>
      <p class="text-lg font-semibold text-red-500">
        {{ currencySymbol }} {{ (0).toFixed(2) }}
      </p>
    </div>

    <!-- Cobrado -->
    <div class="py-3 px-4">
      <p class="text-xs text-muted-foreground uppercase mb-1 tracking-wide">
        Cobrado
      </p>
      <p class="text-lg font-semibold text-emerald-600">
        {{ currencySymbol }} {{ paidAmount.toFixed(2) }}
      </p>
    </div>

    <!-- Por cobrar -->
    <div class="py-3 px-4">
      <p class="text-xs text-muted-foreground uppercase mb-1 tracking-wide">
        Por cobrar
      </p>
      <p
        class="text-lg font-semibold"
        :class="balance <= 0.01 ? 'text-emerald-600' : 'text-orange-600'"
      >
        {{ currencySymbol }} {{ balance.toFixed(2) }}
      </p>
    </div>

  </div>
</div>

    <!-- Aviso errores de pago -->
    <div
      v-if="Object.keys(paymentForm.errors).length"
      class="mb-3 p-2 rounded bg-red-50 text-[11px] text-red-600"
    >
      Hay errores al registrar el pago. Revisa los campos.
    </div>

    <!-- HOJA de venta (solo lectura) -->
    <div class="bg-white rounded-2xl shadow-sm border overflow-hidden mb-6">
      <!-- Encabezado -->
      <div class="px-8 pt-6 pb-4 flex items-start justify-between gap-6 border-b">
        <div class="flex items-center gap-3">
          <div
            class="h-12 w-32 bg-slate-100 rounded-md flex items-center justify-center text-[10px] font-semibold text-slate-500"
          >
            LOGO
          </div>
          <div>
            <p class="text-sm font-semibold">AGENCIA DN</p>
            <p class="text-xs text-muted-foreground">
              Marketing &amp; Diseño Web
            </p>
          </div>
        </div>

        <div class="text-right space-y-2">
          <div>
            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">
              Venta
            </p>
            <p class="text-2xl font-semibold leading-tight">
              No.
              <span class="align-middle text-base text-muted-foreground">
                {{ sale.series }}-{{ sale.number }}
              </span>
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
              Emitida por: Dani
            </p>
          </div>

          <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="text-left">
              <label class="block text-[11px] font-medium mb-1">Serie</label>
              <input
                :value="sale.series"
                readonly
                class="w-full border rounded-lg px-2 py-1 text-xs text-right bg-gray-100 opacity-70"
              />
            </div>
            <div class="text-left">
              <label class="block text-[11px] font-medium mb-1">Número</label>
              <input
                :value="sale.number"
                readonly
                class="w-full border rounded-lg px-2 py-1 text-xs text-right bg-gray-100 opacity-70"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Cliente + Fechas -->
      <div class="px-8 py-4 grid md:grid-cols-2 gap-6 bg-slate-50/60 border-b">
        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium mb-1">Cliente</label>
            <input
              :value="sale.customer?.name || '—'"
              readonly
              class="w-full border rounded-lg px-2 py-2 text-sm bg-gray-100"
            />
          </div>

          <div class="grid sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium mb-1 opacity-60">
                Identificación
              </label>
              <input
                :value="sale.customer?.document_number || ''"
                readonly
                class="w-full border rounded-lg px-2 py-2 text-sm bg-gray-100 opacity-60"
                placeholder="RUC / DNI"
              />
            </div>
            <div>
              <label class="block text-xs font-medium mb-1 opacity-60">
                Teléfono
              </label>
              <input
                :value="sale.customer?.phone || ''"
                readonly
                class="w-full border rounded-lg px-2 py-2 text-sm bg-gray-100 opacity-60"
                placeholder="Teléfono"
              />
            </div>
          </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium mb-1">Fecha emisión</label>
            <input
              type="date"
              :value="sale.issue_date"
              readonly
              class="w-full border rounded-lg px-2 py-2 text-sm bg-gray-100"
            />
          </div>
          <div>
            <label class="block text-xs font-medium mb-1">Fecha vencimiento</label>
            <input
              type="date"
              :value="sale.due_date || ''"
              readonly
              class="w-full border rounded-lg px-2 py-2 text-sm bg-gray-100"
            />
          </div>

          <div>
            <label class="block text-xs font-medium mb-1">Moneda</label>
            <input
              :value="sale.currency === 'USD' ? 'USD - Dólares' : 'PEN - Soles'"
              readonly
              class="w-full border rounded-lg px-2 py-2 text-sm bg-gray-100"
            />
          </div>

          <div>
            <label class="block text-xs font-medium mb-1">Condición de pago</label>
            <input
              :value="sale.payment_term || ''"
              readonly
              class="w-full border rounded-lg px-2 py-2 text-sm bg-gray-100"
            />
          </div>
        </div>
      </div>

      <!-- Tabla de ítems -->
      <div class="px-8 pt-4 pb-2">
        <div class="overflow-x-auto">
          <table class="w-full text-xs md:text-sm">
            <thead class="bg-slate-50 border-y">
              <tr class="text-left">
                <th class="p-2 w-56">Item facturable</th>
                <th class="p-2 w-24">Unidad</th>
                <th class="p-2 w-24 text-right">Precio un.</th>
                <th class="p-2 w-24 text-right">Desc ({{ currencySymbol }})</th>
                <th class="p-2 w-20 text-right">IGV %</th>
                <th class="p-2 w-20 text-right">Cantidad</th>
                <th class="p-2 w-28 text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="it in sale.items"
                :key="it.id"
                class="border-b last:border-b-0"
              >
                <td class="p-2">
                  {{ it.description }}
                </td>
                <td class="p-2">
                  {{ it.unit || 'UND' }}
                </td>
                <td class="p-2 text-right">
                  {{ currencySymbol }} {{ Number(it.unit_price).toFixed(2) }}
                </td>
                <td class="p-2 text-right">
                  {{ currencySymbol }} {{ Number(it.discount || 0).toFixed(2) }}
                </td>
                <td class="p-2 text-right">
                  {{ Number(it.tax_percent ?? 0).toFixed(0) }}%
                </td>
                <td class="p-2 text-right">
                  {{ Number(it.quantity).toFixed(3) }}
                </td>
                <td class="p-2 text-right font-medium">
                  {{ currencySymbol }} {{ Number(it.total).toFixed(2) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="px-1 pt-3 pb-5 grid md:grid-cols-3 gap-6 items-start">
          <div class="md:col-span-2">
            <label class="block text-xs font-medium mb-1">
              Notas
            </label>
            <textarea
              :value="sale.notes || ''"
              readonly
              rows="3"
              class="w-full border rounded-lg px-3 py-2 text-sm bg-gray-100"
              placeholder="Notas adicionales..."
            ></textarea>
          </div>

          <div
            class="bg-slate-50 rounded-xl border px-4 py-4 flex flex-col gap-2 justify-between"
          >
            <div class="space-y-1 text-sm">
              <div class="flex justify-between">
                <span class="text-muted-foreground">Subtotal</span>
                <span>
                  {{ currencySymbol }} {{ Number(sale.subtotal).toFixed(2) }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">IGV</span>
                <span>
                  {{ currencySymbol }} {{ Number(sale.tax).toFixed(2) }}
                </span>
              </div>
              <div class="border-t mt-2 pt-2 flex justify-between font-semibold">
                <span>Total</span>
                <span>
                  {{ currencySymbol }} {{ Number(sale.total).toFixed(2) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- =============================
         CRUD DE PAGOS ASOCIADOS
         ============================= -->
    <div class="bg-white rounded-2xl shadow-sm border p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-sm font-semibold">Pagos registrados</h2>
          <p class="text-xs text-muted-foreground mt-1">
            Registra los pagos asociados a esta venta.
          </p>
        </div>

        <div class="flex items-center gap-4">
          <div class="text-right text-xs mr-4">
            <div>
              <span class="text-muted-foreground mr-1">Total venta:</span>
              <span class="font-medium">
                {{ currencySymbol }} {{ Number(sale.total).toFixed(2) }}
              </span>
            </div>
            <div>
              <span class="text-muted-foreground mr-1">Pagado:</span>
              <span class="font-medium text-emerald-700">
                {{ currencySymbol }} {{ paidAmount.toFixed(2) }}
              </span>
            </div>
            <div>
              <span class="text-muted-foreground mr-1">Saldo:</span>
              <span
                class="font-semibold"
                :class="balance <= 0.01 ? 'text-emerald-700' : 'text-amber-700'"
              >
                {{ currencySymbol }} {{ balance.toFixed(2) }}
              </span>
            </div>
          </div>

          <button
            class="px-3 py-2 rounded-lg bg-primary text-primary-foreground text-sm disabled:opacity-50"
            :disabled="!hasBalance"
            @click="openPaymentModal"
          >
            Registrar pago
          </button>
        </div>
      </div>

      <!-- Tabla de pagos -->
      <div class="overflow-x-auto">
        <table class="w-full text-xs md:text-sm">
          <thead class="bg-slate-50 border-y">
            <tr class="text-left">
              <th class="p-2 w-24">Fecha</th>
              <th class="p-2 w-32">Método</th>
              <th class="p-2 w-32">Referencia</th>
              <th class="p-2">Notas</th>
              <th class="p-2 text-right w-28">Monto</th>
              <th class="p-2 text-right w-12"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="p in sale.payments"
              :key="p.id"
              class="border-b last:border-b-0"
            >
              <td class="p-2">
                {{ p.payment_date }}
              </td>
              <td class="p-2">
                {{ p.method || '—' }}
              </td>
              <td class="p-2">
                {{ p.reference || '—' }}
              </td>
              <td class="p-2">
                <span class="line-clamp-2">
                  {{ p.notes || '—' }}
                </span>
              </td>
              <td class="p-2 text-right font-medium">
                {{ currencySymbol }} {{ Number(p.amount).toFixed(2) }}
              </td>
              <td class="p-2 text-right">
                <button
                  type="button"
                  class="text-xs text-red-600 hover:underline"
                  @click="deletePayment(p.id)"
                >
                  Eliminar
                </button>
              </td>
            </tr>

            <tr v-if="sale.payments.length === 0">
              <td
                colspan="6"
                class="p-4 text-center text-xs text-muted-foreground"
              >
                Aún no hay pagos registrados para esta venta.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL: Registrar pago -->
    <div
      v-if="showPaymentModal"
      class="fixed inset-0 z-40 flex items-center justify-center bg-black/40"
    >
      <div
        class="bg-white rounded-2xl shadow-lg w-full max-w-md p-5 relative"
      >
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold">
            Registrar pago
          </h3>
          <button
            type="button"
            class="text-slate-500 hover:text-slate-700 text-lg leading-none"
            @click="closePaymentModal"
          >
            ×
          </button>
        </div>

        <div class="space-y-3 text-sm">
          <div>
            <label class="block text-xs font-medium mb-1">Fecha</label>
            <input
              type="date"
              v-model="paymentForm.payment_date"
              class="w-full border rounded-lg px-2 py-2 text-sm"
            />
            <p
              v-if="paymentForm.errors.payment_date"
              class="mt-1 text-[11px] text-red-500"
            >
              {{ paymentForm.errors.payment_date }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-medium mb-1">Método</label>
            <select
              v-model="paymentForm.method"
              class="w-full border rounded-lg px-2 py-2 text-sm"
            >
              <option value="efectivo">Efectivo</option>
              <option value="transferencia">Transferencia</option>
              <option value="tarjeta_credito">Tarjeta de crédito</option>
              <option value="tarjeta_debito">Tarjeta de débito</option>
              <option value="yape_plin">Yape / Plin</option>
              <option value="">Otro / No especificado</option>
            </select>
            <p
              v-if="paymentForm.errors.method"
              class="mt-1 text-[11px] text-red-500"
            >
              {{ paymentForm.errors.method }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-medium mb-1">Referencia</label>
            <input
              type="text"
              v-model="paymentForm.reference"
              class="w-full border rounded-lg px-2 py-2 text-sm"
              placeholder="N° operación, voucher…"
            />
            <p
              v-if="paymentForm.errors.reference"
              class="mt-1 text-[11px] text-red-500"
            >
              {{ paymentForm.errors.reference }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-medium mb-1">Monto</label>
            <input
              type="number"
              step="0.01"
              v-model.number="paymentForm.amount"
              class="w-full border rounded-lg px-2 py-2 text-sm text-right"
            />
            <p
              v-if="paymentForm.errors.amount"
              class="mt-1 text-[11px] text-red-500"
            >
              {{ paymentForm.errors.amount }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-medium mb-1">Notas</label>
            <textarea
              v-model="paymentForm.notes"
              rows="3"
              class="w-full border rounded-lg px-2 py-2 text-sm"
              placeholder="Notas internas sobre este pago..."
            ></textarea>
            <p
              v-if="paymentForm.errors.notes"
              class="mt-1 text-[11px] text-red-500"
            >
              {{ paymentForm.errors.notes }}
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-2 mt-4">
          <button
            type="button"
            class="px-3 py-2 rounded-lg border bg-white text-sm"
            :disabled="paymentForm.processing"
            @click="closePaymentModal"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="px-4 py-2 rounded-lg bg-primary text-primary-foreground text-sm"
            :disabled="paymentForm.processing"
            @click="submitPayment"
          >
            Guardar pago
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
