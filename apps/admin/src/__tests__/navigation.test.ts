import { describe, expect, it } from 'vitest';

import { NAVIGATION, isPermitted, navigationFor } from '../navigation.js';

/**
 * The navigation matrix.
 *
 * This is a usability measure, never a security one — every route enforces its
 * own roles server-side. What these tests protect is the promise the panel
 * makes to an operator: that what they are shown is what they can actually use,
 * so a day is not spent collecting refusals.
 */
describe('navigation', () => {
  it('offers a support agent the sections they can use and no others', () => {
    const paths = navigationFor('support_agent').map((item) => item.path);

    expect(paths).toContain('/decisions');
    expect(paths).toContain('/plans');
    // Rights, media configuration and the staff list are all refused
    // server-side for this role, so offering them would only produce a 403.
    expect(paths).not.toContain('/rights');
    expect(paths).not.toContain('/media');
    expect(paths).not.toContain('/staff');
  });

  it('offers a platform engineer everything', () => {
    expect(navigationFor('platform_engineer')).toHaveLength(NAVIGATION.length);
  });

  it('offers an auditor reading but never the rights or media surfaces they cannot reach', () => {
    const paths = navigationFor('auditor').map((item) => item.path);

    expect(paths).toContain('/audit');
    expect(paths).toContain('/staff');
    expect(paths).not.toContain('/rights');
  });

  it('offers nothing at all when the role is unknown', () => {
    // Default deny extends to vocabulary the panel does not recognise: a role
    // added to the server before the panel knows about it shows an empty
    // navigation rather than every link.
    expect(navigationFor(undefined)).toEqual([]);
    expect(isPermitted('/staff', undefined)).toBe(false);
  });

  it('gives every section a description', () => {
    // Several screens answer a question that is not obvious from a table of
    // rows, and an operator who does not know what a screen is for uses it
    // wrongly or not at all.
    for (const item of NAVIGATION) {
      expect(item.description.length).toBeGreaterThan(10);
      expect(item.roles.length).toBeGreaterThan(0);
    }
  });
});
