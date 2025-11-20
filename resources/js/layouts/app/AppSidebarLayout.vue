<script setup lang="ts">
import AppContent from '@/components/AppContent.vue'
import AppShell from '@/components/AppShell.vue'
import AppSidebar from '@/components/AppSidebar.vue'
import AppSidebarHeader from '@/components/AppSidebarHeader.vue'
import type { BreadcrumbItemType } from '@/types'

interface Props {
  breadcrumbs?: BreadcrumbItemType[]
}
withDefaults(defineProps<Props>(), { breadcrumbs: () => [] })
</script>

<template>
  <AppShell variant="sidebar">
    <AppSidebar />

    <!-- Fondo tipo Alegra -->
    <AppContent
      variant="sidebar"
      class="min-h-screen overflow-x-hidden flex flex-col"
      :style="{ backgroundColor: '#F4F5FB' }"
    >
      <!-- ===== Header (breadcrumbs) con MISMO ancho de caja ===== -->
      <div class="flex justify-center px-4 md:px-6 pt-4 md:pt-6">
        <div class="w-full max-w-7xl">


        <!--  <AppSidebarHeader :breadcrumbs="breadcrumbs" /> -->
          
        </div>
      </div>

      <!-- ===== Contenido (CRUD) centrado con el MISMO ancho ===== -->
      <div class="flex-1 flex justify-center px-4 md:px-6 pb-8">
        <div class="w-full max-w-7xl space-y-4">
          <slot />
        </div>
      </div>
    </AppContent>
  </AppShell>
</template>
