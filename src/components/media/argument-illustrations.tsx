/**
 * Two illustrations that make the argument their page is making, rather than
 * decorating around it.
 *
 * The about page claims that one accountable supplier beats coordinating four;
 * the process page claims each stage hands you something concrete. Both are
 * assertions a visitor has to take on trust from prose — drawing them is the
 * cheapest way to make them checkable.
 */

const line = 'rgb(var(--c-line) / 0.14)';
const lineStrong = 'rgb(var(--c-line) / 0.26)';
const accent = 'rgb(var(--c-accent-500))';
const primary = 'rgb(var(--c-primary-400))';
const surface = 'rgb(var(--c-surface-2) / 0.75)';
const surfaceDeep = 'rgb(var(--c-surface-3) / 0.5)';
const label = 'rgb(var(--c-text-tertiary))';
const heading = 'rgb(var(--c-text-primary))';
const warn = 'rgb(var(--c-warning))';
const ok = 'rgb(var(--c-success))';

const svg = {
  className: 'h-full w-full',
  preserveAspectRatio: 'xMidYMid meet',
  'aria-hidden': true as const,
  focusable: 'false' as const,
};
const mono = { fontFamily: 'var(--font-mono)' } as const;

/* ========================================================================== */
/* About — four contractors with gaps between them, against one team that owns */
/* the whole thing. The gaps are the point: nobody owns them.                  */
/* ========================================================================== */

function SingleVendor() {
  const trades = ['CABLING', 'NETWORK', 'CCTV', 'AUTOMATION'];

  return (
    <svg viewBox="0 0 560 400" {...svg}>
      {/* ---- Left: separate contractors ---------------------------------- */}
      <text x="42" y="46" fill={label} fontSize="9" letterSpacing="1.2" {...mono}>
        FOUR CONTRACTORS
      </text>

      {trades.map((trade, i) => (
        <g key={trade}>
          <rect
            x="42"
            y={62 + i * 60}
            width="178"
            height="42"
            rx="4"
            fill={surface}
            stroke={line}
            strokeWidth="1.2"
          />
          <text x="58" y={88 + i * 60} fill={label} fontSize="9.5" letterSpacing="0.6" {...mono}>
            {trade}
          </text>
          {/* Nobody's responsibility, drawn as the hole it is. */}
          {i < trades.length - 1 && (
            <g>
              <path
                d={`M131 ${104 + i * 60} v18`}
                stroke={warn}
                strokeOpacity="0.5"
                strokeWidth="1.3"
                strokeDasharray="3 4"
              />
              <circle cx="131" cy={113 + i * 60} r="7" fill="rgb(var(--c-surface-base))" stroke={warn} strokeOpacity="0.6" strokeWidth="1.2" />
              <text x="131" y={117 + i * 60} fill={warn} fontSize="9" textAnchor="middle" fontWeight="700" {...mono}>
                ?
              </text>
            </g>
          )}
        </g>
      ))}

      <text x="131" y="336" fill={warn} fontSize="8.5" textAnchor="middle" {...mono}>
        GAPS BELONG TO NOBODY
      </text>
      <text x="131" y="352" fill={label} fontSize="8.5" textAnchor="middle" {...mono}>
        YOU COORDINATE
      </text>

      {/* ---- Divider ------------------------------------------------------ */}
      <path d="M280 40 V366" stroke={lineStrong} strokeWidth="1" strokeDasharray="5 6" />

      {/* ---- Right: one accountable team --------------------------------- */}
      <text x="340" y="46" fill={label} fontSize="9" letterSpacing="1.2" {...mono}>
        ONE TEAM
      </text>

      <rect
        x="340"
        y="62"
        width="178"
        height="222"
        rx="6"
        fill="rgb(var(--c-primary-500) / 0.1)"
        stroke={primary}
        strokeWidth="1.7"
      />

      {trades.map((trade, i) => (
        <g key={`own-${trade}`}>
          <rect
            x="358"
            y={82 + i * 52}
            width="142"
            height="36"
            rx="3"
            fill={surfaceDeep}
            stroke={line}
            strokeWidth="1"
          />
          <text x="374" y={105 + i * 52} fill={label} fontSize="9.5" letterSpacing="0.6" {...mono}>
            {trade}
          </text>
          {/* Continuous spine: the interfaces are inside one scope. */}
          {i < trades.length - 1 && (
            <path d={`M429 ${118 + i * 52} v14`} stroke={accent} strokeOpacity="0.65" strokeWidth="2" />
          )}
        </g>
      ))}

      <circle cx="429" cy="304" r="13" fill="rgb(var(--c-success) / 0.15)" stroke={ok} strokeWidth="1.5" />
      <path d="M423 304 l4.5 4.5 l8 -9" stroke={ok} strokeWidth="2" fill="none" strokeLinecap="round" strokeLinejoin="round" />
      <path d="M429 284 v7" stroke={accent} strokeOpacity="0.65" strokeWidth="2" />

      <text x="429" y="336" fill={ok} fontSize="8.5" textAnchor="middle" {...mono}>
        ONE POINT OF ACCOUNTABILITY
      </text>
      <text x="429" y="352" fill={label} fontSize="8.5" textAnchor="middle" {...mono}>
        WE COORDINATE
      </text>
    </svg>
  );
}

