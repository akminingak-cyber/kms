/**
 * One technical illustration per service.
 *
 * Each draws the actual engineering the page is describing — a coverage plan, a
 * segmented network, a rack layout — rather than decorating around it. A
 * prospective client scanning the CCTV page learns more from a coverage diagram
 * with overlap and blind spots marked than from a stock photo of a camera.
 *
 * All of them are built from the palette tokens, so the same drawing reads
 * correctly on a dark section and on a light one. They are rendered inside
 * <Figure>, which owns the frame, the aspect ratio and the accessible label —
 * hence every element here being aria-hidden.
 */

const line = 'rgb(var(--c-line) / 0.14)';
const lineStrong = 'rgb(var(--c-line) / 0.26)';
const accent = 'rgb(var(--c-accent-500))';
const accentSoft = 'rgb(var(--c-accent-500) / 0.5)';
const primary = 'rgb(var(--c-primary-400))';
const surface = 'rgb(var(--c-surface-2) / 0.75)';
const surfaceDeep = 'rgb(var(--c-surface-3) / 0.5)';
const label = 'rgb(var(--c-text-tertiary))';
const heading = 'rgb(var(--c-text-primary))';

const svg = {
  className: 'h-full w-full',
  preserveAspectRatio: 'xMidYMid meet',
  'aria-hidden': true as const,
  focusable: 'false' as const,
};

const mono = { fontFamily: 'var(--font-mono)' } as const;

/* ========================================================================== */
/* IT infrastructure — a server room in plan, with the hot/cold aisle that      */
/* decides whether the kit survives a Tbilisi summer.                          */
/* ========================================================================== */

function ServerRoom() {
  return (
    <svg viewBox="0 0 560 400" {...svg}>
      <rect x="40" y="40" width="480" height="320" rx="6" fill={surfaceDeep} stroke={lineStrong} strokeWidth="1.6" />

      {/* Cold aisle, drawn as the containment it is. */}
      <rect x="150" y="120" width="260" height="70" fill="rgb(var(--c-primary-500) / 0.12)" stroke={primary} strokeWidth="1" strokeDasharray="5 4" />
      <text x="280" y="162" fill={primary} fontSize="11" textAnchor="middle" {...mono}>COLD AISLE</text>

      {/* Two facing rack rows. */}
      {[96, 190].map((y, row) => (
        <g key={y}>
          {Array.from({ length: 6 }, (_, i) => (
            <g key={i}>
              <rect x={152 + i * 43} y={y} width="38" height="24" rx="2" fill={surface} stroke={lineStrong} strokeWidth="1.2" />
              {Array.from({ length: 3 }, (_, u) => (
                <rect key={u} x={156 + i * 43} y={y + 4 + u * 6} width="30" height="4" rx="1" fill={row === 0 ? accentSoft : line} />
              ))}
            </g>
          ))}
        </g>
      ))}
      <text x="280" y="90" fill={label} fontSize="10" textAnchor="middle" {...mono}>RACK ROW A</text>
      <text x="280" y="232" fill={label} fontSize="10" textAnchor="middle" {...mono}>RACK ROW B</text>

      {/* Overhead cable tray. */}
      <g stroke={accent} strokeOpacity="0.45" strokeWidth="1.4" fill="none">
        <path d="M150 68 H410" strokeDasharray="6 5" />
        {[172, 236, 300, 364].map((x) => (
          <path key={x} d={`M${x} 68 V96`} strokeDasharray="3 4" />
        ))}
      </g>
      <text x="120" y="72" fill={label} fontSize="9" textAnchor="end" {...mono}>TRAY</text>

      {/* Power and cooling down the sides. */}
      <rect x="60" y="120" width="66" height="88" rx="4" fill={surface} stroke={primary} strokeWidth="1.5" />
      <text x="93" y="152" fill={heading} fontSize="12" textAnchor="middle" {...mono}>UPS</text>
      <path d="M87 162 l-8 14 h7 l-3 12 12 -16 h-8 z" fill={accent} />

      <rect x="434" y="120" width="66" height="88" rx="4" fill={surface} stroke={lineStrong} strokeWidth="1.4" />
      <text x="467" y="150" fill={label} fontSize="10" textAnchor="middle" {...mono}>CRAC</text>
      <g stroke={accent} strokeOpacity="0.6" strokeWidth="1.4" fill="none">
        {[0, 1, 2].map((i) => (
          <path key={i} d={`M448 ${168 + i * 14} q 9 -7 18 0 q 9 7 18 0`} />
        ))}
      </g>

      {/* Airflow, the point of the whole layout. */}
      <g stroke={primary} strokeOpacity="0.55" strokeWidth="1.2" fill="none" markerEnd="">
        <path d="M420 155 H414" />
        <path d="M146 155 H140" />
      </g>

      {/* Dimension line. */}
      <g stroke={line} strokeWidth="1">
        <path d="M40 380 H520" />
        <path d="M40 374 V386" />
        <path d="M520 374 V386" />
      </g>
      <text x="280" y="396" fill={label} fontSize="10" textAnchor="middle" {...mono}>PLAN — 1:50</text>
    </svg>
  );
}

