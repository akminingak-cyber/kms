import { COMPANY, TEL_URL } from '../../config/site';
import { ROUTES } from '../../i18n/routes';
import { useLocalePath } from '../../i18n/LocaleContext';
import { Icon } from '../ui/Icon';
import { Button, Container, Eyebrow, Reveal, Section } from '../ui/primitives';

interface CtaSectionProps {
  eyebrow?: string;
  heading: string;
  description: string;
  primaryLabel: string;
  secondaryLabel?: string;
  points?: string[];
}

export function CtaSection({
  eyebrow,
  heading,
  description,
  primaryLabel,
  secondaryLabel,
  points = [],
}: CtaSectionProps) {
  const path = useLocalePath();

  return (
    <Section tone="accent" grid>
      <Container>
        <div className="grid gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
          <div className="flex flex-col gap-5">
            {eyebrow && <Eyebrow>{eyebrow}</Eyebrow>}
            <h2 className="text-h2 font-semibold text-content-primary">{heading}</h2>
            <p className="max-w-2xl text-body-lg text-content-secondary">{description}</p>
            <div className="mt-2 flex flex-wrap gap-3">
              <Button to={path(ROUTES.contact)} size="lg" icon="arrow-right">
                {primaryLabel}
              </Button>
              {secondaryLabel && (
                <Button href={TEL_URL} variant="secondary" size="lg" icon="phone">
                  {secondaryLabel}
                </Button>
              )}
            </div>
            <p className="mt-1 text-caption text-content-tertiary">{COMPANY.phoneDisplay}</p>
          </div>

          {points.length > 0 && (
            <Reveal delay={100}>
              <ul className="flex flex-col gap-3 rounded-lg border border-line/10 bg-surface-1/50 p-6">
                {points.map((point) => (
                  <li key={point} className="flex items-start gap-3 text-body-sm text-content-secondary">
                    <Icon name="check-circle" className="mt-0.5 h-4 w-4 shrink-0 text-accent-400" />
                    {point}
                  </li>
                ))}
              </ul>
            </Reveal>
          )}
        </div>
      </Container>
    </Section>
  );
}
