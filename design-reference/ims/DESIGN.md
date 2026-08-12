---
name: IMS - ระบบบริหารจัดการการฝึกงาน
colors:
  surface: '#fcf8ff'
  surface-dim: '#dcd8e5'
  surface-bright: '#fcf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f5f2ff'
  surface-container: '#f0ecf9'
  surface-container-high: '#eae6f4'
  surface-container-highest: '#e4e1ee'
  on-surface: '#1b1b24'
  on-surface-variant: '#464555'
  inverse-surface: '#302f39'
  inverse-on-surface: '#f3effc'
  outline: '#777587'
  outline-variant: '#c7c4d8'
  surface-tint: '#4d44e3'
  primary: '#3525cd'
  on-primary: '#ffffff'
  primary-container: '#4f46e5'
  on-primary-container: '#dad7ff'
  inverse-primary: '#c3c0ff'
  secondary: '#4648d4'
  on-secondary: '#ffffff'
  secondary-container: '#6063ee'
  on-secondary-container: '#fffbff'
  tertiary: '#7e3000'
  on-tertiary: '#ffffff'
  tertiary-container: '#a44100'
  on-tertiary-container: '#ffd2be'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e2dfff'
  primary-fixed-dim: '#c3c0ff'
  on-primary-fixed: '#0f0069'
  on-primary-fixed-variant: '#3323cc'
  secondary-fixed: '#e1e0ff'
  secondary-fixed-dim: '#c0c1ff'
  on-secondary-fixed: '#07006c'
  on-secondary-fixed-variant: '#2f2ebe'
  tertiary-fixed: '#ffdbcc'
  tertiary-fixed-dim: '#ffb695'
  on-tertiary-fixed: '#351000'
  on-tertiary-fixed-variant: '#7b2f00'
  background: '#fcf8ff'
  on-background: '#1b1b24'
  surface-variant: '#e4e1ee'
  status-success: '#10B981'
  status-warning: '#F59E0B'
  status-error: '#EF4444'
  status-inactive: '#94A3B8'
  bg-light: '#F8FAFC'
  bg-dark: '#0F172A'
  surface-dark: '#1E293B'
  text-dark-mode: '#E2E8F0'
typography:
  display-metrics:
    fontFamily: Plus Jakarta Sans
    fontSize: 40px
    fontWeight: '700'
    lineHeight: 48px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Be Vietnam Pro
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Be Vietnam Pro
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Be Vietnam Pro
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
  metadata:
    fontFamily: Be Vietnam Pro
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 16px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  touch-target: 44px
  gutter: 1rem
  margin-mobile: 1.25rem
  margin-desktop: 2rem
  sidebar-width: 280px
  bottom-nav-height: 64px
---

## Brand & Style

The design system is engineered to bridge the gap between academic formalness and the intuitive, high-engagement feel of a modern consumer mobile app. The brand personality is **Professional, Encouraging, and Accessible**, moving away from "bureaucratic stiffness" toward a supportive mentorship tone. 

The aesthetic is **Corporate / Modern** with a **Tactile** twist:
- **Depth & Dimension:** A focus on "app-like" surfaces that feel touchable and layered.
- **Micro-copy:** Conversational Thai greetings (e.g., "สวัสดี [ชื่อ]", "ยินดีด้วยคุณทำสำเร็จ!") to foster a warm, human-centric environment.
- **Motion-Driven:** The UI feels alive through scale-down press effects, staggered entrance animations, and celebratory confetti for milestone completions, reinforcing the "Gamification" aspect of student internship logs.

## Colors

The palette centers on a **Primary Indigo** core, symbolizing trust and institutional reliability, balanced by a vibrant set of semantic colors for instant status communication.

- **Dual-Theme Architecture:**
  - **Light Mode:** Uses `bg-light` (#F8FAFC) for backgrounds with soft shadows to define elevation.
  - **Dark Mode:** Swaps to a deep slate `bg-dark` (#0F172A) where elevation is communicated via subtle 1px borders using `surface-dark` or low-opacity whites.
- **Semantic Logic:** Green for approvals/on-time status, Amber for pending/leave requests, and Red for rejections or overdue tasks.
- **Gradients:** Used sparingly for hero cards and "milestone" states to inject energy into the student experience.

## Typography

The system utilizes **Plus Jakarta Sans** for headlines and metrics to provide a friendly, optimistic geometric feel, while **Be Vietnam Pro** handles body text and Thai script with exceptional legibility and modern proportions.

- **Thai Optimization:** Line heights are set 20-30% taller than standard Latin settings to accommodate Thai vowel and tone markers without clipping.
- **Scale:** Large "Display Metrics" are reserved for dashboard stats (e.g., hours completed, days remaining) to provide immediate visual feedback.
- **Mobile Readability:** Body text is locked to a minimum of 16px to prevent iOS auto-zoom on input focus.

## Layout & Spacing

The layout philosophy follows a **Mobile-First Responsive** model that transitions from a bottom-anchored app experience to a professional desktop dashboard.

- **Desktop:** Features a fixed 12-column grid with a persistent left sidebar. The sidebar collapses to an icon-only rail on tablets.
- **Mobile:** Adopts a native-app structure with a fixed bottom navigation bar (4-5 icons) and a floating action button (FAB) for primary tasks like "Check-in" or "Daily Log."
- **Touch-First Spacing:** All interactive elements adhere to a 44px minimum touch target.
- **Safe Areas:** Padding is increased at the bottom of mobile screens to prevent interference with OS-level home indicators.

## Elevation & Depth

Visual hierarchy is established through a mode-dependent strategy:

- **Light Mode (Tonal Layers & Shadows):** Uses highly diffused, low-opacity shadows (e.g., `box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05)`) to make cards appear as if floating over the `bg-light` surface.
- **Dark Mode (Subtle Outlines):** Shadows are largely disabled in favor of 1px borders (`#1E293B`) and slightly lighter surface fills for elevated cards to maintain clarity and prevent "muddy" dark UI.
- **Intervention Layers:** Modals and expanded FAB menus use a `backdrop-filter: blur(8px)` with a semi-transparent overlay to keep the user focused while maintaining context of the underlying screen.

## Shapes

The design uses a consistent **Rounded** (0.5rem base) language to reinforce the friendly and modern brand persona. 

- **Cards & Inputs:** Use the `rounded-lg` (1rem / 16px) setting to create a soft, container-like feel typical of high-end mobile apps.
- **Interactive Elements:** Buttons and pills use `rounded-xl` or full "pill" shapes for primary navigation and status badges, signaling clear clickability.
- **Signature Pad:** The digital canvas is the only element allowed to have sharper corners (Soft - 4px) to maximize the drawing area.

## Components

- **Buttons:** Feature a subtle scale-down (`transform: scale(0.97)`) micro-interaction on press. Primary buttons use indigo gradients; secondary buttons use ghost styles with borders.
- **Bottom Navigation (Mobile):** Uses outline icons that transition to filled Indigo states when active, accompanied by a small dot indicator.
- **Attendance Cards:** High-contrast cards featuring a "Radar" pulse animation when the GPS is active, ensuring students know their location is being verified.
- **Status Chips:** Pill-shaped badges with low-opacity backgrounds (e.g., 10% green background with 100% green text) for status indicators like "อนุมัติแล้ว" (Approved).
- **Skeleton Loaders:** Shimmering gray gradients that match the exact shape of cards and list items to reduce perceived latency during API fetches.
- **Input Fields:** Utilize floating labels and clear error states with a "shake" animation for invalid submissions.