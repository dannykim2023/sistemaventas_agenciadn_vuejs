<script setup>
import {
  Chart as ChartJS,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend
} from 'chart.js'

import { Line } from 'vue-chartjs'

ChartJS.register(
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend
)

const props = defineProps({
  labels: Array,
  values: Array,
  title: String,
})
</script>

<template>
  <div class="bg-white p-4 rounded-xl shadow h-[350px]">
    <h2 class="font-bold text-gray-700 mb-3">{{ title }}</h2>

    <Line
      :data="{
        labels: labels,
        datasets: [
          {
            label: title,
            data: values,
            borderWidth: 2,
            tension: 0.4,
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99,102,241,.15)',
          }
        ]
      }"
      :options="{ responsive: true, maintainAspectRatio: false }"
    />
  </div>
</template>
