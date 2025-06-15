<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItemType, Campaign } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ModalLink } from '@inertiaui/modal-vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useStorage } from '@vueuse/core';
import { EmailEditor } from 'vue-email-editor';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import axios from 'axios';

interface MergeTag {
  key: string;
  name: string;
  description: string;
  default: string;
  required?: boolean;
}

const props = defineProps<{
  campaign: Campaign;
  templates: Array<{ id: number; uuid: string; name: string }>;
  lists: Array<{ id: number; uuid: string; name: string }>;
  mergeTags: Record<string, MergeTag[]>;
}>();

const campaignEditor = ref(null);
const previewHtml = ref('');
const activeTab = useStorage('campaign_edit', 'edit');
const unlayerProjectId = parseInt(import.meta.env.VITE_UNLAYER_PROJECT_ID);

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

const form = useForm({
  name: props.campaign.name,
  subject: props.campaign.subject,
  preview_text: props.campaign.preview_text,
  template_id: props.campaign.template_id,
  content: props.campaign.content,
  design: props.campaign.design,
  from_name: props.campaign.from_name,
  from_email: props.campaign.from_email,
  reply_to: props.campaign.reply_to,
  list_ids: props.campaign.list_ids,
  scheduled_at: props.campaign.scheduled_at,
  merge_tags: props.campaign.merge_tags || {},
  status: props.campaign.status
});

const canEdit = computed(() => {
  return ['draft', 'sent', 'completed'].includes(props.campaign.status);
});

const canChangeTemplate = computed(() => {
  return props.campaign.status === 'draft';
});

const canChangeStatus = computed(() => {
  return ['archive'].includes(props.campaign.status);
});

const purgedMergeTags = () => {
  form.merge_tags = Object.entries(form.merge_tags).reduce((acc, [key, tag]) => {
    if (tag.name && tag.value) {
      acc[key] = {
        name: tag.name,
        value: tag.value,
        required: tag.required || false
      };
    }
    return acc;
  }, {});
};

const editorLoaded = () => {
  if (form.design) {
    try {
      const designData = typeof form.design === 'string'
        ? JSON.parse(form.design)
        : form.design

      if (designData.body) {
        // const flattenedTags = Object.values(props.mergeTags).flat();
        const mergeTags = Object.entries(form.merge_tags).reduce((acc, [key, tag]) => ({
          ...acc,
          [key]: {
            name: tag.name,
            value: `{{${key}}}`,
            required: tag.required
          }
        }), {});

        /*campaignEditor.value?.editor.setMergeTags(
          flattenedTags.reduce((acc, tag) => ({
            ...acc,
            [tag.key]: {
              name: tag.name,
              value: `{{${tag.key}}}`,
              required: tag.required
            }
          }), {})
        );*/

        campaignEditor.value?.editor.setMergeTags(mergeTags);

        campaignEditor.value.editor.loadDesign(JSON.parse(JSON.stringify(designData)));
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

        campaignEditor.value.editor.loadDesign(design);
      }

      // Set merge tags
      campaignEditor.value?.editor.setMergeTags(
        props.mergeTags.reduce((acc, tag) => ({
          ...acc,
          [tag.key]: {
            name: tag.name,
            value: `{{${tag.key}}}`,
            required: tag.required
          }
        }), {})
      );
    } catch (error) {
      console.error('Error loading design:', error);
    }
  }
};

const saveDesign = async () => {
  if (!canEdit.value) {
    toast.error('Cannot edit campaign in current status');
    return;
  }

  try {
    purgedMergeTags()

    const design = await new Promise((resolve) => {
      campaignEditor.value.editor?.saveDesign((design) => {
        resolve(design);
      });
    });

    const { html } = await new Promise((resolve) => {
      campaignEditor.value.editor?.exportHtml((data) => resolve(data));
    });

    form.design = design;
    form.content = html;

    // Update preview text if available
    if (form.preview_text) {
      campaignEditor.value.editor?.setBodyValues({
        preheaderText: form.preview_text,
      });
    }
  } catch (error) {
    console.error('Error saving design:', error);
  }
};

const submit = async () => {
  if (!canEdit.value) {
    toast.error('Cannot update campaign in current status');
    return;
  }

  await saveDesign();

  form.put(route('app.campaigns.update', props.campaign.uuid), {
    onSuccess: () => {
      toast.success('Campaign updated', {
        description: 'Your campaign has been updated successfully.',
      });
    },
    onError: (err) => {
      console.log('Update error:', err);
      toast.error('Failed to update campaign', {
        description: err.response?.data?.message || 'An error occurred while updating the campaign.',
      });
    },
  });
};

const breadcrumbs: BreadcrumbItemType = [
  { title: usePage().props.auth.current_team.name, href: route('dashboard') },
  { title: 'Campaigns', href: route('app.campaigns.index') },
  { title: props.campaign.name, href: '#' },
];

const loadPreview = async () => {
  try {
    const response = await axios.get(route('api.campaigns.preview', props.campaign.uuid));
    previewHtml.value = response.data.html;
  } catch (error) {
    toast.error('Failed to load preview');
  }
};

watch(() => activeTab.value, (newTab) => {
  if (newTab === 'preview') {
    loadPreview();
  }
});

