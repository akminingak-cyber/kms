import { company, contact, footer } from '../content/site';
import { Logo } from './Nav';

export default function Footer() {
  const year = new Date().getFullYear();

  return (
    <footer className="relative border-t border-white/8 bg-ink-950">
      <div className="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-brand-400/30 to-transparent" />

      <div className="shell relative py-16">
        <div className="grid gap-12 lg:grid-cols-[1.6fr_1fr_1fr_1.2fr]">
          <div>
            <Logo />
            <p className="mt-5 max-w-xs text-sm leading-relaxed text-slate-500">{footer.about}</p>
          </div>

          {footer.columns.map((col) => (
            <div key={col.title}>
              <h3 className="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                {col.title}
              </h3>
              <ul className="mt-5 space-y-3">
                {col.links.map((link) => (
                  <li key={link.label}>
                    <a
                      href={link.href}
                      className="text-sm text-slate-500 transition-colors duration-200 hover:text-brand-300"
                    >
                      {link.label}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          ))}

          <div>
            <h3 className="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
              კონტაქტი
            </h3>
            <ul className="mt-5 space-y-3 text-sm">
              <li>
                <a
                  href={`tel:${contact.phone.replace(/\s/g, '')}`}
                  className="text-slate-500 transition-colors hover:text-brand-300"
                >
                  {contact.phone}
                </a>
              </li>
              <li>
                <a
                  href={`mailto:${contact.email}`}
                  className="text-slate-500 transition-colors hover:text-brand-300"
                >
                  {contact.email}
                </a>
              </li>
              <li className="text-slate-500">{contact.address}</li>
            </ul>
          </div>
        </div>

        <div className="rule mt-14" />

        <div className="mt-8 flex flex-col items-center justify-between gap-4 text-xs text-slate-600 sm:flex-row">
          <p>
            © {year} {company.legalName}. {footer.legal}
          </p>
          <p className="font-mono tracking-tight">{company.domain}</p>
        </div>
      </div>
    </footer>
  );
}
