<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
  LayoutGrid,
  Mail,
  Users,
  FileText,
  Building2,
  PlayCircle,
  ChartLineIcon
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();
const user = page.props.auth.user;

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: route('dashboard'),
    icon: LayoutGrid,
  },
  {
    title: 'Organization',
    href: route('organizations.show', page.props.auth.current_organization?.uuid),
    icon: Building2,
  },
  {
    title: 'Templates',
    href: route('templates.index'),
    icon: FileText,
  },
  {
    title: 'Campaigns',
    href: route('campaigns.index'),
    icon: Mail,
  },
  {
    title: 'Subscribers',
    href: route('subscribers.index'),
    icon: Users,
  },
  {
    title: 'Automations',
    href: route('automations.index'),
    icon: PlayCircle,
  }
];

const footerNavItems: NavItem[] = [
  {
    title: 'Analytics',
    href: route('analytics.index'),
    icon: ChartLineIcon,
  },
];
</script>

<template>
  <Sidebar collapsible="icon"
           variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg"
                             as-child>
            <Link :href="route('dashboard')">
            <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
