<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import StatsCard from '@/components/StatsCard.vue';
import StatsChart from '@/components/charts/StatsChart.vue';
import ActivityFeed from '@/components/ActivityFeed.vue';
import {
  Users,
  UserCircle,
  Mail,
  Zap,
  SendHorizonal,
  MailOpen,
  MousePointer,
  AlertTriangle
} from 'lucide-vue-next';

const props = defineProps({
  organization: Object,
  team: Object,
  isOwner: Boolean,
  teamStats: Object,
  campaignStats: Object,
  subscriberTrends: Object,
  campaignHistory: Array
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

const subscriberTrendData = computed(() => {
  const months = Object.keys(props.subscriberTrends || {});
  const values = Object.values(props.subscriberTrends || {});

  return {
    categories: months,
    series: [{
      name: 'Subscribers',
      data: values
    }]
  };
});

const campaignPerformanceData = computed(() => ({
  series: [{
    name: 'Email Performance',
    data: [
      props.campaignStats.total_sent,
      props.campaignStats.total_opened,
      props.campaignStats.total_clicked,
      props.campaignStats.total_bounced
    ]
  }],
  categories: ['Sent', 'Opened', 'Clicked', 'Bounced']
}));

const engagementRatesData = computed(() => {
  const totalSent = props.campaignStats.total_sent || 1;
  const openRate = ((props.campaignStats.total_opened / totalSent) * 100).toFixed(1);
  const clickRate = ((props.campaignStats.total_clicked / totalSent) * 100).toFixed(1);
  const bounceRate = ((props.campaignStats.total_bounced / totalSent) * 100).toFixed(1);

  return {
    series: [{
      name: 'Rate (%)',
      data: [parseFloat(openRate), parseFloat(clickRate), parseFloat(bounceRate)]
    }],
    categories: ['Open Rate', 'Click Rate', 'Bounce Rate']
  };
});
</script>

<template>
  <Head :title="`${team.name} Dashboard`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Team Overview -->
      <div>
        <h1 class="text-2xl font-semibold">{{ team.name }}</h1>
        <p class="text-muted-foreground">Team Overview</p>
      </div>

      <!-- Team Stats -->
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <StatsCard
          title="Team Members"
          :value="teamStats.members_count"
          :icon="Users"
        />
        <StatsCard
          title="Subscribers"
          :value="teamStats.subscribers_count"
          :icon="UserCircle"
        />
        <StatsCard
          title="Campaigns"
          :value="teamStats.campaigns_count"
          :icon="Mail"
        />
        <StatsCard
          title="Active Automations"
          :value="teamStats.active_automations"
          :icon="Zap"
        />
      </div>

      <!-- Charts Grid -->
      <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border bg-card p-6">
          <StatsChart
            title="Subscriber Growth"
            type="area"
            :height="350"
            :series="subscriberTrendData.series"
            :categories="subscriberTrendData.categories"
          />
        </div>

        <div class="rounded-lg border bg-card p-6">
          <StatsChart
            title="Campaign Performance"
            type="bar"
            :height="350"
            :series="campaignPerformanceData.series"
            :categories="campaignPerformanceData.categories"
          />
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <!-- Campaign Stats -->
        <div class="space-y-6">
          <div class="grid gap-6 sm:grid-cols-2">
            <StatsCard
              title="Emails Sent"
              :value="campaignStats.total_sent"
              :icon="SendHorizonal"
            />
            <StatsCard
              title="Emails Opened"
              :value="campaignStats.total_opened"
              :icon="MailOpen"
            />
            <StatsCard
              title="Links Clicked"
              :value="campaignStats.total_clicked"
              :icon="MousePointer"
            />
            <StatsCard
              title="Bounces"
              :value="campaignStats.total_bounced"
              :icon="AlertTriangle"
            />
          </div>

          <div class="rounded-lg border bg-card p-6">
            <StatsChart
              title="Engagement Rates"
              type="bar"
              :height="250"
              :series="engagementRatesData.series"
              :categories="engagementRatesData.categories"
            />
          </div>
        </div>

        <!-- Activity Feed -->
        <div class="rounded-lg border bg-card">
          <div class="p-6">
            <h2 class="text-lg font-semibold mb-4">Recent Activity</h2>
            <ActivityFeed :activities="team.recent_activities" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
