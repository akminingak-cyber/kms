/**
 * One illustration per industry.
 *
 * Each draws the priority that actually distinguishes that building type — a
 * clinic's dual power feeds, a hotel's per-room state, a government facility's
 * concentric zones. Eight generic network diagrams would tell a visitor
 * nothing; eight different silhouettes tell them we have thought about their
 * kind of building specifically.
 *
 * Built from palette tokens so each reads correctly on dark and light sections,
 * and rendered inside <Figure>, which owns the frame and the accessible label.
 */

const line = 'rgb(var(--c-line) / 0.14)';
const lineStrong = 'rgb(var(--c-line) / 0.26)';
const accent = 'rgb(var(--c-accent-500))';
const accentSoft = 'rgb(var(--c-accent-500) / 0.45)';
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
/* Healthcare — the distinguishing requirement is that critical rooms keep     */
/* running when the grid does not, so redundancy is what gets drawn.           */
/* ========================================================================== */

function ClinicFloor() {
  const rooms = [
    { x: 78, y: 92, w: 104, h: 78, name: 'WARD', critical: false },
    { x: 190, y: 92, w: 104, h: 78, name: 'WARD', critical: false },
    { x: 302, y: 92, w: 128, h: 78, name: 'THEATRE', critical: true },
    { x: 78, y: 220, w: 96, h: 74, name: 'LAB', critical: true },
    { x: 182, y: 220, w: 112, h: 74, name: 'PHARMACY', critical: true },
    { x: 302, y: 220, w: 128, h: 74, name: 'RECEPTION', critical: false },
  ];

  return (
    <svg viewBox="0 0 560 400" {...svg}>
      <rect x="60" y="70" width="386" height="248" rx="5" fill={surfaceDeep} stroke={lineStrong} strokeWidth="1.7" />
      {/* Corridor */}
      <rect x="60" y="178" width="386" height="34" fill="rgb(var(--c-surface-4) / 0.2)" />

      {rooms.map((room) => (
        <g key={`${room.name}-${room.x}`}>
          <rect
            x={room.x}
            y={room.y}
            width={room.w}
            height={room.h}
            rx="3"
            fill={room.critical ? 'rgb(var(--c-primary-500) / 0.13)' : surface}
            stroke={room.critical ? primary : line}
            strokeOpacity={room.critical ? 0.65 : 1}
            strokeWidth="1.3"
          />
          <text x={room.x + 10} y={room.y + 19} fill={label} fontSize="9" letterSpacing="0.8" {...mono}>
            {room.name}
          </text>
          {room.critical && (
            <g>
              {/* Dual feed — the whole argument for the extra cost. */}
              <path d={`M${room.x + room.w - 26} ${room.y + 34} v${room.h - 48}`} stroke={ok} strokeOpacity="0.75" strokeWidth="2" />
              <path d={`M${room.x + room.w - 18} ${room.y + 34} v${room.h - 48}`} stroke={accent} strokeOpacity="0.6" strokeWidth="2" strokeDasharray="4 3" />
              <circle cx={room.x + room.w - 22} cy={room.y + 28} r="3.4" fill={ok} />
            </g>
          )}
        </g>
      ))}

      {/* Two independent supplies. */}
      <rect x="464" y="92" width="76" height="46" rx="4" fill={surface} stroke={ok} strokeWidth="1.5" />
      <text x="502" y="112" fill={heading} fontSize="10" textAnchor="middle" {...mono}>GRID</text>
      <text x="502" y="126" fill={label} fontSize="8" textAnchor="middle" {...mono}>FEED A</text>

      <rect x="464" y="152" width="76" height="46" rx="4" fill={surface} stroke={accent} strokeWidth="1.5" />
      <text x="502" y="172" fill={heading} fontSize="10" textAnchor="middle" {...mono}>UPS+GEN</text>
      <text x="502" y="186" fill={label} fontSize="8" textAnchor="middle" {...mono}>FEED B</text>

      <path d="M464 115 H446" stroke={ok} strokeOpacity="0.7" strokeWidth="1.6" />
      <path d="M464 175 H446" stroke={accent} strokeOpacity="0.6" strokeWidth="1.6" strokeDasharray="4 3" />

      {/* Patient-privacy boundary: cameras stop at the corridor. */}
      <path d="M60 178 H446" stroke={warn} strokeOpacity="0.45" strokeWidth="1.2" strokeDasharray="5 4" />
      <text x="253" y="200" fill={warn} fontSize="8.5" textAnchor="middle" letterSpacing="0.8" {...mono}>
        CAMERAS: CORRIDOR ONLY
      </text>

      <text x="253" y="344" fill={label} fontSize="10" textAnchor="middle" {...mono}>
        CRITICAL ROOMS ON TWO FEEDS
      </text>
    </svg>
  );
}

