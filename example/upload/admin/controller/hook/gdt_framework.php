<?php
/*
name: gdt_framework
description: This hook adds a test module to the admin menu and demonstrates various hook types.
version: 1.0.1
controller: extension/module/gdt_framework
author: GbitStudio
priority: 10
hidden: false
*/

use GbitStudio\GDT\Engine\HookController;
use GbitStudio\GDT\Engine\Hook;

class ControllerHookGdtFrameWork extends HookController
{
   
    public function boot(){

        Hook::register_global('add_menu_item', function($title, $route, $icon = 'fa fa-cog', $children = [], $position = 'last') {
            // Реализация добавления элемента меню
        });
    }

}