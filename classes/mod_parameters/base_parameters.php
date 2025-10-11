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

namespace local_datacurso\mod_parameters;

/**
 * Class base_parameters
 *
 * @package    local_datacurso
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_parameters {
    /**
     * @var object $parameters Default parameters for the module.
     */
    protected $parameters;

    /**
     * Constructor.
     *
     * @param object $parameters Default parameters for the module.
     */
    public function __construct($parameters) {
        $this->parameters = $parameters;
    }

    /**
     * Returns the adjusted parameters for the module.
     *
     * @return object Adjusted parameters for the module.
     */
    abstract public function get_parameters();
}
