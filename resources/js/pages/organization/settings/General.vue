<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type Organization } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertCircle, Building2, MailIcon } from 'lucide-vue-next';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';

interface Props {
  organization: Organization;
  availableSizes: string[];
  availableIndustries: string[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: props.organization.name, href: route('dashboard') },
  { title: 'General Settings', href: route('organization.settings.general.edit') },
];

const form = useForm({
  name: props.organization.name,
  size: props.organization.size,
  industry: props.organization.industry,
  website: props.organization.website || '',
  phone: props.organization.phone || '',
  default_from_name: props.organization.default_from_name || '',
  default_from_email: props.organization.default_from_email || '',
  default_reply_to: props.organization.default_reply_to || '',
});

const submit = () => {
  form.put(route('organization.settings.general.update', props.organization.uuid), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('default_from_name', 'default_from_email', 'default_reply_to');
    },
  });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Organization Settings" />

    <SettingsLayout
      type="organization">
      <Alert v-if="Object.keys(form.errors).length > 0" variant="destructive">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription> Please check the form for errors and try again. </AlertDescription>
      </Alert>

      <HeadingSmall
        title="General Brand Settings"
        description="Update your organization preferences and email settings"
      />

      <form @submit.prevent="submit" class="space-y-8">
        <!-- Organization Details -->
        <div class="space-y-4">
          <div class="flex items-center gap-2">
            <Building2 class="text-muted-foreground h-5 w-5" />
            <h3 class="text-lg font-semibold">Organization Details</h3>
          </div>

          <div class="grid gap-6">
            <div class="grid gap-y-2">
              <Label for="name">Organization name</Label>
              <Input
                id="name"
                name="company_name"
                v-model="form.name"
                type="text"
              />

              <InputError :message="form.errors.name" />
            </div>

            <section class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <div class="grid gap-y-2">
                <Label for="size">Company size</Label>
                <Select v-model="form.size">
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select size" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="size in availableSizes" :key="size" :value="size">
                      {{ size }}
                    </SelectItem>
                  </SelectContent>
                </Select>

                <InputError :message="form.errors.size" />
              </div>

              <div class="grid gap-y-2">
                <Label for="industry">Industry</Label>
                <Select v-model="form.industry">
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select industry" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="industry in availableIndustries"
                      :key="industry" :value="industry">
                      <span class="capitalize">{{ industry }}</span>
                    </SelectItem>
                  </SelectContent>
                </Select>

                <InputError :message="form.errors.industry" />
              </div>
            </section>

            <div class="grid gap-y-2">
              <Label for="website">Website</Label>
              <Input id="website" v-model="form.website" type="url" />

              <InputError :message="form.errors.website" />
            </div>

            <div class="grid gap-2">
              <Label for="phone">Phone</Label>
              <Input id="phone" v-model="form.phone" type="tel" />

              <InputError :message="form.errors.phone" />
            </div>
          </div>
        </div>

        <Separator />

        <!-- Email Settings -->
        <div class="space-y-4">
          <div class="flex items-center gap-2">
            <MailIcon class="text-muted-foreground h-5 w-5" />
            <h3 class="text-lg font-semibold">Email Settings</h3>
          </div>

          <div class="grid gap-6">
            <div class="grid gap-2">
              <Label for="default_from_name">Default sender name</Label>
              <Input
                id="default_from_name"
                v-model="form.default_from_name"
              />

              <InputError :message="form.errors.default_from_name" />
            </div>

            <div class="grid gap-2">
              <Label for="default_from_email">Default sender email</Label>
              <Input
                id="default_from_email"
                v-model="form.default_from_email"
                type="email"
              />

              <InputError :message="form.errors.default_from_email" />
            </div>

            <div class="grid gap-2">
              <Label for="default_reply_to">Default reply-to email</Label>
              <Input
                id="default_reply_to"
                v-model="form.default_reply_to"
                type="email"
              />

              <InputError :message="form.errors.default_reply_to" />
            </div>
          </div>
        </div>

        <div class="flex justify-end">
          <Button type="submit" :disabled="form.processing"> Save changes </Button>
        </div>
      </form>
    </SettingsLayout>
  </AppLayout>
</template>
