/**
 * Built-in vector illustrations.
 *
 * These are not decoration standing in for a missing photo — each one draws the
 * thing the surrounding copy is describing, which is often more useful to a
 * prospective client than a stock photograph of a server room would be.
 *
 * They are drawn entirely from the palette tokens, so the same illustration
 * reads correctly on a dark section and on a light one with no extra work. Each
 * fills its container and is rendered inside <Figure>, which owns the aspect
 * ratio and the accessible label — that is why every element here is
 * aria-hidden and the SVG itself carries no title.
 */

import { SERVICE_ILLUSTRATIONS } from './service-illustrations';
import { INDUSTRY_ILLUSTRATIONS } from './industry-illustrations';
import { ARGUMENT_ILLUSTRATIONS } from './argument-illustrations';

const stroke = 'rgb(var(--c-line) / 0.14)';
const strokeStrong = 'rgb(var(--c-line) / 0.24)';
const accent = 'rgb(var(--c-accent-500))';
const primary = 'rgb(var(--c-primary-400))';
const surface = 'rgb(var(--c-surface-2) / 0.7)';
const surfaceDeep = 'rgb(var(--c-surface-3) / 0.55)';
const label = 'rgb(var(--c-text-tertiary))';

const svgProps = {
  className: 'h-full w-full',
  preserveAspectRatio: 'xMidYMid meet',
  'aria-hidden': true as const,
  focusable: 'false' as const,
};

/* -------------------------------------------------------------------------- */

function NetworkTopology() {
  const nodes = [
    { x: 300, y: 88 },
    { x: 132, y: 152 },
    { x: 462, y: 140 },
    { x: 108, y: 300 },
    { x: 492, y: 296 },
    { x: 236, y: 356 },
  ];
  return (
    <svg viewBox="0 0 600 450" {...svgProps}>
      <defs>
        <radialGradient id="nt-glow" cx="50%" cy="45%" r="55%">
          <stop offset="0%" stopColor={primary} stopOpacity="0.22" />
          <stop offset="100%" stopColor={primary} stopOpacity="0" />
        </radialGradient>
      </defs>

      <rect width="600" height="450" fill="url(#nt-glow)" />

      {/* Links from the core switch out to every edge node. */}
      <g stroke={accent} strokeOpacity="0.42" strokeWidth="1.4" strokeDasharray="5 7" fill="none">
        {nodes.map((node) => (
          <line key={`${node.x}-${node.y}`} x1="300" y1="228" x2={node.x} y2={node.y} />
        ))}
      </g>

      {/* Buildings served by the network. */}
      <g fill={surface} stroke={strokeStrong} strokeWidth="1.4">
        <rect x="56" y="316" width="96" height="96" rx="4" />
        <rect x="196" y="352" width="118" height="60" rx="4" />
        <rect x="446" y="292" width="104" height="120" rx="4" />
      </g>
      <g fill={stroke}>
        {[0, 1, 2].map((row) =>
          [0, 1, 2].map((col) => (
            <rect key={`a${row}${col}`} x={70 + col * 26} y={332 + row * 26} width="14" height="14" rx="2" />
          )),
        )}
        {[0, 1].map((row) =>
          [0, 1, 2, 3].map((col) => (
            <rect key={`b${row}${col}`} x={210 + col * 26} y={366 + row * 22} width="14" height="12" rx="2" />
          )),
        )}
        {[0, 1, 2, 3].map((row) =>
          [0, 1, 2].map((col) => (
            <rect key={`c${row}${col}`} x={460 + col * 28} y={308 + row * 26} width="16" height="14" rx="2" />
          )),
        )}
      </g>

      {/* Edge nodes. */}
      {nodes.map((node, index) => (
        <circle
          key={`n${node.x}`}
          cx={node.x}
          cy={node.y}
          r="6"
          fill={accent}
          className="animate-pulse-node"
          style={{ animationDelay: `${index * 340}ms` }}
        />
      ))}

      {/* Core. */}
      <circle cx="300" cy="228" r="30" fill="none" stroke={accent} strokeOpacity="0.4" strokeWidth="1.4" />
      <circle cx="300" cy="228" r="19" fill="rgb(var(--c-primary-500) / 0.18)" stroke={primary} strokeWidth="1.6" />
      <circle cx="300" cy="228" r="7" fill={accent} />
    </svg>
  );
}

