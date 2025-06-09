<script setup lang="ts">
import { Modal } from '@inertiaui/modal-vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { toast } from 'vue-sonner';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import ModalOverlay from '@/components/ModalOverlay.vue';

const subscriberCreateModal = ref(null);

const form = useForm({
  email: '',
  first_name: '',
  last_name: '',
  status: 'subscribed',
  mailing_lists: [] as number[],
});

const submit = () => {
  form.post(route('subscribers.store'), {
    onSuccess: () => {
      subscriberCreateModal.value.close();
      form.reset();
      toast.success('Success', {
        description: 'Subscriber created successfully',
      });
    },
    onError: () => {
      toast.error('Error', {
        description: 'Failed to create subscriber',
      });
    },
  });
};
</script>

<template>
  <ModalOverlay>
    <Head title="Create Subscriber" />

    <Modal
      :close-button="false"
      max-width="sm"
      panel-classes=""
      padding-classes="p-0"
      :close-explicitly="true"
      ref="subscriberCreateModal"
      v-slot="{ close }">
      <Card>
        <CardHeader>
          <CardTitle>Create a new subscriber</CardTitle>
        </CardHeader>

        <CardContent>
          <form
            @submit.prevent="submit"
            class="space-y-6">
            <div>
              <Label for="email">Email Address</Label>
              <Input id="email" v-model="form.email" type="email" required class="mt-1" />

              <InputError :message="form.errors.email" />
            </div>

            <div>
              <Label for="first_name">First Name</Label>
              <Input id="first_name" v-model="form.first_name" type="text" required class="mt-1" />

              <InputError :message="form.errors.first_name" />
            </div>

            <div>
              <Label for="last_name">Last Name</Label>
              <Input id="last_name" v-model="form.last_name" type="text" required class="mt-1" />

              <InputError :message="form.errors.last_name" />
            </div>

            <div class="flex justify-end space-x-2">
              <Button type="button" variant="ghost" @click="close"> Cancel </Button>

              <Button type="submit" :disabled="form.processing"> Add </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </Modal>
  </ModalOverlay>
</template>
