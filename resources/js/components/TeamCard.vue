<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Link } from '@inertiajs/vue3';
import { Mail, UserCircle, Users } from 'lucide-vue-next';

defineProps<{
  team: {
    id: number
    uuid: string
    name: string
    owner: string
    members_count: number
    campaigns_count: number
    subscribers_count: number
  }
  isCurrent: boolean
  isOwner: boolean
}>();
</script>

<template>
  <Card class="relative overflow-hidden transition-all duration-200 hover:shadow-sm">
    <div class="absolute -top-1 right-0 flex gap-2">
      <span v-if="isCurrent" class="bg-primary/10 text-primary rounded-bl-lg px-2 py-1 text-xs font-medium"> Current </span>
      <span v-if="isOwner" class="rounded-full bg-orange-500/10 px-2 py-1 text-xs font-medium text-orange-600"> Owner </span>
    </div>

    <CardHeader>
      <div>
        <CardTitle class="text-lg font-semibold tracking-tight">
          {{ team.name }}
        </CardTitle>

        <CardDescription>Managed by {{ team.owner }}</CardDescription>
      </div>
    </CardHeader>

    <CardContent>
      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1">
          <div class="flex items-center gap-1.5">
            <Users class="text-muted-foreground h-4 w-4" />
            <span class="text-sm font-medium">{{ team.members_count }}</span>
          </div>
          <p class="text-muted-foreground text-xs">Members</p>
        </div>

        <div class="space-y-1">
          <div class="flex items-center gap-1.5">
            <Mail class="text-muted-foreground h-4 w-4" />
            <span class="text-sm font-medium">{{ team.campaigns_count }}</span>
          </div>
          <p class="text-muted-foreground text-xs">Campaigns</p>
        </div>

        <div class="space-y-1">
          <div class="flex items-center gap-1.5">
            <UserCircle class="text-muted-foreground h-4 w-4" />
            <span class="text-sm font-medium">{{ team.subscribers_count }}</span>
          </div>
          <p class="text-muted-foreground text-xs">Subscribers</p>
        </div>
      </div>

      <Separator class="my-4" />

      <div class="flex justify-end gap-2">
        <Button size="sm" :as="Link" :href="route('teams.show', team.uuid)" variant="outline"> View Stats</Button>

        <Button size="sm" v-if="!isCurrent" :as="Link" :href="route('teams.switch', team)" method="put" variant="ghost"> Switch to Team </Button>
      </div>
    </CardContent>
  </Card>
</template>
