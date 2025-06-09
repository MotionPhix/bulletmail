<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Heading from '@/components/Heading.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type Organization } from '@/types';

interface Props {
  organizations: Organization[];
  can: {
    create: boolean;
  };
}

const props = defineProps<Props>();

const formatNumber = (num: number): string => {
  return new Intl.NumberFormat().format(num);
};
</script>

<template>
  <AppLayout>

    <Head title="Organizations" />

    <div class="px-4 sm:px-6 lg:px-8">
      <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <Heading title="Organizations"
                   description="Manage your organizations" />
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none"
             v-if="can.create">
          <Button as-child>
            <Link :href="route('organizations.create')">
            <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5"
                      aria-hidden="true" />
            New Organization
            </Link>
          </Button>
        </div>
      </div>

      <div class="mt-8 flow-root">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Size</TableHead>
              <TableHead>Industry</TableHead>
              <TableHead>Teams</TableHead>
              <TableHead>Members</TableHead>
              <TableHead>Subscribers</TableHead>
              <TableHead>Campaigns</TableHead>
              <TableHead class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                <span class="sr-only">Actions</span>
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="org in organizations"
                      :key="org.uuid">
              <TableCell>
                <Link :href="route('organizations.show', org.uuid)"
                      class="text-primary hover:underline">
                {{ org.name }}
                </Link>
              </TableCell>
              <TableCell>{{ org.size }}</TableCell>
              <TableCell>{{ org.industry }}</TableCell>
              <TableCell>{{ formatNumber(org.teams_count) }}</TableCell>
              <TableCell>{{ formatNumber(org.total_members) }}</TableCell>
              <TableCell>{{ formatNumber(org.total_subscribers) }}</TableCell>
              <TableCell>{{ formatNumber(org.total_campaigns) }}</TableCell>
              <TableCell class="relative py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                <Button variant="ghost"
                        size="sm"
                        as-child>
                  <Link :href="route('organizations.show', org.uuid)"
                        class="text-primary hover:text-primary/80">
                  View
                  </Link>
                </Button>
              </TableCell>
            </TableRow>
            <TableRow v-if="organizations.length === 0">
              <TableCell colspan="8"
                         class="text-center py-8 text-muted-foreground">
                No organizations found.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>