/* -------------------------------------------------------------------------- */

function RackElevation() {
  /** 1U ≈ 26px. Laid out the way a real rack elevation drawing is. */
  const units = [
    { y: 46, h: 22, kind: 'patch', text: 'PATCH PANEL 24P' },
    { y: 72, h: 22, kind: 'patch', text: 'PATCH PANEL 24P' },
    { y: 98, h: 10, kind: 'manager', text: '' },
    { y: 112, h: 26, kind: 'switch', text: 'SWITCH 48P PoE+' },
    { y: 142, h: 26, kind: 'switch', text: 'SWITCH 24P' },
    { y: 172, h: 10, kind: 'manager', text: '' },
    { y: 186, h: 30, kind: 'server', text: 'NVR / STORAGE' },
    { y: 220, h: 30, kind: 'server', text: 'SERVER' },
    { y: 254, h: 10, kind: 'manager', text: '' },
    { y: 268, h: 34, kind: 'ups', text: 'UPS' },
  ];

  return (
    <svg viewBox="0 0 460 360" {...svgProps}>
      {/* Rack frame. */}
      <rect x="70" y="24" width="320" height="304" rx="6" fill={surfaceDeep} stroke={strokeStrong} strokeWidth="1.6" />
      <line x1="94" y1="24" x2="94" y2="328" stroke={stroke} strokeWidth="1.2" />
      <line x1="366" y1="24" x2="366" y2="328" stroke={stroke} strokeWidth="1.2" />

      {/* Rail holes. */}
      <g fill={stroke}>
        {Array.from({ length: 22 }, (_, i) => (
          <g key={i}>
            <rect x="80" y={36 + i * 13} width="5" height="5" rx="1" />
            <rect x="375" y={36 + i * 13} width="5" height="5" rx="1" />
          </g>
        ))}
      </g>

      {units.map((unit) => {
        if (unit.kind === 'manager') {
          return (
            <rect
              key={unit.y}
              x="98"
              y={unit.y}
              width="264"
              height={unit.h}
              rx="2"
              fill="rgb(var(--c-surface-4) / 0.35)"
              stroke={stroke}
              strokeWidth="1"
            />
          );
        }
        return (
          <g key={unit.y}>
            <rect
              x="98"
              y={unit.y}
              width="264"
              height={unit.h}
              rx="3"
              fill={surface}
              stroke={unit.kind === 'ups' ? primary : strokeStrong}
              strokeWidth={unit.kind === 'ups' ? 1.6 : 1.2}
            />
            {unit.kind === 'patch' && (
              <g fill="rgb(var(--c-accent-500) / 0.55)">
                {Array.from({ length: 12 }, (_, i) => (
                  <rect key={i} x={110 + i * 21} y={unit.y + 7} width="13" height="8" rx="1.5" />
                ))}
              </g>
            )}
            {unit.kind === 'switch' && (
              <>
                <g fill="rgb(var(--c-line) / 0.22)">
                  {Array.from({ length: 16 }, (_, i) => (
                    <rect key={i} x={110 + i * 15} y={unit.y + 8} width="9" height="10" rx="1.5" />
                  ))}
                </g>
                <circle cx="352" cy={unit.y + 13} r="3" fill={accent} />
              </>
            )}
            {unit.kind === 'server' && (
              <>
                <rect x="108" y={unit.y + 8} width="40" height="14" rx="2" fill="rgb(var(--c-line) / 0.16)" />
                <g fill="rgb(var(--c-line) / 0.16)">
                  {Array.from({ length: 4 }, (_, i) => (
                    <rect key={i} x={162 + i * 44} y={unit.y + 8} width="36" height="14" rx="2" />
                  ))}
                </g>
                <circle cx="352" cy={unit.y + 15} r="3" fill={accent} />
              </>
            )}
            {unit.kind === 'ups' && (
              <>
                <rect x="110" y={unit.y + 9} width="70" height="16" rx="2" fill="rgb(var(--c-primary-500) / 0.25)" />
                <path
                  d={`M300 ${unit.y + 8} l-9 12 h7 l-3 10 12 -14 h-8 z`}
                  fill={accent}
                />
              </>
            )}
          </g>
        );
      })}

      {/* Dimension line — the detail that makes it read as an engineering drawing. */}
      <g stroke={stroke} strokeWidth="1">
        <line x1="46" y1="24" x2="46" y2="328" />
        <line x1="41" y1="24" x2="51" y2="24" />
        <line x1="41" y1="328" x2="51" y2="328" />
      </g>
      <text x="30" y="180" fill={label} fontSize="11" fontFamily="var(--font-mono)" transform="rotate(-90 30 180)" textAnchor="middle">
        42U
      </text>
    </svg>
  );
}