/* ========================================================================== */
/* Corporate — density. An office fails on Wi-Fi long before it fails on       */
/* anything else, so the drawing is a coverage plan over a desk grid.          */
/* ========================================================================== */

function OfficeFloor() {
  const aps = [
    { x: 150, y: 140 },
    { x: 330, y: 140 },
    { x: 150, y: 252 },
    { x: 330, y: 252 },
  ];

  return (
    <svg viewBox="0 0 560 400" {...svg}>
      <rect x="52" y="66" width="420" height="266" rx="5" fill={surfaceDeep} stroke={lineStrong} strokeWidth="1.7" />

      {/* Wi-Fi cells, overlapping as they must at desk density. */}
      {aps.map((ap, i) => (
        <g key={i}>
          <circle cx={ap.x} cy={ap.y} r="76" fill="rgb(var(--c-accent-500) / 0.07)" stroke={accentSoft} strokeWidth="1" strokeDasharray="4 5" />
        </g>
      ))}

      {/* Desk grid. */}
      <g>
        {Array.from({ length: 5 }, (_, row) =>
          Array.from({ length: 8 }, (_, col) => (
            <rect
              key={`${row}-${col}`}
              x={72 + col * 42}
              y={92 + row * 38}
              width="30"
              height="20"
              rx="2"
              fill={surface}
              stroke={line}
              strokeWidth="1"
            />
          )),
        )}
      </g>

      {/* Meeting rooms along the right. */}
      {[{ y: 86 }, { y: 174 }, { y: 262 }].map((room, i) => (
        <g key={room.y}>
          <rect x="418" y={room.y} width="86" height="66" rx="3" fill={surface} stroke={primary} strokeOpacity="0.5" strokeWidth="1.3" />
          <rect x="432" y={room.y + 12} width="58" height="6" rx="3" fill={accentSoft} />
          <ellipse cx="461" cy={room.y + 40} rx="24" ry="13" fill="none" stroke={line} strokeWidth="1.1" />
          <text x="461" y={room.y + 61} fill={label} fontSize="8" textAnchor="middle" {...mono}>{`MR-0${i + 1}`}</text>
        </g>
      ))}

      {/* Access points on top of their cells. */}
      {aps.map((ap, i) => (
        <g key={`ap${i}`}>
          <circle cx={ap.x} cy={ap.y} r="8" fill="rgb(var(--c-surface-1))" stroke={accent} strokeWidth="1.7" />
          <circle cx={ap.x} cy={ap.y} r="3" fill={accent} className="animate-pulse-node" style={{ animationDelay: `${i * 400}ms` }} />
        </g>
      ))}

      {/* Controlled entry. */}
      <rect x="46" y="180" width="12" height="40" rx="2" fill="rgb(var(--c-surface-1))" stroke={primary} strokeWidth="1.6" />
      <circle cx="36" cy="200" r="7" fill="rgb(var(--c-primary-500) / 0.2)" stroke={primary} strokeWidth="1.4" />
      <text x="36" y="228" fill={label} fontSize="8" textAnchor="middle" {...mono}>BADGE</text>

      <text x="262" y="360" fill={label} fontSize="10" textAnchor="middle" {...mono}>
        COVERAGE PLANNED FOR DENSITY, NOT FLOOR AREA
      </text>
    </svg>
  );
}

