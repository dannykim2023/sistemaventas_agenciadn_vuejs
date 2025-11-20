<script setup lang="ts">
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar'
import { urlIsActive } from '@/lib/utils'
import type { NavItem } from '@/types'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps<{
  items: NavItem[]
}>()

const page = usePage()
</script>

<template>
  <SidebarGroup class="px-2 py-0">
    <SidebarGroupLabel>Platform</SidebarGroupLabel>

    <SidebarMenu>
      <SidebarMenuItem
        v-for="item in props.items"
        :key="item.title"
      >
        <SidebarMenuButton
          as-child
          :is-active="urlIsActive(item.href, page.url)"
          :tooltip="item.title"
          class="relative w-full"
        >
          <!-- Link del menú -->
          <Link
            :href="item.href"
            class="relative flex items-center gap-2 w-full px-3 py-2 text-sm rounded-lg"
            :class="
              urlIsActive(item.href, page.url)
                ? 'bg-muted text-foreground'
                : 'text-muted-foreground hover:bg-muted/60'
            "
          >
            <!-- Barrita turquesa tipo Alegra -->
            <span
              v-if="urlIsActive(item.href, page.url)"
              class="absolute left-0 top-1 bottom-1 w-[3px] rounded-full bg-primary"
            ></span>

            <!-- Icono -->
            <component
              :is="item.icon"
              class="h-4 w-4"
            />

            <!-- Texto -->
            <span class="truncate">
              {{ item.title }}
            </span>
          </Link>
        </SidebarMenuButton>
      </SidebarMenuItem>
    </SidebarMenu>
  </SidebarGroup>
</template>
