---
name: Hotel Ads /staff
description: La hoja de turno de un hotel, de 00:00 a 23:59, impresa en papel cálido con tinta marina y una sola hora naranja.
colors:
  pared: "#e2e0dc"
  tarjeta: "#fcfbf8"
  pauta: "#d8d2c6"
  tinta: "#1f3d52"
  tinta-2: "#4b5560"
  marca: "#284b62"
  marca-hondo: "#1c3648"
  hora: "#c2531b"
  hora-hondo: "#9e3f10"
  hilo: "#ece7dd"
  burbuja-asistente: "#dcefd9"
  burbuja-recepcion: "#f6e7c8"
typography:
  display:
    fontFamily: "Archivo, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "clamp(4.5rem, 14vw, 6rem)"
    fontWeight: 850
    lineHeight: 0.85
    letterSpacing: "-0.04em"
    fontVariation: "'wdth' 72"
  clock:
    fontFamily: "Archivo, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "3.5rem"
    fontWeight: 800
    lineHeight: 1
    letterSpacing: "-0.03em"
    fontFeature: "'tnum'"
    fontVariation: "'wdth' 70"
  headline:
    fontFamily: "Archivo, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "clamp(1.75rem, 3.4vw, 2.6rem)"
    fontWeight: 750
    lineHeight: 1.06
    letterSpacing: "-0.025em"
    fontVariation: "'wdth' 86"
  title:
    fontFamily: "Archivo, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "clamp(1.6rem, 3.6vw, 2.25rem)"
    fontWeight: 700
    lineHeight: 1.08
    letterSpacing: "-0.02em"
    fontVariation: "'wdth' 88"
  body:
    fontFamily: "Archivo, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "1.075rem"
    fontWeight: 400
    lineHeight: 1.65
  label:
    fontFamily: "Archivo, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "0.8rem"
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: "0.08em"
  caption:
    fontFamily: "Archivo, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "0.8rem"
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: "0.01em"
rounded:
  focus: "4px"
  plate: "12px"
  bubble: "0.9rem"
  phone: "2rem"
  device: "2.4rem"
  pill: "999px"
spacing:
  gutter-sm: "1rem"
  gutter-md: "2rem"
  entry: "4.5rem"
  column-gap: "3.5rem"
  stack: "1.25rem"
components:
  button-primary:
    backgroundColor: "{colors.hora}"
    textColor: "{colors.tarjeta}"
    rounded: "{rounded.pill}"
    padding: "0 1.75rem"
    height: "3.25rem"
  button-primary-hover:
    backgroundColor: "{colors.hora-hondo}"
    textColor: "{colors.tarjeta}"
  button-primary-big:
    backgroundColor: "{colors.hora}"
    textColor: "{colors.tarjeta}"
    rounded: "{rounded.pill}"
    padding: "0 2.25rem"
    height: "3.75rem"
  link-underlined:
    textColor: "{colors.marca-hondo}"
  plate:
    backgroundColor: "{colors.tarjeta}"
    rounded: "{rounded.plate}"
  plate-caption:
    textColor: "{colors.tinta-2}"
    typography: "{typography.caption}"
    padding: "0.6rem 0.9rem"
  channel-chip:
    backgroundColor: "{colors.tarjeta}"
    textColor: "{colors.marca-hondo}"
    rounded: "{rounded.pill}"
    padding: "0.45rem 0.9rem 0.45rem 0.75rem"
  phone-head:
    backgroundColor: "{colors.marca}"
    textColor: "{colors.tarjeta}"
    padding: "1rem 1.25rem"
  rail-clock:
    textColor: "{colors.hora}"
    typography: "{typography.clock}"
---

# Design System: Hotel Ads /staff

Scope: this system is page-scoped to `src/pages/staff.astro` (all tokens live on the `.st` root, not `:root`). The shared layout (`src/layouts/layout.astro` nav and footer) and the rest of hotelads.es are the older generic Tailwind slate/blue look; they are incumbent and out of scope, not part of this system.

## Overview

**Creative North Star: "La hoja de turno"**

The page is the portal's own world: a shift sheet pinned to a warm grey wall. Screens of the real product sit on it as paper plates with a soft lift; the text is set in marine ink; hours are the only ornament, printed large in condensed tabular numerals. The reading order is the day itself, 00:00 to 23:59, and every module appears at the hour it works.

Density is editorial, not dashboard: one idea per hour-entry, long vertical rhythm, a fine ruled line between entries like the rows of a cuadrante. Colour is almost entirely ink-on-paper; a single burnt orange marks "now" and the one action. There are no gradients as surfaces, no glass panels, no icon-grid feature cards.

**Key Characteristics:**
- Warm grey wall, off-white paper plates, marine ink; one orange for the hour and the CTA.
- Archivo variable, width axis doing the hierarchy: condensed for hours and titles, normal for reading.
- Hours in tabular figures as the structural device (ruler, sticky clock, rail, closing sheet).
- Real, anonymized product screenshots framed as plates; conversations built in HTML, not faked as images.
- Hairline rules (1px) instead of boxes to separate the day.

