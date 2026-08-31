// resources/js/composables/useCloudinaryUpload.ts
import { ref, computed } from 'vue';

export interface UploadedImage {
  public_id: string;
  url: string;
  format?: string;
  width?: number;
  height?: number;
  bytes?: number;
}

export interface UploadItem {
  uid: string;
  name: string;
  preview: string;
  progress: number;
  status: 'uploading' | 'done' | 'error';
  error?: string;
  result?: UploadedImage;
}

interface Signature {
  cloud_name: string;
  api_key: string;
  timestamp: number;
  folder: string;
  signature: string;
  upload_url: string;
}

const SIGNATURE_URL = '/admin/trips/cloudinary-signature';
const DISCARD_URL = '/admin/trips/uploaded-image';
const MAX_SIZE = 5 * 1024 * 1024;
const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp', 'image/heic'];

/** crypto.randomUUID chỉ tồn tại ở secure context (https/localhost) */
function makeUid(): string {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }
  return `u-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

export function useCloudinaryUpload() {
  const items = ref<UploadItem[]>([]);

  const uploading = computed(() => items.value.some((i) => i.status === 'uploading'));
  const uploaded = computed(() =>
    items.value.filter((i) => i.status === 'done' && i.result).map((i) => i.result!),
  );

  /**
   * Thay thế TOÀN BỘ mảng bằng mảng mới.
   * Cách này luôn kích hoạt re-render, kể cả khi ref bị shallow
   * hoặc khi có tham chiếu object thô lẫn vào.
   */
  function patch(uid: string, partial: Partial<UploadItem>) {
    items.value = items.value.map((it) =>
      it.uid === uid ? { ...it, ...partial } : it,
    );
  }

  /** Lưới an toàn: ép mọi item còn kẹt 'uploading' về trạng thái cuối */
  function finalizeStuck() {
    if (!items.value.some((i) => i.status === 'uploading')) return;

    items.value = items.value.map((it) =>
      it.status === 'uploading'
        ? { ...it, status: 'error', error: 'Tải ảnh không hoàn tất. Vui lòng thử lại.' }
        : it,
    );
  }

  async function getSignature(): Promise<Signature> {
    const res = await fetch(SIGNATURE_URL, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!res.ok) throw new Error('Không lấy được chữ ký tải lên.');
    return res.json();
  }

  function putFile(file: File, sig: Signature, uid: string) {
    return new Promise<UploadedImage>((resolve, reject) => {
      let settled = false;
      const done = (fn: () => void) => {
        if (settled) return;
        settled = true;
        fn();
      };

      const fd = new FormData();
      fd.append('file', file);
      fd.append('api_key', sig.api_key);
      fd.append('timestamp', String(sig.timestamp));
      fd.append('folder', sig.folder);
      fd.append('signature', sig.signature);

      const xhr = new XMLHttpRequest();
      xhr.open('POST', sig.upload_url, true);
      xhr.timeout = 120_000;

      xhr.upload.onprogress = (e) => {
        if (!e.lengthComputable) return;
        // giữ tối đa 99% vì còn chờ Cloudinary xử lý và trả response
        patch(uid, { progress: Math.min(99, Math.round((e.loaded / e.total) * 100)) });
      };

      xhr.onerror = () => done(() => reject(new Error('Lỗi kết nối khi tải ảnh.')));
      xhr.ontimeout = () => done(() => reject(new Error('Quá thời gian tải ảnh.')));
      xhr.onabort = () => done(() => reject(new Error('Đã huỷ tải ảnh.')));

      /**
       * Dùng onloadend thay cho onload: sự kiện này LUÔN bắn ra
       * dù thành công, lỗi, timeout hay bị huỷ => không bao giờ treo.
       */
      xhr.onloadend = () => {
        done(() => {
          if (xhr.status < 200 || xhr.status >= 300) {
            let msg = 'Tải ảnh lên thất bại.';
            try {
              const err = JSON.parse(xhr.responseText)?.error?.message;
              if (err) msg = String(err);
            } catch {
              /* giữ thông báo mặc định */
            }
            return reject(new Error(msg));
          }

          try {
            const r = JSON.parse(xhr.responseText);
            if (!r?.public_id || !r?.secure_url) {
              return reject(new Error('Phản hồi tải lên không hợp lệ.'));
            }
            resolve({
              public_id: r.public_id,
              url: r.secure_url,
              format: r.format,
              width: r.width,
              height: r.height,
              bytes: r.bytes,
            });
          } catch {
            reject(new Error('Phản hồi tải lên không hợp lệ.'));
          }
        });
      };

      xhr.send(fd);
    });
  }

  async function addFiles(files: File[], remainingSlots = 10) {
    const list = files.slice(0, Math.max(0, remainingSlots));
    if (!list.length) return;

    // Hiển thị ngay, kể cả khi còn đang chờ chữ ký
    const queued = list.map((file) => {
      const uid = makeUid();
      items.value = [
        ...items.value,
        {
          uid,
          name: file.name,
          preview: URL.createObjectURL(file),
          progress: 0,
          status: 'uploading',
        },
      ];
      return { uid, file };
    });

    let sig: Signature;
    try {
      sig = await getSignature();
    } catch {
      queued.forEach(({ uid }) =>
        patch(uid, { status: 'error', error: 'Không khởi tạo được phiên tải lên.' }),
      );
      return;
    }

    try {
      await Promise.all(
        queued.map(async ({ uid, file }) => {
          if (!ACCEPTED.includes(file.type)) {
            patch(uid, {
              status: 'error',
              error: 'Định dạng không hỗ trợ (JPG, PNG, WEBP, HEIC).',
            });
            return;
          }
          if (file.size > MAX_SIZE) {
            patch(uid, { status: 'error', error: 'Ảnh vượt quá 5MB.' });
            return;
          }

          try {
            const result = await putFile(file, sig, uid);
            patch(uid, { status: 'done', progress: 100, result });
          } catch (e: any) {
            patch(uid, {
              status: 'error',
              progress: 100,
              error: e?.message ?? 'Tải ảnh lên thất bại.',
            });
          }
        }),
      );
    } finally {
      // Không item nào được phép còn 'uploading' sau khi hàng đợi kết thúc
      finalizeStuck();
    }
  }

  function remove(uid: string) {
    const target = items.value.find((x) => x.uid === uid);
    if (!target) return;
    if (target.preview) URL.revokeObjectURL(target.preview);
    items.value = items.value.filter((x) => x.uid !== uid);
  }

  function reset() {
    items.value.forEach((i) => i.preview && URL.revokeObjectURL(i.preview));
    items.value = [];
  }

  async function discardOrphans() {
    const ids = uploaded.value.map((i) => i.public_id);
    if (!ids.length) {
      reset();
      return;
    }

    const token =
      document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    try {
      await fetch(DISCARD_URL, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        credentials: 'same-origin',
        body: JSON.stringify({ public_ids: ids }),
      });
    } catch {
      /* bỏ qua, để tác vụ dọn dẹp định kỳ xử lý */
    }
    reset();
  }

  return { items, uploading, uploaded, addFiles, remove, reset, discardOrphans, finalizeStuck };
}