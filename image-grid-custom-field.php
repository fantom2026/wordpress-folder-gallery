<?php
/**
 * Template Name: Image Grid - Custom Field Folder
 * Description: Robust pagination for Post Name permalinks
 */

get_header(); 
?>

<div style="max-width: 1400px; margin: 40px auto; padding: 20px;">
    <h1><?php the_title(); ?></h1>

    <?php
    $custom_path = get_post_meta(get_the_ID(), 'image_folder', true);

    if (empty($custom_path)) {
        echo '<p style="color:red;">Please add Custom Field <strong>image_folder</strong>.</p>';
        get_footer();
        exit;
    }

    $image_folder = rtrim($custom_path, '/') . '/';

    if (!is_dir($image_folder)) {
        echo '<p style="color:red;">Folder not found.</p>';
        get_footer();
        exit;
    }

    $display_name = basename(rtrim($custom_path, '/'));
    echo '<p><strong>Image Folder:</strong> ' . esc_html($display_name) . '</p>';

    $current_sub = isset($_GET['sub']) ? sanitize_text_field($_GET['sub']) : '';
    $full_path = $image_folder . ltrim($current_sub, '/');
    $full_path = rtrim($full_path, '/') . '/';

    if (!is_dir($full_path)) {
        $full_path = $image_folder;
        $current_sub = '';
    }

    $per_page = 100;
    $current_page = max(1, intval($_GET['paged'] ?? 1));

    $images = [];
    $subfolders = [];

    foreach (scandir($full_path) as $item) {
        if ($item === '.' || $item === '..') continue;
        $item_path = $full_path . $item;

        if (is_dir($item_path)) {
            $subfolders[] = $item;
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp','bmp'])) {
                $images[] = $item;
            }
        }
    }

    usort($images, fn($a,$b) => filemtime($full_path.$b) - filemtime($full_path.$a));
    sort($subfolders);

    $total_images = count($images);
    $total_pages = ceil($total_images / $per_page);
    $offset = ($current_page - 1) * $per_page;
    $paged_images = array_slice($images, $offset, $per_page);

    echo '<p><strong>Location:</strong> ' . esc_html($current_sub ?: 'Root') . ' — ' . $total_images . ' images</p>';
    ?>

    <?php if (!empty($subfolders) || $current_sub): ?>
    <div style="margin:20px 0 30px 0;">
        <?php if ($current_sub): ?>
            <a href="<?php echo esc_url(add_query_arg(['sub' => dirname($current_sub), 'paged' => 1])); ?>">← Back</a>
        <?php endif; ?>

        <strong>Subfolders:</strong><br><br>
        <?php foreach ($subfolders as $sub): ?>
            <a href="<?php echo esc_url(add_query_arg(['sub' => trim($current_sub.'/'.$sub,'/'), 'paged' => 1])); ?>" 
               style="display:inline-block; margin:6px; padding:10px 16px; background:#f0f0f0; border-radius:8px;">
                📁 <?php echo esc_html($sub); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($paged_images)): ?>
        <p>No images found.</p>
    <?php else: ?>
        <div class="image-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px;">
            <?php foreach ($paged_images as $img):
                $file_path = $full_path . $img;
                $url = content_url(str_replace(ABSPATH . 'wp-content/', '', $file_path));
            ?>
                <div class="image-card" style="background:#f9f9f9; padding:8px; border-radius:10px;">
                    <a href="<?php echo esc_url($url); ?>" data-lightbox="gallery" data-title="<?php echo esc_attr($img); ?>">
                        <img src="<?php echo esc_url($url); ?>" 
                             alt="<?php echo esc_attr($img); ?>" 
                             loading="lazy"
                             style="width:100%; height:220px; object-fit:cover; border-radius:8px;">
                    </a>
                    <p style="margin:8px 0 0 0; font-size:0.9em; text-align:center; word-break:break-all;">
                        <?php echo esc_html($img); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div style="margin:50px 0; text-align:center; font-size:1.2em;">
            <?php if ($current_page > 1): ?>
                <a href="<?php echo esc_url(add_query_arg(['sub' => $current_sub, 'paged' => $current_page-1])); ?>">← Previous</a>
            <?php endif; ?>

            <span style="margin:0 20px;">Page <strong><?php echo $current_page; ?></strong> of <?php echo $total_pages; ?></span>

            <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo esc_url(add_query_arg(['sub' => $current_sub, 'paged' => $current_page+1])); ?>">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Lightbox -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

<script>
lightbox.option({ resizeDuration: 200, wrapAround: true });
</script>

<style>
.image-card:hover { transform: scale(1.03); transition: 0.3s; }
</style>

<?php get_footer(); ?>
