# GEO Optimizer

A WordPress plugin that optimizes your content for AI-powered search engines like ChatGPT, Perplexity, and Google's AI Overviews.

Traditional SEO focuses on ranking in Google's blue links. **GEO (Generative Engine Optimization)** focuses on getting your content cited and surfaced by AI assistants. This plugin handles the technical side — install it, configure a few settings, and it works in the background on every post and page.

## Features

### Schema Markup
Auto-injects JSON-LD structured data on posts and pages. Supported types:
- **Article** — Blog posts, news, guides
- **Blog Post** — More specific subtype of Article
- **FAQ Page** — Auto-detects question headings (H2/H3 ending with `?`) and pairs them with answers
- **How To** — Reads headings as step names with content below as step details
- **Local Business** — For service/business pages
- **Product** — WooCommerce integration with price, availability, reviews, and ratings
- **Web Page** — Generic fallback for static pages (About, Contact, etc.)

### Content Scoring
Every post gets a 0–100 GEO readiness score in the editor, measuring:
- **Direct answers** — Clear, upfront answers in opening paragraphs (20 pts)
- **Question-based headings** — H2/H3 phrased as questions (20 pts)
- **Reading level** — Grade 6–10 sweet spot (20 pts)
- **Entity clarity** — Primary entity in title and first paragraph (20 pts)
- **Content length** — 300+ words minimum, 2,000+ for full marks (20 pts)

Score colors: **Red** (0–39), **Yellow** (40–69), **Green** (70–100)

### AI Snippet Optimization
Per-post fields injected as invisible metadata for AI crawlers:
- **AI Summary** — 1–2 sentence summary optimized for AI extraction
- **Primary Entity** — The main subject of the content
- **Target Question** — The question the content answers

Compatible with RankMath — skips the AI summary tag if RankMath has already set a meta description.

### AI Sitemap & Robots
- Generates `/ai-sitemap.xml` with your best content (filtered by minimum GEO score)
- Adds `robots.txt` rules to allow/block specific AI crawlers (GPTBot, ClaudeBot, PerplexityBot, Google-Extended)

## Installation

1. Download or zip the `geo-optimzer` folder
2. In WordPress: **Plugins > Add Plugin > Upload Plugin** > select the zip
3. Activate and configure under **GEO Optimizer** in the admin sidebar
4. Go to **Settings > Permalinks** and click Save to register the AI sitemap URL

## Settings

| Setting | Default | Description |
|---------|---------|-------------|
| Enable Schema Markup | On | Auto-inject JSON-LD on posts and pages |
| Default Schema Type | Article | Default schema type (overridable per-post) |
| WooCommerce Product Schema | On | Rich Product + Review schema for WooCommerce |
| Enable GEO Scoring | On | Show GEO score in the post editor |
| Enable AI Snippets | On | Inject AI-optimized meta tags |
| Enable AI Sitemap | On | Generate `/ai-sitemap.xml` |
| Minimum GEO Score for Sitemap | 50 | Threshold for sitemap inclusion |
| Bot Access Mode | Allow | Allow or block listed bots in robots.txt |
| Bot List | GPTBot, ClaudeBot, PerplexityBot, Google-Extended | AI crawlers to manage |
| Delete Data on Uninstall | Off | Remove all plugin data when deleted |

## Usage

1. Edit any post or page
2. Scroll to the **GEO Optimizer** meta box
3. Select a schema type (or keep the default)
4. Fill in **AI Summary**, **Primary Entity**, and **Target Question**
5. Check your score and follow the suggestions
6. Publish — schema, meta tags, sitemap, and robots rules update automatically

## Compatibility

- WordPress 6.0+
- PHP 8.0+
- Works alongside RankMath, Yoast, WooCommerce, and Elementor

## FAQ

**Does this slow down my site?**
No. A small JSON-LD block and a few meta tags per page. No external API calls, no frontend JavaScript, no extra database queries beyond cached post meta.

**Do I need to fill in the AI fields on every post?**
No, they're optional. But they improve your GEO score and give AI engines clearer signals. Prioritize your highest-traffic pages.

**What if I already use RankMath or Yoast?**
They work fine together. The plugin uses namespaced meta tags (`geo-opt:`) that don't overlap. If RankMath has set a description, the plugin skips its own summary tag.

**What if I don't use WooCommerce?**
The WooCommerce features simply don't load. No errors, no wasted resources.

**Can AI bots actually read this?**
Yes. GPTBot, ClaudeBot, and other AI crawlers read JSON-LD, meta tags, and robots.txt directives. This is the same approach used by major publishers optimizing for AI citation.

## Uninstall

- **Deactivate** to preserve all data for later
- **Delete** with "Delete Data on Uninstall" off (default) to keep settings in the database
- **Delete** with "Delete Data on Uninstall" on to remove all options, post meta, and transients

## License

GPL-2.0-or-later
