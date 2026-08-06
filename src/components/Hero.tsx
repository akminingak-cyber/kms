import { hero, partners, stats } from '../content/site';
import { useCountUp, useReveal } from '../lib/hooks';
import { Arrow, AuroraField, GridFloor, NetworkGraphic } from './Visuals';

function Stat({
  value,
  suffix,
  label,
  decimals = 0,
}: {
  value: number;
  suffix: string;
  label: string;
  decimals?: number;
}) {
  const { ref, display } = useCountUp(value, decimals);
  return (
    <div className="text-center sm:text-left">
      <div className="flex items-baseline justify-center gap-0.5 sm:justify-start">
        <span ref={ref} className="text-3xl font-bold tracking-tight text-white sm:text-4xl">
          {display}
        </span>
        <span className="text-2xl font-bold text-brand-400 sm:text-3xl">{suffix}</span>
      </div>
      <p className="mt-1.5 text-xs font-medium text-slate-500 sm:text-sm">{label}</p>
    </div>
  );
}

function StatusPanel() {
  const toneMap = {
    ok: 'text-signal-400',
    alert: 'text-amber-400',
    neutral: 'text-brand-300',
  } as const;

  return (
    <div className="glass-strong relative w-[290px] rounded-2xl p-5 shadow-2xl shadow-black/50 sm:w-[310px]">
      {/* sweeping highlight */}
      <div className="pointer-events-none absolute inset-0 overflow-hidden rounded-2xl">
        <div className="absolute -inset-y-8 w-24 -skew-x-12 bg-gradient-to-r from-transparent via-white/[0.07] to-transparent animate-sweep" />
      </div>

      <div className="relative flex items-start justify-between gap-4">
        <div>
          <p className="text-sm font-semibold text-white">{hero.panel.title}</p>
          <p className="mt-0.5 text-xs text-slate-500">{hero.panel.subtitle}</p>
        </div>
        <span className="relative mt-1 flex h-2.5 w-2.5 shrink-0">
          <span className="absolute inline-flex h-full w-full rounded-full bg-signal-400 animate-pulse-ring" />
          <span className="relative inline-flex h-2.5 w-2.5 rounded-full bg-signal-400" />
        </span>
      </div>

      <div className="relative mt-5 space-y-3">
        {hero.panel.rows.map((row) => (
          <div key={row.label} className="flex items-center justify-between gap-3">
            <span className="text-xs text-slate-400">{row.label}</span>
            <span className={`text-xs font-semibold tabular-nums ${toneMap[row.tone as keyof typeof toneMap]}`}>
              {row.value}
            </span>
          </div>
        ))}
      </div>

      {/* mini sparkline */}
      <div className="relative mt-5 flex h-10 items-end gap-1">
        {[38, 52, 44, 68, 58, 76, 64, 88, 72, 94, 82, 100].map((h, i) => (
          <div
            key={i}
            className="flex-1 rounded-sm bg-gradient-to-t from-brand-500/25 to-brand-400/80"
            style={{ height: `${h}%` }}
          />
        ))}
      </div>
    </div>
  );
}

export default function Hero() {
  const ref = useReveal<HTMLElement>();

  return (
    <section id="top" ref={ref} className="relative overflow-hidden pb-20 pt-32 sm:pt-40 lg:pb-28 lg:pt-44">
      <AuroraField />
      <GridFloor />
      {/* horizon glow */}
      <div className="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-brand-400/50 to-transparent" />

      <div className="shell relative">
        <div className="grid items-center gap-14 lg:grid-cols-[1.05fr_0.95fr] lg:gap-10">
          {/* ── copy ── */}
          <div>
            <span className="eyebrow reveal">
              <span className="relative flex h-1.5 w-1.5">
                <span className="absolute inline-flex h-full w-full rounded-full bg-brand-400 animate-pulse-ring" />
                <span className="relative inline-flex h-1.5 w-1.5 rounded-full bg-brand-400" />
              </span>
              {hero.badge}
            </span>

            <h1
              className="reveal mt-6 text-balance text-4xl font-bold leading-[1.12] tracking-tight text-white sm:text-5xl lg:text-[3.6rem]"
              style={{ transitionDelay: '80ms' }}
            >
              {hero.title}{' '}
              <span className="gradient-text">{hero.titleAccent}</span>
            </h1>

            <p
              className="reveal mt-6 max-w-xl text-base leading-relaxed text-slate-400 sm:text-lg"
              style={{ transitionDelay: '160ms' }}
            >
              {hero.subtitle}
            </p>

            <div
              className="reveal mt-9 flex flex-col gap-3 sm:flex-row"
              style={{ transitionDelay: '240ms' }}
            >
              <a href="#contact" className="btn-primary">
                {hero.primaryCta}
                <Arrow className="h-4 w-4" />
              </a>
              <a href="#services" className="btn-ghost">
                {hero.secondaryCta}
              </a>
            </div>

            <div
              className="reveal mt-14 grid grid-cols-2 gap-x-6 gap-y-8 border-t border-white/8 pt-8 sm:grid-cols-4"
              style={{ transitionDelay: '320ms' }}
            >
              {stats.map((s) => (
                <Stat key={s.label} {...s} />
              ))}
            </div>
          </div>

          {/* ── visual ── */}
          <div className="reveal relative" style={{ transitionDelay: '200ms' }}>
            <div className="relative mx-auto aspect-square w-full max-w-[520px]">
              {/* rings + node graph, nudged up so the status panel clears the core */}
              <div className="absolute inset-0 -translate-y-8 lg:-translate-y-10">
                <div className="absolute inset-[6%] rounded-full border border-white/[0.07]" />
                <div className="absolute inset-[17%] rounded-full border border-white/[0.06]" />
                <div className="absolute inset-[28%] rounded-full border border-brand-400/12" />
                <div className="absolute inset-[39%] rounded-full border border-iris-400/10" />
                <NetworkGraphic className="absolute inset-[13%] h-[74%] w-[74%]" />
              </div>

              {/* floating panel — lower-left, clear of the core node */}
              <div className="absolute -bottom-8 -left-3 animate-float sm:-left-10 lg:-left-20">
                <StatusPanel />
              </div>

              {/* floating badge */}
              <div
                className="glass absolute -right-1 top-2 animate-float rounded-xl px-4 py-3 sm:right-2"
                style={{ animationDelay: '-3.5s' }}
              >
                <p className="text-[10px] font-medium uppercase tracking-wider text-slate-500">
                  Uptime SLA
                </p>
                <p className="mt-0.5 text-xl font-bold text-white">
                  99.9<span className="text-brand-400">%</span>
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* ── partner marquee ── */}
        <div className="reveal mt-20 lg:mt-24" style={{ transitionDelay: '400ms' }}>
          <p className="text-center text-xs font-medium uppercase tracking-[0.2em] text-slate-600">
            ტექნოლოგიური პარტნიორები
          </p>
          <div className="mask-fade-x mt-7 overflow-hidden">
            <div className="flex w-max animate-marquee items-center gap-14">
              {[...partners, ...partners].map((p, i) => (
                <span
                  key={`${p}-${i}`}
                  className="whitespace-nowrap text-xl font-semibold tracking-tight text-slate-600 transition-colors duration-300 hover:text-slate-300"
                >
                  {p}
                </span>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
