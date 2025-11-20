<script setup lang="ts">
import { Link, useForm, router } from '@inertiajs/vue3'
import { computed, watch, ref } from 'vue'
import CustomerModal from '@/components/CustomerModal.vue'

/* =========================================================
   Modal: nuevo cliente rápido
   ========================================================= */
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

/* =========================================================
   Props del controlador
   ========================================================= */
const props = defineProps<{
  customers: Array<{ id: number; name: string; tax_id: string | null; phone: string | null }>
  products: Array<{ id: number; name: string; unit: string; price: number; tax_pct: number }>
  mode?: 'create' | 'edit'
  quotation?: any
}>()

const isEdit = computed(() => props.mode === 'edit')

/* =========================================================
   Form principal
   - Solo IGV por ítem (tax_pct)
   - Moneda PEN / USD
   ========================================================= */
const form = useForm(
  isEdit.value && props.quotation
    ? {
        customer_id: props.quotation.customer_id,
        customer_tax_id: (() => {
          const c = props.customers.find(c => c.id === props.quotation.customer_id)
          return c?.tax_id ?? props.quotation.customer?.document_number ?? ''
        })(),
        customer_phone: (() => {
          const c = props.customers.find(c => c.id === props.quotation.customer_id)
          return c?.phone ?? props.quotation.customer?.phone ?? ''
        })(),
        issue_date: props.quotation.issue_date,
        valid_until: props.quotation.valid_until ?? props.quotation.issue_date,
        currency: props.quotation.currency,
        exchange_rate: props.quotation.exchange_rate,
        notes: props.quotation.notes ?? '',
        terms: props.quotation.terms ?? '',
        items:
          props.quotation.items.length > 0
            ? props.quotation.items.map((it: any) => ({

                product_id: it.product_id,
                description: it.description ?? '',
                unit: it.unit ?? 'UND',
                quantity: it.quantity ?? 1,
                unit_price: it.unit_price ?? 0,
                discount_pct: it.discount_pct ?? 0,
                discount_amount: it.discount_amount ?? 0,
                tax_pct: it.tax_pct ?? 0.18,
              }))
            : [
                {
                  product_id: null,
                  description: '',
                  unit: 'UND',
                  quantity: 1,
                  unit_price: 0,
                  discount_pct: 0,
                  discount_amount: 0,
                  tax_pct: 0.18,
                },
              ],
      }
    : {
        customer_id: null,
        customer_tax_id: '',
        customer_phone: '',
        issue_date: new Date().toISOString().substring(0, 10),
        valid_until: new Date().toISOString().substring(0, 10),
        currency: 'PEN',
        exchange_rate: null,
        notes: '',
        terms: '',
        items: [
          {
            product_id: null,
            description: '',
            unit: 'UND',
            quantity: 1,
            unit_price: 0,
            discount_pct: 0,
            discount_amount: 0,
            tax_pct: 0.18,
          },
        ],
      }
)



/* =========================================================
   Símbolo de moneda según lo que se elija en el select
   PEN -> "S/"; USD -> "$"
   ========================================================= */
const currencySymbol = computed(() => (form.currency === 'USD' ? '$' : 'S/'))

/* =========================================================
   Calcula el total de una línea
   base = cantidad * precio
   desc = % de descuento
   total = (base - desc) + IGV
   ========================================================= */
function calcLineTotal(it: {
  quantity: number
  unit_price: number
  discount_pct: number
  tax_pct: number
}) {
  const base = (it.quantity || 0) * (it.unit_price || 0)
  const disc = base * ((it.discount_pct || 0) / 100)
  const after = base - disc
  const tax = after * (it.tax_pct || 0)
  return after + tax
}

/* =========================================================
   Cuando cambia el cliente, rellenamos RUC y teléfono
   ========================================================= */
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
      form.customer_tax_id = customer.tax_id ?? ''
      form.customer_phone = customer.phone ?? ''
    } else {
      form.customer_tax_id = ''
      form.customer_phone = ''
    }
  },
  { immediate: isEdit.value }
)


/* =========================================================
   Líneas: agregar / quitar
   ========================================================= */
function addLine() {
  form.items.push({
    product_id: null as number | null,
    description: '',
    unit: 'UND',
    quantity: 1,
    unit_price: 0,
    discount_pct: 0,
    discount_amount: 0,
    tax_pct: 0.18, // nueva línea con IGV 18%
  })
}

