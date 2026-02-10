# Frontend Design System

This document outlines the visual language, design tokens, and UI components used in the Sentinel platform. It serves as a reference for maintaining a consistent, premium, and modern user experience.

## Design Philosophy & Principles

Sentinel's design language is built to be **Modular**, **Immersive**, and **Data-Centric**. These principles should guide both this project and any future "Sentinel-family" applications.

### 1. Immersive Depth (The Glassmorphism Core)
We use depth to create a sense of focus. 
- **Rule of Thumb**: Backgrounds should be dark and static, while interactive elements should feel like they are floating on a plane above the data.
- **Implementation**: Avoid solid colors for cards; use `@glass-blur` and gradients to let the background "breathe" through the UI.

### 2. High-Performance Branding
The UI should look like a cockpit or a command center.
- **Typography**: Clean, geometric sans-serif (like Outfit) for all headers to convey precision.
- **Gradients**: Use "Cyber" colors (Indigos, Purples, Cyan) to represent speed and data flow.
- **Grit**: Borders should be very thin (`1px` with low alpha) to feel specialized and "sharp".

### 3. Reactive Feedback (Micro-animations)
A premium app is an "alive" app.
- Every state change (hover, active, error) must have a transition (`var(--transition)`).
- Use **Glow** as a primary indicator of focus or "health".

## Brand Identity

## Design Tokens

### Color Palette
The colors are managed via CSS variables in `assets/styles/app.css`.

| Token | Value | Sample | Usage |
|-------|-------|--------|-------|
| `--bg-color` | `#0d0f14` | ![#0d0f14](https://via.placeholder.com/15/0d0f14?text=+) | Main background |
| `--surface-color` | `#161a23` | ![#161a23](https://via.placeholder.com/15/161a23?text=+) | Card and sidebar backgrounds |
| `--primary-color` | `#6366f1` | ![#6366f1](https://via.placeholder.com/15/6366f1?text=+) | Primary actions, branding |
| `--secondary-color`| `#a855f7` | ![#a855f7](https://via.placeholder.com/15/a855f7?text=+) | Accent and gradient end |
| `--success-color` | `#10b981` | ![#10b981](https://via.placeholder.com/15/10b981?text=+) | Uptime, success states |
| `--danger-color`  | `#ef4444` | ![#ef4444](https://via.placeholder.com/15/ef4444?text=+) | Downtime, errors |

### Typography
- **Font Family**: [Outfit](https://fonts.google.com/specimen/Outfit) (Google Fonts)
- **Scale**:
  - `h1`: 2.5rem, Semi-bold.
  - `h2`: 1.5rem, Semi-bold.
  - `p`: 1rem, Regular/Light.
  - `text-xs`: 0.75rem.

### Elevation & Effects
Using layered shadows and blurs to simulate elevation in a dark environment.
- **Glass Blur**: `12px`
- **Radius**: `12px` (Medium), `16px` (Large)
- **Primary Glow**: `rgba(99, 102, 241, 0.4)`

## Core Components

### 1. Glass Card (`.glass-card`)
The fundamental building block for dashboard sections.
- **Background**: Linear gradient (transparent white to transparent).
- **Effect**: Backdrop blur and subtle border.

### 2. Primary Button (`.btn`)
High-impact call-to-action buttons. **Updated to "Compact & Rounded" style.**
- **Dimensions**: `padding: 0.5rem 1rem`, `font-size: 0.875rem` (14px).
- **radius**: `var(--radius-lg)` (16px).
- **Style**: Indigo gradient with a distinct glow effect.
- **Interaction**: Scales up slightly on hover and has a "radial sweep" animation.

### 3. Badges (`.badge`)
Used for status and channel indicators.
- **Styles**: `.badge-success`, `.badge-danger`, `.badge-[channel-name]`.
- **Implementation**: Low-opacity background with high-opacity text.

## Animations

Sentinel uses CSS Keyframes for life-like movement:
- `float`: Used for hero visuals to give an anti-gravity feel.
- `pulse`: Subtle background glow movements.
- `fadeIn` / `scaleUp`: Standard modal and element transitions.

## Form Validation
As of the latest update, validation errors follow these rules:
- **Invalid Input**: `.glass-input.invalid` (Red border, red glow, subtle red background).
- **Error Text**: `.text-error` (High contrast light-red text, bolded for readability).

## Form Guidelines

### Input Helpers
Use `<small class="text-muted">` below inputs to clarify ambiguous fields (e.g., "Interval", "Timeout").
- **Example**: "How often to check the URL."

### Standardized Selects
Avoid free-form text inputs for standard data sets.
- **Status Codes**: Use a `<select>` with standard HTTP codes (200, 301, 404, 500) instead of a numeric input.

## Future Project Guidelines (Scalability)

To reuse this design language in future projects, follow these structural rules:

### Component Extraction
- **Atomic approach**: Keep CSS variables centralized (`app.css`) so they can be swapped for a new "Skin" without rewriting component logic.
- **Theme Support**: The system is currently "Dark Mode First", but the use of variables allows for an "Obsidian" or "Deep Blue" theme swap by only changing the values in `:root`.

### Expanding the Palette
When adding new features or projects:
1. **Primary Accent**: Should be a gradient to maintain the "dynamic" feel.
2. **Surface Elevation**: Maintain the 3-tier hierarchy:
   - Level 0: `--bg-color` (Static)
   - Level 1: `--surface-color` (Containers)
   - Level 2: `--glass-bg` (Interactive / Modals)

### Responsive Evolution
The design uses a `1600px` max-width container. Future projects should maintain this "Ultra-wide optimization" while ensuring the mobile view collapses into a "Tab-based" or "Bottom-sheet" navigation style to preserve the premium feel on touch devices.
