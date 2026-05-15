<?php
/**
 * Template Name: Video Grid - Custom Field Folder
 * Description: Fixed pagination for Post Name permalinks
 */

get_header(); 
?>

<div style="max-width: 1400px; margin: 40px auto; padding: 20px;">
    <h1><?php the_title(); ?></h1>

    <?php
    $custom_path = get_post_meta(get_the_ID(), 'video_folder', true);

    if (empty($custom_path)) {
        echo '<p style="color:red;">Please add Custom Field <strong>video_folder</strong>.</p>';
        get_footer();
        exit;
    }

    $video_folder = rtrim($custom_path, '/') . '/';

    if (!is_dir($video_folder)) {
        echo '<p style="color:red;">Folder not found.</p>';
        get_footer();
        exit;
    }

    $display_name = basename(rtrim($custom_path, '/'));
    echo '<p><strong>Video Folder:</strong> ' . esc_html($display_name) . '</p>';

    $current_sub = isset($_GET['sub']) ? sanitize_text_field($_GET['sub']) : '';
    $full_path = $video_folder . ltrim($current_sub, '/');
    $full_path = rtrim($full_path, '/') . '/';

    if (!is_dir($full_path)) {
        $full_path = $video_folder;
        $current_sub = '';
    }

    $per_page = 80;
    $current_page = max(1, intval($_GET['paged'] ?? 1));

    $videos = [];
    $subfolders = [];

    foreach (scandir($full_path) as $item) {
        if ($item === '.' || $item === '..') continue;
        $item_path = $full_path . $item;

        if (is_dir($item_path)) {
            $subfolders[] = $item;
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, ['mp4','mov','webm','mpg','mpeg'])) {
                $videos[] = $item;
            }
        }
    }

    usort($videos, fn($a,$b) => filemtime($full_path.$b) - filemtime($full_path.$a));
    sort($subfolders);

    $total_videos = count($videos);
    $total_pages = ceil($total_videos / $per_page);
    $offset = ($current_page - 1) * $per_page;
    $paged_videos = array_slice($videos, $offset, $per_page);

    echo '<p><strong>Location:</strong> ' . esc_html($current_sub ?: 'Root') . ' — ' . $total_videos . ' videos</p>';
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

    <?php if (empty($paged_videos)): ?>
        <p>No videos found.</p>
    <?php else: ?>
        <div class="video-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php foreach ($paged_videos as $video):
                $file_path = $full_path . $video;
                $url = content_url(str_replace(ABSPATH . 'wp-content/', '', $file_path));
                $thumb = video_get_thumbnail($file_path, $video);
            ?>
                <div class="video-card" style="background:#f9f9f9; padding:10px; border-radius:12px; cursor:pointer;" onclick="playVideo('<?php echo esc_js($url); ?>', '<?php echo esc_js($video); ?>')">
                    <div style="position:relative;">
                        <img src="<?php echo esc_url($thumb); ?>" 
                             alt="<?php echo esc_attr($video); ?>" 
                             loading="lazy"
                             style="width:100%; height:200px; object-fit:cover; border-radius:8px;">
                        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:rgba(0,0,0,0.75); color:white; border-radius:50%; width:70px; height:70px; display:flex; align-items:center; justify-content:center; font-size:32px; border:3px solid white;">
                            ▶
                        </div>
                    </div>
                    <p style="margin:10px 0 0 0; font-size:0.9em; text-align:center; word-break:break-all;">
                        <?php echo esc_html($video); ?>
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

<style>
.video-card:hover { transform: scale(1.03); transition: 0.3s; }
</style>

<?php
function video_get_thumbnail($filepath, $filename) {
    $cache_dir = WP_CONTENT_DIR . '/video-thumbs/';
    if (!is_dir($cache_dir)) mkdir($cache_dir, 0755, true);

    $thumb_name = md5($filename) . '.jpg';
    $thumb_path = $cache_dir . $thumb_name;
    $thumb_url  = content_url('/video-thumbs/' . $thumb_name);

    if (file_exists($thumb_path)) return $thumb_url;

    $cmd = "ffmpeg -i " . escapeshellarg($filepath) . " -ss 00:00:02 -vframes 1 -q:v 5 " . escapeshellarg($thumb_path) . " 2>&1";
    exec($cmd);

    return file_exists($thumb_path) ? $thumb_url : 'https://via.placeholder.com/280x200/333/fff?text=Video';
}
?>

<?php get_footer(); ?>
