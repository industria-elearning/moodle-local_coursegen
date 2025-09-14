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

namespace local_datacurso\mod_settings;

use mod_wiki_external;

/**
 * Class wiki_settings
 *
 * @package    local_datacurso
 * @copyright  2025 Buendata <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wiki_settings extends base_settings {
    /**
     * Add settings to wiki module.
     */
    public function add_settings() {

        $firstpage = $this->get_first_page();
        $this->add_page($firstpage);
        foreach ($this->modsettings['pages'] as $page) {
            $this->add_page($page);
        }
    }

    /**
     * Build first page data for the wiki.
     *
     * Creates the first page content with links to each additional page using Moodle
     * Wiki syntax `[[Title]]`. Clicking a link to a non-existent page lets users create it.
     *
     * @return array {title, newcontent_editor{text (HTML), format=FORMAT_HTML}} ready for self::add_page().
     */
    protected function get_first_page() {
        $wiki = wiki_get_wiki($this->cm->instance);
        $firstpagetitle = $wiki->firstpagetitle;
        $firstpagecontent = '';
        foreach ($this->modsettings['pages'] as $page) {
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
}
