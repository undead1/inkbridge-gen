<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Publisher {

	private $settings;
	private $logger;

	public function __construct( Inkbridge_Gen_Settings $settings, Inkbridge_Gen_Logger $logger ) {
		$this->settings = $settings;
		$this->logger   = $logger;
	}

	public function create_post( array $article, string $language, string $pillar, array $options = array() ): int {
		$status           = $options['status'] ?? $this->settings->get( 'default_post_status', 'draft' );
		$author_id        = $options['author_id'] ?? $this->settings->get( 'default_author_id', 1 );
		$featured_image_id = $options['featured_image_id'] ?? 0;

		// Resolve category IDs.
		$category_ids = $this->resolve_categories( $language, $pillar );

		// Resolve tags.
		$tag_ids = array();
		if ( ! empty( $article['tags'] ) && is_array( $article['tags'] ) ) {
			foreach ( $article['tags'] as $tag_name ) {
				$tag_id = $this->get_or_create_tag( $tag_name );
				if ( $tag_id ) {
					$tag_ids[] = $tag_id;
				}
			}
		}

		// Build post data.
		$post_data = array(
			'post_title'     => sanitize_text_field( $article['title'] ?? '' ),
			'post_content'   => wp_kses_post( $article['content'] ?? '' ),
			'post_excerpt'   => sanitize_textarea_field( $article['excerpt'] ?? '' ),
			'post_name'      => sanitize_title( $article['slug'] ?? '' ),
			'post_status'    => $status,
			'post_author'    => $author_id,
			'post_category'  => $category_ids,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);

		// Handle scheduled publishing.
		if ( 'future' === $status ) {
			$delay_hours = $this->settings->get( 'schedule_delay_hours', 24 );
			$post_data['post_date']     = gmdate( 'Y-m-d H:i:s', time() + ( $delay_hours * 3600 ) );
			$post_data['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', time() + ( $delay_hours * 3600 ) );
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			throw new \RuntimeException( 'Failed to create post: ' . $post_id->get_error_message() );
		}

		// Set tags.
		if ( ! empty( $tag_ids ) ) {
			wp_set_post_terms( $post_id, $tag_ids, 'post_tag' );
		}

		// Set featured image.
		if ( $featured_image_id ) {
			set_post_thumbnail( $post_id, $featured_image_id );
		}

		// Set RankMath SEO meta.
		$this->set_seo_meta( $post_id, $article );

		return $post_id;
	}

	public function set_seo_meta( int $post_id, array $article ): void {
		if ( ! empty( $article['seo_title'] ) ) {
			update_post_meta( $post_id, 'rank_math_title', sanitize_text_field( $article['seo_title'] ) );
		}
		if ( ! empty( $article['seo_description'] ) ) {
			update_post_meta( $post_id, 'rank_math_description', sanitize_text_field( $article['seo_description'] ) );
		}
		if ( ! empty( $article['focus_keyword'] ) ) {
			update_post_meta( $post_id, 'rank_math_focus_keyword', sanitize_text_field( $article['focus_keyword'] ) );
		}
	}

	public function set_translation_links( array $post_ids ): void {
		$meta_key = $this->settings->get( 'translation_meta_key', '_inkbridge_gen_translations' );

		// Filter out empty/zero values.
		$post_ids = array_filter( $post_ids );

		foreach ( $post_ids as $lang => $post_id ) {
			update_post_meta( $post_id, $meta_key, $post_ids );
		}
	}

	public function publish_multilingual( array $articles, int $featured_image_id = 0, array $options = array() ): array {
		$result   = array( 'posts' => array(), 'errors' => array() );
		$post_ids = array();

		foreach ( $articles as $lang => $article ) {
			if ( isset( $article['error'] ) ) {
				$result['errors'][ $lang ] = $article['error'];
				continue;
			}

			try {
				$opts = array_merge( $options, array( 'featured_image_id' => $featured_image_id ) );
				$post_id = $this->create_post( $article, $lang, $article['pillar'] ?? '', $opts );
				$post_ids[ $lang ] = $post_id;

				$result['posts'][ $lang ] = array(
					'post_id'  => $post_id,
					'url'      => get_permalink( $post_id ),
					'edit_url' => get_edit_post_link( $post_id, 'raw' ),
					'title'    => $article['title'],
					'status'   => get_post_status( $post_id ),
				);
			} catch ( \RuntimeException $e ) {
				$result['errors'][ $lang ] = $e->getMessage();
				$this->logger->log_error( 'publish', 'wordpress', $e->getMessage(), $article['title'] ?? '', $lang );
			}
		}

		// Cross-link translations.
		if ( count( $post_ids ) > 1 ) {
			$this->set_translation_links( $post_ids );
		}

		$result['success'] = empty( $result['errors'] ) || ! empty( $result['posts'] );
		return $result;
	}

	private function resolve_categories( string $language, string $pillar ): array {
		$ids = array();

		// Language parent category.
		$languages = $this->settings->get_languages();
		$parent_slug = '';
		foreach ( $languages as $lang ) {
			if ( $lang['code'] === $language ) {
				$parent_slug = $lang['parent_category'];
				break;
			}
		}

		if ( $parent_slug ) {
			$term = get_term_by( 'slug', $parent_slug, 'category' );
			if ( $term ) {
				$ids[] = $term->term_id;
			}
		}

		// Pillar child category.
		if ( $pillar ) {
			$pillar_data = $this->settings->get_pillar( $pillar );
			if ( $pillar_data && ! empty( $pillar_data['categories'][ $language ] ) ) {
				$pillar_slug = $pillar_data['categories'][ $language ];
				$term = get_term_by( 'slug', $pillar_slug, 'category' );
				if ( $term ) {
					$ids[] = $term->term_id;
				}
			}
		}

		return $ids;
	}

	private function get_or_create_tag( string $tag_name ): int {
		$tag_name = sanitize_text_field( trim( $tag_name ) );
		if ( '' === $tag_name ) {
			return 0;
		}

		$term = get_term_by( 'name', $tag_name, 'post_tag' );
		if ( $term ) {
			return $term->term_id;
		}

		$result = wp_insert_term( $tag_name, 'post_tag' );
		if ( is_wp_error( $result ) ) {
			return 0;
		}

		return $result['term_id'];
	}
}
