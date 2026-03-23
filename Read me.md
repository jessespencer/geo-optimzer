# GEO Optimizer — Plain Language Guide

## What is this plugin?

GEO Optimizer helps your website content show up in AI-powered search tools like ChatGPT, Perplexity, and Google's AI Overviews.

Traditional SEO focuses on ranking in Google's blue links. GEO (Generative Engine Optimization) focuses on getting your content **cited and surfaced by AI assistants** when people ask them questions. This plugin handles the technical side of that — you install it, configure a few settings, and it works quietly in the background on every post and page.

---

## Who is this for?

Content teams, site owners, and agencies managing WordPress sites who want their content to be discoverable by AI search engines — not just traditional Google.

No coding knowledge is required to use the plugin. Install it, turn on the features you want, and fill in a few fields when writing posts.

---

## What does it actually do?

The plugin does four things:

### 1. It tells AI engines what your content is about (Schema Markup)

When you publish a post or page, the plugin adds invisible structured data to the page that AI crawlers can read. Think of it like a label on a filing cabinet — it tells machines "this is an article about X, written by Y, published on Z."

You can set the content type:

- **Article** — Standard blog posts, news, guides
- **FAQ Page** — Content organized as questions and answers. The plugin automatically detects your question headings (any H2 or H3 that ends with a question mark) and pairs them with the answer text below
- **How To** — Step-by-step instructions. The plugin reads your headings as step names and the content below each heading as the step details
- **Product** — For product pages. If you use WooCommerce, the plugin pulls in price, availability, reviews, and ratings automatically

You pick a default type in settings (Article is fine for most sites), and can override it on any individual post.

### 2. It scores your content for AI readiness (Content Scoring)

Every post gets a score from 0 to 100 that tells you how well-optimized it is for AI engines. The score shows up right in the WordPress editor when you're writing.

Five things are measured, each worth up to 20 points:

- **Direct answers** — Does your content directly answer questions in the opening paragraphs? AI engines love content that gets straight to the point with clear statements like "A widget is..." or "The answer is..."
- **Question-based headings** — Are your H2 and H3 headings phrased as questions? This matches how people ask AI assistants things. "What is a widget?" is better than "About Widgets"
- **Reading level** — Is the content written at a grade 6–10 level? Too simple and it lacks substance. Too complex and AI engines struggle to extract clean answers. Grade 8 is the sweet spot
- **Entity clarity** — Have you filled in the Primary Entity and Target Question fields (see below), and does your main subject appear in the title and first paragraph?
- **Content length** — Longer content generally performs better. Under 300 words scores zero. Over 2,000 words gets full marks

Below the score, you'll see specific suggestions telling you exactly what to improve. The score updates automatically when you save a post, or you can hit "Recalculate" anytime.

**What the colors mean:**
- **Red (0–39)** — Needs significant work
- **Yellow (40–69)** — Getting there, review the suggestions
- **Green (70–100)** — Well-optimized for AI engines

### 3. It adds AI-specific metadata to your pages (AI Snippets)

When editing a post, you'll see three fields:

- **AI Summary** — Write a 1–2 sentence summary of the content, optimized for an AI to read and quote. Think of it as the answer you'd want ChatGPT to give when someone asks about this topic
- **Primary Entity** — The main subject. If your post is about "How to Train a Puppy," the primary entity is "puppy training"
- **Target Question** — The question your content answers. For the puppy post: "How do you train a puppy?"

These fields are optional but improve your score and help AI engines understand your content. The plugin injects them as invisible metadata that crawlers read but visitors never see.

**If you use RankMath:** The plugin checks whether RankMath has already set a meta description for the post. If it has, the plugin skips its own AI summary meta tag to avoid sending mixed signals. Everything else works normally alongside RankMath.

### 4. It makes your site crawlable by AI bots (Sitemap & Robots)

Two things happen here:

**AI Sitemap** — The plugin creates a special sitemap at `yoursite.com/ai-sitemap.xml` that lists your best content (anything above the minimum score threshold you set) ordered by quality. AI crawlers can use this to find your most important pages first.

