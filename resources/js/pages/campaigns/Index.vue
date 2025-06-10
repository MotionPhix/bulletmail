<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
  campaigns: Array<{
    id: number;
    name: string;
    subject: string;
    status: string;
    scheduled_at: string | null;
    created_at: string;
  }>;
}>();
</script>

<template>
  <div>
    <Head title="Campaigns" />
    <h1 class="text-2xl font-bold mb-4">Campaigns</h1>
    <Link href="/campaigns/create" class="btn btn-primary mb-4">Create Campaign</Link>
    <table class="table-auto w-full border-collapse border border-gray-300">
      <thead>
      <tr>
        <th class="border border-gray-300 px-4 py-2">Name</th>
        <th class="border border-gray-300 px-4 py-2">Subject</th>
        <th class="border border-gray-300 px-4 py-2">Status</th>
        <th class="border border-gray-300 px-4 py-2">Scheduled At</th>
        <th class="border border-gray-300 px-4 py-2">Actions</th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="campaign in campaigns" :key="campaign.id">
        <td class="border border-gray-300 px-4 py-2">{{ campaign.name }}</td>
        <td class="border border-gray-300 px-4 py-2">{{ campaign.subject }}</td>
        <td class="border border-gray-300 px-4 py-2">{{ campaign.status }}</td>
        <td class="border border-gray-300 px-4 py-2">{{ campaign.scheduled_at || 'Not Scheduled' }}</td>
        <td class="border border-gray-300 px-4 py-2">
          <Link :href="`/campaigns/${campaign.id}/edit`" class="btn btn-sm btn-outline">Edit</Link>
        </td>
      </tr>
      </tbody>
    </table>
  </div>
</template>
