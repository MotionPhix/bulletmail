<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import VueApexCharts from 'vue3-apexcharts';
import { computed, ref } from 'vue';
import { format } from 'date-fns';
import { TrashIcon, ArrowLeftIcon } from 'lucide-vue-next';
import { ModalLink } from '@inertiaui/modal-vue';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';

interface Event {
  id: number;
  type: string;
  created_at: string;
  metadata: Record<string, any>;
}

interface Props {
  subscriber: {
    uuid: string;
    email: string;
    first_name: string;
    last_name: string;
    status: string;
    created_at: string;
    subscribed_at: string;
    unsubscribed_at: string | null;
    last_emailed_at: string | null;
    last_opened_at: string | null;
    last_clicked_at: string | null;
    emails_received: number;
    emails_opened: number;
    emails_clicked: number;
    engagement_score: number;
    average_open_rate: number;
    average_click_rate: number;
    lists: Array<{ id: number; name: string }>;
    events: Event[];
  };
  available_lists: Array<{ id: number; name: string }>
}

const props = defineProps<Props>();

const engagementChartOptions = computed(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    sparkline: { enabled: true }
  },
  stroke: { curve: 'smooth', width: 2 },
  colors: ['#10B981'],
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.7,
      opacityTo: 0.1
    }
  },
  tooltip: { theme: 'dark' }
}));

const engagementData = computed(() => [{
  name: 'Engagement',
  data: props.subscriber.events
    .filter(e => ['opened', 'clicked'].includes(e.type))
    .map(e => ({
      x: format(new Date(e.created_at), 'MMM dd'),
      y: 1
    }))
}]);

const selectedListId = ref<number>();

const getStatusColor = (status: string) => {
  return {
    subscribed: 'success',
    unsubscribed: 'destructive',
    bounced: 'warning',
    complained: 'destructive'
  }[status] || 'secondary';
};

const formatDate = (date: string | null) => {
  return date ? format(new Date(date), 'MMM dd, yyyy HH:mm') : 'Never';
};

const page = usePage().props

const breadcrumbs = computed(() => [
  {
    title: page.auth.current_team.name,
    href: route('dashboard'),
  },
  {
    title: 'Subscribers List',
    href: route('app.subscribers.index'),
  },
  {
    title: 'Subscribers Details',
    href: '#',
  },
]);

// Compute available lists (lists subscriber is not already in)
const availableLists = computed(() => {
  return props.available_lists.filter(list =>
    !props.subscriber.lists.some(subList => subList.id === list.id)
  );
});

/*const addToList = () => {
  if (!selectedListId.value) return;

  router.post(route('app.subscribers.lists.add', props.subscriber.uuid), {
    list_id: selectedListId.value
  }, {
    preserveScroll: true
  });

  selectedListId.value = undefined;
};*/

const addToList = (list: any) => {
  router.post(route('app.subscribers.lists.add', props.subscriber.uuid), {
    list_id: list.id
  }, {
    preserveScroll: true
  });
};

