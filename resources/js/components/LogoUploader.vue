<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue';
import { ImageIcon, Trash2, RefreshCw, Upload } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

interface Props {
  modelValue: File | null;
  currentLogo?: string;
  config: {
    maxLogoSize: number;
    allowedTypes: string[];
    minDimensions: { width: number; height: number };
    maxDimensions: { width: number; height: number };
  };
  error?: string;
}

interface Emits {
  (e: 'update:modelValue', value: File | null): void;
  (e: 'error', message: string): void;
  (e: 'delete'): void;
  (e: 'regenerate'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const fileInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string>(props.currentLogo || '');
const isDragging = ref(false);
const error = ref(props.error || '');

// Watch for external error changes
watch(() => props.error, (newError) => {
  error.value = newError || '';
});

// Cleanup preview URL when component is unmounted
onBeforeUnmount(() => {
  if (previewUrl.value && previewUrl.value !== props.currentLogo) {
    URL.revokeObjectURL(previewUrl.value);
  }
});

const validateFile = (file: File): boolean => {
  // Check file type
  if (!props.config.allowedTypes.includes(file.type)) {
    error.value = `File type must be one of: ${props.config.allowedTypes.join(', ')}`;
    emit('error', error.value);
    return false;
  }

  // Check file size
  if (file.size > props.config.maxLogoSize) {
    error.value = `File size must be less than ${props.config.maxLogoSize / (1024 * 1024)}MB`;
    emit('error', error.value);
    return false;
  }

  return true;
};

const validateDimensions = (img: HTMLImageElement): boolean => {
  const { minDimensions, maxDimensions } = props.config;

  if (img.width < minDimensions.width || img.height < minDimensions.height) {
    error.value = `Image dimensions must be at least ${minDimensions.width}x${minDimensions.height}px`;
    emit('error', error.value);
    return false;
  }

  if (img.width > maxDimensions.width || img.height > maxDimensions.height) {
    error.value = `Image dimensions cannot exceed ${maxDimensions.width}x${maxDimensions.height}px`;
    emit('error', error.value);
    return false;
  }

  return true;
};

const handleFile = (file: File) => {
  if (!validateFile(file)) return;

  const img = new Image();
  img.onload = () => {
    if (!validateDimensions(img)) return;

    // Clear any previous errors
    error.value = '';

    // Update preview and emit new value
    if (previewUrl.value && previewUrl.value !== props.currentLogo) {
      URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = URL.createObjectURL(file);
    emit('update:modelValue', file);
  };
  img.src = URL.createObjectURL(file);
};

const handleDrop = (e: DragEvent) => {
  e.preventDefault();
  isDragging.value = false;

  const file = e.dataTransfer?.files[0];
  if (file) handleFile(file);
};

const handleFileInput = (e: Event) => {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0];
  if (file) handleFile(file);
};

const handleDelete = () => {
  if (previewUrl.value && previewUrl.value !== props.currentLogo) {
    URL.revokeObjectURL(previewUrl.value);
  }
  previewUrl.value = '';
  emit('update:modelValue', null);
  emit('delete');
};
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <ImageIcon class="h-5 w-5 text-muted-foreground" />
        <h3 class="font-semibold text-lg">Organization Logo</h3>
      </div>
      <div class="flex items-center gap-2"
           v-if="previewUrl">
        <Button type="button"
                variant="outline"
                size="sm"
                @click="$emit('regenerate')">
          <RefreshCw class="h-4 w-4 mr-2" />
          Regenerate
        </Button>
        <Button type="button"
                variant="destructive"
                size="sm"
                @click="handleDelete">
          <Trash2 class="h-4 w-4 mr-2" />
          Remove
        </Button>
      </div>
    </div>

    <div class="relative rounded-lg border-2 border-dashed p-4"
         :class="{
          'border-primary bg-primary/5': isDragging,
          'border-muted-foreground/25': !isDragging
        }"
         @dragover.prevent="isDragging = true"
         @dragleave.prevent="isDragging = false"
         @drop="handleDrop">
      <div class="flex items-start gap-4">
        <!-- Preview Area -->
        <div class="relative h-24 w-24 rounded-lg border bg-muted/25 flex items-center justify-center overflow-hidden">
          <img v-if="previewUrl"
               :src="previewUrl"
               class="h-full w-full rounded-lg object-cover"
               alt="Logo preview" />
          <Upload v-else
                  class="h-8 w-8 text-muted-foreground" />
        </div>

        <!-- Upload Instructions -->
        <div class="space-y-2 flex-1">
          <div>
            <Button type="button"
                    variant="outline"
                    @click="fileInput?.click()">
              Choose File
            </Button>
            <input ref="fileInput"
                   type="file"
                   class="hidden"
                   :accept="config.allowedTypes.join(',')"
                   @change="handleFileInput" />
          </div>
          <p
            class="text-sm text-muted-foreground"
            @click="fileInput?.click()">
            Drop your logo here or click to browse
            <br>
            Recommended size: {{ config.minDimensions.width }}x{{ config.minDimensions.height }}px
            <br>
            Formats: {{ config.allowedTypes.join(', ') }}
          </p>
          <p v-if="error"
             class="text-sm text-destructive">
            {{ error }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
