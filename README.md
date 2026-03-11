# Inkbridge Generator

AI-powered content generation, translation, and publishing pipeline for WordPress. Supports OpenAI, Claude, and Gemini for text generation, and Unsplash, Shutterstock, and Depositphotos for featured images.

## Requirements

- WordPress 6.0+
- PHP 8.0+
- OpenSSL extension (for API key encryption)

## Features

- **Multi-provider AI generation** — Generate articles using OpenAI (GPT-4o, GPT-4.1), Anthropic Claude (Sonnet 4, Haiku 4.5), or Google Gemini (2.0 Flash, 2.5 Pro/Flash)
- **Automatic translation** — Translate generated articles into multiple languages while preserving HTML, proper nouns, URLs, and metadata
- **Featured image sourcing** — Search and attach images from Unsplash, Shutterstock, or Depositphotos with proper attribution
- **Publishing pipeline** — Create WordPress posts with categories, tags, SEO metadata (RankMath compatible), and cross-linked translations
- **Background queue processing** — Articles are generated in the background; no need to keep the page open
- **Queue system** — Batch import topics via JSON, process them with priority-based ordering
- **Auto-generate on schedule** — AI suggests topics and queues articles automatically on a configurable schedule with pillar rotation
- **WP-Cron scheduling** — Automate queue processing on configurable intervals
- **Auto-updates** — Receive update notifications directly in wp-admin via GitHub releases
- **Encrypted API keys** — AES-256-CBC encryption for all stored provider credentials
- **Customizable prompts** — Full control over generation and translation prompts with placeholder support
- **Content pillars** — Organize content by pillars with per-language category mapping
- **Logging & monitoring** — Track API calls, token usage, errors, and durations with filterable log viewer

## Installation

