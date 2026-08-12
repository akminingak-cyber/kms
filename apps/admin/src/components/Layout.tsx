import type { ReactNode } from 'react';
import { NavLink } from 'react-router-dom';

import { useSession } from '../api/SessionProvider.js';
import { navigationFor } from '../navigation.js';

/**
 * The frame: who you are, what you may reach, and a way out.
 *
 * The role is shown next to the name on purpose. An operator who cannot see
 * which role they hold cannot tell whether a refusal is a bug or the system
 * working, and "it says I can't" is a much cheaper support conversation when
 * they can read their own role off the screen.
 */
export function Layout({ children }: { readonly children: ReactNode }) {
  const { staff, signOut } = useSession();
  const items = navigationFor(staff?.role);

  return (
    <div className="layout">
      <header className="layout__header">
        <span className="layout__brand">KMS TV</span>
        <span className="layout__identity">
          {staff?.name}
          <span className="badge">{staff?.role?.replace(/_/g, ' ')}</span>
        </span>
        <button type="button" onClick={signOut}>
          Sign out
        </button>
      </header>

      <div className="layout__body">
        <nav className="layout__nav" aria-label="Sections">
          <ul>
            {items.map((item) => (
              <li key={item.path}>
                <NavLink to={item.path} title={item.description}>
                  {item.label}
                </NavLink>
              </li>
            ))}
          </ul>
          {items.length === 0 && (
            <p className="muted">
              Your role has no sections assigned. That is a configuration question for a platform
              engineer, not something to work around here.
            </p>
          )}
        </nav>

        <main className="layout__main">{children}</main>
      </div>
    </div>
  );
}