/* ========================================================================== */
/* Networking — segmentation, which is the whole argument for doing it         */
/* properly: guest traffic never meets the cameras.                            */
/* ========================================================================== */

function NetworkSegments() {
  const vlans = [
    { name: 'VLAN 10 · STAFF', y: 132, colour: 'rgb(var(--c-primary-400))' },
    { name: 'VLAN 20 · GUEST', y: 190, colour: 'rgb(var(--c-accent-500))' },
    { name: 'VLAN 30 · CCTV', y: 248, colour: 'rgb(var(--c-success))' },
    { name: 'VLAN 40 · BMS', y: 306, colour: 'rgb(var(--c-warning))' },
  ];

  return (
    <svg viewBox="0 0 560 400" {...svg}>
      {/* Edge. */}
      <rect x="34" y="42" width="120" height="46" rx="5" fill={surface} stroke={lineStrong} strokeWidth="1.4" />
      <text x="94" y="70" fill={label} fontSize="11" textAnchor="middle" {...mono}>INTERNET</text>

      {/* Firewall. */}
      <rect x="196" y="36" width="120" height="58" rx="5" fill="rgb(var(--c-primary-500) / 0.18)" stroke={primary} strokeWidth="1.7" />
      <text x="256" y="62" fill={heading} fontSize="12" textAnchor="middle" {...mono}>FIREWALL</text>
      <text x="256" y="80" fill={label} fontSize="9" textAnchor="middle" {...mono}>POLICY · NAT</text>
      <path d="M154 65 H196" stroke={line} strokeWidth="1.4" />

      {/* Core switch. */}
      <rect x="196" y="118" width="120" height="52" rx="5" fill={surface} stroke={accent} strokeWidth="1.6" />
      <text x="256" y="140" fill={heading} fontSize="12" textAnchor="middle" {...mono}>CORE SW</text>
      <g fill={accentSoft}>
        {Array.from({ length: 10 }, (_, i) => (
          <rect key={i} x={206 + i * 11} y="150" width="7" height="8" rx="1" />
        ))}
      </g>
      <path d="M256 94 V118" stroke={line} strokeWidth="1.4" />

      {/* Each VLAN as its own lane — the visual point is that they do not cross. */}
      {vlans.map((vlan) => (
        <g key={vlan.name}>
          <path d={`M316 ${vlan.y + 16} H366`} stroke={vlan.colour} strokeOpacity="0.6" strokeWidth="1.6" strokeDasharray="5 4" />
          <rect x="366" y={vlan.y} width="164" height="34" rx="4" fill={surface} stroke={vlan.colour} strokeOpacity="0.55" strokeWidth="1.3" />
          <circle cx="382" cy={vlan.y + 17} r="4" fill={vlan.colour} />
          <text x="394" y={vlan.y + 21} fill={label} fontSize="10" {...mono}>{vlan.name}</text>
        </g>
      ))}
      <path d="M316 144 V322 M316 144 H330" stroke={line} strokeWidth="1.2" fill="none" />
      {vlans.map((vlan) => (
        <path key={`t-${vlan.y}`} d={`M316 ${vlan.y + 16} H316`} stroke={line} strokeWidth="1.2" />
      ))}

      {/* Access layer + wireless coverage. */}
      <rect x="34" y="132" width="120" height="46" rx="5" fill={surface} stroke={lineStrong} strokeWidth="1.3" />
      <text x="94" y="160" fill={label} fontSize="10" textAnchor="middle" {...mono}>ACCESS SW</text>
      <path d="M196 144 H154" stroke={line} strokeWidth="1.4" />

      {[{ x: 70, y: 250 }, { x: 118, y: 300 }].map((ap, i) => (
        <g key={i}>
          {[16, 26, 36].map((r, ring) => (
            <circle key={r} cx={ap.x} cy={ap.y} r={r} fill="none" stroke={accent} strokeOpacity={0.3 - ring * 0.08} strokeWidth="1.2" />
          ))}
          <circle cx={ap.x} cy={ap.y} r="6" fill={accent} className="animate-pulse-node" style={{ animationDelay: `${i * 500}ms` }} />
        </g>
      ))}
      <text x="94" y="205" fill={label} fontSize="9" textAnchor="middle" {...mono}>Wi-Fi COVERAGE</text>
      <path d="M94 178 V214" stroke={line} strokeWidth="1.2" strokeDasharray="3 4" />
    </svg>
  );
}

