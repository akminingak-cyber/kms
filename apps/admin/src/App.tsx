import { useEffect } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';

import { useSession } from './api/SessionProvider.js';
import { SignIn } from './auth/SignIn.js';
import { Layout } from './components/Layout.js';
import { navigationFor } from './navigation.js';
import { Audit } from './screens/Audit.js';
import { Categories } from './screens/Categories.js';
import { Channels } from './screens/Channels.js';
import { Decisions } from './screens/Decisions.js';
import { Media } from './screens/Media.js';
import { Plans } from './screens/Plans.js';
import { Rights } from './screens/Rights.js';
import { Sessions } from './screens/Sessions.js';
import { Staff } from './screens/Staff.js';

/**
 * Routing, and the landing decision.
 *
 * There is no dashboard. A dashboard would need numbers, the numbers would need
 * endpoints that count things, and counting rows on a growing table for a
 * screen nobody acts on is a cost with no reader. Operators land on the first
 * section their role can use.
 */
export function App() {
  const { staff } = useSession();

  useEffect(() => {
    document.title = staff === null ? 'Sign in — KMS TV' : 'KMS TV — Operator panel';
  }, [staff]);

  if (staff === null) {
    return <SignIn />;
  }

  const sections = navigationFor(staff.role);
  const landing = sections[0]?.path ?? '/channels';

  return (
    <Layout>
      <Routes>
        <Route path="/" element={<Navigate to={landing} replace />} />
        <Route path="/channels" element={<Channels />} />
        <Route path="/categories" element={<Categories />} />
        <Route path="/plans" element={<Plans />} />
        <Route path="/rights" element={<Rights />} />
        <Route path="/media" element={<Media />} />
        <Route path="/decisions" element={<Decisions />} />
        <Route path="/sessions" element={<Sessions />} />
        <Route path="/audit" element={<Audit />} />
        <Route path="/staff" element={<Staff />} />
        {/*
          Unknown paths land on the caller's first section rather than showing a
          "not found" page. Every route here is enforced server-side anyway, so
          a client-side gate would add a second place for the rules to live and
          a second place for them to be wrong.
        */}
        <Route path="*" element={<Navigate to={landing} replace />} />
      </Routes>
    </Layout>
  );
}
