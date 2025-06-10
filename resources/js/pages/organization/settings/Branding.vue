<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import LogoUploader from '@/components/LogoUploader.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type Media, type Organization } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, MailIcon, PaletteIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import HeadingSmall from '@/components/HeadingSmall.vue';

interface Props {
  organization: Organization & {
    media: Media[];
  };
  brandingConfig: {
    maxLogoSize: number;
    allowedTypes: string[];
    minDimensions: { width: number; height: number };
    maxDimensions: { width: number; height: number };
  };
  logo: string;
  logoThumbnail: string;
  logoEmail: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: props.organization.name, href: route('dashboard') },
  { title: 'Branding Settings', href: route('organization.settings.branding.edit') },
];

const previewUrl = ref<string>(props.logo || '');

const form = useForm({
  primary_color: props.organization.primary_color || '#4F46E5',
  secondary_color: props.organization.secondary_color || '#7C3AED',
  email_header: props.organization.email_header || '',
  email_footer: props.organization.email_footer || '',
  logo: null as File | null,
});

const handleLogoError = (message: string) => {
  form.errors.logo = message;
};

const deleteLogo = () => {
  if (!props.logo) return;

  router.delete(route('organization.settings.branding.logo.delete', props.organization.uuid), {
    preserveScroll: true,
  });
};

const regenerateConversions = () => {
  if (!props.logo) return;

  router.post(
    route('organization.settings.branding.logo.regenerate', props.organization.uuid),
    {},
    {
      preserveScroll: true,
    },
  );
};

const submit = () => {
  form.post(route('organization.settings.branding.update', props.organization.uuid), {
    preserveScroll: true,
    onSuccess: () => {
      if (form.logo) {
        URL.revokeObjectURL(previewUrl.value);
      }
      form.reset('logo');
    },
  });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Branding Settings" />

    <SettingsLayout
      type="organization">
      <Alert v-if="Object.keys(form.errors).length > 0" variant="destructive">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription> Please check the form for errors and try again. </AlertDescription>
      </Alert>

      <HeadingSmall
        title="Branding Settings"
        description="Customize your organization's appearance"
      />

      <form @submit.prevent="submit" class="space-y-8">
        <!-- Logo Section -->
        <LogoUploader
          v-model="form.logo"
          :current-logo="props.logo"
          :config="props.brandingConfig"
          :error="form.errors.logo"
          @error="handleLogoError"
          @delete="deleteLogo"
          @regenerate="regenerateConversions"
        />

        <Separator />

        <!-- Colors Section -->
        <div class="space-y-4">
          <div class="flex items-center gap-2">
            <PaletteIcon class="text-muted-foreground h-5 w-5" />
            <h3 class="text-lg font-semibold">Brand Colors</h3>
          </div>
          <div class="grid gap-6">
            <div class="grid gap-2">
              <Label for="primary_color">Primary Color</Label>
              <div class="flex gap-2">
                <Input id="primary_color" v-model="form.primary_color" type="color" class="h-10 w-12 p-1" />
                <Input v-model="form.primary_color" type="text" :error="form.errors.primary_color" />
              </div>
            </div>

            <div class="grid gap-2">
              <Label for="secondary_color">Secondary Color</Label>
              <div class="flex gap-2">
                <Input id="secondary_color" v-model="form.secondary_color" type="color" class="h-10 w-12 p-1" />
                <Input v-model="form.secondary_color" type="text" :error="form.errors.secondary_color" />
              </div>
            </div>
          </div>
        </div>

        <Separator />

        <!-- Email Templates Section -->
        <div class="space-y-4">
          <div class="flex items-center gap-2">
            <MailIcon class="text-muted-foreground h-5 w-5" />
            <h3 class="text-lg font-semibold">Email Templates</h3>
          </div>

          <p class="text-muted-foreground text-sm">
            Customize the header and footer that appear in all emails sent from your organization. You can use HTML and the following variables:
            {organization_name}, {organization_address}, {unsubscribe_link}
          </p>

          <section class="mt-8 grid gap-y-8">
            <div class="grid gap-2">
              <Label for="email_header">Email Header Template</Label>
              <p class="text-muted-foreground text-xs">This appears at the top of all emails. Typically includes your logo and organization name.</p>
              <Textarea
                id="email_header"
                v-model="form.email_header"
                rows="6"
                placeholder="
                <div style='text-align: center;'>
                  <img
                    src='{organization_logo}'
                    alt='{organization_name}'
                    style='max-width: 200px;'>

                  <h1>{organization_name}</h1>
                </div>
                "
              />

              <p v-if="!form.errors.email_header" class="text-muted-foreground text-xs">
                Tip: Keep the header simple and focused on your brand identity.
              </p>

              <InputError :message="form.errors.email_header" />
            </div>

            <div class="grid gap-2">
              <Label for="email_footer">Email Footer Template</Label>

              <p class="text-muted-foreground text-xs">
                This appears at the bottom of all emails. Usually contains contact information and unsubscribe link.
              </p>

              <Textarea
                id="email_footer"
                v-model="form.email_footer"
                rows="6"
                placeholder="
                <div
                  style='text-align: center; color: #666;'>
                  <p>{organization_name}</p>
                  <p>{organization_address}</p>
                  <p>
                    <a href='{unsubscribe_link}'>
                      Unsubscribe
                    </a>
                  </p>
                </div>
                "
              />

              <p v-if="!form.errors.email_footer" class="text-muted-foreground text-xs">
                Note: The unsubscribe link is required by law for marketing emails.
              </p>

              <InputError :message="form.errors.email_footer" />
            </div>
          </section>
        </div>

        <div class="flex justify-end">
          <Button type="submit" :disabled="form.processing"> Save changes </Button>
        </div>
      </form>
    </SettingsLayout>
  </AppLayout>
</template>
