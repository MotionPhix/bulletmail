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
import TeamSwitcher from '@/components/TeamSwitcher.vue';

const page = usePage<SharedData>();
const user = page.props.auth.user;
const teams = page.props.auth.teams || [];
const currentTeam = page.props.auth.current_team || { uuid: '', name: '', logo: null, plan: '' };

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: route('dashboard'),
    icon: LayoutGrid,
  },
  {
    title: 'Subscribers',
    href: route('app.subscribers.index'),
    icon: Users,
  },
  {
    title: 'Templates',
    href: route('app.templates.index'),
    icon: FileText,
  },
  {
    title: 'Campaigns',
    href: route('app.campaigns.index'),
    icon: Mail,
  },
  {
    title: 'Automations',
    href: route('app.automations.index'),
    icon: PlayCircle,
  }
];

const footerNavItems: NavItem[] = [
  {
    title: 'Analytics',
    href: route('app.analytics.index'),
    icon: ChartLineIcon,
  },
];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <!--      <SidebarMenu>-->
      <!--        <SidebarMenuItem>-->
      <!--          <SidebarMenuButton-->
      <!--            size="lg"-->
      <!--            as-child>-->
      <!--            <Link :href="route('dashboard')">-->
      <!--            <AppLogo />-->
      <!--            </Link>-->
      <!--          </SidebarMenuButton>-->
      <!--        </SidebarMenuItem>-->
      <!--      </SidebarMenu>-->
      <TeamSwitcher :teams="teams" :active-team="currentTeam" />
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
