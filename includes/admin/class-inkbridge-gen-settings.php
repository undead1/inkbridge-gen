<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Settings {

	private $settings = null;

	public function get( string $key, $default = null ) {
		$settings = $this->get_all();
		return $settings[ $key ] ?? $default;
	}

	public function get_all() {
		if ( null === $this->settings ) {
			$this->settings = get_option( 'inkbridge_gen_settings', array() );
		}
		return $this->settings;
	}

	public function save( string $section, array $data ) {
		$settings = $this->get_all();

		switch ( $section ) {
			case 'general':
				$settings['default_word_count']   = absint( $data['default_word_count'] ?? 1500 );
				$settings['default_post_status']  = sanitize_text_field( $data['default_post_status'] ?? 'draft' );
				$settings['default_author_id']    = absint( $data['default_author_id'] ?? 1 );
				$settings['schedule_delay_hours']  = absint( $data['schedule_delay_hours'] ?? 24 );
				$settings['translation_meta_key']  = sanitize_key( $data['translation_meta_key'] ?? '_inkbridge_gen_translations' );
				break;

			case 'text_providers':
				$settings['active_text_provider'] = sanitize_text_field( $data['active_text_provider'] ?? 'openai' );
				foreach ( array( 'openai', 'claude', 'gemini' ) as $pid ) {
					if ( isset( $data['text_providers'][ $pid ] ) ) {
						$settings['text_providers'][ $pid ] = array(
							'model'      => sanitize_text_field( $data['text_providers'][ $pid ]['model'] ?? '' ),
							'max_tokens' => absint( $data['text_providers'][ $pid ]['max_tokens'] ?? 4096 ),
						);
					}
					if ( isset( $data['api_keys'][ $pid ] ) && '' !== $data['api_keys'][ $pid ] ) {
						$this->set_provider_api_key( $pid, $data['api_keys'][ $pid ] );
					}
				}
				break;

			case 'image_providers':
				$settings['active_image_provider'] = sanitize_text_field( $data['active_image_provider'] ?? 'unsplash' );
				$settings['image_orientation']      = sanitize_text_field( $data['image_orientation'] ?? 'landscape' );
				$settings['image_search_suffix']    = sanitize_text_field( $data['image_search_suffix'] ?? '' );
				foreach ( array( 'unsplash', 'shutterstock', 'depositphotos' ) as $pid ) {
					if ( isset( $data['api_keys'][ $pid ] ) && '' !== $data['api_keys'][ $pid ] ) {
						$this->set_provider_api_key( $pid, $data['api_keys'][ $pid ] );
					}
				}
				break;

			case 'languages':
				$settings['languages'] = array();
				if ( ! empty( $data['languages'] ) && is_array( $data['languages'] ) ) {
					foreach ( $data['languages'] as $lang ) {
						$settings['languages'][] = array(
							'code'            => sanitize_key( $lang['code'] ?? '' ),
							'name'            => sanitize_text_field( $lang['name'] ?? '' ),
							'hreflang'        => sanitize_text_field( $lang['hreflang'] ?? '' ),
							'parent_category' => sanitize_text_field( $lang['parent_category'] ?? '' ),
							'is_source'       => ! empty( $lang['is_source'] ),
						);
					}
				}
				break;

			case 'pillars':
				$settings['pillars'] = array();
				if ( ! empty( $data['pillars'] ) && is_array( $data['pillars'] ) ) {
					foreach ( $data['pillars'] as $pillar ) {
						$cats = array();
						if ( ! empty( $pillar['categories'] ) && is_array( $pillar['categories'] ) ) {
							foreach ( $pillar['categories'] as $lang_code => $slug ) {
								$cats[ sanitize_key( $lang_code ) ] = sanitize_title( $slug );
							}
						}
						$settings['pillars'][] = array(
							'key'        => sanitize_key( $pillar['key'] ?? '' ),
							'label'      => sanitize_text_field( $pillar['label'] ?? '' ),
							'categories' => $cats,
							'context'    => wp_kses_post( $pillar['context'] ?? '' ),
						);
					}
				}
				break;

			case 'prompts':
				foreach ( array( 'prompt_generate_system', 'prompt_generate_user', 'prompt_translate_system', 'prompt_translate_user' ) as $key ) {
					if ( isset( $data[ $key ] ) ) {
						$settings[ $key ] = wp_kses_post( $data[ $key ] );
					}
				}
				break;

			case 'scheduling':
				$settings['cron_enabled']     = ! empty( $data['cron_enabled'] );
				$settings['cron_frequency']   = sanitize_text_field( $data['cron_frequency'] ?? 'daily' );
				$settings['cron_max_per_run'] = absint( $data['cron_max_per_run'] ?? 1 );
				// Auto-generate settings.
				$settings['autogen_enabled']     = ! empty( $data['autogen_enabled'] );
				$settings['autogen_pillars']     = array_map( 'sanitize_key', (array) ( $data['autogen_pillars'] ?? array() ) );
				$settings['autogen_frequency']   = sanitize_text_field( $data['autogen_frequency'] ?? 'daily' );
				$settings['autogen_time']        = preg_match( '/^\d{2}:\d{2}$/', $data['autogen_time'] ?? '' ) ? $data['autogen_time'] : '09:00';
				$settings['autogen_count']       = max( 1, min( 10, absint( $data['autogen_count'] ?? 1 ) ) );
				$settings['autogen_word_count']  = absint( $data['autogen_word_count'] ?? 1500 );
				$settings['autogen_post_status'] = sanitize_text_field( $data['autogen_post_status'] ?? 'draft' );
				break;
		}

		$this->settings = $settings;
		update_option( 'inkbridge_gen_settings', $settings );
	}

	// ─── Language / Pillar helpers ───────────────────────

	public function get_languages() {
		return $this->get( 'languages', array() );
	}

	public function get_source_language() {
		foreach ( $this->get_languages() as $lang ) {
			if ( ! empty( $lang['is_source'] ) ) {
				return $lang;
			}
		}
		$langs = $this->get_languages();
		return $langs[0] ?? array( 'code' => 'en', 'name' => 'English' );
	}

	public function get_target_languages() {
		return array_filter( $this->get_languages(), function ( $lang ) {
			return empty( $lang['is_source'] );
		} );
	}

	public function get_pillars() {
		return $this->get( 'pillars', array() );
	}

	public function get_pillar( string $key ) {
		foreach ( $this->get_pillars() as $pillar ) {
			if ( $pillar['key'] === $key ) {
				return $pillar;
			}
		}
		return null;
	}

	// ─── Provider helpers ────────────────────────────────

	public function get_active_text_provider() {
		return $this->get( 'active_text_provider', 'openai' );
	}

	public function get_active_image_provider() {
		return $this->get( 'active_image_provider', 'unsplash' );
	}

	public function get_text_provider_config( string $provider_id = '' ) {
		if ( ! $provider_id ) {
			$provider_id = $this->get_active_text_provider();
		}
		$providers = $this->get( 'text_providers', array() );
		return $providers[ $provider_id ] ?? array( 'model' => '', 'max_tokens' => 4096 );
	}

	public function get_provider_api_key( string $provider_id ) {
		$stored = get_option( 'inkbridge_gen_api_key_' . $provider_id, '' );
		if ( ! $stored ) {
			return '';
		}
		return $this->decrypt( $stored );
	}

	public function set_provider_api_key( string $provider_id, string $key ) {
		update_option( 'inkbridge_gen_api_key_' . $provider_id, $this->encrypt( $key ) );
	}

	public function has_provider_api_key( string $provider_id ) {
		return '' !== get_option( 'inkbridge_gen_api_key_' . $provider_id, '' );
	}

	// ─── Prompt helpers ──────────────────────────────────

	public function get_prompt( string $type ) {
		$key = 'prompt_' . $type;
		return $this->get( $key, '' );
	}

	// ─── System cron command ─────────────────────────────

	public function get_system_cron_command() {
		$path = ABSPATH;
		return "*/15 * * * * cd {$path} && php wp-cron.php > /dev/null 2>&1";
	}

	public function get_wp_cli_cron_command() {
		$path = ABSPATH;
		return "*/15 * * * * cd {$path} && wp cron event run inkbridge_gen_process_queue --quiet";
	}

	// ─── Encryption ──────────────────────────────────────

	private function encrypt( string $value ) {
		if ( '' === $value ) {
			return '';
		}
		$key    = hash( 'sha256', wp_salt( 'auth' ), true );
		$iv     = openssl_random_pseudo_bytes( 16 );
		$cipher = openssl_encrypt( $value, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
		return base64_encode( $iv . $cipher );
	}

	private function decrypt( string $stored ) {
		if ( '' === $stored ) {
			return '';
		}
		$key  = hash( 'sha256', wp_salt( 'auth' ), true );
		$data = base64_decode( $stored );
		if ( strlen( $data ) <= 16 ) {
			return '';
		}
		$iv = substr( $data, 0, 16 );
		return (string) openssl_decrypt( substr( $data, 16 ), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
	}
}