/* -------------------------------------------------------------------------- */

function SmartHomeZones() {
  const zones = [
    { x: 44, y: 52, w: 168, h: 118, icon: 'sofa', name: 'LIVING' },
    { x: 220, y: 52, w: 128, h: 118, icon: 'bed', name: 'BED' },
    { x: 356, y: 52, w: 120, h: 118, icon: 'bath', name: 'BATH' },
    { x: 44, y: 178, w: 128, h: 118, icon: 'kitchen', name: 'KITCHEN' },
    { x: 180, y: 178, w: 168, h: 118, icon: 'hall', name: 'HALL' },
    { x: 356, y: 178, w: 120, h: 118, icon: 'garage', name: 'GARAGE' },
  ];

  return (
    <svg viewBox="0 0 520 360" {...svgProps}>
      {/* Building outline. */}
      <rect x="36" y="44" width="448" height="260" rx="6" fill={surfaceDeep} stroke={strokeStrong} strokeWidth="1.8" />

      {zones.map((zone) => (
        <g key={zone.name}>
          <rect
            x={zone.x}
            y={zone.y}
            width={zone.w}
            height={zone.h}
            rx="3"
            fill={surface}
            stroke={stroke}
            strokeWidth="1.2"
          />
          <text
            x={zone.x + 10}
            y={zone.y + 20}
            fill={label}
            fontSize="9"
            letterSpacing="1.2"
            fontFamily="var(--font-mono)"
          >
            {zone.name}
          </text>
          {/* Zone controller node. */}
          <circle cx={zone.x + zone.w - 18} cy={zone.y + zone.h - 18} r="4.5" fill={accent} fillOpacity="0.8" />
        </g>
      ))}

      {/* Bus running to every zone controller. */}
      <g stroke={accent} strokeOpacity="0.45" strokeWidth="1.4" strokeDasharray="4 6" fill="none">
        <path d="M260 322 L260 296" />
        <path d="M60 322 H460" />
        {zones.map((zone) => (
          <path
            key={`link-${zone.name}`}
            d={`M${zone.x + zone.w - 18} ${zone.y + zone.h - 18} V322`}
          />
        ))}
      </g>

      {/* Controller. */}
      <rect x="212" y="316" width="96" height="30" rx="5" fill="rgb(var(--c-primary-500) / 0.2)" stroke={primary} strokeWidth="1.6" />
      <text x="260" y="335" fill="rgb(var(--c-text-primary))" fontSize="11" textAnchor="middle" fontFamily="var(--font-mono)">
        CONTROLLER
      </text>

      {/* Scenario legend. */}
      <g>
        {[
          { x: 60, c: accent, t: 'LIGHT' },
          { x: 152, c: primary, t: 'CLIMATE' },
          { x: 256, c: 'rgb(var(--c-success))', t: 'SECURITY' },
          { x: 372, c: 'rgb(var(--c-accent-300))', t: 'BLINDS' },
        ].map((item) => (
          <g key={item.t}>
            <circle cx={item.x} cy="22" r="4" fill={item.c} />
            <text x={item.x + 12} y="26" fill={label} fontSize="10" letterSpacing="0.8" fontFamily="var(--font-mono)">
              {item.t}
            </text>
          </g>
        ))}
      </g>
    </svg>
  );
}

