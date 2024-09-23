<?php
require_once('wp-load.php'); // Ensure this path is correct relative to your script location
global $wpdb;

// Define the category ID you want to target
$category_id = 16; // Replace with your category ID

// Define the part of the meta description that remains constant
$description_before_company = "Stay updated on ";
$description_after_company = " shareholding insights. Explore ownership patterns, major shareholders, and investor sentiment.";

// Define the keyword that separates the company name from the rest of the title
$separation_keyword = "Shareholding pattern";

// Query to get all posts within a specific category
$posts_query = "
    SELECT p.ID, p.post_title
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    WHERE p.post_type = 'post'
    AND p.post_status = 'publish'
    AND tt.term_id = %d
    AND tt.taxonomy = 'category'
";

// Prepare and execute the query with the category ID
$posts_results = $wpdb->get_results($wpdb->prepare($posts_query, $category_id));

// Array to store post details of posts with meta descriptions longer than 160 characters
$posts_with_long_meta = [];

// Check if there are any posts
if ($posts_results) {
    foreach ($posts_results as $post) {
        // Retrieve the meta description (using Yoast SEO's meta key or appropriate meta key)
        $meta_description = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true); // Replace '_yoast_wpseo_metadesc' if using a different key

        // Check if the meta description length exceeds 160 characters
        if (strlen($meta_description) > 160) {
            // Extract company name from the title (assuming the company name is before "Shareholding pattern")
            $title_parts = explode($separation_keyword, $post->post_title, 2);
            $company_name = isset($title_parts[0]) ? trim($title_parts[0]) : 'Company';

            // Create the new meta description with the company name
            $new_meta_description = $description_before_company . $company_name . $description_after_company;

            // Update the meta description with the new value
            update_post_meta($post->ID, '_yoast_wpseo_metadesc', $new_meta_description);

            // Store the post title and ID
            $posts_with_long_meta[] = [
                'ID' => $post->ID,
                'title' => $post->post_title
            ];
        }
    }

    // Display the post details with meta descriptions longer than 160 characters
    if (!empty($posts_with_long_meta)) {
        echo 'The following posts had their meta descriptions updated:<br>';
        echo '<ul>';
        foreach ($posts_with_long_meta as $post) {
            echo '<li><strong>Post ID:</strong> ' . esc_html($post['ID']) . ' - <strong>Title:</strong> ' . esc_html($post['title']) . '</li>';
        }
        echo '</ul>';
    } else {
        echo 'No posts had their meta descriptions updated.';
    }
} else {
    // No posts found
    echo 'No posts found in this category.';
}
?>