/* ========================================================================== */
/* CCTV — a coverage plan with overlap and a marked blind spot, because that   */
/* is the conversation worth having before anything is mounted.                */
/* ========================================================================== */

function CameraCoverage() {
  const cameras = [
    { x: 96, y: 96, rotate: 35 },
    { x: 404, y: 96, rotate: 145 },
    { x: 96, y: 274, rotate: -35 },
    { x: 404, y: 274, rotate: -145 },
  ];

  return (
    <svg viewBox="0 0 560 400" {...svg}>
      <defs>
        <radialGradient id="cone" cx="0%" cy="50%" r="100%">
          <stop offset="0%" stopColor="rgb(var(--c-accent-500))" stopOpacity="0.3" />
          <stop offset="100%" stopColor="rgb(var(--c-accent-500))" stopOpacity="0.02" />
        </radialGradient>
      </defs>

      {/* Floor plate with an internal partition. */}
      <rect x="66" y="66" width="368" height="238" rx="4" fill={surfaceDeep} stroke={lineStrong} strokeWidth="1.8" />
      <path d="M250 66 V170 M250 210 V304" stroke={lineStrong} strokeWidth="1.4" />
      <path d="M66 210 H160" stroke={lineStrong} strokeWidth="1.4" />

      {/* Fields of view. */}
      {cameras.map((cam) => (
        <g key={`${cam.x}-${cam.y}`} transform={`rotate(${cam.rotate} ${cam.x} ${cam.y})`}>
          <path d={`M${cam.x} ${cam.y} L${cam.x + 178} ${cam.y - 66} A190 190 0 0 1 ${cam.x + 178} ${cam.y + 66} Z`} fill="url(#cone)" />
          <path
            d={`M${cam.x} ${cam.y} L${cam.x + 178} ${cam.y - 66} A190 190 0 0 1 ${cam.x + 178} ${cam.y + 66} Z`}
            fill="none"
            stroke={accent}
            strokeOpacity="0.35"
            strokeWidth="1"
            strokeDasharray="4 5"
          />
        </g>
      ))}

      {/* Cameras. */}
      {cameras.map((cam, i) => (
        <g key={`c${i}`}>
          <circle cx={cam.x} cy={cam.y} r="9" fill="rgb(var(--c-surface-1))" stroke={accent} strokeWidth="1.8" />
          <circle cx={cam.x} cy={cam.y} r="3.4" fill={accent} />
          <text x={cam.x} y={cam.y - 16} fill={label} fontSize="9" textAnchor="middle" {...mono}>{`CAM-0${i + 1}`}</text>
        </g>
      ))}

      {/* The uncovered corner, named rather than hidden. */}
      <rect x="176" y="182" width="58" height="46" rx="3" fill="rgb(var(--c-warning) / 0.12)" stroke="rgb(var(--c-warning))" strokeOpacity="0.6" strokeWidth="1.2" strokeDasharray="4 4" />
      <text x="205" y="209" fill="rgb(var(--c-warning))" fontSize="9" textAnchor="middle" {...mono}>BLIND</text>

      {/* Recorder and retention — the part clients forget to budget. */}
      <rect x="66" y="330" width="180" height="46" rx="5" fill={surface} stroke={primary} strokeWidth="1.5" />
      <text x="86" y="352" fill={heading} fontSize="12" {...mono}>NVR</text>
      <text x="86" y="367" fill={label} fontSize="9" {...mono}>RAID · 30-DAY RETENTION</text>
      <g stroke={accent} strokeOpacity="0.5" strokeWidth="1.2" strokeDasharray="4 5" fill="none">
        <path d="M96 310 V330" />
        <path d="M404 290 V320 H250 V330" />
      </g>

      <rect x="290" y="330" width="144" height="46" rx="5" fill={surface} stroke={lineStrong} strokeWidth="1.3" />
      <text x="310" y="352" fill={label} fontSize="10" {...mono}>SECURE VPN</text>
      <text x="310" y="367" fill={label} fontSize="9" {...mono}>NO PORT FORWARD</text>
    </svg>
  );
}

