<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'

/*
|--------------------------------------------------------------------------
| PROPS
|--------------------------------------------------------------------------
*/
const props = defineProps<{
  open: boolean
  mode?: 'create' | 'edit'
  customer?: any | null
}>()

const emit = defineEmits(['close', 'saved', 'updated'])
const isEdit = () => props.mode === 'edit'

/*
|--------------------------------------------------------------------------
| FORMULARIO + VALIDACIONES
|--------------------------------------------------------------------------
*/
const form = useForm({
  id: null,
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

function validate() {
  // limpiamos errores previos (también los del backend)
  form.clearErrors()

  // === NÚMERO DE DOCUMENTO ===
  if (!form.document_number.trim()) {
    form.setError('document_number', 'Ingrese el número de identificación')
  } else {
    // DNI debe ser 8 dígitos
    if (form.document_type === 'DNI' && !/^\d{8}$/.test(form.document_number)) {
      form.setError(
        'document_number',
        'El DNI debe tener 8 dígitos numéricos'
      )
    }

    // RUC debe ser 11 dígitos
    if (form.document_type === 'RUC' && !/^\d{11}$/.test(form.document_number)) {
      form.setError(
        'document_number',
        'El RUC debe tener 11 dígitos numéricos'
      )
    }
  }

  // === NOMBRE / RAZÓN SOCIAL ===
  if (!form.name.trim()) {
    form.setError('name', 'El nombre es obligatorio')
  }

  // === CORREO ===
  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    form.setError('email', 'Correo inválido')
  }

  // si hay errores, form.hasErrors será true
  return !form.hasErrors
}


/*
|--------------------------------------------------------------------------
| AUTOCOMPLETE NAVEGADOR (como Alegra)
|--------------------------------------------------------------------------
| autocomplete="email" autocomplete="name" etc.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| CARGAR DATOS EN MODO EDITAR
|--------------------------------------------------------------------------
*/
watch(
  () => props.customer,
  (c) => {
    if (!c) return
    form.id = c.id
    form.type = c.type ?? 'company'
    form.document_type = c.document_type ?? 'RUC'
    form.document_number = c.document_number ?? ''
    form.name = c.name ?? ''
    form.email = c.email ?? ''
    form.phone = c.phone ?? ''
    form.address = c.address ?? ''
    form.notes = c.notes ?? ''
    form.is_active = !!c.is_active
  },
  { immediate: true }
)

/*
|--------------------------------------------------------------------------
| CREATE
|--------------------------------------------------------------------------
*/
function submitCreate() {
  if (!validate()) return

  form.post('/customers', {
    onSuccess: () => {
      emit('saved')
      form.reset()
    },
  })
}

/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/
function submitUpdate() {
  if (!validate()) return
  if (!form.id) return

  form.put(`/customers/${form.id}`, {
    onSuccess: () => {
      emit('updated')
    },
  })
}

function closeModal() {
  emit('close')
}
</script>

<template>
  <div
    v-if="props.open"
    class="fixed inset-0 bg-black/40 z-50 grid place-items-center"
    @click.self="closeModal"
  >
    <div
      class="bg-white w-full max-w-2xl rounded-xl shadow-xl border p-6 space-y-5 animate-fade"
      :class="{ 'animate-shake': form.hasErrors }"
    >
      <!-- HEADER -->
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold">
          {{ isEdit() ? 'Editar contacto' : 'Nuevo contacto' }}
        </h3>
        <button class="opacity-70 hover:opacity-100" @click="closeModal">✕</button>
      </div>

      <!-- Tipo -->
      <div class="grid grid-cols-2 gap-2">
        <button
          class="px-4 py-2 rounded-lg border font-medium"
          :class="form.type==='person' ? 'bg-primary/10 border-primary text-primary' : 'hover:bg-muted/40'"
          @click="form.type='person'; form.document_type='DNI'; form.document_number=''"
        >
          Persona
        </button>
        <button
          class="px-4 py-2 rounded-lg border font-medium"
          :class="form.type==='company' ? 'bg-primary/10 border-primary text-primary' : 'hover:bg-muted/40'"
          @click="form.type='company'; form.document_type='RUC'; form.document_number=''"
        >
          Empresa
        </button>
      </div>

      <!-- FORM -->
      <div class="grid md:grid-cols-2 gap-3">
        <div>
        <label class="block text-sm font-medium mb-1">Tipo de identificación *</label>
        <select
          v-model="form.document_type"
          class="w-full border rounded-lg px-2 py-2"
          :class="form.errors.document_type ? 'border-red-500' : ''"
        >
          <option value="RUC">RUC</option>
          <option value="DNI">DNI</option>
          <!-- <option value="CE">CE</option>
          <option value="PAS">Pasaporte</option> -->
        </select>
        <p v-if="form.errors.document_type" class="text-xs text-red-600">
          {{ form.errors.document_type }}
        </p>
      </div>

        <div>
          <label class="block text-sm font-medium mb-1">Número *</label>
          <input
            v-model="form.document_number"
            class="w-full border rounded-lg px-3 py-2"
            :class="form.errors.document_number ? 'border-red-500' : ''"
            autocomplete="off"
          />
          <p v-if="form.errors.document_number" class="text-xs text-red-600">
            {{ form.errors.document_number }}
          </p>
        </div>


        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Razón social / Nombre *</label>
          <input
            v-model="form.name"
            class="w-full border rounded-lg px-3 py-2"
            :class="form.errors.name ? 'border-red-500' : ''"
            autocomplete="name"
          />
          <p v-if="form.errors.name" class="text-xs text-red-600">
            {{ form.errors.name }}
          </p>
        </div>


        <div>
          <label class="block text-sm font-medium mb-1">Correo</label>
          <input
            v-model="form.email"
            class="w-full border rounded-lg px-3 py-2"
            autocomplete="email"
            :class="form.errors.email ? 'border-red-500' : ''"
          />
          <p v-if="form.errors.email" class="text-xs text-red-600">
            {{ form.errors.email }}
          </p>
        </div>


        <div>
          <label class="block text-sm font-medium mb-1">Teléfono</label>
          <input v-model="form.phone" class="w-full border rounded-lg px-3 py-2" autocomplete="tel" />
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Dirección</label>
          <input v-model="form.address" class="w-full border rounded-lg px-3 py-2" autocomplete="street-address" />
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Notas</label>
          <textarea v-model="form.notes" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
        </div>
      </div>

      <!-- FOOTER -->
      <div class="flex items-center justify-end gap-2 pt-3 border-t mt-1">
        <button class="px-3 py-2 rounded border" @click="closeModal">Cancelar</button>

        <button
          v-if="!isEdit()"
          class="px-4 py-2 rounded bg-primary text-white"
          @click="submitCreate"
        >
          Crear contacto
        </button>

        <button
          v-if="isEdit()"
          class="px-4 py-2 rounded bg-primary text-white"
          @click="submitUpdate"
        >
          Actualizar
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* animación cuando hay errores */
@keyframes shake {
  0% { transform: translateX(0px); }
  25% { transform: translateX(-4px); }
  50% { transform: translateX(4px); }
  75% { transform: translateX(-4px); }
  100% { transform: translateX(0px); }
}
.animate-shake {
  animation: shake 0.25s;
}

/* animación suave al aparecer */
@keyframes fade {
  from { opacity: 0; transform: scale(.98); }
  to   { opacity: 1; transform: scale(1); }
}
.animate-fade {
  animation: fade .15s ease-out;
}
</style>
