<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Building2, AlertCircle, MailIcon } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { type Organization, type BreadcrumbItem } from '@/types';

interface Props {
  organization: Organization;
  availableSizes: string[];
  availableIndustries: string[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Organizations', href: route('organizations.index') },
  { title: props.organization.name, href: route('organizations.show', props.organization.uuid) },
  { title: 'General', href: route('organization.settings.general', props.organization.uuid) }
];

const form = useForm({
  name: props.organization.name,
  size: props.organization.size,
  industry: props.organization.industry,
  website: props.organization.website || '',
  phone: props.organization.phone || '',
  default_from_name: props.organization.default_from_name || '',
  default_from_email: props.organization.default_from_email || '',
  default_reply_to: props.organization.default_reply_to || ''
});

const submit = () => {
  form.put(route('organization.settings.general', props.organization.uuid));
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">

    <Head title="Organization Settings" />

    <SettingsLayout type="organization"
                    title="Organization Settings"
                    description="Manage your organization preferences">
      <Alert v-if="Object.keys(form.errors).length > 0"
             variant="destructive">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>
          Please check the form for errors and try again.
        </AlertDescription>
      </Alert>

      <form @submit.prevent="submit"
            class="space-y-8">
        <!-- Organization Details -->
        <div class="space-y-4">
          <div class="flex items-center gap-2">
            <Building2 class="h-5 w-5 text-muted-foreground" />
            <h3 class="font-semibold text-lg">Organization Details</h3>
          </div>

          <div class="grid gap-6">
            <div class="grid gap-2">
              <Label for="name">Organization name</Label>
              <Input id="name"
                     v-model="form.name"
                     type="text"
                     :error="form.errors.name" />
            </div>

            <section class="grid grid-cols-1 sm:grid-cols-2 gap-6">

              <div class="grid gap-2">
                <Label for="size">Company size</Label>
                <Select v-model="form.size">
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select size" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="size in availableSizes"
                                :key="size"
                                :value="size">
                      {{ size }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="grid gap-2">
                <Label for="industry">Industry</Label>
                <Select v-model="form.industry">
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select industry" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="industry in availableIndustries"
                                :key="industry"
                                :value="industry">
                      {{ industry }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

            </section>

            <div class="grid gap-2">
              <Label for="website">Website</Label>
              <Input id="website"
                     v-model="form.website"
                     type="url"
                     :error="form.errors.website" />
            </div>

            <div class="grid gap-2">
              <Label for="phone">Phone</Label>
              <Input id="phone"
                     v-model="form.phone"
                     type="tel"
                     :error="form.errors.phone" />
            </div>
          </div>
        </div>

        <Separator />

        <!-- Email Settings -->
        <div class="space-y-4">
          <div class="flex items-center gap-2">
            <MailIcon class="h-5 w-5 text-muted-foreground" />
            <h3 class="font-semibold text-lg">Email Settings</h3>
          </div>

          <div class="grid gap-6">
            <div class="grid gap-2">
              <Label for="default_from_name">Default sender name</Label>
              <Input id="default_from_name"
                     v-model="form.default_from_name"
                     type="text"
                     :error="form.errors.default_from_name" />
            </div>

            <div class="grid gap-2">
              <Label for="default_from_email">Default sender email</Label>
              <Input id="default_from_email"
                     v-model="form.default_from_email"
                     type="email"
                     :error="form.errors.default_from_email" />
            </div>

            <div class="grid gap-2">
              <Label for="default_reply_to">Default reply-to email</Label>
              <Input id="default_reply_to"
                     v-model="form.default_reply_to"
                     type="email"
                     :error="form.errors.default_reply_to" />
            </div>
          </div>
        </div>

        <div class="flex justify-end">
          <Button type="submit"
                  :disabled="form.processing">
            Save changes
          </Button>
        </div>
      </form>
    </SettingsLayout>
  </AppLayout>
</template>
