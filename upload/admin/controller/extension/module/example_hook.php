<?php
use \GbitStudio\GDT\Engine\Hook;
/**
 * Приклад модуля з hook_boot методом
 * 
 * Цей модуль демонструє як використовувати hook_boot для ініціалізації хуків
 * при завантаженні системи без необхідності створювати окремі файли в controller/hook/
 */
class ControllerExtensionModuleExampleHook extends Controller {
    
    /**
     * Метод hook_boot викликається автоматично при завантаженні системи
     * якщо модуль встановлений та активований
     * 
     * Використовуйте цей метод для:
     * - Реєстрації action хуків
     * - Реєстрації filter хуків
     * - Реєстрації подій OpenCart
     * - Додавання глобальних методів
     */
    public function hook_boot() {

    }
}
