<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { computed, ref, watch } from 'vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import {PencilIcon, SearchIcon} from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { debounce } from 'lodash';

const props = defineProps<{
  campaigns: {
    data: Array<{
      id: number
      uuid?: string
      name: string
      status: string
      subject: string
      scheduled_at?: string
      stats?: {
        sent: number;
        opened: number;
        clicked: number;
      }
      user?: {
        name: string;
        email: string;
      }
    }>,
    current_page: number
    first_page_url: string
    last_page: number
    from: number
    next_page_url?: string
    per_page: number
    to: number
    total: number
    links: Array<{
      active: boolean
      label: string | number
      url?: string
    }>
  };
  filters: {
    search?: string;
    status?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
  };
  statuses: Array<string>;
}>();

const page = usePage().props.auth

const breadcrumbs = computed(() => [
  {
    title: page.current_organization.name,
    href: route('dashboard')
  },
  {
    title: page.current_team.name,
    href: '#'
  },
  {
    title: 'Campaigns',
    href: '#'
  }
]);

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const sort = ref(props.filters.sort || 'name');
const direction = ref(props.filters.direction || 'desc');
const loading = ref(false);

const toggleSort = (field: string) => {
  if (sort.value === field) {
    direction.value = direction.value === 'asc' ? 'desc' : 'asc';
  } else {
    sort.value = field;
    direction.value = 'asc';
  }
};

const fetchCampaigns = debounce((query: any) => {
    router.visit(route('app.campaigns.index', query), {
      preserveScroll: true,
      replace: true,
      onFinish: () => (loading.value = false),
    });
  }, 300);

watch([search, status, sort], ([newSearch, newStatus, newSort]) => {
  loading.value = true;

  const query = {
    search: newSearch || undefined,
    status: newStatus || undefined,
    sort: newSort || undefined,
  };

  fetchCampaigns(query);
});
</script>

<template>
  <Head title="Campaigns" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 max-w-4xl">
      <!-- Filters -->
      <div class="flex items-center gap-4 mb-6">
        <div class="flex items-center gap-2 flex-1 relative">
          <Input
            v-model="search"
            placeholder="Search campaigns..."
            class="w-full"
            icon="search"
          />

          <SearchIcon class="h-4 w-4 text-muted-foreground absolute right-3" />
        </div>

        <Select v-model="status">
          <SelectTrigger>
            <SelectValue placeholder="Select Status" />
          </SelectTrigger>

          <SelectContent>
            <SelectItem :value="null">All Statuses</SelectItem>
            <SelectItem
              v-for="statusOption in props.statuses"
              :key="statusOption"
              :value="statusOption">
              {{ statusOption }}
            </SelectItem>
          </SelectContent>
        </Select>

        <Select v-model="sort">
          <SelectTrigger>
            <SelectValue placeholder="Sort By" />
          </SelectTrigger>

          <SelectContent>
            <SelectItem value="name">Name</SelectItem>
            <SelectItem value="status">Status</SelectItem>
            <SelectItem value="scheduled_at">Scheduled At</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div v-if="loading" class="text-center py-6">
        <p>Loading campaigns...</p>
      </div>

      <Table v-if="campaigns.data.length > 0 && !loading">
        <TableHeader>
          <TableRow>
            <TableHead>Name</TableHead>
            <TableHead>Subject</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Scheduled At</TableHead>
            <TableHead></TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow v-for="campaign in campaigns.data" :key="campaign.id">
            <TableCell>{{ campaign.name }}</TableCell>
            <TableCell>{{ campaign.subject }}</TableCell>
            <TableCell class="capitalize">{{ campaign.status }}</TableCell>
            <TableCell>{{ campaign.scheduled_at || 'Not Scheduled' }}</TableCell>
            <TableCell>
              <Button
                size="icon"
                :as="Link"
                variant="link"
                :href="route('app.campaigns.edit', { uuid: campaign.uuid })">
                <PencilIcon />
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <div v-else-if="!loading" class="flex flex-col items-center justify-center py-12">
        <div class="text-center">
          <h2 class="text-lg font-semibold text-muted-foreground">No Campaigns Found</h2>
          <p class="text-sm text-muted-foreground mt-2">
            You haven’t created any campaigns yet. Start by creating your first campaign.
          </p>
        </div>

        <Button class="mt-4" :as="Link" :href="route('app.campaigns.create')">
          Create Campaign
        </Button>
      </div>

      <!-- Pagination -->
      <div class="mt-6 flex justify-between items-center">
        <p class="text-sm text-muted-foreground">
          Showing {{ props.campaigns.from }} to {{ props.campaigns.to }} of {{ props.campaigns.total }} campaigns
        </p>
        <div class="flex gap-2">
          <Button
            v-for="link in props.campaigns.links"
            :key="link.label"
            :variant="link.active ? 'primary' : 'outline'"
            :disabled="!link.url"
            :href="link.url"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
