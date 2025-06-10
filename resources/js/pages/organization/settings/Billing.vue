<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type Organization, type Plan, type Subscription } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, CreditCard } from 'lucide-vue-next';
import HeadingSmall from '@/components/HeadingSmall.vue';

interface Props {
  organization: Organization;
  currentPlan?: Plan;
  subscription?: Subscription;
  availablePlans: Plan[];
  error?: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: props.organization.name, href: route('dashboard') },
  { title: 'Billing', href: route('organization.settings.billing') },
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

    <SettingsLayout type="organization">
      <Alert v-if="error" variant="destructive">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>{{ error }}</AlertDescription>
      </Alert>

      <HeadingSmall
        title="Billing Settings"
        description="Manage your subscription and billing information"
      />

      <!-- Current Plan -->
      <Card>
        <CardHeader>
          <div class="flex items-start gap-2">
            <CreditCard class="text-muted-foreground h-5 w-5" />
            <div>
              <CardTitle>Current Plan</CardTitle>
              <CardDescription> Your organization is currently on the {{ currentPlan?.name }} plan </CardDescription>
            </div>
          </div>
        </CardHeader>

        <CardContent>
          <div class="text-2xl font-bold">
            {{ formatPrice(currentPlan?.price) }}
            <span class="text-muted-foreground text-sm font-normal">/month</span>
          </div>
          <div class="mt-4 space-y-2">
            <div v-for="feature in getPlanFeatures(currentPlan)" :key="feature" class="flex items-center gap-2">
              <CheckCircle2 class="text-primary h-4 w-4" />
              {{ feature }}
            </div>
          </div>
        </CardContent>
        <CardFooter>
          <Button v-if="subscription.status === 'active'" variant="outline"> Cancel Subscription </Button>
        </CardFooter>
      </Card>

      <!-- Available Plans -->
      <div class="mt-8">
        <h3 class="mb-4 text-lg font-semibold">Available Plans</h3>
        <div class="grid gap-6 md:grid-cols-2">
          <Card
            v-for="plan in availablePlans"
            :key="plan.id"
            :class="{
              'border-primary': plan.id === currentPlan?.id,
            }"
          >
            <CardHeader>
              <CardTitle>{{ plan.name }}</CardTitle>
              <CardDescription>{{ plan.description }}</CardDescription>
            </CardHeader>

            <CardContent>
              <div class="text-2xl font-bold">
                {{ formatPrice(plan.price) }}
                <span class="text-muted-foreground text-sm font-normal">/month</span>
              </div>
              <div class="mt-4 space-y-2">
                <div v-for="feature in getPlanFeatures(plan)" :key="feature" class="flex items-center gap-2">
                  <CheckCircle2 class="text-primary h-4 w-4" />
                  {{ feature }}
                </div>
              </div>
            </CardContent>

            <div class="flex-1"></div>

            <CardFooter>
              <Button v-if="plan.id !== currentPlan?.id" as-child class="w-full">
                <Link :href="route('organization.settings.billing.subscribe', [organization.uuid, plan.uuid])"> Switch to {{ plan.name }} </Link>
              </Button>
              <Button v-else disabled class="w-full"> Current Plan </Button>
            </CardFooter>
          </Card>
        </div>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
