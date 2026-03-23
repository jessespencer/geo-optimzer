<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Geo_Opt_Settings {

    private const OPTION_GROUP = 'geo_opt_settings_group';
    private const OPTION_NAME  = 'geo_opt_settings';
    private const PAGE_SLUG    = 'geo-optimzer';

    public function init(): void {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_menu_page(): void {
        add_menu_page(
            'GEO Optimizer Settings',
            'GEO Optimizer',
            'manage_options',
            self::PAGE_SLUG,
            array( $this, 'render_page' ),
            'dashicons-chart-area',
            80
        );
    }

    public function register_settings(): void {
        register_setting( self::OPTION_GROUP, self::OPTION_NAME, array(
            'sanitize_callback' => array( $this, 'sanitize_settings' ),
        ) );

        // Schema section
        add_settings_section(
            'geo_opt_schema_section',
            'Schema / Structured Data',
            array( $this, 'render_schema_section' ),
            self::PAGE_SLUG
        );

        add_settings_field(
            'schema_enabled',
            'Enable Schema Markup',
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'geo_opt_schema_section',
            array( 'field' => 'schema_enabled', 'description' => 'Auto-inject JSON-LD schema on posts and pages.' )
        );

        add_settings_field(
            'schema_default_type',
            'Default Schema Type',
            array( $this, 'render_select_field' ),
            self::PAGE_SLUG,
            'geo_opt_schema_section',
            array(
                'field'   => 'schema_default_type',
                'options' => array(
                    'Article' => 'Article',
                    'FAQPage' => 'FAQ Page',
                    'HowTo'   => 'How To',
                    'Product' => 'Product',
                ),
                'description' => 'Default schema type for posts. Can be overridden per-post.',
            )
        );

        add_settings_field(
            'woocommerce_schema',
            'WooCommerce Product Schema',
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'geo_opt_schema_section',
            array( 'field' => 'woocommerce_schema', 'description' => 'Extend WooCommerce products with rich Product + Review schema.' )
        );

        // Scoring section
        add_settings_section(
            'geo_opt_scoring_section',
            'Content Scoring',
            array( $this, 'render_scoring_section' ),
            self::PAGE_SLUG
        );

        add_settings_field(
            'scoring_enabled',
            'Enable GEO Scoring',
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'geo_opt_scoring_section',
            array( 'field' => 'scoring_enabled', 'description' => 'Show GEO readiness score in the post editor.' )
        );

        // Snippet section
        add_settings_section(
            'geo_opt_snippet_section',
            'AI Snippet Optimization',
            array( $this, 'render_snippet_section' ),
            self::PAGE_SLUG
        );

        add_settings_field(
            'snippet_enabled',
            'Enable AI Snippets',
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'geo_opt_snippet_section',
            array( 'field' => 'snippet_enabled', 'description' => 'Inject AI-optimized meta tags and structured data for LLM parsing.' )
        );

        // Sitemap section
        add_settings_section(
            'geo_opt_sitemap_section',
            'AI Sitemap & Crawlability',
            array( $this, 'render_sitemap_section' ),
            self::PAGE_SLUG
        );

        add_settings_field(
            'sitemap_enabled',
            'Enable AI Sitemap',
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'geo_opt_sitemap_section',
            array( 'field' => 'sitemap_enabled', 'description' => 'Generate /ai-sitemap.xml for AI crawlers.' )
        );

        add_settings_field(
            'sitemap_min_score',
            'Minimum GEO Score for Sitemap',
            array( $this, 'render_number_field' ),
            self::PAGE_SLUG,
            'geo_opt_sitemap_section',
            array(
                'field'       => 'sitemap_min_score',
                'min'         => 0,
                'max'         => 100,
                'description' => 'Only include content with a GEO score at or above this threshold.',
            )
        );

        // Robots section
        add_settings_section(
            'geo_opt_robots_section',
            'AI Bot Access',
            array( $this, 'render_robots_section' ),
            self::PAGE_SLUG
        );

        add_settings_field(
            'robots_mode',
            'Bot Access Mode',
            array( $this, 'render_select_field' ),
            self::PAGE_SLUG,
            'geo_opt_robots_section',
            array(
                'field'   => 'robots_mode',
                'options' => array(
                    'allow' => 'Allow listed bots',
                    'block' => 'Block listed bots',
                ),
                'description' => 'Whether to allow or block the bots listed below in robots.txt.',
            )
        );

        add_settings_field(
            'robots_bots',
            'Bot List',
            array( $this, 'render_bot_list_field' ),
            self::PAGE_SLUG,
            'geo_opt_robots_section',
            array( 'field' => 'robots_bots' )
        );

        // Data section
        add_settings_section(
            'geo_opt_data_section',
            'Data Management',
            array( $this, 'render_data_section' ),
            self::PAGE_SLUG
        );

        add_settings_field(
            'delete_data_on_uninstall',
            'Delete Data on Uninstall',
            array( $this, 'render_checkbox_field' ),
            self::PAGE_SLUG,
            'geo_opt_data_section',
            array( 'field' => 'delete_data_on_uninstall', 'description' => 'When checked, all GEO Optimizer data (options, post meta, and transients) will be permanently removed when the plugin is deleted.' )
        );

    }

    public function sanitize_settings( array $input ): array {
        $sanitized = array();

        $sanitized['schema_enabled']           = ! empty( $input['schema_enabled'] );
        $sanitized['schema_default_type']      = sanitize_text_field( $input['schema_default_type'] ?? 'Article' );
        $sanitized['woocommerce_schema']       = ! empty( $input['woocommerce_schema'] );
        $sanitized['scoring_enabled']          = ! empty( $input['scoring_enabled'] );
        $sanitized['snippet_enabled']          = ! empty( $input['snippet_enabled'] );
        $sanitized['sitemap_enabled']          = ! empty( $input['sitemap_enabled'] );
        $sanitized['sitemap_min_score']        = absint( $input['sitemap_min_score'] ?? 50 );
        $sanitized['robots_mode']              = in_array( ( $input['robots_mode'] ?? '' ), array( 'allow', 'block' ), true ) ? $input['robots_mode'] : 'allow';
        $sanitized['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] );

        $bots = array();
        if ( ! empty( $input['robots_bots'] ) && is_array( $input['robots_bots'] ) ) {
            foreach ( $input['robots_bots'] as $bot ) {
                $bot = sanitize_text_field( trim( $bot ) );
                if ( $bot !== '' ) {
                    $bots[] = $bot;
                }
            }
        }
        $sanitized['robots_bots'] = $bots;

        if ( $sanitized['sitemap_min_score'] > 100 ) {
            $sanitized['sitemap_min_score'] = 100;
        }

        $valid_types = array( 'Article', 'FAQPage', 'HowTo', 'Product' );
        if ( ! in_array( $sanitized['schema_default_type'], $valid_types, true ) ) {
            $sanitized['schema_default_type'] = 'Article';
        }

        return $sanitized;
    }

    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap geo-opt-settings-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( self::PAGE_SLUG );
                submit_button( 'Save Settings' );
                ?>
            </form>
        </div>
        <?php
    }

    public function render_schema_section(): void {
        echo '<p>Configure how JSON-LD structured data is generated for your content.</p>';
    }

    public function render_scoring_section(): void {
        echo '<p>Configure the GEO readiness content scoring system.</p>';
    }

    public function render_snippet_section(): void {
        echo '<p>Configure AI-optimized meta tags and structured snippets for LLM engines.</p>';
    }

    public function render_sitemap_section(): void {
        echo '<p>Configure the AI-specific sitemap and crawlability settings.</p>';
    }

    public function render_robots_section(): void {
        echo '<p>Manage which AI bots are allowed or blocked via robots.txt rules.</p>';
    }

    public function render_data_section(): void {
        echo '<p>Control what happens to plugin data when GEO Optimizer is uninstalled.</p>';
    }

    public function render_checkbox_field( array $args ): void {
        $settings = get_option( self::OPTION_NAME, array() );
        $value    = ! empty( $settings[ $args['field'] ] );
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr( self::OPTION_NAME . '[' . $args['field'] . ']' ); ?>"
                   value="1"
                   <?php checked( $value ); ?> />
            <?php echo esc_html( $args['description'] ?? '' ); ?>
        </label>
        <?php
    }

    public function render_select_field( array $args ): void {
        $settings = get_option( self::OPTION_NAME, array() );
        $value    = $settings[ $args['field'] ] ?? '';
        ?>
        <select name="<?php echo esc_attr( self::OPTION_NAME . '[' . $args['field'] . ']' ); ?>">
            <?php foreach ( $args['options'] as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }

    public function render_number_field( array $args ): void {
        $settings = get_option( self::OPTION_NAME, array() );
        $value    = $settings[ $args['field'] ] ?? 0;
        ?>
        <input type="number"
               name="<?php echo esc_attr( self::OPTION_NAME . '[' . $args['field'] . ']' ); ?>"
               value="<?php echo esc_attr( $value ); ?>"
               min="<?php echo esc_attr( $args['min'] ?? 0 ); ?>"
               max="<?php echo esc_attr( $args['max'] ?? 100 ); ?>"
               class="small-text" />
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }

    public function render_bot_list_field( array $args ): void {
        $settings = get_option( self::OPTION_NAME, array() );
        $bots     = $settings['robots_bots'] ?? array( 'GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended' );
        ?>
        <div id="geo-opt-bot-list">
            <?php foreach ( $bots as $index => $bot ) : ?>
                <div class="geo-opt-bot-row">
                    <input type="text"
                           name="<?php echo esc_attr( self::OPTION_NAME . '[robots_bots][]' ); ?>"
                           value="<?php echo esc_attr( $bot ); ?>"
                           class="regular-text" />
                    <button type="button" class="button geo-opt-remove-bot">Remove</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button geo-opt-add-bot" id="geo-opt-add-bot">Add Bot</button>
        <p class="description">Enter the User-Agent names of AI bots to manage in robots.txt.</p>
        <?php
    }

}
