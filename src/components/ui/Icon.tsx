import {
  Activity,
  AlertTriangle,
  ArrowRight,
  ArrowUpRight,
  AudioLines,
  BadgeCheck,
  BedDouble,
  Blinds,
  Building,
  Building2,
  Cable,
  Camera,
  Check,
  CheckCircle,
  ChevronDown,
  ChevronRight,
  ClipboardCheck,
  Clock,
  Cloud,
  Compass,
  Cpu,
  Database,
  DoorOpen,
  Eye,
  Factory,
  FileText,
  Fingerprint,
  Flame,
  Gauge,
  Globe,
  GraduationCap,
  HardDrive,
  Headset,
  House,
  Landmark,
  Layers,
  Lightbulb,
  Link as LinkIcon,
  ListChecks,
  Lock,
  Mail,
  MapPin,
  Menu,
  MonitorPlay,
  Network,
  Phone,
  Presentation,
  RadioTower,
  RefreshCw,
  Route,
  Router,
  Ruler,
  ScanFace,
  Search,
  Server,
  Settings,
  Shield,
  ShieldCheck,
  ShoppingBag,
  Siren,
  Snowflake,
  Sparkles,
  Speaker,
  Stethoscope,
  Target,
  Thermometer,
  TrendingUp,
  Users,
  Video,
  Wifi,
  Workflow,
  Wrench,
  X,
  Zap,
  type LucideIcon,
} from 'lucide-react';

/**
 * Icons are imported by name rather than resolved dynamically so the bundler
 * can drop the ~1400 lucide icons this site never uses. Content files reference
 * these keys as strings; the IconName union makes a typo a build error instead
 * of a blank square in production.
 */
export const ICONS = {
  activity: Activity,
  'alert-triangle': AlertTriangle,
  'arrow-right': ArrowRight,
  'arrow-up-right': ArrowUpRight,
  'audio-lines': AudioLines,
  'badge-check': BadgeCheck,
  'bed-double': BedDouble,
  blinds: Blinds,
  building: Building,
  'building-2': Building2,
  cable: Cable,
  camera: Camera,
  check: Check,
  'check-circle': CheckCircle,
  'chevron-down': ChevronDown,
  'chevron-right': ChevronRight,
  'clipboard-check': ClipboardCheck,
  clock: Clock,
  cloud: Cloud,
  compass: Compass,
  cpu: Cpu,
  database: Database,
  'door-open': DoorOpen,
  eye: Eye,
  factory: Factory,
  'file-text': FileText,
  fingerprint: Fingerprint,
  flame: Flame,
  gauge: Gauge,
  globe: Globe,
  'graduation-cap': GraduationCap,
  'hard-drive': HardDrive,
  headset: Headset,
  house: House,
  landmark: Landmark,
  layers: Layers,
  lightbulb: Lightbulb,
  link: LinkIcon,
  'list-checks': ListChecks,
  lock: Lock,
  mail: Mail,
  'map-pin': MapPin,
  menu: Menu,
  'monitor-play': MonitorPlay,
  network: Network,
  phone: Phone,
  presentation: Presentation,
  'radio-tower': RadioTower,
  'refresh-cw': RefreshCw,
  route: Route,
  router: Router,
  ruler: Ruler,
  'scan-face': ScanFace,
  search: Search,
  server: Server,
  settings: Settings,
  shield: Shield,
  'shield-check': ShieldCheck,
  'shopping-bag': ShoppingBag,
  siren: Siren,
  snowflake: Snowflake,
  sparkles: Sparkles,
  speaker: Speaker,
  stethoscope: Stethoscope,
  target: Target,
  thermometer: Thermometer,
  'trending-up': TrendingUp,
  users: Users,
  video: Video,
  wifi: Wifi,
  workflow: Workflow,
  wrench: Wrench,
  x: X,
  zap: Zap,
} satisfies Record<string, LucideIcon>;

export type IconName = keyof typeof ICONS;

interface IconProps {
  name: IconName;
  className?: string;
  strokeWidth?: number;
}

/**
 * Always decorative. Every icon on the site sits next to a text label, so
 * exposing it to a screen reader would only produce a duplicate announcement.
 */
export function Icon({ name, className = 'h-5 w-5', strokeWidth = 1.75 }: IconProps) {
  const Glyph = ICONS[name];
  return <Glyph className={className} strokeWidth={strokeWidth} aria-hidden="true" />;
}

export function IconBadge({ name, className = '' }: { name: IconName; className?: string }) {
  return (
    <span
      className={`icon-badge inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md ${className}`}
    >
      <Icon name={name} className="h-5 w-5" />
    </span>
  );
}
