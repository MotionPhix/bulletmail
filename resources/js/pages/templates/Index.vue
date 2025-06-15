<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { PencilIcon, TrashIcon } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';

const props = defineProps<{
  templates: {
    data: Array<{
      id: number;
      uuid: string;
      name: string;
      description: string;
      subject: string;
      category: string;
      type: string;
      status: string;
      created_at: string;
    }>;
    links: Array<{
      active: boolean;
      label: string | number;
      url?: string;
    }>;
  };
  filters: {
    search?: string;
    category?: string;
    type?: string;
    status?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
  };
  categories: Array<{ value: string; label: string }>;
  statuses: Array<{ value: string; label: string }>;
  types: Array<{ value: string; label: string }>;
}>();

const filters = ref({
  search: props.filters.search || '',
  category: props.filters.category || '',
  type: props.filters.type || '',
  status: props.filters.status || '',
  sort: props.filters.sort || 'created_at',
  direction: props.filters.direction || 'desc'
});

const breadcrumbs = [
  { title: usePage().props.auth.current_team.name, href: route('dashboard') },
  { title: 'Templates', href: '#' },
];

const debouncedSearch = debounce((query: any) => {
  router.get(route('app.templates.index'), query, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}, 300);

watch(filters, (newFilters) => {
  debouncedSearch({
    ...newFilters,
    page: 1
  });
}, { deep: true });

const toggleSort = (field: string) => {
  filters.value.direction = filters.value.sort === field && filters.value.direction === 'asc' ? 'desc' : 'asc';
  filters.value.sort = field;
};

const getSortIcon = (field: string) => {
  if (filters.value.sort !== field) return null;
  return filters.value.direction === 'asc' ? '↑' : '↓';
};
</script>

<template>
  <Head title="Templates" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 max-w-4xl">
      <div class="flex items-start justify-between mb-6">
        <Heading title="Templates" description="Manage your templates" />
        <Button :as="Link" :href="route('app.templates.create')">New</Button>
      </div>

      <div class="mb-6 space-y-4">

        <div class="flex gap-4">
          <Input
            v-model="filters.search"
            placeholder="Search templates..."
            class="max-w-xs"
          />

          <Select v-model="filters.category">
            <SelectTrigger class="w-[180px]">
              <SelectValue placeholder="Category" />
            </SelectTrigger>

            <SelectContent>
              <SelectItem :value="null">All Categories</SelectItem>
              <SelectItem v-for="cat in categories" :key="cat.value" :value="cat.value">
                {{ cat.label }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="filters.type">
            <SelectTrigger class="w-[180px]">
              <SelectValue placeholder="Type" />
            </SelectTrigger>

            <SelectContent>
              <SelectItem :value="null">All Types</SelectItem>
              <SelectItem v-for="t in types" :key="t.value" :value="t.value">
                {{ t.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <Table>
        <TableHeader>
          <TableRow>
            <TableHead
              @click="toggleSort('name')"
              class="cursor-pointer">
              Name {{ getSortIcon('name') }}
            </TableHead>

            <TableHead
              @click="toggleSort('category')"
              class="cursor-pointer">
              Category {{ getSortIcon('category') }}
            </TableHead>

            <TableHead
              @click="toggleSort('type')"
              class="cursor-pointer">
              Type {{ getSortIcon('type') }}
            </TableHead>

            <TableHead
              @click="toggleSort('status')"
              class="cursor-pointer">
              Status {{ getSortIcon('status') }}
            </TableHead>

            <TableHead />
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="template in props.templates.data" :key="template.id">
            <TableCell class="grid">
              <strong>{{ template.name }}</strong>
              <p class="text-sm text-muted-foreground truncate max-w-xs">
                {{ template.description }}
              </p>
            </TableCell>

            <TableCell class="capitalize">
              {{ template.category }}
            </TableCell>

            <TableCell class="capitalize">
              {{ template.type }}
            </TableCell>

            <TableCell>
              <Badge
                class="capitalize"
                :variant="template.status === 'published'
                  ? 'success'
                  : 'secondary'
                ">
                {{ template.status }}
              </Badge>
            </TableCell>

            <TableCell>
              <div class="flex items-center">
                <Button
                  class="text-danger-foreground hover:bg-danger/10 focus:bg-danger/10"
                  variant="link" size="icon" :as="Link"
                  :href="route('app.templates.edit', template.uuid)">
                  <PencilIcon />
                </Button>

                <Button
                  :as="Link" size="icon" variant="link"
                  :href="route('app.templates.destroy', template.uuid)">
                  <TrashIcon />
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <Separator />

      <!-- Pagination -->
      <div class="mt-4 flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
          Showing {{ templates.data.length }} of {{ templates.total }} templates
        </p>

        <div class="flex gap-2">
          <Button
            v-for="link in templates.links"
            :key="link.label"
            :disabled="!link.url"
            :variant="link.active ? 'default' : 'outline'"
            @click="router.visit(link.url)"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
