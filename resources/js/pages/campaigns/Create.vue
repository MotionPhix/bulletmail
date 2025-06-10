<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
  templates: Array<{ id: number; name: string }>;
  lists: Array<{ id: number; name: string }>;
}>();

const form = useForm({
  name: '',
  subject: '',
  template_id: null,
  content: '',
  list_ids: [],
  scheduled_at: null,
});

const submit = () => {
  form.post('/campaigns');
};
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Create Campaign</h1>
    <form @submit.prevent="submit">
      <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Name</label>
        <input v-model="form.name" type="text" class="input" required />
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Subject</label>
        <input v-model="form.subject" type="text" class="input" required />
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Template</label>
        <select v-model="form.template_id" class="input" required>
          <option v-for="template in props.templates" :key="template.id" :value="template.id">
            {{ template.name }}
          </option>
        </select>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Content</label>
        <textarea v-model="form.content" class="input" required></textarea>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Mailing Lists</label>
        <select v-model="form.list_ids" class="input" multiple required>
          <option v-for="list in props.lists" :key="list.id" :value="list.id">
            {{ list.name }}
          </option>
        </select>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Scheduled At</label>
        <input v-model="form.scheduled_at" type="datetime-local" class="input" />
      </div>
      <button type="submit" class="btn btn-primary">Create</button>
    </form>
  </div>
</template>
