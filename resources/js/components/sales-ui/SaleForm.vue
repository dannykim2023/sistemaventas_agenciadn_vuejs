<script setup lang="ts">
import { Link, useForm, router } from '@inertiajs/vue3'
import { computed, watch, ref } from 'vue'
import CustomerModal from '@/components/CustomerModal.vue'

/* =======================
   Modal: nuevo cliente
   ======================= */
const showCustomerModal = ref(false)

const customerForm = useForm({
  type: 'company' as 'person' | 'company',
  document_type: 'RUC',
  document_number: '',
  name: '',
  email: '',
  phone: '',
  address: '',
  notes: '',
  is_active: true,
})

function submitCustomer() {
  customerForm.post('/customers', {
    onSuccess: () => {
      showCustomerModal.value = false
      customerForm.reset()
      customerForm.type = 'company'
      customerForm.document_type = 'RUC'
      customerForm.is_active = true
      router.reload({ only: ['customers'] })
    },
  })
}

/* =======================
   Props
   ======================= */
const props = defineProps<{
  customers: Array<{ id: number; name: string; document_number: string | null; phone?: string | null }>
  products: Array<{ id: number; name: string; unit: string; price: number; tax_pct: number }>
  quotation?: any | null
  quotations?: any[]
  defaultSeries: string
  nextNumber: string
  sale?: any | null
}>()

function today() {
  return new Date().toISOString().substring(0, 10)
}

/* =======================
   Form principal de venta
   ======================= */

const isEditing = computed(() => !!props.sale)

const form = useForm({
  customer_id: props.sale?.customer_id
    ?? props.quotation?.customer_id
    ?? null,

  quotation_id: props.quotation?.id ?? null,

  customer_tax_id:
    props.sale?.customer?.document_number
    ?? props.quotation?.customer?.document_number
    ?? '',

  customer_phone:
    props.sale?.customer?.phone
    ?? props.quotation?.customer?.phone
    ?? '',

  series: props.sale?.series ?? props.defaultSeries,
  number: props.sale?.number ?? props.nextNumber,

  issue_date: props.sale?.issue_date ?? today(),
  due_date: props.sale?.due_date ?? props.quotation?.valid_until ?? today(),
  payment_term: props.sale?.payment_term ?? '',

  currency: props.sale?.currency ?? 'PEN',

  register_payment: false,
  payment_date: today(),
  payment_amount: null as number | null,
  payment_method: '',
  payment_reference: '',
  payment_notes: '',

  items: props.sale
    ? props.sale.items.map((it: any) => ({
        product_id: it.product_id ?? null,
        description: it.description ?? it.product?.name ?? '',
        unit: it.unit ?? it.product?.unit ?? 'UND',
        quantity: it.quantity ?? 1,
        unit_price: it.unit_price ?? it.product?.price ?? 0,
        discount: it.discount ?? 0,
        tax_percent: it.tax_percent ?? it.product?.tax_pct ?? 18,
      }))
    : props.quotation?.items?.length
    ? props.quotation.items.map((it: any) => ({
        product_id: it.product_id ?? null,
        description: it.description ?? it.product?.name ?? '',
        unit: it.unit ?? it.product?.unit ?? 'UND',
        quantity: it.quantity ?? 1,
        unit_price: it.unit_price ?? it.product?.price ?? 0,
        discount: it.discount ?? 0,
        tax_percent: it.tax_percent ?? it.product?.tax_pct ?? 18,
      }))
    : [
        {
          product_id: null as number | null,
          description: '',
          unit: 'UND',
          quantity: 1,
          unit_price: 0,
          discount: 0,
          tax_percent: 18,
        },
      ],

  notes: props.sale?.notes ?? props.quotation?.notes ?? '',
})


/* =======================
   Buscador de cotizaciones
   (si luego lo reactivas)
   ======================= */
const quotationSearch = ref('')
const showQuotationDropdown = ref(false)

const filteredQuotations = computed<any[]>(() => {
  const list = props.quotations ?? []
  const term = quotationSearch.value.trim().toLowerCase()
  if (!term) return list

  return list.filter((q: any) => {
    const num = String(q.number ?? `CTZ-${q.id}`).toLowerCase()
    const cli = String(q.customer?.name ?? '').toLowerCase()
    return num.includes(term) || cli.includes(term)
  })
})

