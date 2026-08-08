import { Link } from 'react-router-dom';
import {
  COMPANY,
  MAILTO_SALES,
  MAILTO_SUPPORT,
  MAP_URL,
  TEL_URL,
  addressLine,
  socialLinks,
} from '../../config/site';
import { useContent, useLocale, useLocalePath, useUi } from '../../i18n/LocaleContext';
import { Icon, type IconName } from '../ui/Icon';
import { Container } from '../ui/primitives';

const SOCIAL_ICONS: Record<string, IconName> = {
  facebook: 'globe',
  linkedin: 'link',
  instagram: 'camera',
  youtube: 'monitor-play',
};

export function Footer() {
  const content = useContent();
  const ui = useUi();
  const locale = useLocale();
  const path = useLocalePath();
  const social = socialLinks();

  const channels = [
    { icon: 'map-pin' as const, label: ui.address, value: addressLine(locale), href: MAP_URL, external: true },
    { icon: 'phone' as const, label: ui.callUs, value: COMPANY.phoneDisplay, href: TEL_URL },
    { icon: 'mail' as const, label: ui.emailSales, value: COMPANY.emailSales, href: MAILTO_SALES },
    { icon: 'headset' as const, label: ui.emailSupport, value: COMPANY.emailSupport, href: MAILTO_SUPPORT },
  ];

  return (
    <footer className="border-t border-line/10 bg-surface-1/40">
      <Container>
        <div className="grid gap-12 py-16 lg:grid-cols-[1.2fr_2fr]">
          <div className="flex flex-col gap-5">
            <span className="text-h5 font-extrabold tracking-tight text-content-primary">
              {COMPANY.name}
            </span>
            <p className="max-w-sm text-body-sm text-content-tertiary">{content.footer.description}</p>
            {social.length > 0 && (
              <ul className="flex items-center gap-2">
                {social.map((link) => (
                  <li key={link.key}>
                    <a
                      href={link.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      aria-label={link.key}
                      className="panel inline-flex h-10 w-10 items-center justify-center rounded-md text-content-secondary transition-colors duration-fast hover:text-content-primary"
                    >
                      <Icon name={SOCIAL_ICONS[link.key] ?? 'link'} className="h-4 w-4" />
                    </a>
                  </li>
                ))}
              </ul>
            )}
          </div>

          <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            {content.footer.columns.map((column) => (
              <nav key={column.title} aria-label={column.title} className="flex flex-col gap-4">
                <h2 className="text-overline font-semibold uppercase tracking-[0.14em] text-content-primary">
                  {column.title}
                </h2>
                <ul className="flex flex-col gap-2.5">
                  {column.links.map((link) => (
                    <li key={`${link.to}-${link.label}`}>
                      <Link
                        to={path(link.to)}
                        className="text-body-sm text-content-tertiary transition-colors duration-fast hover:text-content-primary"
                      >
                        {link.label}
                      </Link>
                    </li>
                  ))}
                </ul>
              </nav>
            ))}

            <div className="flex flex-col gap-4">
              <h2 className="text-overline font-semibold uppercase tracking-[0.14em] text-content-primary">
                {content.footer.contactTitle}
              </h2>
              <ul className="flex flex-col gap-3">
                {channels.map((channel) => (
                  <li key={channel.label}>
                    <a
                      href={channel.href}
                      {...(channel.external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
                      className="group flex items-start gap-2.5 text-body-sm text-content-tertiary transition-colors duration-fast hover:text-content-primary"
                    >
                      <Icon name={channel.icon} className="mt-1 h-4 w-4 shrink-0 text-accent-400" />
                      <span className="flex flex-col">
                        {/* Full tertiary, not tertiary/80: the 80% variant
                            lands at 4.38:1 on the footer surface, just under
                            the 4.5:1 this 13px text needs. */}
                        <span className="text-caption uppercase tracking-[0.1em] text-content-tertiary">
                          {channel.label}
                        </span>
                        <span>{channel.value}</span>
                      </span>
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>

        <div className="rule-gradient" />

        <div className="flex flex-col gap-4 py-8 text-caption text-content-tertiary lg:flex-row lg:items-center lg:justify-between">
          <p className="max-w-2xl">{content.footer.legalNote}</p>
          <p className="shrink-0">
            © {new Date().getFullYear()} {COMPANY.name}. {content.footer.copyright}
          </p>
        </div>
      </Container>
    </footer>
  );
}
