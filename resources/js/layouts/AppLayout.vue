<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css'

interface Props {
  breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => [],
});
</script>

<template>

  <Toaster position="top-right" rich-colors class="z-50" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <slot />
  </AppLayout>

</template>
