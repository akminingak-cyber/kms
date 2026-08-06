import Nav from './components/Nav';
import Hero from './components/Hero';
import { About, CtaBand, Process, Services, Solutions, Testimonials, Work } from './components/Sections';
import Contact from './components/Contact';
import Footer from './components/Footer';

export default function App() {
  return (
    <>
      <a
        href="#services"
        className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-brand-400 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-ink-950"
      >
        მთავარ კონტენტზე გადასვლა
      </a>

      <Nav />

      <main>
        <Hero />
        <Services />
        <Solutions />
        <Process />
        <Work />
        <About />
        <Testimonials />
        <CtaBand />
        <Contact />
      </main>

      <Footer />
    </>
  );
}
