// Generates the TypeScript types for every API surface from its OpenAPI
// contract.
//
// The output is **committed**, so that diffs are reviewable and builds are
// reproducible, and it is **never hand-edited**. `--check` regenerates into
// memory and fails if the result differs from what is on disk, which is what
// CI runs: a spec change that nobody regenerated for is a client that compiles
// against an API that no longer exists.
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

import openapiTS, { astToString } from 'openapi-typescript';

const here = dirname(fileURLToPath(import.meta.url));
const contracts = resolve(here, '../../api-contracts');
const outputDir = resolve(here, '../src/types');

const SURFACES = [
  { name: 'client', spec: 'client/v1/openapi.yaml' },
  { name: 'admin', spec: 'admin/v1/openapi.yaml' },
];

const BANNER = (surface, spec) =>
  `/**\n` +
  ` * GENERATED FILE — DO NOT EDIT.\n` +
  ` *\n` +
  ` * Source: packages/api-contracts/${spec}\n` +
  ` * Regenerate: pnpm contracts:generate\n` +
  ` *\n` +
  ` * The contract is the source of truth (ADR-0010). Editing this file makes the\n` +
  ` * client disagree with the server in a way no test can see, because both sides\n` +
  ` * would still compile.\n` +
  ` */\n\n`;

const checkOnly = process.argv.includes('--check');
let drifted = false;

await mkdir(outputDir, { recursive: true });

for (const { name, spec } of SURFACES) {
  const source = new URL(`file://${join(contracts, spec)}`);
  const generated = BANNER(name, spec) + astToString(await openapiTS(source));
  const target = join(outputDir, `${name}.ts`);

  if (checkOnly) {
    const existing = await readFile(target, 'utf8').catch(() => null);

    if (existing !== generated) {
      drifted = true;
      console.error(
        `${name}: generated types differ from ${target}.\n` +
          '  The contract changed and the client was not regenerated. Run: pnpm contracts:generate',
      );
    }

    continue;
  }

  await writeFile(target, generated, 'utf8');
  console.log(`${name}: wrote ${target}`);
}

if (drifted) {
  process.exit(1);
}

if (checkOnly) {
  console.log('Generated types are up to date with the contracts.');
}