/* ========================================================================== */
/* Hospitality — per-room state. The saving that pays for the system is the    */
/* empty room dropping to eco on its own.                                      */
/* ========================================================================== */

function HotelSection() {
  const floors = [70, 132, 194];
  const occupancy = [
    [1, 0, 1, 1, 0, 1, 1, 0],
    [1, 1, 0, 1, 1, 0, 1, 1],
    [0, 1, 1, 0, 1, 1, 0, 1],
  ];

  return (
    <svg viewBox="0 0 560 400" {...svg}>
      <rect x="56" y="58" width="380" height="252" rx="5" fill={surfaceDeep} stroke={lineStrong} strokeWidth="1.7" />

      {floors.map((y, f) => (
        <g key={y}>
          <path d={`M56 ${y + 52} H436`} stroke={lineStrong} strokeWidth="1.2" />
          {occupancy[f].map((occupied, i) => (
            <g key={i}>
              <rect
                x={88 + i * 43}
                y={y + 8}
                width="36"
                height="36"
                rx="3"
                fill={occupied ? 'rgb(var(--c-accent-500) / 0.16)' : surface}
                stroke={occupied ? accent : line}
                strokeOpacity={occupied ? 0.6 : 1}
                strokeWidth="1.2"
              />
              {/* Card switch — present means occupied. */}
              <rect
                x={94 + i * 43}
                y={y + 14}
                width="9"
                height="13"
                rx="1.5"
                fill={occupied ? accent : 'transparent'}
                stroke={occupied ? 'none' : line}
                strokeWidth="1"
              />
              <text x={106 + i * 43} y={y + 39} fill={label} fontSize="7.5" textAnchor="middle" {...mono}>
                {occupied ? 'ON' : 'ECO'}
              </text>
            </g>
          ))}
          <text x="62" y={y + 20} fill={label} fontSize="8" {...mono}>{`L${3 - f}`}</text>
        </g>
      ))}

      {/* Lobby. */}
      <rect x="56" y="256" width="380" height="54" fill="rgb(var(--c-surface-4) / 0.18)" />
      <text x="72" y="276" fill={label} fontSize="9" letterSpacing="0.8" {...mono}>LOBBY · RECEPTION</text>
      {[140, 210, 280, 350].map((x) => (
        <g key={x}>
          <circle cx={x} cy="288" r="6" fill="rgb(var(--c-surface-1))" stroke={accent} strokeWidth="1.4" />
          <circle cx={x} cy="288" r="2.2" fill={accent} />
        </g>
      ))}

      {/* Legend + saving. */}
      <rect x="456" y="120" width="86" height="128" rx="5" fill={surface} stroke={primary} strokeWidth="1.4" />
      <text x="499" y="142" fill={label} fontSize="8" textAnchor="middle" letterSpacing="1" {...mono}>ROOM STATE</text>
      <g>
        <rect x="470" y="152" width="12" height="12" rx="2" fill="rgb(var(--c-accent-500) / 0.3)" stroke={accent} strokeWidth="1" />
        <text x="490" y="162" fill={label} fontSize="8" {...mono}>OCCUPIED</text>
        <rect x="470" y="172" width="12" height="12" rx="2" fill={surface} stroke={line} strokeWidth="1" />
        <text x="490" y="182" fill={label} fontSize="8" {...mono}>ECO</text>
      </g>
      <path d="M470 196 H528" stroke={line} strokeWidth="1" />
      <text x="499" y="216" fill={heading} fontSize="17" textAnchor="middle" {...mono}>−22%</text>
      <text x="499" y="232" fill={label} fontSize="7.5" textAnchor="middle" {...mono}>HVAC LOAD</text>

      <text x="246" y="342" fill={label} fontSize="10" textAnchor="middle" {...mono}>
        EMPTY ROOMS DROP TO ECO WITHOUT STAFF ACTION
      </text>
    </svg>
  );
}

