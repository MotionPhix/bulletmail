<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import StatsCard from '@/components/StatsCard.vue';
import ActivityFeed from '@/components/ActivityFeed.vue';

const props = defineProps({
  organization: Object,
  team: Object,
  isOwner: Boolean
});

const breadcrumbs = computed(() => [
  {
    title: props.organization.name,
    href: route('organizations.show', props.organization)
  },
  {
    title: props.team.name,
    href: route('teams.show', props.team)
  },
  {
    title: 'Dashboard',
    href: route('teams.show', props.team)
  }
]);
</script>

<template>
  <Head :title="`${team.name} Dashboard`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Team Overview -->
      <div>
        <h1 class="text-2xl font-semibold">{{ team.name }}</h1>
        <p class="text-muted-foreground">Team Overview</p>
      </div>

      <!-- Stats Grid -->
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <StatsCard title="Campaigns" :value="team.stats?.campaigns_count" />
        <StatsCard title="Subscribers" :value="team.stats?.subscribers_count" />
        <StatsCard title="Templates" :value="team.stats?.templates_count" />
        <StatsCard title="Team Members" :value="team.stats?.members_count" />
      </div>

      <!-- Activity Feed -->
      <div class="rounded-lg border">
        <div class="p-4 sm:p-6">
          <h2 class="text-lg font-semibold">Recent Activity</h2>
          <ActivityFeed :activities="team.recent_activities" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
