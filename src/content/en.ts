import type { LocaleContent } from './types';

/**
 * EN site content.
 *
 * Every user-facing string on the site lives here — nothing is hard-coded in a
 * component. Editing copy means editing this file and nothing else.
 */
export const en: LocaleContent = {
  ui: {
    brandTagline: 'Infrastructure, security, automation',
    skipToContent: 'Skip to main content',
    menu: 'Menu',
    close: 'Close',
    language: 'Language',
    languageNote: 'Russian and Spanish in preparation',
    allServices: 'All services',
    allIndustries: 'All industries',
    readMore: 'Read more',
    learnMore: 'Learn more',
    viewAll: 'View all',
    backTo: 'Back to',
    relatedServices: 'Related services',
    relatedIndustries: 'Where it applies',
    onThisPage: 'On this page',
    breadcrumbHome: 'Home',
    breadcrumbNav: 'Breadcrumb',
    ctaPrimary: 'Discuss your project',
    ctaSecondary: 'Explore services',
    callUs: 'Call us',
    emailSales: 'Sales',
    emailSupport: 'Support',
    whatsapp: 'WhatsApp',
    address: 'Address',
    viewOnMap: 'View on map',
    notFoundTitle: 'Page not found',
    notFoundText: 'The address may have changed or been mistyped. Return to the homepage or browse our services.',
    goHome: 'Back to homepage',
    projectKind: {
      'reference-architecture': 'Reference architecture',
      'concept-design': 'Concept design',
      delivered: 'Delivered project'
    },
    form: {
      name: 'Full name',
      company: 'Company or site',
      email: 'Email',
      phone: 'Phone',
      service: 'Which area are you interested in',
      servicePlaceholder: 'Select an area',
      message: 'Project description',
      messagePlaceholder: 'Briefly describe the site, floor area, the systems you need and your timeline — this helps us prepare a precise answer.',
      consent: 'I agree that the information provided will be used only to respond to my enquiry.',
      submit: 'Send enquiry',
      submitting: 'Sending...',
      successTitle: 'Enquiry received',
      successText: 'Thank you. We will review your enquiry and get back to you during working hours.',
      errorTitle: 'Could not send',
      errorText: 'Please try again or email us directly.',
      fallbackNotice: 'Online submission is not configured yet — your enquiry will open in your email client.',
      fallbackAction: 'Send by email',
      required: 'This field is required',
      invalidEmail: 'Enter a valid email address',
      tooShort: 'Please add a little more detail',
      statusLabel: 'Form status'
    }
  },
  nav: {
    home: 'Home',
    about: 'About',
    services: 'Services',
    industries: 'Industries',
    portfolio: 'Projects',
    process: 'Process',
    technologies: 'Technologies',
    faq: 'FAQ',
    contact: 'Contact'
  },
  footer: {
    description: 'KMS is a systems integrator for IT infrastructure and smart technologies. We design, install and integrate network, security and automation systems — from the first site survey through handover and ongoing support.',
    columns: [
      {
        title: 'Company',
        links: [
          {
            label: 'About us',
            to: '/about'
          },
          {
            label: 'Our process',
            to: '/process'
          },
          {
            label: 'Projects',
            to: '/portfolio'
          },
          {
            label: 'Industries',
            to: '/industries'
          },
          {
            label: 'Contact',
            to: '/contact'
          }
        ]
      },
      {
        title: 'Services',
        links: [
          {
            label: 'IT infrastructure',
            to: '/services/it-infrastructure'
          },
          {
            label: 'Network infrastructure',
            to: '/services/networking'
          },
          {
            label: 'Video surveillance',
            to: '/services/cctv'
          },
          {
            label: 'Access control',
            to: '/services/access-control'
          },
          {
            label: 'Smart home',
            to: '/services/smart-home'
          },
          {
            label: 'Smart building',
            to: '/services/smart-building'
          },
          {
            label: 'Audio visual systems',
            to: '/services/audio-visual'
          },
          {
            label: 'Managed IT services',
            to: '/services/managed-it'
          }
        ]
      },
      {
        title: 'Resources',
        links: [
          {
            label: 'Frequently asked questions',
            to: '/faq'
          },
          {
            label: 'Technologies',
            to: '/technologies'
          },
          {
            label: 'Privacy policy',
            to: '/privacy'
          },
          {
            label: 'Terms of use',
            to: '/terms'
          },
          {
            label: 'Cookies',
            to: '/cookies'
          }
        ]
      }
    ],
    contactTitle: 'Contact',
    legalNote: 'Vendor names mentioned on this site belong to their respective owners and are listed only to describe technology compatibility.',
    copyright: 'All rights reserved.',
    builtNote: 'IT infrastructure and smart systems integrator'
  },
  home: {
    hero: {
      eyebrow: 'Systems integrator',
      headlineLead: 'Infrastructure, security and automation',
      headlineAccent: 'held to one engineering standard',
      description: 'KMS designs, installs and integrates network, security and smart building systems — for offices, clinics, hotels, industrial sites and private residences. One team is accountable from the first site survey through handover and ongoing support.',
      primaryCta: 'Discuss your project',
      secondaryCta: 'Explore services',
      scrollHint: 'Scroll',
      pillars: [
        {
          label: 'Network infrastructure',
          icon: 'network'
        },
        {
          label: 'Physical security',
          icon: 'shield'
        },
        {
          label: 'Smart systems',
          icon: 'house'
        },
        {
          label: 'Managed support',
          icon: 'headset'
        }
      ]
    },
    trust: {
      heading: 'How we work',
      items: [
        {
          title: 'One supplier, full accountability',
          description: 'Network, cabling, video, access control and automation come together in a single project. No more coordinating separate contractors.',
          icon: 'layers'
        },
        {
          title: 'Engineering documentation',
          description: 'Every site is handed over with diagrams, port and device registers, configuration records and warranty documents.',
          icon: 'file-text'
        },
        {
          title: 'Standards-based installation',
          description: 'We work to established international practice for structured cabling and network infrastructure — not to whatever fits on the day.',
          icon: 'ruler'
        },
        {
          title: 'Support after handover',
          description: 'A project does not end when the system powers on. We offer monitoring, preventive maintenance and response under agreed terms.',
          icon: 'refresh-cw'
        }
      ]
    },
    intro: {
      eyebrow: 'Who we are',
      heading: 'An engineering company that builds technology infrastructure end to end',
      paragraphs: [
        'KMS is not a hardware shop or a one-off repair service. We work at the level where a building needs a considered system: where the cable runs, where the switch goes, how the site is covered by cameras, who is allowed through which door, and how all of it is managed from one place.',
        'We serve companies, offices, medical facilities, hotels, industrial sites and residential properties. One of our core directions is equipping houses, villas and offices with smart systems and automation.'
      ],
      points: [
        'Designed around the real needs of the site, not a product catalogue',
        'Systems that integrate with each other rather than merely coexist',
        'Scalable architecture — today’s budget, tomorrow’s growth',
        'Transparent documentation that stays with the building'
      ],
      cta: 'About us'
    },
    services: {
      eyebrow: 'Services',
      heading: 'Eight disciplines, one accountable team',
      description: 'Each discipline stands on its own, but delivers the most when planned together — when network, security and automation are designed as one system from the start.',
      cta: 'All services'
    },
    industries: {
      eyebrow: 'Industries',
      heading: 'Every type of building has its own logic',
      description: 'A clinic, a hotel, a factory and an office block have nothing in common in their requirements. We start with how your building actually operates — and only then choose the technology.',
      cta: 'All industries'
    },
    why: {
      eyebrow: 'Why KMS',
      heading: 'What an engineering approach actually changes',
      description: 'Installed equipment and a working system are two different outcomes. Below is what we treat as standard in every project.',
      items: [
        {
          title: 'Systems that understand each other',
          description: 'Access control links to camera events, alarms link to recorded video, smart scenarios link to sensors. Integration is designed in from the beginning, not bolted on at the end.',
          icon: 'workflow'
        },
        {
          title: 'A measured, documented network',
          description: 'Every line is labelled and tested. On critical sites we produce test records so a fault can still be located years later.',
          icon: 'clipboard-check'
        },
        {
          title: 'Resilience and redundancy',
          description: 'Backup power, secondary links, duplicated critical nodes — to the level the site’s risk profile and budget justify.',
          icon: 'shield-check'
        },
        {
          title: 'Transparent cost',
          description: 'The specification shows what was selected and why. Alternatives are offered openly, with any quality trade-off stated.',
          icon: 'list-checks'
        },
        {
          title: 'Vendor-independent choices',
          description: 'We are not tied to a single brand. Technology is chosen for the task, compatibility and availability of long-term support.',
          icon: 'compass'
        },
        {
          title: 'Designed for the people who run it',
          description: 'A system has to be operable by the staff on site. We build clear interfaces and provide hands-on training at handover.',
          icon: 'users'
        }
      ]
    },
    smart: {
      eyebrow: 'Smart systems',
      heading: 'Automation that makes daily life simpler',
      description: 'For us a smart home is not a light bulb switched on from an app. It is lighting, climate, security, blinds, irrigation and energy brought into one logic — so the building responds automatically to time, occupancy and events.',
      scenarios: [
        {
          title: 'Scenarios that follow the day',
          description: 'Morning, working, evening and away modes reset lighting, climate, blinds and security in a single action.',
          icon: 'sparkles'
        },
        {
          title: 'Energy under control',
          description: 'Heating and cooling follow occupancy and schedule, consumption appears in reports — savings are measured, not assumed.',
          icon: 'gauge'
        },
        {
          title: 'Safety and notifications',
          description: 'Leak, smoke, door and motion sensors are tied to scenarios: water shuts off, lights come on, a notification reaches your phone.',
          icon: 'siren'
        },
        {
          title: 'Multi-zone comfort',
          description: 'Multi-room audio, video zones, blinds and lighting controlled from one panel — wall keypad, phone or voice.',
          icon: 'speaker'
        }
      ],
      cta: 'Smart home services'
    },
    technologies: {
      eyebrow: 'Technologies',
      heading: 'Open standards and compatible platforms',
      description: 'We work with technologies built on industry standards, which is what makes long-term support, expansion and integration with other systems possible.',
      disclaimer: 'Listed below are areas of technology compatibility. Vendor names are given only to describe the ecosystems we work with — they do not indicate official partnership or authorisation.',
      cta: 'Technology ecosystem'
    },
    process: {
      eyebrow: 'Process',
      heading: 'Six stages, no ambiguity',
      description: 'Each stage has a concrete deliverable that you receive. That makes it clear where the project stands and what comes next.',
      cta: 'See how we work'
    },
    projects: {
      eyebrow: 'Projects',
      heading: 'Reference architectures',
      description: 'Below are engineering solutions for typical buildings: which systems come together, in what architecture, and to what end.',
      notice: 'This section contains reference architectures and concept designs that describe our approach. Named client projects are published only with their written consent.',
      cta: 'All projects'
    },
    metrics: {
      eyebrow: 'In brief',
      heading: 'Numbers you can verify on this very site',
      description: 'We do not publish unverified statistics. The figures below reflect the actual structure of our services.',
      note: 'If a figure cannot be independently verified, it does not appear on this site.',
      items: [
        {
          value: '8',
          label: 'Service disciplines',
          description: 'From infrastructure through to managed support',
          icon: 'layers'
        },
        {
          value: '8',
          label: 'Industry segments',
          description: 'Clinics, offices, hotels, industrial sites and more',
          icon: 'building-2'
        },
        {
          value: '4',
          label: 'Technology domains',
          description: 'Network, security, smart systems, managed services',
          icon: 'cpu'
        },
        {
          value: '6',
          label: 'Project stages',
          description: 'From consultation to support, each with documented deliverables',
          icon: 'route'
        }
      ]
    },
    faq: {
      eyebrow: 'Questions',
      heading: 'What people ask us most often',
      description: 'If your question is not here, get in touch — we will go through it in detail during a free consultation.',
      cta: 'All questions'
    },
    cta: {
      eyebrow: 'Getting started',
      heading: 'Let’s talk about the building first — the hardware comes after',
      description: 'Tell us about the site: floor area, purpose, existing systems and timeline. We will prepare solution options and a transparent specification.',
      primary: 'Send an enquiry',
      secondary: 'Call us',
      points: [
        'Initial consultation at no cost',
        'Site survey at a time that suits you',
        'Specification with alternatives and open pricing'
      ]
    },
    seo: {
      title: 'KMS — IT Infrastructure, Security and Smart Systems',
      description: 'Systems integrator for IT infrastructure and smart technologies: networks, server rooms, video surveillance, access control, smart home and smart building.'
    }
  },
  about: {
    hero: {
      eyebrow: 'About us',
      headline: 'An engineering team that looks at a building as a system',
      description: 'KMS is an integrator of IT infrastructure and smart systems. We work at the level where a site needs not individual devices but a considered, documented and manageable system.',
      highlights: [
        'Design, installation, integration, support',
        'Vendor-independent choices',
        'Documentation that stays with the building'
      ]
    },
    story: {
      heading: 'What we do — and what we do not',
      paragraphs: [
        'KMS builds the technology infrastructure of companies, offices, medical facilities, hotels, industrial sites and private properties — from nothing to completion. That covers networks, structured cabling, server rooms, video surveillance and access control, plus smart home and smart building automation.',
        'We are not a hardware retailer and not a one-off computer service. Our work starts with studying the site and ends with a working, documented system that has support behind it.',
        'One of our core directions is equipping houses, villas and offices with smart systems — built so the automation is genuinely useful day to day rather than merely demonstrable.'
      ]
    },
    mission: {
      heading: 'What we set out to do',
      items: [
        {
          title: 'A system, not a shopping list',
          description: 'Instead of standalone devices we create an interconnected system whose components share one logic.',
          icon: 'workflow'
        },
        {
          title: 'A predictable outcome',
          description: 'Every project stage has a concrete, verifiable deliverable — not a general promise.',
          icon: 'target'
        },
        {
          title: 'Long-term usefulness',
          description: 'We design with headroom so growth does not mean rebuilding the infrastructure.',
          icon: 'trending-up'
        }
      ]
    },
    principles: {
      heading: 'Principles we do not compromise on',
      intro: 'These are not slogans — they are the rules every project is checked against before handover.',
      items: [
        {
          title: 'Honesty in communication',
          description: 'We do not publish unverified statistics, invented projects or fabricated testimonials. If we have not done something yet, we say so plainly.',
          icon: 'badge-check'
        },
        {
          title: 'Documentation by default',
          description: 'Diagrams, line registers, configurations and test results are part of handover, not an optional extra.',
          icon: 'file-text'
        },
        {
          title: 'Open pricing',
          description: 'The specification shows what the price covers. Alternatives are offered openly, with any effect on quality stated.',
          icon: 'list-checks'
        },
        {
          title: 'Technology independence',
          description: 'Equipment is chosen for the task rather than out of commercial obligation. Clients are not left locked to one vendor.',
          icon: 'compass'
        },
        {
          title: 'Security as a baseline',
          description: 'Factory passwords, recorders exposed straight to the internet and unsegmented networks do not exist in our projects.',
          icon: 'shield-check'
        },
        {
          title: 'Transferable knowledge',
          description: 'Systems are handed over with training. A site should never depend on one particular individual.',
          icon: 'users'
        }
      ]
    },
    disciplines: {
      heading: 'Engineering disciplines',
      intro: 'Our competence divides into four domains that complement each other in every project.',
      groups: [
        {
          title: 'Infrastructure',
          items: [
            'Structured cabling',
            'Network architecture',
            'Server and comms rooms',
            'Power, UPS and redundancy',
            'Data storage and recovery'
          ]
        },
        {
          title: 'Physical security',
          items: [
            'Video surveillance',
            'Access control',
            'Perimeter protection',
            'Video door entry',
            'Integration with alarm systems'
          ]
        },
        {
          title: 'Smart systems',
          items: [
            'Smart home and villa',
            'Smart building and energy monitoring',
            'Lighting and climate automation',
            'Audio visual systems',
            'Scenarios and integrations'
          ]
        },
        {
          title: 'Managed services',
          items: [
            'Monitoring and alerting',
            'User support',
            'Preventive maintenance and updates',
            'Backups and recovery',
            'Technical consulting'
          ]
        }
      ]
    },
    standards: {
      heading: 'What we work to',
      intro: 'We follow established engineering practice. The approaches below are applied systematically across our projects:',
      items: [
        'Structured cabling planning and labelling',
        'Network segmentation and access policy',
        'Line certification with recorded results',
        'Power calculation and separation of critical load',
        'Video coverage mapping by task level',
        'Access zoning and role-based permissions',
        'Backup policy and restore testing',
        'Handover documentation and operating instructions'
      ],
      note: 'Which regulatory requirements apply to a given site depends on its use and is agreed during the design stage.'
    },
    seo: {
      title: 'About Us — KMS Systems Integrator',
      description: 'KMS is an integrator of IT infrastructure and smart systems. An engineering approach, documented projects, vendor-independent solutions and long-term support.'
    }
  },
  servicesIndex: {
    hero: {
      eyebrow: 'Services',
      headline: 'Eight disciplines that work as one system',
      description: 'Each discipline is a project in its own right and part of a larger infrastructure. Below you will find what each covers and where it applies.'
    },
    groups: [
      {
        key: 'infrastructure',
        title: 'Infrastructure',
        description: 'The digital foundation — cabling, network, server room, power.'
      },
      {
        key: 'security',
        title: 'Physical security',
        description: 'Who enters, where and when; what happens on site and how an event is reconstructed.'
      },
      {
        key: 'smart',
        title: 'Smart systems',
        description: 'Automation and audio visual spaces for homes and buildings.'
      },
      {
        key: 'managed',
        title: 'Managed services',
        description: 'Day-to-day operation, monitoring and support under agreed terms.'
      }
    ],
    seo: {
      title: 'Services — Infrastructure, Security, Automation | KMS',
      description: 'KMS services: IT infrastructure, networks, video surveillance, access control, smart home and smart building, audio visual systems and managed IT.'
    }
  },
  services: [
    {
      slug: 'it-infrastructure',
      icon: 'server',
      category: 'infrastructure',
      name: 'IT infrastructure',
      summary: 'Server rooms, structured cabling, backup power and data storage — the digital foundation of a building, built as one coherent project.',
      hero: {
        eyebrow: 'IT infrastructure',
        headline: 'The digital foundation every other system stands on',
        description: 'We design and install complete IT infrastructure: from cabling and server rooms through to backup power, data storage and monitoring.',
        highlights: [
          'Server and comms room fit-out',
          'Structured cabling systems',
          'UPS and power distribution',
          'Backups and recovery'
        ]
      },
      overview: {
        heading: 'What we mean by IT infrastructure',
        paragraphs: [
          'IT infrastructure is the layer users never see but everything depends on: internet, telephony, cameras, access control, workstations and business applications. If that layer is weak, nothing built on top of it will be stable.',
          'Our approach is straightforward: understand how the building operates and how much it will grow over the next few years, then design infrastructure with headroom. That means the right cable category, enough ports, well-planned routes, power redundancy and documentation that still makes the system manageable years later.',
          'We work both on new sites from scratch and on operating premises, where infrastructure is brought into order in stages without halting day-to-day work.'
        ],
        highlights: [
          {
            title: 'Scalable architecture',
            description: 'Spare ports, routes and power capacity are built in so that growth does not mean starting over.',
            icon: 'trending-up'
          },
          {
            title: 'Complete register',
            description: 'Every line, port and device is labelled and recorded in the site documentation.',
            icon: 'clipboard-check'
          },
          {
            title: 'Reduced risk',
            description: 'Power redundancy, cooling and monitoring cut the probability of critical nodes going down.',
            icon: 'shield-check'
          }
        ]
      },
      challenges: {
        heading: 'Typical problems we find',
        intro: 'On most sites infrastructure has grown over the years without a plan. The result usually looks like this:',
        items: [
          {
            title: 'A chaotic comms cabinet',
            description: 'Unlabelled cables, overloaded ports, exposed equipment — locating a single fault takes hours.',
            icon: 'cable'
          },
          {
            title: 'A single point of power',
            description: 'Nodes without redundancy go down with every power interruption and lose data with them.',
            icon: 'zap'
          },
          {
            title: 'Overheating equipment',
            description: 'Unplanned cooling shortens hardware life and causes unexpected outages.',
            icon: 'thermometer'
          },
          {
            title: 'No documentation',
            description: 'With no diagrams, every change is a risk and every new contractor starts the investigation from scratch.',
            icon: 'file-text'
          }
        ]
      },
      solutions: {
        heading: 'What we do',
        intro: 'An infrastructure project covers four interlinked blocks:',
        items: [
          {
            title: 'Structured cabling',
            description: 'Route planning, copper and fibre installation, patch panel mounting, a labelling scheme and line certification.',
            icon: 'cable'
          },
          {
            title: 'Server and comms room',
            description: 'Rack selection and build, equipment layout, cable management, cooling, fire protection and access control.',
            icon: 'server'
          },
          {
            title: 'Power and redundancy',
            description: 'UPS sizing, in-rack power distribution, separation of critical and non-critical loads, generator integration.',
            icon: 'zap'
          },
          {
            title: 'Data and recovery',
            description: 'File services, a backup policy, version retention and genuine testing of the restore procedure.',
            icon: 'database'
          }
        ]
      },
      benefits: {
        heading: 'What you get',
        items: [
          {
            title: 'Less downtime',
            description: 'Redundant power and proper cooling reduce unplanned interruptions.',
            icon: 'activity'
          },
          {
            title: 'Fast diagnostics',
            description: 'Labelled lines and diagrams cut fault localisation from hours to minutes.',
            icon: 'search'
          },
          {
            title: 'Predictable growth',
            description: 'A new workstation, camera or device fits into the existing architecture.',
            icon: 'trending-up'
          },
          {
            title: 'Asset control',
            description: 'You know what you own, where it sits and when warranty expires — purchasing becomes planned, not reactive.',
            icon: 'list-checks'
          }
        ]
      },
      capabilities: {
        heading: 'Full scope of work',
        groups: [
          {
            title: 'Cabling',
            items: [
              'Route and containment planning',
              'Cat6 / Cat6A copper lines',
              'Fibre optic backbones',
              'Patch panels and organisers',
              'Line labelling and certification',
              'As-built documentation'
            ]
          },
          {
            title: 'Server room',
            items: [
              'Rack selection and installation',
              'Equipment layout planning',
              'Cable management and airflow',
              'Cooling and temperature control',
              'Environmental monitoring and alerts',
              'Physical room security'
            ]
          },
          {
            title: 'Power and data',
            items: [
              'UPS sizing and installation',
              'Power distribution and circuit groups',
              'Generator integration',
              'Server and storage build',
              'Virtualisation and consolidation',
              'Backups and restore testing'
            ]
          }
        ]
      },
      stack: {
        heading: 'Technology ecosystem',
        intro: 'We work with platforms built on open standards. Specific hardware is selected to match the task and the budget.',
        groups: [
          {
            title: 'Cabling systems',
            items: [
              'Cat6 / Cat6A',
              'Single-mode and multi-mode fibre',
              'LSZH cables',
              'MPO/LC termination'
            ]
          },
          {
            title: 'Server hardware',
            items: [
              '19" rack systems',
              'Rack and tower servers',
              'NAS and SAN storage',
              'KVM and management modules'
            ]
          },
          {
            title: 'Power and monitoring',
            items: [
              'Online and line-interactive UPS',
              'PDU distribution',
              'Temperature and humidity sensors',
              'SNMP monitoring'
            ]
          }
        ]
      },
      industries: [
        'corporate',
        'healthcare',
        'industrial',
        'government',
        'education'
      ],
      related: [
        'networking',
        'managed-it',
        'cctv'
      ],
      faq: [
        {
          q: 'Can infrastructure be upgraded without stopping operations?',
          a: 'In most cases yes. We plan a staged migration: new lines and nodes first, then a gradual move of services, and finally decommissioning of the old segment. Critical work is done outside working hours in an agreed window.'
        },
        {
          q: 'Which cable category do we need?',
          a: 'It depends on run length, required speed and expected future load. Cat6 is often sufficient for office workstations, while Cat6A is recommended for cameras, Wi-Fi access points and any segment likely to move to 10G. Backbones use fibre.'
        },
        {
          q: 'How large should the server room be?',
          a: 'Size is driven by the amount of equipment, cooling requirements and the space needed to service it. A small office may be well served by a single wall or floor rack with adequate ventilation; larger sites need a dedicated room with planned power, cooling and access control.'
        },
        {
          q: 'What is included in the handover documentation?',
          a: 'Cabling diagrams, a port and line register, an equipment list with serial numbers, a configuration record, test results and warranty terms.'
        }
      ],
      seo: {
        title: 'IT Infrastructure — Server Rooms, Cabling, UPS | KMS',
        description: 'IT infrastructure design and installation: structured cabling, server rooms, UPS and power redundancy, data storage and backups.'
      }
    },
    {
      slug: 'networking',
      icon: 'network',
      category: 'infrastructure',
      name: 'Network infrastructure',
      summary: 'A sound, secure network: switching, routing, Wi-Fi coverage, segmentation and monitoring — for offices, clinics and industrial sites alike.',
      hero: {
        eyebrow: 'Network infrastructure',
        headline: 'A network that stays stable at peak load',
        description: 'We design networks around the real load and risk profile of the site: correct segmentation, sufficient bandwidth, seamless Wi-Fi coverage and control over who can reach what.',
        highlights: [
          'Switching and routing',
          'Enterprise Wi-Fi coverage',
          'VLAN segmentation and access policy',
          'Monitoring and fault response'
        ]
      },
      overview: {
        heading: 'The network as a business-critical service',
        paragraphs: [
          'On a modern site the network carries far more than internet access: cameras, access controllers, telephony, point-of-sale terminals, medical equipment and smart systems all share the same infrastructure. So network planning starts with one question — what traffic will run across it, and what happens if it stops.',
          'We build a segmented network where systems are logically separated. Camera traffic does not interfere with office work, guest Wi-Fi cannot reach internal resources, and critical services get priority.',
          'Wi-Fi coverage is planned on measurements: how many devices will operate where, what walls stand in the way, and how a user moves from one area to the next without dropping.'
        ],
        highlights: [
          {
            title: 'Segmentation from day one',
            description: 'Separate networks for office, security, guests and technical systems.',
            icon: 'layers'
          },
          {
            title: 'Predictable Wi-Fi',
            description: 'Coverage is planned on the floor plan and verified by on-site measurement.',
            icon: 'wifi'
          },
          {
            title: 'Visibility',
            description: 'Monitoring shows load, outages and problem nodes in real time.',
            icon: 'activity'
          }
        ]
      },
      challenges: {
        heading: 'What does not work in typical networks',
        intro: 'These are the faults we encounter most often:',
        items: [
          {
            title: 'A flat network',
            description: 'Every device sits in one segment — from a camera to a guest’s phone. One problem affects the whole site.',
            icon: 'network'
          },
          {
            title: 'Wi-Fi blind spots',
            description: 'Access points were installed where a socket happened to be, not where coverage is needed. Signal is excessive in one place and absent in another.',
            icon: 'wifi'
          },
          {
            title: 'Uncontrolled access',
            description: 'Everyone knows the password, guests can see internal resources, and former employees still have working credentials.',
            icon: 'lock'
          },
          {
            title: 'No redundancy',
            description: 'One internet link and one switch — either failing brings the site to a stop.',
            icon: 'alert-triangle'
          }
        ]
      },
      solutions: {
        heading: 'Our solution',
        items: [
          {
            title: 'Network architecture',
            description: 'Separation of core, distribution and access layers, bandwidth calculation and redundancy for critical nodes.',
            icon: 'route'
          },
          {
            title: 'Segmentation and policy',
            description: 'VLANs per system, traffic filtering between segments, an isolated guest network and priority for critical services.',
            icon: 'shield'
          },
          {
            title: 'Enterprise Wi-Fi',
            description: 'Access points positioned to plan, unified management, seamless roaming between zones and a separate guest network.',
            icon: 'radio-tower'
          },
          {
            title: 'Monitoring and support',
            description: 'Node health checks, outage alerts, configuration backups and scheduled updates.',
            icon: 'activity'
          }
        ]
      },
      benefits: {
        heading: 'Outcome',
        items: [
          {
            title: 'Stable operation',
            description: 'Systems stop competing with each other, even under peak load.',
            icon: 'check-circle'
          },
          {
            title: 'A security baseline',
            description: 'Segmentation limits the spread of a problem and reduces the risk of unauthorised access.',
            icon: 'shield-check'
          },
          {
            title: 'Continuous connectivity',
            description: 'A secondary link and duplicated nodes keep the site running.',
            icon: 'refresh-cw'
          },
          {
            title: 'Simpler administration',
            description: 'A single management interface and clear documentation lower day-to-day effort.',
            icon: 'settings'
          }
        ]
      },
      capabilities: {
        heading: 'Scope of work',
        groups: [
          {
            title: 'Design',
            items: [
              'Load and requirements analysis',
              'Logical and physical topology',
              'Wi-Fi coverage plan',
              'IP addressing scheme',
              'Security policies'
            ]
          },
          {
            title: 'Implementation',
            items: [
              'Switch installation and configuration',
              'Routing and internet links',
              'Access point placement',
              'VLAN and QoS settings',
              'VPN for remote access'
            ]
          },
          {
            title: 'Operations',
            items: [
              'Monitoring setup',
              'Alert configuration',
              'Configuration backups',
              'Firmware and software updates',
              'Periodic audit'
            ]
          }
        ]
      },
      stack: {
        heading: 'Technologies',
        intro: 'We work with platforms that offer long-term support and open standards.',
        groups: [
          {
            title: 'Switching and routing',
            items: [
              'L2/L3 managed switches',
              'PoE and PoE+ power',
              'Link aggregation',
              'Stacking'
            ]
          },
          {
            title: 'Wireless',
            items: [
              'Wi-Fi 5 / Wi-Fi 6',
              'Centralised controllers',
              'Fast roaming',
              'Guest portal'
            ]
          },
          {
            title: 'Security and management',
            items: [
              'Firewall and UTM',
              'VPN (site-to-site and remote)',
              'RADIUS authentication',
              'SNMP monitoring'
            ]
          }
        ]
      },
      industries: [
        'corporate',
        'hospitality',
        'healthcare',
        'education',
        'retail'
      ],
      related: [
        'it-infrastructure',
        'managed-it',
        'cctv'
      ],
      faq: [
        {
          q: 'How do you plan Wi-Fi coverage?',
          a: 'We start from the floor plan and how the space is used: how many users and devices per area, what the walls are made of, where high-density zones sit. Where needed we take on-site measurements and verify coverage again after installation.'
        },
        {
          q: 'Are managed switches really necessary?',
          a: 'If the site runs several systems — cameras, access control, office network, guests — then yes. Segmentation, PoE control and diagnostics are only possible on managed hardware. Smaller sites can start lighter, provided the design allows for expansion.'
        },
        {
          q: 'Can a backup internet link be configured?',
          a: 'Yes. We configure automatic failover to a second provider or a mobile link. For critical services both links can be used simultaneously.'
        },
        {
          q: 'How is the guest network kept separate?',
          a: 'Guests get their own network with a separate password or portal. That segment is isolated from internal resources, has a bandwidth limit and, where required, time-limited access.'
        }
      ],
      seo: {
        title: 'Network Infrastructure — Switching and Wi-Fi | KMS',
        description: 'Network design and installation: managed switching, routing, enterprise Wi-Fi, VLAN segmentation, VPN, monitoring and network support.'
      }
    },
    {
      slug: 'cctv',
      icon: 'camera',
      category: 'security',
      name: 'Video surveillance',
      summary: 'A camera system that actually answers questions: correct optics, sufficient retention, video analytics and secured access.',
      hero: {
        eyebrow: 'Video surveillance',
        headline: 'Cameras that answer the question you are asking',
        description: 'We design video surveillance from the coverage map through to the recording policy — so that when it matters, the footage exists, is legible and can be found quickly.',
        highlights: [
          'Coverage map and camera selection',
          'Night-time and difficult lighting',
          'Video analytics and alerts',
          'Secured remote access'
        ]
      },
      overview: {
        heading: 'A system that reconstructs an event',
        paragraphs: [
          'The value of a surveillance system comes down to one question: can you see today what happened two weeks ago at a specific point? If the answer is uncertain, the system is not doing its job.',
          'So a project starts with a coverage map: which zones are critical, where a general view is enough, and where face or licence plate recognition is required. That determines camera type, lens, resolution and mounting height.',
          'Next comes recording and retention: how many days of archive are needed, at what frame rate and on which cameras. This directly drives storage capacity and network load, which we calculate up front.'
        ],
        highlights: [
          {
            title: 'Coverage on the plan',
            description: 'Every camera’s field of view is drawn on the floor plan — blind spots are visible before installation.',
            icon: 'eye'
          },
          {
            title: 'Usable footage',
            description: 'Resolution and frame rate are chosen for the task, not for a marketing figure.',
            icon: 'video'
          },
          {
            title: 'Secured access',
            description: 'A separate network segment, individual user accounts and logged access.',
            icon: 'lock'
          }
        ]
      },
      challenges: {
        heading: 'Why existing systems fail',
        items: [
          {
            title: 'Footage exists but shows nothing',
            description: 'The wrong lens or mounting height gives a general view in which no face or plate is legible.',
            icon: 'eye'
          },
          {
            title: 'The archive is far too short',
            description: 'Storage was never sized, so the system overwrites itself within days.',
            icon: 'hard-drive'
          },
          {
            title: 'The system is blind at night',
            description: 'Infrared illumination or camera sensitivity does not match the zone, making night footage useless.',
            icon: 'lightbulb'
          },
          {
            title: 'Access is uncontrolled',
            description: 'The recorder is exposed directly to the internet with factory credentials — a direct security risk.',
            icon: 'alert-triangle'
          }
        ]
      },
      solutions: {
        heading: 'How we build it',
        items: [
          {
            title: 'Coverage design',
            description: 'Zone classification (monitor, recognise, identify), camera and lens selection, placement on the floor plan.',
            icon: 'map-pin'
          },
          {
            title: 'Recording and retention',
            description: 'Recorder or server sizing, retention depth, motion- and event-based recording modes.',
            icon: 'database'
          },
          {
            title: 'Video analytics',
            description: 'Zone intrusion, line crossing, object left or removed, person and vehicle classification — to cut false alarms.',
            icon: 'scan-face'
          },
          {
            title: 'Integration and access',
            description: 'Linking to access control and alarm sensors, mobile and web access, tiered user permissions.',
            icon: 'link'
          }
        ]
      },
      benefits: {
        heading: 'What it gives the site',
        items: [
          {
            title: 'Incident reconstruction',
            description: 'Footage is located in minutes by exact time and place.',
            icon: 'search'
          },
          {
            title: 'Deterrence',
            description: 'Visible cameras and alerting reduce the likelihood of incidents.',
            icon: 'shield'
          },
          {
            title: 'Process oversight',
            description: 'Deliveries, loading bays and work zones can be monitored in real time.',
            icon: 'activity'
          },
          {
            title: 'Fewer false alarms',
            description: 'Analytics filters the noise and surfaces only meaningful events.',
            icon: 'target'
          }
        ]
      },
      capabilities: {
        heading: 'What the project covers',
        groups: [
          {
            title: 'Cameras',
            items: [
              'Indoor and outdoor cameras',
              'Fixed and varifocal lenses',
              'PTZ controllable cameras',
              'Panoramic and multi-sensor models',
              'Vandal-resistant housings'
            ]
          },
          {
            title: 'Recording and archive',
            items: [
              'NVR recorders',
              'Server-based recording',
              'RAID disk arrays',
              'Retention calculation',
              'Event-based recording'
            ]
          },
          {
            title: 'Management',
            items: [
              'Video management software (VMS)',
              'Multi-site view',
              'User permissions',
              'Mobile application',
              'Footage export'
            ]
          }
        ]
      },
      stack: {
        heading: 'Technologies',
        intro: 'We work with open IP camera standards, which keeps future expansion free of vendor lock-in.',
        groups: [
          {
            title: 'Protocols',
            items: [
              'ONVIF',
              'RTSP',
              'H.264 / H.265',
              'PoE power'
            ]
          },
          {
            title: 'Analytics',
            items: [
              'Motion detection',
              'Line crossing and perimeter',
              'Person/vehicle classification',
              'Licence plate recognition'
            ]
          },
          {
            title: 'Integration',
            items: [
              'Access control',
              'Alarm systems',
              'Smart building scenarios',
              'Remote monitoring'
            ]
          }
        ]
      },
      industries: [
        'retail',
        'industrial',
        'healthcare',
        'corporate',
        'residential'
      ],
      related: [
        'access-control',
        'networking',
        'it-infrastructure'
      ],
      faq: [
        {
          q: 'How many days of archive do we need?',
          a: 'It depends on the type of site and internal policy. Offices are often well served by 14–30 days; retail and industrial sites usually need more. Retention drives storage capacity directly, so it is calculated at design stage.'
        },
        {
          q: 'Can existing cameras be kept?',
          a: 'If they are IP cameras supporting ONVIF, they can usually be brought into the new system. For analogue cameras we assess whether they still meet the requirement — partial replacement is often the more effective route.'
        },
        {
          q: 'How is remote access secured?',
          a: 'We never leave a recorder exposed directly to the internet. Access runs over VPN or the manufacturer’s secured service, with individual accounts and, where supported, two-factor authentication.'
        },
        {
          q: 'What about personal data?',
          a: 'We advise on appropriate placement (for example excluding private spaces), recommend signage and configure an access log. Ultimate legal responsibility for compliance rests with the site owner.'
        }
      ],
      seo: {
        title: 'CCTV Video Surveillance — Design and Installation | KMS',
        description: 'CCTV design and installation: coverage mapping, IP cameras, NVR and retention, video analytics, secured remote access and integration with access control.'
      }
    },
    {
      slug: 'access-control',
      icon: 'fingerprint',
      category: 'security',
      name: 'Access control',
      summary: 'Who goes where, and when — under control. Cards, codes, biometrics and mobile credentials, integrated with the video system.',
      hero: {
        eyebrow: 'Access control',
        headline: 'Doors that follow rules rather than chance',
        description: 'We deploy access control where permissions are assigned by role, every entry is recorded, and a departing employee’s access is revoked in a single action.',
        highlights: [
          'Cards, PIN, biometrics, mobile',
          'Time zones and role-based rights',
          'Integration with video',
          'Evacuation modes and life safety'
        ]
      },
      overview: {
        heading: 'Managed access instead of keys',
        paragraphs: [
          'A mechanical key cannot answer the question "who entered the server room at two in the morning". An access control system can — and more importantly, it lets you define access in advance: who, which door, which hours.',
          'A project begins with zoning: which spaces are public, which are for staff, and which are restricted — server room, stores, clinical areas, cash office. Then the identification method and door hardware are selected.',
          'Access control delivers most when it is not isolated: an entry event ties to recorded video, to the alarm system and, where required, to time and attendance.'
        ],
        highlights: [
          {
            title: 'Role-based model',
            description: 'Rights attach to a position rather than an individual — changes take seconds.',
            icon: 'users'
          },
          {
            title: 'A complete log',
            description: 'Every entry, denied attempt and door-held-open event is recorded.',
            icon: 'file-text'
          },
          {
            title: 'Life safety compliance',
            description: 'On a fire alarm signal, doors release along evacuation routes.',
            icon: 'flame'
          }
        ]
      },
      challenges: {
        heading: 'Risks we close',
        items: [
          {
            title: 'Uncontrolled keys',
            description: 'A lost or passed-on key means rekeying an entire group of locks.',
            icon: 'lock'
          },
          {
            title: 'Former employees still have access',
            description: 'A card or code stays active because the process was never formalised.',
            icon: 'alert-triangle'
          },
          {
            title: 'No event record',
            description: 'After an incident it is impossible to establish who was in the area.',
            icon: 'search'
          },
          {
            title: 'Isolated systems',
            description: 'Access, cameras and alarms run separately — response slows down.',
            icon: 'workflow'
          }
        ]
      },
      solutions: {
        heading: 'What we deploy',
        items: [
          {
            title: 'Zoning and permissions',
            description: 'Space classification, role definition, time zones and exception rules.',
            icon: 'layers'
          },
          {
            title: 'Identification',
            description: 'Card and fob, PIN, fingerprint or facial recognition, mobile credentials — matched to the risk of the zone.',
            icon: 'fingerprint'
          },
          {
            title: 'Door hardware',
            description: 'Magnetic and electromechanical locks, door position sensors, exit buttons, emergency release.',
            icon: 'door-open'
          },
          {
            title: 'Integration',
            description: 'Event linking to the video system, automatic release on fire alarm, time and attendance, visitor management.',
            icon: 'link'
          }
        ]
      },
      benefits: {
        heading: 'Outcome',
        items: [
          {
            title: 'A controlled perimeter',
            description: 'Every door is managed and every entry is known.',
            icon: 'shield-check'
          },
          {
            title: 'Fast changes',
            description: 'Adding a staff member or revoking access happens in one interface.',
            icon: 'refresh-cw'
          },
          {
            title: 'Incident analysis',
            description: 'The log and the video complement each other — reconstruction takes minutes.',
            icon: 'search'
          },
          {
            title: 'Lower operating cost',
            description: 'No more key management, lock changes or manual sign-in sheets.',
            icon: 'trending-up'
          }
        ]
      },
      capabilities: {
        heading: 'System components',
        groups: [
          {
            title: 'Identification',
            items: [
              'RFID cards and fobs',
              'PIN keypads',
              'Fingerprint readers',
              'Facial recognition terminals',
              'Mobile credentials (Bluetooth/NFC)'
            ]
          },
          {
            title: 'Door hardware',
            items: [
              'Magnetic locks',
              'Electromechanical locks',
              'Door position sensors',
              'Exit buttons and sensors',
              'Turnstiles and barriers'
            ]
          },
          {
            title: 'Management',
            items: [
              'Controllers and modules',
              'Centralised management software',
              'Time zones and schedules',
              'Event log and reporting',
              'Visitor and temporary access management'
            ]
          }
        ]
      },
      stack: {
        heading: 'Technologies',
        groups: [
          {
            title: 'Interfaces',
            items: [
              'Wiegand',
              'OSDP',
              'TCP/IP controllers',
              'PoE power'
            ]
          },
          {
            title: 'Credentials',
            items: [
              'MIFARE / DESFire',
              'EM-Marine',
              'Biometric templates',
              'Mobile credentials'
            ]
          },
          {
            title: 'Integrations',
            items: [
              'Video management (VMS)',
              'Fire alarm systems',
              'Time and attendance',
              'Smart building scenarios'
            ]
          }
        ]
      },
      industries: [
        'corporate',
        'healthcare',
        'government',
        'industrial',
        'education'
      ],
      related: [
        'cctv',
        'smart-building',
        'networking'
      ],
      faq: [
        {
          q: 'Which identification method should we choose?',
          a: 'It depends on the risk of the zone. For ordinary office doors a card or mobile credential is enough. High-risk zones — server room, cash office, medicine store — warrant two factors, for example card plus PIN, or biometrics.'
        },
        {
          q: 'What happens during a power cut?',
          a: 'The system runs on a UPS. Doors are configured to the site’s safety requirements: automatic release along evacuation routes, and fail-secure behaviour in protected zones with an emergency release available.'
        },
        {
          q: 'Can the same system handle time and attendance?',
          a: 'Yes. Reports are generated from entry and exit events. It is important to define in advance which readers count as attendance terminals and how exceptions are handled.'
        },
        {
          q: 'Can the system be rolled out in stages?',
          a: 'Yes. We start with critical doors — main entrance, server room, stores — and expand from there. What matters is choosing controllers and a platform that anticipate the expansion.'
        }
      ],
      seo: {
        title: 'Access Control Systems — Cards, Biometrics | KMS',
        description: 'Access control design and installation: RFID cards, biometrics, mobile credentials, door hardware, event logging and integration with video surveillance.'
      }
    },
    {
      slug: 'smart-home',
      icon: 'house',
      category: 'smart',
      name: 'Smart home',
      summary: 'Lighting, climate, blinds, security, irrigation and multimedia in one logic — for houses, villas and apartments, with scenarios and remote control.',
      hero: {
        eyebrow: 'Smart home',
        headline: 'A home that follows your rhythm',
        description: 'We design and deploy smart systems where lighting, climate, blinds, security and multimedia are connected and driven by scenarios — not by a dozen separate apps.',
        highlights: [
          'Scenarios that follow the day',
          'Lighting and climate control',
          'Security sensors and notifications',
          'Control by keypad, phone and voice'
        ]
      },
      overview: {
        heading: 'Automation that gets used every day',
        paragraphs: [
          'The real test of a smart home is whether it is still in use a month later. If every action requires picking up a phone and opening an app, the system gets abandoned.',
          'So we start not with devices but with routine: when you get up, how you leave the house, what happens in the evening, what changes when guests arrive. Then we write the scenarios and choose hardware that can execute them — including from an ordinary wall switch.',
          'In parallel we build a dependable foundation: a stable network, correctly installed wiring, and a central controller that keeps working locally even when the internet is down.'
        ],
        highlights: [
          {
            title: 'Local operation',
            description: 'Core scenarios run in the house itself and do not depend on the cloud.',
            icon: 'cpu'
          },
          {
            title: 'Familiar control',
            description: 'Physical switches stay — the app is an addition, never the only way in.',
            icon: 'lightbulb'
          },
          {
            title: 'Room to grow',
            description: 'Lighting and climate today; blinds, irrigation and audio tomorrow, on the same system.',
            icon: 'layers'
          }
        ]
      },
      challenges: {
        heading: 'Why smart homes so often "do not work"',
        items: [
          {
            title: 'Ten apps for one house',
            description: 'Every device has its own application and there is no unifying logic.',
            icon: 'workflow'
          },
          {
            title: 'Total dependence on the internet',
            description: 'When the connection drops, even the lights stop responding, because everything runs in the cloud.',
            icon: 'cloud'
          },
          {
            title: 'The decision came after the renovation',
            description: 'No cabling was installed, leaving only wireless options with limited capability.',
            icon: 'cable'
          },
          {
            title: 'The rest of the family cannot use it',
            description: 'Only the person who installed it understands the system — which kills daily use.',
            icon: 'users'
          }
        ]
      },
      solutions: {
        heading: 'Our approach',
        items: [
          {
            title: 'Scenario planning',
            description: 'Morning, leaving, arriving, evening, night, holiday — each mode ties together lighting, climate, blinds and security.',
            icon: 'sparkles'
          },
          {
            title: 'Lighting and blinds',
            description: 'Dimming, colour temperature, zones, motion-triggered lighting, blinds driven by sun position and time.',
            icon: 'blinds'
          },
          {
            title: 'Climate and energy',
            description: 'Zoned heating and cooling, thermostats, schedules, occupancy sensing and consumption monitoring.',
            icon: 'thermometer'
          },
          {
            title: 'Safety and comfort',
            description: 'Leak, smoke and door sensors, cameras, video door entry, irrigation and multimedia.',
            icon: 'shield'
          }
        ]
      },
      benefits: {
        heading: 'What it delivers',
        items: [
          {
            title: 'Time and comfort',
            description: 'One action replaces ten — the house adapts to time and situation on its own.',
            icon: 'clock'
          },
          {
            title: 'Energy savings',
            description: 'Heating and lighting run only when needed — and it shows up in the reports.',
            icon: 'gauge'
          },
          {
            title: 'Safety',
            description: 'A leak, smoke or unexpected motion triggers an automatic response and a notification.',
            icon: 'siren'
          },
          {
            title: 'Property value',
            description: 'Well-considered automation makes a property modern and desirable.',
            icon: 'trending-up'
          }
        ]
      },
      capabilities: {
        heading: 'What we install',
        groups: [
          {
            title: 'Comfort',
            items: [
              'Lighting scenarios and dimming',
              'Blind and shutter control',
              'Zoned climate control',
              'Multi-room audio',
              'Voice control'
            ]
          },
          {
            title: 'Safety',
            items: [
              'Water leak sensors with valve shut-off',
              'Smoke and gas sensors',
              'Motion and door sensors',
              'Video door entry',
              'Cameras and notifications'
            ]
          },
          {
            title: 'Infrastructure',
            items: [
              'Central controller',
              'Stable Wi-Fi and wired runs',
              'Consumer unit automation',
              'UPS for critical nodes',
              'Remote access and updates'
            ]
          }
        ]
      },
      stack: {
        heading: 'Protocols and platforms',
        intro: 'We choose open, interoperable ecosystems so the system is not tied to one manufacturer.',
        groups: [
          {
            title: 'Wireless protocols',
            items: [
              'Zigbee',
              'Z-Wave',
              'Thread',
              'Matter',
              'Wi-Fi'
            ]
          },
          {
            title: 'Wired systems',
            items: [
              'KNX',
              'Modbus',
              'Relay modules',
              'DALI lighting'
            ]
          },
          {
            title: 'Control platforms',
            items: [
              'Local controllers',
              'Mobile applications',
              'Wall touch panels',
              'Voice assistants'
            ]
          }
        ]
      },
      industries: [
        'residential',
        'hospitality',
        'corporate'
      ],
      related: [
        'smart-building',
        'audio-visual',
        'cctv'
      ],
      faq: [
        {
          q: 'The renovation is finished — is it too late for a smart home?',
          a: 'No. There are wireless solutions that fit into wall switches, the consumer unit and the devices themselves without new cabling. Some functionality — certain types of zoned climate control, for example — does need wiring, so we adapt the design to the conditions.'
        },
        {
          q: 'Will the system work without internet?',
          a: 'Core scenarios run on the local controller and need no internet. You need connectivity for remote access, notifications and updates.'
        },
        {
          q: 'How hard is it for the family to use?',
          a: 'Our rule is that every main function must also be available from a physical switch. The app and scenarios are added convenience. At handover we run a walkthrough and leave a short guide.'
        },
        {
          q: 'Can it be deployed in stages?',
          a: 'Yes, and often that is the most sensible route. We start with the basics — network, controller, lighting — and add climate, blinds, security and multimedia over time.'
        }
      ],
      seo: {
        title: 'Smart Home — Automation, Scenarios, Security | KMS',
        description: 'Smart home design and installation: lighting, climate, blinds, security sensors, video entry and multimedia, using KNX, Zigbee, Z-Wave and Matter locally.'
      }
    },
    {
      slug: 'smart-building',
      icon: 'building-2',
      category: 'smart',
      name: 'Smart building',
      summary: 'Unified control of building services: lighting, HVAC, energy monitoring, access and security in one operational logic.',
      hero: {
        eyebrow: 'Smart building',
        headline: 'A building that manages its own resources',
        description: 'We bring building services together into one managed system: lighting and climate adapt to occupancy automatically, while the facilities team gains visibility and alerting.',
        highlights: [
          'Unified operations view',
          'Energy consumption monitoring',
          'HVAC and lighting automation',
          'Integration with security systems'
        ]
      },
      overview: {
        heading: 'Building services that talk to each other',
        paragraphs: [
          'On commercial sites, systems typically run independently: ventilation at one setting all day, lighting on across an empty floor, and news of a fault arriving only when someone calls.',
          'A smart building brings those systems into shared logic: occupancy, schedule, outside temperature and security events together determine how lighting and climate behave.',
          'The effect runs both ways: consumption falls, and the facilities team sees a fault before it becomes visible to occupants.'
        ],
        highlights: [
          {
            title: 'One picture',
            description: 'The status of every system in one interface, broken down by floor and zone.',
            icon: 'gauge'
          },
          {
            title: 'Measurable effect',
            description: 'Consumption data is visible over time, and the result of each measure shows up in numbers.',
            icon: 'trending-up'
          },
          {
            title: 'Proactive operations',
            description: 'Deviations and faults reach the responsible person by alert.',
            icon: 'siren'
          }
        ]
      },
      challenges: {
        heading: 'Typical difficulties on commercial sites',
        items: [
          {
            title: 'Energy spent where it is not needed',
            description: 'Lighting and ventilation run at full capacity in empty zones.',
            icon: 'zap'
          },
          {
            title: 'Systems do not communicate',
            description: 'HVAC, lighting, access and alarms are each driven from a separate panel.',
            icon: 'workflow'
          },
          {
            title: 'Response is late',
            description: 'A fault only becomes known after a complaint.',
            icon: 'clock'
          },
          {
            title: 'No data is collected',
            description: 'The effect of an investment cannot be assessed because no baseline exists.',
            icon: 'database'
          }
        ]
      },
      solutions: {
        heading: 'What we deploy',
        items: [
          {
            title: 'Zoned lighting',
            description: 'Occupancy sensing, daylight compensation, schedules and scenarios for common areas.',
            icon: 'lightbulb'
          },
          {
            title: 'HVAC control',
            description: 'Temperature zones, schedules, occupancy-driven modes and fault alarms.',
            icon: 'snowflake'
          },
          {
            title: 'Energy monitoring',
            description: 'Meters brought into the system, consumption profiles per zone, alerts on deviation.',
            icon: 'gauge'
          },
          {
            title: 'Security integration',
            description: 'Access events, video and fire alarm in one scenario logic, including evacuation mode.',
            icon: 'shield'
          }
        ]
      },
      benefits: {
        heading: 'Effect',
        items: [
          {
            title: 'Lower operating cost',
            description: 'Automatic modes cut energy spent where it delivers nothing.',
            icon: 'trending-up'
          },
          {
            title: 'Consistent comfort',
            description: 'Temperature and light stay even across zones through the day.',
            icon: 'thermometer'
          },
          {
            title: 'Longer equipment life',
            description: 'Balanced operating modes reduce wear and unplanned repairs.',
            icon: 'wrench'
          },
          {
            title: 'Decisions based on data',
            description: 'Reports show where headroom remains and which measures paid off.',
            icon: 'activity'
          }
        ]
      },
      capabilities: {
        heading: 'System components',
        groups: [
          {
            title: 'Control',
            items: [
              'Central management system',
              'Zone controllers',
              'Touch panels',
              'Schedules and scenarios',
              'User permissions'
            ]
          },
          {
            title: 'Building services',
            items: [
              'Lighting control',
              'Heating, ventilation, air conditioning',
              'Shading systems',
              'Water supply monitoring',
              'Backup power monitoring'
            ]
          },
          {
            title: 'Data',
            items: [
              'Energy meter integration',
              'Temperature and humidity sensors',
              'Air quality sensors',
              'Reporting and export',
              'Notification channels'
            ]
          }
        ]
      },
      stack: {
        heading: 'Protocols',
        groups: [
          {
            title: 'Automation',
            items: [
              'KNX',
              'BACnet',
              'Modbus',
              'DALI'
            ]
          },
          {
            title: 'Network',
            items: [
              'IP infrastructure',
              'PoE power',
              'VLAN segmentation',
              'Secured remote access'
            ]
          },
          {
            title: 'Integration',
            items: [
              'Access control',
              'Video surveillance',
              'Fire alarm',
              'Energy monitoring'
            ]
          }
        ]
      },
      industries: [
        'corporate',
        'hospitality',
        'healthcare',
        'education',
        'government'
      ],
      related: [
        'smart-home',
        'access-control',
        'it-infrastructure'
      ],
      faq: [
        {
          q: 'Can this be deployed in an existing building?',
          a: 'Yes. We start with an audit: which systems exist, which protocols they support and where measurement points sit. Often the first stage is monitoring alone — that produces the baseline data on which further automation is planned.'
        },
        {
          q: 'How soon does the effect appear?',
          a: 'Lighting and schedule automation shows results within the first months. HVAC optimisation follows a seasonal cycle, so the full picture emerges over a year. The exact figure depends on the building type and its starting condition.'
        },
        {
          q: 'Who runs the system after handover?',
          a: 'The system is set up so day-to-day management sits with the site’s own facilities team. We provide training and documentation, and offer a support agreement if wanted.'
        }
      ],
      seo: {
        title: 'Smart Building — BMS, Energy Monitoring, Automation | KMS',
        description: 'Smart building systems: lighting and HVAC automation, energy consumption monitoring, unified operational control and integration with security systems.'
      }
    },
    {
      slug: 'audio-visual',
      icon: 'monitor-play',
      category: 'smart',
      name: 'Audio visual systems',
      summary: 'Meeting rooms, conference halls, digital signage and sound systems — equipment that never delays the start of a meeting.',
      hero: {
        eyebrow: 'Audio visual systems',
        headline: 'A meeting should start with one button',
        description: 'We fit out meeting and conference spaces, digital displays and sound systems so the technology stays in the background and attention stays on the content.',
        highlights: [
          'Video conferencing rooms',
          'Projection and large displays',
          'Sound systems and microphones',
          'Unified control panel'
        ]
      },
      overview: {
        heading: 'Technology that stays out of the way',
        paragraphs: [
          'The most common problem in meeting rooms is not technical but organisational: the meeting starts five minutes late because a cable does not fit, a microphone cannot be heard or a screen will not switch input.',
          'We design the room around its purpose: how many people it seats, what the acoustics are like, where the light comes from, which platform you use for online meetings, and who — if anyone — will operate it.',
          'The result should be simple: one button brings screen, sound, camera and lighting into the right mode.'
        ],
        highlights: [
          {
            title: 'One-touch scenarios',
            description: '"Meeting", "presentation", "shut down" — one button changes every setting.',
            icon: 'sparkles'
          },
          {
            title: 'Intelligible sound',
            description: 'Microphones and speakers are chosen for the room’s acoustics, not from a catalogue.',
            icon: 'audio-lines'
          },
          {
            title: 'Compatibility',
            description: 'The room works with the platforms your company actually uses.',
            icon: 'link'
          }
        ]
      },
      challenges: {
        heading: 'What we fix',
        items: [
          {
            title: 'Cable chaos',
            description: 'Adapters and cables scattered across the table cost time at every meeting.',
            icon: 'cable'
          },
          {
            title: 'Poor intelligibility',
            description: 'One microphone for a large room, or echo with no acoustic treatment.',
            icon: 'audio-lines'
          },
          {
            title: 'The screen cannot be read',
            description: 'Size and position do not match the depth of the room or its lighting.',
            icon: 'monitor-play'
          },
          {
            title: 'Complicated control',
            description: 'Several remotes and menus that only one member of staff understands.',
            icon: 'settings'
          }
        ]
      },
      solutions: {
        heading: 'How we build it',
        items: [
          {
            title: 'Room analysis',
            description: 'Assessment of dimensions, acoustics, lighting and usage scenarios; calculation of screen size and position.',
            icon: 'ruler'
          },
          {
            title: 'Image',
            description: 'Professional displays, projectors, video walls, source switching and wireless sharing.',
            icon: 'monitor-play'
          },
          {
            title: 'Sound and camera',
            description: 'Ceiling and table microphones, echo cancellation, speaker layout, auto-framing cameras.',
            icon: 'video'
          },
          {
            title: 'Control',
            description: 'A touch panel or simple buttons, scenarios, integration with lighting and shading, room booking integration.',
            icon: 'presentation'
          }
        ]
      },
      benefits: {
        heading: 'Outcome',
        items: [
          {
            title: 'Meetings start on time',
            description: 'Technical setup takes seconds rather than minutes.',
            icon: 'clock'
          },
          {
            title: 'A professional impression',
            description: 'Good sound and image carry through to how partners experience you.',
            icon: 'badge-check'
          },
          {
            title: 'Fewer support calls',
            description: 'A clear interface reduces how often IT has to step in.',
            icon: 'headset'
          },
          {
            title: 'Better use of space',
            description: 'One room serves presentations, online meetings and training equally well.',
            icon: 'layers'
          }
        ]
      },
      capabilities: {
        heading: 'Typical spaces',
        groups: [
          {
            title: 'Meeting rooms',
            items: [
              'Small rooms (4–8 seats)',
              'Medium rooms (10–20 seats)',
              'Wireless presentation',
              'Video conferencing kits',
              'Table connectivity'
            ]
          },
          {
            title: 'Halls',
            items: [
              'Projection and large displays',
              'Sound reinforcement and microphones',
              'Integration with stage lighting',
              'Recording and streaming',
              'Operator position'
            ]
          },
          {
            title: 'Public spaces',
            items: [
              'Digital signage and menu boards',
              'Background music zones',
              'Information panels',
              'Announcement systems',
              'Remote content management'
            ]
          }
        ]
      },
      stack: {
        heading: 'Technologies',
        groups: [
          {
            title: 'Signal',
            items: [
              'HDMI and HDBaseT',
              'AV over IP',
              'Wireless presentation',
              'USB cameras and audio'
            ]
          },
          {
            title: 'Audio',
            items: [
              'Ceiling microphone arrays',
              'DSP processing',
              'Echo cancellation',
              'Zoned sound reinforcement'
            ]
          },
          {
            title: 'Platforms',
            items: [
              'Video conferencing services',
              'Room booking systems',
              'Digital signage management',
              'Centralised control'
            ]
          }
        ]
      },
      industries: [
        'corporate',
        'education',
        'hospitality',
        'government'
      ],
      related: [
        'smart-building',
        'networking',
        'managed-it'
      ],
      faq: [
        {
          q: 'Which video conferencing platform does the system support?',
          a: 'We build the room to work with the platforms your company uses. In most cases the setup is universal: camera and microphones connect over USB or the network to any service.'
        },
        {
          q: 'What screen size is required?',
          a: 'Screen size follows the depth of the room and the type of content — text needs a larger screen than video. During design we verify that content is legible from the back row.'
        },
        {
          q: 'Can existing displays be reused?',
          a: 'Often yes, provided their resolution and interfaces are adequate. We assess existing equipment and propose an option where replacement happens only where it genuinely adds value.'
        }
      ],
      seo: {
        title: 'Audio Visual Systems — Meeting Rooms and Halls | KMS',
        description: 'AV design and installation: video conferencing rooms, projection and displays, sound reinforcement and microphones, digital signage and unified control systems.'
      }
    },
    {
      slug: 'managed-it',
      icon: 'headset',
      category: 'managed',
      name: 'Managed IT services',
      summary: 'An external IT team with agreed response times: monitoring, preventive maintenance, user support, a security baseline and a recovery plan.',
      hero: {
        eyebrow: 'Managed IT services',
        headline: 'IT that looks after things in advance, not just repairs them',
        description: 'We take responsibility for the day-to-day condition of your infrastructure: we watch it, patch it, verify backups and support your users — under agreed terms.',
        highlights: [
          'Monitoring and alerting',
          'User support',
          'Updates and preventive maintenance',
          'Backups and recovery'
        ]
      },
      overview: {
        heading: 'From reactive to proactive',
        paragraphs: [
          'In many companies IT support only engages once something has already broken. That is the most expensive model there is: the fault surfaces during working hours, the fix is urgent, and the result is downtime.',
          'Managed services work on different logic. Systems are watched continuously, critical parameters — disk space, temperature, power status, service availability — are checked automatically, and an alert arrives before a user notices anything.',
          'In parallel we maintain the site’s technical history: what changed, when and why. That makes decisions predictable and reduces dependence on one particular individual.'
        ],
        highlights: [
          {
            title: 'Agreed terms',
            description: 'Response times and service scope are fixed contractually.',
            icon: 'clipboard-check'
          },
          {
            title: 'Visibility',
            description: 'Periodic reporting: what happened, what was done and what needs attention.',
            icon: 'file-text'
          },
          {
            title: 'Knowledge retained',
            description: 'Documentation stays with the site — changing supplier does not mean starting from zero.',
            icon: 'database'
          }
        ]
      },
      challenges: {
        heading: 'Typical situations',
        items: [
          {
            title: 'Problems appear without warning',
            description: 'A disk fills up, a certificate expires, backups have not run for months.',
            icon: 'alert-triangle'
          },
          {
            title: 'Knowledge sits with one person',
            description: 'Passwords and configurations are undocumented.',
            icon: 'lock'
          },
          {
            title: 'Updates fall behind',
            description: 'Out-of-date software creates a security risk.',
            icon: 'refresh-cw'
          },
          {
            title: 'Recovery has never been tested',
            description: 'A backup exists, but nobody has ever tried restoring from it.',
            icon: 'hard-drive'
          }
        ]
      },
      solutions: {
        heading: 'What the service covers',
        items: [
          {
            title: 'Monitoring',
            description: 'Continuous checks on servers, network nodes, power and critical services, with alerting.',
            icon: 'activity'
          },
          {
            title: 'User support',
            description: 'Request intake, prioritisation and resolution remotely or on site, within agreed timeframes.',
            icon: 'headset'
          },
          {
            title: 'Preventive maintenance',
            description: 'Updates, configuration backups, hardware checks and scheduled work.',
            icon: 'wrench'
          },
          {
            title: 'Security baseline',
            description: 'Access audits, password policy, endpoint protection, backup testing and a recovery plan.',
            icon: 'shield-check'
          }
        ]
      },
      benefits: {
        heading: 'What it delivers',
        items: [
          {
            title: 'Less downtime',
            description: 'Most problems are resolved before they affect anyone’s work.',
            icon: 'check-circle'
          },
          {
            title: 'Predictable budget',
            description: 'A fixed monthly cost instead of emergency call-outs.',
            icon: 'trending-up'
          },
          {
            title: 'Protected data',
            description: 'Backups run on schedule and restores are verified.',
            icon: 'database'
          },
          {
            title: 'Focus on the business',
            description: 'Your own team works on development instead of firefighting.',
            icon: 'target'
          }
        ]
      },
      capabilities: {
        heading: 'What is included',
        groups: [
          {
            title: 'Infrastructure',
            items: [
              'Server and storage management',
              'Network equipment administration',
              'Virtualisation support',
              'UPS and power monitoring',
              'Asset register'
            ]
          },
          {
            title: 'Users',
            items: [
              'Workstation provisioning',
              'Account and permission management',
              'Email and file service support',
              'Printers and peripherals',
              'Remote assistance'
            ]
          },
          {
            title: 'Security and continuity',
            items: [
              'Backup policy',
              'Restore testing',
              'Patch management',
              'Periodic access audit',
              'Incident response'
            ]
          }
        ]
      },
      stack: {
        heading: 'Tooling',
        groups: [
          {
            title: 'Monitoring',
            items: [
              'SNMP and agent-based monitoring',
              'Alerting channels',
              'Log collection',
              'Availability checks'
            ]
          },
          {
            title: 'Management',
            items: [
              'Remote access',
              'Patch management',
              'Configuration versioning',
              'Asset database'
            ]
          },
          {
            title: 'Data protection',
            items: [
              'Local backups',
              'Cloud backups',
              'Version retention',
              'Recovery procedures'
            ]
          }
        ]
      },
      industries: [
        'corporate',
        'healthcare',
        'retail',
        'education',
        'government'
      ],
      related: [
        'it-infrastructure',
        'networking',
        'smart-building'
      ],
      faq: [
        {
          q: 'How are response times defined?',
          a: 'The contract sets priority levels: critical (a site or service is down), high, and normal. Each carries its own response time. Terms are agreed against the site’s risk profile and working hours.'
        },
        {
          q: 'Do we still need an in-house IT specialist?',
          a: 'It depends on the size of the organisation. For small and mid-sized companies managed services often replace the internal resource entirely. In larger organisations we work alongside the in-house team, taking on the infrastructure layer or specific systems.'
        },
        {
          q: 'What happens if we end the contract?',
          a: 'All documentation, credentials and configurations remain yours and are handed over in structured form. Our principle is that a client should never be technically locked in to a supplier.'
        },
        {
          q: 'Does the service include hardware repair?',
          a: 'Diagnostics and fault localisation are included. Physical repair or replacement is handled separately — we prepare a recommendation and, where applicable, manage the warranty process for you.'
        }
      ],
      seo: {
        title: 'Managed IT Services — Monitoring and Support | KMS',
        description: 'Managed IT services: infrastructure monitoring, user support, updates and preventive maintenance, backups and a recovery plan under agreed terms.'
      }
    }
  ],
  industriesIndex: {
    hero: {
      eyebrow: 'Industries',
      headline: 'The same technology behaves differently in different buildings',
      description: 'A clinic, a hotel, a factory and an office have nothing in common in their requirements. We start with how the building works and only then choose the solution.'
    },
    seo: {
      title: 'Industries — Solutions by Sector | KMS',
      description: 'Technology solutions by industry: healthcare, offices, hospitality, education, industry, public sector, retail and residential properties.'
    }
  },
  industries: [
    {
      slug: 'healthcare',
      icon: 'stethoscope',
      name: 'Healthcare',
      summary: 'Clinics and hospitals where network, security and access systems must run without interruption and respect patient space.',
      hero: {
        eyebrow: 'Healthcare',
        headline: 'Infrastructure that does not interrupt clinical work',
        description: 'On a medical site a network outage is more than an inconvenience. We design systems where critical services are redundant, zones are properly separated and access is controlled.',
        highlights: [
          'Redundant network and power',
          'Strict zone separation',
          'Access control in critical spaces',
          'Video coverage that respects patient privacy'
        ]
      },
      context: {
        heading: 'What shapes the solution',
        paragraphs: [
          'A medical facility runs several very different systems at once: clinical equipment, the information system, administrative workstations, patient and visitor Wi-Fi, and security systems. They all share one infrastructure, and separating them is essential.',
          'The second factor is continuity. Operating theatres, intensive care, laboratories and the server room cannot lose power or connectivity. Redundancy is therefore designed in from the start, at both power and network routing level.',
          'The third is patient space. Video coverage is planned to cover common and critical areas without intruding into wards and treatment rooms.'
        ]
      },
      priorities: {
        heading: 'Priorities',
        items: [
          {
            title: 'Continuity',
            description: 'Critical zones stay functional through a power or link interruption.',
            icon: 'zap'
          },
          {
            title: 'Segmentation',
            description: 'Clinical equipment, administration and visitors sit on separate networks.',
            icon: 'layers'
          },
          {
            title: 'Controlled access',
            description: 'Medicine stores, laboratories and the server room — authorised staff only.',
            icon: 'lock'
          },
          {
            title: 'Patient privacy',
            description: 'Video coverage zones are defined by ethical and legal requirements.',
            icon: 'eye'
          }
        ]
      },
      solutions: {
        heading: 'What we deploy',
        items: [
          {
            title: 'Redundant network',
            description: 'Duplicated nodes, a secondary internet link and UPS on critical segments.',
            icon: 'network'
          },
          {
            title: 'Access control',
            description: 'Zone permissions, time schedules, event logging and fire alarm integration.',
            icon: 'fingerprint'
          },
          {
            title: 'Video surveillance',
            description: 'Entrances, corridors, cash desk and stores — with a considered coverage map.',
            icon: 'camera'
          },
          {
            title: 'Managed support',
            description: 'Monitoring, preventive maintenance and response within agreed timeframes.',
            icon: 'headset'
          }
        ]
      },
      scenarios: {
        heading: 'Typical projects',
        items: [
          {
            title: 'Opening a new clinic',
            description: 'From scratch: cabling, server room, network, cameras, access control and smart lighting in one project, synchronised with the construction programme.',
            icon: 'building-2'
          },
          {
            title: 'Bringing an operating site into order',
            description: 'Staged migration without halting clinical work: new lines and nodes first, then services moved across.',
            icon: 'refresh-cw'
          },
          {
            title: 'Tightening access',
            description: 'Isolating critical zones, adding event logging and linking events to the video system.',
            icon: 'shield-check'
          }
        ]
      },
      services: [
        'it-infrastructure',
        'networking',
        'access-control',
        'cctv',
        'managed-it'
      ],
      faq: [
        {
          q: 'Can work be carried out in an operating clinic?',
          a: 'Yes. We work in stages and schedule noisy or dusty work into agreed windows. Critical zones stay functional throughout.'
        },
        {
          q: 'How is patient confidentiality protected?',
          a: 'Video coverage zones are defined together with the facility, excluding wards and treatment rooms. Access to recordings is individual and logged. Ultimate legal responsibility rests with the facility.'
        }
      ],
      seo: {
        title: 'IT and Security Systems for Clinics | KMS',
        description: 'Healthcare facility infrastructure: redundant network and power, zone segmentation, access control, video surveillance and managed support.'
      }
    },
    {
      slug: 'corporate',
      icon: 'building',
      name: 'Offices and corporates',
      summary: 'Office spaces where network, meeting rooms, access control and automation need to work as one system.',
      hero: {
        eyebrow: 'Corporate',
        headline: 'An office where the technology goes unnoticed',
        description: 'We build office infrastructure from the desk to the server room: a stable network, meeting rooms that work, controlled access and automated common areas.',
        highlights: [
          'Workstation network and Wi-Fi',
          'Meeting and conference rooms',
          'Access control and attendance',
          'Lighting and climate automation'
        ]
      },
      context: {
        heading: 'What matters most',
        paragraphs: [
          'In a modern office, productivity depends directly on infrastructure: how quickly a meeting starts, whether Wi-Fi holds up in the negotiation room, how long it takes to set up a new employee’s desk.',
          'So we treat an office project as one system: cabling and network form the base, and AV, access control and automation sit on top of it. When those components are planned separately, the result is always a compromise.',
          'We pay particular attention to expansion: offices grow and teams move, so the design has to allow change without reopening walls.'
        ]
      },
      priorities: {
        heading: 'Priorities',
        items: [
          {
            title: 'Reliable connectivity',
            description: 'Network and Wi-Fi across every working area, sized for peak load.',
            icon: 'wifi'
          },
          {
            title: 'Meetings without delay',
            description: 'Rooms where one button starts a presentation or an online call.',
            icon: 'monitor-play'
          },
          {
            title: 'Security and records',
            description: 'Access control, video at entrances, time and attendance.',
            icon: 'fingerprint'
          },
          {
            title: 'Operational efficiency',
            description: 'Lighting and climate automation in shared spaces.',
            icon: 'gauge'
          }
        ]
      },
      solutions: {
        heading: 'Our solutions',
        items: [
          {
            title: 'Network and cabling',
            description: 'Workstation ports, Wi-Fi coverage, segmentation and a guest network.',
            icon: 'network'
          },
          {
            title: 'AV spaces',
            description: 'Small and large meeting rooms, wireless sharing, video conferencing.',
            icon: 'presentation'
          },
          {
            title: 'Access and video',
            description: 'Entrance, server room, stores and restricted zones under one logic.',
            icon: 'shield'
          },
          {
            title: 'Managed IT',
            description: 'Monitoring, user support and backups.',
            icon: 'headset'
          }
        ]
      },
      scenarios: {
        heading: 'Typical projects',
        items: [
          {
            title: 'Moving into a new office',
            description: 'A project synchronised with the fit-out: containment, ports, server room, AV and access control — ready for opening day.',
            icon: 'building'
          },
          {
            title: 'Office expansion',
            description: 'A new floor or wing fits into the existing architecture — same policies, same management.',
            icon: 'layers'
          },
          {
            title: 'Meeting room refresh',
            description: 'Existing rooms reconfigured around one-touch scenarios with standardised video conferencing.',
            icon: 'monitor-play'
          }
        ]
      },
      services: [
        'it-infrastructure',
        'networking',
        'audio-visual',
        'access-control',
        'managed-it'
      ],
      faq: [
        {
          q: 'How early should the technical team be involved?',
          a: 'Ideally at architectural design stage. That allows containment, the server room and electrical provision to be planned correctly from the outset. Later involvement is always more expensive and more constrained.'
        },
        {
          q: 'Can it be deployed in stages?',
          a: 'Yes. We start with the network base and add AV, access control and automation over time — the architecture anticipates that expansion from the beginning.'
        }
      ],
      seo: {
        title: 'Office IT Infrastructure and Security | KMS',
        description: 'Office technology fit-out: network and Wi-Fi, structured cabling, meeting rooms, access control, video surveillance and managed IT support.'
      }
    },
    {
      slug: 'hospitality',
      icon: 'bed-double',
      name: 'Hotels and hospitality',
      summary: 'Guest comfort and operational control at once: Wi-Fi, room automation, security and energy efficiency.',
      hero: {
        eyebrow: 'Hospitality',
        headline: 'The guest feels comfort, you see control',
        description: 'For a hotel, technology solves two problems: the guest experience and the operating cost. We design systems that serve both.',
        highlights: [
          'Seamless Wi-Fi in every area',
          'Room lighting and climate',
          'Electronic door locks',
          'Energy efficiency in vacant rooms'
        ]
      },
      context: {
        heading: 'What shapes the project',
        paragraphs: [
          'In a hotel, guest ratings often hinge on two things: whether Wi-Fi works in the room and how easy the room is to control. Both are technical questions with a direct effect on reputation.',
          'At the same time the property has operational goals: energy cost, room status visibility, staff access to service areas and security in public spaces.',
          'We treat those two sides as one system — the guest-facing part kept as simple as possible, the operational part finely controllable.'
        ]
      },
      priorities: {
        heading: 'Priorities',
        items: [
          {
            title: 'Wi-Fi everywhere',
            description: 'Rooms, corridors, restaurant, pool and grounds — with seamless roaming.',
            icon: 'wifi'
          },
          {
            title: 'Simplicity for the guest',
            description: 'Room lighting and climate on a clear panel, no instructions needed.',
            icon: 'lightbulb'
          },
          {
            title: 'Energy control',
            description: 'Vacant rooms drop automatically into economy mode.',
            icon: 'gauge'
          },
          {
            title: 'Security',
            description: 'Video coverage of public spaces and zoned staff access.',
            icon: 'shield'
          }
        ]
      },
      solutions: {
        heading: 'What we deploy',
        items: [
          {
            title: 'Hotel network',
            description: 'High-density Wi-Fi, an isolated guest segment, portal and bandwidth limits.',
            icon: 'radio-tower'
          },
          {
            title: 'Room automation',
            description: 'Lighting scenarios, climate, blinds, do-not-disturb and key card switches.',
            icon: 'house'
          },
          {
            title: 'Access',
            description: 'Electronic locks on rooms, zoned permissions for staff.',
            icon: 'door-open'
          },
          {
            title: 'Video and public spaces',
            description: 'Entrances, reception, corridors, parking and background music zones.',
            icon: 'camera'
          }
        ]
      },
      scenarios: {
        heading: 'Typical projects',
        items: [
          {
            title: 'Opening a new hotel',
            description: 'Full infrastructure from the construction stage: network, room automation, access, video and AV.',
            icon: 'building-2'
          },
          {
            title: 'Wi-Fi rebuild',
            description: 'Coverage measurement, redistribution of access points and centralised management.',
            icon: 'wifi'
          },
          {
            title: 'Reducing energy cost',
            description: 'Occupancy sensing, key card switches and climate schedules in rooms and public areas.',
            icon: 'trending-up'
          }
        ]
      },
      services: [
        'networking',
        'smart-building',
        'access-control',
        'cctv',
        'audio-visual'
      ],
      faq: [
        {
          q: 'How complicated is a smart room for the guest?',
          a: 'Our principle is that a guest should never need instructions. Core scenarios are one touch away on the wall panel, while complex settings stay hidden at the operational level.'
        },
        {
          q: 'Can this be deployed in an operating hotel?',
          a: 'Yes, we work through rooms in blocks according to the occupancy schedule. Work in public areas is carried out during low-traffic hours.'
        }
      ],
      seo: {
        title: 'Hotel Technology — Wi-Fi, Rooms, Security | KMS',
        description: 'Hospitality systems: high-density Wi-Fi, room automation, electronic access, video surveillance, background music and energy efficiency.'
      }
    },
    {
      slug: 'education',
      icon: 'graduation-cap',
      name: 'Education',
      summary: 'Schools, universities and training centres: classroom AV, campus-wide Wi-Fi, security and controlled access.',
      hero: {
        eyebrow: 'Education',
        headline: 'Technology in service of teaching',
        description: 'We build learning environments where the classroom is ready for the lesson, the network copes with hundreds of devices, and entrances and grounds are monitored.',
        highlights: [
          'Classroom and lecture AV',
          'High-density Wi-Fi',
          'Site video coverage',
          'Access control and announcements'
        ]
      },
      context: {
        heading: 'What is specific here',
        paragraphs: [
          'Load on an education site is uneven: at break time hundreds of devices join the network at once, while during a lesson what matters is stability in the room. Those different demands have to be reconciled in one design.',
          'The second area is safety: entrance control, video coverage of the grounds and an announcement system for emergencies.',
          'The third is ease of operation. The system must be usable by a teacher without technical support.'
        ]
      },
      priorities: {
        heading: 'Priorities',
        items: [
          {
            title: 'Coping with load',
            description: 'Wi-Fi planned around the real density of devices.',
            icon: 'wifi'
          },
          {
            title: 'A simple classroom',
            description: 'Screen, sound and source come on with one button.',
            icon: 'presentation'
          },
          {
            title: 'Site oversight',
            description: 'Entrances, corridors, yard and perimeter under video coverage.',
            icon: 'camera'
          },
          {
            title: 'Access management',
            description: 'Laboratories, stores and the server room on restricted access.',
            icon: 'lock'
          }
        ]
      },
      solutions: {
        heading: 'Solutions',
        items: [
          {
            title: 'Network and Wi-Fi',
            description: 'Segmentation for students, staff and administration, with content filtering.',
            icon: 'network'
          },
          {
            title: 'Classroom AV',
            description: 'Interactive displays or projection, sound reinforcement, wireless sharing.',
            icon: 'monitor-play'
          },
          {
            title: 'Security',
            description: 'Video surveillance, access control and a public address system.',
            icon: 'shield'
          },
          {
            title: 'Support',
            description: 'Monitoring, preventive maintenance and readiness before term starts.',
            icon: 'headset'
          }
        ]
      },
      scenarios: {
        heading: 'Typical projects',
        items: [
          {
            title: 'Standardising classrooms',
            description: 'An identical AV kit in every room — one set of instructions, one type of support.',
            icon: 'layers'
          },
          {
            title: 'Campus-wide Wi-Fi',
            description: 'Backbones between buildings and seamless coverage across shared spaces.',
            icon: 'radio-tower'
          },
          {
            title: 'Strengthening security',
            description: 'Entrance control, video retention and announcement scenarios.',
            icon: 'siren'
          }
        ]
      },
      services: [
        'networking',
        'audio-visual',
        'cctv',
        'access-control',
        'it-infrastructure'
      ],
      faq: [
        {
          q: 'Can work be done during the academic year?',
          a: 'Major work is scheduled into holidays or weekends. Ongoing stages are carried out in blocks so teaching is not disrupted.'
        },
        {
          q: 'How is the student network controlled?',
          a: 'Students get a separate segment with bandwidth limits and, where required, content filtering. Administrative systems are fully isolated.'
        }
      ],
      seo: {
        title: 'IT and AV Systems for Education | KMS',
        description: 'Technology for schools and universities: campus Wi-Fi, classroom AV systems, video surveillance, access control and IT support.'
      }
    },
    {
      slug: 'industrial',
      icon: 'factory',
      name: 'Industry and logistics',
      summary: 'Production and warehouse sites: a network that survives harsh conditions, perimeter protection, site oversight and vehicle logging.',
      hero: {
        eyebrow: 'Industry and logistics',
        headline: 'Infrastructure built for a production environment',
        description: 'Dust, temperature swings, long distances and steel structures — an industrial site cannot simply take an office-grade solution. We design for these conditions.',
        highlights: [
          'Industrial-grade equipment',
          'Perimeter video coverage',
          'Vehicle and cargo logging',
          'Access control in production zones'
        ]
      },
      context: {
        heading: 'What shapes the solution',
        paragraphs: [
          'On production and warehouse sites infrastructure operates in different conditions: long distances between buildings, steel structures that block signal, dust and temperature ranges that shorten the life of ordinary equipment.',
          'So we specify equipment with the appropriate protection rating, use fibre backbones between buildings, and plan Wi-Fi coverage around the actual racking layout.',
          'On the security side what matters is the perimeter, entrances, loading bays and vehicle logging — often addressed with video analytics.'
        ]
      },
      priorities: {
        heading: 'Priorities',
        items: [
          {
            title: 'Environmental resilience',
            description: 'Equipment specified for dust, moisture and temperature.',
            icon: 'shield'
          },
          {
            title: 'Covering large sites',
            description: 'Fibre backbones and outdoor access points between buildings.',
            icon: 'radio-tower'
          },
          {
            title: 'Perimeter control',
            description: 'Fence line, entrances and loading zones with analytics.',
            icon: 'camera'
          },
          {
            title: 'Vehicle logging',
            description: 'Licence plate recognition integrated with barriers.',
            icon: 'scan-face'
          }
        ]
      },
      solutions: {
        heading: 'Solutions',
        items: [
          {
            title: 'Industrial network',
            description: 'Fibre backbones, protected enclosures, PoE power to outdoor devices.',
            icon: 'network'
          },
          {
            title: 'Perimeter video',
            description: 'Line-crossing detection, night mode and object classification.',
            icon: 'camera'
          },
          {
            title: 'Access and vehicles',
            description: 'Turnstiles for staff, barriers and licence plate recognition.',
            icon: 'door-open'
          },
          {
            title: 'Monitoring',
            description: 'Environmental monitoring of the server room and nodes, with fault alerts.',
            icon: 'activity'
          }
        ]
      },
      scenarios: {
        heading: 'Typical projects',
        items: [
          {
            title: 'A new production building',
            description: 'Cabling, network, video and access control in one project, aligned with the construction programme.',
            icon: 'factory'
          },
          {
            title: 'Warehouse Wi-Fi',
            description: 'Coverage between racking for reliable operation of handheld terminals and scanners.',
            icon: 'wifi'
          },
          {
            title: 'Automating entrances',
            description: 'Licence plate recognition, barriers and a vehicle movement log.',
            icon: 'route'
          }
        ]
      },
      services: [
        'networking',
        'cctv',
        'access-control',
        'it-infrastructure',
        'managed-it'
      ],
      faq: [
        {
          q: 'How do you link buildings that are far apart?',
          a: 'The primary option is a fibre backbone — it covers distance and resists electromagnetic interference. Where cabling is impossible we consider directional wireless bridges.'
        },
        {
          q: 'Are ordinary cameras suitable for a production zone?',
          a: 'Usually not. Housings with the appropriate protection rating are required, and in dusty or cold zones additional heating and protection. This is determined at design stage.'
        }
      ],
      seo: {
        title: 'Network and Security for Industry and Warehousing | KMS',
        description: 'Industrial site infrastructure: fibre backbones, warehouse Wi-Fi, perimeter video surveillance, licence plate recognition and access control.'
      }
    },
    {
      slug: 'government',
      icon: 'landmark',
      name: 'Public sector',
      summary: 'Government and municipal facilities where documented process, access control and data protection are critical.',
      hero: {
        eyebrow: 'Public sector',
        headline: 'Documented infrastructure, transparent process',
        description: 'On public sector sites the process matters as much as the outcome: a clear specification, documented stages and a complete handover package.',
        highlights: [
          'Detailed technical specification',
          'Strict access control',
          'Data protection and backups',
          'Complete handover documentation'
        ]
      },
      context: {
        heading: 'What matters',
        paragraphs: [
          'In public sector projects a technical solution has to be clearly justified: why this architecture, why this equipment and how the outcome will be measured. Our specifications are written to exactly that logic.',
          'The second area is access control — both physical (zones, archives, server room) and logical (user permissions, event logging).',
          'The third is data continuity: a backup policy, a verified restore procedure and redundancy for critical systems.'
        ]
      },
      priorities: {
        heading: 'Priorities',
        items: [
          {
            title: 'Transparent specification',
            description: 'Every line item is justified and comparable against alternatives.',
            icon: 'file-text'
          },
          {
            title: 'Access control',
            description: 'Zoned access, event logging and periodic audit.',
            icon: 'fingerprint'
          },
          {
            title: 'Data protection',
            description: 'Backups, restore testing and a segmented network.',
            icon: 'database'
          },
          {
            title: 'Complete handover',
            description: 'Diagrams, test records, configurations and operating instructions.',
            icon: 'clipboard-check'
          }
        ]
      },
      solutions: {
        heading: 'Solutions',
        items: [
          {
            title: 'Network and segmentation',
            description: 'Strict separation of internal, operational and public segments.',
            icon: 'network'
          },
          {
            title: 'Physical security',
            description: 'Video surveillance, access control, protection of archives and the server room.',
            icon: 'shield'
          },
          {
            title: 'Server infrastructure',
            description: 'Redundant power, cooling, monitoring and asset registers.',
            icon: 'server'
          },
          {
            title: 'Support',
            description: 'Agreed response times and periodic reporting.',
            icon: 'headset'
          }
        ]
      },
      scenarios: {
        heading: 'Typical projects',
        items: [
          {
            title: 'Fitting out a building',
            description: 'Complete infrastructure in one project, staged, with documented acceptance.',
            icon: 'landmark'
          },
          {
            title: 'Security modernisation',
            description: 'Video system upgrade, tighter access control and event logging.',
            icon: 'shield-check'
          },
          {
            title: 'Data continuity',
            description: 'A backup policy and genuine testing of the restore procedure.',
            icon: 'hard-drive'
          }
        ]
      },
      services: [
        'it-infrastructure',
        'networking',
        'access-control',
        'cctv',
        'managed-it'
      ],
      faq: [
        {
          q: 'Can you prepare a technical specification for a tender?',
          a: 'Yes. We prepare a vendor-neutral specification describing functional and technical requirements in a way that allows several suppliers to be compared.'
        },
        {
          q: 'What is included in the handover package?',
          a: 'As-built diagrams, a line and port register, an equipment list with serial numbers, a configuration record, test results, warranty documents and a short operating guide.'
        }
      ],
      seo: {
        title: 'Public Sector IT Infrastructure and Security | KMS',
        description: 'Technology for government and municipal facilities: segmented networks, access control, video surveillance, server rooms and documented handover.'
      }
    },
    {
      slug: 'retail',
      icon: 'shopping-bag',
      name: 'Retail and services',
      summary: 'Shops, shopping centres and branch networks: checkout oversight, one network across sites and customer Wi-Fi.',
      hero: {
        eyebrow: 'Retail',
        headline: 'Control over the shop floor — in every branch',
        description: 'We build systems where the checkout, stockroom and sales floor are covered, branches share one network, and customers get reliable Wi-Fi.',
        highlights: [
          'Checkout area video coverage',
          'One network across branches',
          'Access control for stockrooms',
          'Customer Wi-Fi and digital signage'
        ]
      },
      context: {
        heading: 'What is decisive',
        paragraphs: [
          'In retail the main job of a video system is the checkout area and the stockroom — that is where the events most often needing reconstruction occur. So cameras there are specified to identification level rather than a general view.',
          'The second task is managing multiple branches: with several sites you need one network, centralised access to the video system and standardised equipment, so every new branch becomes a repeatable project.',
          'The third is customer experience: reliable Wi-Fi, digital displays and background music, all managed from one place.'
        ]
      },
      priorities: {
        heading: 'Priorities',
        items: [
          {
            title: 'Checkout oversight',
            description: 'Identification-grade coverage and, where useful, linkage to till data.',
            icon: 'camera'
          },
          {
            title: 'A branch standard',
            description: 'The same architecture on every site — for straightforward scaling.',
            icon: 'layers'
          },
          {
            title: 'Stockroom protection',
            description: 'Access control and video in restricted areas.',
            icon: 'lock'
          },
          {
            title: 'Customer comfort',
            description: 'An isolated guest network and managed digital content.',
            icon: 'wifi'
          }
        ]
      },
      solutions: {
        heading: 'Solutions',
        items: [
          {
            title: 'Video system',
            description: 'Checkout, entrances, sales floor and stockroom; centralised multi-branch access.',
            icon: 'video'
          },
          {
            title: 'Network between branches',
            description: 'VPN links, centralised management and backup connections.',
            icon: 'network'
          },
          {
            title: 'Access control',
            description: 'Staff zones, stockroom and administrative areas.',
            icon: 'fingerprint'
          },
          {
            title: 'Digital environment',
            description: 'Displays, menu boards and background music under unified management.',
            icon: 'monitor-play'
          }
        ]
      },
      scenarios: {
        heading: 'Typical projects',
        items: [
          {
            title: 'Opening a new branch',
            description: 'A standardised kit that quickly replicates the existing architecture.',
            icon: 'shopping-bag'
          },
          {
            title: 'Centralised video monitoring',
            description: 'Every site visible from one interface, with tiered permissions.',
            icon: 'eye'
          },
          {
            title: 'Reducing shrinkage',
            description: 'Stronger coverage and analytics on checkout and stockroom zones.',
            icon: 'target'
          }
        ]
      },
      services: [
        'cctv',
        'networking',
        'access-control',
        'audio-visual',
        'managed-it'
      ],
      faq: [
        {
          q: 'Can all branches be viewed from one place?',
          a: 'Yes. Sites are consolidated into a central video management system over secured links. Users are given permissions — a branch manager, for example, sees only their own site.'
        },
        {
          q: 'How quickly can a new site be opened?',
          a: 'Once the architecture is standardised, fitting out a typical store gets significantly faster: the specification exists, the equipment is known and installation is a repeatable process.'
        }
      ],
      seo: {
        title: 'Video Surveillance and Networks for Retail | KMS',
        description: 'Retail systems: checkout area video coverage, one network and VPN across branches, access control, customer Wi-Fi and digital signage.'
      }
    },
    {
      slug: 'residential',
      icon: 'house',
      name: 'Residential',
      summary: 'Houses, villas, apartments and residential complexes: smart systems, security, video door entry and comfort automation.',
      hero: {
        eyebrow: 'Residential',
        headline: 'A home that gives you both comfort and peace of mind',
        description: 'We equip residential properties where lighting, climate, blinds, security and multimedia share one logic — and where the system makes sense to everyone in the household.',
        highlights: [
          'Smart scenarios and comfort',
          'Security sensors and video door entry',
          'Energy efficiency',
          'Infrastructure for residential complexes'
        ]
      },
      context: {
        heading: 'Where we focus',
        paragraphs: [
          'On a private property the decisive criterion is everyday use. A complicated system gets abandoned — so we write scenarios around the household’s real rhythm and keep physical switches in place.',
          'On the security side we offer one unified sensor logic: a water leak closes the valve, smoke turns the lights on, a door sensor sends a notification.',
          'In multi-unit developments the project splits in two: shared infrastructure (network, video, door entry, parking) and in-apartment systems, which stay independent of each other.'
        ]
      },
      priorities: {
        heading: 'Priorities',
        items: [
          {
            title: 'Everyday simplicity',
            description: 'Every main function stays available from a switch, not only from an app.',
            icon: 'lightbulb'
          },
          {
            title: 'Local reliability',
            description: 'Scenarios keep running without internet.',
            icon: 'cpu'
          },
          {
            title: 'Safety',
            description: 'Leaks, smoke and intrusion trigger automatic response and notification.',
            icon: 'siren'
          },
          {
            title: 'Aesthetics',
            description: 'Equipment fits the interior rather than dictating it.',
            icon: 'sparkles'
          }
        ]
      },
      solutions: {
        heading: 'Solutions',
        items: [
          {
            title: 'Smart home',
            description: 'Lighting, climate, blinds, irrigation and multimedia driven by scenarios.',
            icon: 'house'
          },
          {
            title: 'Security and video',
            description: 'Perimeter, grounds, entrance, video door entry and notifications.',
            icon: 'camera'
          },
          {
            title: 'Network',
            description: 'Stable Wi-Fi across the property, wired runs for critical devices.',
            icon: 'wifi'
          },
          {
            title: 'Complex infrastructure',
            description: 'Shared areas, parking, door entry and access control.',
            icon: 'building-2'
          }
        ]
      },
      scenarios: {
        heading: 'Typical projects',
        items: [
          {
            title: 'Full villa fit-out',
            description: 'From the renovation stage: wiring, consumer unit, controller, lighting, climate, security and audio.',
            icon: 'house'
          },
          {
            title: 'Apartment automation after renovation',
            description: 'Wireless solutions in wall switches and the consumer unit, with no new cabling.',
            icon: 'zap'
          },
          {
            title: 'Residential complex',
            description: 'Shared infrastructure: video surveillance, door entry, parking access and network.',
            icon: 'building-2'
          }
        ]
      },
      services: [
        'smart-home',
        'cctv',
        'access-control',
        'networking',
        'audio-visual'
      ],
      faq: [
        {
          q: 'At what stage should smart home planning start?',
          a: 'Ideally at electrical design stage, when wiring and the consumer unit can be done correctly from the outset. That said, complete wireless solutions exist for finished properties too.'
        },
        {
          q: 'How secure is remote access to the house?',
          a: 'Access runs over secured channels with individual accounts and, where supported, two-factor authentication. We never leave cameras exposed directly to the internet.'
        }
      ],
      seo: {
        title: 'Smart Home and Security for Residential Properties | KMS',
        description: 'Fit-out for houses, villas and residential complexes: smart systems, lighting and climate, security sensors, video door entry, surveillance and networking.'
      }
    }
  ],
  portfolio: {
    hero: {
      eyebrow: 'Projects',
      headline: 'Reference architectures for typical buildings',
      description: 'Below are descriptions of engineering solutions: which systems come together on a given type of site, in what architecture, and to what end.'
    },
    notice: {
      heading: 'What you are looking at',
      text: 'These are reference architectures and concept designs describing our engineering approach. They are not presented as work delivered for a specific client. Real projects appear on this site only with the client’s written consent.'
    },
    filtersAll: 'All',
    projects: [
      {
        slug: 'clinic-reference',
        kind: 'reference-architecture',
        name: 'Multi-specialty clinic',
        summary: 'Redundant network, zone separation, controlled access to critical spaces and video coverage that respects patient privacy.',
        industry: 'healthcare',
        services: [
          'it-infrastructure',
          'networking',
          'access-control',
          'cctv'
        ],
        scope: [
          'Structured cabling',
          'Server room with UPS and cooling',
          'Segmented network and Wi-Fi',
          'Access control on critical zones',
          'Video coverage of common areas'
        ],
        outcomes: [
          'Critical services stay up through a power interruption',
          'Clinical and guest traffic are isolated from each other',
          'Entry events are logged and linked to video'
        ],
        icon: 'stethoscope'
      },
      {
        slug: 'office-reference',
        kind: 'reference-architecture',
        name: 'Office building',
        summary: 'Workstation network, meeting rooms with one-touch scenarios, access control and automation of shared spaces.',
        industry: 'corporate',
        services: [
          'networking',
          'audio-visual',
          'access-control',
          'smart-building'
        ],
        scope: [
          'Cat6A cabling to workstations',
          'Wi-Fi coverage sized for density',
          'AV kits in meeting rooms',
          'Access control and attendance',
          'Lighting automation in shared zones'
        ],
        outcomes: [
          'Meetings start with one button, with no technical setup',
          'New workstations fit into the existing architecture',
          'Energy consumption falls through automatic modes'
        ],
        icon: 'building'
      },
      {
        slug: 'hotel-reference',
        kind: 'reference-architecture',
        name: 'Hotel',
        summary: 'High-density Wi-Fi, room automation, electronic access and video coverage of public spaces.',
        industry: 'hospitality',
        services: [
          'networking',
          'smart-building',
          'access-control',
          'cctv'
        ],
        scope: [
          'Wi-Fi in rooms, corridors and outdoor areas',
          'Room lighting, climate and key card switch',
          'Electronic locks and staff zones',
          'Video coverage at reception and entrances',
          'Background music zones'
        ],
        outcomes: [
          'Guests get a reliable connection in every area',
          'Vacant rooms drop automatically into economy mode',
          'Staff movement between zones is controlled'
        ],
        icon: 'bed-double'
      },
      {
        slug: 'villa-concept',
        kind: 'concept-design',
        name: 'Private villa',
        summary: 'A complete smart home: scenarios, climate, blinds, security sensors, video door entry and multi-room audio.',
        industry: 'residential',
        services: [
          'smart-home',
          'cctv',
          'networking',
          'audio-visual'
        ],
        scope: [
          'Local controller and consumer unit automation',
          'Lighting scenarios and dimming',
          'Zoned climate control',
          'Leak, smoke and door sensors',
          'Perimeter cameras and video door entry'
        ],
        outcomes: [
          'Scenarios keep running without internet',
          'Response to an incident is automatic',
          'Control available from switch, panel and phone'
        ],
        icon: 'house'
      },
      {
        slug: 'warehouse-reference',
        kind: 'reference-architecture',
        name: 'Warehouse complex',
        summary: 'Fibre backbones between buildings, warehouse Wi-Fi, perimeter video analytics and vehicle logging.',
        industry: 'industrial',
        services: [
          'networking',
          'cctv',
          'access-control',
          'it-infrastructure'
        ],
        scope: [
          'Fibre backbones and protected enclosures',
          'Wi-Fi coverage between racking',
          'Perimeter cameras with line-crossing detection',
          'Licence plate recognition and barriers',
          'Environmental monitoring at nodes'
        ],
        outcomes: [
          'Handheld terminals and scanners work reliably across the whole floor area',
          'Perimeter breaches are detected automatically',
          'Vehicle movements are recorded in a log'
        ],
        icon: 'factory'
      },
      {
        slug: 'retail-chain-reference',
        kind: 'reference-architecture',
        name: 'Retail chain',
        summary: 'A standardised branch: checkout video coverage, VPN to head office, stockroom access control and customer Wi-Fi.',
        industry: 'retail',
        services: [
          'cctv',
          'networking',
          'access-control',
          'managed-it'
        ],
        scope: [
          'A typical branch specification',
          'Checkout and stockroom video coverage',
          'VPN link to head office',
          'Isolated customer Wi-Fi',
          'Centralised video monitoring'
        ],
        outcomes: [
          'Opening a new branch becomes a repeatable process',
          'Every site is reachable from one interface',
          'Permissions are assigned per branch'
        ],
        icon: 'shopping-bag'
      }
    ],
    cta: {
      heading: 'Need something similar for your site?',
      description: 'Tell us about the building and we will prepare an architecture option with a concrete specification and stages.',
      action: 'Discuss your project'
    },
    seo: {
      title: 'Projects and Reference Architectures | KMS',
      description: 'Examples of engineering solutions for typical buildings: clinic, office, hotel, villa, warehouse and retail chain — with system scope and expected outcomes.'
    }
  },
  process: {
    hero: {
      eyebrow: 'How we work',
      headline: 'Six stages, each with a concrete deliverable',
      description: 'At every stage it is clear what is being done, what you receive and what comes next. That removes ambiguity and makes timelines predictable.',
      highlights: [
        'Documented stages',
        'Verifiable deliverables',
        'Handover with training'
      ]
    },
    intro: {
      heading: 'Why process matters',
      paragraphs: [
        'Most technical projects fail on coordination rather than equipment: work overlaps, decisions arrive late, and at handover it turns out part of the documentation was never produced.',
        'Our process is built to prevent exactly that. Every stage has a deliverable that you receive — and without which the next stage does not begin.'
      ]
    },
    steps: [
      {
        index: '01',
        title: 'Consultation and requirements analysis',
        description: 'We listen to the brief: what kind of site, what problem needs solving, what timeline and budget frame applies. We ask the questions that often have not been asked yet.',
        deliverables: [
          'A defined brief',
          'Initial direction',
          'Budget orientation'
        ],
        icon: 'compass'
      },
      {
        index: '02',
        title: 'Site survey and measurement',
        description: 'We study the site in person: existing infrastructure, route options, electrical provision, obstacles and risks. Where needed we take signal measurements.',
        deliverables: [
          'Technical site description',
          'List of constraints',
          'Measurement results'
        ],
        icon: 'ruler'
      },
      {
        index: '03',
        title: 'Design and specification',
        description: 'We prepare the architecture, diagrams and an equipment specification with alternatives. Stages, timelines and responsibilities are fixed at this point.',
        deliverables: [
          'Technical design and diagrams',
          'Equipment specification',
          'Works programme'
        ],
        icon: 'file-text'
      },
      {
        index: '04',
        title: 'Installation and integration',
        description: 'We carry out the works to programme: cabling, equipment installation, configuration and linking systems to each other.',
        deliverables: [
          'Installed systems',
          'Configuration',
          'Progress reports'
        ],
        icon: 'wrench'
      },
      {
        index: '05',
        title: 'Testing and handover',
        description: 'We test every line and function, close out defects and hand over the system with documentation and training.',
        deliverables: [
          'Test results',
          'As-built documentation',
          'Training and warranty'
        ],
        icon: 'clipboard-check'
      },
      {
        index: '06',
        title: 'Support and development',
        description: 'After handover we stay in contact: monitoring, preventive maintenance, consulting and staged expansion of the system.',
        deliverables: [
          'Support terms',
          'Periodic reports',
          'Development plan'
        ],
        icon: 'refresh-cw'
      }
    ],
    quality: {
      heading: 'Quality control',
      intro: 'Each stage carries its own verification points.',
      items: [
        {
          title: 'Line certification',
          description: 'Cable runs are tested after installation and the results recorded.',
          icon: 'cable'
        },
        {
          title: 'Functional testing',
          description: 'Every scenario is tested under real conditions, not only on the bench.',
          icon: 'check-circle'
        },
        {
          title: 'Load verification',
          description: 'Network and power are checked under simulated peak load where practical.',
          icon: 'gauge'
        },
        {
          title: 'Defect log',
          description: 'Every observation is recorded and closed out before handover.',
          icon: 'list-checks'
        }
      ]
    },
    documentation: {
      heading: 'What you receive at handover',
      intro: 'The handover package covers as standard:',
      items: [
        'As-built diagrams and topology',
        'Line and port register',
        'Equipment list with models and serial numbers',
        'Configuration record and credentials',
        'Test results',
        'Warranty terms and service contacts',
        'A short operating guide'
      ]
    },
    faq: [
      {
        q: 'How long does a project take?',
        a: 'Duration depends on the scale of the site and the volume of work. We give an orientation after the survey; during design the timeline is fixed in the programme, stage by stage.'
      },
      {
        q: 'Can we commission design work only?',
        a: 'Yes. We produce the technical design and specification, which can then be put out to tender or executed by another contractor.'
      },
      {
        q: 'What if we need a change mid-project?',
        a: 'Changes are a normal part of the process. They are recorded in writing: what changes, how it affects timeline and cost — and only then executed.'
      }
    ],
    seo: {
      title: 'Our Process — From Consultation to Support | KMS',
      description: 'How a KMS project runs: consultation, site survey, design and specification, installation and integration, testing and handover, ongoing support.'
    }
  },
  technologies: {
    hero: {
      eyebrow: 'Technologies',
      headline: 'Open standards, compatible platforms',
      description: 'We select technology on three criteria: does it fit the task, does it have long-term support, and can it be integrated with other systems.',
      highlights: [
        'Vendor-independent choices',
        'Open protocols preferred',
        'Long-term support'
      ]
    },
    intro: {
      heading: 'How we choose technology',
      paragraphs: [
        'Our approach is technology neutrality: we are under no obligation to propose a particular brand. That lets us fit the solution to the real requirement of the site rather than the other way round.',
        'We give preference to systems built on open standards — ONVIF in video, OSDP in access control, KNX and Matter in automation. That reduces the risk of finding yourself dependent on a single supplier when the system needs to grow.'
      ]
    },
    disclaimer: 'Listed below are technologies, protocols and platforms we work with or ensure compatibility with. Vendor names are given only to describe the ecosystem and do not indicate official partnership, distribution or authorisation.',
    diagram: {
      heading: 'How the systems fit together',
      description: 'A building’s technology stack divides into four layers. The bottom one is the physical foundation, the top one is unified control. Each layer rests on the one below it: weak cabling undermines even the smartest scenario.',
      alt: 'Four-layer diagram: physical layer, network layer, systems layer and management layer, connected to each other',
      layers: [
        {
          title: 'Management layer',
          items: [
            'Unified control',
            'Scenarios',
            'Monitoring',
            'Reporting'
          ]
        },
        {
          title: 'Systems layer',
          items: [
            'Video',
            'Access',
            'Climate',
            'Lighting',
            'Audio visual'
          ]
        },
        {
          title: 'Network layer',
          items: [
            'Switching',
            'Wi-Fi',
            'Segmentation',
            'Routing'
          ]
        },
        {
          title: 'Physical layer',
          items: [
            'Structured cabling',
            'Server room',
            'Power and UPS'
          ]
        }
      ]
    },
    domains: [
      {
        title: 'Network and connectivity',
        description: 'The foundation of network architecture: switching, routing and wireless access.',
        icon: 'network',
        items: [
          'L2/L3 managed switching',
          'PoE / PoE+ power',
          'VLAN and QoS',
          'Wi-Fi 5 / Wi-Fi 6',
          'VPN and secured channels',
          'SNMP monitoring'
        ]
      },
      {
        title: 'Cabling infrastructure',
        description: 'The physical layer everything else stands on.',
        icon: 'cable',
        items: [
          'Cat6 / Cat6A copper',
          'Single-mode and multi-mode fibre',
          'LSZH cables',
          'Patch panels and organisers',
          'Line certification',
          '19" rack systems'
        ]
      },
      {
        title: 'Video and analytics',
        description: 'Video surveillance built on open protocols.',
        icon: 'camera',
        items: [
          'ONVIF-compatible IP cameras',
          'RTSP streams',
          'H.264 / H.265 encoding',
          'NVR and server-based recording',
          'Video analytics and line crossing',
          'Licence plate recognition'
        ]
      },
      {
        title: 'Access and identification',
        description: 'Access control components and interfaces.',
        icon: 'fingerprint',
        items: [
          'OSDP and Wiegand',
          'MIFARE / DESFire cards',
          'Biometric terminals',
          'Mobile credentials',
          'Magnetic locks',
          'Turnstiles and barriers'
        ]
      },
      {
        title: 'Automation',
        description: 'Smart home and smart building protocols.',
        icon: 'house',
        items: [
          'KNX',
          'Matter and Thread',
          'Zigbee',
          'Z-Wave',
          'Modbus',
          'DALI lighting',
          'BACnet'
        ]
      },
      {
        title: 'Audio visual systems',
        description: 'Signal transport, processing and control.',
        icon: 'monitor-play',
        items: [
          'HDMI and HDBaseT',
          'AV over IP',
          'DSP audio processing',
          'Ceiling microphone arrays',
          'Wireless presentation',
          'Digital signage management'
        ]
      }
    ],
    selection: {
      heading: 'Selection criteria',
      intro: 'Every technology decision answers these four questions:',
      items: [
        {
          title: 'Does it fit the task',
          description: 'Functionality must cover the actual need — no less, and no surplus you pay for without using.',
          icon: 'target'
        },
        {
          title: 'What is the support outlook',
          description: 'The platform needs updates, available components and a sensible lifecycle.',
          icon: 'clock'
        },
        {
          title: 'How integrable is it',
          description: 'Open protocol support determines whether the system can be connected to anything else.',
          icon: 'link'
        },
        {
          title: 'What does it cost to run',
          description: 'We count not just purchase price but licences, servicing and the cost of expansion.',
          icon: 'gauge'
        }
      ]
    },
    standards: {
      heading: 'Standards and practice',
      intro: 'Our design work follows established industry approaches:',
      groups: [
        {
          title: 'Infrastructure',
          items: [
            'Structured cabling planning',
            'A single line labelling scheme',
            'Rack organisation and airflow',
            'Power calculation and redundancy'
          ]
        },
        {
          title: 'Network',
          items: [
            'Segmentation by system',
            'Access policies and authentication',
            'Configuration backups',
            'Monitoring and alerting'
          ]
        },
        {
          title: 'Security',
          items: [
            'Mandatory replacement of factory passwords',
            'Individual accounts and event logging',
            'Remote access over secured channels only',
            'A software update policy'
          ]
        }
      ]
    },
    faq: [
      {
        q: 'Are you an official partner of a particular brand?',
        a: 'The technologies listed on this site describe the ecosystems we work with. If a specific project requires authorised supply or a vendor warranty, we clarify that in advance and state it openly.'
      },
      {
        q: 'Can you integrate with an existing system?',
        a: 'In most cases yes, provided the existing equipment supports open protocols. We begin with an audit: which interfaces the system offers and what limitations exist.'
      },
      {
        q: 'Why do you favour open standards?',
        a: 'A closed system limits your options: when it comes time to expand, you have to go back to the same supplier at their prices. Open standards reduce that dependence and extend the system’s useful life.'
      }
    ],
    seo: {
      title: 'Technologies and Compatibility — ONVIF, KNX, Matter | KMS',
      description: 'The technology ecosystem we work with: network standards, cabling systems, ONVIF video, OSDP access control, KNX/Matter/Zigbee automation and AV platforms.'
    }
  },
  faq: {
    hero: {
      eyebrow: 'Frequently asked questions',
      headline: 'Answers worth having before the project starts',
      description: 'We have collected the questions we are asked most often before work begins. If yours is not here, get in touch.'
    },
    categories: [
      {
        title: 'Getting started',
        items: [
          {
            q: 'How does working with you begin?',
            a: 'The first step is a consultation, by phone or in person. We ask about the site and the brief and give an initial orientation. Then we survey the site and prepare a specification.'
          },
          {
            q: 'Is the initial consultation chargeable?',
            a: 'The initial consultation and site survey are free. Cost arises when it comes to detailed design or works — and that is agreed in advance.'
          },
          {
            q: 'Do you work on small sites?',
            a: 'Yes. We work on large sites as well as apartments, small offices and individual systems. The approach is the same — only the scale changes.'
          }
        ]
      },
      {
        title: 'Cost and timelines',
        items: [
          {
            q: 'How is the price made up?',
            a: 'The price comprises equipment, installation works, configuration and design. Each line item appears separately in the specification, which makes comparison and discussion of alternatives straightforward.'
          },
          {
            q: 'Can the work be split into stages?',
            a: 'Yes, and often that is the most sensible route. We start with the base — cabling and network — and add systems over time, with the architecture designed for expansion from the outset.'
          },
          {
            q: 'What affects the timeline?',
            a: 'The main factors are site readiness, equipment lead times and coordination with other contractors. All of this is fixed in the programme at design stage.'
          }
        ]
      },
      {
        title: 'Technical questions',
        items: [
          {
            q: 'Can you work with an existing system?',
            a: 'Yes. We begin with an audit: what is installed, what condition it is in and what can be retained. Partial modernisation is often more effective than full replacement.'
          },
          {
            q: 'Who owns access to the system after handover?',
            a: 'All credentials, passwords and configurations are handed to you. Our principle is that a client should never be technically locked in to a supplier.'
          },
          {
            q: 'What happens during the warranty period?',
            a: 'Under warranty we correct installation and configuration defects. Equipment warranty follows the manufacturer’s terms — we manage that process on your behalf.'
          }
        ]
      },
      {
        title: 'Support',
        items: [
          {
            q: 'What does support after handover cover?',
            a: 'Several formats are available: one-off call-outs, periodic preventive maintenance, or full managed service with monitoring and agreed response times.'
          },
          {
            q: 'Do we need an in-house IT specialist?',
            a: 'For small and mid-sized organisations managed services often replace the internal resource entirely. In larger organisations we work alongside the in-house team with responsibility for specific systems.'
          }
        ]
      }
    ],
    cta: {
      heading: 'Did not find your answer?',
      description: 'Ask us directly — by phone or through the form. Consultation is free.',
      action: 'Ask a question'
    },
    seo: {
      title: 'Frequently Asked Questions | KMS',
      description: 'Answers on getting started, how pricing is built up, project timelines, working with existing systems, handover and ongoing support options.'
    }
  },
  contact: {
    hero: {
      eyebrow: 'Contact',
      headline: 'Tell us about the site',
      description: 'The more you tell us about the building, the more specific our answer will be. Floor area, purpose, the systems you want and an approximate timeline are enough to start.'
    },
    channels: {
      heading: 'Direct contact',
      description: 'For urgent matters, calling is the fastest route.'
    },
    formHeading: 'Project enquiry',
    formDescription: 'Fill in the form and we will get back to you during working hours. Fields marked * are required.',
    serviceOptions: [
      {
        value: 'it-infrastructure',
        label: 'IT infrastructure'
      },
      {
        value: 'networking',
        label: 'Network infrastructure'
      },
      {
        value: 'cctv',
        label: 'Video surveillance'
      },
      {
        value: 'access-control',
        label: 'Access control'
      },
      {
        value: 'smart-home',
        label: 'Smart home'
      },
      {
        value: 'smart-building',
        label: 'Smart building'
      },
      {
        value: 'audio-visual',
        label: 'Audio visual systems'
      },
      {
        value: 'managed-it',
        label: 'Managed IT services'
      },
      {
        value: 'other',
        label: 'Other / not sure yet'
      }
    ],
    expectations: {
      heading: 'What happens next',
      items: [
        {
          title: 'A reply and clarifying questions',
          description: 'We review the enquiry and ask the questions that affect the solution.',
          icon: 'mail'
        },
        {
          title: 'Site survey',
          description: 'We agree a time to visit and study the conditions in person.',
          icon: 'map-pin'
        },
        {
          title: 'Specification and options',
          description: 'We prepare solution options, cost and stages.',
          icon: 'file-text'
        }
      ]
    },
    hours: {
      heading: 'When to reach us',
      lines: [
        'Monday – Saturday',
        'During working hours'
      ],
      note: 'Terms for urgent matters are set individually under an active support agreement.'
    },
    seo: {
      title: 'Contact — Discuss Your Project | KMS',
      description: 'Get in touch: +995 597 43 97 97, sales@kms.ge, support@kms.ge. Tell us about your site and we will prepare a technical solution.'
    }
  },
  legal: [
    {
      slug: 'privacy',
      title: 'Privacy policy',
      updated: 'Updated: 2026-08-07',
      intro: 'This policy describes what data we collect on this website, what we use it for, and how to contact us about it.',
      sections: [
        {
          heading: 'Who processes your data',
          paragraphs: [
            'The controller of personal data submitted through this website is KMS. For any question, write to sales@kms.ge or call +995 597 43 97 97.'
          ]
        },
        {
          heading: 'What data we collect',
          paragraphs: [
            'We collect only the information you provide voluntarily when completing the contact form or otherwise getting in touch with us.'
          ],
          bullets: [
            'Full name',
            'Company or site name',
            'Email address',
            'Phone number',
            'Any information you supply about the project'
          ]
        },
        {
          heading: 'Why we process it, and on what basis',
          paragraphs: [
            'We use the data you provide solely to respond to your enquiry, hold a consultation and prepare a proposal.',
            'The basis for processing is your consent, given when you submit the form, together with steps taken at your own request before entering into a contract. You can withdraw that consent at any time — a message to us is enough.',
            'We do not use your data for marketing mailings without separate consent, and we do not sell it to third parties.'
          ]
        },
        {
          heading: 'Data retention',
          paragraphs: [
            'We retain data for as long as needed to respond to your enquiry and consider possible cooperation — no longer than 24 months from our last contact. On request, data will be deleted sooner.'
          ]
        },
        {
          heading: 'Third-party services and international transfers',
          paragraphs: [
            'Messages sent via the contact form are processed by Web3Forms (Nexinvent LLC), which delivers them to our email. That service receives only the data you enter into the form and acts under its own privacy policy.',
            'Because Web3Forms operates servers outside Georgia, submitting the form involves a cross-border transfer of your data. It happens only with your consent and only to the extent of what you typed in.',
            'The site uses Plausible Analytics for aggregate visit statistics. It sets no cookies, stores nothing on your device and collects no personal data, so an individual visitor cannot be identified.',
            'Typefaces are served from this website itself. No fonts or advertising trackers are loaded from third-party servers.'
          ]
        },
        {
          heading: 'Your rights',
          paragraphs: [
            'You have the right to request information about the data held about you, and to have it corrected, updated, blocked, deleted or destroyed, as well as to withdraw consent you previously gave.',
            'If you believe your data is being processed unlawfully, you may complain to the Personal Data Protection Service of Georgia (personaldata.ge) or apply to a court.'
          ]
        },
        {
          heading: 'Contact',
          paragraphs: [
            'For any question about this policy, or to exercise a right, write to us: sales@kms.ge'
          ]
        }
      ],
      seo: {
        title: 'Privacy Policy | KMS',
        description: 'How KMS processes the data you submit through the contact form, what we use it for, how long we keep it, who it is shared with and what rights you have.'
      }
    },
    {
      slug: 'terms',
      title: 'Terms of use',
      updated: 'Updated: 2026-08-07',
      intro: 'These terms apply to the use of this website. By visiting the site you accept the terms set out below.',
      sections: [
        {
          heading: 'Purpose of the site',
          paragraphs: [
            'This website is an informational resource about the services of KMS. The information on it is general in nature and does not constitute a binding commercial offer.'
          ]
        },
        {
          heading: 'Technical information',
          paragraphs: [
            'The technical solutions and reference architectures described on this site are general examples. The solution appropriate to a specific site is determined after individual assessment.',
            'Material in the projects section is marked as reference architecture or concept design and is not presented as work delivered for a specific client unless expressly stated.'
          ]
        },
        {
          heading: 'Third-party trademarks',
          paragraphs: [
            'The names of technologies, protocols and manufacturers mentioned on this site belong to their respective owners. They are referenced solely to describe technology compatibility and do not indicate official partnership, authorisation or sponsorship.'
          ]
        },
        {
          heading: 'Intellectual property',
          paragraphs: [
            'The content, text and visual material of this site belong to KMS unless otherwise stated. Commercial use of this material without prior written consent is not permitted.'
          ]
        },
        {
          heading: 'Limitation of liability',
          paragraphs: [
            'We aim to keep the information on this site accurate and current, but we accept no responsibility for decisions made solely on the basis of site material without individual consultation.'
          ]
        },
        {
          heading: 'Contact',
          paragraphs: [
            'For any questions: sales@kms.ge'
          ]
        }
      ],
      seo: {
        title: 'Terms of Use | KMS',
        description: 'Terms of use for the KMS website: what the site is for, the status of the technical information on it, third-party trademarks and limits of liability.'
      }
    },
    {
      slug: 'cookies',
      title: 'Cookies',
      updated: 'Updated: 2026-08-07',
      intro: 'This page describes whether the website uses cookies and similar technologies.',
      sections: [
        {
          heading: 'Cookies',
          paragraphs: [
            'This site sets no cookies at all and writes nothing to your browser\'s local storage. The language you are reading is determined by the address of the page, not by anything stored on your device.'
          ]
        },
        {
          heading: 'Cookieless analytics',
          paragraphs: [
            'We use Plausible Analytics for aggregate visit statistics. It is built specifically not to set cookies and not to store anything on your device: no visitor identifier is assigned, so no individual can be recognised here or followed across other sites.',
            'What we see is aggregate only: which pages are opened, from which country, and on which type of device. That is why there is no cookie banner to accept — there is nothing to consent to.'
          ]
        },
        {
          heading: 'External resources',
          paragraphs: [
            'The only data that ever leaves this site is what you type into the contact form, which is passed to Web3Forms to deliver it to our inbox. Everything else — typefaces included — is served from this website.'
          ]
        },
        {
          heading: 'Browser settings',
          paragraphs: [
            'Cookies and local storage can be managed from your browser settings. Disabling them does not affect the core functionality of this site.'
          ]
        },
        {
          heading: 'Changes',
          paragraphs: [
            'If cookies or any other technology that stores data on your device is added in future, this page will be updated accordingly.'
          ]
        }
      ],
      seo: {
        title: 'Cookies | KMS',
        description: 'The KMS website sets no cookies and stores nothing in your browser. This page explains what that means and which external service receives contact form data.'
      }
    }
  ]
};
