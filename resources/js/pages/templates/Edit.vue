<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EmailEditor } from 'vue-email-editor';
import { useDark } from '@vueuse/core';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { ArrowLeftIcon } from 'lucide-vue-next';

const props = defineProps<{
  template: {
    uuid: string;
    name: string;
    description: string;
    subject: string;
    content: string;
    preview_text: string;
    category: string;
    type: string;
    design: any;
    variables: any;
    tags: string[];
    campaigns_count: number;
  };
  categories: Array<{ value: string; label: string }>;
}>();

const unlayerEmailEditor = ref();
const isDarkMode = useDark();
const unlayerProjectId = parseInt(import.meta.env.VITE_UNLAYER_PROJECT_ID);

const form = useForm({
  name: props.template.name,
  description: props.template.description,
  subject: props.template.subject,
  content: props.template.content,
  preview_text: props.template.preview_text,
  category: props.template.category,
  type: props.template.type,
  design: props.template.design,
  variables: props.template.variables,
  tags: props.template.tags || []
});

const types = [
  { value: 'drag-drop', label: 'Drag & Drop' },
  { value: 'html', label: 'HTML' }
];

// Unlayer configuration
const appearance = {
  theme: isDarkMode.value ? 'dark' : 'light',
  panels: {
    tools: {
      dock: 'left'
    }
  }
};

const tools = {
  heading: {
    properties: {
      text: {
        value: 'Email Template'
      }
    }
  }
};

const editorLoaded = () => {
  if (form.design && Object.keys(form.design).length > 0) {
    unlayerEmailEditor.value.editor.loadDesign(JSON.parse(JSON.stringify(form.design)));
  }
};

const saveDesign = () => {
  try {
    unlayerEmailEditor.value.editor.saveDesign(design => {
      form.design = design;
    });

    unlayerEmailEditor.value.editor.exportHtml(data => {
      form.content = data.html;
    });
  } catch (error) {
    console.error('Error saving design:', error);
  }
};

const submit = async () => {
  form.put(route('app.templates.update', props.template.uuid), {
    preserveScroll: true
  });
};

const breadcrumbs = [
  { title: usePage().props.auth.current_team.name, href: route('dashboard') },
  { title: 'Templates', href: route('app.templates.index') },
  { title: 'Edit Template', href: '#' }
];

onMounted(() => {
  if (unlayerEmailEditor.value?.editor) {
    unlayerEmailEditor.value.editor.addEventListener('design:updated', function () {
      unlayerEmailEditor.value.editor.setBodyValues({
        fontFamily: {
          label: 'Helvetica',
          value: "'Helvetica Neue', Helvetica, Arial, sans-serif",
        },
        preheaderText: form.preview_text
      })

      saveDesign();
    })
  }
});
</script>

<template>
  <Head title="Edit Template" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-5xl p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-semibold">Edit Template</h1>
          <p class="text-sm text-muted-foreground">
            Used in {{ template.campaigns_count }} campaign(s)
          </p>
        </div>

        <div class="flex gap-3">
          <Button
            :as="Link"
            variant="ghost"
            size="icon"
            :href="route('app.templates.index')">
            <ArrowLeftIcon />
          </Button>

          <Button @click="submit">
            Save Changes
          </Button>
        </div>
      </div>

      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Template Details</CardTitle>
            <CardDescription>Basic information about your template</CardDescription>
          </CardHeader>

          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label>Template Name</Label>
                <Input v-model="form.name" placeholder="Monthly Newsletter" />

                <InputError :message="form.errors.name" class="mt-1" />
              </div>

              <div class="space-y-2">
                <Label>Email Subject</Label>
                <Input v-model="form.subject" placeholder="Your Monthly Update" />

                <InputError :message="form.errors.subject" class="mt-1" />
              </div>
            </div>

            <div class="space-y-2">
              <Label>Description</Label>
              <Textarea
                v-model="form.description"
                placeholder="Brief description of this template's purpose"
              />

              <InputError :message="form.errors.description" class="mt-1" />
            </div>

            <div class="space-y-2">
              <Label>Preview Text</Label>
              <Input
                v-model="form.preview_text"
                placeholder="A brief preview that appears in email clients"
              />

              <InputError :message="form.errors.preview_text" class="mt-1" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label>Category</Label>
                <Select v-model="form.category">
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select category" />
                  </SelectTrigger>

                  <SelectContent>
                    <SelectItem
                      v-for="category in categories"
                      :key="category.value"
                      :value="category.value">
                      {{ category.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>

                <InputError :message="form.errors.category" class="mt-1" />
              </div>

              <div class="space-y-2">
                <Label>Type</Label>
                <Select v-model="form.type">
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select type" />
                  </SelectTrigger>

                  <SelectContent>
                    <SelectItem
                      v-for="type in types"
                      :key="type.value"
                      :value="type.value">
                      {{ type.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Template Design</CardTitle>
            <CardDescription>Design your email template</CardDescription>
          </CardHeader>

          <CardContent>
            <div class="border rounded-lg overflow-hidden">
              <div class="container">
                <EmailEditor
                  ref="unlayerEmailEditor"
                  :projectId="unlayerProjectId"
                  :appearance="appearance"
                  displayMode="email"
                  :tools="tools"
                  minHeight="700px"
                  v-on:load="editorLoaded"
                  @editor-loaded="editorLoaded"
                  @design-updated="saveDesign"
                />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.unlayer-editor {
  border-radius: 0.375rem;
}

.unlayer-editor-counter {
  display: none;
}

a.blockbuilder-branding {
  display: none !important;
}

.blockbuilder {
  overflow-x: hidden !important;
}

.container {
  border-radius: 1rem;
  overflow: hidden;
}

.unlayer-editor {
  height: 700px;
}

#editor {
  width: 20px !important;
}

#bar {
  flex: 1;
  background-color: #40B883;
  padding: 10px;
  display: flex;
  max-height: 50px;
}

#bar h1 {
  flex: 1;
  font-size: 16px;
  text-align: left;
}
</style>
