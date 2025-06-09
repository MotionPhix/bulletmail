<script setup lang="ts">
import StatsCard from '@/components/StatsCard.vue';
import TeamCard from '@/components/TeamCard.vue';
import StatsChart from '@/components/charts/StatsChart.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
  AlertTriangle,
  Mail,
  MailOpen,
  Megaphone,
  MousePointer,
  SendHorizonal,
  UserCircle,
  Users
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
  organization: Object,
  teams: Array,
  currentTeam: Object,
  isOwner: Boolean,
  stats: Object,
  campaignStats: Object,
  subscriberGrowth: Object,
});

const breadcrumbs = computed(() => [
  {
    title: props.organization.name,
    href: route('organizations.show', props.organization),
  },
  {
    title: 'Dashboard',
    href: route('organizations.show', props.organization),
  },
]);

const subscriberGrowthData = computed(() => {
  const months = Object.keys(props.subscriberGrowth || {});
  const values = Object.values(props.subscriberGrowth || {});

  return {
    categories: months,
    series: [
      {
        name: 'Subscribers',
        data: values,
      },
    ],
  };
});

const campaignPerformanceData = computed(() => ({
  series: [
    {
      name: 'Email Performance',
      data: [props.campaignStats.total_sent, props.campaignStats.total_opened, props.campaignStats.total_clicked, props.campaignStats.total_bounced],
    },
  ],
  categories: ['Sent', 'Opened', 'Clicked', 'Bounced'],
}));
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
          <Button as="Link" :href="route('teams.create')" class="ml-4"> Create Team </Button>
        </div>
      </div>

      <!-- Organization Stats -->
      <div class="grid gap-4 sm:grid-cols-2">
        <StatsCard title="Teams" :value="stats.teams_count" :icon="Users" />
        <StatsCard title="Total Members" :value="stats.members_count" :icon="UserCircle" />
        <StatsCard title="Total Subscribers" :value="stats.subscribers_count" :icon="Mail" />
        <StatsCard title="Total Campaigns" :value="stats.campaigns_count" :icon="Megaphone" />
      </div>

      <!-- Campaign Stats -->
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <StatsCard title="Emails Sent" :value="campaignStats.total_sent" :icon="SendHorizonal" />
        <StatsCard title="Emails Opened" :value="campaignStats.total_opened" :icon="MailOpen" />
        <StatsCard title="Links Clicked" :value="campaignStats.total_clicked" :icon="MousePointer" />
        <StatsCard title="Bounces" :value="campaignStats.total_bounced" :icon="AlertTriangle" />
      </div>

      <!-- Charts Section -->
      <div class="grid gap-6 lg:grid-cols-2">
        <div class="bg-card rounded-lg border p-6">
          <StatsChart
            title="Subscriber Growth"
            type="area"
            :height="350"
            :series="subscriberGrowthData.series"
            :categories="subscriberGrowthData.categories"
          />
        </div>

        <div class="bg-card rounded-lg border p-6">
          <StatsChart
            title="Campaign Performance"
            type="bar"
            :height="350"
            :series="campaignPerformanceData.series"
            :categories="campaignPerformanceData.categories"
          />
        </div>
      </div>

      <!-- Teams Grid -->
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <TeamCard v-for="team in teams" :key="team.id" :team="team" :is-current="currentTeam?.id === team.id" :is-owner="isOwner" />
      </div>
    </div>
  </AppLayout>
</template>
