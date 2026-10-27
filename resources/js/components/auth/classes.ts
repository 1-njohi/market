// Shared look for every auth form field. Full class strings so Tailwind can find them.

export const labelClass = 'text-sm font-semibold text-slate-300';

export const inputClass =
    'h-12 w-full rounded-xl border border-white/10 bg-white/[0.04] px-4 text-base font-medium text-white placeholder:text-slate-500 transition-all focus-visible:border-amber-300/60 focus-visible:bg-white/[0.06] focus-visible:ring-2 focus-visible:ring-amber-300/20 focus-visible:outline-none';

export const errorClass = 'mt-1 text-sm font-semibold text-rose-300';

export const buttonClass =
    'mt-2 flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-amber-400 text-sm font-black text-[#070b14] shadow-[0_12px_32px_-12px_rgba(251,191,36,0.8)] transition-all duration-200 hover:bg-amber-300 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0e1422] focus-visible:outline-none active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50';

export const linkClass = 'font-bold text-sky-300 transition-colors hover:text-sky-200 hover:underline';