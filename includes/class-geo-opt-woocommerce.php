<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Geo_Opt_Woocommerce {

    public function init(): void {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        add_action( 'wp_head', array( $this, 'inject_product_schema' ), 2 );
        add_filter( 'geo_opt_skip_base_schema', array( $this, 'should_skip_base' ), 10, 2 );
    }

    public function should_skip_base( bool $skip, int $post_id ): bool {
        if ( get_post_type( $post_id ) === 'product' ) {
            return true;
        }
        return $skip;
    }

    public function inject_product_schema(): void {
        if ( ! is_singular( 'product' ) ) {
            return;
        }

        $product = wc_get_product( get_the_ID() );
        if ( ! $product ) {
            return;
        }

        $schema = $this->build_woo_product_schema( $product );

        echo "\n" . '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
        echo "\n" . '</script>' . "\n";
    }

    public function build_woo_product_schema( WC_Product $product ): array {
        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $product->get_name(),
            'description' => $product->get_short_description() ?: wp_trim_words( $product->get_description(), 55, '' ),
            'url'         => $product->get_permalink(),
        );

        // SKU
        $sku = $product->get_sku();
        if ( $sku ) {
            $schema['sku'] = $sku;
        }

        // Images
        $image_id = $product->get_image_id();
        if ( $image_id ) {
            $image_url = wp_get_attachment_image_url( $image_id, 'full' );
            if ( $image_url ) {
                $schema['image'] = $image_url;
            }
        }

        // Gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        if ( ! empty( $gallery_ids ) && ! empty( $schema['image'] ) ) {
            $images = array( $schema['image'] );
            foreach ( $gallery_ids as $gid ) {
                $gurl = wp_get_attachment_image_url( $gid, 'full' );
                if ( $gurl ) {
                    $images[] = $gurl;
                }
            }
            $schema['image'] = $images;
        }

        // Brand from product attribute
        $brand_attr = $product->get_attribute( 'brand' );
        if ( $brand_attr ) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name'  => $brand_attr,
            );
        } else {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name'  => get_bloginfo( 'name' ),
            );
        }

        // Offers
        $price = $product->get_price();
        if ( $price !== '' && $price !== null ) {
            $availability = $product->is_in_stock()
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock';

            $schema['offers'] = array(
                '@type'           => 'Offer',
                'url'             => $product->get_permalink(),
                'priceCurrency'   => get_woocommerce_currency(),
                'price'           => $price,
                'availability'    => $availability,
                'priceValidUntil' => gmdate( 'Y-12-31' ),
            );
        }

        // Aggregate rating
        $aggregate = $this->get_aggregate_rating( $product );
        if ( $aggregate ) {
            $schema['aggregateRating'] = $aggregate;
        }

        // Reviews
        $reviews = $this->build_review_schema( $product->get_id() );
        if ( ! empty( $reviews ) ) {
            $schema['review'] = $reviews;
        }

        return $schema;
    }

    public function build_review_schema( int $product_id ): array {
        $comments = get_comments( array(
            'post_id' => $product_id,
            'status'  => 'approve',
            'type'    => 'review',
            'number'  => 10,
            'orderby' => 'comment_date',
            'order'   => 'DESC',
        ) );

        if ( empty( $comments ) ) {
            // Fall back to regular comments
            $comments = get_comments( array(
                'post_id' => $product_id,
                'status'  => 'approve',
                'number'  => 10,
                'orderby' => 'comment_date',
                'order'   => 'DESC',
            ) );
        }

        $reviews = array();
        foreach ( $comments as $comment ) {
            $rating = get_comment_meta( $comment->comment_ID, 'rating', true );
            $review = array(
                '@type'      => 'Review',
                'author'     => array(
                    '@type' => 'Person',
                    'name'  => $comment->comment_author,
                ),
                'reviewBody' => $comment->comment_content,
                'datePublished' => get_comment_date( 'c', $comment ),
            );

            if ( $rating ) {
                $review['reviewRating'] = array(
                    '@type'       => 'Rating',
                    'ratingValue' => (string) $rating,
                    'bestRating'  => '5',
                );
            }

            $reviews[] = $review;
        }

        return $reviews;
    }

    public function get_aggregate_rating( WC_Product $product ): ?array {
        $rating_count = $product->get_rating_count();
        $avg_rating   = $product->get_average_rating();

        if ( $rating_count === 0 || empty( $avg_rating ) ) {
            return null;
        }

        return array(
            '@type'       => 'AggregateRating',
            'ratingValue' => $avg_rating,
            'reviewCount' => (string) $rating_count,
            'bestRating'  => '5',
            'worstRating' => '1',
        );
    }
}
