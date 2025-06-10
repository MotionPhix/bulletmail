<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ModalLink } from '@inertiaui/modal-vue';
import { DownloadIcon, PlusIcon, UploadIcon } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import StatsCard from '@/components/StatsCard.vue';

interface Subscriber {
  id: number;
  uuid: string;
  email: string;
  first_name: string;
  last_name: string;
  status: 'active' | 'unsubscribed' | 'bounced';
  created_at: string;
  lists: Array<{ id: number; name: string }>;
}

interface MailingList {
  id: number;
  name: string;
  subscriber_count: number;
}

interface Segment {
  id: number;
  name: string;
  subscriber_count: number;
}

interface Props {
  subscribers: {
    data: Subscriber[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  filters: {
    search?: string;
    status?: string;
    list_id?: number;
    segment_id?: number;
  };
  stats: {
    total: number;
    active: number;
    unsubscribed: number;
    bounced: number;
    engaged_30d: number;
    unengaged_30d: number;
  };
  lists: MailingList[];
  segments: Segment[];
}

const props = defineProps<Props>();

const selected = ref<number[]>([]);
const filters = reactive({ ...props.filters });
const bulkAction = ref('');
const bulkStatus = ref('');
const bulkListId = ref<number>();

const isAllSelected = computed(() => {
  return props.subscribers.data.length > 0 && selected.value.length === props.subscribers.data.length;
});

const formatNumber = (num: number): string => {
  return new Intl.NumberFormat().format(num);
};

const page = usePage();

const getStatusBadgeVariant = (status: string) => {
  return (
    {
      subscribed: 'default',
      unsubscribed: 'destructive',
      bounced: 'warning',
    }[status] || 'secondary'
  );
};

const applyFilters = () => {
  router.get(route('app.subscribers.index'), filters, {
    preserveState: true,
    replace: true,
  });
};

const breadcrumbs = computed(() => [
  {
    title: page.props.auth.current_organization.name,
    href: route('dashboard'),
  },
  {
    title: page.props.auth.current_team.name,
    href: route('teams.show', page.props.auth.current_team.uuid),
  },
  {
    title: 'Subscribers List',
    href: '#',
  },
]);

const executeBulkAction = () => {
  if (!selected.value.length || !bulkAction.value) return;

  const data: any = {
    action: bulkAction.value,
    ids: selected.value,
  };

  if (bulkAction.value === 'update_status') {
    data.status = bulkStatus.value;
  }

  if (['add_to_list', 'remove_from_list'].includes(bulkAction.value)) {
    data.list_id = bulkListId.value;
  }

  router.post(route('subscribers.bulk'), data);
};

const toggleSelectAll = (checked: boolean) => {
  selected.value = checked ? props.subscribers.data.map((s) => s.id) : [];
};

const toggleSubscriber = (subscriberId: number, checked: boolean) => {
  if (checked) {
    // Add to selection if not already present
    if (!selected.value.includes(subscriberId)) {
      selected.value.push(subscriberId);
    }
  } else {
    // Remove from selection
    const index = selected.value.indexOf(subscriberId);
    if (index > -1) {
      selected.value.splice(index, 1);
    }
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Subscribers" />

    <div class="max-w-4xl p-6">
      <div class="sm:flex sm:items-start">
        <div class="sm:flex-auto">
          <Heading title="Subscribers" description="Manage your email subscribers" />
        </div>

        <div class="mt-4 space-x-2 sm:mt-0 sm:ml-16 sm:flex-none">
          <Button variant="outline" @click="router.get(route('app.subscribers.export'))">
            <DownloadIcon />
            Export
          </Button>

          <Button
            :as="ModalLink"
            :href="route('app.subscribers.upload')">
            <UploadIcon />
            Import
          </Button>

          <Button variant="ghost" :as="ModalLink" :href="route('app.subscribers.create')">
            <PlusIcon />
            Add
          </Button>
        </div>
      </div>

      <!-- Filters -->
      <div class="mt-8 flex items-center gap-4">
        <Input v-model="filters.search" placeholder="Search subscribers..." class="max-w-sm" @input="applyFilters" />

        <Select v-model="filters.status" @update:modelValue="applyFilters">
          <SelectTrigger class="w-[180px]">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="null">All Statuses</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="unsubscribed">Unsubscribed</SelectItem>
            <SelectItem value="bounced">Bounced</SelectItem>
          </SelectContent>
        </Select>

        <Select v-model="filters.list_id" @update:modelValue="applyFilters">
          <SelectTrigger class="w-[180px]">
            <SelectValue placeholder="Select List" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="null">All Lists</SelectItem>
            <SelectItem v-for="list in lists" :key="list.id" :value="list.id">
              {{ list.name }}
            </SelectItem>
          </SelectContent>
        </Select>

        <Select v-model="filters.segment_id" @update:modelValue="applyFilters">
          <SelectTrigger class="w-[180px]">
            <SelectValue placeholder="Select Segment" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="null">All Segments</SelectItem>
            <SelectItem v-for="segment in segments" :key="segment.id" :value="segment.id">
              {{ segment.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Stats -->
      <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatsCard
          v-for="(value, key) in stats" :key="key"
          :title="key.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase())"
          :value="formatNumber(value)"
        />
      </div>

      <!-- Bulk Actions -->
      <div v-if="selected.length" class="mt-4 flex items-center gap-4">
        <Select v-model="bulkAction">
          <SelectTrigger class="w-[180px]">
            <SelectValue placeholder="Bulk Actions" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="delete">Delete Selected</SelectItem>
            <SelectItem value="update_status">Update Status</SelectItem>
            <SelectItem value="add_to_list">Add to List</SelectItem>
            <SelectItem value="remove_from_list">Remove from List</SelectItem>
          </SelectContent>
        </Select>

        <Select v-if="bulkAction === 'update_status'" v-model="bulkStatus">
          <SelectTrigger class="w-[180px]">
            <SelectValue placeholder="Select Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="unsubscribed">Unsubscribed</SelectItem>
            <SelectItem value="bounced">Bounced</SelectItem>
          </SelectContent>
        </Select>

        <Select v-if="['add_to_list', 'remove_from_list'].includes(bulkAction)" v-model="bulkListId">
          <SelectTrigger class="w-[180px]">
            <SelectValue placeholder="Select List" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="list in lists" :key="list.id" :value="list.id">
              {{ list.name }}
            </SelectItem>
          </SelectContent>
        </Select>

        <Button @click="executeBulkAction">Apply</Button>
        <Button variant="ghost" @click="selected = []">Clear Selection</Button>
      </div>

      <!-- Subscribers Table -->
      <Card class="mt-8" v-if="subscribers.data.length">
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="w-[30px]">
                  <div class="flex items-center">
                    <Checkbox
                      :model-value="isAllSelected"
                      @update:model-value="toggleSelectAll"
                    />
                  </div>
                </TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Lists</TableHead>
                <TableHead>Joined</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>

            <TableBody>
              <TableRow v-for="subscriber in subscribers.data" :key="subscriber.id">
                <TableCell class="w-[30px]">
                  <div class="flex items-center">
                    <Checkbox
                      :model-value="selected.includes(subscriber.id)"
                      @update:model-value="(checked) => toggleSubscriber(subscriber.id, checked)"
                    />
                  </div>
                </TableCell>
                <TableCell>{{ subscriber.email }}</TableCell>
                <TableCell>{{ subscriber.first_name }} {{ subscriber.last_name }}</TableCell>
                <TableCell>
                  <Badge
                    class="capitalize"
                    :variant="getStatusBadgeVariant(subscriber.status)">
                    {{ subscriber.status }}
                  </Badge>
                </TableCell>
                <TableCell>{{ subscriber.lists?.length || 0 }} lists</TableCell>
                <TableCell>{{ new Date(subscriber.created_at).toLocaleDateString() }}</TableCell>
                <TableCell>
                  <Button variant="ghost" size="sm" :href="route('app.subscribers.show', subscriber.uuid)"> View</Button>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Card v-else class="mt-8">
        <CardContent class="text-center text-muted-foreground">
          No subscribers found. Use the filters above to refine your search. <br />

          <Button
            :as="ModalLink"
            :href="route('app.subscribers.create')"
            class="mt-4">
            <PlusIcon />
            Or add subscriber
          </Button>
        </CardContent>
      </Card>

      <!-- Pagination -->
      <div v-if="subscribers.data.length" class="mt-4 flex items-center justify-between">
        <div class="text-muted-foreground text-sm">
          Showing {{ subscribers.data.length }} of {{ subscribers.total }}
          results
        </div>
        <div class="space-x-2">
          <Button
            v-for="link in subscribers.links"
            :key="link.label"
            :disabled="!link.url"
            variant="outline"
            size="sm"
            @click="link.url && router.get(link.url)"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
