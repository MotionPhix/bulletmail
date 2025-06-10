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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
  subscriber: {
    uuid: string;
    email: string;
    first_name: string;
    last_name: string;
    status: string;
  };
}>()

const subscriberEditModal = ref(null);

const form = useForm({
  email: props.subscriber.email ?? '',
  first_name: props.subscriber.first_name ?? '',
  last_name: props.subscriber.last_name ?? '',
  status: props.subscriber.status ?? 'subscribed',
});

const submit = () => {
  form.put(route('app.subscribers.update', props.subscriber.uuid), {
    onSuccess: () => {
      subscriberEditModal.value?.close();
      form.reset();
      toast.success('Success', {
        description: 'Subscriber updated successfully',
      });
    },
    onError: () => {
      toast.error('Error', {
        description: 'Failed to update subscriber',
      });
    },
  });
};
</script>

<template>
  <ModalOverlay>
    <Head :title="`Update ${subscriber.first_name} ${subscriber.last_name}`" />

    <Modal
      :close-button="false"
      max-width="sm"
      panel-classes=""
      padding-classes="p-0"
      :close-explicitly="true"
      ref="subscriberEditModal"
      position="center"
      v-slot="{ close }">
      <Card>
        <CardHeader>
          <CardTitle>
            Update {{ `${subscriber.first_name} ${subscriber.last_name}` }}
          </CardTitle>
        </CardHeader>

        <CardContent>
          <form
            @submit.prevent="submit"
            class="space-y-6">
            <div>
              <Label for="email">Email Address</Label>
              <Input id="email" v-model="form.email" type="email" required class="mt-2" />

              <InputError :message="form.errors.email" />
            </div>

            <div>
              <Label for="first_name">First Name</Label>
              <Input id="first_name" v-model="form.first_name" type="text" required class="mt-2" />

              <InputError :message="form.errors.first_name" />
            </div>

            <div>
              <Label for="last_name">Last Name</Label>
              <Input id="last_name" v-model="form.last_name" type="text" required class="mt-2" />

              <InputError :message="form.errors.last_name" />
            </div>

            <div>
              <Label for="status">Subscription Status</Label>

              <Select id="status" v-model="form.status">
                <SelectTrigger class="w-full mt-2">
                  <SelectValue placeholder="Pick a status to assign" />
                </SelectTrigger>

                <SelectContent>
                  <SelectItem value="subscribed">Subscribed</SelectItem>
                  <SelectItem value="unsubscribed">Unsubscribed</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                </SelectContent>
              </Select>

              <InputError :message="form.errors.status" />
            </div>

            <div class="flex justify-end space-x-2">
              <Button type="button" variant="ghost" @click="close"> Cancel </Button>

              <Button type="submit" :disabled="form.processing"> Save </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </Modal>
  </ModalOverlay>
</template>
