<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  description: '',
  subject: '',
  content: '',
  category: '',
  type: '',
});

const submit = () => {
  form.post(route('templates.store'));
};
</script>

<template>
  <div>
    <h1>Create Template</h1>
    <form @submit.prevent="submit">
      <div>
        <label>Name</label>
        <input v-model="form.name" type="text" required />
      </div>
      <div>
        <label>Description</label>
        <input v-model="form.description" type="text" />
      </div>
      <div>
        <label>Subject</label>
        <input v-model="form.subject" type="text" required />
      </div>
      <div>
        <label>Content</label>
        <textarea v-model="form.content" required></textarea>
      </div>
      <div>
        <label>Category</label>
        <input v-model="form.category" type="text" required />
      </div>
      <div>
        <label>Type</label>
        <input v-model="form.type" type="text" required />
      </div>
      <button type="submit">Create</button>
    </form>
  </div>
</template>