function removeLine(i: number) {
  form.items.splice(i, 1)
}

/* =========================================================
   Al elegir un producto, rellenar unit / price / tax_pct
   ========================================================= */
function onPickProduct(i: number) {
  const pid = form.items[i].product_id
  if (!pid) return

  const p = props.products.find((p) => p.id === pid)
  if (p) {
    form.items[i].description = p.name
    form.items[i].unit = p.unit
    form.items[i].unit_price = p.price
    form.items[i].tax_pct = p.tax_pct // IGV según el producto
  }
}

/* =========================================================
   Totales
   - subtotal  = suma de (cantidad * precio)
   - discount  = suma de descuentos por % de cada línea
   - tax       = IGV sobre el monto después de descuento
   - total     = subtotal - discount + tax
   ========================================================= */
const summary = computed(() => {
  let subtotal = 0
  let discount = 0
  let tax = 0

  for (const it of form.items) {
    const qty = it.quantity || 0
    const price = it.unit_price || 0
    const base = qty * price

    const disc = base * ((it.discount_pct || 0) / 100)
    const after = base - disc

    subtotal += base
    discount += disc

    tax += after * (it.tax_pct || 0)
  }

  const total = subtotal - discount + tax
  return { subtotal, discount, tax, total }
})

/* =========================================================
   Envío al backend
   ========================================================= */
const submit = () => {
  if (isEdit.value && props.quotation) {
    form.put(`/quotations/${props.quotation.id}`)
  } else {
    form.post('/quotations')
  }
}

</script>

