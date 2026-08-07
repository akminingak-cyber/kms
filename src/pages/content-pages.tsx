import { useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ROUTES } from '../i18n/routes';
import { useContent, useLocale, useLocalePath, useUi } from '../i18n/LocaleContext';
import { Seo } from '../components/seo/Seo';
import { breadcrumbs, faqPage, localBusiness, service as serviceLd, webPage } from '../components/seo/jsonld';
import { PageHero, Breadcrumbs } from '../components/layout/PageHero';
import { CtaSection } from '../components/layout/CtaSection';
import { Figure } from '../components/media/Figure';
import { ContactForm } from '../components/forms/ContactForm';
import { Icon } from '../components/ui/Icon';
import {
  Accordion,
  Button,
  CheckList,
  Container,
  FeatureGrid,
  GroupGrid,
  Reveal,
  Section,
  SectionHeading,
  TagList,
} from '../components/ui/primitives';
import { IndustryGrid, LayerDiagram, ServiceGrid, StepList } from '../components/ui/collections';
import {
  COMPANY,
  MAILTO_SALES,
  MAILTO_SUPPORT,
  MAP_URL,
  TEL_URL,
  WHATSAPP_URL,
  addressLine,
} from '../config/site';

/* ========================================================================== */
/* About                                                                      */
/* ========================================================================== */

