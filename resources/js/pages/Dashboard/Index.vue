<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import SalesChart from '@/components/Charts/SalesChart.vue';

const props = defineProps<{
    salesChart: {
        labels: string[];
        values: number[];
        total: number;
        period: string;
    };
    stats: {
        accountsReceivable: {
            total: number;
            vigente_total: number;
            vencida_total: number;
            vigente_docs: number;
            vencida_docs: number;
        };
        accountsPayable: {
            total: number;
            vigente_total: number;
            vencida_total: number;
            vigente_docs: number;
            vencida_docs: number;
        };
        totalSalesMonth: number;
        totalTaxMonth: number;
        taxableBaseMonth: number;
        productsSoldMonth: number;
        customersWithSalesMonth: number;
        returnsTotal: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
        minimumFractionDigits: 2,
    }).format(value || 0);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Título principal del módulo -->
            <h1 class="mb-1 text-xl font-semibold text-gray-900 dark:text-gray-50">
                Resumen del negocio
            </h1>
            <p class="mb-3 text-xs text-gray-500">
                Vista general de tus ventas, impuestos y clientes en el período actual.
            </p>

            <!-- GRID 4 COLUMNAS (estilo Alegra) -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-4">
                <!-- Cuentas por cobrar (col-span-2) -->
                <div
                    class="md:col-span-2 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-sidebar"
                >
                    <p class="text-xs font-semibold uppercase text-gray-500">
                        Cuentas por cobrar
                    </p>
                    <p
                        class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
                    >
                        {{ formatCurrency(stats.accountsReceivable.total) }}
                    </p>

                    <div
                        class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-gray-200"
                    >
                        <div
                            class="h-full rounded-full bg-red-500 transition-all"
                            :style="{
                                width:
                                    stats.accountsReceivable.total > 0
                                        ? (stats.accountsReceivable.vencida_total /
                                              stats.accountsReceivable.total) *
                                              100 +
                                          '%'
                                        : '0%',
                            }"
                        ></div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <p
                                class="flex items-center gap-1 text-[11px] font-medium text-gray-500"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-emerald-500"
                                ></span>
                                Vigentes
                            </p>
                            <p
                                class="text-sm font-semibold text-gray-900 dark:text-gray-50"
                            >
                                {{ formatCurrency(stats.accountsReceivable.vigente_total) }}
                            </p>
                            <p class="text-[11px] text-gray-500">
                                {{ stats.accountsReceivable.vigente_docs }}
                                documentos
                            </p>
                        </div>
                        <div>
                            <p
                                class="flex items-center gap-1 text-[11px] font-medium text-gray-500"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-red-500"
                                ></span>
                                Vencidas
                            </p>
                            <p
                                class="text-sm font-semibold text-gray-900 dark:text-gray-50"
                            >
                                {{ formatCurrency(stats.accountsReceivable.vencida_total) }}
                            </p>
                            <p class="text-[11px] text-gray-500">
                                {{ stats.accountsReceivable.vencida_docs }}
                                documentos
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Cuentas por pagar (col-span-2) -->
                <div
                    class="md:col-span-2 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-sidebar"
                >
                    <p class="text-xs font-semibold uppercase text-gray-500">
                        Cuentas por pagar
                    </p>
                    <p
                        class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
                    >
                        {{ formatCurrency(stats.accountsPayable.total) }}
                    </p>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <p
                                class="flex items-center gap-1 text-[11px] font-medium text-gray-500"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-emerald-500"
                                ></span>
                                Vigentes
                            </p>
                            <p
                                class="text-sm font-semibold text-gray-900 dark:text-gray-50"
                            >
                                {{ formatCurrency(stats.accountsPayable.vigente_total) }}
                            </p>
                            <p class="text-[11px] text-gray-500">
                                {{ stats.accountsPayable.vigente_docs }}
                                documentos
                            </p>
                        </div>
                        <div>
                            <p
                                class="flex items-center gap-1 text-[11px] font-medium text-gray-500"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-red-500"
                                ></span>
                                Vencidas
                            </p>
                            <p
                                class="text-sm font-semibold text-gray-900 dark:text-gray-50"
                            >
                                {{ formatCurrency(stats.accountsPayable.vencida_total) }}
                            </p>
                            <p class="text-[11px] text-gray-500">
                                {{ stats.accountsPayable.vencida_docs }}
                                documentos
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Fila 2: Impuestos / Productos / Devoluciones / Clientes -->
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-sidebar"
                >
                    <p class="text-xs font-semibold uppercase text-gray-500">
                        Impuestos en venta
                    </p>
                    <p
                        class="mt-1 text-xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
                    >
                        {{ formatCurrency(stats.totalTaxMonth) }}
                    </p>

                    <div
                        v-if="stats.totalTaxMonth > 0"
                        class="mt-2 space-y-1 text-[11px] text-gray-600 dark:text-gray-300"
                    >
                        <p>
                            IGV generado (18%):
                            <span class="font-semibold">
                                {{ formatCurrency(stats.totalTaxMonth) }}
                            </span>
                        </p>
                        <p>
                            Base imponible afectas:
                            <span class="font-semibold">
                                {{ formatCurrency(stats.taxableBaseMonth) }}
                            </span>
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-sidebar"
                >
                    <p class="text-xs font-semibold uppercase text-gray-500">
                        Productos vendidos
                    </p>
                    <p
                        class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
                    >
                        {{ stats.productsSoldMonth }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-sidebar"
                >
                    <p class="text-xs font-semibold uppercase text-gray-500">
                        Devoluciones de clientes
                    </p>
                    <p
                        class="mt-1 text-xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
                    >
                        {{ formatCurrency(stats.returnsTotal) }}
                    </p>
                    <p class="mt-1 text-[11px] text-gray-500">
                        Incluye impuestos
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-sidebar"
                >
                    <p
                        class="text-xs font-semibold uppercase text-blue-600 underline"
                    >
                        Clientes con ventas
                    </p>
                    <p
                        class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
                    >
                        {{ stats.customersWithSalesMonth }}
                    </p>
                </div>
            </div>

            <!-- PANEL: GRÁFICO TOTAL DE VENTAS -->
            <div
                class="relative mt-2 flex-1 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:min-h-min dark:border-sidebar-border dark:bg-sidebar"
            >
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p
                            class="text-sm font-semibold text-gray-800 dark:text-gray-50"
                        >
                            Total de ventas
                        </p>
                        <p class="text-xs text-gray-500">
                            La gráfica muestra el valor de tus ventas con
                            impuestos incluidos.
                        </p>
                    </div>
                    <div
                        class="text-right text-sm font-semibold text-gray-800 dark:text-gray-50"
                    >
                        {{ formatCurrency(salesChart.total) }}
                    </div>
                </div>

                <div class="h-[320px]">
                    <SalesChart
                        :labels="salesChart.labels"
                        :values="salesChart.values"
                        title="Ventas del mes"
                    />
                </div>

                <p
                    class="mt-2 text-center text-[11px] text-gray-500"
                >
                    {{ salesChart.period }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>
