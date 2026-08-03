<?php
/**
 * Elementor Widget for Argsellers (Gestor de Vendedores)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (class_exists('\Elementor\Widget_Base')) {
    class ElementorWidgetArgsellers extends \Elementor\Widget_Base
    {
        public function get_name()
        {
            return 'argsellers';
        }

        public function get_title()
        {
            return 'Vendedores (ARGSEGURIDAD)';
        }

        public function get_icon()
        {
            return 'eicon-person';
        }

        public function get_categories()
        {
            return array('prestashop', 'general');
        }

        protected function _register_controls()
        {
            $this->start_controls_section(
                'section_content',
                array(
                    'label' => 'Ajustes de Vendedores',
                )
            );

            $this->add_control(
                'info_text',
                array(
                    'label' => 'Información',
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => 'Muestra la grilla dinámica de Asesores Comerciales configurados en Diseño -> Vendedores.',
                )
            );

            $this->end_controls_section();
        }

        protected function render()
        {
            if (class_exists('Module')) {
                $argsellers = Module::getInstanceByName('argsellers');
                if ($argsellers && method_exists($argsellers, 'renderSellersGrid')) {
                    echo $argsellers->renderSellersGrid();
                }
            }
        }

        protected function _content_template()
        {
        }
    }
}
