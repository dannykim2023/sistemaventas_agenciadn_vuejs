<script setup lang="ts">
/* =========================================================
   IMPORTS BÁSICOS (Layout, rutas, Inertia, Vue reactivity)
   ========================================================= */
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import { type BreadcrumbItem } from '@/types'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

/* =========================================================
   (OPCIONAL) TIPADO DE LA API GLOBAL window.$toast
   - Así no te marca error TypeScript al usar $toast
   ========================================================= */
declare global {
  interface Window {
    $toast?: {
      success: (m: string, ms?: number) => void
      error:   (m: string, ms?: number) => void
      warning: (m: string, ms?: number) => void
      info:    (m: string, ms?: number) => void
    }
  }
}

/* =========================================================
   PROPS QUE LLEGAN DESDE LARAVEL/INERTIA
   - products: paginación + data
   - filters: filtros activos (search)
   ========================================================= */
const props = defineProps<{
  products: { data:any[]; current_page:number; last_page:number; total:number; links:any[] },
  filters: { search?: string }
}>()

/* =========================================================
   MIGAS DE PAN (UI)
   ========================================================= */
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Ítems de venta', href: '/products' },
]

/* =========================================================
   LISTADO / FILTROS
   - search: texto de búsqueda
   - fetchList(): actualiza la URL y refresca lista
   - onEnter(): permite Enter para buscar
   ========================================================= */
const search = ref(props.filters?.search ?? '')

function fetchList() {
  router.get('/products', { search: search.value || undefined }, { preserveState:true, replace:true })
}
function onEnter(e: KeyboardEvent) { if (e.key === 'Enter') fetchList() }

/* =========================================================
   CREAR (MODAL + FORM)
   - showCreate: abre/cierra modal
   - formCreate: datos del producto
   - pickKind(): lógica UI para "Producto/Servicio/Combo"
   - priceTotalCreate: preview del total con IGV
   - submitCreate(): POST -> toast + cerrar modal
   ========================================================= */
const showCreate = ref(false)
const formCreate = useForm({
  kind: 'product' as 'product'|'service'|'combo', // solo UI
  sku: '', name: '', unit: 'UND', price: 0, tax_pct: 0.18, is_service: false,
})

function pickKind(k:'product'|'service'|'combo') {
  formCreate.kind = k
  if (k === 'service') { formCreate.is_service = true; if (formCreate.unit === 'UND') formCreate.unit = 'SERV' }
  else { formCreate.is_service = false; if (formCreate.unit === 'SERV') formCreate.unit = 'UND' }
}

const priceTotalCreate = computed(() =>
  (Number(formCreate.price||0) * (1 + Number(formCreate.tax_pct||0))).toFixed(2)
)

function submitCreate() {
  formCreate.post('/products', {
    onSuccess: () => {
      // ✅ Toast inmediato (además, si mandas flash desde el controller, también lo escuchará Toast.vue)
      window.$toast?.success('Producto creado correctamente')

      // Reset suave + cerrar modal
      formCreate.reset()
      formCreate.kind = 'product'
      formCreate.unit = 'UND'
      formCreate.tax_pct = 0.18
      showCreate.value = false

      // NO fetchList: Inertia normalmente rehidrata; si quieres forzar:
      // fetchList()
    },
  })
}

/* =========================================================
   VER / EDITAR / ELIMINAR
   - selected: fila seleccionada
   - openView(): abre modal VER
   - openEdit(): hidrata form con datos y abre EDITAR
   - priceTotalEdit: preview total en edición
   - submitEdit(): PUT -> toast + cerrar modal
   - openDelete(): abre confirmación
   - submitDelete(): DELETE -> toast + cerrar modal
   ========================================================= */
const selected = ref<any|null>(null)

const showView = ref(false)
function openView(p:any){ selected.value = p; showView.value = true }

const showEdit = ref(false)
const formEdit = useForm({ id:0, sku:'', name:'', unit:'UND', price:0, tax_pct:0.18, is_service:false })

function openEdit(p:any){
  selected.value = p
  formEdit.id = p.id
  formEdit.sku = p.sku || ''
  formEdit.name = p.name
  formEdit.unit = p.unit
  formEdit.price = p.price
  formEdit.tax_pct = p.tax_pct
  formEdit.is_service = !!p.is_service
  showEdit.value = true
}