function pickQuotation(q: any) {
  showQuotationDropdown.value = false
  router.get('/sales/create', { quotation_id: q.id })
}

/* =======================
   Moneda y totales
   ======================= */
const currencySymbol = computed(() => (form.currency === 'USD' ? '$' : 'S/'))

const summary = computed(() => {
  let subtotal = 0
  let tax = 0

  for (const it of form.items) {
    const qty = Number(it.quantity) || 0
    const price = Number(it.unit_price) || 0
    const discount = Number(it.discount) || 0
    const taxPercent = Number(it.tax_percent ?? 18) || 0

    const base = Math.max(0, qty * price - discount)
    const lineTax = base * (taxPercent / 100)

    subtotal += base
    tax += lineTax
  }

  return {
    subtotal,
    tax,
    total: subtotal + tax,
  }
})

/* =======================
   Cliente -> RUC / teléfono
   ======================= */
watch(
  () => form.customer_id,
  id => {
    if (!id) {
      form.customer_tax_id = ''
      form.customer_phone = ''
      return
    }

    const customer = props.customers.find(c => c.id == id)

    if (customer) {
      form.customer_tax_id = customer.document_number ?? ''
      form.customer_phone  = customer.phone ?? ''   // 👈 AQUÍ
      // si luego agregas phone en la consulta, puedes rellenarlo aquí
    } else {
      form.customer_tax_id = ''
      form.customer_phone = ''
    }
  },
  { immediate: !!(props.quotation || props.sale) }
)

/* =======================
   Líneas: agregar / quitar
   ======================= */
function addLine() {
  form.items.push({
    product_id: null as number | null,
    description: '',
    unit: 'UND',
    quantity: 1,
    unit_price: 0,
    discount: 0,
    tax_percent: 18,
  })
}

function removeLine(i: number) {
  if (form.items.length <= 1) return
  form.items.splice(i, 1)
}

/* =======================
   Al elegir producto
   ======================= */
function onPickProduct(i: number) {
  const pid = form.items[i].product_id
  if (!pid) return

  const p = props.products.find(p => p.id === pid)
  if (p) {
    form.items[i].description = p.name
    form.items[i].unit = p.unit
    form.items[i].unit_price = p.price
    form.items[i].tax_percent = p.tax_pct ?? 18
  }
}

/* =======================
   Total por línea
   ======================= */
function calcLineTotal(it: {
  quantity: number
  unit_price: number
  discount: number
  tax_percent: number
}) {
  const qty = Number(it.quantity) || 0
  const price = Number(it.unit_price) || 0
  const discount = Number(it.discount) || 0
  const taxPercent = Number(it.tax_percent ?? 18) || 0

  const base = Math.max(0, qty * price - discount)
  const lineTax = base * (taxPercent / 100)
  return base + lineTax
}

/* =======================
   Enviar venta
   ======================= */