/* ========================================================================== */
/* Process — six stages on one axis, each with the artefact it hands over.     */
/* The claim being drawn is that you can tell where the project stands.        */
/* ========================================================================== */

function ProcessTimeline() {
  const stages = [
    { n: '01', name: 'CONSULT', out: 'BRIEF' },
    { n: '02', name: 'SURVEY', out: 'SITE REPORT' },
    { n: '03', name: 'DESIGN', out: 'DRAWINGS' },
    { n: '04', name: 'INSTALL', out: 'TEST RECORDS' },
    { n: '05', name: 'HANDOVER', out: 'AS-BUILT PACK' },
    { n: '06', name: 'SUPPORT', out: 'SLA' },
  ];
  const x = (i: number) => 62 + i * 87;

  return (
    <svg viewBox="0 0 560 400" {...svg}>
      {/* Axis. */}
      <path d="M48 176 H524" stroke={lineStrong} strokeWidth="1.4" />
      <path d="M516 170 l10 6 l-10 6 z" fill={lineStrong} />

      {stages.map((stage, i) => {
        const cx = x(i);
        const above = i % 2 === 0;
        return (
          <g key={stage.n}>
            {/* Stage marker. */}
            <circle cx={cx} cy="176" r="15" fill="rgb(var(--c-surface-1))" stroke={primary} strokeWidth="1.8" />
            <text x={cx} y="181" fill={heading} fontSize="11" textAnchor="middle" fontWeight="700" {...mono}>
              {stage.n}
            </text>

            {/* Segment between stages, so progress reads left to right. */}
            {i < stages.length - 1 && (
              <path
                d={`M${cx + 15} 176 H${x(i + 1) - 15}`}
                stroke={accent}
                strokeOpacity="0.45"
                strokeWidth="2"
                strokeDasharray="5 5"
              />
            )}

            {/* Name and the artefact it produces, alternating sides so six fit. */}
            <path
              d={above ? `M${cx} 161 v-22` : `M${cx} 191 v22`}
              stroke={line}
              strokeWidth="1.1"
            />
            <text
              x={cx}
              y={above ? 130 : 232}
              fill={heading}
              fontSize="10"
              textAnchor="middle"
              letterSpacing="0.6"
              {...mono}
            >
              {stage.name}
            </text>
            <rect
              x={cx - 40}
              y={above ? 84 : 244}
              width="80"
              height="30"
              rx="4"
              fill={surface}
              stroke={accent}
              strokeOpacity="0.4"
              strokeWidth="1.1"
            />
            <text
              x={cx}
              y={above ? 103 : 263}
              fill={label}
              fontSize="8"
              textAnchor="middle"
              {...mono}
            >
              {stage.out}
            </text>
          </g>
        );
      })}

      <text x="286" y="330" fill={label} fontSize="9.5" textAnchor="middle" letterSpacing="0.8" {...mono}>
        EVERY STAGE HANDS OVER SOMETHING
      </text>
      <text x="286" y="350" fill={label} fontSize="9.5" textAnchor="middle" letterSpacing="0.8" {...mono}>
        SO YOU ALWAYS KNOW WHERE THE PROJECT STANDS
      </text>
    </svg>
  );
}

export const ARGUMENT_ILLUSTRATIONS = {
  SingleVendor,
  ProcessTimeline,
} as const;
