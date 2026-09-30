import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-full border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-hidden",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground [a&]:hover:bg-primary/90",
        secondary:
          "border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90",
        destructive:
         "border-transparent bg-destructive text-white [a&]:hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60",
        outline:
          "text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
        primary:
            'bg-blue-700 text-white hover:bg-blue-800 focus-visible:ring-blue-500/50',

        success:
            'bg-green-700 text-white hover:bg-green-800 focus-visible:ring-green-500/50',

        danger:
            'bg-red-700 text-white hover:bg-red-800 focus-visible:ring-red-500/50',

        warning:
            'bg-yellow-600 text-white hover:bg-yellow-700 focus-visible:ring-yellow-500/50',

        info:
            'bg-cyan-700 text-white hover:bg-cyan-800 focus-visible:ring-cyan-500/50',

        light:
            'bg-gray-100 text-gray-900 hover:bg-gray-200 focus-visible:ring-gray-400/50 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700',

        dark:
            'bg-gray-900 text-white hover:bg-gray-800 focus-visible:ring-gray-700/50 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white',
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
