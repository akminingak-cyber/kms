import { Link } from 'react-router-dom';
import { ROUTES } from '../i18n/routes';
import { useContent, useLocale, useLocalePath } from '../i18n/LocaleContext';
import { Seo } from '../components/seo/Seo';
import { faqPage, localBusiness, webPage } from '../components/seo/jsonld';
import { Figure } from '../components/media/Figure';
import { CtaSection } from '../components/layout/CtaSection';
import { Icon } from '../components/ui/Icon';
import {
  Accordion,
  Button,
  CheckList,
  Container,
  FeatureGrid,
  Reveal,
  Section,
  SectionHeading,
  StatGrid,
} from '../components/ui/primitives';
import { IndustryGrid, LayerDiagram, ServiceGrid, StepList } from '../components/ui/collections';

function Hero() {
  const { home, ui } = useContent();
  const path = useLocalePath();
  const hero = home.hero;

  return (
    <section className="relative overflow-hidden border-b border-line/10 pb-20 pt-[calc(var(--header-h)+3.5rem)] lg:pb-28 lg:pt-[calc(var(--header-h)+6rem)]">
      <div
        className="pointer-events-none absolute inset-0 bg-gradient-to-b from-primary-950/60 via-surface-base to-surface-base"
        aria-hidden="true"
      />
      <div
        className="pointer-events-none absolute inset-0 bg-grid-faint [background-size:64px_64px] [mask-image:linear-gradient(to_bottom,black,transparent_80%)]"
        aria-hidden="true"
      />
      <Container>
        <div className="relative grid gap-14 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
          <div className="flex flex-col gap-7">
            <Reveal>
              <span className="inline-flex items-center gap-2.5 rounded-md border border-primary-400/30 bg-primary-500/10 px-3.5 py-2 text-caption font-medium text-primary-200">
                <span className="h-1.5 w-1.5 rounded-full bg-accent-500" aria-hidden="true" />
                {hero.eyebrow}
              </span>
            </Reveal>

            <h1 className="max-w-xl text-display-lg font-extrabold text-content-primary animate-fade-up">
              {hero.headlineLead}{' '}
              <span className="text-gradient-brand">{hero.headlineAccent}</span>
            </h1>

            <p className="max-w-xl text-body-lg text-content-secondary animate-fade-up">
              {hero.description}
            </p>

            <div className="flex flex-wrap gap-3 animate-fade-up">
              <Button to={path(ROUTES.contact)} size="lg" icon="arrow-right">
                {hero.primaryCta}
              </Button>
              <Button to={path(ROUTES.services)} variant="secondary" size="lg">
                {hero.secondaryCta}
              </Button>
            </div>

            <ul className="mt-2 flex flex-wrap gap-2.5">
              {hero.pillars.map((pillar) => (
                <li
                  key={pillar.label}
                  className="flex items-center gap-2 rounded-md border border-line/10 bg-surface-1/60 px-3.5 py-2 text-caption text-content-secondary"
                >
                  <Icon name={pillar.icon} className="h-4 w-4 shrink-0 text-accent-400" />
                  {pillar.label}
                </li>
              ))}
            </ul>
          </div>

          {/* The hero illustration is the largest element above the fold, so it
              loads eagerly — everything further down stays lazy. */}
          <Figure name="heroDiagram" priority sizes="(min-width: 1024px) 46vw, 100vw" />
        </div>

        <p className="mt-14 flex items-center gap-2 text-caption uppercase tracking-[0.14em] text-content-tertiary">
          <Icon name="chevron-down" className="h-4 w-4 animate-scroll-hint" />
          {hero.scrollHint}
          <span className="sr-only">{ui.skipToContent}</span>
        </p>
      </Container>
    </section>
  );
}

