// Self-hosts a Font Awesome subset instead of loading the full ~110KB CDN kit
// (https://cdnjs.cloudflare.com/.../font-awesome/6.5.1/css/all.min.css) for the ~80 icons
// this app actually uses. Run automatically before `npm run build` (see package.json).
//
// How it works:
//   1. Scan every view/script for `fa-[a-z0-9-]+` class names.
//   2. Split those into real icon glyphs vs. FA's own modifier/utility classes (fa-solid,
//      fa-spin, fa-fw, ...) using the codepoint map parsed straight out of the installed
//      @fortawesome/fontawesome-free package's own CSS — the same source cdnjs serves, so
//      subsetting can't drift from what's rendered today.
//   3. Subset fa-solid-900.woff2 and fa-regular-400.woff2 down to just those codepoints.
//   4. Emit resources/css/icons.css (self-contained: @font-face + base rules + one
//      `content:` rule per icon actually used) and resources/fonts/*.woff2.
//
// Fails the build if a used class looks like an icon name but isn't found anywhere in the
// FA metadata — that's almost certainly a typo, and letting it through would silently
// render as a blank box instead of an error.

import { readFileSync, writeFileSync, mkdirSync, readdirSync, statSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import subsetFont from 'subset-font';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));
const FA_PKG = join(ROOT, 'node_modules/@fortawesome/fontawesome-free');
const OUT_CSS = join(ROOT, 'resources/css/icons.css');
const OUT_FONTS_DIR = join(ROOT, 'resources/fonts');

const SCAN_DIRS = ['resources/views', 'resources/js', 'public/js'];
const SCAN_EXTENSIONS = ['.blade.php', '.php', '.js'];

// FA's own non-glyph classes: family/weight selectors, animation, sizing, layout helpers.
// Anything in this set is never subsetted — it carries no `:before` content of its own.
const UTILITY_CLASSES = new Set([
    'fa-solid', 'fa-regular', 'fa-light', 'fa-thin', 'fa-duotone', 'fa-brands', 'fa-sharp',
    'fas', 'far', 'fal', 'fat', 'fad', 'fab',
    'fa-spin', 'fa-spin-pulse', 'fa-spin-reverse', 'fa-pulse', 'fa-beat', 'fa-beat-fade',
    'fa-fade', 'fa-shake', 'fa-bounce',
    'fa-fw', 'fa-border', 'fa-inverse', 'fa-pull-left', 'fa-pull-right', 'fa-li', 'fa-ul',
    'fa-flip', 'fa-flip-horizontal', 'fa-flip-vertical', 'fa-flip-both',
    'fa-rotate-90', 'fa-rotate-180', 'fa-rotate-270', 'fa-rotate-by',
    'fa-stack', 'fa-stack-1x', 'fa-stack-2x',
    'fa-layers', 'fa-layers-text', 'fa-layers-counter', 'fa-layers-bottom-right',
    'fa-layers-bottom-left', 'fa-layers-top-right', 'fa-layers-top-left',
    'fa-xs', 'fa-sm', 'fa-lg', 'fa-2xs', 'fa-2xl',
    'fa-1x', 'fa-2x', 'fa-3x', 'fa-4x', 'fa-5x', 'fa-6x', 'fa-7x', 'fa-8x', 'fa-9x', 'fa-10x',
]);

function walk(dir, results = []) {
    for (const entry of readdirSync(dir)) {
        const full = join(dir, entry);
        const stats = statSync(full);
        if (stats.isDirectory()) {
            walk(full, results);
        } else if (SCAN_EXTENSIONS.some((ext) => entry.endsWith(ext))) {
            results.push(full);
        }
    }
    return results;
}

function findUsedClasses() {
    const used = new Set();
    // Icon names literally paired with `fa-regular` somewhere in the source (either order,
    // since Alpine bindings sometimes write `'fa-regular fa-x'` and sometimes reverse it).
    // FA6's per-icon `content:` mapping is one shared codepoint across weights — whether a
    // glyph actually exists in the *regular* font is a property of that font binary, not
    // the CSS, so this is the only reliable signal for which icons need a regular glyph.
    // An icon used only via a fully dynamic expression with fa-regular (no literal name
    // anywhere) would be missed here and fall back to solid-only.
    const regularNames = new Set();

    for (const dir of SCAN_DIRS) {
        for (const file of walk(join(ROOT, dir))) {
            const text = readFileSync(file, 'utf8');
            for (const match of text.matchAll(/\bfa-[a-z0-9-]+\b/g)) {
                used.add(match[0]);
            }
            for (const match of text.matchAll(/fa-regular\s+(fa-[a-z0-9-]+)|(fa-[a-z0-9-]+)\s+fa-regular/g)) {
                regularNames.add(match[1] ?? match[2]);
            }
        }
    }

    return { used, regularNames };
}

