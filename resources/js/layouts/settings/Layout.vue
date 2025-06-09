<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
  Building2,
  PaintBucket,
  CreditCard,
  Settings,
  Shield,
  Webhook,
  UserIcon,
  PaletteIcon,
  LockIcon,
  UsersIcon
} from 'lucide-vue-next';

const page = usePage();
const user = page.props.auth.user;
const org = page.props.auth.current_organization;
const team = page.props.auth.current_team;


const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';

// Base settings available to everyone
const sidebarNavItems: NavItem[] = [
  {
    title: 'Profile',
    href: route('settings.profile.edit'),
    icon: UserIcon
  },
  {
    title: 'Password',
    href: route('settings.password.edit'),
    icon: LockIcon
  },
  {
    title: 'Appearance',
    href: route('settings.appearance'),
    icon: PaletteIcon
  }
];

// Additional settings for organization admins/owners
const adminSidebarNavItems: NavItem[] = [
  {
    title: 'General',
    href: route('organization.settings.general', org?.uuid),
    icon: Building2
  },
  {
    title: 'Branding',
    href: route('organization.settings.branding', org?.uuid),
    icon: PaintBucket
  },
  {
    title: 'Billing',
    href: route('organization.settings.billing', org?.uuid),
    icon: CreditCard
  },
  {
    title: 'Integrations',
    href: route('organization.settings.integrations', org?.uuid),
    icon: Webhook
  },
  {
    title: 'Team Settings',
    href: route('teams.settings.general', team?.uuid),
    icon: Settings
  },
  {
    title: 'Members',
    href: route('teams.settings.members', team?.uuid),
    icon: UsersIcon
  },
  {
    title: 'Roles',
    href: route('teams.settings.roles', team?.uuid),
    icon: Shield
  }
];

/*const combinedSidebarNavItems = user?.can['organization:settings:edit']
  ? [...sidebarNavItems, ...adminSidebarNavItems]
  : sidebarNavItems;*/

const combinedSidebarNavItems = [...sidebarNavItems, ...adminSidebarNavItems]

function isCurrent(href: string): boolean {
  const newhref = new URL(href, window.location.origin).pathname;
  return currentPath === newhref;
}
</script>

<template>
  <div class="px-4 py-6">
    <Heading
      title="Settings"
      description="Manage your organization and team settings"
    />

    <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-x-12 lg:space-y-0">
      <aside class="w-full max-w-xl lg:w-48">
        <nav class="flex flex-col space-x-0 space-y-1 sticky top-5">
          <Button
            v-for="item in combinedSidebarNavItems"
            :key="item.href"
            variant="ghost"
            :class="[
              'w-full justify-start space-x-2',
              { 'bg-muted': isCurrent(item.href) }
            ]"
            as-child>
            <Link
              preserve-scroll
              :href="item.href" class="flex items-center">
              <component :is="item.icon" class="h-4 w-4 mr-2" />
              <span>{{ item.title }}</span>
            </Link>
          </Button>
        </nav>
      </aside>

      <Separator class="my-6 md:hidden" />

      <div class="flex-1 md:max-w-2xl pt-10 md:mt-0">
        <section class="max-w-xl space-y-12">
          <slot />
        </section>
      </div>
    </div>
  </div>
</template>