const priceTotalEdit = computed(() =>
  (Number(formEdit.price||0) * (1 + Number(formEdit.tax_pct||0))).toFixed(2)
)

function submitEdit() {
  formEdit.put(`/products/${formEdit.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      window.$toast?.success('Producto actualizado')
      showEdit.value = false
      // fetchList()
    },
  })
}

const showDelete = ref(false)
function openDelete(p:any){ selected.value = p; showDelete.value = true }

function submitDelete() {
  if (!selected.value) return
  router.delete(`/products/${selected.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      window.$toast?.success('Producto eliminado')
      showDelete.value = false
      selected.value = null
      // fetchList()
    },
  })
}
</script>

<template>
  <Head title="Ítems de venta" />

  <!-- =====================================================
       LAYOUT BASE CON MIGAS DE PAN
       ===================================================== -->
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 md:p-6 space-y-4">

      <!-- ===================================================
           HEADER (Título + Botones)
           =================================================== -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold">Ítems de venta</h1>
          <p class="text-sm text-muted-foreground">Crea, edita y administra tus productos y servicios.</p>
        </div>
        <div class="flex gap-2">
          <button class="px-3 py-2 rounded-lg border hover:bg-muted/40">Más acciones</button>
          <button class="px-4 py-2 rounded-lg bg-primary text-white" @click="showCreate=true">
            + Nuevo ítem de venta
          </button>
        </div>
      </div>

      <!-- ===================================================
           CARD PRINCIPAL (Toolbar + Tabla + Paginación)
           =================================================== -->
      <div class="bg-white dark:bg-card rounded-xl shadow-sm border">

        <!-- Toolbar de búsqueda -->
        <div class="p-4 flex items-center gap-2">
          <div class="relative flex-1">
            <input
              v-model="search"
              @keyup.enter="onEnter"
              placeholder="Buscar producto/servicio…"
              class="w-full border rounded-lg px-10 py-2 dark:bg-background"
            />
            <svg class="w-5 h-5 absolute left-3 top-2.5 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="11" cy="11" r="7" stroke-width="2"/><path d="m21 21-4.3-4.3" stroke-width="2"/>
            </svg>
          </div>
          <button class="px-3 py-2 rounded-lg border hover:bg-muted/40" @click="fetchList">Filtrar</button>
        </div>

        <!-- Tabla de productos -->
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-muted-foreground border-y bg-muted/40">
              <tr>
                <th class="p-3 text-left">Nombre</th>
                <th class="p-3 text-left">SKU</th>
                <th class="p-3 text-left">Unidad</th>
                <th class="p-3 text-right">Precio base</th>
                <th class="p-3 text-left">Impuesto</th>
                <th class="p-3 text-left">Tipo</th>
                <th class="p-3 text-left">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in props.products.data" :key="p.id" class="border-b hover:bg-muted/30">
                <td class="p-3 font-medium">{{ p.name }}</td>
                <td class="p-3">{{ p.sku || '—' }}</td>
                <td class="p-3">{{ p.unit }}</td>
                <td class="p-3 text-right">S/ {{ Number(p.price).toFixed(2) }}</td>
                <td class="p-3">{{ (Number(p.tax_pct)*100).toFixed(0) }}%</td>
                <td class="p-3">
                  <span v-if="p.is_service" class="text-blue-700 bg-blue-50 px-2 py-1 rounded-full text-xs">Servicio</span>
                  <span v-else class="text-emerald-700 bg-emerald-50 px-2 py-1 rounded-full text-xs">Producto</span>
                </td>
                <td class="p-3">
                  <div class="flex items-center gap-3">
                    <button class="underline opacity-90 hover:opacity-100" @click="openView(p)">Ver</button>
                    <button class="underline opacity-90 hover:opacity-100" @click="openEdit(p)">Editar</button>
                    <button class="text-red-600 opacity-90 hover:opacity-100" @click="openDelete(p)">Eliminar</button>
                  </div>
                </td>
              </tr>
              <tr v-if="props.products.data.length===0">
                <td colspan="7" class="p-6 text-center text-muted-foreground">No hay ítems</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="p-4 flex items-center justify-between text-sm">
          <div>Mostrando {{ props.products.data.length }} de {{ props.products.total }}</div>
          <div class="flex items-center gap-2">
            <a
              v-for="(ln,i) in props.products.links"
              :key="i"
              :href="ln.url || '#'"
              :class="['px-3 py-1.5 rounded border', ln.active ? 'bg-primary text-white border-primary' : 'hover:bg-muted/40']"
              v-html="ln.label"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- =====================================================
         MODAL: CREAR
         ===================================================== -->
    <div v-if="showCreate" class="fixed inset-0 bg-black/40 grid place-items-center z-50">
      <div class="bg-white dark:bg-card w-full max-w-2xl rounded-xl shadow-2xl border p-6 space-y-5">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Formulario básico de productos</h3>
          <button class="opacity-70 hover:opacity-100" @click="showCreate=false">
            <svg class="w-6 h-6" viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-width="2" d="M6 6l12 12M6 18L18 6"/></svg>
          </button>
        </div>

        <!-- Tabs visuales: Producto / Servicio / Combo -->
        <div class="grid grid-cols-3 gap-2">
          <button :class="formCreate.kind==='product' ? 'bg-primary/10 border-primary text-primary' : 'hover:bg-muted/40'"
                  class="px-4 py-2 rounded-lg border font-medium" @click="pickKind('product')">Producto</button>
          <button :class="formCreate.kind==='service' ? 'bg-primary/10 border-primary text-primary' : 'hover:bg-muted/40'"
                  class="px-4 py-2 rounded-lg border font-medium" @click="pickKind('service')">Servicio</button>
          <button :class="formCreate.kind==='combo' ? 'bg-primary/10 border-primary text-primary' : 'hover:bg-muted/40'"
                  class="px-4 py-2 rounded-lg border font-medium" @click="pickKind('combo')" title="Visual; guarda como producto">Combo</button>
        </div>

        <!-- Campos -->
        <div class="grid md:grid-cols-2 gap-3">
          <div class="col-span-2">
            <label class="block text-sm font-medium mb-1">Nombre *</label>
            <input v-model="formCreate.name" class="w-full border rounded-lg px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Unidad *</label>
            <select v-model="formCreate.unit" class="w-full border rounded-lg px-2 py-2">
              <option value="UND">UND</option><option value="SERV">SERV</option><option value="HORA">HORA</option><option value="KG">KG</option><option value="LT">LT</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">SKU</label>
            <input v-model="formCreate.sku" class="w-full border rounded-lg px-3 py-2" placeholder="Opcional"/>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Precio base *</label>
            <input type="number" step="0.01" v-model.number="formCreate.price" class="w-full border rounded-lg px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Impuesto</label>
            <select v-model.number="formCreate.tax_pct" class="w-full border rounded-lg px-2 py-2">
              <option :value="0">Ninguno (0%)</option>
              <option :value="0.18">IGV 18%</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Precio total (preview)</label>
            <input :value="`S/ ${priceTotalCreate}`" class="w-full border rounded-lg px-3 py-2 bg-muted/40" disabled />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Tipo guardado</label>
            <input :value="formCreate.is_service ? 'Servicio' : 'Producto'" class="w-full border rounded-lg px-3 py-2 bg-muted/40" disabled />
          </div>
        </div>

        <!-- Footer modal -->
        <div class="flex items-center justify-between pt-3 border-t mt-1">
          <button class="text-sm underline opacity-80 hover:opacity-100">Ir al formulario avanzado</button>
          <div class="flex gap-2">
            <button class="px-3 py-2 rounded border" @click="showCreate=false">Cancelar</button>
            <button class="px-4 py-2 rounded bg-primary text-white" :disabled="formCreate.processing" @click="submitCreate">Crear producto</button>
          </div>
        </div>
      </div>
    </div>

    <!-- =====================================================
         MODAL: VER
         ===================================================== -->
    <div v-if="showView && selected" class="fixed inset-0 bg-black/30 grid place-items-center z-50">
      <div class="bg-white dark:bg-card w-full max-w-lg rounded-xl shadow-xl border p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Detalle del ítem</h3>
          <button class="opacity-70 hover:opacity-100" @click="showView=false">
            <svg class="w-6 h-6" viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-width="2" d="M6 6l12 12M6 18L18 6"/></svg>
          </button>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><div class="text-xs text-muted-foreground">Nombre</div><div class="font-medium">{{ selected.name }}</div></div>
          <div><div class="text-xs text-muted-foreground">SKU</div><div>{{ selected.sku || '—' }}</div></div>
          <div><div class="text-xs text-muted-foreground">Unidad</div><div>{{ selected.unit }}</div></div>
          <div><div class="text-xs text-muted-foreground">Impuesto</div><div>{{ (Number(selected.tax_pct)*100).toFixed(0) }}%</div></div>
          <div><div class="text-xs text-muted-foreground">Precio base</div><div>S/ {{ Number(selected.price).toFixed(2) }}</div></div>
          <div><div class="text-xs text-muted-foreground">Tipo</div><div>{{ selected.is_service ? 'Servicio' : 'Producto' }}</div></div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button class="px-3 py-2 rounded border" @click="showView=false">Cerrar</button>
          <button class="px-3 py-2 rounded border" @click="openEdit(selected)">Editar</button>
          <button class="px-3 py-2 rounded bg-red-600 text-white" @click="openDelete(selected)">Eliminar</button>
        </div>
      </div>
    </div>

    <!-- =====================================================
         MODAL: EDITAR
         ===================================================== -->
    <div v-if="showEdit" class="fixed inset-0 bg-black/40 grid place-items-center z-50">
      <div class="bg-white dark:bg-card w-full max-w-2xl rounded-xl shadow-2xl border p-6 space-y-5">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Editar producto</h3>
          <button class="opacity-70 hover:opacity-100" @click="showEdit=false">
            <svg class="w-6 h-6" viewBox="0 0 24 24" stroke="currentColor" fill="none"><path stroke-width="2" d="M6 6l12 12M6 18L18 6"/></svg>
          </button>
        </div>

        <div class="grid md:grid-cols-2 gap-3">
          <div class="col-span-2">
            <label class="block text-sm font-medium mb-1">Nombre *</label>
            <input v-model="formEdit.name" class="w-full border rounded-lg px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Unidad *</label>
            <select v-model="formEdit.unit" class="w-full border rounded-lg px-2 py-2">
              <option value="UND">UND</option><option value="SERV">SERV</option><option value="HORA">HORA</option><option value="KG">KG</option><option value="LT">LT</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">SKU</label>
            <input v-model="formEdit.sku" class="w-full border rounded-lg px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Precio base *</label>
            <input type="number" step="0.01" v-model.number="formEdit.price" class="w-full border rounded-lg px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Impuesto</label>
            <select v-model.number="formEdit.tax_pct" class="w-full border rounded-lg px-2 py-2">
              <option :value="0">Ninguno (0%)</option>
              <option :value="0.18">IGV 18%</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Precio total (preview)</label>
            <input :value="`S/ ${priceTotalEdit}`" class="w-full border rounded-lg px-3 py-2 bg-muted/40" disabled />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Tipo guardado</label>
            <select v-model="formEdit.is_service" class="w-full border rounded-lg px-2 py-2">
              <option :value="false">Producto</option>
              <option :value="true">Servicio</option>
            </select>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t mt-1">
          <button class="px-3 py-2 rounded border" @click="showEdit=false">Cancelar</button>
          <button class="px-4 py-2 rounded bg-primary text-white" :disabled="formEdit.processing" @click="submitEdit">Actualizar</button>
        </div>
      </div>
    </div>

    <!-- =====================================================
         MODAL: ELIMINAR
         ===================================================== -->
    <div v-if="showDelete" class="fixed inset-0 bg-black/40 grid place-items-center z-50">
      <div class="bg-white dark:bg-card w-full max-w-md rounded-xl shadow-xl border p-6 space-y-4">
        <h3 class="text-lg font-semibold">Eliminar ítem</h3>
        <p class="text-sm text-muted-foreground">
          ¿Seguro que deseas eliminar <b>{{ selected?.name }}</b>? Esta acción no se puede deshacer.
        </p>
        <div class="flex justify-end gap-2">
          <button class="px-3 py-2 rounded border" @click="showDelete=false">Cancelar</button>
          <button class="px-4 py-2 rounded bg-red-600 text-white" @click="submitDelete">Eliminar</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
