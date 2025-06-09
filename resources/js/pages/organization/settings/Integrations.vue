<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Mail, CreditCard, AlertCircle, SendIcon, StickerIcon } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { type Organization, type BreadcrumbItem } from '@/types';
import InputError from '@/components/InputError.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Props {
  organization: Organization;
  integrations?: {
    smtp: {
      enabled: boolean;
      host?: string;
      port?: number;
      username?: string;
      password?: string;
      encryption?: 'tls' | 'ssl' | null;
      from_address?: string;
      from_name?: string;
    };
    sendgrid: {
      enabled: boolean;
      api_key?: string;
    };
    mailgun: {
      enabled: boolean;
      api_key?: string;
      domain?: string;
    };
    stripe: {
      enabled: boolean;
      public_key?: string;
      secret_key?: string;
    };
  };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Organizations', href: route('organizations.index') },
  { title: props.organization.name, href: route('organizations.show', props.organization.uuid) },
  { title: 'Integrations', href: route('organization.settings.integrations', props.organization.uuid) }
];

const form = useForm({
  integrations: {
    smtp: {
      enabled: props.integrations?.smtp?.enabled || false,
      host: props.integrations?.smtp?.host || '',
      port: props.integrations?.smtp?.port || 587,
      username: props.integrations?.smtp?.username || '',
      password: props.integrations?.smtp?.password || '',
      encryption: props.integrations?.smtp?.encryption || 'tls',
      from_address: props.integrations?.smtp?.from_address || '',
      from_name: props.integrations?.smtp?.from_name || ''
    },
    sendgrid: {
      enabled: props.integrations?.sendgrid.enabled || false,
      api_key: props.integrations?.sendgrid.api_key || '',
    },
    mailgun: {
      enabled: props.integrations?.mailgun.enabled || false,
      api_key: props.integrations?.mailgun.api_key || '',
      domain: props.integrations?.mailgun.domain || '',
    },
    stripe: {
      enabled: props.integrations?.stripe.enabled || false,
      public_key: props.integrations?.stripe.public_key || '',
      secret_key: props.integrations?.stripe.secret_key || '',
    },
  },
});