**Robots.txt rules** — The plugin adds rules to your site's `robots.txt` file that explicitly allow (or block) specific AI crawlers. By default, it allows:
- **GPTBot** (OpenAI / ChatGPT)
- **ClaudeBot** (Anthropic / Claude)
- **PerplexityBot** (Perplexity)
- **Google-Extended** (Google's AI features)

You can add or remove bots from the list, and switch between allowing and blocking them.

---

## Settings page walkthrough

Go to **GEO Optimizer** in the WordPress admin sidebar. Everything is on one page:

| Setting | What it controls | Recommendation |
|---------|-----------------|----------------|
| Enable Schema Markup | Whether structured data is added to pages | Leave on |
| Default Schema Type | What type of content your posts default to | Article for most sites |
| WooCommerce Product Schema | Rich product data for WooCommerce stores | Leave on if you sell products |
| Enable GEO Scoring | Whether the score shows in the editor | Leave on |
| Enable AI Snippets | Whether AI metadata is injected | Leave on |
| Enable AI Sitemap | Whether /ai-sitemap.xml is generated | Leave on |
| Minimum GEO Score for Sitemap | Only content above this score appears in the AI sitemap | 50 is a good starting point |
| Bot Access Mode | Allow or block the bots listed below | Allow |
| Bot List | Which AI crawlers to manage | Keep the defaults unless you have a reason to change |
| Delete Data on Uninstall | Whether to wipe all plugin data when you delete the plugin | Leave off unless you're permanently removing it |
**For most sites, the defaults are fine.** Install, activate, and start filling in the per-post fields.

---

## How to use it day-to-day

When writing or editing a post:

1. **Scroll down** to the GEO Optimizer meta box below the editor
2. **Pick a schema type** if the default isn't right (most posts should stay on Article)
3. **Fill in the three AI fields:**
   - AI Summary — one clear sentence summarizing the post
   - Primary Entity — the main subject
   - Target Question — what question does this post answer?
4. **Check your score** — if it's yellow or red, read the suggestions and adjust your content
5. **Publish** — the plugin handles everything else automatically

That's it. The schema, meta tags, sitemap, and robots rules all update automatically.

---

## Installing on client sites

The plugin is designed for agency distribution:

1. Zip the `geo-optimzer` folder
2. On the client's site: **Plugins → Add Plugin → Upload Plugin → choose the .zip**
3. Activate and configure settings
4. Have the client go to **Settings → Permalinks** and click Save (this registers the AI sitemap URL)

The plugin is safe to install alongside RankMath, Yoast, WooCommerce, and Elementor. All internal names are prefixed to avoid conflicts.

---

## Removing the plugin

**Temporary removal (keeping data):**
- Deactivate the plugin. All settings and post data are preserved. Reactivate anytime.

**Permanent removal (keeping data):**
- Deactivate, then delete. With "Delete Data on Uninstall" unchecked (the default), all your settings and post meta stay in the database. If you reinstall later, everything picks up where you left off.

**Permanent removal (clean wipe):**
- Go to GEO Optimizer settings, check "Delete Data on Uninstall," and save
- Deactivate the plugin, then delete it
- All options, post meta, and transients are removed from the database

---

## Frequently asked questions

**Does this slow down my site?**
No. The plugin adds a small JSON-LD block and a few meta tags to each page. There are no external API calls, no JavaScript on the frontend, and no database queries on page load beyond reading cached post meta.

**Do I need to fill in the AI fields on every post?**
No, they're optional. But filling them in improves your GEO score and gives AI engines clearer signals about your content. Prioritize your highest-traffic pages.

**What if I already use RankMath or Yoast?**
They work fine together. The plugin uses its own namespaced meta tags (`geo-opt:`) that don't overlap with any SEO plugin. If RankMath has set a description for a post, the plugin respects that and skips its own summary tag.

**What if I don't use WooCommerce?**
The WooCommerce features simply don't load. No errors, no wasted resources. The plugin checks whether WooCommerce is active before initializing any product-related code.

**Can AI bots actually read this stuff?**
Yes. GPTBot, ClaudeBot, and other AI crawlers read structured data (JSON-LD), meta tags, and robots.txt directives. The hidden div with the `<dl>` structure is also parseable by LLMs when they crawl your page content. This is the same approach used by major publishers optimizing for AI citation.




1. Meta Box (Score + AI Fields)

  In the editor you're in now, click the three dots menu (...) top-right → Preferences → Panels → make sure "GEO Optimizer" is toggled on. Then scroll below the editor content — the meta box should appear there. Alternatively, you can switch to the classic editor by
  adding ?classic-editor to the URL, but you'd need the Classic Editor plugin.

  Easier approach: Just scroll down below the block editor content area. The meta box should be at the bottom of the page.

  2. Schema Output

  Open this post on the frontend (click the "View Post" link or the external link icon top-left), then View Page Source (right-click → View Page Source). Search for application/ld+json — you should see the Article schema with headline, author, dates, etc.

  3. AI Snippet Meta Tags

  In the same page source, search for geo-opt: — you should see the meta tags for ai-summary, primary-entity, and target-question (the import populated these).

  4. AI Sitemap

  Visit: plugin-test.local/ai-sitemap.xml
  If it shows "Page not found," go to Settings → Permalinks and click Save Changes first to flush rewrite rules, then try again.

  5. Robots.txt

  Visit: plugin-test.local/robots.txt
  You should see the GPTBot, ClaudeBot, PerplexityBot, and Google-Extended rules appended at the bottom.

  6. GEO Score

  Scroll down to the GEO Optimizer meta box on any post. You should see the score, color-coded bar, breakdown, and suggestions. Click "Recalculate Score" to test the AJAX recalculation.