/* ========================================================================== */
/* Access control — zones by permission level, and the audit trail that makes  */
/* an incident reconstructable.                                                */
/* ========================================================================== */

function AccessZones() {
  const zones = [
    { x: 60, y: 70, w: 150, h: 110, name: 'PUBLIC', level: 0 },
    { x: 218, y: 70, w: 150, h: 110, name: 'STAFF', level: 1 },
    { x: 376, y: 70, w: 124, h: 110, name: 'SERVER', level: 2 },
    { x: 60, y: 188, w: 150, h: 96, name: 'RECEPTION', level: 0 },
    { x: 218, y: 188, w: 282, h: 96, name: 'RESTRICTED', level: 2 },
  ];
  const tone = ['rgb(var(--c-line) / 0.18)', 'rgb(var(--c-primary-400))', 'rgb(var(--c-accent-500))'];

  return (
    <svg viewBox="0 0 560 400" {...svg}>
      {zones.map((zone) => (
        <g key={zone.name}>
          <rect
            x={zone.x}
            y={zone.y}
            width={zone.w}
            height={zone.h}
            rx="4"
            fill={zone.level === 2 ? 'rgb(var(--c-accent-500) / 0.07)' : surface}
            stroke={tone[zone.level]}
            strokeOpacity={zone.level === 0 ? 1 : 0.55}
            strokeWidth="1.4"
          />
          <text x={zone.x + 12} y={zone.y + 22} fill={label} fontSize="10" letterSpacing="1" {...mono}>{zone.name}</text>
          {zone.level > 0 && (
            <g transform={`translate(${zone.x + zone.w - 30} ${zone.y + 12})`}>
              {Array.from({ length: zone.level }, (_, i) => (
                <rect key={i} x={i * 7} y="0" width="4" height="12" rx="1" fill={tone[zone.level]} />
              ))}
            </g>
          )}
        </g>
      ))}

      {/* Controlled doors sit on the boundaries they protect. */}
      {[
        { x: 218, y: 125 },
        { x: 376, y: 125 },
        { x: 218, y: 236 },
      ].map((door, i) => (
        <g key={i}>
          <rect x={door.x - 4} y={door.y - 15} width="8" height="30" rx="2" fill="rgb(var(--c-surface-1))" stroke={accent} strokeWidth="1.6" />
          <circle cx={door.x + 16} cy={door.y} r="6.5" fill="rgb(var(--c-primary-500) / 0.2)" stroke={primary} strokeWidth="1.4" />
          <path d={`M${door.x + 13} ${door.y} h6 M${door.x + 16} ${door.y - 3} v6`} stroke={primary} strokeWidth="1.2" />
        </g>
      ))}

      {/* Controller and the event log it writes. */}
      <rect x="60" y="304" width="170" height="66" rx="5" fill={surface} stroke={primary} strokeWidth="1.6" />
      <text x="78" y="328" fill={heading} fontSize="12" {...mono}>CONTROLLER</text>
      <text x="78" y="345" fill={label} fontSize="9" {...mono}>OSDP · ENCRYPTED</text>
      <text x="78" y="360" fill={label} fontSize="9" {...mono}>FAIL-SAFE ON ALARM</text>

      <rect x="248" y="304" width="252" height="66" rx="5" fill={surfaceDeep} stroke={lineStrong} strokeWidth="1.3" />
      <text x="264" y="322" fill={label} fontSize="9" letterSpacing="1" {...mono}>EVENT LOG</text>
      {[
        '09:14  A-102  GRANTED',
        '09:16  A-114  DENIED',
        '09:21  A-102  GRANTED',
      ].map((row, i) => (
        <g key={row}>
          <circle cx="270" cy={336 + i * 13} r="2.6" fill={i === 1 ? 'rgb(var(--c-warning))' : accent} />
          <text x="280" y={340 + i * 13} fill={label} fontSize="9" {...mono}>{row}</text>
        </g>
      ))}
    </svg>
  );
}

