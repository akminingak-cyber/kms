# Contact form — diagnosis and changes

## What was verified

The built site in `dist/` was served locally under a replica of the production
Apache config (the routing rules and the exact `Content-Security-Policy` header
from `dist/.htaccess`) and the contact form was driven in a real Chromium
browser, with the Web3Forms endpoint intercepted so the outgoing request could
be inspected.

Result: **the front-end is not the problem.** On a valid submission the form
sends exactly one request:

```
POST https://api.web3forms.com/submit
{"access_key":"2720eb8f-…","subject":"KMS — website enquiry (…)","from_name":"…",
 "replyto":"…","name":"…","company":"…","email":"…","phone":"…","service":"…","message":"…"}
```

with no JavaScript errors and no CSP violations — `connect-src` already allows
`https://api.web3forms.com`. The success and failure branches both render
correctly, and the pre-compressed `.br` / `.gz` variants that `.htaccess` serves
in preference to the `.js` decompress to byte-identical content, so the browser
is not being handed a stale bundle.

Since the request leaves the page correctly, a missing email can only come from
the delivery side: the access key, the inbox it is registered to, or a build
other than this one being live. The old code could not tell those apart, because
it discarded the API's answer.

## Changes

`dist/assets/index-*.js`:

1. **The real failure reason is now captured and shown.** The old transport
   collapsed every outcome to `true`/`false`, so `Invalid Access Key`, a spam
   block, and an offline browser all produced the same generic "could not send"
   panel. The HTTP status and the Web3Forms `message` are now displayed under
   that panel and logged to the console under `[KMS contact]`. Non-JSON error
   bodies and network failures are handled without throwing.
2. **`botcheck` is no longer sent.** The payload always carried `botcheck: ""`.
   The honeypot still works — it is checked client-side before the request — but
   the empty field is no longer submitted to the spam filter.
3. **A 20-second timeout** on the request, so a hanging connection reports an
   error instead of leaving the button spinning forever.

The patched bundle was re-hashed (`index-QxxBkaAJ.js` → `index-d8O93NAw.js`),
its `.br` / `.gz` variants regenerated, and the reference updated in all 57
prerendered pages. This rename matters: `.htaccess` serves `/assets/*` with
`Cache-Control: immutable, max-age=31536000`, so a changed file under an
unchanged name would never be re-fetched by a browser that already has it.

## What to check next

1. Open `tools/web3forms-check.html` in a browser. It posts one test submission
   straight to the API and prints the raw response, which settles whether the
   key itself works. It is a local tool — do not upload it to the server.
2. If it reports `success: true` but no email arrives, the key is fine and the
   problem is delivery: check spam, and confirm on web3forms.com which inbox the
   key is registered to.
3. If it reports `Invalid Access Key`, issue a new key and rebuild with it.

## Note for whoever holds the source

This repository contains no `src/`, so the fix above was applied to the built
bundle. Re-running `vite build` from the real source will overwrite it. The
equivalent source change is in `submitContactForm`: return the API's
`message` and HTTP status instead of a bare boolean, drop `botcheck` from the
request body, and render the reason in the error panel.
