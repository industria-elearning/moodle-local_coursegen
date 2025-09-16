<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_datacurso\helper;

/**
 * Class image_helper
 *
 * @package    local_datacurso
 * @copyright  2025 Buendata <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class image_helper {

    /**
     * Get images from html.
     * The imagetags is an array of image tags. E.g. ['<img src="https://example.com/image.jpg">', ...].
     * The imageurls is an array of image urls. E.g. ['https://example.com/image.jpg', ...].
     * The order of the images in the arrays is the same.
     *
     * @param string $text The html text.
     * @return array Associative array with imagetags and imageurls.
     */
    public static function get_images_from_html($text) {
        preg_match_all('/<img[^>]+src="([^"]+)"/', $text, $matches);

        $imagetags = $matches[0];
        $imageurls = $matches[1];

        return [
            'imagetags' => $imagetags,
            'imageurls' => $imageurls,
        ];
    }


    /**
     * Store images in moodledata and replace them in html text.
     *
     * @param string $text The html text.
     * @param int $draftid The draft item id.
     * @param int $userid The user id to store the images in their draft area.
     * @return string The html text with images replaced.
     */
    public static function store_and_replace_images($text, $draftid, $userid) {
        $images = self::get_images_from_html($text);
        $imageurls = $images['imageurls'];
        $imagetags = $images['imagetags'];

        foreach ($imageurls as $index => $imageurl) {
            // Store image in moodledata.
            $fs = get_file_storage();
            $context = \context_user::instance($userid);

            // Get image name from URL.
            $urlparts = parse_url($imageurl);
            $urlpath = $urlparts['path'] ?? '';
            $filename = basename($urlpath);

            // If not filename, delete this image tag from text.
            if (empty($filename)) {
                $imagetag = $imagetags[$index];
                $text = str_replace($imagetag, '', $text);
                continue;
            }

            $fileinfo = [
                'contextid' => $context->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $draftid,
                'filepath' => '/',
                'filename' => $filename,
            ];
            $file = $fs->create_file_from_url($fileinfo, $imageurl, null, true);

            $storedfileurl = \moodle_url::make_draftfile_url(
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );

            // Replace the external image url with the stored image url.
            $text = str_replace($imageurl, $storedfileurl->out(), $text);
        }
        return $text;
    }
}
