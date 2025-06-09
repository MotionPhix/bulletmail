<script setup lang="ts">
interface Activity {
  id: string;
  type: string;
  description: string;
  created_at: string;
  user: {
    name: string;
    avatar?: string;
  };
}

defineProps<{
  activities: Activity[]
}>();
</script>

<template>
  <div class="space-y-4">
    <div v-if="activities.length === 0" class="text-center py-8">
      <p class="text-muted-foreground">No recent activity</p>
    </div>

    <div v-else v-for="activity in activities" :key="activity.id"
      class="flex items-start space-x-4 py-3">
      <div class="h-8 w-8">
        <img v-if="activity.user.avatar"
          :src="activity.user.avatar"
          :alt="activity.user.name"
          class="rounded-full"
        />
        <div v-else class="h-full w-full rounded-full bg-primary/10 flex items-center justify-center">
          {{ activity.user.name.charAt(0) }}
        </div>
      </div>

      <div class="flex-1 space-y-1">
        <p class="text-sm">
          <span class="font-medium">{{ activity.user.name }}</span>
          {{ activity.description }}
        </p>
        <p class="text-xs text-muted-foreground">
          {{ new Date(activity.created_at).toLocaleDateString() }}
        </p>
      </div>
    </div>
  </div>
</template>
