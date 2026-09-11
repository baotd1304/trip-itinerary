// resources/js/directives/can.js

import { usePage } from '@inertiajs/vue3'

/**
 * Lấy danh sách permissions và roles của user hiện tại
 */
function getAuthData() {
    // Nếu dùng Inertia
    const page = usePage()
    const auth = page.props.auth

    return {
        roles: auth?.user?.roles ?? [],
        permissions: auth?.user?.permissions ?? [],
        can: auth?.user?.can ?? {},
    }
}

/**
 * Kiểm tra xem user có role cụ thể không
 */
function hasRole(role) {
    const { roles } = getAuthData()
    return roles.includes(role)
}

/**
 * Kiểm tra xem user có permission cụ thể không
 */
function hasPermission(permission) {
    const { permissions, can } = getAuthData()

    // Kiểm tra qua mảng permissions
    if (permissions.includes(permission)) return true

    // Hoặc kiểm tra qua object can
    if (can && can[permission]) return true

    // Super admin bypass
    if (permissions.includes('*') || permissions.includes('super-admin')) return true

    return false
}

/**
 * Parse directive argument
 * Hỗ trợ các format:
 *   v-can="'admin'"                  -> kiểm tra role
 *   v-can="'role:admin'"            -> kiểm tra role
 *   v-can="'permission:edit-post'"  -> kiểm tra permission
 *   v-can="'admin|editor'"           -> kiểm tra 1 trong các role (OR)
 *   v-can="'role:admin,permission:edit-post'" -> kiểm tra role VÀ permission (AND với dấu phẩy)
 */
function parseDirectiveValue(bindingValue) {
    if (!bindingValue) return { type: null, values: [] }

    const value = bindingValue.trim()

    // Nếu có dấu phẩy -> AND logic (phải thỏa tất cả)
    if (value.includes(',')) {
        const conditions = value.split(',').map(c => parseSingleCondition(c.trim()))
        return { type: 'and', conditions }
    }

    // Nếu có dấu | -> OR logic (chỉ cần thỏa 1)
    if (value.includes('|')) {
        const conditions = value.split('|').map(c => parseSingleCondition(c.trim()))
        return { type: 'or', conditions }
    }

    // Single condition
    return { type: 'single', ...parseSingleCondition(value) }
}

function parseSingleCondition(condition) {
    if (condition.startsWith('role:')) {
        return { checkType: 'role', value: condition.replace('role:', '') }
    }
    if (condition.startsWith('permission:')) {
        return { checkType: 'permission', value: condition.replace('permission:', '') }
    }
    // Mặc định: nếu không có prefix, kiểm tra role
    return { checkType: 'role', value: condition }
}

/**
 * Thực hiện kiểm tra
 */
function checkAccess(bindingValue) {
    const parsed = parseDirectiveValue(bindingValue)

    if (parsed.type === 'single') {
        return parsed.checkType === 'role'
            ? hasRole(parsed.value)
            : hasPermission(parsed.value)
    }

    if (parsed.type === 'or') {
        return parsed.conditions.some(c =>
            c.checkType === 'role' ? hasRole(c.value) : hasPermission(c.value)
        )
    }

    if (parsed.type === 'and') {
        return parsed.conditions.every(c =>
            c.checkType === 'role' ? hasRole(c.value) : hasPermission(c.value)
        )
    }

    return false
}

// ============================================
// Vue Directive
// ============================================
export const vCan = {
    // Khi element được mounted vào DOM
    mounted(el, binding) {
        const hasAccess = checkAccess(binding.value)

        if (!hasAccess) {
            // Xóa element khỏi DOM (giống v-if)
            el.remove()

            // HOẶC: ẩn element nhưng vẫn giữ trong DOM (giống v-show)
            // el.style.display = 'none'
        }
    },

    // Khi giá trị binding thay đổi (dùng cho reactivity)
    updated(el, binding) {
        // Nếu đã remove trước đó thì không làm gì
        if (!el.parentNode) return

        const hasAccess = checkAccess(binding.value)

        if (!hasAccess) {
            el.remove()
        }
    },

    // Hàm helper để dùng trong <script setup>
    // import { can } from '@/directives/can'
    // const canEdit = can('permission:edit-post')
    check: checkAccess,
}

// Export function helper để dùng trong JS/script
export const can = (value) => checkAccess(value)
export const cannot = (value) => !checkAccess(value)