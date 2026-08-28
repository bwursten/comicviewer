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

### Two mapping rules worth knowing

**Skip the page furniture.** Logos, section headers and the legal/credits block at the foot of
a page are full-page-width strips. A panel as wide as the frame cannot zoom — there is nowhere
to zoom *to* — so making one a step produces a click where nothing appears to happen. Leave
them out; they're still visible in the opening full-page view. The four sample configs here
were mapped this way, which is why *Scouts in Action* is six panels rather than eight.

**Wide panels get swept automatically.** A genuine wide panel — an establishing shot spanning
the whole page — is handled for you: the viewer breaks it into two or three overlapping
sub-frames and pans across it, so the reader still sees every part of it at a real zoom level.
This is recomputed on resize, so the same page might be 6 steps on a desktop and 7 on a phone.
Set `"split": false` in the config to turn it off.

The one case with no good answer is a *thin* full-width strip, like a "MANY MORE MOMENTS
LATER…" caption bar. It can't zoom and it can't be swept without cutting the sentence in half,
so the viewer leaves it whole and relies on the dimming to show it's the current beat. If that
bothers you on a particular page, merge the strip into the panel above or below it.

---

## Embedding in WordPress

### Recommended: the shortcode plugin

Upload the four viewer files by SFTP to a top-level folder — **not** inside
`wp-content/themes/`, which a theme switch can wipe:

```
scoutlife.org/
└── comics/
    ├── viewer.html
    ├── mapper.html          ← staff tool, see below
    └── pages/
        ├── peewee-sep2026.jpg
        └── peewee-sep2026.json
```

Then copy `wordpress/scoutlife-comic-viewer.php` into `wp-content/mu-plugins/`. Must-use
plugins load automatically and can't be deactivated by accident from wp-admin. Editors then
write:

```
[comic page="peewee-sep2026"]
[comic page="sia-sep2026" title="Scouts in Action" caption="From the September 2026 issue"]
[comic page="msia-sep2026" ratio="4/5" width="640" align="wide"]
```

| Attribute | Default | Notes |
|---|---|---|
| `page` | *required* | JSON filename without the extension. Stripped to `A–Z a–z 0–9 _ -`, so it can't escape the comics folder |
| `title` | `Comic viewer` | The iframe's accessible name |
| `ratio` | `5/6` | Accepts `5/6`, `5:6` or a decimal. Anything else falls back to the default |
| `width` | `820` | Max width in px, clamped to 240–2000 |
| `caption` | — | Optional line under the embed |
| `align` | `center` | `center`, `left`, `right` or `wide` |

If the paths differ on your server, override them near the top of the plugin file —
`SLCV_VIEWER_URL`, `SLCV_COMICS_PATH`, `SLCV_DEFAULT_RATIO`, `SLCV_DEFAULT_WIDTH`. Change them
once and every embed on the site follows.

A `[comic]` with no `page` renders nothing for visitors and a red warning for logged-in
editors, so a typo never ships as a broken box.

**Keep `mapper.html` unlisted.** It's harmless — entirely client-side, it makes no server
calls and writes nothing — but put it behind basic auth or at an unguessable path so it
doesn't turn up in search results.

### Alternative: a plain Custom HTML block

If you'd rather not install anything, add a **Custom HTML** block and paste this, changing the
domain and the JSON filename:

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

### Two gotchas either way

**WordPress blocks `.html` and `.json` uploads through the media library** by default. That's
a deliberate security measure — don't allow those MIME types site-wide to work around it. Use
SFTP or the host's file manager for the viewer files.

The page JPGs *can* live in the media library if you prefer. WordPress renames and
date-folders them, so paste the resulting full media URL into the mapper's *Image path* field.

`viewer.html` only accepts a same-site relative path for `?c=`, so a third party can't point
your embed at their own config.

---

## Reader controls

| Action | Control |
|---|---|
| Next panel | Right arrow, Space, click the artwork, swipe left, or the Next button |
| Previous panel | Left arrow, swipe right, or the Back button |
| Start the tour | Click anywhere on the page from the full-page view |
| Finish | One more Next past the last panel returns to the full page |
| Jump to a panel | Click it in the full-page view, or click the progress bar |
| Full page | The *Full page* button, `O`, or `Esc` — press again to return to where you were |
| First / last | `Home` / `End` |
| Fullscreen | The expand button or `F` |

The full-page view is clean artwork with nothing drawn over it. Moving the pointer across it
lights up whichever panel is underneath and dims the rest, so a reader who doesn't want the
guided tour can find what catches their eye and click straight to it. The *Full page* button
is grey while you're already on the full page and turns red once you're zoomed in.

Next is never a dead end. On the last panel it changes into a *Full page* button, and pressing
it pulls back out to the whole page — which is also where the tour started, so the comic reads
as a loop. Back immediately afterwards returns to the last panel, in case the reader overshot.

Panel changes are announced to screen readers, panels are tab-reachable, and everything works
from the keyboard.

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
  "minZoom": 1.6,
  "maxParts": 3,
  "split": true,
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
| `minZoom` | How far a step must zoom past the full-page view before it counts as a real move. Below this, a wide panel is swept in sub-frames instead. Default `1.6` |
| `maxParts` | Cap on sub-frames per panel. Default `3` |
| `split` | Set `false` to disable sweeping entirely and always fit each panel whole |
| `autostart` | If `true`, moves to panel 1 automatically a moment after load |
| `panels[]` | `x`, `y`, `w`, `h` as fractions of the image (0–1), in reading order. `label` is optional and appears as a caption |

You can also pass a whole config inline as base64 via `?config=…` — that's what the mapper's
preview button uses — or set `window.COMIC_CONFIG` before the script runs.

---

## Notes and limits

- **Thin full-width strips don't zoom.** See the mapping rules above — this is geometry, not a
  bug, and the fix is to not make them their own step.
- **No free panning within a panel.** Each step is a fixed framing; drag is reserved for swipe
  navigation. Wide panels are swept automatically instead.
- The viewer avoids `localStorage` entirely, so it works inside restrictive iframe sandboxes.
- Tested in Chromium at 1100×900, 1000×900, 820×970 and 390×780. No console errors; all four
  sample configs load, every step lands at 1.6× or better except the one thin banner strip
  noted above, single clicks on the arrows register first time, and the hover highlight,
  hotspot jumps, progress scrubbing and fullscreen all behave.

## Local testing

```bash
cd comicviewer
python3 -m http.server 8000
# then open http://localhost:8000/
```

A plain `file://` open works for `mapper.html` but not for `viewer.html?c=…`, because browsers
block `fetch` of local JSON. Use the mapper's *Preview in viewer* button instead, or run the
tiny server above.