// Watch for template changes (only in draft mode)
watch(() => form.template_id, async (newId, oldId) => {
  if (!canChangeTemplate.value) {
    form.template_id = oldId;
    toast.error('Cannot change template in current status');
    return;
  }

  if (newId && campaignEditor.value?.editor) {
    try {
      const { data } = await axios.get(route('api.templates.show', newId));
      const template = data.template;

      // Update merge tags from template
      form.merge_tags = template.merge_tags;

      // Update design and content
      const design = typeof template.design === 'string'
        ? JSON.parse(template.design)
        : template.design;

      campaignEditor.value.editor.loadDesign(design);
      form.content = template.content;

      // Set new merge tags in editor
      const mergeTags = Object.entries(template.merge_tags).reduce((acc, [key, tag]) => ({
        ...acc,
        [key]: {
          name: tag.name,
          value: `{{${key}}}`,
          required: tag.required
        }
      }), {});

      campaignEditor.value.editor.setMergeTags(mergeTags);
    } catch (error) {
      console.error('Error loading template:', error);
      toast.error('Failed to load template');
    }
  }
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head>
      <title>Edit Campaign - {{ campaign.name }}</title>
      <meta name="description" content="Edit your email campaign details" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <meta name="theme-color" content="#ffffff" />
    </Head>

    <div class="p-6 max-w-4xl">
      <div v-if="!canEdit" class="mb-4 p-4 bg-warning/10 rounded-lg">
        <p class="text-warning-foreground">
          This campaign cannot be edited in its current status ({{ campaign.status }})
        </p>
      </div>

      <section>
        <Heading title="Edit Campaign" description="Update your email campaign details" />

        <Button
          :as="ModalLink"
          size="sm" :href="route('app.campaigns.preview', campaign.uuid)">
          Preview
        </Button>
      </section>

      <Tabs v-model="activeTab" class="space-y-4">

        <TabsList>
          <TabsTrigger value="edit">Edit</TabsTrigger>
          <TabsTrigger value="preview">Preview</TabsTrigger>
        </TabsList>

        <TabsContent value="edit">
          <Card>
            <CardHeader>
              <CardTitle>
                Campaign Details
              </CardTitle>

              <Button @click.prevent="submit">Update</Button>
            </CardHeader>

            <CardContent>
              <form>
                <div class="mb-4">
                  <Label>Name</Label>
                  <Input v-model="form.name" type="text" required />
                </div>

                <div class="mb-4">
                  <Label>Subject</Label>
                  <Input v-model="form.subject" type="text" required />
                </div>

                <div class="mb-4">
                  <Label>Preview Text</Label>
                  <Input v-model="form.preview_text" type="text" />
                </div>

                <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                  <div v-if="canChangeTemplate" class="mb-4">
                    <Label>Template</Label>
                    <Select v-model="form.template_id" required>
                      <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select a template" />
                      </SelectTrigger>

                      <SelectContent>
                        <SelectItem v-for="template in props.templates" :key="template.id" :value="template.id">
                          {{ template.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <div class="mb-4">
                    <Label>Mailing List</Label>
                    <Select v-model="form.list_ids" required>
                      <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select mailing lists" />
                      </SelectTrigger>

                      <SelectContent>
                        <SelectItem v-for="list in props.lists" :key="list.id" :value="list.id">
                          {{ list.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                </section>

                <Separator />

                <!-- Available Merge Tags Section -->
                <div class="my-4">
                  <h2>Available Merge Tags</h2>
                  <div class="grid gap-4">
                      <div v-for="(tags, category) in props.mergeTags" :key="category">
                        <h3 class="font-medium mb-2 capitalize">{{ category }}</h3>
                        <div class="grid gap-2">
                          <div v-for="tag in tags" :key="tag.key"
                               class="flex justify-between items-center p-2 bg-muted rounded-md">
                            <div>
                              <code class="text-sm" v-text="`{{${tag.key}}}`"></code>
                              <p class="text-sm text-muted-foreground">{{ tag.description }}</p>
                            </div>
                            <Badge v-if="tag.required" variant="secondary">Required</Badge>
                          </div>
                        </div>
                      </div>
                    </div>
                </div>

                <Separator />

                <section class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                  <div class="mb-4">
                    <Label>From Name</Label>
                    <Input v-model="form.from_name" type="text" required />
                  </div>

                  <div class="mb-4">
                    <Label>From Email</Label>
                    <Input v-model="form.from_email" type="email" required />
                  </div>
                </section>

                <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                  <div class="mb-4">
                    <Label>Reply-To Email</Label>
                    <Input v-model="form.reply_to" type="email" />
                  </div>

                  <div class="mb-4">
                    <Label>Scheduled At</Label>
                    <Input v-model="form.scheduled_at" type="datetime-local" />
                  </div>

                </section>

                <div class="mb-4">
                  <Label>Content</Label>

                  <div class="editor-container">
                    <EmailEditor
                      ref="campaignEditor"
                      :projectId="unlayerProjectId"
                      :appearance="appearance"
                      displayMode="email"
                      :tools="tools"
                      minHeight="700px"
                      v-on:load="editorLoaded"
                    />
                  </div>
                </div>
              </form>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="preview">
          <Card>
            <CardHeader>
              <CardTitle>Preview</CardTitle>
            </CardHeader>

            <CardContent>
              <div v-html="previewHtml" class="prose max-w-none"></div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
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