/* ========================================================================== */
/* Education — a classroom that starts on time, and a network where students   */
/* cannot reach the administrative side.                                       */
/* ========================================================================== */

function CampusClassrooms() {
  return (
    <svg viewBox="0 0 560 400" {...svg}>
      {/* Three classrooms. */}
      {[0, 1, 2].map((i) => (
        <g key={i}>
          <rect x={54 + i * 158} y="62" width="140" height="128" rx="4" fill={surface} stroke={lineStrong} strokeWidth="1.4" />
          {/* Board / display */}
          <rect x={70 + i * 158} y="74" width="108" height="12" rx="2" fill={accentSoft} />
          {/* Desks */}
          {Array.from({ length: 3 }, (_, row) =>
            Array.from({ length: 4 }, (_, col) => (
              <rect
                key={`${row}-${col}`}
                x={72 + i * 158 + col * 27}
                y={104 + row * 26}
                width="19"
                height="12"
                rx="1.5"
                fill="rgb(var(--c-surface-3) / 0.7)"
                stroke={line}
                strokeWidth="0.9"
              />
            )),
          )}
          <text x={124 + i * 158} y="182" fill={label} fontSize="8.5" textAnchor="middle" {...mono}>{`ROOM ${101 + i}`}</text>
          <path d={`M124 190 V214`} transform={`translate(${i * 158} 0)`} stroke={accentSoft} strokeWidth="1.3" strokeDasharray="4 4" />
        </g>
      ))}

      {/* Distribution. */}
      <rect x="54" y="214" width="456" height="34" rx="4" fill="rgb(var(--c-primary-500) / 0.14)" stroke={primary} strokeWidth="1.4" />
      <text x="282" y="236" fill={heading} fontSize="10" textAnchor="middle" letterSpacing="1" {...mono}>
        DISTRIBUTION · PoE TO EVERY ROOM
      </text>

      {/* Two separated networks — the point of the page. */}
      <rect x="54" y="278" width="216" height="64" rx="5" fill={surface} stroke={accent} strokeOpacity="0.55" strokeWidth="1.4" />
      <text x="72" y="300" fill={heading} fontSize="10" {...mono}>STUDENT</text>
      <text x="72" y="316" fill={label} fontSize="8" {...mono}>FILTERED · RATE-LIMITED</text>
      <text x="72" y="330" fill={label} fontSize="8" {...mono}>NO ACCESS TO ADMIN</text>

      <rect x="294" y="278" width="216" height="64" rx="5" fill={surface} stroke={primary} strokeOpacity="0.6" strokeWidth="1.4" />
      <text x="312" y="300" fill={heading} fontSize="10" {...mono}>ADMIN · STAFF</text>
      <text x="312" y="316" fill={label} fontSize="8" {...mono}>RECORDS · FINANCE</text>
      <text x="312" y="330" fill={label} fontSize="8" {...mono}>SEPARATE VLAN</text>

      <path d="M162 248 V278 M402 248 V278" stroke={line} strokeWidth="1.2" />
      {/* The barrier, drawn. */}
      <g>
        <path d="M282 288 V332" stroke={warn} strokeOpacity="0.55" strokeWidth="1.4" strokeDasharray="4 4" />
        <circle cx="282" cy="310" r="9" fill="rgb(var(--c-surface-1))" stroke={warn} strokeOpacity="0.7" strokeWidth="1.3" />
        <path d="M278 310 h8 M282 306 v8" stroke={warn} strokeWidth="1.4" transform="rotate(45 282 310)" />
      </g>

      <text x="282" y="370" fill={label} fontSize="10" textAnchor="middle" {...mono}>
        ONE CABLE PLANT, TWO ISOLATED NETWORKS
      </text>
    </svg>
  );
}

/* ========================================================================== */
/* Industrial — the environment is the problem: dust, vibration, distance,     */
/* and a line that must not stop.                                             */
/* ========================================================================== */

