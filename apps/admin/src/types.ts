/**
 * Domain types for the panel, taken **from the generated contract** rather than
 * declared here.
 *
 * Re-declaring a shape by hand is how a client starts disagreeing with a server
 * that still compiles: both sides look right, and the mismatch only appears at
 * runtime, in front of an operator. These aliases exist so screens can name a
 * type without reaching into the generated file's structure, and nothing more.
 */
import type { AdminComponents } from '@kms/ts-api-client';

type Schemas = AdminComponents['schemas'];

export type StaffMember = Schemas['StaffMember'];
export type StaffRole = Schemas['StaffRole'];
export type AdminChannel = Schemas['AdminChannel'];
export type Category = Schemas['Category'];
export type AdminPackage = Schemas['AdminPackage'];
export type AdminPlan = Schemas['AdminPlan'];
export type Agreement = Schemas['Agreement'];
export type Right = Schemas['Right'];
export type Blackout = Schemas['Blackout'];
export type Publication = Schemas['Publication'];
export type PackagingProfile = Schemas['PackagingProfile'];
export type Ladder = Schemas['Ladder'];
export type Decision = Schemas['Decision'];
export type AdminSession = Schemas['AdminSession'];
export type AuditEntry = Schemas['AuditEntry'];
export type Page = Schemas['Page'];

/**
 * Money, formatted from minor units and an explicit currency.
 *
 * Never from a float, and never from an amount without its currency beside it —
 * "999" is not a price, and rendering it as one is how a currency mistake
 * reaches a customer.
 */
export function formatMoney(price: AdminPlan['price']): string {
  if (price === null || price === undefined) {
    return '—';
  }

  const { amount_minor: minor, currency } = price;

  if (typeof minor !== 'number' || typeof currency !== 'string') {
    return '—';
  }

  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency,
  }).format(minor / 100);
}

/** Renders an instant in the operator's locale, or an em dash for absence. */
export function formatInstant(value: string | null | undefined): string {
  if (value === null || value === undefined || value === '') {
    return '—';
  }

  const parsed = new Date(value);

  return Number.isNaN(parsed.getTime()) ? '—' : parsed.toLocaleString();
}
