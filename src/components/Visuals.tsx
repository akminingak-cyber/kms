/**
 * Generative background + illustration primitives.
 * Everything here is pure CSS/SVG — no external image requests, so the page
 * paints instantly and works offline.
 */

/** Soft drifting colour clouds used behind the hero and CTA band. */
export function AuroraField({ className = '' }: { className?: string }) {
  return (
    <div className={`pointer-events-none absolute inset-0 overflow-hidden ${className}`} aria-hidden>
      <div className="absolute -left-[10%] -top-[20%] h-[520px] w-[520px] rounded-full bg-brand-500/20 blur-[120px] animate-drift" />
      <div
        className="absolute -right-[5%] top-[5%] h-[460px] w-[460px] rounded-full bg-iris-500/20 blur-[130px] animate-drift"
        style={{ animationDelay: '-7s' }}
      />
      <div
        className="absolute bottom-[-25%] left-[30%] h-[440px] w-[440px] rounded-full bg-brand-400/12 blur-[120px] animate-drift"
        style={{ animationDelay: '-14s' }}
      />
    </div>
  );
}

/** Perspective grid floor that slowly pans upward. */
export function GridFloor() {
  return (
    <div className="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden>
      <div
        className="absolute inset-x-0 top-0 h-full opacity-[0.28] animate-grid-pan"
        style={{
          backgroundImage:
            'linear-gradient(to right, rgba(148,163,184,0.14) 1px, transparent 1px),' +
            'linear-gradient(to bottom, rgba(148,163,184,0.14) 1px, transparent 1px)',
          backgroundSize: '64px 64px',
          maskImage: 'radial-gradient(ellipse 80% 60% at 50% 40%, #000 20%, transparent 75%)',
          WebkitMaskImage: 'radial-gradient(ellipse 80% 60% at 50% 40%, #000 20%, transparent 75%)',
        }}
      />
    </div>
  );
}

/** Fine dot matrix — a quieter texture for mid-page sections. */
export function DotField({ className = '' }: { className?: string }) {
  return (
    <div
      className={`pointer-events-none absolute inset-0 ${className}`}
      aria-hidden
      style={{
        backgroundImage: 'radial-gradient(rgba(148,163,184,0.18) 1px, transparent 1px)',
        backgroundSize: '28px 28px',
        maskImage: 'radial-gradient(ellipse 70% 70% at 50% 50%, #000, transparent 78%)',
        WebkitMaskImage: 'radial-gradient(ellipse 70% 70% at 50% 50%, #000, transparent 78%)',
      }}
    />
  );
}

