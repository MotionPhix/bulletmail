<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';

defineProps({
  team: Object,
  isCurrent: Boolean,
  isOwner: Boolean
});
</script>

<template>
  <div class="relative rounded-lg border p-4 hover:shadow-sm transition-shadow">
    <div class="absolute top-2 right-2" v-if="isCurrent">
      <span class="text-xs bg-primary/10 text-primary px-2 py-1 rounded-full">
        Current
      </span>
    </div>

    <h3 class="font-medium text-lg">{{ team.name }}</h3>
    <p class="text-sm text-muted-foreground">{{ team.members_count }} members</p>

    <div class="mt-4 space-x-2">
      <Button as="Link" :href="route('teams.show', team)" variant="outline">
        View Dashboard
      </Button>
      <Button
        v-if="!isCurrent"
        as="Link"
        :href="route('teams.switch', team)"
        method="put"
        variant="ghost"
      >
        Switch to Team
      </Button>
    </div>
  </div>
</template>
