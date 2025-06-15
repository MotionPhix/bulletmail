<script setup lang="ts">
import ModalOverlay from '@/components/ModalOverlay.vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card/index.js';
import { Campaign } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Modal } from '@inertiaui/modal-vue';
import { ref } from 'vue';

defineProps({
  campaign: Campaign,
});

const previewModal = ref();
</script>

<template>
  <ModalOverlay>
    <Head>
      <title>{{ `Preview: ${campaign.name} campaign` }}</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <meta name="description" content="Preview of the email campaign" />
      <meta name="theme-color" content="#ffffff" />
    </Head>

    <Modal
      :close-button="false"
      max-width="4xl"
      panel-classes="!max-h-[90vh] overflow-y-auto"
      padding-classes="p-0"
      :close-explicitly="true"
      ref="previewModal"
      v-slot="{ close }">
      <Card>
        <CardHeader>
          <div class="sticky top-0 z-10 border-b bg-white p-4 shadow-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
              <div>
                <h1 class="text-lg font-semibold">Preview: {{ campaign.name }}</h1>
                <p class="text-sm text-gray-500">Subject: {{ campaign.subject }}</p>
              </div>

              <div class="flex space-x-2">
                <Button @click="close" variant="outline">Close Preview</Button>
                <Button :as="Link" :href="route('app.campaigns.edit', campaign.uuid)" variant="default">Edit Campaign </Button>
              </div>
            </div>
          </div>
        </CardHeader>

        <CardContent>
          <!-- Email preview -->
          <div class="mx-auto my-8 max-w-3xl overflow-hidden rounded-md bg-white shadow-md">
            <!-- Email header -->
            <div class="border-b bg-gray-50 p-4">
              <div class="space-y-2">
                <div>
                  <span class="text-sm text-gray-500">From:</span>
                  <span class="ml-2"> {{ campaign.from_name || 'Your Name' }} &lt;{{ campaign.from_email || 'email@example.com' }}&gt; </span>
                </div>
                <div>
                  <span class="text-sm text-gray-500">To:</span>
                  <span class="ml-2">[Subscriber Email]</span>
                </div>
                <div>
                  <span class="text-sm text-gray-500">Subject:</span>
                  <span class="ml-2 font-medium">{{ campaign.subject }}</span>
                </div>
                <div v-if="campaign.reply_to">
                  <span class="text-sm text-gray-500">Reply-To:</span>
                  <span class="ml-2">{{ campaign.reply_to }}</span>
                </div>
              </div>
            </div>

            <!-- Email body -->
            <div class="p-6">
              <div v-if="campaign.content" class="prose max-w-none" v-html="campaign.content"></div>
              <div v-else class="py-8 text-center text-gray-500">No content available for preview.</div>
            </div>

            <!-- Email footer -->
            <div class="border-t bg-gray-50 p-4 text-sm text-gray-500">
              <p>This is a preview of your email campaign. The actual email may look slightly different depending on the recipient's email client.</p>
              <p class="mt-2">You can customize the footer of your emails in the template settings.</p>
            </div>
          </div>
        </CardContent>

        <CardFooter>
          <!-- Preview notes -->
          <div class="mx-auto mb-8 max-w-3xl rounded-md border border-amber-200 bg-amber-50 p-4">
            <h3 class="font-medium text-amber-800">Preview Notes</h3>
            <ul class="mt-2 space-y-1 text-sm text-amber-700">
              <li>This is a preview of how your email will appear to recipients.</li>
              <li>Personalization tags (merge tags — if any) will be replaced with actual subscriber data when sent.</li>
              <li>Always test your campaigns before sending them to your subscriber lists.</li>
            </ul>
          </div>
        </CardFooter>
      </Card>
    </Modal>
  </ModalOverlay>
</template>
