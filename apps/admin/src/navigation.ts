import type { StaffRole } from './types.js';

/**
 * What each role is offered in the navigation.
 *
 * **This is a usability measure and nothing else.** Every route on the admin API
 * names the roles that may reach it and enforces them server-side; hiding a
 * link has never stopped anyone typing a URL, and the client is never a
 * security boundary (`CLAUDE.md` §7).
 *
 * What it does buy is real: an operator who is shown only what they can use
 * does not spend their day collecting refusals, and a support agent who never
 * sees a "Rights" tab never has to wonder whether they should have.
 *
 * The lists mirror the role matrix in the admin API contract. They are
 * duplicated here rather than derived, because deriving them would mean the
 * server telling the client what to render — which is a larger coupling than
 * the duplication saves, and would make the contract's matrix the second copy
 * instead of the first.
 */
export interface NavigationItem {
  readonly path: string;
  readonly label: string;
  readonly roles: readonly StaffRole[];
  readonly description: string;
}

const ALL_ROLES: readonly StaffRole[] = [
  'platform_engineer',
  'content_operator',
  'commercial_operator',
  'rights_manager',
  'support_agent',
  'auditor',
];

export const NAVIGATION: readonly NavigationItem[] = [
  {
    path: '/channels',
    label: 'Channels',
    roles: ALL_ROLES,
    description: 'Every channel, including those a viewer cannot see',
  },
  {
    path: '/categories',
    label: 'Categories',
    roles: ['content_operator', 'platform_engineer', 'support_agent', 'rights_manager', 'auditor'],
    description: 'The content taxonomy',
  },
  {
    path: '/plans',
    label: 'Plans & packages',
    roles: ['commercial_operator', 'platform_engineer', 'support_agent', 'auditor'],
    description: 'What is sold, and what it includes',
  },
  {
    path: '/rights',
    label: 'Rights',
    roles: ['rights_manager', 'platform_engineer'],
    description: 'Agreements, rights, blackouts, and availability preview',
  },
  {
    path: '/media',
    label: 'Media',
    roles: ['platform_engineer'],
    description: 'Encoding ladders, packaging and publications',
  },
  {
    path: '/decisions',
    label: 'Playback decisions',
    roles: ['support_agent', 'platform_engineer', 'auditor', 'rights_manager'],
    description: 'Why a viewer was allowed or refused',
  },
  {
    path: '/sessions',
    label: 'Sessions',
    roles: ['support_agent', 'platform_engineer', 'auditor', 'rights_manager'],
    description: 'What is playing right now',
  },
  {
    path: '/audit',
    label: 'Audit',
    roles: ['auditor', 'platform_engineer', 'support_agent'],
    description: 'Who changed what',
  },
  {
    path: '/staff',
    label: 'Staff',
    roles: ['auditor', 'platform_engineer'],
    description: 'Who has access',
  },
];

export function navigationFor(role: StaffRole | undefined): readonly NavigationItem[] {
  if (role === undefined) {
    return [];
  }

  return NAVIGATION.filter((item) => item.roles.includes(role));
}

export function isPermitted(path: string, role: StaffRole | undefined): boolean {
  return navigationFor(role).some((item) => item.path === path);
}
