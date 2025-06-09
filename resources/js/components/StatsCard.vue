<script setup lang="ts">
defineProps<{
  title: string
  value: number|string
  change?: number
  trend?: 'up'|'down'|'neutral'
}>();
</script>

<template>
  <div class="rounded-lg border bg-card p-4">
    <div class="space-y-2">
      <p class="text-sm text-muted-foreground">{{ title }}</p>
      <div class="flex items-center justify-between">
        <h2 class="text-3xl font-semibold">{{ value }}</h2>
        <div v-if="change !== null" class="flex items-center space-x-1">
          <span :class="{
            'text-success': trend === 'up',
            'text-destructive': trend === 'down'
          }">{{ change }}%</span>
        </div>
      </div>
    </div>
  </div>
</template>
