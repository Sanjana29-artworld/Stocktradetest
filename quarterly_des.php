<?php
require_once('wp-load.php'); // Ensure this path is correct relative to your script location
global $wpdb;

// Define the category ID you want to target
$category_id = 15; // Replace with your category ID

// Define the part of the meta description that remains constant
$description_before_company = "Check out ";
$description_after_company = "Report. Get a detailed look at the company's financial performance and key metrics.";

// Define the keywords used to extract the company name
$keyword_before_announced = 'announced';
$keyword_after_results = 'Financial Results';

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
            // Extract company name and period from the title
            $title = $post->post_title;

            // Find positions of the keywords
            $pos_before_announced = strpos($title, $keyword_before_announced);
            $pos_after_results = strpos($title, $keyword_after_results);

            if ($pos_before_announced !== false && $pos_after_results !== false && $pos_before_announced > $pos_after_results) {
                // Extract company name and period
                $company_period = substr($title, 0, $pos_before_announced);
                $company_period .= ' ' . substr($title, $pos_after_results + strlen($keyword_after_results));

                // Trim any extra spaces
                $company_period = trim($company_period);

                // Debugging information
                echo '<p><strong>Title:</strong> ' . esc_html($title) . '</p>';
                echo '<p><strong>Extracted:</strong> ' . esc_html($company_period) . '</p>';
            } else {
                $company_period = 'Company Period'; // Default value if keywords are not found

                // Debugging information
                echo '<p><strong>Company Period Default:</strong> ' . esc_html($company_period) . '</p>';
            }

            // Create the new meta description with the extracted company period
            $new_meta_description = $description_before_company . $company_period . $description_after_company;

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
