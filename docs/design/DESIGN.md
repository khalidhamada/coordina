# Design System Document: High-End Project Editorial

## 1. Overview & Creative North Star
### Creative North Star: "The Architectural Editor"
This design system moves beyond the utility of a standard WordPress plugin and enters the realm of a bespoke productivity suite. The "Architectural Editor" philosophy treats the project management interface not as a grid of boxes, but as a curated workspace. 

We break the "template" look by utilizing heavy intentionality in white space, a high-contrast typography scale, and a "layered paper" approach to depth. By prioritizing atmospheric breathing room and subtle tonal shifts over rigid borders, we create an experience that feels quiet, authoritative, and premium.

---

## 2. Colors & Surface Philosophy
The palette is anchored by a sophisticated neutral base and a high-energy indigo-purple, creating a workspace that is both calm and focused.

### Core Tokens
- **Background:** `#f8f9fa` (The canvas)
- **Primary:** `#4a4bd7` (The action)
- **Surface-Lowest:** `#ffffff` (The focused content)
- **Surface-Container:** `#ebeef0` (The structural foundation)

### The "No-Line" Rule
**Strict Directive:** 1px solid borders are prohibited for sectioning or layout containment. 
Boundaries must be defined through background color shifts. For example, a card (using `surface-container-lowest`) should sit atop a section (using `surface-container-low`) to define its perimeter. The eye should perceive the edge through the shift in luminance, not a technical stroke.

### The Glass & Gradient Rule
To prevent the UI from feeling flat or "default," floating elements (like modals or dropdowns) should utilize **Glassmorphism**:
- **Backdrop:** `surface` at 80% opacity.
- **Effect:** `backdrop-blur: 12px`.
- **Accent:** Main CTAs or active navigation states should use a subtle linear gradient from `primary` to `primary-container` at a 135-degree angle to add "visual soul."

---

## 3. Typography
We use a dual-font strategy to balance editorial character with functional clarity.

### The Typographic Pair
- **Display & Headlines (Manrope):** A geometric sans-serif with a modern, high-end architectural feel. Use this for page titles and section headers to establish a premium tone.
- **Body & Labels (Inter):** The industry standard for readability. Use this for all data-heavy contexts, task titles, and inputs.

### Key Scales
- **Headline-LG (Manrope, 2rem):** Used for primary page titles (e.g., "Advanced Modules").
- **Title-SM (Inter, 1rem, Medium/Semi-bold):** Used for card titles and navigation items.
- **Label-MD (Inter, 0.75rem, Bold):** For all caps or status text to provide an "official" meta-data feel.

---

## 4. Elevation & Depth
Depth in this design system is achieved through **Tonal Layering** rather than heavy shadows.

### The Layering Principle
Think of the UI as a series of stacked, fine-milled paper. 
1. **Level 0 (Base):** `surface` (#f8f9fa)
2. **Level 1 (Sections):** `surface-container-low` (#f1f4f5)
3. **Level 2 (Cards):** `surface-container-lowest` (#ffffff)

### Ambient Shadows
Shadows are reserved only for "floating" elements that exist above the page flow (e.g., popovers). 
- **Specification:** `box-shadow: 0 12px 32px -4px rgba(45, 51, 53, 0.06);`
- **The Ghost Border:** If high-contrast accessibility is required, use a 1px border with `outline-variant` at **15% opacity**. Never use 100% opaque borders.

---

## 5. Components

### Cards & Modules
Cards are the primary container. 
- **Style:** `bg-surface-container-lowest`, `border-radius: lg (1rem)`.
- **Constraint:** No dividers. Use `body-md` for description text and `spacing-xl` for internal padding to separate content blocks.

### Primary Buttons
- **Style:** Gradient fill (`primary` to `primary-container`), `border-radius: md (0.75rem)`.
- **Interaction:** On hover, a subtle `primary-dim` shadow adds a sense of "press-ability."

### Status Indicators (Chips)
- **Style:** Use a "soft-wash" approach. 
- **Enabled/Positive:** Background `primary-container` (at 20% opacity) with `primary` text.
- **Neutral/Disabled:** Background `surface-variant` with `on-surface-variant` text.
- **Shape:** `rounded-full` for a modern, pill-like aesthetic.

### Input Fields
- **Style:** `bg-surface-container-low`, `none` border.
- **Active State:** A `primary` 2px bottom-accent or a "Ghost Border" of `primary` at 40% opacity.

---

## 6. Do’s and Don’ts

### Do
- **Do** use asymmetrical margins to create an editorial feel in dashboards.
- **Do** allow content to "breathe" by using significantly more white space than standard WordPress plugins.
- **Do** use `tertiary` (#745479) for low-priority feature accents to provide a sophisticated color counterpoint to the purple.

### Don’t
- **Don’t** use black (#000000) for text. Use `on-surface` (#2d3335) to maintain a soft, high-end contrast.
- **Don’t** use standard 4px or 8px corners. Lean into the `lg (1rem)` and `xl (1.5rem)` tokens for a friendlier, modern feel.
- **Don’t** use horizontal lines to separate list items. Use a `surface-container-high` background hover state and vertical padding instead.