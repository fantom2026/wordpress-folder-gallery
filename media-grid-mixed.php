<?php
/**
 * Template Name: Media Grid - Mixed Images & Videos
 * Description: Proper video thumbnails using ffmpeg cache
 */

get_header(); 

$current_url = get_permalink();
?>

<div style="max-width: 1400px; margin: 40px auto; padding: 20px;">
    <h1><?php the_title(); ?></h1>

    <?php
    $custom_path = get_post_meta(get_the_ID(), 'media_folder', true);

    if (empty($custom_path)) {
        echo '<p style="color:red;">Please add Custom Field <strong>media_folder</strong>.</p>';
        get_footer();
        exit;
    }

    $media_folder = rtrim($custom_path, '/') . '/';

    if (!is_dir($media_folder)) {
        echo '<p style="color:red;">Folder not found.</p>';
        get_footer();
        exit;
    }

    $display_name = basename(rtrim($custom_path, '/'));
    echo '<p><strong>Media Folder:</strong> ' . esc_html($display_name) . '</p>';

    $current_sub = isset($_GET['sub']) ? sanitize_text_field($_GET['sub']) : '';
    $full_path = $media_folder . ltrim($current_sub, '/');
    $full_path = rtrim($full_path, '/') . '/';

    if (!is_dir($full_path)) {
        $full_path = $media_folder;
        $current_sub = '';
    }

    $per_page = 80;
    $current_page = max(1, intval($_GET['page'] ?? 1));

    $items = [];
    $subfolders = [];

    foreach (scandir($full_path) as $item) {
        if ($item === '.' || $item === '..') continue;
        $item_path = $full_path . $item;

        if (is_dir($item_path)) {
            $subfolders[] = $item;
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp','bmp'])) {
                $items[] = ['type' => 'image', 'name' => $item, 'path' => $item_path];
            } elseif (in_array($ext, ['mp4','mov','webm','mpg','mpeg'])) {
                $items[] = ['type' => 'video', 'name' => $item, 'path' => $item_path];
            }
        }
    }

    usort($items, fn($a,$b) => filemtime($b['path']) - filemtime($a['path']));
    sort($subfolders);

    $total_items = count($items);
    $total_pages = ceil($total_items / $per_page);
    $offset = ($current_page - 1) * $per_page;
    $paged_items = array_slice($items, $offset, $per_page);

    echo '<p><strong>Location:</strong> ' . esc_html($current_sub ?: 'Root') . ' — ' . $total_items . ' files</p>';
    ?>

    <!-- Subfolders -->
    <?php if (!empty($subfolders) || $current_sub): ?>
    <div style="margin:20px 0 30px 0;">
        <?php if ($current_sub): ?>
            <a href="<?php echo add_query_arg(['sub' => dirname($current_sub), 'page' => 1], $current_url); ?>">← Back</a>
        <?php endif; ?>

        <strong>Subfolders:</strong><br><br>
        <?php foreach ($subfolders as $sub): ?>
            <a href="<?php echo add_query_arg(['sub' => trim($current_sub.'/'.$sub,'/'), 'page' => 1], $current_url); ?>" 
               style="display:inline-block; margin:6px; padding:10px 16px; background:#f0f0f0; border-radius:8px;">
                📁 <?php echo esc_html($sub); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($paged_items)): ?>
        <p>No media found.</p>
    <?php else: ?>
        <div class="media-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php foreach ($paged_items as $item):
                $file_path = $item['path'];
                $name = $item['name'];
                $url = content_url(str_replace(ABSPATH . 'wp-content/', '', $file_path));
            ?>
                <div class="media-card" style="background:#f9f9f9; padding:10px; border-radius:12px;">
                    <?php if ($item['type'] === 'image'): ?>
                        <a href="<?php echo esc_url($url); ?>" data-lightbox="gallery" data-title="<?php echo esc_attr($name); ?>">
                            <img src="<?php echo esc_url($url); ?>" 
                                 alt="<?php echo esc_attr($name); ?>" 
                                 loading="lazy"
                                 style="width:100%; height:200px; object-fit:cover; border-radius:8px;">
                        </a>
                    <?php else: ?>
                        <?php $thumb = mixed_get_video_thumbnail($file_path, $name); ?>
                        <div onclick="playVideo('<?php echo esc_js($url); ?>', '<?php echo esc_js($name); ?>')" style="cursor:pointer; position:relative;">
                            <img src="<?php echo esc_url($thumb); ?>" 
                                 alt="<?php echo esc_attr($name); ?>" 
                                 loading="lazy"
                                 style="width:100%; height:200px; object-fit:cover; border-radius:8px;">
                            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:rgba(0,0,0,0.75); color:white; border-radius:50%; width:70px; height:70px; display:flex; align-items:center; justify-content:center; font-size:32px; border:3px solid white;">
                                ▶
                            </div>
                        </div>
                    <?php endif; ?>

                    <p style="margin:10px 0 0 0; font-size:0.9em; text-align:center; word-break:break-all;">
                        <?php echo esc_html($name); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div style="margin:50px 0; text-align:center;">
            <?php if ($current_page > 1): ?>
                <a href="<?php echo add_query_arg(['sub' => $current_sub, 'page' => $current_page-1], $current_url); ?>">← Previous</a>
            <?php endif; ?>

            <span style="margin:0 20px;">Page <strong><?php echo $current_page; ?></strong> of <?php echo $total_pages; ?></span>

            <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo add_query_arg(['sub' => $current_sub, 'page' => $current_page+1], $current_url); ?>">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Video Modal -->
