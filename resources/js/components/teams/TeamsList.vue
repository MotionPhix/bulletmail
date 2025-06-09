<script setup lang="ts">
import { Team } from '@/types';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';

interface Props {
  teams: Team[];
  currentTeam: Team;
}

const props = defineProps<Props>();

const switchTeam = (team: Team) => {
  router.put(route('teams.switch', team.uuid), {}, {
    preserveScroll: true,
    preserveState: true
  });
};
</script>

<template>
  <div class="space-y-4">
    <div
      v-for="team in teams"
      :key="team.id"
      class="flex items-center justify-between p-4 rounded-lg border"
      :class="{ 'bg-muted': team.id === currentTeam.id }"
    >
      <div>
        <h3 class="font-medium">{{ team.name }}</h3>
        <p class="text-sm text-muted-foreground">
          {{ team.users_count }} members ·
          {{ team.subscribers_count }} subscribers ·
          {{ team.campaigns_count }} campaigns
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Button
          v-if="team.id !== currentTeam.id"
          variant="outline"
          @click="switchTeam(team)"
        >
          Switch
        </Button>
        <Button
          v-if="team.id === currentTeam.id"
          variant="secondary"
          disabled
        >
          Current
        </Button>
      </div>
    </div>
  </div>
</template>
