<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EmailEditor } from 'vue-email-editor';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import { ArrowLeftIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';

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
    merge_tags: Array<{
      key: string;
      name: string;
      description: string;
      default: string;
      category: string;
      required: boolean;
    }>;
    tags: string[];
    campaigns_count: number;
  };
  categories: Array<{ value: string; label: string }>;
  mergeTags: Record<string, Array<{
    id: number;
    key: string;
    name: string;
    description: string;
    default: string;
  }>>;
}>();

const templateEditor = ref();

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
  tags: props.template.tags || [],
});

const types = [
  { value: 'drag-drop', label: 'Drag & Drop' },
  { value: 'html', label: 'HTML' },
];

// Unlayer configuration
const appearance = {
  theme: 'dark',
  panels: {
    tools: {
      dock: 'left',
    },
  },
};

const tools = {
  image: {
    enabled: true,
  },
  social: {
    enabled: true,
  },
  heading: {
    properties: {
      text: {
        value: 'Enter your heading here',
      },
    },
  },
};

const editorLoaded = () => {
  if (form.design) {
    try {
      const designData = form.design
        ? (typeof form.design === 'string' ? JSON.parse(form.design) : form.design)
        : {};

      if (designData.body) {
        const flattenedTags = Object.values(props.mergeTags).flat();

        templateEditor.value?.editor.setMergeTags(
          flattenedTags.reduce((acc, tag) => ({
            ...acc,
            [tag.key]: {
              name: tag.name,
              value: `{{${tag.key}}}`,
              required: tag.required
            }
          }), {})
        );

        templateEditor.value.editor.loadDesign(JSON.parse(JSON.stringify(designData)));
      } else {
        // Create a basic Unlayer design structure
        const design = {
          body: {
            rows: [],
            values: {
              backgroundColor: designData?.colors?.primary || '#ffffff',
              containerPadding: '0px',
              fontFamily: { label: 'Geist Mono', value: '"Geist Mono",monospace' }
            }
          },
          schemaVersion: 21
        };

        templateEditor.value.editor.loadDesign(design);
      }
    } catch (error) {
      console.error('Error loading design:', error);
    }
  }
};

const saveDesign = async () => {
  try {
    const design = await new Promise((resolve) => {
      templateEditor.value.editor.saveDesign((design) => {
        resolve(design);
      });
    });

    const { html } = await new Promise((resolve) => {
      templateEditor.value.editor.exportHtml((data) => {
        resolve(data);
      });
    });

    form.design = design;
    form.content = html;

    // Update preview text if available
    if (form.preview_text) {
      templateEditor.value.editor.setBodyValues({
        preheaderText: form.preview_text,
      });
    }
  } catch (error) {
    console.error('Error saving design:', error);
  }
};

const submit = async () => {
  try {
    // Get final design and HTML before submitting
    await saveDesign();

    await form.put(route('app.templates.update', props.template.uuid), {
      preserveScroll: true,
    });
  } catch (error) {
    console.error('Error submitting template:', error);
  }
};

const breadcrumbs = [
  { title: usePage().props.auth.current_team.name, href: route('dashboard') },
  { title: 'Templates', href: route('app.templates.index') },
  { title: 'Edit Template', href: '#' },
];
</script>

<template>
  <Head title="Edit Template" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-5xl p-6">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold">Edit Template</h1>
          <p class="text-muted-foreground text-sm">Used in {{ template.campaigns_count }} campaign(s)</p>
        </div>

        <div class="flex gap-3">
          <Button :as="Link" variant="ghost" size="icon" :href="route('app.templates.index')">
            <ArrowLeftIcon />
          </Button>

          <Button @click="submit"> Save Changes </Button>
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
              <Textarea v-model="form.description" placeholder="Brief description of this template's purpose" />

              <InputError :message="form.errors.description" class="mt-1" />
            </div>

            <div class="space-y-2">
              <Label>Preview Text</Label>
              <Input v-model="form.preview_text" placeholder="A brief preview that appears in email clients" />

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
                    <SelectItem v-for="category in categories" :key="category.value" :value="category.value">
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
                    <SelectItem v-for="type in types" :key="type.value" :value="type.value">
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
            <CardTitle>Available Merge Tags</CardTitle>
            <CardDescription>Tags you can use in your template</CardDescription>
          </CardHeader>

          <CardContent>
            <div class="grid gap-4">
              <div v-for="(tags, category) in mergeTags" :key="category">
                <h3 class="font-medium mb-2 capitalize">{{ category }}</h3>
                <div class="space-y-2">
                  <div v-for="tag in tags" :key="tag.key" class="flex justify-between items-center p-2 bg-muted rounded-md">
                    <div>
                      <p class="font-mono text-sm" v-text="`{{${tag.key}}}`"></p>
                      <p class="text-sm text-muted-foreground">{{ tag.description }}</p>
                    </div>
                    <Badge v-if="tag.required" variant="secondary">Required</Badge>
                  </div>
                </div>
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
            <div class="overflow-hidden rounded-lg border">
              <div class="editor-container">
                <EmailEditor
                  ref="templateEditor"
                  :projectId="unlayerProjectId"
                  :appearance="appearance"
                  displayMode="email"
                  :tools="tools"
                  minHeight="700px"
                  v-on:load="editorLoaded"
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

.editor-container {
  border-radius: 1rem;
  overflow: hidden;
}

.unlayer-editor {
  height: 700px;
}

#editor {
  width: 20px !important;
}
</style>