/* -------------------------------------------------------------------------- */

function HandoverDocs() {
  return (
    <svg viewBox="0 0 520 380" {...svgProps}>
      {/* Back sheet — the as-built diagram. */}
      <g transform="rotate(-6 250 190)">
        <rect x="76" y="42" width="230" height="290" rx="6" fill={surface} stroke={stroke} strokeWidth="1.3" />
        <g stroke={strokeStrong} strokeWidth="1.1" fill="none">
          <rect x="104" y="78" width="58" height="40" rx="3" />
          <rect x="216" y="78" width="58" height="40" rx="3" />
          <rect x="160" y="182" width="58" height="40" rx="3" />
          <path d="M133 118 V150 H189 V182" />
          <path d="M245 118 V150 H189" />
          <path d="M189 222 V262" />
        </g>
        <circle cx="189" cy="268" r="6" fill={accent} fillOpacity="0.7" />
        <g fill={stroke}>
          <rect x="104" y="292" width="120" height="6" rx="3" />
          <rect x="104" y="306" width="84" height="6" rx="3" />
        </g>
      </g>

      {/* Front sheet — the port register. */}
      <g transform="rotate(3 300 200)">
        <rect x="214" y="66" width="238" height="286" rx="6" fill={surfaceDeep} stroke={strokeStrong} strokeWidth="1.5" />
        <rect x="214" y="66" width="238" height="34" rx="6" fill="rgb(var(--c-primary-500) / 0.16)" />
        <text x="234" y="88" fill="rgb(var(--c-text-primary))" fontSize="12" fontFamily="var(--font-mono)" letterSpacing="1">
          PORT REGISTER
        </text>
        {Array.from({ length: 9 }, (_, row) => (
          <g key={row}>
            <text
              x="234"
              y={126 + row * 24}
              fill={label}
              fontSize="10"
              fontFamily="var(--font-mono)"
            >
              {`A-${String(row + 1).padStart(2, '0')}`}
            </text>
            <rect x="278" y={118 + row * 24} width="96" height="7" rx="3.5" fill={stroke} />
            <rect
              x="388"
              y={118 + row * 24}
              width="44"
              height="7"
              rx="3.5"
              fill={row % 3 === 0 ? accent : stroke}
              fillOpacity={row % 3 === 0 ? 0.7 : 1}
            />
          </g>
        ))}
      </g>

      {/* Cable label — the thing that makes a fault findable years later. */}
      <g transform="rotate(-14 108 300)">
        <rect x="46" y="284" width="124" height="42" rx="5" fill="rgb(var(--c-surface-2))" stroke={primary} strokeWidth="1.5" />
        <text x="60" y="303" fill={label} fontSize="9" fontFamily="var(--font-mono)" letterSpacing="1">
          LABEL
        </text>
        <text x="60" y="318" fill="rgb(var(--c-text-primary))" fontSize="12" fontFamily="var(--font-mono)">
          SW1/A-04
        </text>
      </g>
    </svg>
  );
}

/* -------------------------------------------------------------------------- */

export const ILLUSTRATIONS = {
  NetworkTopology,
  RackElevation,
  SmartHomeZones,
  HandoverDocs,
  ...SERVICE_ILLUSTRATIONS,
  ...INDUSTRY_ILLUSTRATIONS,
  ...ARGUMENT_ILLUSTRATIONS,
} as const;

export type IllustrationName = keyof typeof ILLUSTRATIONS;
