import { useState } from 'react';
import {
  cta,
  differentiators,
  process,
  services,
  solutions,
  testimonials,
  work,
} from '../content/site';
import { useReveal } from '../lib/hooks';
import {
  Arrow,
  AuroraField,
  DotField,
  NetworkGraphic,
  ServiceIcon,
  ShieldGraphic,
  StackGraphic,
} from './Visuals';

function SectionHead({
  eyebrow,
  title,
  accent,
  lead,
  center = false,
}: {
  eyebrow: string;
  title: string;
  accent?: string;
  lead?: string;
  center?: boolean;
}) {
  return (
    <div className={`max-w-2xl ${center ? 'mx-auto text-center' : ''}`}>
      <span className="eyebrow reveal">{eyebrow}</span>
      <h2
        className="reveal mt-5 text-balance text-3xl font-bold tracking-tight sm:text-4xl lg:text-[2.7rem]"
        style={{ transitionDelay: '60ms' }}
      >
        {title} {accent && <span className="gradient-text">{accent}</span>}
      </h2>
      {lead && (
        <p
          className="reveal mt-5 text-base leading-relaxed text-slate-400"
          style={{ transitionDelay: '120ms' }}
        >
          {lead}
        </p>
      )}
    </div>
  );
}

/* ───────────────────────────── Services (bento) ──────────────────────────── */

