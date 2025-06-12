<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { PencilIcon, TrashIcon } from 'lucide-vue-next';

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
  };
}>();

const search = ref(props.filters.search || '');

const fetchTemplates = debounce((query: any) => {
  router.visit(route('templates.index', query), {
    preserveScroll: true,
    replace: true,
  });
}, 300);

watch(search, (newSearch) => {
  fetchTemplates({ search: newSearch || undefined });
});
</script>

<template>
  <Head title="Templates" />

  <AppLayout>
    <div class="p-6 max-w-4xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Templates</h1>
        <Button :as="Link" :href="route('app.templates.create')">Create Template</Button>
      </div>

      <div class="mb-6">
        <Input v-model="search" placeholder="Search templates..." />
      </div>

      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>
              Name
            </TableHead>

            <TableHead>Category</TableHead>
            <TableHead>Type</TableHead>
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

            <TableCell class="capitalize">{{ template.category }}</TableCell>

            <TableCell class="capitalize">{{ template.type }}</TableCell>

            <TableCell>
              <div class="flex items-center gap-x-2">
                <Button size="icon" :as="Link" :href="route('app.templates.edit', template.uuid)">
                  <PencilIcon />
                </Button>

                <Button :as="Link" size="icon" variant="destructive" :href="route('app.templates.destroy', template.uuid)">
                  <TrashIcon />
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </AppLayout>
</template>
