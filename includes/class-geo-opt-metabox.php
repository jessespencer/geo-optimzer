<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Geo_Opt_Metabox {

    public function init(): void {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 2 );
    }

    public function register_meta_boxes(): void {
        $screens = array( 'post', 'page', 'product' );
        add_meta_box(
            'geo_opt_meta_box',
            'GEO Optimizer',
            array( $this, 'render_meta_box' ),
            $screens,
            'normal',
            'high'
        );
    }

    public function render_meta_box( WP_Post $post ): void {
        wp_nonce_field( 'geo_opt_meta_nonce_action', 'geo_opt_meta_nonce' );

        $schema_type     = get_post_meta( $post->ID, '_geo_opt_schema_type', true );
        $ai_summary      = get_post_meta( $post->ID, '_geo_opt_ai_summary', true );
        $primary_entity  = get_post_meta( $post->ID, '_geo_opt_primary_entity', true );
        $target_question = get_post_meta( $post->ID, '_geo_opt_target_question', true );
        $score           = get_post_meta( $post->ID, '_geo_opt_score', true );
        $breakdown       = get_post_meta( $post->ID, '_geo_opt_score_breakdown', true );

        $settings         = get_option( 'geo_opt_settings', array() );
        $scoring_enabled  = ! empty( $settings['scoring_enabled'] );
        ?>

        <!-- Schema Type Override -->
        <div class="geo-opt-metabox-section">
            <h4>Schema Type</h4>
            <label for="geo_opt_schema_type">Override default schema type for this content:</label>
            <select name="geo_opt_schema_type" id="geo_opt_schema_type">
                <option value="" <?php selected( $schema_type, '' ); ?>>Default (from settings)</option>
                <option value="Article" <?php selected( $schema_type, 'Article' ); ?>>Article</option>
                <option value="BlogPosting" <?php selected( $schema_type, 'BlogPosting' ); ?>>Blog Post</option>
                <option value="FAQPage" <?php selected( $schema_type, 'FAQPage' ); ?>>FAQ Page</option>
                <option value="HowTo" <?php selected( $schema_type, 'HowTo' ); ?>>How To</option>
                <option value="LocalBusiness" <?php selected( $schema_type, 'LocalBusiness' ); ?>>Local Business</option>
                <option value="Product" <?php selected( $schema_type, 'Product' ); ?>>Product</option>
                <option value="WebPage" <?php selected( $schema_type, 'WebPage' ); ?>>Web Page</option>
            </select>
        </div>

        <!-- AI Snippet Optimization -->
        <div class="geo-opt-metabox-section">
            <h4>AI Snippet Optimization</h4>

            <label for="geo_opt_ai_summary">AI Summary</label>
            <textarea name="geo_opt_ai_summary"
                      id="geo_opt_ai_summary"
                      placeholder="A concise summary optimized for AI engines to extract and display as a snippet."
            ><?php echo esc_textarea( $ai_summary ); ?></textarea>

            <label for="geo_opt_primary_entity">Primary Entity</label>
            <input type="text"
                   name="geo_opt_primary_entity"
                   id="geo_opt_primary_entity"
                   value="<?php echo esc_attr( $primary_entity ); ?>"
                   placeholder="The main subject or entity this content is about" />

            <label for="geo_opt_target_question">Target Question</label>
            <input type="text"
                   name="geo_opt_target_question"
                   id="geo_opt_target_question"
                   value="<?php echo esc_attr( $target_question ); ?>"
                   placeholder="The primary question this content answers" />
        </div>

        <!-- GEO Content Score -->
        <?php if ( $scoring_enabled ) : ?>
        <div class="geo-opt-metabox-section">
            <h4>GEO Content Score</h4>

            <?php if ( $score !== '' && $score !== false ) :
                $score      = (int) $score;
                $color_class = $score < 40 ? 'red' : ( $score < 70 ? 'yellow' : 'green' );
            ?>
                <div id="geo-opt-score-area">
                    <div class="geo-opt-score-display">
                        <div id="geo-opt-score-number" class="geo-opt-score-number geo-opt-score-<?php echo esc_attr( $color_class ); ?>-text">
                            <?php echo esc_html( $score ); ?>
                        </div>
                        <div class="geo-opt-score-label">GEO Score</div>
                        <div class="geo-opt-score-bar">
                            <div id="geo-opt-score-bar-fill"
                                 class="geo-opt-score-bar-fill geo-opt-score-<?php echo esc_attr( $color_class ); ?>"
                                 style="width: <?php echo esc_attr( $score ); ?>%;">
                            </div>
                        </div>
                    </div>

                    <?php if ( is_array( $breakdown ) && ! empty( $breakdown ) ) : ?>
                    <div id="geo-opt-breakdown" class="geo-opt-breakdown">
                        <?php
                        $labels = array(
                            'direct_answers'    => 'Direct Answers',
                            'question_headings' => 'Question Headings',
                            'reading_level'     => 'Reading Level',
                            'entity_clarity'    => 'Entity Clarity',
                            'content_length'    => 'Content Length',
                        );
                        foreach ( $breakdown as $key => $val ) :
                            if ( ! isset( $labels[ $key ] ) ) {
                                continue;
                            }
                        ?>
                            <div class="geo-opt-breakdown-item">
                                <span class="geo-opt-breakdown-label"><?php echo esc_html( $labels[ $key ] ); ?></span>
                                <span class="geo-opt-breakdown-score"><?php echo esc_html( $val ); ?> / 20</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else : ?>
                    <div id="geo-opt-breakdown" class="geo-opt-breakdown"></div>
                    <?php endif; ?>

                    <ul id="geo-opt-suggestions" class="geo-opt-suggestions">
                        <?php
                        if ( is_array( $breakdown ) && ! empty( $breakdown ) ) {
                            $scorer = new Geo_Opt_Scoring();
                            $content = apply_filters( 'the_content', $post->post_content );
                            $suggestions = $scorer->generate_suggestions( $breakdown, $content, $post->ID );
                            if ( empty( $suggestions ) ) {
                                echo '<li style="background:#edf7ed;border-left-color:#00a32a;">Great job! Your content is well-optimized for AI engines.</li>';
                            } else {
                                foreach ( $suggestions as $suggestion ) {
                                    echo '<li>' . esc_html( $suggestion ) . '</li>';
                                }
                            }
                        }
                        ?>
                    </ul>
                </div>

                <div id="geo-opt-no-score" class="geo-opt-no-score" style="display:none;">
                    Save or publish the post to calculate the GEO score, or click Recalculate.
                </div>

            <?php else : ?>
                <div id="geo-opt-score-area" style="display:none;">
                    <div class="geo-opt-score-display">
                        <div id="geo-opt-score-number" class="geo-opt-score-number">0</div>
                        <div class="geo-opt-score-label">GEO Score</div>
                        <div class="geo-opt-score-bar">
                            <div id="geo-opt-score-bar-fill" class="geo-opt-score-bar-fill" style="width:0%;"></div>
                        </div>
                    </div>
                    <div id="geo-opt-breakdown" class="geo-opt-breakdown"></div>
                    <ul id="geo-opt-suggestions" class="geo-opt-suggestions"></ul>
                </div>

                <div id="geo-opt-no-score" class="geo-opt-no-score">
                    Save or publish the post to calculate the GEO score, or click Recalculate.
                </div>
            <?php endif; ?>

            <div class="geo-opt-recalculate">
                <button type="button" id="geo-opt-recalculate-btn" class="button button-secondary">
                    <span id="geo-opt-recalculate-spinner" class="spinner"></span>
                    Recalculate Score
                </button>
            </div>
        </div>
        <?php endif; ?>

        <?php
    }

    public function save_meta_box( int $post_id, WP_Post $post ): void {
        if ( ! isset( $_POST['geo_opt_meta_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['geo_opt_meta_nonce'], 'geo_opt_meta_nonce_action' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $allowed = array( 'post', 'page', 'product' );
        if ( ! in_array( $post->post_type, $allowed, true ) ) {
            return;
        }

        $fields = array(
            'geo_opt_schema_type'     => '_geo_opt_schema_type',
            'geo_opt_primary_entity'  => '_geo_opt_primary_entity',
            'geo_opt_target_question' => '_geo_opt_target_question',
        );

        foreach ( $fields as $input_name => $meta_key ) {
            $value = sanitize_text_field( $_POST[ $input_name ] ?? '' );
            if ( $value !== '' ) {
                update_post_meta( $post_id, $meta_key, $value );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        }

        // AI summary uses textarea sanitization
        $ai_summary = sanitize_textarea_field( $_POST['geo_opt_ai_summary'] ?? '' );
        if ( $ai_summary !== '' ) {
            update_post_meta( $post_id, '_geo_opt_ai_summary', $ai_summary );
        } else {
            delete_post_meta( $post_id, '_geo_opt_ai_summary' );
        }
    }
}
