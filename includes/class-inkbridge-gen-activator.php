<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Activator {

	public static function activate() {
		self::create_tables();
		self::set_default_options();
		flush_rewrite_rules();
	}

	private static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		$logs_table  = $wpdb->prefix . 'inkbridge_gen_logs';
		$queue_table = $wpdb->prefix . 'inkbridge_gen_queue';

		$sql = "CREATE TABLE {$logs_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			type VARCHAR(50) NOT NULL DEFAULT '',
			provider VARCHAR(30) NOT NULL DEFAULT '',
			model VARCHAR(100) NOT NULL DEFAULT '',
			prompt_tokens INT UNSIGNED NOT NULL DEFAULT 0,
			completion_tokens INT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'success',
			error TEXT NULL,
			topic VARCHAR(500) NOT NULL DEFAULT '',
			language VARCHAR(10) NOT NULL DEFAULT '',
			duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_created_at (created_at),
			KEY idx_type (type),
			KEY idx_status (status),
			KEY idx_provider (provider)
		) {$charset};

		CREATE TABLE {$queue_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			topic VARCHAR(500) NOT NULL DEFAULT '',
			pillar VARCHAR(100) NOT NULL DEFAULT '',
			word_count INT UNSIGNED NOT NULL DEFAULT 0,
			languages TEXT NOT NULL,
			extra_context TEXT NULL,
			options TEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			priority INT NOT NULL DEFAULT 10,
			result_data LONGTEXT NULL,
			error_message TEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			processed_at DATETIME NULL,
			PRIMARY KEY (id),
			KEY idx_status (status),
			KEY idx_priority_created (priority, created_at),
			KEY idx_created_at (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'inkbridge_gen_db_version', '1.1' );
	}

	private static function set_default_options() {
		if ( false !== get_option( 'inkbridge_gen_settings' ) ) {
			return;
		}

		$defaults = array(
			'default_word_count'      => 1500,
			'default_post_status'     => 'draft',
			'default_author_id'       => get_current_user_id() ?: 1,
			'schedule_delay_hours'    => 24,
			'translation_meta_key'    => '_inkbridge_gen_translations',

			'active_text_provider'    => 'openai',
			'active_image_provider'   => 'unsplash',

			'text_providers'          => array(
				'openai' => array( 'model' => 'gpt-4o-mini', 'max_tokens' => 4096 ),
				'claude' => array( 'model' => 'claude-sonnet-4-20250514', 'max_tokens' => 4096 ),
				'gemini' => array( 'model' => 'gemini-2.0-flash', 'max_tokens' => 4096 ),
			),

			'image_orientation'       => 'landscape',
			'image_search_suffix'     => '',

			'languages'               => array(
				array(
					'code'            => 'en',
					'name'            => 'English',
					'hreflang'        => 'en',
					'parent_category' => 'en',
					'is_source'       => true,
				),
			),

			'pillars'                 => array(),

			'prompt_generate_system'  => "You are an expert content writer for a professional website.\n\nYour writing style:\n- Professional but approachable\n- Data-driven with specific numbers, names, and addresses where relevant\n- SEO-optimized with natural keyword usage (no keyword stuffing)\n- Structured with clear H2 and H3 headings for scannability\n- Practical and actionable\n\n{{pillar_context}}\n\nIMPORTANT: Write in standard HTML for WordPress Classic Editor. Use <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong>, <em>, <blockquote> tags. Do NOT use Markdown. Do NOT include <h1>. Do NOT include <html>, <body>, or <article> tags.",

			'prompt_generate_user'    => "Write a comprehensive article about: {{topic}}\n\nTarget word count: approximately {{word_count}} words.\nContent pillar: {{pillar}}\n{{extra_context}}\n\nReturn your response as a JSON object with these exact keys:\n{\n    \"title\": \"The article title (compelling, SEO-friendly, under 60 characters)\",\n    \"slug\": \"url-friendly-slug-with-hyphens\",\n    \"content\": \"The full article HTML content\",\n    \"excerpt\": \"A compelling 25-35 word excerpt/summary for archive cards\",\n    \"seo_title\": \"SEO title for RankMath (can differ from article title, under 60 chars)\",\n    \"seo_description\": \"Meta description for search engines (150-160 characters)\",\n    \"focus_keyword\": \"Primary focus keyword (2-4 words)\",\n    \"tags\": [\"tag1\", \"tag2\", \"tag3\", \"tag4\", \"tag5\"]\n}\n\nReturn ONLY the JSON object, no other text before or after it.",

			'prompt_translate_system' => "You are an expert translator specializing in {{lang_name}}.\n\nTranslation rules:\n- Translate naturally, not word-for-word. The text should read as if originally written in {{lang_name}}.\n- Preserve all HTML tags exactly (<h2>, <h3>, <p>, <ul>, <li>, <strong>, <em>, <blockquote>).\n- Do NOT translate proper nouns (company names, place names, building names, etc.)\n- Keep numbers, addresses, phone numbers, and URLs unchanged.\n- Use the standard formal register appropriate for an informational website.\n- Preserve the SEO intent — the translated version should target equivalent keywords in {{lang_name}}.",

			'prompt_translate_user'   => "Translate this article from English to {{lang_name}}.\n\nSource article:\n- Title: {{article_title}}\n- Content (HTML): {{article_content}}\n- Excerpt: {{article_excerpt}}\n- SEO Title: {{article_seo_title}}\n- SEO Description: {{article_seo_description}}\n- Focus Keyword: {{article_focus_keyword}}\n- Tags: {{article_tags}}\n\nReturn your translation as a JSON object with these exact keys:\n{\n    \"title\": \"Translated article title\",\n    \"slug\": \"translated-url-slug-in-target-language\",\n    \"content\": \"Full translated HTML content\",\n    \"excerpt\": \"Translated excerpt (25-35 words)\",\n    \"seo_title\": \"Translated SEO title (under 60 chars)\",\n    \"seo_description\": \"Translated meta description (150-160 chars)\",\n    \"focus_keyword\": \"Translated focus keyword in {{lang_name}}\",\n    \"tags\": [\"translated-tag1\", \"translated-tag2\"]\n}\n\nReturn ONLY the JSON object, no other text.",

			'cron_enabled'            => false,
			'cron_frequency'          => 'daily',
			'cron_max_per_run'        => 1,
		);

		update_option( 'inkbridge_gen_settings', $defaults );
	}
}