export function HomePage() {
  const content = useContent();
  const locale = useLocale();
  const path = useLocalePath();
  const { home } = content;
  const faqItems = content.faq.categories.flatMap((category) => category.items).slice(0, 6);

  return (
    <>
      <Seo
        title={home.seo.title}
        description={home.seo.description}
        path={ROUTES.home}
        jsonLd={[
          localBusiness(locale, home.seo.description),
          webPage(locale, ROUTES.home, home.seo.title, home.seo.description),
          faqPage(faqItems),
        ]}
      />

      <Hero />

      <Section scheme="light" tone="raised" className="border-y border-line/10">
        <Container>
          <h2 className="sr-only">{home.trust.heading}</h2>
          <FeatureGrid items={home.trust.items} columns={4} variant="plain" />
        </Container>
      </Section>

      <Section scheme="light" grid>
        <Container>
          <div className="grid gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
            <div className="flex flex-col gap-6">
              <SectionHeading
                eyebrow={home.intro.eyebrow}
                heading={home.intro.heading}
                className="max-w-none"
              />
              <Reveal delay={80} className="flex flex-col gap-5">
                {home.intro.paragraphs.map((paragraph) => (
                  <p key={paragraph} className="max-w-prose text-body-md text-content-secondary">
                    {paragraph}
                  </p>
                ))}
                <CheckList items={home.intro.points} className="mt-2" />
                <div className="mt-3">
                  <Button to={path(ROUTES.about)} variant="secondary" icon="arrow-right">
                    {home.intro.cta}
                  </Button>
                </div>
              </Reveal>
            </div>
            <Reveal delay={140}>
              <Figure name="teamPhoto" />
            </Reveal>
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="raised" id="services">
        <Container>
          <div className="flex flex-col gap-10">
            <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
              <SectionHeading
                eyebrow={home.services.eyebrow}
                heading={home.services.heading}
                description={home.services.description}
              />
              <Reveal>
                <Button to={path(ROUTES.services)} variant="secondary" icon="arrow-right">
                  {home.services.cta}
                </Button>
              </Reveal>
            </div>
            <ServiceGrid services={content.services} />
          </div>
        </Container>
      </Section>

      <Section>
        <Container>
          <div className="flex flex-col gap-10">
            <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
              <SectionHeading
                eyebrow={home.industries.eyebrow}
                heading={home.industries.heading}
                description={home.industries.description}
              />
              <Reveal>
                <Button to={path(ROUTES.industries)} variant="secondary" icon="arrow-right">
                  {home.industries.cta}
                </Button>
              </Reveal>
            </div>
            <IndustryGrid industries={content.industries} />
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="accent" grid>
        <Container>
          <div className="flex flex-col gap-10">
            <SectionHeading
              eyebrow={home.why.eyebrow}
              heading={home.why.heading}
              description={home.why.description}
            />
            <FeatureGrid items={home.why.items} columns={3} />
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="raised">
        <Container>
          <div className="grid gap-12 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
            <Reveal>
              <Figure name="smartPhoto" />
            </Reveal>
            <div className="flex flex-col gap-8">
              <SectionHeading
                eyebrow={home.smart.eyebrow}
                heading={home.smart.heading}
                description={home.smart.description}
              />
              <div className="grid gap-4 sm:grid-cols-2">
                {home.smart.scenarios.map((scenario, index) => (
                  <Reveal key={scenario.title} delay={index * 60} className="h-full">
                    <div className="flex h-full flex-col gap-3 rounded-lg border border-line/10 bg-surface-2/40 p-5">
                      <Icon name={scenario.icon} className="h-5 w-5 text-accent-400" />
                      <h3 className="text-h6 font-semibold text-content-primary">{scenario.title}</h3>
                      <p className="text-body-sm text-content-tertiary">{scenario.description}</p>
                    </div>
                  </Reveal>
                ))}
              </div>
              <Reveal>
                <Button to={path(ROUTES.service('smart-home'))} icon="arrow-right">
                  {home.smart.cta}
                </Button>
              </Reveal>
            </div>
          </div>
        </Container>
      </Section>

      <Section>
        <Container>
          <div className="flex flex-col gap-10">
            <SectionHeading
              eyebrow={home.technologies.eyebrow}
              heading={home.technologies.heading}
              description={home.technologies.description}
            />
            <Reveal>
              <LayerDiagram
                layers={content.technologies.diagram.layers}
                alt={content.technologies.diagram.alt}
              />
            </Reveal>
            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
              {content.technologies.domains.map((domain, index) => (
                <Reveal key={domain.title} delay={index * 50} className="h-full">
                  <div className="panel flex h-full flex-col gap-4 rounded-lg p-6">
                    <span className="icon-badge inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md">
                      <Icon name={domain.icon} className="h-5 w-5" />
                    </span>
                    <h3 className="text-h6 font-semibold text-content-primary">{domain.title}</h3>
                    <ul className="flex flex-wrap gap-2">
                      {domain.items.slice(0, 4).map((item) => (
                        <li
                          key={item}
                          className="rounded-sm border border-line/10 bg-surface-2/60 px-2.5 py-1 text-caption text-content-tertiary"
                        >
                          {item}
                        </li>
                      ))}
                    </ul>
                  </div>
                </Reveal>
              ))}
            </div>
            <Reveal className="flex flex-col gap-5">
              <p className="max-w-3xl rounded-md border border-line/10 bg-surface-1/60 p-5 text-body-sm text-content-tertiary">
                {home.technologies.disclaimer}
              </p>
              <div>
                <Button to={path(ROUTES.technologies)} variant="secondary" icon="arrow-right">
                  {home.technologies.cta}
                </Button>
              </div>
            </Reveal>
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="raised" grid>
        <Container>
          <div className="grid gap-12 lg:grid-cols-[0.85fr_1.15fr]">
            <div className="flex flex-col gap-6 lg:sticky lg:top-[calc(var(--header-h)+2rem)] lg:self-start">
              <SectionHeading
                eyebrow={home.process.eyebrow}
                heading={home.process.heading}
                description={home.process.description}
              />
              <Reveal>
                <Button to={path(ROUTES.process)} variant="secondary" icon="arrow-right">
                  {home.process.cta}
                </Button>
              </Reveal>
            </div>
            <StepList steps={content.process.steps} compact />
          </div>
        </Container>
      </Section>

      <Section scheme="light">
        <Container>
          <div className="flex flex-col gap-10">
            <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
              <SectionHeading
                eyebrow={home.projects.eyebrow}
                heading={home.projects.heading}
                description={home.projects.description}
              />
              <Reveal>
                <Button to={path(ROUTES.portfolio)} variant="secondary" icon="arrow-right">
                  {home.projects.cta}
                </Button>
              </Reveal>
            </div>
            <Reveal>
              <p className="flex items-start gap-3 rounded-md border border-line/10 bg-surface-1/60 p-5 text-body-sm text-content-tertiary">
                <Icon name="badge-check" className="mt-0.5 h-4 w-4 shrink-0 text-accent-400" />
                {home.projects.notice}
              </p>
            </Reveal>
            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
              {content.portfolio.projects.slice(0, 3).map((project, index) => (
                <Reveal key={project.slug} delay={index * 60} className="h-full">
                  <Link
                    to={path(ROUTES.portfolio)}
                    className="group flex h-full flex-col gap-4 rounded-lg border border-line/10 bg-surface-1/50 p-6 transition-all duration-base hover:-translate-y-1 hover:border-primary-400/40"
                  >
                    <Icon name={project.icon} className="h-6 w-6 text-accent-400" />
                    <h3 className="text-h6 font-semibold text-content-primary">{project.name}</h3>
                    <p className="flex-1 text-body-sm text-content-tertiary">{project.summary}</p>
                    <span className="text-caption uppercase tracking-[0.1em] text-content-tertiary">
                      {content.ui.projectKind[project.kind]}
                    </span>
                  </Link>
                </Reveal>
              ))}
            </div>
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="raised">
        <Container>
          <div className="flex flex-col gap-10">
            <SectionHeading
              eyebrow={home.metrics.eyebrow}
              heading={home.metrics.heading}
              description={home.metrics.description}
            />
            <StatGrid items={home.metrics.items} />
            <Reveal>
              <p className="max-w-2xl text-body-sm text-content-tertiary">{home.metrics.note}</p>
            </Reveal>
          </div>
        </Container>
      </Section>

      <Section scheme="light">
        <Container>
          <div className="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
            <SectionHeading
              eyebrow={home.faq.eyebrow}
              heading={home.faq.heading}
              description={home.faq.description}
            >
              <Reveal className="mt-2">
                <Button to={path(ROUTES.faq)} variant="secondary" icon="arrow-right">
                  {home.faq.cta}
                </Button>
              </Reveal>
            </SectionHeading>
            <Accordion items={faqItems} />
          </div>
        </Container>
      </Section>

      <CtaSection
        eyebrow={home.cta.eyebrow}
        heading={home.cta.heading}
        description={home.cta.description}
        primaryLabel={home.cta.primary}
        secondaryLabel={home.cta.secondary}
        points={home.cta.points}
      />
    </>
  );
}