function PlantElevation() {
  return (
    <svg viewBox="0 0 560 400" {...svg}>
      {/* Production hall. */}
      <path d="M56 150 L180 92 L304 150 V306 H56 Z" fill={surfaceDeep} stroke={lineStrong} strokeWidth="1.7" />
      <path d="M56 150 H304" stroke={lineStrong} strokeWidth="1.2" />

      {/* Production line. */}
      <rect x="80" y="248" width="200" height="14" rx="3" fill="rgb(var(--c-surface-4) / 0.5)" stroke={line} strokeWidth="1" />
      {[0, 1, 2, 3, 4].map((i) => (
        <rect key={i} x={90 + i * 40} y="234" width="22" height="14" rx="2" fill={surface} stroke={lineStrong} strokeWidth="1" />
      ))}
      <text x="180" y="284" fill={label} fontSize="8.5" textAnchor="middle" {...mono}>PRODUCTION LINE</text>

      {/* Sealed enclosure — the environmental answer. */}
      <rect x="196" y="168" width="88" height="52" rx="4" fill={surface} stroke={primary} strokeWidth="1.6" />
      <text x="240" y="188" fill={heading} fontSize="9" textAnchor="middle" {...mono}>IP66</text>
      <text x="240" y="202" fill={label} fontSize="7.5" textAnchor="middle" {...mono}>SEALED CAB</text>
      <text x="240" y="214" fill={label} fontSize="7.5" textAnchor="middle" {...mono}>−20…+60°C</text>

      {/* Fibre run — the distance answer. */}
      <path d="M284 194 H360" stroke={accent} strokeOpacity="0.6" strokeWidth="2" strokeDasharray="7 4" />
      <text x="322" y="186" fill={accent} fontSize="8" textAnchor="middle" {...mono}>FIBRE</text>
      <text x="322" y="210" fill={label} fontSize="7.5" textAnchor="middle" {...mono}>NO EMI</text>

      {/* Office block. */}
      <rect x="360" y="150" width="144" height="156" rx="4" fill={surface} stroke={lineStrong} strokeWidth="1.5" />
      <text x="432" y="170" fill={label} fontSize="8.5" textAnchor="middle" letterSpacing="0.8" {...mono}>OFFICE / MCC</text>
      {Array.from({ length: 3 }, (_, row) =>
        Array.from({ length: 3 }, (_, col) => (
          <rect key={`${row}-${col}`} x={378 + col * 42} y={184 + row * 38} width="30" height="24" rx="2" fill="rgb(var(--c-surface-3) / 0.6)" stroke={line} strokeWidth="0.9" />
        )),
      )}

      {/* Redundant ring — the line-must-not-stop answer. */}
      <path
        d="M240 220 V300 H432 V306"
        fill="none"
        stroke={ok}
        strokeOpacity="0.6"
        strokeWidth="1.8"
      />
      <text x="336" y="316" fill={ok} fontSize="8" textAnchor="middle" {...mono}>REDUNDANT RING</text>

      {/* Perimeter cameras on masts. */}
      {[{ x: 40, y: 196 }, { x: 522, y: 196 }].map((cam, i) => (
        <g key={i}>
          <path d={`M${cam.x} ${cam.y} V306`} stroke={lineStrong} strokeWidth="1.6" />
          <circle cx={cam.x} cy={cam.y} r="7" fill="rgb(var(--c-surface-1))" stroke={accent} strokeWidth="1.5" />
          <circle cx={cam.x} cy={cam.y} r="2.6" fill={accent} />
        </g>
      ))}
      <path d="M56 306 H504" stroke={lineStrong} strokeWidth="2" />

      <text x="280" y="344" fill={label} fontSize="10" textAnchor="middle" {...mono}>
        BUILT FOR DUST, DISTANCE AND UPTIME
      </text>
    </svg>
  );
}

/* ========================================================================== */
/* Government — concentric zones, and an audit trail that survives scrutiny.  */
/* ========================================================================== */

