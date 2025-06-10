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
import { Checkbox } from '@/components/ui/checkbox';

const subscriberImportModal = ref(null);

const form = useForm({
  file: null as File | null,
  update_existing: true,
});

const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files?.length) {
    form.file = input.files[0];
  }
};

const submit = () => {
  form.post(route('subscribers.import'), {
    onSuccess: () => {
      subscriberImportModal.value.close();
      form.reset();
      toast.success('Success',{
        description: 'Subscribers imported successfully',
      });
    },
    onError: () => {
      toast.error('Error', {
        description: 'Failed to import subscribers',
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
      ref="subscriberImportModal"
      v-slot="{ close }">
      <Card>
        <CardHeader>
          <CardTitle>Import Subscribers</CardTitle>
        </CardHeader>

        <CardContent>
          <form
            @submit.prevent="submit"
            class="space-y-6">
            <div>
              <Label>Upload File (CSV or Excel)</Label>
              <Input
                type="file"
                accept=".csv,.xlsx,.xls"
                @change="handleFileUpload"
                class="mt-1"
              />
              <p class="text-sm text-muted-foreground mt-1">
                File must contain: email, first_name, last_name columns
              </p>
            </div>
            <div>
              <Label for="update_existing" class="flex items-center space-x-1">
                <Checkbox
                  id="update_existing"
                  @update:model-value="checked => form.update_existing = checked"
                  :model-value="form.update_existing"
                />
                <span>Update existing subscribers</span>
              </Label>
            </div>
            <div class="flex justify-end space-x-2">
              <Button
                type="button"
                variant="ghost"
                @click="close">
                Cancel
              </Button>
              <Button
                type="submit"
                :disabled="form.processing || !form.file">
                Import
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </Modal>
  </ModalOverlay>
</template>
