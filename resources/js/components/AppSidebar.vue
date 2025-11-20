<script setup lang="ts">
// Importamos componentes propios
import NavFooter from '@/components/NavFooter.vue'
import NavMain from '@/components/NavMain.vue'
import NavUser from '@/components/NavUser.vue'

// Importamos los componentes del sidebar desde shadcn/ui 
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar'

// Rutas del sistema (uso de helpers con Inertia)
import { dashboard } from '@/routes'

// Tipo NavItem para tipar los menús correctamente
import { type NavItem } from '@/types'

// Link de Inertia
import { Link } from '@inertiajs/vue3'

// Iconos lucide (SOLO los que usaremos)
import {
  LayoutDashboard, // Dashboard
  FileText,        // Cotizaciones
  Users,           // Clientes
  Package,         // Items
  Receipt,         // Ventas
  CreditCard,      // Pagos
  BookOpen,        // Footer: Docs
  Folder,          // Footer: Repo
} from 'lucide-vue-next'

// Logo del proyecto
import AppLogo from './AppLogo.vue'

/**
 * Menú principal
 */
const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: dashboard(),
    icon: LayoutDashboard,
  },
  {
    title: 'Cotizaciones',
    href: '/quotations',
    icon: FileText,
  },
  {
    title: 'Clientes',
    href: '/customers',
    icon: Users,
  },
  {
    title: 'Items',
    href: '/products',
    icon: Package,
  },
  {
    title: 'Ventas',
    href: '/sales',
    icon: Receipt,
  },
  {
    title: 'Pagos',
    href: '/payments',
    icon: CreditCard,
  },
]

/**
 * Menú del pie del sidebar (Footer)
 */
const footerNavItems: NavItem[] = [
  {
    title: 'Github Repo',
    href: 'https://github.com/laravel/vue-starter-kit',
    icon: Folder,
  },
  {
    title: 'Documentation',
    href: 'https://laravel.com/docs/starter-kits#vue',
    icon: BookOpen,
  },
]
</script>


<template>
    <!-- Sidebar principal -->
    <!-- collapsible="icon" hace que solo se muestre el icono cuando se colapsa -->
    <!-- variant="inset" agrega estilo tipo tarjeta dentro del layout -->
    <Sidebar collapsible="icon" variant="inset">

        <!-- Sección superior del sidebar -->
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>

                    <!-- Botón grande del logo -->
                    <SidebarMenuButton size="lg" as-child>

                        <!-- Link con el logo que manda al dashboard -->
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>

                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <!-- Contenido principal del menú -->
        <SidebarContent>
            <!-- Componente que dibuja el menú principal -->
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <!-- Sección inferior del sidebar -->
        <SidebarFooter>

            <!-- Footer con links secundarios -->
            <NavFooter :items="footerNavItems" />

            <!-- Información y menú del usuario -->
            <NavUser />
        </SidebarFooter>

    </Sidebar>

    <!-- Slot para renderizar el contenido de la página actual -->
    <slot />
</template>