// Parses `.fa-name:before,.fa-alias:before{content:"\fXXX"}`-style rules out of FA's own
// all.min.css into a Map<className, codepoint>. Every icon name FA ships resolves through
// here, including v4/v5 shim aliases (e.g. fa-rotate-left -> \f2ea), so this is a faithful
// stand-in for the full icons.json metadata.
function buildCodepointMap() {
    const css = readFileSync(join(FA_PKG, 'css/all.min.css'), 'utf8');
    const map = new Map();

    // Two independent passes rather than one mega-regex: a single rule can alias many
    // names (`.fa-magnifying-glass:before,.fa-search:before{content:"\f002"}`), and a
    // regex that tries to capture the whole selector list in one group has no way to
    // stop at each individual `:before` boundary without either (a) letting its
    // character class swallow the literal word "before" itself — since 'b','e','f','o',
    // 'r' are ordinary lowercase letters — which silently corrupts every alias except
    // the last one in the list, or (b) hand-rolling something far more fragile. Instead:
    // record where every `.fa-name:before` selector sits, and where every `{content:...}`
    // block sits, then pair each selector with the next content block after it — exactly
    // how the CSS itself associates a (possibly multi-alias) selector list with its glyph.
    const selectors = [...css.matchAll(/\.(fa-[a-z0-9-]+):before/g)]
        .map((m) => ({ name: m[1], index: m.index }));
    // Most icons live in FA's private-use area (\fXXX) but a few map straight to standard
    // Unicode symbols instead — e.g. fa-plus is content:"\2b" (the ordinary "+"), not an
    // "f"-prefixed PUA point — so the hex capture must not assume an "f" prefix.
    const contentBlocks = [...css.matchAll(/\{content:"\\([0-9a-f]+)"\}/gi)]
        .map((m) => ({ hex: m[1], index: m.index }));

    let blockCursor = 0;
    for (const selector of selectors) {
        while (blockCursor < contentBlocks.length - 1 && contentBlocks[blockCursor].index < selector.index) {
            blockCursor++;
        }
        map.set(selector.name, parseInt(contentBlocks[blockCursor].hex, 16));
    }
    return map;
}

async function subsetWeight(srcFile, codepoints) {
    const buffer = readFileSync(join(FA_PKG, 'webfonts', srcFile));
    const text = codepoints.map((cp) => String.fromCodePoint(cp)).join('');
    return subsetFont(buffer, text, { targetFormat: 'woff2' });
}

async function main() {
    const { used, regularNames } = findUsedClasses();
    const codepointMap = buildCodepointMap();

    const solidGlyphs = new Map();
    const regularGlyphs = new Map();
    const unknown = [];

    for (const className of used) {
        if (UTILITY_CLASSES.has(className)) continue;

        const codepoint = codepointMap.get(className);
        if (codepoint === undefined) {
            unknown.push(className);
            continue;
        }

        // Only icons actually paired with `fa-regular` in the source go into the regular
        // subset; everything else is solid. Putting a codepoint into a weight's subset
        // whose font doesn't actually contain that glyph is harmless (subset-font just
        // has nothing to copy for it) but wastes bytes, so keep the two lists disjoint
        // by default rather than duplicating every icon into both.
        if (regularNames.has(className)) {
            regularGlyphs.set(className, codepoint);
        } else {
            solidGlyphs.set(className, codepoint);
        }
    }

    if (unknown.length) {
        console.error(
            `\nbuild-icon-subset: ${unknown.length} class(es) look like Font Awesome icons ` +
            `but aren't in @fortawesome/fontawesome-free's metadata:\n  ${unknown.join(', ')}\n` +
            'Fix the typo, or add the class to UTILITY_CLASSES in scripts/build-icon-subset.mjs ' +
            'if it really is a modifier, not an icon.\n'
        );
        process.exitCode = 1;
        return;
    }

    mkdirSync(OUT_FONTS_DIR, { recursive: true });

    const solidCodepoints = [...new Set(solidGlyphs.values())];
    const regularCodepoints = [...new Set(regularGlyphs.values())];

    const [solidBuf, regularBuf] = await Promise.all([
        subsetWeight('fa-solid-900.woff2', solidCodepoints),
        subsetWeight('fa-regular-400.woff2', regularCodepoints),
    ]);

    writeFileSync(join(OUT_FONTS_DIR, 'fa-solid-subset.woff2'), solidBuf);
    writeFileSync(join(OUT_FONTS_DIR, 'fa-regular-subset.woff2'), regularBuf);

    const cssRule = (className, codepoint) =>
        `.${className}:before{content:"\\${codepoint.toString(16)}"}`;

    const css = `/* Generated by scripts/build-icon-subset.mjs — do not edit by hand.
   Regenerate with \`npm run build\` (or \`node scripts/build-icon-subset.mjs\`) after
   adding a new fa-* icon anywhere in the app. */

@font-face {
    font-family: "FA Subset Solid";
    font-style: normal;
    font-weight: 900;
    font-display: block;
    src: url("../fonts/fa-solid-subset.woff2") format("woff2");
}

@font-face {
    font-family: "FA Subset Regular";
    font-style: normal;
    font-weight: 400;
    font-display: block;
    src: url("../fonts/fa-regular-subset.woff2") format("woff2");
}

.fa-solid, .fas {
    font-family: "FA Subset Solid";
    font-weight: 900;
    display: var(--fa-display, inline-block);
    font-style: normal;
    font-variant: normal;
    line-height: 1;
    text-rendering: auto;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.fa-regular, .far {
    font-family: "FA Subset Regular";
    font-weight: 400;
    display: var(--fa-display, inline-block);
    font-style: normal;
    font-variant: normal;
    line-height: 1;
    text-rendering: auto;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.fa-spin {
    animation: fa-spin 2s linear infinite;
}

@keyframes fa-spin {
    100% { transform: rotate(360deg); }
}

${[...solidGlyphs.entries()].map(([name, cp]) => cssRule(name, cp)).join('\n')}
${[...regularGlyphs.entries()].map(([name, cp]) => cssRule(name, cp)).join('\n')}
`;

    writeFileSync(OUT_CSS, css);

    console.log(
        `build-icon-subset: wrote ${solidGlyphs.size} solid + ${regularGlyphs.size} regular ` +
        `glyph(s) to resources/css/icons.css (${(solidBuf.length / 1024).toFixed(1)}KB + ` +
        `${(regularBuf.length / 1024).toFixed(1)}KB woff2).`
    );
}

main();
