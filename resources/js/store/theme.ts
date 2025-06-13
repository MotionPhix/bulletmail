import { defineStore } from 'pinia';
import { usePreferredDark } from '@vueuse/core';
import { ref, watch } from 'vue';

export type ThemeMode = 'light' | 'dark' | 'system';

export const useThemeStore = defineStore('theme', () => {
  const preferredDark = usePreferredDark();
  const mode = ref<ThemeMode>(
    localStorage.getItem('theme-mode') as ThemeMode || 'system'
  );
  const isDark = ref(mode.value === 'dark' || (mode.value === 'system' && preferredDark.value));

  // Update theme when mode changes
  watch(mode, (value) => {
    if (value === 'system') {
      isDark.value = preferredDark.value;
    } else {
      isDark.value = value === 'dark';
    }

    localStorage.setItem('theme-mode', value);
    document.documentElement.classList.toggle('dark', isDark.value);
  }, { immediate: true });

  // Watch system preference changes
  watch(preferredDark, (value) => {
    if (mode.value === 'system') {
      isDark.value = value;
      document.documentElement.classList.toggle('dark', value);
    }
  });

  function setMode(newMode: ThemeMode) {
    mode.value = newMode;
  }

  return {
    mode,
    isDark,
    setMode
  };
});
