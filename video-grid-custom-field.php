<?php
/**
 * Template Name: Video Grid - Custom Field Folder (Advanced)
 * Description: Final fixed URL generation
 */

get_header(); ?>

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
    $video_folder = str_replace(['../', '..\\'], '', $video_folder);

    if (!is_dir($video_folder)) {
        echo '<p style="color:red;">Folder not found.</p>';
        get_footer();
        exit;
    }

    $display_name = basename(rtrim($custom_path, '/'));
    echo '<p><strong>Folder:</strong> ' . esc_html($display_name) . '</p>';

    $current_sub = isset($_GET['sub']) ? sanitize_text_field($_GET['sub']) : '';
    $full_path = $video_folder . ltrim($current_sub, '/');
    $full_path = rtrim($full_path, '/') . '/';

    if (!is_dir($full_path)) {
        $full_path = $video_folder;
        $current_sub = '';
    }

    $subfolders = [];
    $videos = [];

    foreach (scandir($full_path) as $item) {
        if ($item === '.' || $item === '..') continue;
        $item_path = $full_path . $item;

        if (is_dir($item_path)) {
            $subfolders[] = $item;
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, ['mov','mpg','mpeg','mp4','webm'])) {
                $videos[] = $item;
            }
        }
    }

    usort($videos, fn($a,$b) => filemtime($full_path.$b) - filemtime($full_path.$a));
    sort($subfolders);

    echo '<p><strong>Location:</strong> ' . esc_html($current_sub ?: 'Root') . '</p>';
    ?>

    <?php if (!empty($subfolders) || $current_sub): ?>
    <div style="margin:20px 0 30px 0;">
        <?php if ($current_sub): ?>
            <a href="?sub=<?php echo urlencode(dirname($current_sub)); ?>">← Back</a>
        <?php endif; ?>

        <strong>Subfolders:</strong><br><br>
        <?php foreach ($subfolders as $sub): ?>
            <a href="?sub=<?php echo urlencode(trim($current_sub.'/'.$sub,'/')); ?>" 
               style="display:inline-block; margin:6px; padding:10px 16px; background:#f0f0f0; border-radius:8px;">
                📁 <?php echo esc_html($sub); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($videos)): ?>
        <p>No videos found.</p>
    <?php else: ?>
        <p><strong>Found <?php echo count($videos); ?> videos</strong></p>

        <div class="video-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 22px;">
            <?php foreach ($videos as $video):
                $file_path = $full_path . $video;

                // === STRONG URL FIX (this is the part that was broken) ===
                $relative = str_replace(ABSPATH, '', $file_path);           // normal case
                $relative = str_replace('/wp-content/wp-content/', '/wp-content/', $relative); // remove double
                $relative = ltrim($relative, '/');
                $url = content_url($relative);

                // Extra cleanup for your specific case
                $url = str_replace('/wp-content/wp-content/', '/wp-content/', $url);

                $ext = strtolower(pathinfo($video, PATHINFO_EXTENSION));
                $mime = ($ext === 'mov') ? 'video/quicktime' :
                        ($ext === 'webm') ? 'video/webm' :
                        (in_array($ext, ['mpg','mpeg'])) ? 'video/mpeg' : 'video/mp4';

                $thumb = video_grid_get_thumbnail($file_path, $video);
            ?>
                <div class="video-card" onclick="playVideo('<?php echo esc_js($url); ?>', '<?php echo esc_js($mime); ?>', '<?php echo esc_js($video); ?>')">
                    <div style="position:relative; background:#000; border-radius:8px; overflow:hidden;">
                        <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($video); ?>" loading="lazy" style="width:100%; height:190px; object-fit:cover;">
                        <div style="position:absolute; bottom:10px; right:10px; background:rgba(0,0,0,0.75); color:white; padding:4px 10px; border-radius:4px;">▶ Play</div>
                    </div>
                    <p style="margin:12px 0 0 0; font-size:0.95em; word-break:break-all;"><?php echo esc_html($video); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Video Modal -->
<div id="videoModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.97); z-index:99999; align-items:center; justify-content:center;">
    <div style="max-width:1150px; width:92%; background:#111; border-radius:12px; overflow:hidden;">
        <div style="padding:12px 20px; background:#1f1f1f; display:flex; justify-content:space-between; align-items:center;">
            <h3 id="modalTitle" style="margin:0; color:#fff;"></h3>
            <button onclick="closeModal()" style="background:none; border:none; color:#fff; font-size:32px; cursor:pointer;">×</button>
        </div>
        <div style="padding:15px; background:#000;">
            <video id="modalVideo" controls autoplay style="width:100%; max-height:72vh;" preload="metadata"></video>
        </div>
        <div style="padding:15px; text-align:center; background:#1f1f1f;">
            <a id="downloadBtn" href="#" download style="color:#4da6ff;">↓ Download Original</a>
        </div>
    </div>
</div>

<script>
function playVideo(url, mime, title) {
    document.getElementById('modalTitle').textContent = title;
    const video = document.getElementById('modalVideo');
    video.innerHTML = '<source src="' + url + '" type="' + mime + '">';
    document.getElementById('downloadBtn').href = url;
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
.video-card { background:#f9f9f9; padding:12px; border-radius:12px; cursor:pointer; transition:0.25s; }
.video-card:hover { transform:translateY(-6px); box-shadow:0 10px 20px rgba(0,0,0,0.15); }
</style>

<?php
function video_grid_get_thumbnail($filepath, $filename) {
    $cache_dir = WP_CONTENT_DIR . '/video-thumbs/';
    if (!is_dir($cache_dir)) mkdir($cache_dir, 0755, true);

    $thumb_name = md5($filename) . '.jpg';
    $thumb_path = $cache_dir . $thumb_name;
    $thumb_url  = content_url('/video-thumbs/' . $thumb_name);

    if (file_exists($thumb_path)) return $thumb_url;

    $cmd = "ffmpeg -i " . escapeshellarg($filepath) . " -ss 00:00:02 -vframes 1 -q:v 5 " . escapeshellarg($thumb_path) . " 2>&1";
    exec($cmd);
    return file_exists($thumb_path) ? $thumb_url : 'https://via.placeholder.com/280x190/333/fff?text=No+Preview';
}
?>

<?php get_footer(); ?>