<template>
  <!-- Barra superior con acciones (ya está dentro del contenedor de AppLayout) -->
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Nueva cotización</h1>
    <div class="flex gap-2">
      <Link
        href="/quotations"
        class="px-3 py-2 rounded-lg border bg-background hover:bg-muted/60 text-sm"
      >
        Cancelar
      </Link>
      <button
        class="px-4 py-2 rounded-lg bg-primary text-primary-foreground text-sm"
        :disabled="form.processing"
        @click="submit"
      >
        Guardar
      </button>
    </div>
  </div>

  <!-- HOJA de cotización tipo Alegra -->
  <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
    <!-- Encabezado -->
    <div class="px-8 pt-6 pb-4 flex items-start justify-between gap-6 border-b">
      <!-- Logo + nombre empresa -->
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

      <!-- Datos de documento -->
      <div class="text-right">
        <p class="text-[11px] uppercase tracking-wide text-muted-foreground">
          Cotización
        </p>
        <p class="text-2xl font-semibold leading-tight">
          No. <span class="align-middle text-base text-muted-foreground">—</span>
        </p>
        <p class="mt-1 text-xs text-muted-foreground">
          Emitida por: Dani
        </p>
      </div>
    </div>

    <!-- Cliente + Fechas -->
    <div class="px-8 py-4 grid md:grid-cols-2 gap-6 bg-slate-50/60 border-b">
      <!-- Cliente -->
      <div class="space-y-3">
        <div>
          <label class="block text-xs font-medium mb-1">Cliente *</label>

          <div class="flex items-end gap-2">
            <select
              v-model="form.customer_id"
              class="flex-1 border rounded-lg px-2 py-2 text-sm"
            >
              <option :value="null" disabled>Selecciona cliente…</option>
              <option
                v-for="c in props.customers"
                :key="c.id"
                :value="c.id"
              >
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

        <!-- Datos autocompletados -->
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

      <!-- Fechas y moneda -->
      <div class="grid sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium mb-1">Creación *</label>
          <input
            type="date"
            v-model="form.issue_date"
            class="w-full border rounded-lg px-2 py-2 text-sm"
          />
        </div>
        <div>
          <label class="block text-xs font-medium mb-1">Vencimiento</label>
          <input
            type="date"
            v-model="form.valid_until"
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

        <div v-if="form.currency === 'USD'">
          <label class="block text-xs font-medium mb-1">Tipo de cambio</label>
          <input
            type="number"
            step="0.0001"
            v-model.number="form.exchange_rate"
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
              <th class="p-2 w-24">Referencia</th>
              <th class="p-2 w-24">Precio un.</th>
              <th class="p-2 w-20">Desc %</th>
              <th class="p-2 w-20">Impuesto</th>
              <th class="p-2">Descripción</th>
              <th class="p-2 w-20">Cantidad</th>
              <th class="p-2 w-24 text-right">Total</th>
              <th class="p-2 w-8"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(it, i) in form.items"
              :key="i"
              class="border-b last:border-b-0"
            >
              <!-- Producto -->
              <td class="p-2">
                <select
                  v-model="it.product_id"
                  @change="onPickProduct(i)"
                  class="w-full border rounded-lg px-2 py-1 text-xs md:text-sm"
                >
                  <option :value="null">Buscar item facturable…</option>
                  <option
                    v-for="p in props.products"
                    :key="p.id"
                    :value="p.id"
                  >
                    {{ p.name }}
                  </option>
                </select>
              </td>

              <!-- Unidad -->
              <td class="p-2">
                <input
                  v-model="it.unit"
                  class="w-full border rounded-lg px-2 py-1 text-xs md:text-sm"
                  placeholder="UND"
                />
              </td>

              <!-- Precio unitario -->
              <td class="p-2">
                <input
                  type="number"
                  step="0.01"
                  v-model.number="it.unit_price"
                  class="w-full border rounded-lg px-2 py-1 text-xs md:text-sm text-right"
                />
              </td>

              <!-- Descuento % -->
              <td class="p-2">
                <input
                  type="number"
                  step="0.01"
                  v-model.number="it.discount_pct"
                  class="w-full border rounded-lg px-2 py-1 text-xs md:text-sm text-right"
                />
              </td>

              <!-- Impuesto por ítem -->
              <td class="p-2">
                <select
                  v-model.number="it.tax_pct"
                  class="w-28 border rounded-lg px-3 py-2 text-sm"
                >
                  <option :value="0">Ninguno (0%)</option>
                  <option :value="0">Exonerado (0%)</option>
                  <option :value="0.18">IGV (18%)</option>
                </select>
              </td>

              <!-- Descripción -->
              <td class="p-2">
                <input
                  v-model="it.description"
                  class="w-full border rounded-lg px-2 py-1 text-xs md:text-sm"
                  placeholder="Descripción del ítem"
                />
              </td>

              <!-- Cantidad -->
              <td class="p-2">
                <input
                  type="number"
                  step="0.001"
                  v-model.number="it.quantity"
                  class="w-full border rounded-lg px-2 py-1 text-xs md:text-sm text-right"
                />
              </td>

              <!-- Total línea -->
              <td class="p-2 text-right font-medium">
                {{ currencySymbol }} {{ calcLineTotal(it).toFixed(2) }}
              </td>

              <!-- Quitar línea -->
              <td class="p-2 text-right">
                <button
                  class="text-red-500 text-sm"
                  @click="removeLine(i)"
                >
                  ✕
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Botón agregar línea -->
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

    <!-- Pie: firma + términos + totales -->
    <div class="px-8 pb-8 pt-4 border-t grid md:grid-cols-3 gap-6">
      <!-- Firma y textos -->
      <div class="md:col-span-2 space-y-4">
        <div
          class="h-24 border-2 border-dashed rounded-xl flex flex-col items-center justify-center text-[11px] text-muted-foreground bg-slate-50"
        >
          <span class="font-medium">Utilizar mi firma</span>
          <span class="text-[10px]">178 x 51 píxeles</span>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium mb-1">
              Términos y condiciones
            </label>
            <textarea
              v-model="form.terms"
              rows="4"
              class="w-full border rounded-lg px-3 py-2 text-sm"
              placeholder="Visible en la impresión del documento"
            ></textarea>
          </div>
          <div>
            <label class="block text-xs font-medium mb-1">
              Notas
            </label>
            <textarea
              v-model="form.notes"
              rows="4"
              class="w-full border rounded-lg px-3 py-2 text-sm"
              placeholder="Visible en la impresión del documento"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Resumen de totales -->
      <div
        class="bg-slate-50 rounded-xl border px-4 py-4 flex flex-col gap-2 justify-between"
      >
        <div class="space-y-1 text-sm">
          <div class="flex justify-between">
            <span class="text-muted-foreground">Subtotal</span>
            <span>{{ currencySymbol }} {{ summary.subtotal.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Descuento</span>
            <span>- {{ currencySymbol }} {{ summary.discount.toFixed(2) }}</span>
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

  <!-- Modal cliente -->
  <CustomerModal
    :open="showCustomerModal"
    mode="create"
    @close="showCustomerModal = false"
    @saved="() => { showCustomerModal = false; router.reload({ only: ['customers'] }) }"
  />
</template>