export function Services() {
  const ref = useReveal<HTMLElement>();

  return (
    <section id="services" ref={ref} className="relative scroll-mt-24 py-24 lg:py-32">
      <DotField className="opacity-40" />
      <div className="shell relative">
        <SectionHead
          eyebrow="სერვისები"
          title="ყველაფერი, რაც ციფრულ"
          accent="ინფრასტრუქტურას სჭირდება"
          lead="ერთი პარტნიორი დაპროექტებიდან ექსპლუატაციამდე — აღარ დაგჭირდებათ ხუთ სხვადასხვა კონტრაქტორთან კოორდინაცია."
        />

        <div className="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {services.map((s, i) => (
            <article
              key={s.key}
              className="glass card-hover reveal group relative flex flex-col overflow-hidden rounded-2xl p-7"
              style={{ transitionDelay: `${i * 70}ms` }}
            >
              {/* corner glow on hover */}
              <div className="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-brand-400/0 blur-3xl transition-all duration-700 group-hover:bg-brand-400/20" />

              <div className="relative flex h-full flex-col">
                <div className="inline-flex h-12 w-12 items-center justify-center rounded-xl border border-brand-400/20 bg-gradient-to-br from-brand-400/15 to-iris-500/10 text-brand-300 transition-transform duration-500 group-hover:scale-110">
                  <ServiceIcon name={s.key} className="h-6 w-6" />
                </div>

                <h3 className="mt-5 text-lg font-semibold text-white">{s.title}</h3>
                <p className="mt-2.5 text-sm leading-relaxed text-slate-400">{s.description}</p>

                <ul className="mt-5 space-y-2">
                  {s.bullets.map((b) => (
                    <li key={b} className="flex items-center gap-2.5 text-[13px] text-slate-400">
                      <span className="h-1 w-1 shrink-0 rounded-full bg-brand-400" />
                      {b}
                    </li>
                  ))}
                </ul>

                <a
                  href="#contact"
                  className="mt-auto inline-flex items-center gap-1.5 pt-7 text-sm font-semibold text-slate-500 transition-colors duration-300 group-hover:text-brand-300"
                >
                  დეტალურად
                  <Arrow className="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-1" />
                </a>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─────────────────────────── Solutions (tabbed) ──────────────────────────── */

export function Solutions() {
  const ref = useReveal<HTMLElement>();
  const [active, setActive] = useState(0);
  const current = solutions[active];

  return (
    <section id="solutions" ref={ref} className="relative scroll-mt-24 overflow-hidden py-24 lg:py-32">
      <div className="pointer-events-none absolute inset-0 bg-gradient-to-b from-transparent via-ink-900/60 to-transparent" />

      <div className="shell relative">
        <SectionHead
          eyebrow="დარგობრივი გადაწყვეტები"
          title="ერთი მიდგომა ყველასთვის"
          accent="არ მუშაობს"
          lead="ბანკის, ქარხნისა და სამინისტროს ამოცანები სხვადასხვაა — ჩვენც შესაბამისად ვაშენებთ."
          center
        />

        <div className="mt-12 flex flex-wrap justify-center gap-2">
          {solutions.map((s, i) => (
            <button
              key={s.id}
              type="button"
              onClick={() => setActive(i)}
              className={`reveal rounded-xl px-5 py-2.5 text-sm font-semibold transition-all duration-300 ${
                i === active
                  ? 'bg-gradient-to-r from-brand-400 to-iris-500 text-ink-950 shadow-lg shadow-brand-500/25'
                  : 'border border-white/10 bg-white/[0.04] text-slate-400 hover:border-brand-400/30 hover:text-white'
              }`}
              style={{ transitionDelay: `${i * 60}ms` }}
              aria-pressed={i === active}
            >
              {s.label}
            </button>
          ))}
        </div>

        <div className="glass reveal mt-10 overflow-hidden rounded-3xl">
          <div className="grid items-center gap-0 lg:grid-cols-[1.15fr_0.85fr]">
            <div className="p-8 sm:p-11">
              <h3 className="text-2xl font-bold text-white sm:text-3xl">{current.title}</h3>
              <p className="mt-4 text-base leading-relaxed text-slate-400">{current.description}</p>

              <ul className="mt-8 space-y-4">
                {current.points.map((p) => (
                  <li key={p} className="flex items-start gap-3.5">
                    <span className="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full border border-signal-400/35 bg-signal-500/12">
                      <svg viewBox="0 0 12 12" className="h-3 w-3 text-signal-400" aria-hidden>
                        <path
                          d="M2.5 6.2 4.8 8.5 9.5 3.8"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth="1.8"
                          strokeLinecap="round"
                          strokeLinejoin="round"
                        />
                      </svg>
                    </span>
                    <span className="text-sm leading-relaxed text-slate-300">{p}</span>
                  </li>
                ))}
              </ul>

              <a href="#contact" className="btn-ghost mt-9">
                ამ სექტორის შესახებ
                <Arrow className="h-4 w-4" />
              </a>
            </div>

            <div className="relative h-full min-h-[280px] overflow-hidden border-t border-white/8 bg-ink-900/60 lg:border-l lg:border-t-0">
              <AuroraField className="opacity-70" />
              <StackGraphic className="absolute left-1/2 top-1/2 h-[78%] w-[78%] -translate-x-1/2 -translate-y-1/2" />
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

/* ────────────────────────────── Process ──────────────────────────────────── */

export function Process() {
  const ref = useReveal<HTMLElement>();

  return (
    <section id="process" ref={ref} className="relative scroll-mt-24 py-24 lg:py-32">
      <div className="shell relative">
        <SectionHead
          eyebrow="როგორ ვმუშაობთ"
          title="ოთხი ეტაპი"
          accent="სიურპრიზების გარეშე"
          lead="ყოველ ეტაპზე ზუსტად იცით, რა კეთდება, რა ღირს და როდის სრულდება."
        />

        <div className="relative mt-16">
          {/* connecting line */}
          <div className="absolute left-0 right-0 top-[38px] hidden h-px bg-gradient-to-r from-transparent via-white/12 to-transparent lg:block" />

          <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
            {process.map((p, i) => (
              <div
                key={p.step}
                className="reveal relative"
                style={{ transitionDelay: `${i * 100}ms` }}
              >
                <div className="relative z-10 inline-flex h-[76px] w-[76px] items-center justify-center rounded-2xl border border-white/10 bg-ink-900 text-2xl font-bold text-white shadow-xl shadow-black/40">
                  <span className="gradient-text">{p.step}</span>
                  <span className="absolute inset-0 rounded-2xl bg-gradient-to-br from-brand-400/10 to-iris-500/5" />
                </div>
                <h3 className="mt-6 text-lg font-semibold text-white">{p.title}</h3>
                <p className="mt-2.5 text-sm leading-relaxed text-slate-400">{p.description}</p>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

/* ────────────────────────────── Work / cases ─────────────────────────────── */

export function Work() {
  const ref = useReveal<HTMLElement>();

  return (
    <section id="work" ref={ref} className="relative scroll-mt-24 py-24 lg:py-32">
      <DotField className="opacity-30" />
      <div className="shell relative">
        <div className="flex flex-wrap items-end justify-between gap-6">
          <SectionHead
            eyebrow="პროექტები"
            title="შედეგი, რომელიც"
            accent="იზომება"
          />
          <a href="#contact" className="btn-ghost reveal shrink-0">
            ყველა პროექტი
            <Arrow className="h-4 w-4" />
          </a>
        </div>

        <div className="mt-14 grid gap-5 lg:grid-cols-3">
          {work.map((w, i) => (
            <article
              key={w.title}
              className="glass card-hover reveal group relative overflow-hidden rounded-2xl"
              style={{ transitionDelay: `${i * 90}ms` }}
            >
              {/* visual header */}
              <div className={`relative h-44 overflow-hidden bg-gradient-to-br ${w.accent}`}>
                <div
                  className="absolute inset-0 opacity-40"
                  style={{
                    backgroundImage:
                      'linear-gradient(to right, rgba(255,255,255,0.07) 1px, transparent 1px),' +
                      'linear-gradient(to bottom, rgba(255,255,255,0.07) 1px, transparent 1px)',
                    backgroundSize: '26px 26px',
                  }}
                />
                <span className="absolute inset-0 transition-transform duration-700 group-hover:scale-110">
                  {w.visual === 'stack' && (
                    <StackGraphic className="absolute -bottom-8 right-2 h-52 w-52 opacity-80" />
                  )}
                  {w.visual === 'network' && (
                    <NetworkGraphic className="absolute -bottom-4 right-3 h-44 w-44 opacity-80" />
                  )}
                  {w.visual === 'shield' && (
                    <ShieldGraphic className="absolute -bottom-6 right-6 h-40 w-32 opacity-75" />
                  )}
                </span>
                <span className="absolute left-5 top-5 rounded-full border border-white/20 bg-ink-950/60 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-white backdrop-blur">
                  {w.tag}
                </span>
              </div>

              <div className="p-6">
                <h3 className="text-lg font-semibold text-white">{w.title}</h3>
                <p className="mt-2.5 text-sm leading-relaxed text-slate-400">{w.summary}</p>

                <div className="mt-6 flex gap-8 border-t border-white/8 pt-5">
                  {w.metrics.map((m) => (
                    <div key={m.label}>
                      <p className="text-xl font-bold text-white">{m.value}</p>
                      <p className="mt-0.5 text-xs text-slate-500">{m.label}</p>
                    </div>
                  ))}
                </div>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ──────────────────────── About / differentiators ────────────────────────── */

export function About() {
  const ref = useReveal<HTMLElement>();

  return (
    <section id="about" ref={ref} className="relative scroll-mt-24 overflow-hidden py-24 lg:py-32">
      <div className="shell relative">
        <div className="grid items-center gap-14 lg:grid-cols-2 lg:gap-20">
          <div className="reveal relative order-2 lg:order-1">
            <div className="relative mx-auto aspect-square w-full max-w-[440px]">
              <div className="absolute inset-0 rounded-3xl border border-white/8 bg-gradient-to-br from-ink-850 to-ink-950" />
              <AuroraField className="rounded-3xl opacity-80" />
              <div
                className="absolute inset-0 rounded-3xl opacity-50"
                style={{
                  backgroundImage:
                    'linear-gradient(to right, rgba(148,163,184,0.08) 1px, transparent 1px),' +
                    'linear-gradient(to bottom, rgba(148,163,184,0.08) 1px, transparent 1px)',
                  backgroundSize: '38px 38px',
                }}
              />
              <ShieldGraphic className="absolute left-1/2 top-1/2 h-[62%] w-[62%] -translate-x-1/2 -translate-y-1/2" />

              <div className="glass absolute -bottom-5 left-1/2 w-[85%] -translate-x-1/2 rounded-2xl p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-[10px] font-medium uppercase tracking-wider text-slate-500">
                      სერტიფიცირება
                    </p>
                    <p className="mt-0.5 text-sm font-semibold text-white">ISO/IEC 27001</p>
                  </div>
                  <span className="rounded-full border border-signal-400/30 bg-signal-500/10 px-2.5 py-1 text-[10px] font-semibold text-signal-400">
                    აქტიური
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div className="order-1 lg:order-2">
            <SectionHead
              eyebrow="რატომ KMS"
              title="ინჟინრები, და არა"
              accent="გამყიდველები"
              lead="თქვენთან ის ადამიანები საუბრობენ, ვინც სისტემას რეალურად აშენებს და მართავს."
            />

            <div className="mt-10 grid gap-x-8 gap-y-7 sm:grid-cols-2">
              {differentiators.map((d, i) => (
                <div key={d.title} className="reveal" style={{ transitionDelay: `${i * 80}ms` }}>
                  <div className="flex items-center gap-2.5">
                    <span className="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-brand-400/20 to-iris-500/15 text-brand-300">
                      <svg viewBox="0 0 12 12" className="h-3.5 w-3.5" aria-hidden>
                        <path
                          d="M2.5 6.2 4.8 8.5 9.5 3.8"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth="1.8"
                          strokeLinecap="round"
                          strokeLinejoin="round"
                        />
                      </svg>
                    </span>
                    <h3 className="text-[15px] font-semibold text-white">{d.title}</h3>
                  </div>
                  <p className="mt-2 pl-[34px] text-sm leading-relaxed text-slate-400">
                    {d.description}
                  </p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

/* ──────────────────────────── Testimonials ───────────────────────────────── */

export function Testimonials() {
  const ref = useReveal<HTMLElement>();

  return (
    <section ref={ref} className="relative py-24 lg:py-28">
      <div className="shell relative">
        <SectionHead eyebrow="კლიენტები" title="რას ამბობენ" accent="ჩვენზე" center />

        <div className="mt-14 grid gap-5 lg:grid-cols-3">
          {testimonials.map((t, i) => (
            <figure
              key={t.author + t.role}
              className="glass card-hover reveal relative overflow-hidden rounded-2xl p-7"
              style={{ transitionDelay: `${i * 90}ms` }}
            >
              <svg viewBox="0 0 32 32" className="h-8 w-8 text-brand-400/35" aria-hidden>
                <path
                  d="M13 8v8c0 4.4-3 7.4-7 8v-3.4c2-.6 3.4-2.2 3.4-4.2H6V8zm13 0v8c0 4.4-3 7.4-7 8v-3.4c2-.6 3.4-2.2 3.4-4.2H19V8z"
                  fill="currentColor"
                />
              </svg>
              <blockquote className="mt-5 text-[15px] leading-relaxed text-slate-300">
                {t.quote}
              </blockquote>
              <figcaption className="mt-6 flex items-center gap-3 border-t border-white/8 pt-5">
                <span className="grid h-10 w-10 place-items-center rounded-full bg-gradient-to-br from-brand-400/25 to-iris-500/20 text-sm font-bold text-brand-200">
                  {t.author.charAt(0)}
                </span>
                <span>
                  <span className="block text-sm font-semibold text-white">{t.author}</span>
                  <span className="block text-xs text-slate-500">{t.role}</span>
                </span>
              </figcaption>
            </figure>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ───────────────────────────── CTA band ──────────────────────────────────── */

export function CtaBand() {
  const ref = useReveal<HTMLElement>();

  return (
    <section ref={ref} className="relative py-20 lg:py-24">
      <div className="shell">
        <div className="relative overflow-hidden rounded-3xl border border-white/10 bg-ink-900 px-7 py-14 text-center sm:px-14 lg:py-20">
          <AuroraField />
          <div
            className="pointer-events-none absolute inset-0 opacity-30"
            style={{
              backgroundImage:
                'linear-gradient(to right, rgba(148,163,184,0.1) 1px, transparent 1px),' +
                'linear-gradient(to bottom, rgba(148,163,184,0.1) 1px, transparent 1px)',
              backgroundSize: '48px 48px',
              maskImage: 'radial-gradient(ellipse 70% 70% at 50% 50%, #000, transparent 75%)',
              WebkitMaskImage: 'radial-gradient(ellipse 70% 70% at 50% 50%, #000, transparent 75%)',
            }}
          />

          <div className="relative mx-auto max-w-2xl">
            <h2 className="reveal text-balance text-3xl font-bold tracking-tight sm:text-4xl lg:text-[2.7rem]">
              {cta.title}
            </h2>
            <p
              className="reveal mx-auto mt-5 max-w-xl text-base leading-relaxed text-slate-400"
              style={{ transitionDelay: '80ms' }}
            >
              {cta.subtitle}
            </p>
            <div
              className="reveal mt-9 flex flex-col justify-center gap-3 sm:flex-row"
              style={{ transitionDelay: '160ms' }}
            >
              <a href="#contact" className="btn-primary">
                {cta.primary}
                <Arrow className="h-4 w-4" />
              </a>
              <a href="#contact" className="btn-ghost">
                {cta.secondary}
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
