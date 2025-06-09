<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CogIcon, UsersIcon, MailIcon, ChartBarIcon } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type Organization } from '@/types';
import { Table } from '@/components/ui/table';
import { TableHeader } from '@/components/ui/table';
import { TableRow } from '@/components/ui/table';
import { TableHead } from '@/components/ui/table';
import { TableBody } from '@/components/ui/table';
import { TableCell } from '@/components/ui/table';

interface Props {
  organization: Organization & {
    teams: Array<{
      id: number;
      uuid: string;
      name: string;
      users_count: number;
      subscribers_count: number;
      campaigns_count: number;
    }>;
  };
  stats: {
    total_members: number;
    total_subscribers: number;
    total_campaigns: number;
  };
  can: {
    update: boolean;
    delete: boolean;
  };
}

const props = defineProps<Props>();

const formatNumber = (num: number): string => {
  return new Intl.NumberFormat().format(num);
};
</script>

<template>
  <AppLayout>

    <Head :title="organization.name" />

    <div class="px-4 sm:px-6 lg:px-8">
      <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <Heading :title="organization.name"
                   :description="`${organization.size} company · ${organization.industry}`" />
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
          <Button v-if="can.update"
                  variant="outline"
                  as-child>
            <Link :href="route('organization.settings', organization.uuid)">
            <CogIcon class="-ml-0.5 mr-1.5 h-5 w-5" />
            Settings
            </Link>
          </Button>
        </div>
      </div>

      <!-- Stats -->
      <div class="mt-8 grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle class="text-sm font-medium">
              Total Members
            </CardTitle>
            <UsersIcon class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ formatNumber(stats.total_members) }}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle class="text-sm font-medium">
              Total Subscribers
            </CardTitle>
            <MailIcon class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ formatNumber(stats.total_subscribers) }}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle class="text-sm font-medium">
              Total Campaigns
            </CardTitle>
            <ChartBarIcon class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ formatNumber(stats.total_campaigns) }}
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Teams List -->
      <div class="mt-8">
        <Card>
          <CardHeader>
            <CardTitle>Teams</CardTitle>
            <CardDescription>
              Teams in your organization
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Members</TableHead>
                  <TableHead>Subscribers</TableHead>
                  <TableHead>Campaigns</TableHead>
                  <TableHead />
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="team in organization.teams"
                          :key="team.uuid">
                  <TableCell>
                    <Link :href="route('teams.show', team.uuid)"
                          class="text-primary hover:underline">
                    {{ team.name }}
                    </Link>
                  </TableCell>
                  <TableCell>{{ formatNumber(team.users_count) }}</TableCell>
                  <TableCell>{{ formatNumber(team.subscribers_count) }}</TableCell>
                  <TableCell>{{ formatNumber(team.campaigns_count) }}</TableCell>
                  <TableCell class="text-right">
                    <Button variant="ghost"
                            size="sm"
                            as-child>
                      <Link :href="route('teams.show', team.uuid)">
                      View
                      </Link>
                    </Button>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