/** Animated node graph — the hero's "network" illustration. */
export function NetworkGraphic({ className = '' }: { className?: string }) {
  const nodes = [
    { x: 50, y: 50, r: 9, core: true },
    { x: 50, y: 14, r: 4.5 },
    { x: 82, y: 32, r: 4.5 },
    { x: 82, y: 70, r: 4.5 },
    { x: 50, y: 88, r: 4.5 },
    { x: 18, y: 70, r: 4.5 },
    { x: 18, y: 32, r: 4.5 },
    { x: 92, y: 12, r: 2.6 },
    { x: 10, y: 90, r: 2.6 },
    { x: 92, y: 90, r: 2.6 },
    { x: 10, y: 12, r: 2.6 },
  ];
  const spokes = nodes.slice(1, 7);

  return (
    <svg viewBox="0 0 100 100" className={className} aria-hidden>
      <defs>
        <radialGradient id="ng-core" cx="50%" cy="50%">
          <stop offset="0%" stopColor="#67E8F9" />
          <stop offset="100%" stopColor="#0891B2" />
        </radialGradient>
        <linearGradient id="ng-line" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="#22D3EE" stopOpacity="0.7" />
          <stop offset="100%" stopColor="#8B5CF6" stopOpacity="0.35" />
        </linearGradient>
      </defs>

      {spokes.map((n, i) => (
        <line
          key={`l${i}`}
          x1="50"
          y1="50"
          x2={n.x}
          y2={n.y}
          stroke="url(#ng-line)"
          strokeWidth="0.5"
        />
      ))}
      {/* outer ring links */}
      {spokes.map((n, i) => {
        const next = spokes[(i + 1) % spokes.length];
        return (
          <line
            key={`r${i}`}
            x1={n.x}
            y1={n.y}
            x2={next.x}
            y2={next.y}
            stroke="#22D3EE"
            strokeOpacity="0.16"
            strokeWidth="0.35"
            strokeDasharray="2 2"
          />
        );
      })}

      {nodes.map((n, i) => (
        <g key={`n${i}`}>
          {n.core && (
            <circle cx={n.x} cy={n.y} r={n.r} fill="#22D3EE" opacity="0.25">
              <animate
                attributeName="r"
                values={`${n.r};${n.r * 2.1};${n.r}`}
                dur="3.2s"
                repeatCount="indefinite"
              />
              <animate attributeName="opacity" values="0.3;0;0.3" dur="3.2s" repeatCount="indefinite" />
            </circle>
          )}
          <circle
            cx={n.x}
            cy={n.y}
            r={n.r}
            fill={n.core ? 'url(#ng-core)' : '#0E1526'}
            stroke={n.core ? 'none' : '#22D3EE'}
            strokeWidth="0.6"
            strokeOpacity="0.65"
          />
        </g>
      ))}

      {/* packets travelling along two spokes */}
      {[0, 3].map((idx) => (
        <circle key={`p${idx}`} r="1.2" fill="#A5F3FC">
          <animateMotion
            dur={`${2.4 + idx * 0.6}s`}
            repeatCount="indefinite"
            path={`M50,50 L${spokes[idx].x},${spokes[idx].y}`}
          />
          <animate attributeName="opacity" values="0;1;1;0" dur={`${2.4 + idx * 0.6}s`} repeatCount="indefinite" />
        </circle>
      ))}
    </svg>
  );
}

/** Layered shield — the security section's illustration. */
export function ShieldGraphic({ className = '' }: { className?: string }) {
  return (
    <svg viewBox="0 0 120 140" className={className} aria-hidden>
      <defs>
        <linearGradient id="sh-fill" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="#22D3EE" stopOpacity="0.28" />
          <stop offset="100%" stopColor="#8B5CF6" stopOpacity="0.12" />
        </linearGradient>
        <linearGradient id="sh-stroke" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor="#67E8F9" />
          <stop offset="100%" stopColor="#7C3AED" />
        </linearGradient>
      </defs>

      {[0, 1, 2].map((i) => {
        const s = 1 - i * 0.16;
        const cx = 60;
        const cy = 68;
        return (
          <path
            key={i}
            d="M60 8 L110 28 V70 C110 100 88 122 60 132 C32 122 10 100 10 70 V28 Z"
            fill={i === 2 ? 'url(#sh-fill)' : 'none'}
            stroke="url(#sh-stroke)"
            strokeOpacity={0.25 + i * 0.28}
            strokeWidth={1.2}
            transform={`translate(${cx - cx * s} ${cy - cy * s}) scale(${s})`}
          />
        );
      })}

      {/* check mark */}
      <path
        d="M42 68 L55 82 L80 54"
        fill="none"
        stroke="#4ADE80"
        strokeWidth="4"
        strokeLinecap="round"
        strokeLinejoin="round"
      >
        <animate attributeName="stroke-dasharray" values="0 90;90 90" dur="1.4s" fill="freeze" />
      </path>
    </svg>
  );
}

