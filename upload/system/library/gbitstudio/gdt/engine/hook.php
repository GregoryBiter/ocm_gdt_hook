<?php
namespace GbitStudio\GDT\Engine;

use Registry;

final class Hook
{
    use Event;

    public static $registry;
    private static $actions = [];
    private static $filters = [];

    private static $events = []; // Для хранения зарегистрированных событий
    private static $callable_storage = []; // Хранилище для callable callbacks

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
     * Устанавливает реестр в Hook (для инициализации в самом начале)
     *
     * @param Registry $registry Реестр приложения
     */
    public static function setRegistry($registry)
    {
        self::$registry = $registry;
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

        // DIR_APPLICATION определяется в обеих средах (admin и catalog)
        $base_dir = DIR_APPLICATION;

        $registry->get('load')->controller('startup/hook');
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

    /**
     * Сохраняет callable в хранилище с ключом event_name
     *
     * @param string $event_name Название события
     * @param callable $callback Функция для сохранения
     * @return string ID callback
     */
    public static function store_callable($event_name, $callback)
    {
        if (!isset(self::$callable_storage[$event_name])) {
            self::$callable_storage[$event_name] = [];
        }
        
        // Генерируем уникальный ID для callback
        $callback_id = 'cb_' . uniqid() . '_' . mt_rand();
        
        // Сохраняем callback в массив для данного события
        self::$callable_storage[$event_name][] = [
            'id' => $callback_id,
            'callback' => $callback
        ];
        
        return $callback_id;
    }

    /**
     * Вызывает все сохраненные callable для события
     *
     * @param string $event_name Название события
     * @param mixed &...$args Аргументы
     * @return void
     */
    public static function invoke_stored_for_event($event_name, &...$args)
    {
        if (!isset(self::$callable_storage[$event_name])) {
            return;
        }

        foreach (self::$callable_storage[$event_name] as $item) {
            $callback = $item['callback'];
            call_user_func_array($callback, $args);
        }
    }

    /**
     * Удаляет все callable для события
     *
     * @param string $event_name Название события
     * @return bool
     */
    public static function remove_stored_for_event($event_name)
    {
        if (isset(self::$callable_storage[$event_name])) {
            unset(self::$callable_storage[$event_name]);
            return true;
        }
        return false;
    }
}
