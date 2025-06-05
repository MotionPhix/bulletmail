<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const industries = [
  ['technology', 'Technology'],
  ['e-commerce', 'E-commerce'],
  ['healthcare', 'Healthcare'],
  ['education', 'Education'],
  ['finance', 'Finance'],
  ['marketing', 'Marketing'],
  ['retail', 'Retail'],
  ['other', 'Other']
].map(([value, name]) => ({ value, name }));

const form = useForm({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  organization_name: '',
  organization_size: '',
  industry: '',
  website: '',
  terms: false
});

const submit = () => {
  form.post(route('register'), {
    onSuccess: () => form.reset('password', 'password_confirmation'),
    onError: (err) => {

      console.log(err);

    },
    preserveScroll: true,
    preserveState: true,
  });
};
</script>

<template>
  <AuthBase title="Create your account" description="Get started with your email marketing journey">

    <Head title="Register" />

    <form @submit.prevent="submit">
      <div class="grid gap-6">
        <!-- Personal Information -->
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="grid gap-y-1.5">
            <Label>First name</Label>

            <Input v-model="form.first_name" required />

            <InputError :message="form.errors.first_name" />
          </div>

          <div class="grid gap-y-1.5">
            <Label>Last name</Label>

            <Input v-model="form.last_name" required />

            <InputError :message="form.errors.last_name" />
          </div>
        </div>

        <!-- Organization Details -->
        <div class="grid gap-y-1.5">
          <Label>Organization name</Label>

          <Input v-model="form.organization_name" required />

          <InputError :message="form.errors.organization_name" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2 items-start">
          <div class="grid gap-y-1.5">
            <Label>Organization size</Label>

            <Select v-model="form.organization_size">
              <SelectTrigger class="w-full">
                <SelectValue placeholder="Select size" />
              </SelectTrigger>

              <SelectContent>
                <SelectItem value="1-10">1-10 employees</SelectItem>
                <SelectItem value="11-50">11-50 employees</SelectItem>
                <SelectItem value="51-200">51-200 employees</SelectItem>
                <SelectItem value="201-500">201-500 employees</SelectItem>
                <SelectItem value="500+">500+ employees</SelectItem>
              </SelectContent>
            </Select>

            <InputError :message="form.errors.organization_size" />
          </div>

          <div class="grid gap-y-1.5">
            <Label>Industry</Label>

            <Select v-model="form.industry">
              <SelectTrigger class="w-full">
                <SelectValue placeholder="Select industry" />
              </SelectTrigger>

              <SelectContent>
                <SelectItem
                  :key="tech.value"
                  :value="tech.value"
                  v-for="tech in industries">
                  {{ tech.name }}
                </SelectItem>
              </SelectContent>
            </Select>

            <InputError :message="form.errors.industry" />
          </div>

          <div class="grid gap-y-1.5 sm:col-span-2">
            <Label>Organization website</Label>

            <Input
              type="url"
              class="w-full"
              v-model="form.website"
              placeholder="https://example.com"
            />

            <InputError :message="form.errors.website" />
          </div>
        </div>

        <!-- Account Details -->
        <div class="grid gap-y-1.5">
          <Label>Email address</Label>

          <Input
            type="email"
            v-model="form.email"
            required
          />

          <InputError :message="form.errors.email" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="grid gap-y-1.5">
            <Label>Password</Label>
            <Input type="password" v-model="form.password" required />

            <InputError :message="form.errors.password" />
          </div>

          <div class="grid gap-y-1.5">

            <Label>Confirm password</Label>
            <Input type="password" v-model="form.password_confirmation" required />

          </div>
        </div>

        <!-- Terms -->
        <div class="grid">
          <Label class="text-sm flex items-start space-x-1">
            <Checkbox v-model="form.terms" />
            <span>
              I agree to the Terms of Service and Privacy Policy
            </span>
          </Label>

          <InputError :message="form.errors.terms" />
        </div>

        <Button type="submit" class="w-full" :disabled="form.processing">
          <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
          Create account
        </Button>

        <p class="text-center text-sm text-muted-foreground">
          Already have an account?
          <Link
            :href="route('login')"
            class="font-medium text-primary hover:underline">
            Sign in
          </Link>
        </p>
      </div>
    </form>
  </AuthBase>
</template>