function submit() {
  if (isEditing.value && props.sale) {
    // editar
    form.put(`/sales/${props.sale.id}`, {
      preserveScroll: true,
    })
  } else {
    // crear
    form.post('/sales', {
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <!-- Barra superior -->
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">
      {{ isEditing ? 'Editar venta' : 'Nueva venta' }}
    </h1>
    <div class="flex gap-2">
      <Link
        href="/sales"
        class="px-3 py-2 rounded-lg border bg-background hover:bg-muted/60 text-sm"
      >
        Cancelar
      </Link>
      <button
        class="px-4 py-2 rounded-lg bg-primary text-primary-foreground text-sm"
        :disabled="form.processing"
        @click="submit"
      >
        {{ isEditing ? 'Actualizar venta' : 'Guardar venta' }}
      </button>
    </div>
  </div>

  <div
    v-if="Object.keys(form.errors).length"
    class="mb-3 p-2 rounded bg-red-50 text-[11px] text-red-600"
  >
    Hay errores en el formulario. Revisa los campos marcados.
  </div>

  <!-- HOJA de venta -->
  <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
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
              {{ form.series }}-{{ form.number || '—' }}
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
              v-model="form.series"
              readonly
              class="w-full border rounded-lg px-2 py-1 text-xs text-right bg-gray-100 opacity-70"
            />
            <p v-if="form.errors.series" class="mt-1 text-[10px] text-red-500">
              {{ form.errors.series }}
            </p>
          </div>

          <div class="text-left">
            <label class="block text-[11px] font-medium mb-1">Número</label>
            <input
              v-model="form.number"
              readonly
              class="w-full border rounded-lg px-2 py-1 text-xs text-right bg-gray-100 opacity-70"
            />
            <p v-if="form.errors.number" class="mt-1 text-[10px] text-red-500">
              {{ form.errors.number }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Cliente + Fechas -->
    <div class="px-8 py-4 grid md:grid-cols-2 gap-6 bg-slate-50/60 border-b">
      <div class="space-y-3">
        <div>
          <label class="block text-xs font-medium mb-1">Cliente *</label>

          <div class="flex items-end gap-2">
            <select
              v-model="form.customer_id"
              class="flex-1 border rounded-lg px-2 py-2 text-sm"
            >
              <option :value="null" disabled>Selecciona cliente…</option>
              <option v-for="c in props.customers" :key="c.id" :value="c.id">
                {{ c.name }}
              </option>
            </select>

            <button
              type="button"
              class="inline-flex items-center gap-1 text-xs text-primary hover:underline mb-1"
              @click="showCustomerModal = true"
            >
              <span class="text-lg leading-none">＋</span>
              <span>Nuevo cliente</span>
            </button>
          </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium mb-1 opacity-60">
              Identificación
            </label>
            <input
              v-model="form.customer_tax_id"
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
              v-model="form.customer_phone"
              readonly
              class="w-full border rounded-lg px-2 py-2 text-sm bg-gray-100 opacity-60"
              placeholder="Teléfono"
            />
          </div>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium mb-1">Fecha emisión *</label>
          <input
            type="date"
            v-model="form.issue_date"
            class="w-full border rounded-lg px-2 py-2 text-sm"
          />
        </div>
        <div>
          <label class="block text-xs font-medium mb-1">Fecha vencimiento</label>
          <input
            type="date"
            v-model="form.due_date"
            class="w-full border rounded-lg px-2 py-2 text-sm"
          />
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
          <label class="block text-xs font-medium mb-1">
            Condición de pago
          </label>
          <input
            type="text"
            v-model="form.payment_term"
            placeholder="Contado, 7 días, 30 días..."
            class="w-full border rounded-lg px-2 py-2 text-sm"
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
              <th class="p-2 w-8"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(it, i) in form.items"
              :key="i"
              class="border-b last:border-b-0"
            >
              <td class="p-2">
                <select
                  v-model="it.product_id"
                  @change="onPickProduct(i)"
                  class="w-full border rounded-lg px-2 py-1 text-xs md:text-sm"
                >
                  <option :value="null">Buscar item facturable…</option>
                  <option v-for="p in props.products" :key="p.id" :value="p.id">
                    {{ p.name }}
                  </option>
                </select>
              </td>

              <td class="p-2">
                <input
                  v-model="it.unit"
                  class="w-full border rounded-lg px-2 py-1 text-xs md:text-sm"
                  placeholder="UND"
                />
              </td>

              <td class="p-2 text-right">
                <input
                  type="number"
                  step="0.01"
                  v-model.number="it.unit_price"
                  class="w-full border rounded-lg px-2 py-1 text-xs md:text-sm text-right"
                />
              </td>

              <td class="p-2 text-right">
                <input
                  type="number"
                  step="0.01"
                  v-model.number="it.discount"
                  class="w-full border rounded-lg px-2 py-1 text-xs md:text-sm text-right"
                />
              </td>

              <td class="p-2 text-right">
                <select
                  v-model.number="it.tax_percent"
                  class="w-full border rounded-lg px-2 py-1 text-xs md:text-sm"
                >
                  <option :value="0">Exonerado (0%)</option>
                  <option :value="0">Inafecto (0%)</option>
                  <option :value="18">IGV (18%)</option>
                </select>
              </td>

              <td class="p-2 text-right">
                <input
                  type="number"
                  step="0.001"
                  v-model.number="it.quantity"
                  class="w-full border rounded-lg px-2 py-1 text-xs md:text-sm text-right"
                />
              </td>

              <td class="p-2 text-right font-medium">
                {{ currencySymbol }} {{ calcLineTotal(it).toFixed(2) }}
              </td>

              <td class="p-2 text-right">
                <button
                  class="text-red-500 text-sm"
                  type="button"
                  @click="removeLine(i)"
                >
                  ✕
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pt-3">
        <button
          class="text-primary text-sm font-medium"
          type="button"
          @click="addLine"
        >
          + Agregar línea
        </button>
      </div>
    </div>

    <!-- Notas + Totales -->
    <div class="px-8 pb-8 pt-4 border-t grid md:grid-cols-3 gap-6">
      <div class="md:col-span-2">
        <label class="block text-xs font-medium mb-1">
          Notas
        </label>
        <textarea
          v-model="form.notes"
          rows="4"
          class="w-full border rounded-lg px-3 py-2 text-sm"
          placeholder="Notas adicionales para el cliente..."
        ></textarea>
      </div>

      <div
        class="bg-slate-50 rounded-xl border px-4 py-4 flex flex-col gap-2 justify-between"
      >
        <div class="space-y-1 text-sm">
          <div class="flex justify-between">
            <span class="text-muted-foreground">Subtotal</span>
            <span>{{ currencySymbol }} {{ summary.subtotal.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">IGV</span>
            <span>{{ currencySymbol }} {{ summary.tax.toFixed(2) }}</span>
          </div>
          <div class="border-t mt-2 pt-2 flex justify-between font-semibold">
            <span>Total</span>
            <span>{{ currencySymbol }} {{ summary.total.toFixed(2) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- SECCIÓN: Pago recibido (solo para CREAR, no para editar) -->
  <div
    v-if="!isEditing"
    class="px-8 pb-8 pt-4 border-t mt-4 bg-slate-50/60 rounded-b-2xl"
  >
    <div class="flex items-center justify-between mb-3">
      <div>
        <p class="text-sm font-semibold">Registrar pago inicial</p>
        <p class="text-xs text-muted-foreground">
          Opcional. Si ya recibiste un pago de esta venta, regístralo aquí.
        </p>
      </div>
      <label class="flex items-center gap-2 text-xs">
        <span>Registrar pago</span>
        <input
          type="checkbox"
          v-model="form.register_payment"
          class="h-4 w-4"
        />
      </label>
    </div>

    <div v-if="form.register_payment" class="grid md:grid-cols-4 gap-3 items-end">
      <div>
        <label class="block text-xs font-medium mb-1">Fecha</label>
        <input
          type="date"
          v-model="form.payment_date"
          class="w-full border rounded-lg px-2 py-2 text-sm"
        />
      </div>

      <div>
        <label class="block text-xs font-medium mb-1">Método</label>
        <select
          v-model="form.payment_method"
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
        <label class="block text-xs font-medium mb-1">Referencia</label>
        <input
          type="text"
          v-model="form.payment_reference"
          class="w-full border rounded-lg px-2 py-2 text-sm"
          placeholder="N° operación, voucher…"
        />
      </div>

      <div>
        <label class="block text-xs font-medium mb-1">Monto</label>
        <input
          type="number"
          step="0.01"
          v-model.number="form.payment_amount"
          class="w-full border rounded-lg px-2 py-2 text-sm text-right"
          placeholder="0.00"
        />
      </div>
    </div>

    <div v-if="form.register_payment" class="mt-3">
      <label class="block text-xs font-medium mb-1">Notas del pago</label>
      <textarea
        v-model="form.payment_notes"
        rows="2"
        class="w-full border rounded-lg px-2 py-2 text-sm"
        placeholder="Notas internas sobre este pago..."
      ></textarea>
    </div>
  </div>

  <!-- Modal cliente -->
  <CustomerModal
    :open="showCustomerModal"
    mode="create"
    @close="showCustomerModal = false"
    @saved="() => { showCustomerModal = false; router.reload({ only: ['customers'] }) }"
  />
</template>
