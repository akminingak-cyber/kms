import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';

import { App } from './App.js';
import { SessionProvider } from './api/SessionProvider.js';
import './styles.css';

const root = document.getElementById('root');

if (root === null) {
  throw new Error('The application root element is missing from index.html.');
}

createRoot(root).render(
  <StrictMode>
    <BrowserRouter>
      <SessionProvider>
        <App />
      </SessionProvider>
    </BrowserRouter>
  </StrictMode>,
);
