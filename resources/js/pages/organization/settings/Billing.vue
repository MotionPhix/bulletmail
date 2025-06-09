<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CreditCard, CheckCircle2, AlertCircle } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { type Organization, type Plan, type Subscription, type BreadcrumbItem } from '@/types';

interface Props {
  organization: Organization;
  currentPlan?: Plan;
  subscription?: Subscription;
  availablePlans: Plan[];
  error?: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Organizations', href: route('organizations.index') },
  { title: props.organization.name, href: route('organizations.show', props.organization.uuid) },
  { title: 'Billing', href: route('organization.settings.billing', props.organization.uuid) }
];

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(price / 100);
};

const getPlanFeatures = (plan: Plan) => {
  return [
    `${plan.features.campaign_limit} campaigns per month`,
    `${plan.features.subscriber_limit.toLocaleString()} subscribers`,
    `${plan.features.monthly_email_limit.toLocaleString()} monthly emails`,
    plan.features.can_schedule_campaigns ? 'Campaign scheduling' : '',
    plan.features.can_export_data ? 'Data export' : '',
    plan.features.support_type === 'priority' ? 'Priority support' : 'Community support',
  ].filter(Boolean);
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">

    <Head title="Billing Settings" />

    <SettingsLayout type="organization"
                    title="Billing Settings"
                    description="Manage your subscription and billing information">
      <Alert v-if="error"
             variant="destructive">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>{{ error }}</AlertDescription>
      </Alert>

      <!-- Current Plan -->
      <Card>
        <CardHeader>
          <div class="flex items-start gap-2">
            <CreditCard class="h-5 w-5 text-muted-foreground" />
            <div>
              <CardTitle>Current Plan</CardTitle>
              <CardDescription>
                Your organization is currently on the {{ currentPlan?.name }} plan
              </CardDescription>
            </div>
          </div>
        </CardHeader>

        <CardContent>
          <div class="text-2xl font-bold">
            {{ formatPrice(currentPlan?.price) }}
            <span class="text-sm text-muted-foreground font-normal">/month</span>
          </div>
          <div class="mt-4 space-y-2">
            <div v-for="feature in getPlanFeatures(currentPlan)"
                 :key="feature"
                 class="flex items-center gap-2">
              <CheckCircle2 class="h-4 w-4 text-primary" />
              {{ feature }}
            </div>
          </div>
        </CardContent>
        <CardFooter>
          <Button v-if="subscription.status === 'active'"
                  variant="outline">
            Cancel Subscription
          </Button>
        </CardFooter>
      </Card>

      <!-- Available Plans -->
      <div class="mt-8">
        <h3 class="font-semibold text-lg mb-4">Available Plans</h3>
        <div class="grid gap-6 md:grid-cols-2">
          <Card v-for="plan in availablePlans"
                :key="plan.id"
                :class="{
                  'border-primary': plan.id === currentPlan?.id
                }">
            <CardHeader>
              <CardTitle>{{ plan.name }}</CardTitle>
              <CardDescription>{{ plan.description }}</CardDescription>
            </CardHeader>

            <CardContent>
              <div class="text-2xl font-bold">
                {{ formatPrice(plan.price) }}
                <span class="text-sm text-muted-foreground font-normal">/month</span>
              </div>
              <div class="mt-4 space-y-2">
                <div v-for="feature in getPlanFeatures(plan)"
                     :key="feature"
                     class="flex items-center gap-2">
                  <CheckCircle2 class="h-4 w-4 text-primary" />
                  {{ feature }}
                </div>
              </div>
            </CardContent>

            <div class="flex-1"></div>

            <CardFooter>
              <Button v-if="plan.id !== currentPlan?.id"
                      as-child
                      class="w-full">
                <Link :href="route('organization.settings.billing.subscribe', [organization.uuid, plan.uuid])">
                Switch to {{ plan.name }}
                </Link>
              </Button>
              <Button v-else
                      disabled
                      class="w-full">
                Current Plan
              </Button>
            </CardFooter>
          </Card>
        </div>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
