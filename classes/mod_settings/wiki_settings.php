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

namespace local_coursegen\mod_settings;

use mod_wiki_external;

/**
 * Class wiki_settings
 *
 * @package    local_coursegen
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wiki_settings extends base_settings {
    /**
     * Add settings to wiki module.
     */
    public function add_settings() {
        $wiki = wiki_get_wiki($this->cm->instance);

        // Remove duplicate pages by title, excluding the first page title on wiki.
        $pages = $this->unique_pages_by_title($this->modsettings['pages'], $wiki->firstpagetitle);

        // Build first page data for the wiki.
        $firstpage = $this->get_first_page($pages, $wiki);

        // Add first page to wiki.
        $this->add_page($firstpage);

        // Add pages to wiki.
        foreach ($pages as $page) {
            $this->add_page($page);
        }
    }

    /**
     * Build first page data for the wiki.
     *
     * Creates the first page content with links to each additional page using Moodle
     * Wiki syntax `[[Title]]`. Clicking a link to a non-existent page lets users create it.
     *
     * @param array $pages The pages to add to the wiki to generate the links.
     * @param object $wiki The wiki object.
     *
     * @return array {title, newcontent_editor{text (HTML), format=FORMAT_HTML}} ready for self::add_page().
     */
    protected function get_first_page($pages, $wiki) {
        $firstpagetitle = $wiki->firstpagetitle;
        $firstpagecontent = '';
        foreach ($pages as $page) {
            $title = $page['title'];
            $firstpagecontent .= "<p>[[{$title}]]</p>\n";
        }
        return [
            'title' => $firstpagetitle,
            'newcontent_editor' => [
                'text' => $firstpagecontent,
                'format' => FORMAT_HTML,
            ],
        ];
    }

    /**
     * Add page to wiki.
     *
     * @param array $page Page data.
     */
    protected function add_page($page) {
        $content = $page['newcontent_editor']['text'];
        mod_wiki_external::new_page($page['title'], $content, FORMAT_HTML, null, $this->cm->instance);
    }

    /**
     * Remove duplicate pages by title, excluding the first page title on wiki.
     *
     * @param array $pages
     * @param string $firstpagetitle The title of the first page
     * @return array The pages without duplicates by title
     */
    protected function unique_pages_by_title(array $pages, string $firstpagetitle) {
        $result = [];
        foreach ($pages as $page) {
            $title = $page['title'];
            if ($title == $firstpagetitle) {
                continue;
            }
            $result[$title] = $page;
        }
        return array_values($result);
    }
}
