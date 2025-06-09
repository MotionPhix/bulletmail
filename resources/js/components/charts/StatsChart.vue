<script setup lang="ts">
import { ref, onMounted } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { useDark } from '@vueuse/core';

const isDark = useDark();

const props = defineProps({
  type: {
    type: String,
    default: 'area',
    validator: (value: string) => ['line', 'area', 'bar'].includes(value)
  },
  series: {
    type: Array,
    required: true
  },
  height: {
    type: [String, Number],
    default: 350
  },
  title: String,
  categories: {
    type: Array,
    default: () => []
  }
});

const chartOptions = ref({
  chart: {
    type: props.type,
    fontFamily: 'inherit',
    toolbar: {
      show: false
    },
    animations: {
      enabled: true
    },
    background: 'transparent'
  },
  theme: {
    mode: isDark.value ? 'dark' : 'light'
  },
  fill: {
    opacity: 0.3,
    type: 'gradient',
    gradient: {
      shade: 'dark',
      opacityFrom: 0.7,
      opacityTo: 0.3
    }
  },
  stroke: {
    width: 2,
    curve: 'smooth'
  },
  grid: {
    borderColor: isDark.value ? '#334155' : '#e2e8f0',
    strokeDashArray: 4
  },
  dataLabels: {
    enabled: false
  },
  xaxis: {
    categories: props.categories,
    tooltip: {
      enabled: false
    },
    axisBorder: {
      show: false
    }
  },
  yaxis: {
    labels: {
      formatter: (value: number) => {
        return value > 999 ? `${(value / 1000).toFixed(1)}k` : value;
      }
    }
  },
  tooltip: {
    theme: isDark.value ? 'dark' : 'light',
    y: {
      formatter: (value: number) => value.toString()
    }
  }
});
</script>

<template>
  <div class="w-full">
    <h3 v-if="title" class="text-lg font-semibold mb-4">{{ title }}</h3>
    <VueApexCharts
      :options="chartOptions"
      :series="series"
      :height="height"
    />
  </div>
</template>