function SecureZones() {
  const rings = [
    { r: 132, name: 'PERIMETER', colour: line },
    { r: 100, name: 'PUBLIC', colour: 'rgb(var(--c-line) / 0.3)' },
    { r: 68, name: 'STAFF', colour: primary },
    { r: 36, name: 'SECURE', colour: accent },
  ];

  return (
    <svg viewBox="0 0 560 400" {...svg}>
      {rings.map((ring, i) => (
        <g key={ring.name}>
          <circle
            cx="212"
            cy="196"
            r={ring.r}
            fill={i === rings.length - 1 ? 'rgb(var(--c-accent-500) / 0.1)' : 'none'}
            stroke={ring.colour}
            strokeOpacity={i < 2 ? 1 : 0.6}
            strokeWidth="1.5"
            strokeDasharray={i === 0 ? '6 5' : undefined}
          />
          <text
            x="212"
            y={196 - ring.r + 15}
            fill={label}
            fontSize="8"
            textAnchor="middle"
            letterSpacing="1"
            {...mono}
          >
            {ring.name}
          </text>
        </g>
      ))}

      {/* Each boundary is a controlled crossing, not a wall. */}
      {rings.slice(1).map((ring, i) => (
        <g key={`gate-${ring.name}`} transform={`rotate(${-40 + i * 40} 212 196)`}>
          <rect x={208} y={196 - ring.r - 5} width="8" height="10" rx="2" fill="rgb(var(--c-surface-1))" stroke={accent} strokeWidth="1.4" />
        </g>
      ))}

      <circle cx="212" cy="196" r="10" fill={accent} />
      <text x="212" y="200" fill="rgb(var(--c-surface-base))" fontSize="9" textAnchor="middle" fontWeight="700" {...mono}>
        3
      </text>

      {/* Audit trail. */}
      <rect x="374" y="104" width="166" height="184" rx="6" fill={surfaceDeep} stroke={lineStrong} strokeWidth="1.4" />
      <text x="392" y="126" fill={label} fontSize="8.5" letterSpacing="1" {...mono}>AUDIT TRAIL</text>
      <path d="M392 134 H522" stroke={line} strokeWidth="1" />
      {[
        ['08:02', 'Z1→Z2', 'OK'],
        ['08:14', 'Z2→Z3', 'OK'],
        ['09:31', 'Z2→Z3', 'DENY'],
        ['09:33', 'Z2→Z3', 'OK'],
        ['11:08', 'Z3→Z2', 'OK'],
        ['13:47', 'Z1→Z2', 'OK'],
      ].map((row, i) => (
        <g key={i}>
          <circle cx="398" cy={152 + i * 21} r="2.8" fill={row[2] === 'DENY' ? warn : ok} />
          <text x="408" y={156 + i * 21} fill={label} fontSize="8" {...mono}>{row[0]}</text>
          <text x="448" y={156 + i * 21} fill={label} fontSize="8" {...mono}>{row[1]}</text>
          <text x="494" y={156 + i * 21} fill={row[2] === 'DENY' ? warn : label} fontSize="8" {...mono}>{row[2]}</text>
        </g>
      ))}
      <path d="M392 278 H522" stroke={line} strokeWidth="1" />
      <text x="457" y="294" fill={label} fontSize="7.5" textAnchor="middle" {...mono}>IMMUTABLE · TIME-SYNCED</text>

      <text x="212" y="356" fill={label} fontSize="10" textAnchor="middle" {...mono}>
        EVERY CROSSING RECORDED
      </text>
    </svg>
  );
}

/* ========================================================================== */
/* Retail — the till is where loss happens and where the network must not be  */
/* shared with the guest Wi-Fi.                                               */
/* ========================================================================== */