1. Download the latest `inkbridge-gen-x.x.x.zip` from [GitHub Releases](https://github.com/undead1/inkbridge-gen/releases)
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin** and upload the ZIP
3. Activate the plugin
4. Navigate to **Inkbridge Generator > Settings** to configure your providers and languages

Existing installations receive automatic update notifications in wp-admin when new releases are published.

## Configuration

### Text Providers

Configure at **Inkbridge Generator > Settings > Text Providers**. Select your active provider and enter API keys.

| Provider | Models | Get API Key |
|----------|--------|-------------|
| OpenAI | gpt-4o-mini, gpt-4o, gpt-4.1-nano, gpt-4.1-mini, gpt-4.1 | [platform.openai.com/api-keys](https://platform.openai.com/api-keys) |
| Claude | claude-sonnet-4-20250514, claude-haiku-4-5-20251001 | [console.anthropic.com/settings/keys](https://console.anthropic.com/settings/keys) |
| Gemini | gemini-2.0-flash, gemini-2.5-pro, gemini-2.5-flash | [aistudio.google.com/apikey](https://aistudio.google.com/apikey) (free tier available) |

### Image Providers

Configure at **Inkbridge Generator > Settings > Image Providers**. Set orientation preference and optional search suffix.

| Provider | Auth Type | Notes | Get API Key |
|----------|-----------|-------|-------------|
| Unsplash | Access Key | Free for up to 50 requests/hour | [unsplash.com/developers](https://unsplash.com/developers) |
| Shutterstock | Bearer token | Requires active subscription for licensing | [shutterstock.com/developers](https://www.shutterstock.com/developers) |
| Depositphotos | API key | Requires deposit or subscription | [depositphotos.com/api-integration](https://depositphotos.com/api-integration.html) |

### Languages

Configure at **Inkbridge Generator > Settings > Languages**. Define language codes, hreflang values, parent category slugs, and designate one source language.

Articles are generated in the source language first, then translated to all other configured languages.

### Content Pillars

Configure at **Inkbridge Generator > Settings > Categories**. Each pillar maps to a WordPress category per language. Pillar context is injected into AI prompts to guide content tone and focus.

### Prompts

Configure at **Inkbridge Generator > Settings > Prompts**. Customize system and user prompts for both generation and translation.

**Generation placeholders:** `{{topic}}`, `{{word_count}}`, `{{pillar}}`, `{{pillar_context}}`, `{{extra_context}}`

**Translation placeholders:** `{{lang_name}}`, `{{article_title}}`, `{{article_content}}`, `{{article_excerpt}}`, `{{article_seo_title}}`, `{{article_seo_description}}`, `{{article_focus_keyword}}`, `{{article_tags}}`

## Usage

### Single Article Generation

1. Go to **Inkbridge Generator > Generate**
2. Enter a topic, select a content pillar, choose languages and word count
3. Click **Generate Article** to queue and process in the background, or **Add to Queue** to process later

Generation runs in the background — you can navigate away and the article will still be created. The pipeline executes in order: Generate → Translate → Fetch Image → Publish.

### Queue Management

1. Go to **Inkbridge Generator > Queue**
2. Import topics via JSON:

```json
[
  {
    "topic": "Article Topic Here",
    "pillar": "pillar-key",
    "word_count": 1500,
    "languages": ["en", "ms"],
    "extra_context": "Optional context"
  }
]
```

3. Process items individually or let WP-Cron handle them automatically

### Scheduling

Configure at **Inkbridge Generator > Settings > Scheduling**.

**Queue Processing** — Enable WP-Cron processing and set the frequency (hourly, twice daily, daily, or weekly) and max items per run.

**Auto-Generate** — Enable automatic topic generation. Configure:
- Which content pillars to rotate through
- Frequency (twice daily, daily, or weekly)
- Start time (uses your WordPress timezone)
- Articles per run (1–10)
- Word count and post status (draft/publish)

The system uses AI to suggest topics for each pillar, then inserts them into the queue for processing.

For reliable scheduling, use a system cron instead of WP-Cron:

```bash
# WP-Cron via PHP
*/15 * * * * cd /path/to/wordpress && php wp-cron.php > /dev/null 2>&1

# WP-CLI (preferred)
*/15 * * * * cd /path/to/wordpress && wp cron event run inkbridge_gen_process_queue --quiet
```

Add `define( 'DISABLE_WP_CRON', true );` to `wp-config.php` when using system cron.

## Pipeline Architecture

```
┌─────────────┐     ┌──────────────┐     ┌───────────────┐     ┌───────────────┐
│  Generator   │────▶│  Translator  │────▶│ Image Handler │────▶│   Publisher   │
│              │     │              │     │               │     │               │
│ Source lang  │     │ Per-language  │     │ Search/download│    │ Create posts  │
│ article JSON │     │ translation  │     │ sideload to WP│     │ Set categories│
│              │     │              │     │               │     │ Cross-link    │
└─────────────┘     └──────────────┘     └───────────────┘     └───────────────┘
```

Each step logs token usage, duration, and errors to the logs table.

## Generated Article Structure

The AI returns a structured JSON object:

| Field | Description |
|-------|-------------|
| `title` | SEO-optimized title (under 60 chars) |
| `slug` | URL-safe slug |
| `content` | Full HTML article body |
| `excerpt` | 25-35 word summary |
| `seo_title` | Meta title for SEO |
| `seo_description` | Meta description |
| `focus_keyword` | Primary SEO keyword |
| `tags` | Array of 5+ relevant tags |

## Admin Pages

| Page | Description |
|------|-------------|
| **Dashboard** | Stats overview, recent generations, provider status, quick generate |
| **Generate** | Single article generation with advanced options |
| **Queue** | Batch import, queue management, process/retry/delete items |
| **Settings** | General, text providers, image providers, languages, pillars, prompts, scheduling |
| **Logs** | Filterable log viewer with token usage stats and pagination |

## Database

The plugin creates two custom tables on activation:

- `{prefix}inkbridge_gen_logs` — API call logs with token counts, durations, and error tracking
- `{prefix}inkbridge_gen_queue` — Queue items with topic, pillar, languages, status, and results

## Security

- API keys encrypted with AES-256-CBC using `wp_salt('auth')` derived key
- Nonce verification on all AJAX actions
- `manage_options` capability checks on all admin pages and AJAX handlers
- Parameterized database queries via `wpdb->prepare()`
- Input sanitization and output escaping throughout

## Uninstall

On uninstall, the plugin removes:
- Both custom database tables
- All stored settings and encrypted API keys
- Scheduled cron events

Published posts and media attachments are preserved.

## License

GPL-2.0+
