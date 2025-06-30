<?php
class ControllerStartupGDTFramework extends Controller {
    public function index() {
        // Инициализируем GDT Framework
        \GbitStudio\GDT\Engine\GDT::init($this->registry);
        
        // Загружаем хуки
        \GbitStudio\GDT\Engine\Hook::load();
    }
}