function StoreFloor() {
  return (
    <svg viewBox="0 0 560 400" {...svg}>
      <rect x="54" y="62" width="392" height="256" rx="5" fill={surfaceDeep} stroke={lineStrong} strokeWidth="1.7" />

      {/* Aisles. */}
      {[0, 1, 2, 3].map((i) => (
        <g key={i}>
          <rect x={92 + i * 76} y="112" width="26" height="132" rx="3" fill={surface} stroke={line} strokeWidth="1.1" />
          <rect x={126 + i * 76} y="112" width="26" height="132" rx="3" fill={surface} stroke={line} strokeWidth="1.1" />
        </g>
      ))}

      {/* Entrance with people counting. */}
      <rect x="54" y="160" width="10" height="56" fill="rgb(var(--c-surface-1))" stroke={accent} strokeWidth="1.6" />
      <path d="M40 160 V216" stroke={accent} strokeOpacity="0.5" strokeWidth="1.3" strokeDasharray="4 4" />
      <text x="30" y="152" fill={label} fontSize="8" textAnchor="middle" {...mono}>IN</text>
      <text x="30" y="234" fill={accent} fontSize="8" textAnchor="middle" {...mono}>COUNT</text>

      {/* Tills. */}
      {[0, 1, 2].map((i) => (
        <g key={i}>
          <rect x={352} y={100 + i * 62} width="70" height="40" rx="3" fill="rgb(var(--c-primary-500) / 0.14)" stroke={primary} strokeOpacity="0.65" strokeWidth="1.3" />
          <text x={387} y={124 + i * 62} fill={heading} fontSize="9" textAnchor="middle" {...mono}>{`POS-0${i + 1}`}</text>
          {/* Dedicated camera per till. */}
          <circle cx={387} cy={94 + i * 62} r="5.5" fill="rgb(var(--c-surface-1))" stroke={accent} strokeWidth="1.4" />
          <circle cx={387} cy={94 + i * 62} r="2" fill={accent} />
        </g>
      ))}

      {/* Stockroom. */}
      <rect x="54" y="262" width="130" height="56" rx="3" fill="rgb(var(--c-surface-4) / 0.2)" stroke={line} strokeWidth="1.1" />
      <text x="119" y="294" fill={label} fontSize="8.5" textAnchor="middle" {...mono}>STOCKROOM</text>

      {/* Two networks, drawn apart. */}
      <rect x="462" y="100" width="80" height="76" rx="5" fill={surface} stroke={primary} strokeWidth="1.5" />
      <text x="502" y="122" fill={heading} fontSize="9" textAnchor="middle" {...mono}>POS VLAN</text>
      <text x="502" y="138" fill={label} fontSize="7.5" textAnchor="middle" {...mono}>ISOLATED</text>
      <text x="502" y="152" fill={label} fontSize="7.5" textAnchor="middle" {...mono}>PCI SCOPE</text>
      <circle cx="502" cy="166" r="3" fill={ok} />

      <rect x="462" y="196" width="80" height="70" rx="5" fill={surface} stroke={line} strokeWidth="1.3" />
      <text x="502" y="218" fill={label} fontSize="9" textAnchor="middle" {...mono}>GUEST</text>
      <text x="502" y="234" fill={label} fontSize="7.5" textAnchor="middle" {...mono}>Wi-Fi</text>
      <text x="502" y="248" fill={label} fontSize="7.5" textAnchor="middle" {...mono}>NO ROUTE</text>

      <path d="M422 138 H462" stroke={primary} strokeOpacity="0.6" strokeWidth="1.5" />
      <path d="M446 228 H462" stroke={line} strokeWidth="1.3" strokeDasharray="4 4" />

      <text x="250" y="348" fill={label} fontSize="10" textAnchor="middle" {...mono}>
        TILL NETWORK NEVER SHARES THE GUEST Wi-Fi
      </text>
    </svg>
  );
}

/* ========================================================================== */
/* Residential — the site, not the house: gate, perimeter, and the one panel  */
/* the owner actually touches.                                                */
/* ========================================================================== */