## Colors

Ink on warm paper, with one hot hour.

### Primary
- **Hora, burnt orange** (`hora`): the active hour and the single action. Used on the rail clock and its active bar, the mobile mini-clock, the dot on the 24-hour ruler, the "Ahora, HH:MM" of the nowline (deep variant), the "Probar en vivo" button, and the focus ring.
- **Hora hondo, ember** (`hora-hondo`): hover state of the CTA, the tinted CTA shadow, and orange text on light ground where `hora` would be too light.

### Secondary
- **Marca, harbour blue** (`marca`): the portal's brand blue. Phone-mock header bar, in-chat reservation button, list-bullet dashes, handback pill border, selection highlight.
- **Marca hondo, deep harbour** (`marca-hondo`): the `/staff` title, mobile entry hours, closing-sheet hours, secondary links and chip text.

### Neutral
- **Tinta, marine ink** (`tinta`): body ink, the ruler axis, heavy rules framing the closing sheet and CTA band, the device bezel.
- **Tinta 2, slate pencil** (`tinta-2`): paragraphs, captions, area labels, ruler numerals, timestamps.
- **Pared, warm wall** (`pared`): page ground behind the whole world.
- **Tarjeta, sheet paper** (`tarjeta`): plates, phone mocks, chips, the closing section ground.
- **Pauta, ruled line** (`pauta`): every 1px hairline: plate borders, entry separators, rail line, caption divider.

### Conversation mock colours
- **Hilo** (`hilo`), **burbuja asistente** (`burbuja-asistente`), **burbuja recepción** (`burbuja-recepcion`): only inside phone mocks: thread ground, assistant replies, human (reception) replies. Incoming guest bubbles are pure white. Never used outside a conversation.

### Named Rules
**The One Hour Rule.** Orange means "this hour" or "do this". It appears on the current or active time and the CTA (plus the focus ring), nowhere else: not on headings, not on section hours at rest, not as decoration.

**The Ink-on-Paper Rule.** Every surface is `pared` or `tarjeta`; depth comes from paper, hairline and a soft shadow, never from colour fills or gradients.

## Typography

**Display Font:** Archivo (self-hosted variable, wght 400–900, wdth 62–125%), with Segoe UI, Roboto, Helvetica Neue, Arial
**Body Font:** Archivo at normal width

**Character:** One family, stretched and squeezed. Condensed heavy Archivo makes hours and titles read like timetable numerals; normal-width Archivo carries the reading.

### Hierarchy
- **Display** (850, clamp(4.5rem, 14vw, 6rem), 0.85, width 72%): the product name `/staff` only.
- **Clock** (800, 3.5rem rail / 3rem entry hour / 1.9rem mini-clock / 1.25rem sheet, width 70–75%, tabular): every time of day.
- **Headline** (750, clamp(1.75rem, 3.4vw, 2.6rem), 1.06, width 86%, max 22ch, balanced): one per hour-entry; written as a moment in the hotel's day. Quiet entries step down to clamp(1.4rem, 2.4vw, 1.85rem). Closing title 800 at width 80%.
- **Title** (700, clamp(1.6rem, 3.6vw, 2.25rem), 1.08, width 88%): the hero subline under the display; the CTA sentence uses 650 at width 90%.
- **Body** (400, 1.075rem, 1.65, max 62ch, `tinta-2`): entry paragraphs; hero lead 1.125rem/1.6 at max 36rem; bullet lists 1.55 at max 58ch in `tinta`.
- **Label** (600, 0.75–0.8rem, 0.08em, uppercase): only the area name paired with a time (Frontdesk, Equipo, Ventas, Concierge) in the clock and the closing sheet.
- **Caption** (600, 0.8rem, 0.01em): plate and phone captions below a hairline.

### Named Rules
**The Tabular Hour Rule.** Every time is set with tabular figures in condensed heavy Archivo, so hours align and tick without jitter.

**The Label Belongs to a Time Rule.** Uppercase tracked text exists only as the area beside a clock or sheet hour. It never sits above a heading as a kicker.

## Layout

Centered container of 76rem with 1rem gutters (2rem from 768px). The hero splits 5/7 from 1024px, the screenshot plate bleeding 6rem past the right edge; the 24-hour ruler sits under it with the real Madrid time as a dot and a live "Ahora, HH:MM" line linking to the matching entry.