/* ========================================================================== */
/* Smart building — a section through the building, because the argument is    */
/* that one logic runs the whole stack rather than four disconnected ones.     */
/* ========================================================================== */

function BuildingSystems() {
  const floors = [
    { y: 78, name: 'L3 · OFFICE' },
    { y: 146, name: 'L2 · OFFICE' },
    { y: 214, name: 'L1 · RETAIL' },
    { y: 282, name: 'B1 · PLANT' },
  ];

  return (
    <svg viewBox="0 0 560 400" {...svg}>
      <rect x="70" y="60" width="330" height="290" rx="5" fill={surfaceDeep} stroke={lineStrong} strokeWidth="1.8" />

      {floors.map((floor, i) => (
        <g key={floor.name}>
          <path d={`M70 ${floor.y + 58} H400`} stroke={lineStrong} strokeWidth="1.3" />
          {/* Sits right of the riser at x=96 — at x=84 the riser nodes landed
              inside the text. */}
          <text x="112" y={floor.y + 20} fill={label} fontSize="9" letterSpacing="1" {...mono}>{floor.name}</text>

          {/* Lighting */}
          {i < 3 &&
            [0, 1, 2, 3].map((n) => (
              <g key={n}>
                <rect x={196 + n * 52} y={floor.y + 12} width="22" height="5" rx="2.5" fill="rgb(var(--c-warning) / 0.75)" />
                <path d={`M${207 + n * 52} ${floor.y + 19} v6`} stroke="rgb(var(--c-warning))" strokeOpacity="0.3" strokeWidth="1" strokeDasharray="2 3" />
              </g>
            ))}

          {/* HVAC duct */}
          <rect x="112" y={floor.y + 34} width="268" height="12" rx="3" fill="rgb(var(--c-primary-500) / 0.16)" stroke={primary} strokeOpacity="0.5" strokeWidth="1" />
          {[0, 1, 2].map((n) => (
            <path key={n} d={`M${180 + n * 80} ${floor.y + 46} v7`} stroke={primary} strokeOpacity="0.5" strokeWidth="1.2" />
          ))}

          {/* Sensor */}
          <circle cx="376" cy={floor.y + 18} r="4.5" fill={accent} className="animate-pulse-node" style={{ animationDelay: `${i * 420}ms` }} />
        </g>
      ))}

      {/* Riser tying every floor to the management layer. */}
      <path d="M96 78 V340" stroke={accent} strokeOpacity="0.5" strokeWidth="1.6" strokeDasharray="6 5" />
      {floors.map((floor) => (
        <circle key={floor.y} cx="96" cy={floor.y + 18} r="3.4" fill={accent} />
      ))}

      {/* BMS. */}
      <rect x="424" y="150" width="106" height="110" rx="6" fill="rgb(var(--c-primary-500) / 0.18)" stroke={primary} strokeWidth="1.7" />
      <text x="477" y="176" fill={heading} fontSize="12" textAnchor="middle" {...mono}>BMS</text>
      {['HVAC', 'LIGHT', 'ENERGY', 'ALARM'].map((row, i) => (
        <g key={row}>
          <circle cx="444" cy={196 + i * 17} r="3" fill={i === 3 ? 'rgb(var(--c-warning))' : accent} />
          <text x="454" y={200 + i * 17} fill={label} fontSize="9" {...mono}>{row}</text>
        </g>
      ))}
      <path d="M400 205 H424" stroke={accent} strokeOpacity="0.5" strokeWidth="1.4" strokeDasharray="4 4" />

      <text x="235" y="372" fill={label} fontSize="10" textAnchor="middle" {...mono}>SECTION — ONE CONTROL LAYER</text>
    </svg>
  );
}

