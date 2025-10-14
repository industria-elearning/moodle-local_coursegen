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

namespace aiplacement_coursegen\mod_settings\feedback;

use feedback_item_multichoicerated;

/**
 * Class presentation_builder
 *
 * @package    aiplacement_coursegen
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class presentation_builder {
    /**
     * Build presentation.
     *
     * @param \stdClass $item Item data.
     * @return string Presentation.
     */
    public function build($item): string {
        $method = 'build_' . $item->typ;
        if (!method_exists($this, $method)) {
            return '';
        }
        return $this->$method($item);
    }

    /**
     * Build presentation for info.
     *
     * @param \stdClass $item Item data.
     * @return string Presentation.
     */
    private function build_info($item): string {
        return \feedback_item_info::MODE_COURSE;
    }

    /**
     * Build presentation for multichoice.
     *
     * @param \stdClass $item Item data.
     * @return string Presentation.
     */
    private function build_multichoice($item): string {
        if (!isset($item->values) || empty(trim($item->values))) {
            return '';
        }
        $presentation = str_replace("\n", FEEDBACK_MULTICHOICE_LINE_SEP, trim($item->values));
        if (!isset($item->subtype)) {
            $subtype = 'r';
        } else {
            $subtype = substr($item->subtype, 0, 1);
        }
        if (isset($item->horizontal) && $item->horizontal == 1 && $subtype != 'd') {
            $presentation .= FEEDBACK_MULTICHOICE_ADJUST_SEP . '1';
        }

        return $subtype . FEEDBACK_MULTICHOICE_TYPE_SEP . $presentation;
    }

    /**
     * Build presentation for multichoicerated.
     *
     * @param \stdClass $item Item data.
     * @return string Presentation.
     */
    private function build_multichoicerated($item): string {
        if (empty(trim($item->values))) {
            return '';
        }
        $itemobj = new feedback_item_multichoicerated();

        $presentation = $itemobj->prepare_presentation_values_save(
            trim($item->values),
            FEEDBACK_MULTICHOICERATED_VALUE_SEP2,
            FEEDBACK_MULTICHOICERATED_VALUE_SEP
        );
        if (!isset($item->subtype)) {
            $subtype = 'r';
        } else {
            $subtype = substr($item->subtype, 0, 1);
        }
        if (isset($item->horizontal) && $item->horizontal == 1 && $subtype != 'd') {
            $presentation .= FEEDBACK_MULTICHOICERATED_ADJUST_SEP . '1';
        }
        return $subtype . FEEDBACK_MULTICHOICERATED_TYPE_SEP . $presentation;
    }

    /**
     * Build presentation for numeric.
     *
     * @param \stdClass $item Item data.
     * @return string Presentation.
     */
    private function build_numeric($item): string {
        $num1 = unformat_float($item->rangefrom, true);
        if ($num1 === false || $num1 === null) {
            $num1 = '-';
        }

        $num2 = unformat_float($item->rangeto, true);
        if ($num2 === false || $num2 === null) {
            $num2 = '-';
        }

        if ($num1 === '-' || $num2 === '-') {
            return $num1 . '|' . $num2;
        }

        if ($num1 > $num2) {
            return $num2 . '|' . $num1;
        } else {
            return $num1 . '|' . $num2;
        }
    }

    /**
     * Build presentation for textarea.
     *
     * @param \stdClass $item Item data.
     * @return string Presentation.
     */
    private function build_textarea($item): string {
        return $item->itemwidth . '|' . $item->itemheight;
    }

    /**
     * Build presentation for textfield.
     *
     * @param \stdClass $item Item data.
     * @return string Presentation.
     */
    private function build_textfield($item): string {
        return $item->itemsize . '|' . $item->itemmaxlength;
    }
}