The day section is a two-column grid from 1024px: an 11rem sticky rail (clock plus list of the day's hours) and the entries. Below 1024px the rail becomes a sticky mini-clock strip under the site nav and each entry shows its own hour. Entries are 4.5rem tall-padded blocks separated by a `pauta` hairline, in four shapes: split (5/7, flippable to 7/5 from 900px), wide (heading block up to 50rem, then plates), quiet (shorter, smaller headline, small plate right), and duo (list screenshot plus phone). Paired plates go 1.1fr/1fr; stacked plates overlap by 3rem, offset right. The close is a paper band with the full day as a ruled sheet (hour / area / what) and the CTA between two ink rules.

## Elevation & Depth

Paper on a wall: flat ground, lifted sheets. One shadow for all paper objects; the CTA has its own warm cast. No glass, no gradients as surfaces.

### Shadow Vocabulary
- **Sheet lift** (`box-shadow: 0 1px 0 rgba(26,29,33,0.05), 0 18px 40px -22px rgba(40,32,18,0.45)`): plates, phone mocks, the device frame.
- **Ember cast** (`box-shadow: 0 10px 24px -12px rgba(158,63,16,0.7)`): the orange CTA only.
- **Now halo** (`box-shadow: 0 0 0 4px` `hora` at 22%): the ruler's current-time dot.

### Named Rules
**The One Sheet Rule.** Anything that is a piece of the product (screenshot, phone, device) gets the sheet lift and a `pauta` border; nothing else is lifted.

## Shapes

Soft paper corners (12px) on plates; phone mocks round to 2rem and the device bezel to 2.4rem so they read as hardware; chips, CTA and handback are full pills. Borders are always 1px hairlines. Screenshots that continue past the crop fade out horizontally via an alpha mask instead of being cut hard; the WhatsApp list is cropped from the top at a fixed height.

## Components

### Buttons
Warm, round and singular.
- **Shape:** full pill (999px).
- **Primary:** `hora` fill, white text, 700 at 1.05rem, min-height 3.25rem, 0 1.75rem padding, ember cast. Big variant 3.75rem / 0 2.25rem / 1.15rem in the closing band.
- **Hover / Focus:** fill deepens to `hora-hondo` and lifts 1px (0.2s, cubic-bezier(0.16, 1, 0.3, 1)); active returns to 0. Focus is a 2px `hora` outline offset 3px.
- **Secondary:** a text link in `marca-hondo`, 600, 1px underline at 0.3em offset in `marca` at 45%, full `marca` on hover.

### Chips
- **Channel chip:** paper pill with `pauta` hairline, `marca-hondo` 600 at 0.9rem, a 1rem stroked line icon (1.8 stroke) before the label.

### Cards / Containers (Plates)
- **Corner Style:** 12px.
- **Background:** `tarjeta`, 1px `pauta` border, sheet lift.
- **Caption:** below a `pauta` hairline, caption type in `tinta-2`.
- **Motion:** plates in entries rise 1.5rem and fade in on scroll (0.7–0.9s, cubic-bezier(0.16, 1, 0.3, 1)), only with JS and without reduced motion; visible by default.

### Navigation
The site nav is the incumbent layout, out of scope. Inside the world, navigation is the day: the ruler, the rail list (inactive hours `tinta` at 45%, past in `tinta-2`, active in `tinta` 700 with a 2px `hora` bar growing from the rule) and the closing sheet, whose rows link to each entry and tint with `pared` on hover.

### Hour Rail and Clock (signature)
A sticky clock that changes to the hour of the entry in view, with a short blur-rise tick (0.45s) disabled under reduced motion. Area label below in the Label style. On mobile the same data rides a sticky strip under the nav in `hora` at 1.9rem.

### 24-hour Ruler (signature)
A baseline in `tinta` with 25 ticks (majors every 6 h labeled 00/06/12/18/24 in tabular `tinta-2`), and the current Madrid time as an orange dot with halo, refreshed every minute.

### Phone Mock
Paper body, 2rem radius, sheet lift; `marca` header with a paper initial avatar; `hilo` thread; bubbles 0.9rem radius with the tail corner squared to 0.25rem; incoming white, assistant `burbuja-asistente`, reception `burbuja-recepcion`; timestamps bottom-right in tabular `tinta-2` 0.65rem. In-chat action is a `marca` block button. Always captioned "Conversación de ejemplo".

## Do's and Don'ts

### Do:
- **Do** put the time first: every module is introduced by the hour it happens, in condensed tabular Archivo.
- **Do** keep orange to the current/active hour, the CTA and the focus ring (The One Hour Rule).
- **Do** show real, anonymized product screenshots as plates (12px, `pauta` hairline, sheet lift), each with a provenance sidecar and a caption.
- **Do** separate content with 1px `pauta` hairlines and use `tinta` rules only to frame the closing sheet and CTA band.
- **Do** build conversations in HTML with the phone mock, and mark them as examples.
- **Do** keep all motion behind `prefers-reduced-motion` with content visible by default.

### Don't:
- **Don't** use icon-grid feature cards; the day's sequence is the structure.
- **Don't** use gradients as surface fills or glass panels as surfaces; the only gradient is the alpha mask that fades a cropped table.
- **Don't** put uppercase tracked kickers or eyebrows above headings; tracked uppercase is only an area beside a time.
- **Don't** use orange for headings, entry hours at rest, bullets or decoration.
- **Don't** show real guest, employee or client-hotel data, or supplier brands, in any screenshot or mock.
- **Don't** inherit the layout's slate/blue Tailwind look inside this world.