<div id="videoModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.95); z-index:99999; align-items:center; justify-content:center;">
    <div style="max-width:1100px; width:90%; background:#111; border-radius:12px; overflow:hidden;">
        <div style="padding:12px 20px; background:#1f1f1f; display:flex; justify-content:space-between;">
            <h3 id="modalTitle" style="margin:0; color:white;"></h3>
            <button onclick="closeModal()" style="background:none; border:none; color:white; font-size:30px;">×</button>
        </div>
        <div style="padding:15px; background:#000;">
            <video id="modalVideo" controls autoplay style="width:100%; max-height:70vh;" preload="metadata"></video>
        </div>
    </div>
</div>

<script>
function playVideo(url, title) {
    document.getElementById('modalTitle').textContent = title;
    const video = document.getElementById('modalVideo');
    video.innerHTML = `<source src="${url}" type="video/mp4">`;
    document.getElementById('videoModal').style.display = 'flex';
    video.load();
    video.play();
}

function closeModal() {
    const video = document.getElementById('modalVideo');
    video.pause();
    video.innerHTML = '';
    document.getElementById('videoModal').style.display = 'none';
}
</script>

<!-- Lightbox -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

<style>
.media-card:hover { transform: scale(1.03); transition: 0.3s; }
</style>

<?php
// ====================== VIDEO THUMBNAIL FUNCTION ======================
function mixed_get_video_thumbnail($filepath, $filename) {
    $cache_dir = WP_CONTENT_DIR . '/video-thumbs/';
    if (!is_dir($cache_dir)) mkdir($cache_dir, 0755, true);

    $thumb_name = md5($filename) . '.jpg';
    $thumb_path = $cache_dir . $thumb_name;
    $thumb_url  = content_url('/video-thumbs/' . $thumb_name);

    if (file_exists($thumb_path)) return $thumb_url;

    $cmd = "ffmpeg -i " . escapeshellarg($filepath) . 
           " -ss 00:00:02 -vframes 1 -q:v 5 " . 
           escapeshellarg($thumb_path) . " 2>&1";

    exec($cmd);

    return file_exists($thumb_path) ? $thumb_url : 'https://via.placeholder.com/280x200/333/fff?text=Video';
}
?>

<?php get_footer(); ?>