/* ========================================================================== */
/* Audio-visual — the one-button meeting room, drawn as a signal path.         */
/* ========================================================================== */

function MeetingRoom() {
  return (
    <svg viewBox="0 0 560 400" {...svg}>
      <rect x="60" y="56" width="440" height="240" rx="6" fill={surfaceDeep} stroke={lineStrong} strokeWidth="1.7" />

      {/* Display wall. */}
      <rect x="180" y="66" width="200" height="16" rx="3" fill="rgb(var(--c-surface-1))" stroke={accent} strokeWidth="1.6" />
      <text x="280" y="98" fill={label} fontSize="9" textAnchor="middle" {...mono}>DISPLAY · 4K</text>

      {/* Camera. */}
      <circle cx="280" cy="60" r="5" fill={accent} />

      {/* Table and seats. */}
      <rect x="196" y="150" width="168" height="76" rx="34" fill={surface} stroke={lineStrong} strokeWidth="1.4" />
      {Array.from({ length: 8 }, (_, i) => {
        const angle = (i / 8) * Math.PI * 2;
        return (
          <circle
            key={i}
            cx={280 + Math.cos(angle) * 112}
            cy={188 + Math.sin(angle) * 62}
            r="11"
            fill="none"
            stroke={line}
            strokeWidth="1.3"
          />
        );
      })}

      {/* Ceiling mic array, with its pickup pattern. */}
      <rect x="252" y="176" width="56" height="24" rx="4" fill="rgb(var(--c-primary-500) / 0.2)" stroke={primary} strokeWidth="1.4" />
      <text x="280" y="192" fill={heading} fontSize="9" textAnchor="middle" {...mono}>MIC</text>
      {[46, 66, 86].map((r, i) => (
        <ellipse key={r} cx="280" cy="188" rx={r + 20} ry={r} fill="none" stroke={accent} strokeOpacity={0.22 - i * 0.05} strokeWidth="1.1" strokeDasharray="4 6" />
      ))}

      {/* Ceiling speakers. */}
      {[{ x: 128, y: 120 }, { x: 432, y: 120 }, { x: 128, y: 256 }, { x: 432, y: 256 }].map((sp, i) => (
        <g key={i}>
          <circle cx={sp.x} cy={sp.y} r="12" fill="none" stroke={lineStrong} strokeWidth="1.3" />
          <circle cx={sp.x} cy={sp.y} r="4.5" fill={accentSoft} />
        </g>
      ))}

      {/* Signal path down to the rack. */}
      <g stroke={accent} strokeOpacity="0.45" strokeWidth="1.4" strokeDasharray="5 5" fill="none">
        <path d="M280 200 V236 H92 V330" />
        <path d="M280 82 V96 H92" />
      </g>

      <rect x="60" y="330" width="150" height="48" rx="5" fill={surface} stroke={primary} strokeWidth="1.5" />
      <text x="76" y="352" fill={heading} fontSize="11" {...mono}>AV RACK</text>
      <text x="76" y="368" fill={label} fontSize="9" {...mono}>MATRIX · DSP</text>

      {/* The whole point: one button. */}
      <rect x="240" y="330" width="180" height="48" rx="5" fill="rgb(var(--c-accent-500) / 0.12)" stroke={accent} strokeWidth="1.5" />
      <circle cx="266" cy="354" r="10" fill={accent} />
      <path d="M262 354 l3.5 3.5 l6 -7" stroke="rgb(var(--c-surface-base))" strokeWidth="2" fill="none" strokeLinecap="round" strokeLinejoin="round" />
      <text x="286" y="351" fill={heading} fontSize="11" {...mono}>ONE-TOUCH JOIN</text>
      <text x="286" y="365" fill={label} fontSize="9" {...mono}>NO SETUP TIME</text>
    </svg>
  );
}