function VillaSite() {
  return (
    <svg viewBox="0 0 560 400" {...svg}>
      {/* Plot boundary. */}
      <rect x="52" y="56" width="456" height="266" rx="6" fill="none" stroke={lineStrong} strokeWidth="1.5" strokeDasharray="8 6" />

      {/* Perimeter detection. */}
      <rect x="64" y="68" width="432" height="242" rx="5" fill="none" stroke={accent} strokeOpacity="0.35" strokeWidth="1.2" />
      <text x="280" y="84" fill={accent} fontSize="8" textAnchor="middle" letterSpacing="1" {...mono}>
        PERIMETER DETECTION
      </text>

      {/* House. */}
      <path d="M170 168 L262 116 L354 168 V286 H170 Z" fill={surfaceDeep} stroke={lineStrong} strokeWidth="1.7" />
      <path d="M170 168 H354" stroke={lineStrong} strokeWidth="1.2" />
      {[
        { x: 190, y: 190, w: 44, h: 34 },
        { x: 246, y: 190, w: 44, h: 34 },
        { x: 302, y: 190, w: 34, h: 34 },
        { x: 190, y: 238, w: 60, h: 34 },
        { x: 262, y: 238, w: 74, h: 34 },
      ].map((room) => (
        <rect key={`${room.x}-${room.y}`} x={room.x} y={room.y} width={room.w} height={room.h} rx="2.5" fill={surface} stroke={line} strokeWidth="1" />
      ))}

      {/* Gate + video intercom. */}
      <g>
        <rect x="52" y="164" width="8" height="52" fill="rgb(var(--c-surface-1))" stroke={accent} strokeWidth="1.6" />
        <rect x="20" y="176" width="24" height="30" rx="3" fill={surface} stroke={primary} strokeWidth="1.4" />
        <circle cx="32" cy="186" r="3.2" fill={accent} />
        <rect x="26" y="194" width="12" height="7" rx="1.5" fill={line} />
        <text x="32" y="222" fill={label} fontSize="7.5" textAnchor="middle" {...mono}>INTERCOM</text>
        <path d="M60 190 H170" stroke={line} strokeWidth="1.2" strokeDasharray="4 5" />
      </g>

      {/* Driveway camera + garden zones. */}
      <circle cx="120" cy="120" r="6.5" fill="rgb(var(--c-surface-1))" stroke={accent} strokeWidth="1.4" />
      <circle cx="120" cy="120" r="2.3" fill={accent} />
      <path d="M120 120 L196 104 A78 78 0 0 1 196 152 Z" fill="rgb(var(--c-accent-500) / 0.12)" />

      {/* Irrigation / garden zone markers. */}
      {[{ x: 404, y: 128 }, { x: 448, y: 190 }, { x: 404, y: 252 }].map((z, i) => (
        <g key={i}>
          <circle cx={z.x} cy={z.y} r="16" fill="none" stroke={ok} strokeOpacity="0.4" strokeWidth="1.1" strokeDasharray="3 4" />
          <circle cx={z.x} cy={z.y} r="3.2" fill={ok} fillOpacity="0.8" />
        </g>
      ))}
      <text x="426" y="292" fill={label} fontSize="7.5" textAnchor="middle" {...mono}>IRRIGATION</text>

      {/* One panel. */}
      <rect x="196" y="300" width="132" height="42" rx="6" fill="rgb(var(--c-primary-500) / 0.18)" stroke={primary} strokeWidth="1.6" />
      <text x="262" y="318" fill={heading} fontSize="10" textAnchor="middle" {...mono}>ONE PANEL</text>
      <text x="262" y="333" fill={label} fontSize="7.5" textAnchor="middle" {...mono}>WALL · PHONE · VOICE</text>
      <path d="M262 286 V300" stroke={primary} strokeOpacity="0.5" strokeWidth="1.4" />

      <text x="280" y="368" fill={label} fontSize="10" textAnchor="middle" {...mono}>
        GATE, GARDEN AND HOUSE ON ONE LOGIC
      </text>
    </svg>
  );
}

export const INDUSTRY_ILLUSTRATIONS = {
  ClinicFloor,
  OfficeFloor,
  HotelSection,
  CampusClassrooms,
  PlantElevation,
  SecureZones,
  StoreFloor,
  VillaSite,
} as const;
