import { useEffect, useState } from 'react';
import { company, nav } from '../content/site';
import { useScrolled } from '../lib/hooks';
import { Arrow } from './Visuals';

export function Logo({ compact = false }: { compact?: boolean }) {
  return (
    <a href="#top" className="group flex items-center gap-2.5" aria-label={company.name}>
      <span className="relative grid h-9 w-9 place-items-center">
        <span className="absolute inset-0 rounded-xl bg-gradient-to-br from-brand-400 to-iris-500 opacity-90 transition-opacity group-hover:opacity-100" />
        <span className="absolute inset-[1.5px] rounded-[10px] bg-ink-950" />
        <svg viewBox="0 0 24 24" className="relative h-[18px] w-[18px]" aria-hidden>
          <path
            d="M12 2.5 20 6v6c0 5-3.4 8.6-8 9.5-4.6-.9-8-4.5-8-9.5V6z"
            fill="none"
            stroke="url(#logo-g)"
            strokeWidth="1.7"
            strokeLinejoin="round"
          />
          <path d="M12 7.5v9" stroke="url(#logo-g)" strokeWidth="1.7" strokeLinecap="round" />
          <path d="M15.5 9.5 12 13l3.5 3.5" fill="none" stroke="url(#logo-g)" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
          <defs>
            <linearGradient id="logo-g" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stopColor="#67E8F9" />
              <stop offset="100%" stopColor="#A78BFA" />
            </linearGradient>
          </defs>
        </svg>
      </span>
      <span className="flex flex-col leading-none">
        <span className="text-lg font-bold tracking-tight text-white">{company.name}</span>
        {!compact && (
          <span className="mt-0.5 text-[9.5px] font-medium uppercase tracking-[0.2em] text-slate-500">
            Infrastructure
          </span>
        )}
      </span>
    </a>
  );
}

export default function Nav() {
  const scrolled = useScrolled(20);
  const [open, setOpen] = useState(false);

  // Lock body scroll while the mobile sheet is open
  useEffect(() => {
    document.body.style.overflow = open ? 'hidden' : '';
    return () => {
      document.body.style.overflow = '';
    };
  }, [open]);

  return (
    <header
      className={`fixed inset-x-0 top-0 z-50 transition-all duration-500 ${
        scrolled
          ? 'border-b border-white/10 bg-ink-950/80 py-2.5 backdrop-blur-xl'
          : 'border-b border-transparent py-5'
      }`}
    >
      <div className="shell flex items-center justify-between gap-6">
        <Logo />

        <nav className="hidden items-center gap-1 lg:flex" aria-label="მთავარი ნავიგაცია">
          {nav.map((item) => (
            <a
              key={item.href + item.label}
              href={item.href}
              className="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-400 transition-colors duration-200 hover:bg-white/5 hover:text-white"
            >
              {item.label}
            </a>
          ))}
        </nav>

        <div className="hidden items-center gap-3 lg:flex">
          <a href={`tel:${''}`} className="hidden text-sm font-medium text-slate-400 transition-colors hover:text-white xl:block">
            {/* phone shown in contact section; kept short here */}
          </a>
          <a href="#contact" className="btn-primary !px-5 !py-2.5">
            კონსულტაცია
            <Arrow className="h-4 w-4" />
          </a>
        </div>

        <button
          type="button"
          onClick={() => setOpen((v) => !v)}
          className="grid h-10 w-10 place-items-center rounded-lg border border-white/12 bg-white/5 text-white lg:hidden"
          aria-label={open ? 'მენიუს დახურვა' : 'მენიუს გახსნა'}
          aria-expanded={open}
        >
          <span className="relative block h-4 w-5">
            <span
              className={`absolute left-0 block h-[1.6px] w-5 bg-current transition-all duration-300 ${
                open ? 'top-[7px] rotate-45' : 'top-0.5'
              }`}
            />
            <span
              className={`absolute left-0 top-[7px] block h-[1.6px] w-5 bg-current transition-all duration-300 ${
                open ? 'opacity-0' : 'opacity-100'
              }`}
            />
            <span
              className={`absolute left-0 block h-[1.6px] w-5 bg-current transition-all duration-300 ${
                open ? 'top-[7px] -rotate-45' : 'top-[13.5px]'
              }`}
            />
          </span>
        </button>
      </div>

      {/* Mobile sheet */}
      <div
        className={`fixed inset-x-0 top-[64px] z-40 origin-top border-t border-white/10 bg-ink-950/97 backdrop-blur-2xl transition-all duration-300 lg:hidden ${
          open ? 'pointer-events-auto opacity-100' : 'pointer-events-none -translate-y-3 opacity-0'
        }`}
        style={{ height: open ? 'calc(100dvh - 64px)' : 0 }}
      >
        <div className="shell flex flex-col gap-1 py-6">
          {nav.map((item, i) => (
            <a
              key={item.href + item.label}
              href={item.href}
              onClick={() => setOpen(false)}
              className="border-b border-white/6 py-4 text-lg font-medium text-slate-200 transition-colors hover:text-brand-300"
              style={{ transitionDelay: `${i * 30}ms` }}
            >
              {item.label}
            </a>
          ))}
          <a href="#contact" onClick={() => setOpen(false)} className="btn-primary mt-6 w-full">
            კონსულტაცია
            <Arrow className="h-4 w-4" />
          </a>
        </div>
      </div>
    </header>
  );
}
