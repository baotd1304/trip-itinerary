import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

type FlashKey = 'success' | 'error' | 'warning' | 'info';

export function useFlashToast() {
  const page = usePage();
  let lastId: string | null = null;

  watch( () => page.props.flash, (flash: any) => {
      if (!flash || flash.id === lastId) return;
      lastId = flash.id;
      console.log('SHOW TOAST');
      (['success', 'error', 'warning', 'info'] as FlashKey[]).forEach((key) => {
        
        if (flash[key]) toast[key](flash[key]);
      });
    },
    { immediate: true, deep: true },
  );
}