/** Isometric stacked server / datacenter illustration. */
export function StackGraphic({ className = '' }: { className?: string }) {
  const layers = [0, 1, 2, 3];
  return (
    <svg viewBox="0 0 200 170" className={className} aria-hidden>
      <defs>
        <linearGradient id="st-top" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="#1A253E" />
          <stop offset="100%" stopColor="#0A0F1E" />
        </linearGradient>
      </defs>
      {layers.map((i) => {
        const y = 18 + i * 34;
        return (
          <g key={i} opacity={1 - i * 0.12}>
            {/* top face */}
            <path
              d={`M100 ${y} L172 ${y + 26} L100 ${y + 52} L28 ${y + 26} Z`}
              fill="url(#st-top)"
              stroke="#22D3EE"
              strokeOpacity="0.4"
              strokeWidth="1"
            />
            {/* left face */}
            <path
              d={`M28 ${y + 26} L100 ${y + 52} L100 ${y + 64} L28 ${y + 38} Z`}
              fill="#070B16"
              stroke="#22D3EE"
              strokeOpacity="0.22"
              strokeWidth="0.8"
            />
            {/* right face */}
            <path
              d={`M172 ${y + 26} L100 ${y + 52} L100 ${y + 64} L172 ${y + 38} Z`}
              fill="#04060D"
              stroke="#8B5CF6"
              strokeOpacity="0.22"
              strokeWidth="0.8"
            />
            {/* status leds */}
            {[0, 1, 2].map((d) => (
              <circle key={d} cx={64 + d * 12} cy={y + 34 + d * 4} r="2" fill="#4ADE80">
                <animate
                  attributeName="opacity"
                  values="0.25;1;0.25"
                  dur={`${1.8 + d * 0.5 + i * 0.3}s`}
                  repeatCount="indefinite"
                />
              </circle>
            ))}
          </g>
        );
      })}
    </svg>
  );
}

/** Line-art icon set keyed to services — keeps a single visual language. */
export function ServiceIcon({ name, className = '' }: { name: string; className?: string }) {
  const common = {
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 1.5,
    strokeLinecap: 'round' as const,
    strokeLinejoin: 'round' as const,
  };

  const paths: Record<string, JSX.Element> = {
    infrastructure: (
      <>
        <rect x="3" y="3" width="18" height="6" rx="1.5" {...common} />
        <rect x="3" y="15" width="18" height="6" rx="1.5" {...common} />
        <path d="M7 6h.01M7 18h.01" {...common} />
        <path d="M12 9v6" {...common} />
      </>
    ),
    security: (
      <>
        <path d="M12 2.5 20 6v6c0 5-3.4 8.6-8 9.5-4.6-.9-8-4.5-8-9.5V6z" {...common} />
        <path d="M9 12.2l2.2 2.3L15.5 10" {...common} />
      </>
    ),
    cloud: (
      <>
        <path d="M6.5 19a4.5 4.5 0 0 1-.6-8.96 6 6 0 0 1 11.64-1.4A4.25 4.25 0 0 1 17.5 19z" {...common} />
        <path d="M12 12v5m0-5-2 2m2-2 2 2" {...common} />
      </>
    ),
    network: (
      <>
        <circle cx="12" cy="12" r="2.4" {...common} />
        <circle cx="5" cy="6" r="2" {...common} />
        <circle cx="19" cy="6" r="2" {...common} />
        <circle cx="5" cy="18" r="2" {...common} />
        <circle cx="19" cy="18" r="2" {...common} />
        <path d="M6.6 7.4 10 10.4M17.4 7.4 14 10.4M6.6 16.6 10 13.6M17.4 16.6 14 13.6" {...common} />
      </>
    ),
    support: (
      <>
        <path d="M4 13a8 8 0 0 1 16 0" {...common} />
        <rect x="2.5" y="13" width="4" height="6" rx="1.6" {...common} />
        <rect x="17.5" y="13" width="4" height="6" rx="1.6" {...common} />
        <path d="M19.5 19v.6a2.4 2.4 0 0 1-2.4 2.4H13" {...common} />
      </>
    ),
    consulting: (
      <>
        <path d="M3 20V9m5.5 11V4M14 20v-8m5.5 8V7" {...common} />
        <circle cx="8.5" cy="4" r="1.4" {...common} />
        <circle cx="14" cy="12" r="1.4" {...common} />
      </>
    ),
  };

  return (
    <svg viewBox="0 0 24 24" className={className} aria-hidden>
      {paths[name] ?? paths.infrastructure}
    </svg>
  );
}

/** Small chevron used on links and buttons. */
export function Arrow({ className = '' }: { className?: string }) {
  return (
    <svg viewBox="0 0 16 16" className={className} aria-hidden>
      <path
        d="M3 8h9m0 0-3.5-3.5M12 8l-3.5 3.5"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.6"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  );
}
