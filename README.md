# Scout Life comic viewer

A guided, panel-by-panel viewer for comic pages — the reader starts on the full page, then
zooms from panel to panel with an arrow, Prezi style. Built as two plain HTML files with no
build step, no framework and no dependencies, so it drops onto any host and embeds into
WordPress with an iframe.

```
comicviewer/
├── viewer.html          the embed — this is what the iframe points at
├── mapper.html          authoring tool: draw panel boxes, export JSON
├── index.html           local demo/test page listing the sample comics
└── comics/
    ├── peewee-sep2026.jpg   + .json
    ├── wacky-sep2026.jpg    + .json
    ├── sia-sep2026.jpg      + .json
    └── msia-sep2026.jpg     + .json
```

---

## The workflow for a new comic

1. **Export the page as a single JPG.** One flat image of the whole page — no separate panel
   files. See *Image resolution* below for the size to use.
2. **Open `mapper.html`** in a browser, click *Open image…* and pick the JPG.
3. **Drag a box around each panel**, in reading order. Click a box to select it, drag to move
   it, grab a corner to resize. `Delete` removes the selected box; arrow keys nudge it
   (hold `Shift` for bigger steps). *Auto reading order* re-sorts everything top-to-bottom,
   left-to-right if you drew them out of sequence.
4. Set the **Title** and the **Image path** — the path the live site will use, e.g.
   `comics/peewee-sep2026.jpg` or a full media-library URL.
5. Hit **Preview in viewer** to sanity-check the tour, then **Download .json**.
6. Upload the `.jpg` and the `.json` next to `viewer.html` on the server.
7. Embed. Roughly two minutes per page once you've done a couple.

Panel boxes can overlap and don't have to line up with the printed gutters — treat them as
"what should be on screen for this beat." On a page like *Scouts in Action*, a caption block
plus its artwork often works better as one box than two.

---

## Embedding in WordPress

Add a **Custom HTML** block and paste this, changing the domain and the JSON filename:

```html
<div style="position:relative;width:100%;max-width:820px;margin:0 auto;padding-top:118%">
  <iframe src="https://YOURSITE.org/comics/viewer.html?c=comics/peewee-sep2026.json"
          title="Pee Wee Harris Looks at Music"
          loading="lazy" allowfullscreen
          style="position:absolute;inset:0;width:100%;height:100%;border:0;border-radius:10px">
  </iframe>
</div>
```

The `padding-top` percentage sets the aspect ratio of the embed box (height ÷ width × 100).
`118%` is a good default for a portrait magazine page. Raise it toward `135%` if you want more
vertical room on phones; lower it toward `75%` for a wide, letterboxed look on desktop.

**Where to put the files.** Anywhere the web server can serve them — a `/comics/` directory at
the document root is simplest. The WordPress media library also works, but it renames and
date-folders uploads, so if you go that route put the full media URL into the mapper's
*Image path* field and point `?c=` at wherever the JSON ended up.

`viewer.html` only accepts a same-site relative path for `?c=`, so a third party can't point
your embed at their own config.

---

## Reader controls

| Action | Control |
|---|---|
| Next panel | Right arrow, Space, click the artwork, swipe left, or the Next button |
| Previous panel | Left arrow, swipe right, or the Back button |
| Jump to a panel | Click it in the full-page view, or click the progress bar |
| Full page | The *Full page* button, `O`, or `Esc` — press again to return to where you were |
| First / last | `Home` / `End` |
| Fullscreen | The expand button or `F` |

The full-page view outlines every panel with its number, so a reader who doesn't want the
guided tour can jump straight to whatever catches their eye. Panel changes are announced to
screen readers, and everything is keyboard reachable.

---

## Image resolution

**This matters more than anything else in the setup.** The viewer scales one image up; it
never swaps in per-panel files. A page that looks fine at full size will look soft at 3× zoom.

The four sample JPGs here are 700 px wide — fine for proving the interaction, too low for
production. Export pages at **1800–2400 px wide** (roughly 2.5–3× the display width). At
JPEG quality 70–80 that lands around 400–700 KB per page, which is reasonable for a
lazy-loaded embed.

If you want the page to appear instantly but still hold up when zoomed, add a second file:

```json
{
  "image": "comics/peewee-sep2026.jpg",
  "imageHiRes": "comics/peewee-sep2026@2x.jpg"
}
```

The viewer shows the small one immediately and quietly swaps in the large one once it has
downloaded.

---

## The config file

```json
{
  "title": "Pee Wee Harris Looks at Music",
  "image": "comics/peewee-sep2026.jpg",
  "imageHiRes": "comics/peewee-sep2026@2x.jpg",
  "width": 700,
  "height": 916,
  "spotlight": true,
  "padding": 0.06,
  "autostart": false,
  "panels": [
    { "x": 0.012, "y": 0.010, "w": 0.340, "h": 0.248, "label": "Optional caption" }
  ]
}
```

| Key | What it does |
|---|---|
| `title` | Shown in the top-left of the viewer and used as the page title |
| `image` | Path to the page JPG, relative to `viewer.html` |
| `imageHiRes` | Optional larger file, swapped in after load |
| `width` / `height` | Pixel size of the image; lets the viewer lay out before the image arrives |
| `spotlight` | Dims everything outside the current panel. Set `false` to turn it off |
| `padding` | Breathing room around a zoomed panel, as a fraction of the frame. `0.06` = 6% |
| `autostart` | If `true`, moves to panel 1 automatically a moment after load |
| `panels[]` | `x`, `y`, `w`, `h` as fractions of the image (0–1), in reading order. `label` is optional and appears as a caption |

You can also pass a whole config inline as base64 via `?config=…` — that's what the mapper's
preview button uses — or set `window.COMIC_CONFIG` before the script runs.

---

## Notes and limits

- **Very wide panels on portrait phones.** A banner panel that spans the full page width can't
  zoom much on a narrow screen — there's nowhere to zoom to. The viewer keeps the page flush
  in the frame and relies on the spotlight to show which strip is current. If a particular
  page reads badly this way, split the wide panel into two overlapping boxes in the mapper.
- **No panning within a panel.** Each step is a fixed framing. Drag is reserved for swipe
  navigation.
- The viewer avoids `localStorage` entirely, so it works inside restrictive iframe sandboxes.
- Tested in Chromium at desktop (1280×800, 900×1000) and phone (390×780) sizes; no console
  errors, all four sample configs load and step correctly.

## Local testing

```bash
cd comicviewer
python3 -m http.server 8000
# then open http://localhost:8000/
```

A plain `file://` open works for `mapper.html` but not for `viewer.html?c=…`, because browsers
block `fetch` of local JSON. Use the mapper's *Preview in viewer* button instead, or run the
tiny server above.
