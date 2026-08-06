import { useState, type FormEvent } from 'react';
import { contact } from '../content/site';
import { useReveal } from '../lib/hooks';
import { Arrow, AuroraField } from './Visuals';

const details = [
  {
    label: 'ტელეფონი',
    value: contact.phone,
    href: `tel:${contact.phone.replace(/\s/g, '')}`,
    icon: (
      <path
        d="M4.5 3h3l1.5 4-2 1.4a11 11 0 0 0 4.6 4.6L13 11l4 1.5v3a1.5 1.5 0 0 1-1.6 1.5A13.5 13.5 0 0 1 3 4.6 1.5 1.5 0 0 1 4.5 3z"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.4"
        strokeLinejoin="round"
      />
    ),
  },
  {
    label: 'ელ. ფოსტა',
    value: contact.email,
    href: `mailto:${contact.email}`,
    icon: (
      <>
        <rect x="2.5" y="4.5" width="15" height="11" rx="2" fill="none" stroke="currentColor" strokeWidth="1.4" />
        <path d="m3.5 6 6.5 5 6.5-5" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
      </>
    ),
  },
  {
    label: 'მისამართი',
    value: contact.address,
    href: null,
    icon: (
      <>
        <path
          d="M10 17.5s6-5 6-9.5a6 6 0 1 0-12 0c0 4.5 6 9.5 6 9.5z"
          fill="none"
          stroke="currentColor"
          strokeWidth="1.4"
          strokeLinejoin="round"
        />
        <circle cx="10" cy="8" r="2.2" fill="none" stroke="currentColor" strokeWidth="1.4" />
      </>
    ),
  },
  {
    label: 'სამუშაო საათები',
    value: contact.hours,
    href: null,
    icon: (
      <>
        <circle cx="10" cy="10" r="7.5" fill="none" stroke="currentColor" strokeWidth="1.4" />
        <path d="M10 5.5V10l3 2" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
      </>
    ),
  },
];

export default function Contact() {
  const ref = useReveal<HTMLElement>();
  const [sent, setSent] = useState(false);

  // Front-end only for now — wire to Supabase / an API route when the backend exists.
  const onSubmit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setSent(true);
  };

  const L = contact.formLabels;

  return (
    <section id="contact" ref={ref} className="relative scroll-mt-24 overflow-hidden py-24 lg:py-32">
      <AuroraField className="opacity-60" />

      <div className="shell relative">
        <div className="grid gap-12 lg:grid-cols-[0.85fr_1.15fr] lg:gap-16">
          {/* ── details ── */}
          <div>
            <span className="eyebrow reveal">კონტაქტი</span>
            <h2
              className="reveal mt-5 text-balance text-3xl font-bold tracking-tight sm:text-4xl"
              style={{ transitionDelay: '60ms' }}
            >
              {contact.title}
            </h2>
            <p
              className="reveal mt-5 text-base leading-relaxed text-slate-400"
              style={{ transitionDelay: '120ms' }}
            >
              {contact.subtitle}
            </p>

            <div className="mt-10 space-y-3">
              {details.map((d, i) => {
                const inner = (
                  <>
                    <span className="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-brand-300 transition-colors duration-300 group-hover:border-brand-400/40 group-hover:text-brand-200">
                      <svg viewBox="0 0 20 20" className="h-5 w-5" aria-hidden>
                        {d.icon}
                      </svg>
                    </span>
                    <span className="min-w-0">
                      <span className="block text-xs font-medium uppercase tracking-wider text-slate-500">
                        {d.label}
                      </span>
                      <span className="mt-0.5 block truncate text-sm font-semibold text-white">
                        {d.value}
                      </span>
                    </span>
                  </>
                );

                return (
                  <div
                    key={d.label}
                    className="reveal"
                    style={{ transitionDelay: `${160 + i * 70}ms` }}
                  >
                    {d.href ? (
                      <a href={d.href} className="group flex items-center gap-4 rounded-xl p-2 transition-colors hover:bg-white/[0.03]">
                        {inner}
                      </a>
                    ) : (
                      <div className="group flex items-center gap-4 p-2">{inner}</div>
                    )}
                  </div>
                );
              })}
            </div>
          </div>

          {/* ── form ── */}
          <div className="reveal" style={{ transitionDelay: '140ms' }}>
            <div className="glass-strong rounded-3xl p-7 shadow-2xl shadow-black/40 sm:p-10">
              {sent ? (
                <div className="flex min-h-[420px] flex-col items-center justify-center text-center">
                  <span className="grid h-16 w-16 place-items-center rounded-2xl border border-signal-400/30 bg-signal-500/10">
                    <svg viewBox="0 0 24 24" className="h-8 w-8 text-signal-400" aria-hidden>
                      <path
                        d="M5 12.5 10 17.5 19 7"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                    </svg>
                  </span>
                  <p className="mt-6 text-lg font-semibold text-white">{L.sent}</p>
                </div>
              ) : (
                <form onSubmit={onSubmit} className="space-y-5">
                  <div className="grid gap-5 sm:grid-cols-2">
                    <label className="block">
                      <span className="mb-2 block text-xs font-medium uppercase tracking-wider text-slate-500">
                        {L.name}
                      </span>
                      <input type="text" name="name" required className="field" placeholder="გიორგი ბერიძე" />
                    </label>
                    <label className="block">
                      <span className="mb-2 block text-xs font-medium uppercase tracking-wider text-slate-500">
                        {L.company}
                      </span>
                      <input type="text" name="company" className="field" placeholder="შპს კომპანია" />
                    </label>
                  </div>

                  <div className="grid gap-5 sm:grid-cols-2">
                    <label className="block">
                      <span className="mb-2 block text-xs font-medium uppercase tracking-wider text-slate-500">
                        {L.email}
                      </span>
                      <input type="email" name="email" required className="field" placeholder="name@company.ge" />
                    </label>
                    <label className="block">
                      <span className="mb-2 block text-xs font-medium uppercase tracking-wider text-slate-500">
                        {L.phone}
                      </span>
                      <input type="tel" name="phone" className="field" placeholder="+995 5xx xx xx xx" />
                    </label>
                  </div>

                  <label className="block">
                    <span className="mb-2 block text-xs font-medium uppercase tracking-wider text-slate-500">
                      {L.message}
                    </span>
                    <textarea
                      name="message"
                      rows={5}
                      required
                      className="field resize-none"
                      placeholder="მოკლედ აღწერეთ თქვენი ამოცანა..."
                    />
                  </label>

                  <button type="submit" className="btn-primary w-full !py-4">
                    {L.submit}
                    <Arrow className="h-4 w-4" />
                  </button>

                  <p className="text-center text-xs leading-relaxed text-slate-500">
                    ფორმის გაგზავნით ეთანხმებით მონაცემთა დამუშავებას. თქვენს ინფორმაციას მესამე
                    მხარეს არ გადავცემთ.
                  </p>
                </form>
              )}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