const removeFromList = (listId: number) => {
  router.delete(route('app.subscribers.lists.remove', {
    subscriber: props.subscriber.uuid,
    list: listId
  }), {
    preserveScroll: true
  });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="`Subscriber - ${subscriber.email}`" />

    <div class="max-w-4xl p-6">
      <!-- Header -->
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold">Subscriber Details</h1>
          <p class="text-muted-foreground">View detailed information and activity</p>
        </div>
        <div class="space-x-2">
          <Button
            variant="ghost"
            :as="Link"
            :href="route('app.subscribers.index')">
            <ArrowLeftIcon />
          </Button>

          <Button
            variant="outline"
            :as="ModalLink"
            :href="route('app.subscribers.edit', subscriber.uuid)">
            Edit
          </Button>
        </div>
      </div>

      <div class="grid gap-6 md:grid-cols-2">
        <!-- Basic Info -->
        <Card>
          <CardHeader>
            <CardTitle>Basic Information</CardTitle>
          </CardHeader>

          <CardContent>
            <dl class="divide-y">
              <div class="grid py-3">
                <dt class="text-sm font-medium text-muted-foreground">Email</dt>
                <dd class="text-sm">{{ subscriber.email }}</dd>
              </div>

              <div class="grid grid-cols-3 py-3">
                <dt class="text-sm font-medium text-muted-foreground">Name</dt>
                <dd class="col-span-2 text-sm">{{ subscriber.first_name }} {{ subscriber.last_name }}</dd>
              </div>

              <div class="grid grid-cols-3 py-3">
                <dt class="text-sm font-medium text-muted-foreground">Status</dt>
                <dd class="col-span-2">
                  <Badge
                    class="capitalize"
                    :variant="getStatusColor(subscriber.status)">
                    {{ subscriber.status }}
                  </Badge>
                </dd>
              </div>

              <div class="grid grid-cols-3 py-3">
                <dt class="text-sm font-medium text-muted-foreground">Joined</dt>
                <dd class="col-span-2 text-sm">{{ formatDate(subscriber.created_at) }}</dd>
              </div>
            </dl>
          </CardContent>
        </Card>

        <!-- Engagement Metrics -->
        <Card>
          <CardHeader>
            <CardTitle>Engagement Metrics</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="mb-4">
              <VueApexCharts
                type="area"
                height="100"
                :options="engagementChartOptions"
                :series="engagementData"
              />
            </div>

            <dl class="divide-y">
              <div class="grid py-3">
                <dt class="text-sm font-medium text-muted-foreground">Emails Received</dt>
                <dd class="text-sm">{{ subscriber.emails_received }} emails</dd>
              </div>

              <div class="grid py-3">
                <dt class="text-sm font-medium text-muted-foreground">Open Rate</dt>
                <dd class="text-sm">{{ subscriber.average_open_rate }}%</dd>
              </div>

              <div class="grid py-3">
                <dt class="text-sm font-medium text-muted-foreground">Click Rate</dt>
                <dd class="text-sm">{{ subscriber.average_click_rate }}%</dd>
              </div>

              <div class="grid py-3">
                <dt class="text-sm font-medium text-muted-foreground">Engagement Score</dt>
                <dd class="text-sm">{{ subscriber.engagement_score }}</dd>
              </div>
            </dl>
          </CardContent>
        </Card>

        <!-- Lists Section -->
        <Card class="mt-8 md:col-span-2">
          <CardHeader>
            <CardTitle class="flex items-center justify-between">
              <div>
                Mailing Lists
              </div>

              <DropdownMenu>
                <DropdownMenuTrigger as-child v-if="availableLists.length">
                  <Button variant="outline">
                    Add to list
                  </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end">
                  <DropdownMenuItem
                    @click="addToList(list)"
                    v-for="list in availableLists"
                    :key="list.id">
                    <span>{{ list.name }}</span>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </CardTitle>

            <CardDescription>
              Lists this subscriber belongs to
            </CardDescription>
          </CardHeader>

          <CardContent>
            <div v-if="subscriber.lists.length" class="space-y-2">
              <div
                v-for="list in subscriber.lists" :key="list.id"
                class="flex items-center justify-between p-2 bg-muted rounded-md">
                <span>{{ list.name }}</span>

                <Button
                  variant="ghost"
                  size="icon"
                  @click="removeFromList(list.id)">
                  <TrashIcon />
                </Button>
              </div>
            </div>

            <div
              v-else class="text-center text-muted-foreground py-4">
              Not subscribed to any lists
            </div>

            <!-- Add to List -->
<!--            <div class="mt-4 flex items-center gap-2">-->
<!--              <Select v-model="selectedListId">-->
<!--                <SelectTrigger class="w-[200px]">-->
<!--                  <SelectValue placeholder="Select a list" />-->
<!--                </SelectTrigger>-->
<!--                <SelectContent>-->
<!--                  <SelectItem-->
<!--                    v-for="list in availableLists"-->
<!--                    :key="list.id"-->
<!--                    :value="list.id">-->
<!--                    {{ list.name }}-->
<!--                  </SelectItem>-->
<!--                </SelectContent>-->
<!--              </Select>-->
<!--              <Button-->
<!--                :disabled="!selectedListId"-->
<!--                @click="addToList">-->
<!--                Add to List-->
<!--              </Button>-->
<!--            </div>-->
          </CardContent>
        </Card>

        <!-- Recent Activity -->
        <Card class="md:col-span-2">
          <CardHeader>
            <CardTitle>Recent Activity</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="divide-y">
              <div v-for="event in subscriber.events" :key="event.id" class="py-4">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium">{{ event.type }}</p>
                    <p class="text-sm text-muted-foreground">
                      {{ formatDate(event.created_at) }}
                    </p>
                  </div>
                  <Badge>{{ event.metadata?.campaign_name || 'System' }}</Badge>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
