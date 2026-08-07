import { useId, useState, type FormEvent, type ReactNode } from 'react';
import { COMPANY, FORM, FORM_ENABLED } from '../../config/site';
import { useContent, useUi } from '../../i18n/LocaleContext';
import { Icon } from '../ui/Icon';
import { Button } from '../ui/primitives';

interface FormState {
  name: string;
  company: string;
  email: string;
  phone: string;
  service: string;
  serviceLabel: string;
  message: string;
  /** Honeypot. A real person never sees it, so anything in it is a bot. */
  botcheck: string;
}

const EMPTY: FormState = {
  name: '',
  company: '',
  email: '',
  phone: '',
  service: '',
  serviceLabel: '',
  message: '',
  botcheck: '',
};

const FIELD_CLASS =
  'field w-full rounded-md border px-4 py-3 text-body-sm text-content-primary placeholder:text-content-tertiary/90 transition-colors duration-fast focus:border-primary-400/60 focus:outline-none focus:ring-2 focus:ring-primary-500/25';

function mailtoFallback(state: FormState): string {
  const subject = `Website enquiry — ${state.serviceLabel || state.company || state.name}`;
  const body = [
    `Name: ${state.name}`,
    `Company: ${state.company}`,
    `Email: ${state.email}`,
    `Phone: ${state.phone}`,
    `Service: ${state.serviceLabel}`,
    '',
    state.message,
  ].join('\n');
  return `mailto:${COMPANY.emailSales}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
}

async function submit(state: FormState): Promise<boolean> {
  // Report success to the bot so it does not retry, but send nothing.
  if (state.botcheck) return true;
  if (!FORM_ENABLED) return false;

  const response = await fetch(FORM.endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({
      access_key: FORM.accessKey,
      subject: `KMS — website enquiry (${state.serviceLabel || 'general'})`,
      from_name: state.name || COMPANY.name,
      replyto: state.email,
      name: state.name,
      company: state.company,
      email: state.email,
      phone: state.phone,
      service: state.serviceLabel,
      message: state.message,
      botcheck: '',
    }),
  });

  if (!response.ok) return false;
  const result: unknown = await response.json();
  return typeof result === 'object' && result !== null && (result as { success?: boolean }).success === true;
}

/**
 * A labelled control with its error message wired up through
 * aria-describedby — without that link a screen reader announces the field as
 * invalid but never reads why.
 */
function Field({
  id,
  label,
  error,
  required = false,
  children,
}: {
  id: string;
  label: string;
  error?: string;
  required?: boolean;
  children: (describedBy: string | undefined) => ReactNode;
}) {
  const errorId = `${id}-error`;
  return (
    <div className="flex flex-col gap-2">
      <label htmlFor={id} className="text-label font-medium text-content-secondary">
        {label}
        {required && (
          <span className="ml-1 text-accent-400" aria-hidden="true">
            *
          </span>
        )}
      </label>
      {children(error ? errorId : undefined)}
      {error && (
        <p id={errorId} className="text-caption text-error">
          {error}
        </p>
      )}
    </div>
  );
}

export function ContactForm() {
  const content = useContent();
  const ui = useUi();
  const copy = ui.form;
  const baseId = useId();

  const [values, setValues] = useState<FormState>(EMPTY);
  const [consent, setConsent] = useState(false);
  const [errors, setErrors] = useState<Partial<Record<keyof FormState | 'consent', string>>>({});
  const [status, setStatus] = useState<'idle' | 'sending' | 'success' | 'error'>('idle');

  const fieldId = (name: string) => `${baseId}-${name}`;
  const update = (key: keyof FormState, value: string) =>
    setValues((prev) => ({ ...prev, [key]: value }));

  function validate() {
    const next: Partial<Record<keyof FormState | 'consent', string>> = {};
    if (!values.name.trim()) next.name = copy.required;
    if (!values.email.trim()) next.email = copy.required;
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(values.email.trim())) next.email = copy.invalidEmail;
    if (!values.message.trim()) next.message = copy.required;
    else if (values.message.trim().length < 20) next.message = copy.tooShort;
    if (!consent) next.consent = copy.required;
    return next;
  }

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    const found = validate();
    setErrors(found);
    if (Object.keys(found).length > 0) {
      // Move the reader to the first thing that needs fixing.
      const firstKey = Object.keys(found)[0];
      document.getElementById(fieldId(firstKey))?.focus();
      return;
    }

    if (!FORM_ENABLED) {
      window.location.href = mailtoFallback(values);
      return;
    }

    setStatus('sending');
    try {
      if (await submit(values)) {
        setStatus('success');
        setValues(EMPTY);
        setConsent(false);
      } else {
        setStatus('error');
      }
    } catch {
      setStatus('error');
    }
  }

  if (status === 'success') {
    return (
      <div
        role="status"
        aria-live="polite"
        className="flex flex-col items-start gap-4 rounded-lg border border-success/30 bg-success/5 p-8"
      >
        <span className="inline-flex h-12 w-12 items-center justify-center rounded-md border border-success/30 bg-success/10 text-success">
          <Icon name="check-circle" className="h-6 w-6" />
        </span>
        <h3 className="text-h4 font-semibold text-content-primary">{copy.successTitle}</h3>
        <p className="text-body-sm text-content-secondary">{copy.successText}</p>
      </div>
    );
  }

  return (
    <form onSubmit={onSubmit} noValidate className="flex flex-col gap-5">
      {/* Submission state is announced rather than only shown. `polite` waits
          for the reader to finish its current sentence. */}
      <p className="sr-only" role="status" aria-live="polite">
        {status === 'sending' ? copy.submitting : status === 'error' ? copy.errorTitle : ''}
      </p>

      {!FORM_ENABLED && (
        <p className="flex items-start gap-3 rounded-md border border-warning/25 bg-warning/5 p-4 text-body-sm text-content-secondary">
          <Icon name="alert-triangle" className="mt-0.5 h-4 w-4 shrink-0 text-warning" />
          {copy.fallbackNotice}
        </p>
      )}

      <div className="grid gap-5 sm:grid-cols-2">
        <Field id={fieldId('name')} label={copy.name} error={errors.name} required>
          {(describedBy) => (
            <input
              id={fieldId('name')}
              name="name"
              type="text"
              autoComplete="name"
              required
              aria-required="true"
              aria-invalid={errors.name ? true : undefined}
              aria-describedby={describedBy}
              className={FIELD_CLASS}
              value={values.name}
              onChange={(event) => update('name', event.target.value)}
            />
          )}
        </Field>

        <Field id={fieldId('company')} label={copy.company} error={errors.company}>
          {(describedBy) => (
            <input
              id={fieldId('company')}
              name="company"
              type="text"
              autoComplete="organization"
              aria-describedby={describedBy}
              className={FIELD_CLASS}
              value={values.company}
              onChange={(event) => update('company', event.target.value)}
            />
          )}
        </Field>

        <Field id={fieldId('email')} label={copy.email} error={errors.email} required>
          {(describedBy) => (
            <input
              id={fieldId('email')}
              name="email"
              type="email"
              inputMode="email"
              autoComplete="email"
              required
              aria-required="true"
              aria-invalid={errors.email ? true : undefined}
              aria-describedby={describedBy}
              className={FIELD_CLASS}
              value={values.email}
              onChange={(event) => update('email', event.target.value)}
            />
          )}
        </Field>

        <Field id={fieldId('phone')} label={copy.phone} error={errors.phone}>
          {(describedBy) => (
            <input
              id={fieldId('phone')}
              name="phone"
              type="tel"
              inputMode="tel"
              autoComplete="tel"
              aria-describedby={describedBy}
              className={FIELD_CLASS}
              value={values.phone}
              onChange={(event) => update('phone', event.target.value)}
            />
          )}
        </Field>
      </div>

      <Field id={fieldId('service')} label={copy.service} error={errors.service}>
        {(describedBy) => (
          <select
            id={fieldId('service')}
            name="service"
            aria-describedby={describedBy}
            className={FIELD_CLASS}
            value={values.service}
            onChange={(event) => {
              const option = content.contact.serviceOptions.find(
                (item) => item.value === event.target.value,
              );
              setValues((prev) => ({
                ...prev,
                service: event.target.value,
                serviceLabel: option?.label ?? '',
              }));
            }}
          >
            <option value="">{copy.servicePlaceholder}</option>
            {content.contact.serviceOptions.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        )}
      </Field>

      <Field id={fieldId('message')} label={copy.message} error={errors.message} required>
        {(describedBy) => (
          <textarea
            id={fieldId('message')}
            name="message"
            rows={6}
            required
            aria-required="true"
            aria-invalid={errors.message ? true : undefined}
            aria-describedby={describedBy}
            placeholder={copy.messagePlaceholder}
            className={FIELD_CLASS}
            value={values.message}
            onChange={(event) => update('message', event.target.value)}
          />
        )}
      </Field>

      {/* Honeypot. Hidden from sight and from assistive tech, and skipped by
          Tab, so only an automated filler ever reaches it. */}
      <div className="hidden" aria-hidden="true">
        <label htmlFor={fieldId('botcheck')}>Leave this field empty</label>
        <input
          id={fieldId('botcheck')}
          name="botcheck"
          type="text"
          tabIndex={-1}
          autoComplete="off"
          value={values.botcheck}
          onChange={(event) => update('botcheck', event.target.value)}
        />
      </div>

      <div className="flex flex-col gap-2">
        <label htmlFor={fieldId('consent')} className="flex items-start gap-3 text-body-sm text-content-secondary">
          <input
            id={fieldId('consent')}
            type="checkbox"
            checked={consent}
            onChange={(event) => setConsent(event.target.checked)}
            required
            aria-required="true"
            aria-invalid={errors.consent ? true : undefined}
            aria-describedby={errors.consent ? `${fieldId('consent')}-error` : undefined}
            className="mt-1 h-4 w-4 shrink-0 rounded-sm border-line/20 bg-surface-2 accent-primary-500"
          />
          <span>{copy.consent}</span>
        </label>
        {errors.consent && (
          <p id={`${fieldId('consent')}-error`} className="text-caption text-error">
            {errors.consent}
          </p>
        )}
      </div>

      {status === 'error' && (
        <div className="flex flex-col gap-3 rounded-md border border-error/30 bg-error/5 p-4">
          <p className="flex items-center gap-2.5 text-body-sm font-semibold text-content-primary">
            <Icon name="alert-triangle" className="h-4 w-4 shrink-0 text-error" />
            {copy.errorTitle}
          </p>
          <p className="text-body-sm text-content-secondary">{copy.errorText}</p>
          <a href={mailtoFallback(values)} className="text-body-sm font-medium text-link underline">
            {copy.fallbackAction}
          </a>
        </div>
      )}

      <div>
        <Button type="submit" size="lg" icon="arrow-right" disabled={status === 'sending'}>
          {status === 'sending' ? copy.submitting : copy.submit}
        </Button>
      </div>
    </form>
  );
}
