# WordPress Folder Media Gallery

**Lightweight WordPress templates to browse thousands of images and videos directly from any server folder or symlink.**

No Media Library import • No bloat • Proper pagination • Lightbox • ffmpeg thumbnails.

---

## ✨ Features

- Works with **Images**, **Videos**, and **Mixed** content
- Reads files directly from disk (no import needed)
- Handles **10,000+ files** smoothly with pagination
- Beautiful image lightbox
- Real video thumbnails using **ffmpeg**
- Subfolder navigation
- Symlink friendly
- Zero plugin required

---

## Requirements

- WordPress 5.5+
- `ffmpeg` (required for video thumbnails)
- `imagemagick` (recommended)
- PHP `exec()` enabled
- `www-data` user must have read access to your folders

---

## Installation

### 1. Install Required Tools (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install ffmpeg imagemagick -y
2. Upload Template FilesCopy these files into your active theme folder:media-grid-mixed.php
image-grid-custom-field.php
video-grid-catalog.php

3. Set Permissionsbash

# For real folders
sudo chown -R www-data:www-data /path/to/your/media/folder
sudo chmod -R 755 /path/to/your/media/folder

# Recommended: Use symlink inside wp-content
cd /var/www/html/wp-content
sudo ln -s /path/to/your/real/folder my-gallery
sudo chown -R www-data:www-data my-gallery

How to UseGo to Pages → Add New
Enter a title (e.g. "Vacation 2025")
In the right sidebar, select the desired Template:Media Grid - Mixed Images & Videos ← Recommended
Image Grid - Custom Field Folder
Video Grid - Custom Field Folder

Scroll down to Custom Fields (enable under Screen Options → Panels if not visible)
Add the correct Custom Field:Template
Custom Field Name
Example Value
Mixed Images & Videos
media_folder
/var/www/html/wp-content/my-gallery/vacation
Images Only
image_folder
/home/user/photos/2025
Videos Only
video_folder
/var/www/html/wp-content/videos

Publish the page.

Your gallery is now live!TroubleshootingNo video thumbnails?
Delete cache: rm -rf /var/www/html/wp-content/video-thumbs/*
Images or videos not loading?
Check permissions and use a symlink inside /wp-content/
Pagination broken?
Hard refresh (Ctrl + Shift + R)

Recommended Plugins (Optional)Caching: WP Rocket or LiteSpeed Cache
Security: Wordfence or Solid Security
SEO: Rank Math
Lazy Loading: Flying Images

ContributingPull requests and issues are welcome!LicenseMIT License — Free to use in personal and commercial projects.Made for people who want fast, clean, and bloat-free media galleries.