export function AboutPage() {
  const content = useContent();
  const locale = useLocale();
  const { about, nav, home } = content;

  return (
    <>
      <Seo
        title={about.seo.title}
        description={about.seo.description}
        path={ROUTES.about}
        jsonLd={[
          localBusiness(locale, about.seo.description),
          webPage(locale, ROUTES.about, about.seo.title, about.seo.description),
          breadcrumbs(locale, [
            { label: content.ui.breadcrumbHome, path: ROUTES.home },
            { label: nav.about, path: ROUTES.about },
          ]),
        ]}
      />
      <PageHero content={about.hero} crumbs={[{ label: nav.about }]} icon="users" />

      <Section scheme="light">
        <Container>
          <div className="grid gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
            <div className="flex flex-col gap-6">
              <SectionHeading heading={about.story.heading} className="max-w-none" />
              <Reveal delay={80} className="flex flex-col gap-5">
                {about.story.paragraphs.map((paragraph) => (
                  <p key={paragraph} className="max-w-prose text-body-md text-content-secondary">
                    {paragraph}
                  </p>
                ))}
              </Reveal>
            </div>
            <Reveal delay={140}>
              <Figure name="teamPhoto" />
            </Reveal>
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="raised">
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={about.mission.heading} />
            <FeatureGrid items={about.mission.items} columns={3} />
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="accent" grid>
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={about.principles.heading} description={about.principles.intro} />
            <FeatureGrid items={about.principles.items} columns={3} />
          </div>
        </Container>
      </Section>

      <Section scheme="light">
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={about.disciplines.heading} description={about.disciplines.intro} />
            <GroupGrid groups={about.disciplines.groups} columns={2} />
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="raised">
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={about.standards.heading} description={about.standards.intro} />
            <div className="grid gap-x-10 gap-y-3 sm:grid-cols-2">
              <CheckList items={about.standards.items.slice(0, Math.ceil(about.standards.items.length / 2))} />
              <CheckList items={about.standards.items.slice(Math.ceil(about.standards.items.length / 2))} />
            </div>
            <Reveal>
              <p className="max-w-3xl rounded-md border border-line/10 bg-surface-1/60 p-5 text-body-sm text-content-tertiary">
                {about.standards.note}
              </p>
            </Reveal>
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

/* ========================================================================== */
/* Services                                                                   */
/* ========================================================================== */

export function ServicesIndexPage() {
  const content = useContent();
  const locale = useLocale();
  const { servicesIndex, nav, home } = content;

  return (
    <>
      <Seo
        title={servicesIndex.seo.title}
        description={servicesIndex.seo.description}
        path={ROUTES.services}
        jsonLd={[
          webPage(locale, ROUTES.services, servicesIndex.seo.title, servicesIndex.seo.description),
          breadcrumbs(locale, [
            { label: content.ui.breadcrumbHome, path: ROUTES.home },
            { label: nav.services, path: ROUTES.services },
          ]),
        ]}
      />
      <PageHero content={servicesIndex.hero} crumbs={[{ label: nav.services }]} icon="layers" />

      {servicesIndex.groups.map((group, index) => {
        const services = content.services.filter((item) => item.category === group.key);
        if (services.length === 0) return null;
        return (
          <Section key={group.key} scheme="light" tone={index % 2 === 0 ? 'base' : 'raised'}>
            <Container>
              <div className="flex flex-col gap-9">
                <SectionHeading
                  eyebrow={`0${index + 1}`}
                  heading={group.title}
                  description={group.description}
                />
                <ServiceGrid services={services} />
              </div>
            </Container>
          </Section>
        );
      })}

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

export function ServiceDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const content = useContent();
  const locale = useLocale();
  const path = useLocalePath();
  const ui = useUi();

  const service = content.services.find((item) => item.slug === slug);
  if (!service) return <NotFoundPage />;

  const route = ROUTES.service(service.slug);
  const related = content.services.filter((item) => service.related.includes(item.slug));
  const industries = content.industries.filter((item) => service.industries.includes(item.slug));

  return (
    <>
      <Seo
        title={service.seo.title}
        description={service.seo.description}
        path={route}
        jsonLd={[
          webPage(locale, route, service.seo.title, service.seo.description),
          serviceLd(locale, route, service.name, service.summary),
          breadcrumbs(locale, [
            { label: ui.breadcrumbHome, path: ROUTES.home },
            { label: content.nav.services, path: ROUTES.services },
            { label: service.name, path: route },
          ]),
          faqPage(service.faq),
        ]}
      />
      <PageHero
        content={service.hero}
        crumbs={[{ label: content.nav.services, path: ROUTES.services }, { label: service.name }]}
        icon={service.icon}
      />

      <Section scheme="light">
        <Container>
          <div className="grid gap-12 lg:grid-cols-[1.1fr_0.9fr]">
            <div className="flex flex-col gap-6">
              <SectionHeading heading={service.overview.heading} className="max-w-none" />
              <Reveal delay={80} className="flex flex-col gap-5">
                {service.overview.paragraphs.map((paragraph) => (
                  <p key={paragraph} className="max-w-prose text-body-md text-content-secondary">
                    {paragraph}
                  </p>
                ))}
              </Reveal>
            </div>
            <div className="flex flex-col gap-4">
              {service.overview.highlights.map((item, index) => (
                <Reveal key={item.title} delay={index * 60}>
                  <div className="flex gap-4 rounded-lg border border-line/10 bg-surface-1/50 p-5">
                    <Icon name={item.icon} className="mt-0.5 h-5 w-5 shrink-0 text-accent-400" />
                    <div className="flex flex-col gap-1.5">
                      <h3 className="text-h6 font-semibold text-content-primary">{item.title}</h3>
                      <p className="text-body-sm text-content-tertiary">{item.description}</p>
                    </div>
                  </div>
                </Reveal>
              ))}
            </div>
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="raised">
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={service.challenges.heading} description={service.challenges.intro} />
            <FeatureGrid items={service.challenges.items} columns={3} />
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="accent" grid>
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={service.solutions.heading} description={service.solutions.intro} />
            <FeatureGrid items={service.solutions.items} columns={3} />
          </div>
        </Container>
      </Section>

      <Section scheme="light">
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={service.capabilities.heading} />
            <GroupGrid groups={service.capabilities.groups} columns={3} />
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="raised">
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={service.benefits.heading} />
            <FeatureGrid items={service.benefits.items} columns={3} />
          </div>
        </Container>
      </Section>

      <Section scheme="light">
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={service.stack.heading} description={service.stack.intro} />
            <GroupGrid groups={service.stack.groups} columns={3} />
          </div>
        </Container>
      </Section>

      {industries.length > 0 && (
        <Section scheme="light" tone="raised">
          <Container>
            <div className="flex flex-col gap-9">
              <SectionHeading heading={ui.relatedIndustries} />
              <IndustryGrid industries={industries} />
            </div>
          </Container>
        </Section>
      )}

      <Section scheme="light">
        <Container>
          <div className="grid gap-10 lg:grid-cols-[0.7fr_1.3fr]">
            <SectionHeading eyebrow={content.home.faq.eyebrow} heading={content.home.faq.heading} />
            <Accordion items={service.faq} />
          </div>
        </Container>
      </Section>

      {related.length > 0 && (
        <Section scheme="light" tone="raised">
          <Container>
            <div className="flex flex-col gap-9">
              <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <SectionHeading heading={ui.relatedServices} />
                <Reveal>
                  <Button to={path(ROUTES.services)} variant="secondary" icon="arrow-right">
                    {ui.allServices}
                  </Button>
                </Reveal>
              </div>
              <ServiceGrid services={related} />
            </div>
          </Container>
        </Section>
      )}

      <CtaSection
        eyebrow={content.home.cta.eyebrow}
        heading={content.home.cta.heading}
        description={content.home.cta.description}
        primaryLabel={content.home.cta.primary}
        secondaryLabel={content.home.cta.secondary}
        points={content.home.cta.points}
      />
    </>
  );
}

/* ========================================================================== */
/* Industries                                                                 */
/* ========================================================================== */

export function IndustriesIndexPage() {
  const content = useContent();
  const locale = useLocale();
  const { industriesIndex, nav, home } = content;

  return (
    <>
      <Seo
        title={industriesIndex.seo.title}
        description={industriesIndex.seo.description}
        path={ROUTES.industries}
        jsonLd={[
          webPage(locale, ROUTES.industries, industriesIndex.seo.title, industriesIndex.seo.description),
          breadcrumbs(locale, [
            { label: content.ui.breadcrumbHome, path: ROUTES.home },
            { label: nav.industries, path: ROUTES.industries },
          ]),
        ]}
      />
      <PageHero content={industriesIndex.hero} crumbs={[{ label: nav.industries }]} icon="building-2" />

      <Section scheme="light">
        <Container>
          <IndustryGrid industries={content.industries} />
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

export function IndustryDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const content = useContent();
  const locale = useLocale();
  const ui = useUi();

  const industry = content.industries.find((item) => item.slug === slug);
  if (!industry) return <NotFoundPage />;

  const route = ROUTES.industry(industry.slug);
  const services = content.services.filter((item) => industry.services.includes(item.slug));

  return (
    <>
      <Seo
        title={industry.seo.title}
        description={industry.seo.description}
        path={route}
        jsonLd={[
          webPage(locale, route, industry.seo.title, industry.seo.description),
          breadcrumbs(locale, [
            { label: ui.breadcrumbHome, path: ROUTES.home },
            { label: content.nav.industries, path: ROUTES.industries },
            { label: industry.name, path: route },
          ]),
          faqPage(industry.faq),
        ]}
      />
      <PageHero
        content={industry.hero}
        crumbs={[{ label: content.nav.industries, path: ROUTES.industries }, { label: industry.name }]}
        icon={industry.icon}
      />

      <Section scheme="light">
        <Container>
          <div className="flex flex-col gap-6">
            <SectionHeading heading={industry.context.heading} className="max-w-none" />
            <Reveal delay={80} className="flex max-w-3xl flex-col gap-5">
              {industry.context.paragraphs.map((paragraph) => (
                <p key={paragraph} className="text-body-md text-content-secondary">
                  {paragraph}
                </p>
              ))}
            </Reveal>
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="raised">
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={industry.priorities.heading} />
            <FeatureGrid items={industry.priorities.items} columns={3} />
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="accent" grid>
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={industry.solutions.heading} />
            <FeatureGrid items={industry.solutions.items} columns={3} />
          </div>
        </Container>
      </Section>

      <Section scheme="light">
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={industry.scenarios.heading} />
            <FeatureGrid items={industry.scenarios.items} columns={3} />
          </div>
        </Container>
      </Section>

      {services.length > 0 && (
        <Section scheme="light" tone="raised">
          <Container>
            <div className="flex flex-col gap-9">
              <SectionHeading heading={ui.relatedServices} />
              <ServiceGrid services={services} />
            </div>
          </Container>
        </Section>
      )}

      <Section scheme="light">
        <Container>
          <div className="grid gap-10 lg:grid-cols-[0.7fr_1.3fr]">
            <SectionHeading eyebrow={content.home.faq.eyebrow} heading={content.home.faq.heading} />
            <Accordion items={industry.faq} />
          </div>
        </Container>
      </Section>

      <CtaSection
        eyebrow={content.home.cta.eyebrow}
        heading={content.home.cta.heading}
        description={content.home.cta.description}
        primaryLabel={content.home.cta.primary}
        secondaryLabel={content.home.cta.secondary}
        points={content.home.cta.points}
      />
    </>
  );
}

/* ========================================================================== */
/* Portfolio                                                                  */
/* ========================================================================== */

export function PortfolioPage() {
  const content = useContent();
  const locale = useLocale();
  const ui = useUi();
  const { portfolio, nav } = content;
  const [filter, setFilter] = useState<string>('all');

  const filters = useMemo(() => {
    const used = new Set(portfolio.projects.map((project) => project.industry));
    return content.industries.filter((industry) => used.has(industry.slug));
  }, [content.industries, portfolio.projects]);

  const visible =
    filter === 'all'
      ? portfolio.projects
      : portfolio.projects.filter((project) => project.industry === filter);

  return (
    <>
      <Seo
        title={portfolio.seo.title}
        description={portfolio.seo.description}
        path={ROUTES.portfolio}
        jsonLd={[
          webPage(locale, ROUTES.portfolio, portfolio.seo.title, portfolio.seo.description),
          breadcrumbs(locale, [
            { label: ui.breadcrumbHome, path: ROUTES.home },
            { label: nav.portfolio, path: ROUTES.portfolio },
          ]),
        ]}
      />
      <PageHero content={portfolio.hero} crumbs={[{ label: nav.portfolio }]} icon="presentation" />

      <Section scheme="light">
        <Container>
          <div className="flex flex-col gap-9">
            {/* States plainly that these are reference designs rather than
                named client work — the same claim the terms page makes. */}
            <Reveal>
              <div className="flex gap-4 rounded-lg border border-warning/25 bg-warning/5 p-6">
                <Icon name="badge-check" className="mt-0.5 h-5 w-5 shrink-0 text-warning" />
                <div className="flex flex-col gap-2">
                  <h2 className="text-h6 font-semibold text-content-primary">{portfolio.notice.heading}</h2>
                  <p className="text-body-sm text-content-secondary">{portfolio.notice.text}</p>
                </div>
              </div>
            </Reveal>

            <div className="flex flex-wrap gap-2" role="group" aria-label={nav.industries}>
              <button
                type="button"
                aria-pressed={filter === 'all'}
                onClick={() => setFilter('all')}
                className={`rounded-md border px-4 py-2 text-caption font-medium transition-colors duration-fast ${
                  filter === 'all'
                    ? 'border-primary-400/50 bg-primary-500/15 text-content-primary'
                    : 'border-line/10 bg-surface-1/50 text-content-tertiary hover:text-content-primary'
                }`}
              >
                {portfolio.filtersAll}
              </button>
              {filters.map((industry) => (
                <button
                  key={industry.slug}
                  type="button"
                  aria-pressed={filter === industry.slug}
                  onClick={() => setFilter(industry.slug)}
                  className={`rounded-md border px-4 py-2 text-caption font-medium transition-colors duration-fast ${
                    filter === industry.slug
                      ? 'border-primary-400/50 bg-primary-500/15 text-content-primary'
                      : 'border-line/10 bg-surface-1/50 text-content-tertiary hover:text-content-primary'
                  }`}
                >
                  {industry.name}
                </button>
              ))}
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
              {visible.map((project, index) => (
                <Reveal key={project.slug} delay={index * 60} className="h-full">
                  <article className="flex h-full flex-col gap-5 rounded-lg border border-line/10 bg-surface-1/50 p-7">
                    <div className="flex items-start justify-between gap-4">
                      <span className="icon-badge inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md">
                        <Icon name={project.icon} className="h-5 w-5" />
                      </span>
                      <span className="rounded-sm border border-line/10 bg-surface-2/60 px-2.5 py-1 text-caption text-content-tertiary">
                        {ui.projectKind[project.kind]}
                      </span>
                    </div>
                    <h2 className="text-h5 font-semibold text-content-primary">{project.name}</h2>
                    <p className="text-body-sm text-content-secondary">{project.summary}</p>

                    <div className="flex flex-col gap-2">
                      <h3 className="text-caption uppercase tracking-[0.1em] text-content-tertiary">
                        {ui.onThisPage}
                      </h3>
                      <TagList items={project.scope} />
                    </div>

                    <div className="mt-auto flex flex-col gap-2">
                      <CheckList items={project.outcomes} />
                    </div>
                  </article>
                </Reveal>
              ))}
            </div>
          </div>
        </Container>
      </Section>

      <CtaSection
        eyebrow={content.home.cta.eyebrow}
        heading={portfolio.cta.heading}
        description={portfolio.cta.description}
        primaryLabel={portfolio.cta.action}
        secondaryLabel={content.home.cta.secondary}
        points={content.home.cta.points}
      />
    </>
  );
}

/* ========================================================================== */
/* Process                                                                    */
/* ========================================================================== */

export function ProcessPage() {
  const content = useContent();
  const locale = useLocale();
  const ui = useUi();
  const { process, nav, home } = content;

  return (
    <>
      <Seo
        title={process.seo.title}
        description={process.seo.description}
        path={ROUTES.process}
        jsonLd={[
          webPage(locale, ROUTES.process, process.seo.title, process.seo.description),
          breadcrumbs(locale, [
            { label: ui.breadcrumbHome, path: ROUTES.home },
            { label: nav.process, path: ROUTES.process },
          ]),
          faqPage(process.faq),
        ]}
      />
      <PageHero content={process.hero} crumbs={[{ label: nav.process }]} icon="route" />

      <Section scheme="light">
        <Container>
          <div className="grid gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
            <div className="flex flex-col gap-6">
              <SectionHeading heading={process.intro.heading} className="max-w-none" />
              <Reveal delay={80} className="flex flex-col gap-5">
                {process.intro.paragraphs.map((paragraph) => (
                  <p key={paragraph} className="max-w-prose text-body-md text-content-secondary">
                    {paragraph}
                  </p>
                ))}
              </Reveal>
            </div>
            <Reveal delay={140}>
              <Figure name="deliveryPhoto" />
            </Reveal>
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="raised">
        <Container>
          <StepList steps={process.steps} />
        </Container>
      </Section>

      <Section scheme="light" tone="accent" grid>
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={process.quality.heading} description={process.quality.intro} />
            <FeatureGrid items={process.quality.items} columns={3} />
          </div>
        </Container>
      </Section>

      <Section scheme="light">
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading
              heading={process.documentation.heading}
              description={process.documentation.intro}
            />
            <div className="grid gap-x-10 gap-y-3 sm:grid-cols-2">
              <CheckList
                items={process.documentation.items.slice(0, Math.ceil(process.documentation.items.length / 2))}
              />
              <CheckList
                items={process.documentation.items.slice(Math.ceil(process.documentation.items.length / 2))}
              />
            </div>
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="raised">
        <Container>
          <div className="grid gap-10 lg:grid-cols-[0.7fr_1.3fr]">
            <SectionHeading eyebrow={home.faq.eyebrow} heading={home.faq.heading} />
            <Accordion items={process.faq} />
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

/* ========================================================================== */
/* Technologies                                                               */
/* ========================================================================== */

export function TechnologiesPage() {
  const content = useContent();
  const locale = useLocale();
  const ui = useUi();
  const { technologies, nav, home } = content;

  return (
    <>
      <Seo
        title={technologies.seo.title}
        description={technologies.seo.description}
        path={ROUTES.technologies}
        jsonLd={[
          webPage(locale, ROUTES.technologies, technologies.seo.title, technologies.seo.description),
          breadcrumbs(locale, [
            { label: ui.breadcrumbHome, path: ROUTES.home },
            { label: nav.technologies, path: ROUTES.technologies },
          ]),
          faqPage(technologies.faq),
        ]}
      />
      <PageHero content={technologies.hero} crumbs={[{ label: nav.technologies }]} icon="cpu" />

      <Section scheme="light">
        <Container>
          <div className="flex flex-col gap-8">
            <SectionHeading heading={technologies.intro.heading} className="max-w-none" />
            <Reveal delay={80} className="flex max-w-3xl flex-col gap-5">
              {technologies.intro.paragraphs.map((paragraph) => (
                <p key={paragraph} className="text-body-md text-content-secondary">
                  {paragraph}
                </p>
              ))}
            </Reveal>
            <Reveal delay={120}>
              <div className="flex gap-4 rounded-lg border border-warning/25 bg-warning/5 p-6">
                <Icon name="badge-check" className="mt-0.5 h-5 w-5 shrink-0 text-warning" />
                <p className="text-body-sm text-content-secondary">{technologies.disclaimer}</p>
              </div>
            </Reveal>
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="raised">
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading
              heading={technologies.diagram.heading}
              description={technologies.diagram.description}
            />
            <Reveal>
              <LayerDiagram layers={technologies.diagram.layers} alt={technologies.diagram.alt} />
            </Reveal>
          </div>
        </Container>
      </Section>

      <Section scheme="light">
        <Container>
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {technologies.domains.map((domain, index) => (
              <Reveal key={domain.title} delay={index * 50} className="h-full">
                <div className="flex h-full flex-col gap-4 rounded-lg border border-line/10 bg-surface-1/50 p-6">
                  <span className="icon-badge inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md">
                    <Icon name={domain.icon} className="h-5 w-5" />
                  </span>
                  <div className="flex flex-col gap-2">
                    <h2 className="text-h5 font-semibold text-content-primary">{domain.title}</h2>
                    <p className="text-body-sm text-content-tertiary">{domain.description}</p>
                  </div>
                  <TagList items={domain.items} className="mt-1" />
                </div>
              </Reveal>
            ))}
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="accent" grid>
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={technologies.selection.heading} description={technologies.selection.intro} />
            <FeatureGrid items={technologies.selection.items} columns={4} />
          </div>
        </Container>
      </Section>

      <Section scheme="light">
        <Container>
          <div className="flex flex-col gap-9">
            <SectionHeading heading={technologies.standards.heading} description={technologies.standards.intro} />
            <GroupGrid groups={technologies.standards.groups} columns={3} />
          </div>
        </Container>
      </Section>

      <Section scheme="light" tone="raised">
        <Container>
          <div className="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
            <SectionHeading eyebrow={home.faq.eyebrow} heading={home.faq.heading} />
            <Accordion items={technologies.faq} />
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

/* ========================================================================== */
/* FAQ                                                                        */
/* ========================================================================== */

export function FaqPage() {
  const content = useContent();
  const locale = useLocale();
  const ui = useUi();
  const { faq, nav, home } = content;
  const allItems = faq.categories.flatMap((category) => category.items);

  return (
    <>
      <Seo
        title={faq.seo.title}
        description={faq.seo.description}
        path={ROUTES.faq}
        jsonLd={[
          webPage(locale, ROUTES.faq, faq.seo.title, faq.seo.description),
          breadcrumbs(locale, [
            { label: ui.breadcrumbHome, path: ROUTES.home },
            { label: nav.faq, path: ROUTES.faq },
          ]),
          faqPage(allItems),
        ]}
      />
      <PageHero content={faq.hero} crumbs={[{ label: nav.faq }]} icon="list-checks" />

      {faq.categories.map((category, index) => (
        <Section key={category.title} scheme="light" tone={index % 2 === 0 ? 'base' : 'raised'}>
          <Container>
            <div className="grid gap-10 lg:grid-cols-[0.7fr_1.3fr]">
              <SectionHeading eyebrow={`0${index + 1}`} heading={category.title} />
              <Accordion items={category.items} defaultOpen={null} />
            </div>
          </Container>
        </Section>
      ))}

      <CtaSection
        eyebrow={home.faq.eyebrow}
        heading={faq.cta.heading}
        description={faq.cta.description}
        primaryLabel={faq.cta.action}
        secondaryLabel={home.cta.secondary}
        points={home.cta.points}
      />
    </>
  );
}

/* ========================================================================== */
/* Contact                                                                    */
/* ========================================================================== */

export function ContactPage() {
  const content = useContent();
  const locale = useLocale();
  const ui = useUi();
  const { contact, nav } = content;

  const channels = [
    { icon: 'map-pin' as const, label: ui.address, value: addressLine(locale), href: MAP_URL, external: true },
    { icon: 'phone' as const, label: ui.callUs, value: COMPANY.phoneDisplay, href: TEL_URL },
    { icon: 'mail' as const, label: ui.emailSales, value: COMPANY.emailSales, href: MAILTO_SALES },
    { icon: 'headset' as const, label: ui.emailSupport, value: COMPANY.emailSupport, href: MAILTO_SUPPORT },
    { icon: 'link' as const, label: ui.whatsapp, value: COMPANY.phoneDisplay, href: WHATSAPP_URL, external: true },
  ];

  return (
    <>
      <Seo
        title={contact.seo.title}
        description={contact.seo.description}
        path={ROUTES.contact}
        jsonLd={[
          localBusiness(locale, contact.seo.description),
          webPage(locale, ROUTES.contact, contact.seo.title, contact.seo.description),
          breadcrumbs(locale, [
            { label: ui.breadcrumbHome, path: ROUTES.home },
            { label: nav.contact, path: ROUTES.contact },
          ]),
        ]}
      />
      <PageHero content={contact.hero} crumbs={[{ label: nav.contact }]} icon="mail" />

      <Section scheme="light" grid>
        <Container>
          <div className="grid gap-12 lg:grid-cols-[1.1fr_0.9fr] lg:gap-16">
            <div className="flex flex-col gap-8">
              <SectionHeading
                heading={contact.formHeading}
                description={contact.formDescription}
                className="max-w-none"
              />
              <Reveal delay={60}>
                <div className="rounded-lg border border-line/10 bg-surface-1/50 p-6 sm:p-8">
                  <ContactForm />
                </div>
              </Reveal>
            </div>

            <div className="flex flex-col gap-10">
              <div className="flex flex-col gap-5">
                <SectionHeading
                  heading={contact.channels.heading}
                  description={contact.channels.description}
                  className="max-w-none"
                />
                <ul className="flex flex-col gap-3">
                  {channels.map((channel, index) => (
                    <Reveal key={channel.label} as="li" delay={index * 50}>
                      <a
                        href={channel.href}
                        {...(channel.external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
                        className="group flex items-center gap-4 rounded-lg border border-line/10 bg-surface-1/50 p-4 transition-colors duration-base hover:border-primary-400/40 hover:bg-surface-2/50"
                      >
                        <span className="icon-badge inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md">
                          <Icon name={channel.icon} className="h-5 w-5" />
                        </span>
                        <span className="flex flex-col">
                          <span className="text-caption uppercase tracking-[0.1em] text-content-tertiary">
                            {channel.label}
                          </span>
                          <span className="text-body-md font-medium text-content-primary">
                            {channel.value}
                          </span>
                        </span>
                        <Icon
                          name="arrow-up-right"
                          className="ml-auto h-4 w-4 text-content-tertiary transition-transform duration-base group-hover:translate-x-0.5"
                        />
                      </a>
                    </Reveal>
                  ))}
                </ul>
              </div>

              <Reveal>
                <div className="flex flex-col gap-4 rounded-lg border border-line/10 bg-surface-1/50 p-6">
                  <h2 className="text-h6 font-semibold text-content-primary">{contact.hours.heading}</h2>
                  <ul className="flex flex-col gap-1.5">
                    {contact.hours.lines.map((line) => (
                      <li key={line} className="flex items-center gap-2.5 text-body-sm text-content-secondary">
                        <Icon name="clock" className="h-4 w-4 text-accent-400" />
                        {line}
                      </li>
                    ))}
                  </ul>
                  <p className="text-caption text-content-tertiary">{contact.hours.note}</p>
                </div>
              </Reveal>

              <div className="flex flex-col gap-5">
                <h2 className="text-h4 font-semibold text-content-primary">
                  {contact.expectations.heading}
                </h2>
                <ol className="flex flex-col gap-4">
                  {contact.expectations.items.map((item, index) => (
                    <Reveal key={item.title} as="li" delay={index * 60} className="flex gap-4">
                      <span className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-primary-400/30 bg-surface-2/70 text-label font-bold text-link">
                        {index + 1}
                      </span>
                      <span className="flex flex-col gap-1">
                        <span className="text-body-md font-semibold text-content-primary">{item.title}</span>
                        <span className="text-body-sm text-content-tertiary">{item.description}</span>
                      </span>
                    </Reveal>
                  ))}
                </ol>
              </div>
            </div>
          </div>
        </Container>
      </Section>
    </>
  );
}

/* ========================================================================== */
/* Legal                                                                      */
/* ========================================================================== */

export function LegalPage({ slug }: { slug: string }) {
  const content = useContent();
  const locale = useLocale();
  const page = content.legal.find((item) => item.slug === slug);
  if (!page) return <NotFoundPage />;

  const route = ROUTES.legal(page.slug);

  return (
    <>
      <Seo
        title={page.seo.title}
        description={page.seo.description}
        path={route}
        jsonLd={[
          webPage(locale, route, page.seo.title, page.seo.description),
          breadcrumbs(locale, [
            { label: content.ui.breadcrumbHome, path: ROUTES.home },
            { label: page.title, path: route },
          ]),
        ]}
      />
      <div className="pb-section pt-[calc(var(--header-h)+3rem)]">
        <Container>
          <article className="mx-auto flex max-w-3xl flex-col gap-8">
            <Breadcrumbs items={[{ label: page.title }]} />
            <header className="flex flex-col gap-3">
              <h1 className="text-h1 font-semibold text-content-primary">{page.title}</h1>
              <p className="text-caption uppercase tracking-[0.1em] text-content-tertiary">
                {page.updated}
              </p>
              <p className="text-body-lg text-content-secondary">{page.intro}</p>
            </header>
            <div className="rule-gradient" />
            <div className="flex flex-col gap-10">
              {page.sections.map((section) => (
                <Reveal key={section.heading} className="flex flex-col gap-4">
                  <h2 className="text-h4 font-semibold text-content-primary">{section.heading}</h2>
                  {section.paragraphs.map((paragraph) => (
                    <p key={paragraph} className="text-body-md text-content-secondary">
                      {paragraph}
                    </p>
                  ))}
                  {section.bullets && (
                    <ul className="flex flex-col gap-2">
                      {section.bullets.map((bullet) => (
                        <li key={bullet} className="flex items-start gap-2.5 text-body-md text-content-secondary">
                          <span
                            className="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-accent-400/70"
                            aria-hidden="true"
                          />
                          {bullet}
                        </li>
                      ))}
                    </ul>
                  )}
                </Reveal>
              ))}
            </div>
          </article>
        </Container>
      </div>
    </>
  );
}

/* ========================================================================== */
/* 404                                                                        */
/* ========================================================================== */

export function NotFoundPage() {
  const content = useContent();
  const ui = useUi();
  const path = useLocalePath();

  return (
    <>
      {/* No canonical and no alternates — a 404 is not a destination, and
          pointing crawlers at one only invites it into the index. The status
          code leads the title so a tab full of open pages stays scannable. */}
      <Seo title={`404 — ${ui.notFoundTitle}`} description={ui.notFoundText} path="/404" noindex />
      <div className="flex min-h-[70vh] items-center pt-[calc(var(--header-h)+2rem)]">
        <Container>
          <div className="mx-auto flex max-w-xl flex-col items-start gap-6">
            <span className="text-display-xl font-extrabold leading-none text-gradient-brand">404</span>
            <h1 className="text-h2 font-semibold text-content-primary">{ui.notFoundTitle}</h1>
            <p className="text-body-lg text-content-secondary">{ui.notFoundText}</p>
            <div className="flex flex-wrap gap-3">
              <Button to={path(ROUTES.home)} icon="arrow-right">
                {ui.goHome}
              </Button>
              <Button to={path(ROUTES.services)} variant="secondary">
                {ui.allServices}
              </Button>
            </div>
            <nav aria-label={content.nav.services} className="mt-4 flex flex-wrap gap-2">
              {content.services.slice(0, 6).map((service) => (
                <Link
                  key={service.slug}
                  to={path(ROUTES.service(service.slug))}
                  className="rounded-sm border border-line/10 bg-surface-1/60 px-3 py-1.5 text-caption text-content-tertiary transition-colors duration-fast hover:text-content-primary"
                >
                  {service.name}
                </Link>
              ))}
            </nav>
          </div>
        </Container>
      </div>
    </>
  );
}
