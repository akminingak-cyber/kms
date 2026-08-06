# KMS

საწარმოო IT ინფრასტრუქტურისა და კიბერუსაფრთხოების კომპანიის ვებსაიტი.

## Stack

Vite · React 18 · TypeScript · Tailwind CSS 3

ფონტები თვითმასპინძლებულია (`@fontsource-variable/noto-sans-georgian`, `@fontsource-variable/inter`) —
Google Fonts-ზე გარე მოთხოვნა არ ხდება. ყველა ილუსტრაცია CSS/SVG-ითაა აწყობილი,
ამიტომ გარე სურათების ჩატვირთვა არ სჭირდება.

## Commands

```bash
npm install
npm run dev        # dev server
npm run build      # production build → dist/
npm run preview    # serve the build on :4173
npm run typecheck  # tsc --noEmit
npm run lint       # eslint
npm run shots      # design-preview/ სქრინშოთები (preview server უნდა იყოს გაშვებული)
```

## კონტენტი

საიტის **მთელი ტექსტი** ერთ ფაილშია: `src/content/site.ts`.
კომპონენტები მხოლოდ ამ ფაილიდან კითხულობენ, ამიტომ ტექსტის შეცვლა
სხვა ფაილს არ ეხება.

> ⚠️ **ამჟამინდელი ტექსტი placeholder-ია.** ის ჯერ არ არის kms.ge-ის რეალური
> კონტენტი — ამ სამუშაო გარემოს ქსელის პოლიტიკამ kms.ge-ზე წვდომა დაბლოკა
> (proxy: `403 CONNECT kms.ge:443`), ამიტომ ორიგინალი ტექსტის წამოღება ვერ მოხერხდა.
> დიზაინი და სტრუქტურა საბოლოოა; რეალურ კონტენტზე გადასვლა მხოლოდ
> `src/content/site.ts`-ის ჩანაცვლებას მოითხოვს.
>
> განსაკუთრებით შესამოწმებელი placeholder-ები: საკონტაქტო მონაცემები
> (ტელეფონი, ელ. ფოსტა, მისამართი), სტატისტიკა, პროექტების ციფრები, სერტიფიკატები.

## სტრუქტურა

```
src/
├─ content/site.ts        ყველა ტექსტი — ერთადერთი ფაილი რედაქტირებისთვის
├─ lib/hooks.ts           scroll-reveal, count-up, sticky-nav ჰუკები
├─ components/
│  ├─ Visuals.tsx         გენერაციული SVG/CSS გრაფიკა და აიკონები
│  ├─ Nav.tsx             sticky glass ნავიგაცია + მობილური მენიუ
│  ├─ Hero.tsx            hero, სტატისტიკა, პარტნიორების marquee
│  ├─ Sections.tsx        სერვისები, გადაწყვეტები, პროცესი, პროექტები, About, CTA
│  ├─ Contact.tsx         საკონტაქტო ბლოკი და ფორმა
│  └─ Footer.tsx
└─ App.tsx
```

## რაც ჯერ დასაკავშირებელია

საკონტაქტო ფორმა ამჟამად front-end-ია — `onSubmit` მხოლოდ წარმატების მდგომარეობას
აჩვენებს და არსად აგზავნის. `@supabase/supabase-js` უკვე დამოკიდებულებებშია;
ფორმის რეალურ backend-თან დაკავშირება `src/components/Contact.tsx`-ში ხდება.
