// import { watch } from 'vue';
// import { usePage } from '@inertiajs/vue3';
// import { toast } from 'vue-sonner';

// type FlashKey = 'success' | 'error' | 'warning' | 'info';

// export function useFlashToast() {
//   const page = usePage();
//   let lastId: string | null = null;

//   watch( () => page.props.flash, (flash: any) => {
//       if (!flash || flash.id === lastId) return;
//       lastId = flash.id;
//       console.log('SHOW TOAST');
//       (['success', 'error', 'warning', 'info'] as FlashKey[]).forEach((key) => {
        
//         if (flash[key]) toast[key](flash[key]);
//       });
//     },
//     { immediate: true, deep: true },
//   );
// }

import { router, usePage } from '@inertiajs/vue3';
import { watch, onMounted, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';

type FlashBag = {
  success?: string | null;
  error?: string | null;
  warning?: string | null;
  info?: string | null;
};

export function useFlashToast() {
  const page = usePage();

  const fire = (flash?: FlashBag) => {
    if (!flash) return;
    if (flash.success) toast.success(flash.success);
    if (flash.error)   toast.error(flash.error,   { duration: 3000 });
    if (flash.warning) toast.warning(flash.warning);
    if (flash.info)    toast.info(flash.info);
  };

  onMounted(() => fire(page.props.flash as FlashBag));

  watch(
    () => page.props.flash as FlashBag,
    (flash) => fire(flash),
    { deep: true },
  );

  // Bắt luôn 403 trả về dạng JSON (nếu có request axios ngoài Inertia)
  const off = (router.on as (
    eventName: string,
    callback: (event: any) => void,
  ) => () => void)('invalid', (event) => {
    const response = (event.detail as { response?: { status?: number } } | undefined)?.response;
    const status = response?.status;

    if (status === 403) {
      event.preventDefault();
      toast.error('Bạn không có quyền thực hiện thao tác này.');
    }
  });

  onUnmounted(() => off());
}