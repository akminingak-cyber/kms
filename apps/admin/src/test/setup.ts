import '@testing-library/react';

/*
 * `crypto.randomUUID` is used by the API client to originate a correlation id
 * per request. jsdom does not provide it in every environment, so it is
 * supplied here rather than being made conditional in production code — a
 * transport that sometimes omits a correlation id is a transport whose
 * failures are sometimes untraceable.
 */
if (typeof globalThis.crypto?.randomUUID !== 'function') {
  Object.defineProperty(globalThis, 'crypto', {
    value: {
      ...globalThis.crypto,
      randomUUID: () => '00000000-0000-4000-8000-000000000000',
    },
  });
}
