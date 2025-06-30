<?php
/*
name: Module Test Hook
description: This hook adds a test module to the admin menu and demonstrates various hook types.
version: 1.0.3
controller: extension/module/test
author: GbitStudio
priority: 10
hidden: false
*/

use GbitStudio\GDT\Engine\HookController;
use GbitStudio\GDT\Engine\Hook;

class ControllerHookTestModule extends HookController
{
    public function boot()
    {
        $this->menu_admin();

        //echo 1;
        Hook::add_event('view/common/footer/after', 'hook/test_module/after_footer');

    }

    protected function menu_admin()
    {
        Hook::add_filter('common_column_left_menus', function ($menu_items) {
            // Добавляем пункт меню для тестового модуля
            $menu_items[] = [
                'name' => 'Test Module',
                'href' => route('extension/module/test_module'),
                'icon' => 'fa fa-cog'
            ];
            return $menu_items;
        });
    }

    public function after_footer(&$route, &$data, &$output)
    {
        // Здесь можно добавить код, который будет выполняться перед загрузкой футера
        $json_route = json_encode($data);
        $output = "
        <script> 
            alert('{$json_route}'); 
        </script>
        " . $output;
    }

    /**
     * после активации хука
     */
    public function activate()
    {
        // Код для выполнения после активации модуля
    }
    /**
     * после деактивации хука
     */
    public function deactivate()
    {

    }

    public function uninstall()
    {
        // Код для выполнения при удалении модуля
    }

    public function install()
    {
        // Код для выполнения при установке модуля
    }
}