const submit = () => {
  form.put(route('organization.settings.integrations', props.organization.uuid));
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">

    <Head title="Integration Settings" />

    <SettingsLayout type="organization"
                    title="Integration Settings"
                    description="Configure external service integrations">
      <Alert v-if="Object.keys(form.errors).length > 0"
             variant="destructive">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>
          Please check the form for errors and try again.
        </AlertDescription>
      </Alert>

      <form @submit.prevent="submit"
            class="space-y-8">
        <!-- Email Service Integrations -->
        <div class="space-y-6">
          <h3 class="font-semibold text-lg">Email Services</h3>

          <!-- SMTP Integration -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div class="flex items-start gap-2">
                  <StickerIcon class="h-5 w-5 text-muted-foreground" />
                  <div>
                    <CardTitle>SMTP</CardTitle>
                    <CardDescription>Configure SMTP settings for email delivery</CardDescription>
                  </div>
                </div>
                <Switch v-model="form.integrations.smtp.enabled" />
              </div>
            </CardHeader>
            <CardContent v-if="form.integrations.smtp.enabled">
              <div class="grid gap-4">
                <div class="grid gap-2">
                  <Label for="smtp_host">Host</Label>
                  <Input id="smtp_host"
                         v-model="form.integrations.smtp.host"
                         type="text"
                  />

                  <InputError :message="form.errors['integrations.smtp.host']" />
                </div>
                <div class="grid gap-2">
                  <Label for="smtp_port">Port</Label>
                  <Input id="smtp_port"
                         v-model="form.integrations.smtp.port"
                         type="number"
                  />

                  <InputError :message="form.errors['integrations.smtp.port']" />
                </div>

                <div class="grid gap-2">
                  <Label for="smtp_username">Username</Label>
                  <Input
                    id="smtp_username"
                    v-model="form.integrations.smtp.username"
                    type="text"
                  />

                  <InputError :message="form.errors['integrations.smtp.username']" />
                </div>
                <div class="grid gap-2">
                  <Label for="smtp_password">Password</Label>
                  <Input id="smtp_password"
                         v-model="form.integrations.smtp.password"
                         type="password"
                  />

                  <InputError :message="form.errors['integrations.smtp.password']" />
                </div>
                <div class="grid gap-2">
                  <Label for="smtp_encryption">Encryption</Label>
                  <Select
                    id="smtp_encryption"
                    v-model="form.integrations.smtp.encryption">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Select encryption" />
                    </SelectTrigger>

                    <SelectContent>
                      <SelectItem value="tls">TLS</SelectItem>
                      <SelectItem value="ssl">SSL</SelectItem>
                      <SelectItem :value="null">None</SelectItem>
                    </SelectContent>
                  </Select>

                  <InputError :message="form.errors['integrations.smtp.encryption']" />
                </div>

                <div class="grid gap-2">
                  <Label for="smtp_from_address">From Address</Label>
                  <Input id="smtp_from_address"
                         v-model="form.integrations.smtp.from_address"
                         type="email"
                  />

                  <InputError :message="form.errors['integrations.smtp.from_address']" />
                </div>
                <div class="grid gap-2">
                  <Label for="smtp_from_name">From Name</Label>
                  <Input id="smtp_from_name"
                         v-model="form.integrations.smtp.from_name"
                         type="text"
                  />

                  <InputError :message="form.errors['integrations.smtp.from_name']" />
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- SendGrid Integration -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div class="flex items-start gap-2">
                  <SendIcon class="h-5 w-5 text-muted-foreground" />
                  <div>
                    <CardTitle>SendGrid</CardTitle>
                    <CardDescription>Configure SendGrid for email delivery</CardDescription>
                  </div>
                </div>
                <Switch v-model="form.integrations.sendgrid.enabled" />
              </div>
            </CardHeader>
            <CardContent v-if="form.integrations.sendgrid.enabled">
              <div class="grid gap-4">
                <div class="grid gap-2">
                  <Label for="sendgrid_api_key">API Key</Label>
                  <Input id="sendgrid_api_key"
                         v-model="form.integrations.sendgrid.api_key"
                         placeholder="SG.xxxxxxx"
                         type="password"
                  />

                  <InputError :message="form.errors['integrations.sendgrid.api_key']" />
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Mailgun Integration -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div class="flex items-start gap-2">
                  <Mail class="h-5 w-5 text-muted-foreground" />
                  <div>
                    <CardTitle>Mailgun</CardTitle>
                    <CardDescription>Configure Mailgun for email delivery</CardDescription>
                  </div>
                </div>
                <Switch v-model="form.integrations.mailgun.enabled" />
              </div>
            </CardHeader>
            <CardContent v-if="form.integrations.mailgun.enabled">
              <div class="grid gap-4">
                <div class="grid gap-2">
                  <Label for="mailgun_api_key">API Key</Label>
                  <Input id="mailgun_api_key"
                         v-model="form.integrations.mailgun.api_key"
                         type="password"
                  />

                  <InputError :message="form.errors['integrations.mailgun.api_key']" />
                </div>
                <div class="grid gap-2">
                  <Label for="mailgun_domain">Domain</Label>
                  <Input id="mailgun_domain"
                         v-model="form.integrations.mailgun.domain"
                         type="text"
                  />

                  <InputError :message="form.errors['integrations.mailgun.domain']" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Payment Service Integrations -->
        <div class="space-y-6">
          <h3 class="font-semibold text-lg">Payment Services</h3>

          <!-- Stripe Integration -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div class="flex items-start gap-2">
                  <CreditCard class="h-5 w-5 text-muted-foreground" />
                  <div>
                    <CardTitle>Stripe</CardTitle>
                    <CardDescription>Configure Stripe for payment processing</CardDescription>
                  </div>
                </div>
                <Switch v-model="form.integrations.stripe.enabled" />
              </div>
            </CardHeader>
            <CardContent v-if="form.integrations.stripe.enabled">
              <div class="grid gap-4">
                <div class="grid gap-2">
                  <Label for="stripe_public_key">Public Key</Label>
                  <Input id="stripe_public_key"
                         v-model="form.integrations.stripe.public_key"
                         type="text"
                  />

                  <InputError :message="form.errors['integrations.stripe.public_key']" />
                </div>

                <div class="grid gap-2">
                  <Label for="stripe_secret_key">Secret Key</Label>
                  <Input id="stripe_secret_key"
                         v-model="form.integrations.stripe.secret_key"
                         type="password"
                  />

                  <InputError :message="form.errors['integrations.stripe.secret_key']" />
                </div>
              </div>
            </CardContent>
          </Card>
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
