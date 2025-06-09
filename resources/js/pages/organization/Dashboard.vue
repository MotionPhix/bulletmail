<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import TeamCard from '@/components/TeamCard.vue';
import { Button } from '@/components/ui/button';

const props = defineProps({
  organization: Object,
  teams: Array,
  currentTeam: Object,
  isOwner: Boolean
});

const breadcrumbs = computed(() => [
  {
    title: props.organization.name,
    href: route('organizations.show', props.organization)
  },
  {
    title: 'Dashboard',
    href: route('organizations.show', props.organization)
  }
]);
</script>

<template>
  <Head :title="`${organization.name} Dashboard`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6 max-w-4xl">
      <!-- Organization Overview -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold">{{ organization.name }}</h1>
          <p class="text-muted-foreground">Organization Overview</p>
        </div>

        <div v-if="isOwner">
          <Button as="Link" :href="route('teams.create')" class="ml-4">
            Create Team
          </Button>
        </div>
      </div>

      <!-- Teams Grid -->
      <div class="grid gap-6 sm:grid-cols-2">
        <TeamCard
          v-for="team in teams"
          :key="team.id"
          :team="team"
          :is-current="currentTeam?.id === team.id"
          :is-owner="isOwner"
        />
      </div>
    </div>
  </AppLayout>
</template>
