<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import Alert from '@/components/ui/alert/Alert.vue'

// ✅ Tipos claros
type AlertVariant = 'default' | 'destructive'
type Kind = 'success' | 'error' | 'warning' | 'info'

const page = usePage<any>()
const open = ref(false)
const text = ref('')
const kind = ref<Kind>('success')
let timer: number | undefined

function show(msg: string, k: Kind = 'success', ms = 3200) {
  text.value = msg
  kind.value = k
  open.value = true
  clearTimeout(timer)
  timer = window.setTimeout(() => (open.value = false), ms)
}

// ✅ Mapea el tipo del Alert
const alertVariant = computed<AlertVariant>(() =>
  kind.value === 'error' ? 'destructive' : 'default'
)

// ✅ Colores de borde personalizados
const toneClass = computed(() => {
  switch (kind.value) {
    case 'success': return 'border-emerald-300'
    case 'warning': return 'border-amber-300'
    case 'info':    return 'border-blue-300'
    case 'error':   return 'border-destructive/50'
    default:        return ''
  }
})

// ✅ Escucha flashes de Inertia
watch(() => page.props.flash, (f) => {
  if (f?.success) show(f.success, 'success')
  else if (f?.error) show(f.error, 'error')
  else if (f?.warning) show(f.warning, 'warning')
  else if (f?.info) show(f.info, 'info')
}, { immediate: true })

// ✅ API global opcional
// @ts-ignore
window.$toast = {
  success: (m: string, ms?: number) => show(m, 'success', ms),
  error:   (m: string, ms?: number) => show(m, 'error', ms),
  warning: (m: string, ms?: number) => show(m, 'warning', ms),
  info:    (m: string, ms?: number) => show(m, 'info', ms),
}
</script>

<template>
  <transition name="toast-fade">
    <div v-if="open" class="fixed bottom-6 right-6 z-[9999] max-w-sm min-w-[260px] pointer-events-none">
      <Alert :variant="alertVariant" :class="['relative shadow-lg pr-10 pointer-events-auto bg-white', toneClass]">
        <div class="text-sm leading-5">{{ text }}</div>
        <button
          class="absolute top-2 right-2 rounded p-1 opacity-70 hover:opacity-100"
          @click="open=false"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" stroke="currentColor" fill="none">
            <path stroke-width="2" d="M6 6l12 12M6 18L18 6"/>
          </svg>
        </button>
      </Alert>
    </div>
  </transition>
</template>

<style scoped>
.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: opacity .2s, transform .2s;
}
.toast-fade-enter-from,
.toast-fade-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
