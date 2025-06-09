import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
  user: User;
}

export interface BreadcrumbItem {
  title: string;
  href: string;
}

export interface NavItem {
  title: string;
  href: string;
  icon?: LucideIcon;
  isActive?: boolean;
  can?: boolean;
  children?: Array<{
    title: string;
    href: string;
    can?: boolean;
  }>;
}

export interface SharedData extends PageProps {
  name: string;
  quote: { message: string; author: string };
  auth: Auth;
  ziggy: Config & { location: string };
  sidebarOpen: boolean;
}

export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface Organization {
  id: number;
  uuid: string;
  name: string;
  size: string;
  industry: string;
  website?: string;
  phone?: string;
  owner_id: number;
  primary_color?: string;
  secondary_color?: string;
  email_header?: string;
  email_footer?: string;
  default_from_name?: string;
  default_from_email?: string;
  default_reply_to?: string;
  settings: OrganizationSettings;
  preferences: OrganizationPreferences;
  integrations: OrganizationIntegrations;
  created_at: string;
  updated_at: string;
  teams_count?: number;
  teams?: Team[];
  owner?: User;
  logo?: Media;
}

export interface OrganizationSettings {
  billing_email?: string;
  billing_address?: string;
  vat_number?: string;
}

export interface OrganizationPreferences {
  timezone: string;
  date_format: string;
  notifications?: {
    new_subscriber?: boolean;
    campaign_sent?: boolean;
  };
}

export interface OrganizationIntegrations {
  sendgrid?: {
    enabled: boolean;
    api_key?: string;
  };
  mailgun?: {
    enabled: boolean;
    api_key?: string;
    domain?: string;
  };
  stripe?: {
    enabled: boolean;
    public_key?: string;
    secret_key?: string;
  };
}

export interface Media {
  id: number;
  uuid: string;
  name: string;
  file_name: string;
  mime_type: string;
  size: number;
  url: string;
  preview_url?: string;
  created_at: string;
}

export interface Plan {
  id: number;
  uuid: string;
  name: string;
  slug: string;
  description?: string;
  price: number;
  currency: string;
  trial_days: number;
  is_active: boolean;
  is_featured: boolean;
  features: PlanFeatures;
  sort_order: number;
}

export interface PlanFeatures {
  campaign_limit: number;
  subscriber_limit: number;
  monthly_email_limit: number;
  daily_email_limit: number;
  can_schedule_campaigns: boolean;
  can_use_templates: boolean;
  can_import_subscribers: boolean;
  can_export_data: boolean;
  support_type: 'community' | 'email' | 'priority';
}

export interface Subscription {
  id: number;
  uuid: string;
  user_id: number;
  plan_id: number;
  status: 'active' | 'canceled' | 'expired' | 'trialing';
  trial_ends_at?: string;
  ends_at?: string;
  created_at: string;
  updated_at: string;
  plan?: Plan;
}

export type BreadcrumbItemType = BreadcrumbItem;
