<script setup lang="ts">
import { type Component, computed } from 'vue';
import {
  TrendingUp,
  TrendingDown,
  Minus
} from 'lucide-vue-next';
import {
  Card, CardContent
} from '@/components/ui/card';

const props = defineProps<{
  title: string;
  value: number | string;
  change?: number;
  trend?: 'up' | 'down' | 'neutral';
  icon?: Component;
  prefix?: string;
  suffix?: string;
  loading?: boolean;
}>();

const formattedValue = computed(() => {
  if (props.loading) return '-';
  if (typeof props.value === 'number' && props.value > 1000) {
    return `${(props.value / 1000).toFixed(1)}k`;
  }
  return props.value;
});

const trendColor = computed(() => {
  if (!props.trend) return 'text-muted-foreground';
  return {
    up: 'text-success',
    down: 'text-destructive',
    neutral: 'text-muted-foreground',
  }[props.trend];
});

const trendIcon = computed(() => {
  if (!props.trend) return null;
  return {
    'up': TrendingUp,
    'down': TrendingDown,
    'neutral': Minus
  }[props.trend];
});
</script>

<template>
  <Card class="relative overflow-hidden transition-all duration-200 hover:shadow-md">
    <CardContent>
      <div class="flex flex-col space-y-4">
        <div class="grid gap-y-4">
          <div
            class="relative h-12 w-12"
            v-if="icon">
            <div class="absolute inset-0 rounded-lg bg-primary/10"></div>
            <component
              :is="icon"
              class="absolute inset-0 m-2.5 h-7 w-7 text-primary"
            />
          </div>

          <div
            class="flex-1"
            v-if="icon">
          </div>

          <div>
            <p class="text-sm font-medium text-muted-foreground">{{ title }}</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight">
              <span v-if="prefix" class="text-xl text-muted-foreground/70">{{ prefix }}</span>
              {{ formattedValue }}
              <span v-if="suffix" class="text-xl text-muted-foreground/70">{{ suffix }}</span>
            </h2>
          </div>
        </div>

        <div v-if="change !== undefined" class="flex items-center space-x-2">
          <div
            :class="[
              'flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
              trend === 'up' ? 'bg-success/10 text-success' : '',
              trend === 'down' ? 'bg-destructive/10 text-destructive' : '',
              trend === 'neutral' ? 'bg-muted text-muted-foreground' : ''
            ]">
            <component
              v-if="trendIcon"
              :is="trendIcon"
              class="mr-1 h-3 w-3"
            />
            {{ change }}%
          </div>
          <span class="text-xs text-muted-foreground">vs. last period</span>
        </div>

        <div v-if="$slots.default" class="border-t pt-4 mt-2">
          <slot />
        </div>
      </div>

      <div
        v-if="icon"
        class="absolute -right-6 -top-6 h-24 w-24 opacity-[0.02]"
        aria-hidden="true">
        <component :is="icon" class="h-full w-full" />
      </div>
    </CardContent>
  </Card>
</template>
