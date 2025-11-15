<?php
namespace GbitStudio\GDT\Engine;

use Registry;

final class Hook
{

    public static $registry;
    private static $actions = [];
    private static $filters = [];

    private static $events = []; // Для хранения зарегистрированных событий

    /**
     * Додає функцію до action-хука
     */

    
    public static function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
        // 
        self::add_hook('action', $hook_name, $callback, $priority, $accepted_args);
    }

    public static function getRegistry()
    {
        return self::$registry;
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
     * Регистрирует обработчик на встроенное событие OpenCart
     * Позволяет вешать свои callback на любые события OpenCart через систему хуков
     * Callback получает аргументы по ссылке, что позволяет модифицировать их
     * 
     * @param string $event_name Название встроенного события OpenCart (например, 'admin/view/common/column_left/before')
     * @param callable $callback Функция обработчик, получает аргументы по ссылке
     * @param int $priority Приоритет выполнения (чем меньше число, тем раньше выполнится)
     * 
     * Пример использования:
     * Hook::register_event('admin/view/common/column_left/before', function(&$route, &$data) {
     *     // Модифицируем данные напрямую
     *     $data['custom_field'] = 'value';
     * });
     */
    public static function register_event($event_name, $callback, $priority = 10)
    {
        // Проверяем что реестр инициализирован
        if (self::$registry === null) {
            return;
        }
        
        $registry = self::$registry;
        
        // Проверяем что event система доступна
        if (!$registry->has('event')) {
            return;
        }

        $registry->get('event')->register(
            $event_name,
            new \Action($callback),
            $priority
        );
        return true;
    }

    /**
     * Триггерит встроенное событие OpenCart через систему хуков
     * Используется в OCMOD для интеграции встроенных событий с системой хуков
     * Передает аргументы по ссылке, что позволяет callback-ам модифицировать их
     * 
     * @param string $event_name Название события OpenCart
     * @param mixed &...$args Аргументы по ссылке для передачи в callback
     * @return void
     */
    public static function trigger_oc_event($event_name, &...$args)
    {
        // Проверяем что реестр инициализирован
        if (self::$registry === null) {
            return;
        }
        
        $registry = self::$registry;
        
        // Проверяем что event система доступна
        if (!$registry->has('event')) {
            return;
        }
        
        // Формируем имя action-хука
        $hook_name = 'oc_event_' . str_replace('/', '_', $event_name);
        
        // Запускаем action с аргументами по ссылке
        // self::do_action_ref($hook_name, $args);

        $registry->get('event')->trigger(
            $event_name,
            $args
        );
    }

    /**
     * Выполняет action хук с передачей аргументов по ссылке
     * Внутренний метод для поддержки trigger_oc_event
     * 
     * @param string $hook_name Название хука
     * @param array &$args Массив аргументов по ссылке
     * @return void
     */
    private static function do_action_ref($hook_name, &$args)
    {
        if (!isset(self::$actions[$hook_name])) {
            return;
        }

        ksort(self::$actions[$hook_name]);

        foreach (self::$actions[$hook_name] as $priority => $callbacks) {
            foreach ($callbacks as $hook) {
                $function = $hook['function'];
                $accepted = $hook['accepted_args'];

                // Берем только нужное количество аргументов
                $callback_args = array_slice($args, 0, $accepted);

                // Вызываем callback с аргументами по ссылке
                // Используем call_user_func_array с массивом ссылок
                call_user_func_array($function, $callback_args);
            }
        }
    }

    /**
     * Старый метод для совместимости - регистрирует обработчик события OpenCart напрямую
     * 
     * @deprecated Используйте register_event() для более удобной работы
     * @param string $trigger Триггер события (например, 'catalog/model/checkout/order/addOrder/after')
     * @param callable $callback Функция обработчик
     * @param int $priority Приоритет выполнения
     */
    public static function add_event($trigger, $callback, $priority = 0)
    {
        try {
            $registry = self::$registry;

            if (!$registry->has('event')) {
                $registry->get('log')->write("Event system not available in registry");
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
            self::$registry->get('log')->write("Error registering event {$trigger}: " . $e->getMessage());
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
            $registry = self::$registry;

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
            self::$registry->get('log')->write("Error removing event {$trigger}: " . $e->getMessage());
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
            $registry = self::$registry;

            if (!$registry->has('event')) {
                return null;
            }

            $event_system = $registry->get('event');
            return $event_system->trigger($event, $args);

        } catch (\Exception $e) {
            self::$registry->get('log')->write("Error triggering event {$event}: " . $e->getMessage());
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

    public static function load(Registry $registry)
    {
        self::$registry = $registry;

        $registry->set('hook', Hook::class);
        // Подключаем класс кеша
        require_once(DIR_SYSTEM . 'library/gbitstudio/gdt/engine/hookcache.php');

        // Определяем текущую среду (admin или catalog)
        $is_admin = defined('DIR_CATALOG');

        if ($is_admin) {
            $base_dir = constant('DIR_APPLICATION');
        } else {
            $base_dir = constant('DIR_APPLICATION');
        }

        // Пытаемся загрузить из кеша
        $cached_hooks = HookCache::get();

        if ($cached_hooks !== null) {
            // Загружаем хуки из кеша
            self::loadHooksFromCache($cached_hooks);
            self::do_action('gdt_hook_loaded');
            return;
        }

        // Кеш недействителен, сканируем заново
        $hooks_to_cache = [];

        // 2. Загружаем hook_boot из всех установленных расширений
        $extension_hooks = self::scanExtensionsForHooks($is_admin, $base_dir);

        foreach ($extension_hooks as $hook_data) {
            $hooks_to_cache[] = $hook_data;
        }
        // Сохраняем в кеш
        HookCache::set($hooks_to_cache);

    }

    /**
     * Загружает хуки из кеша
     */
    private static function loadHooksFromCache($cached_hooks)
    {
        foreach ($cached_hooks as $hook_data) {
            if ($hook_data['type'] === 'extension') {
                // Новая система hook_boot из расширений
                if (is_file($hook_data['path'])) {
                    self::loadExtensionHookBoot($hook_data, false);
                }
            }
        }
    }

    /**
     * Сканирует все установленные расширения на наличие метода hook_boot
     * 
     * @param bool $is_admin Флаг админской части
     * @param string $base_dir Базовая директория
     * @return array Массив найденных хуков
     */
    private static function scanExtensionsForHooks($is_admin, $base_dir)
    {
        $hooks = [];

        try {
            // Получаем реестр для доступа к БД
            $registry = self::$registry;

            if (!$registry->has('db')) {
                return $hooks;
            }

            $db = $registry->get('db');

            // Получаем все установленные расширения из БД
            $query = $db->query("SELECT `type`, `code` FROM `" . DB_PREFIX . "extension` ORDER BY `type`, `code`");

            foreach ($query->rows as $extension) {
                $type = $extension['type'];
                $code = $extension['code'];

                // Путь к контроллеру: controller/extension/{type}/{code}.php
                $controller_path = $base_dir . 'controller/extension/' . $type . '/' . $code . '.php';

                if (!is_file($controller_path)) {
                    continue;
                }


                // Пытаемся загрузить контроллер и проверить наличие метода
                $hook_data = [
                    'type' => 'extension',
                    'extension_type' => $type,
                    'extension_code' => $code,
                    'path' => $controller_path,
                    'route' => 'extension/' . $type . '/' . $code
                ];
                

                // Проверяем наличие метода hook_boot и сразу загружаем
                if (self::loadExtensionHookBoot($hook_data, false)) {
                    $hooks[] = $hook_data;
                }
            }

        } catch (\Exception $e) {
            self::$registry->get('log')->write("Error scanning extensions for hooks: " . $e->getMessage());
            echo "Error scanning extensions for hooks: " . $e->getMessage();
        }

        return $hooks;
    }

    /**
     * Загружает и вызывает метод hook_boot из контроллера расширения
     * 
     * @param array $hook_data Данные о хуке
     * @param bool $scan_mode Режим сканирования (только проверка наличия метода)
     * @return bool true если метод hook_boot существует и был вызван
     */
    private static function loadExtensionHookBoot($hook_data, $scan_mode = false)
    {
        try {
            // Получаем реестр
            $registry = self::$registry;

            // Формируем имя класса контроллера
            $parts = explode('/', $hook_data['route']);
            $class_name = 'Controller';
            foreach ($parts as $part) {
                $class_name .= str_replace('_', '', ucwords($part, '_'));
            }

            // Проверяем существование файла контроллера
            if (!is_file($hook_data['path'])) {
                return false;
            }

        // Проверяем наличие метода hook_boot через регулярное выражение
        $file_content = file_get_contents($hook_data['path']);
        $has_hook_boot = preg_match('/function\s+hook_boot\s*\(/', $file_content);

        if (!$has_hook_boot) {
            return false;
        }



            // Подключаем файл контроллера
            if (!class_exists($class_name)) {
                require_once($hook_data['path']);
            }
            

            // Проверяем существование класса
            if (!class_exists($class_name)) {
                return false;
            }

            // Создаем экземпляр контроллера
            $controller = new $class_name($registry);

            // Проверяем наличие метода hook_boot через рефлексию
            if (!method_exists($controller, 'hook_boot')) {
                return false;
            }

            // Если режим сканирования - только проверяем наличие метода
            if ($scan_mode) {
                return true;
            }

            

            // Проверяем что метод публичный
            $reflection = new \ReflectionMethod($controller, 'hook_boot');

            if (!$reflection->isPublic()) {
                self::$registry->get('log')->write(
                    "hook_boot method must be public in {$hook_data['route']}"
                );
                return false;
            }

            // Вызываем метод hook_boot
            $controller->hook_boot();

            self::$registry->get('log')->write(
                "Loaded hook_boot from extension: {$hook_data['extension_type']}/{$hook_data['extension_code']}"
            );

            return true;

        } catch (\Exception $e) {
            if (!$scan_mode) {
                self::$registry->get('log')->write(
                    "Error loading hook_boot from {$hook_data['route']}: " . $e->getMessage()
                );
            }
            return false;
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
