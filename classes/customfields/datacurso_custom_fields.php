<?php
namespace local_datacurso\customfields;

class datacurso_custom_fields {

// FUNCIONES PARA RECARGAR Y MOSTRAR LOS DATOS ACTUALIZADOS DEL CUSTOM FIELDS (customfield/externallib.php)

     /**
     * Parameters for reload template function
     *
     * @return external_function_parameters
     */
    public static function reload_template_parameters() {
        return new external_function_parameters(
            array(
                'component' => new external_value(PARAM_COMPONENT, 'component', VALUE_REQUIRED),
                'area' => new external_value(PARAM_ALPHANUMEXT, 'area', VALUE_REQUIRED),
                'itemid' => new external_value(PARAM_INT, 'itemid', VALUE_REQUIRED)
            )
        );
    }
    
     /**
     * Reload template function
     *
     * @param string $component
     * @param string $area
     * @param int $itemid
     * @return array|object|stdClass
     */
    public static function reload_template($component, $area, $itemid) {
        global $PAGE;

        $params = self::validate_parameters(self::reload_template_parameters(),
                      ['component' => $component, 'area' => $area, 'itemid' => $itemid]);

        $PAGE->set_context(context_system::instance());
        $handler = \core_customfield\handler::get_handler($params['component'], $params['area'], $params['itemid']);
        self::validate_context($handler->get_configuration_context());
        if (!$handler->can_configure()) {
            throw new moodle_exception('nopermissionconfigure', 'core_customfield');
        }
        $output = $PAGE->get_renderer('core_customfield');
        $outputpage = new \core_customfield\output\management($handler);
        return $outputpage->export_for_template($output);
    }

// FUNCIONES PARA CREAR LA NUEVA CATEGORIA (customfield/externallib.php)

    /**
     * Parameters for create category
     *
     * @return external_function_parameters
     */
    public static function create_category_parameters() {
        return new external_function_parameters(
            array(
                'component' => new external_value(PARAM_COMPONENT, 'component', VALUE_REQUIRED),
                'area' => new external_value(PARAM_ALPHANUMEXT, 'area', VALUE_REQUIRED),
                'itemid' => new external_value(PARAM_INT, 'itemid', VALUE_REQUIRED)
            )
        );
    }

     /**
     * Create category function
     *
     * @param string $component
     * @param string $area
     * @param int    $itemid
     * @return mixed
     */
    public static function create_category($component, $area, $itemid) {
        $params = self::validate_parameters(self::create_category_parameters(),
            ['component' => $component, 'area' => $area, 'itemid' => $itemid]);

        $handler = \core_customfield\handler::get_handler($params['component'], $params['area'], $params['itemid']);
        self::validate_context($handler->get_configuration_context());
        if (!$handler->can_configure()) {
            throw new moodle_exception('nopermissionconfigure', 'core_customfield');
        }
        return $handler->create_category();
    }

// FUNCIONES PARA EDITAR EL NOMBRE DEL ACORDEON (lib/external/externallib.php)

    /**
     * Parameters for function update_inplace_editable()
     *
     * @since Moodle 3.1
     * @return external_function_parameters
     */
    public static function update_inplace_editable_parameters() {
        return new external_function_parameters(
            array(
                'component' => new external_value(PARAM_COMPONENT, 'component responsible for the update', VALUE_REQUIRED),
                'itemtype' => new external_value(PARAM_NOTAGS, 'type of the updated item inside the component', VALUE_REQUIRED),
                'itemid' => new external_value(PARAM_RAW, 'identifier of the updated item', VALUE_REQUIRED),
                'value' => new external_value(PARAM_RAW, 'new value', VALUE_REQUIRED),
            ));
    }

    /**
     * Update any component's editable value assuming that component implements necessary callback
     *
     * @since Moodle 3.1
     * @param string $component
     * @param string $itemtype
     * @param string $itemid
     * @param string $value
     */
    public static function update_inplace_editable($component, $itemtype, $itemid, $value) {
        global $PAGE;
        // Validate and normalize parameters.
        $params = self::validate_parameters(self::update_inplace_editable_parameters(),
                      array('component' => $component, 'itemtype' => $itemtype, 'itemid' => $itemid, 'value' => $value));
        if (!$functionname = component_callback_exists($component, 'inplace_editable')) {
            throw new \moodle_exception('inplaceeditableerror');
        }
        $tmpl = component_callback($params['component'], 'inplace_editable',
            array($params['itemtype'], $params['itemid'], $params['value']));
        if (!$tmpl || !($tmpl instanceof \core\output\inplace_editable)) {
            throw new \moodle_exception('inplaceeditableerror');
        }
        return $tmpl->export_for_template($PAGE->get_renderer('core'));
    }

// FUNCION PARA ENVIAR LA CATEGORIA Y TIPO (lib/external/externallib.php)

    /**
     * Get field
     *
     * @return field_controller
     * @throws \moodle_exception
     */
    protected function get_field(): field_controller {
        if ($this->field === null) {
            if (!empty($this->_ajaxformdata['id'])) {
                $this->field = \core_customfield\field_controller::create((int)$this->_ajaxformdata['id']);
            } else if (!empty($this->_ajaxformdata['categoryid']) && !empty($this->_ajaxformdata['type'])) {
                $category = \core_customfield\category_controller::create((int)$this->_ajaxformdata['categoryid']);
                $type = clean_param($this->_ajaxformdata['type'], PARAM_PLUGIN);
                $this->field = \core_customfield\field_controller::create(0, (object)['type' => $type], $category);
            } else {
                throw new \moodle_exception('fieldnotfound', 'core_customfield');
            }
        }
        return $this->field;
    }

// FUNCION PARA ENVIAR LOS DATOS DEL CONTENIDO (lib/external/externallib.php)

    /**
     * Process the form submission
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * @return mixed
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $field = $this->get_field();
        $handler = $field->get_handler();
        $handler->save_field_configuration($field, $data);
        return null;
    }

}
