import { cva, type VariantProps } from 'class-variance-authority'

export { default as Button } from './Button.vue'

export const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl border text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*=\'size-\'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:ring-primary/40 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive cursor-pointer select-none',
    {
        variants: {
            variant: {
                default:
                    'border-border/70 bg-transparent text-foreground shadow-none hover:border-border hover:bg-accent/45 hover:text-accent-foreground dark:border-border/70 dark:bg-transparent dark:text-foreground dark:hover:border-border dark:hover:bg-accent/35',
                create:
                    'border-slate-600/80 bg-primary text-primary-foreground shadow-xs hover:border-slate-500 hover:bg-primary/90 btn-gradient dark:border-slate-500/60 dark:hover:border-slate-400/60',
                destructive:
                    'border-destructive/35 bg-transparent text-destructive/80 shadow-none hover:border-destructive/55 hover:bg-destructive/8 hover:text-destructive focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-transparent dark:border-destructive/40 dark:text-destructive/80',
                outline:
                    'border-border bg-background text-foreground shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:text-foreground dark:hover:bg-input/50',
                secondary:
                    'border-muted-foreground/25 bg-transparent text-muted-foreground shadow-none hover:border-muted-foreground/40 hover:bg-muted/45 hover:text-foreground dark:border-muted-foreground/30 dark:bg-transparent dark:text-muted-foreground dark:hover:border-muted-foreground/45 dark:hover:bg-muted/35 dark:hover:text-foreground',
                ghost:
                    'border-transparent text-foreground hover:bg-accent hover:text-accent-foreground dark:hover:bg-accent/50',
                link: 'border-transparent text-primary underline-offset-4 hover:underline',
                success:
                    'border-green-600/80 bg-green-600 text-white shadow-xs hover:bg-green-700 dark:bg-green-700 dark:border-green-600/60 dark:hover:bg-green-600',
                warning:
                    'border-amber-500/80 bg-amber-500 text-white shadow-xs hover:bg-amber-600 dark:bg-amber-600 dark:border-amber-500/60',
            },
            size: {
                default: 'h-9 px-4 py-2 has-[>svg]:px-3',
                sm: 'h-8 gap-1.5 px-3 text-xs has-[>svg]:px-2.5',
                lg: 'h-10 px-6 has-[>svg]:px-4',
                icon: 'size-9',
                'icon-sm': 'size-8',
                'icon-lg': 'size-10',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
)

export type ButtonVariants = VariantProps<typeof buttonVariants>
