<?php
namespace GbitStudio\GDT\Engine;
final class Hook
{
    private static $actions = [];
    private static $filters = [];

    private static $events = []; // Для хранения зарегистрированных событий

    /**
     * Додає функцію до action-хука
     */
    public static function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
        self::add_hook('action', $hook_name, $callback, $priority, $accepted_args);
    }

    /**
     * Викликає action-хук
     */
    public static function do_action($hook_name, ...$args)
    {
        self::run_hooks('action', $hook_name, null, ...$args);
    }

    /**
     * Додає функцію до filter-хука
     */
    public static function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
        self::add_hook('filter', $hook_name, $callback, $priority, $accepted_args);
    }

    /**
     * Викликає filter-хук
     */
    public static function apply_filters($hook_name, $value, ...$args)
    {
        return self::run_hooks('filter', $hook_name, $value, ...$args);
    }


    /**
     * Регистрирует обработчик события OpenCart
     *
     * @param string $trigger Триггер события (например, 'catalog/model/checkout/order/addOrder/after')
     * @param callable $callback Функция обработчик
     * @param int $priority Приоритет выполнения
     */
    public static function add_event($trigger, $callback, $priority = 0)
    {
        try {
            $registry = \GbitStudio\GDT\Engine\GDT::registry();

            if (!$registry->has('event')) {
                \GbitStudio\GDT\Engine\GDT::logWrite("Event system not available in registry");
                return false;
            }

            $event = $registry->get('event');

            // Создаем уникальный ID для события
            $event_id = 'hook_' . uniqid();

            // Создаем Action объект
            $action = new \Action($callback);

            // Регистрируем событие
            $event->register($trigger, $action, $priority);

            // Сохраняем для возможности удаления
            self::$events[] = [
                'trigger' => $trigger,
                'callback' => $callback,
                'priority' => $priority,
                'id' => $event_id
            ];

            return $event_id;

        } catch (\Exception $e) {
            \GbitStudio\GDT\Engine\GDT::logWrite("Error registering event {$trigger}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Удаляет обработчик события OpenCart
     *
     * @param string $trigger Триггер события
     * @param callable $callback Функция обработчик
     */
    public static function remove_event($trigger, $callback)
    {
        try {
            $registry = \GbitStudio\GDT\Engine\GDT::registry();

            if (!$registry->has('event')) {
                return false;
            }

            $event = $registry->get('event');

            // Формируем route для Action
            if (is_array($callback)) {
                $route = get_class($callback[0]) . '/' . $callback[1];
            } elseif (is_string($callback)) {
                $route = $callback;
            } else {
                $route = 'anonymous_' . spl_object_hash($callback);
            }

            $event->unregister($trigger, $route);

            // Удаляем из нашего списка
            foreach (self::$events as $key => $event_data) {
                if ($event_data['trigger'] === $trigger && $event_data['callback'] === $callback) {
                    unset(self::$events[$key]);
                    break;
                }
            }

            return true;

        } catch (\Exception $e) {
            \GbitStudio\GDT\Engine\GDT::logWrite("Error removing event {$trigger}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Запускает событие OpenCart
     *
     * @param string $event Название события
     * @param array $args Аргументы для передачи
     * @return mixed
     */
    public static function trigger_event($event, $args = [])
    {
        try {
            $registry = \GbitStudio\GDT\Engine\GDT::registry();

            if (!$registry->has('event')) {
                return null;
            }

            $event_system = $registry->get('event');
            return $event_system->trigger($event, $args);

        } catch (\Exception $e) {
            \GbitStudio\GDT\Engine\GDT::logWrite("Error triggering event {$event}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Получает список зарегистрированных событий
     *
     * @return array
     */
    public static function get_events()
    {
        return self::$events;
    }



    /**
     * Реєстрація callback-функції
     */
    private static function add_hook($type, $hook_name, $callback, $priority, $accepted_args)
    {
        // Исправляем получение ссылки на массив
        if ($type === 'action') {
            $target = &self::$actions;
        } else {
            $target = &self::$filters;
        }

        if (!isset($target[$hook_name])) {
            $target[$hook_name] = [];
        }

        if (!isset($target[$hook_name][$priority])) {
            $target[$hook_name][$priority] = [];
        }

        $target[$hook_name][$priority][] = [
            'function' => $callback,
            'accepted_args' => $accepted_args
        ];
    }

    /**
     * Внутрішня реалізація виконання хуків
     */
    private static function run_hooks($type, $hook_name, $value = null, ...$args)
    {
        // Исправляем получение ссылки на массив
        if ($type === 'action') {
            $target = &self::$actions;
        } else {
            $target = &self::$filters;
        }

        if (!isset($target[$hook_name])) {
            return $type === 'filter' ? $value : null;
        }

        ksort($target[$hook_name]);

        foreach ($target[$hook_name] as $priority => $callbacks) {
            foreach ($callbacks as $hook) {
                $function = $hook['function'];
                $accepted = $hook['accepted_args'];

                if ($type === 'filter') {
                    $value = call_user_func_array($function, array_slice([$value, ...$args], 0, $accepted));
                } else {
                    call_user_func_array($function, array_slice($args, 0, $accepted));
                }
            }
        }

        return $type === 'filter' ? $value : null;
    }

    /**
     * Чи зареєстровано хук
     */
    public static function has_action($hook_name)
    {
        return isset(self::$actions[$hook_name]);
    }

    public static function has_filter($hook_name)
    {
        return isset(self::$filters[$hook_name]);
    }

    /**
     * Видаляє всі хуки (для тестів або перезапуску)
     */
    public static function remove_all()
    {
        self::$actions = [];
        self::$filters = [];
    }

    public static function load()
    {
        // Определяем текущую среду (admin или catalog)
        $is_admin = defined('DIR_CATALOG');

        if ($is_admin) {
            $base_dir = constant('DIR_APPLICATION');
        } else {
            $base_dir = constant('DIR_CATALOG');
        }

        // Загружаем хуки из соответствующей директории
        $hook_dir = $base_dir . 'controller/hook/';

        if (is_dir($hook_dir)) {
            $hook_files = glob($hook_dir . '*.php');

            foreach ($hook_files as $hook_file) {
                $hook_name = basename($hook_file, '.php');

                // Проверяем, активен ли модуль
                if (self::isModuleActive($hook_name)) {
                    self::loadHookController($hook_file, $hook_name, $is_admin);
                }
            }
        }
    }

    /**
     * Загружает и инициализирует контроллер хука
     */
    private static function loadHookController($hook_file, $hook_name, $is_admin)
    {
        try {
            // Подключаем файл хука
            require_once($hook_file);

            // Формируем имя класса в стиле OpenCart
            $class_name = 'ControllerHook' . str_replace('_', '', ucwords($hook_name, '_'));

            if (!class_exists($class_name)) {
                \GbitStudio\GDT\Engine\GDT::logWrite("Hook class not found: {$class_name} in file {$hook_file}");
                return;
            }

            // Получаем реестр OpenCart
            $registry = \GbitStudio\GDT\Engine\GDT::registry();

            // Создаем экземпляр контроллера хука
            $hook_controller = new $class_name($registry);

            // Проверяем, что это действительно контроллер хука
            if (!($hook_controller instanceof \GbitStudio\GDT\Engine\HookController)) {
                \GbitStudio\GDT\Engine\GDT::logWrite("Invalid hook controller: {$class_name} must extend HookController");
                return;
            }

            // Вызываем метод boot для инициализации хука
            $hook_controller->boot();

        } catch (\Exception $e) {
            \GbitStudio\GDT\Engine\GDT::logWrite("Error loading hook {$hook_name}: " . $e->getMessage());
        }
    }

    /**
     * Проверяет, активен ли модуль
     */
    private static function isModuleActive($module_code)
    {
        try {
            // Получаем статус модуля из настроек
            $config = \GbitStudio\GDT\Engine\GDT::config();
            $status = $config->get('module_' . $module_code . '_status');

            // Если статус не установлен, проверяем наличие в таблице extensions
            if ($status === null) {
                $db = \GbitStudio\GDT\Engine\GDT::db();
                $query = $db->query("
                    SELECT code 
                    FROM `" . constant('DB_PREFIX') . "extension` 
                    WHERE `type` = 'module' AND `code` = '" . $db->escape($module_code) . "'
                ");

                return $query->num_rows > 0;
            }

            return (bool) $status;

        } catch (\Exception $e) {
            // В случае ошибки считаем модуль активным (для разработки)
            return true;
        }
    }




    /** @var array Глобальные методы */
    private static $global_methods = [];

    /**
     * Регистрирует глобальный метод
     *
     * @param string $name Имя метода
     * @param callable $callback Функция обратного вызова
     */
    public static function register_global($name, $callback)
    {
        self::$global_methods[$name] = $callback;
    }

    /**
     * Вызывает глобальный метод
     *
     * @param string $name Имя метода
     * @param array $args Аргументы
     * @return mixed
     */
    public static function call_global($name, ...$args)
    {
        if (!isset(self::$global_methods[$name])) {
            throw new \Exception("Global method '{$name}' not registered");
        }

        return call_user_func_array(self::$global_methods[$name], $args);
    }

    /**
     * Проверяет существование глобального метода
     *
     * @param string $name Имя метода
     * @return bool
     */
    public static function has_global($name)
    {
        return isset(self::$global_methods[$name]);
    }

    /**
     * Получает список всех глобальных методов
     *
     * @return array
     */
    public static function get_globals()
    {
        return array_keys(self::$global_methods);
    }





}