/* ========================================================================== */
/* Managed IT — what the client is actually buying: someone watching, and a    */
/* response clock that is written down.                                        */
/* ========================================================================== */

function MonitoringDashboard() {
  const uptime = [1, 1, 1, 1, 0.6, 1, 1, 1, 1, 1, 1, 0.85, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1];

  return (
    <svg viewBox="0 0 560 400" {...svg}>
      <rect x="44" y="44" width="472" height="312" rx="8" fill={surfaceDeep} stroke={lineStrong} strokeWidth="1.6" />
      <path d="M44 82 H516" stroke={lineStrong} strokeWidth="1.2" />
      <g fill={line}>
        <circle cx="66" cy="63" r="4" />
        <circle cx="82" cy="63" r="4" />
        <circle cx="98" cy="63" r="4" />
      </g>
      <text x="124" y="67" fill={label} fontSize="10" letterSpacing="1" {...mono}>MONITORING</text>

      {/* Uptime, with the two dips left visible. */}
      <text x="68" y="112" fill={label} fontSize="9" letterSpacing="1" {...mono}>UPTIME — 24H</text>
      {uptime.map((v, i) => (
        <rect
          key={i}
          x={68 + i * 17.6}
          y={124 + (1 - v) * 46}
          width="11"
          height={46 * v}
          rx="2"
          fill={v === 1 ? 'rgb(var(--c-success) / 0.85)' : 'rgb(var(--c-warning))'}
        />
      ))}
      <path d="M68 170 H488" stroke={line} strokeWidth="1" />

      {/* Alert feed. */}
      <text x="68" y="204" fill={label} fontSize="9" letterSpacing="1" {...mono}>ALERTS</text>
      {[
        { t: 'SW-02 port flap', s: 'warn' },
        { t: 'Backup completed', s: 'ok' },
        { t: 'UPS self-test passed', s: 'ok' },
      ].map((row, i) => (
        <g key={row.t}>
          <rect x="68" y={214 + i * 30} width="252" height="24" rx="4" fill={surface} stroke={line} strokeWidth="1" />
          <circle cx="84" cy={226 + i * 30} r="4" fill={row.s === 'warn' ? 'rgb(var(--c-warning))' : 'rgb(var(--c-success))'} />
          <text x="96" y={230 + i * 30} fill={label} fontSize="9" {...mono}>{row.t}</text>
        </g>
      ))}

      {/* Response clock. */}
      <rect x="340" y="204" width="148" height="100" rx="6" fill="rgb(var(--c-primary-500) / 0.14)" stroke={primary} strokeWidth="1.5" />
      <text x="414" y="226" fill={label} fontSize="9" textAnchor="middle" letterSpacing="1" {...mono}>RESPONSE</text>
      <circle cx="414" cy="262" r="26" fill="none" stroke={line} strokeWidth="4" />
      <circle
        cx="414"
        cy="262"
        r="26"
        fill="none"
        stroke={accent}
        strokeWidth="4"
        strokeLinecap="round"
        strokeDasharray="122 163"
        transform="rotate(-90 414 262)"
      />
      <text x="414" y="267" fill={heading} fontSize="15" textAnchor="middle" {...mono}>4h</text>
      <text x="414" y="296" fill={label} fontSize="8" textAnchor="middle" {...mono}>AGREED SLA</text>

      <text x="280" y="378" fill={label} fontSize="10" textAnchor="middle" {...mono}>PROACTIVE · NOT ON-CALL-ONLY</text>
    </svg>
  );
}

export const SERVICE_ILLUSTRATIONS = {
  ServerRoom,
  NetworkSegments,
  CameraCoverage,
  AccessZones,
  BuildingSystems,
  MeetingRoom,
  MonitoringDashboard,
} as const;
