<?php
namespace GbitStudio\GDT\Engine;

/**
 * Клас для кешування hook_boot функцій розширень
 * Використовує файлове кешування без системи OpenCart
 */
class HookCache {
    
    /** @var string Директорія для кешу */
    private static $cache_dir = null;
    
    /** @var string Ім'я файлу кешу */
    private static $cache_file = null;
    
    /** @var array Кеш у пам'яті */
    private static $memory_cache = null;
    
    /**
     * Ініціалізує директорію для кешування
     */
    private static function initCacheDir() {
        if (self::$cache_dir !== null) {
            return;
        }
        
        // Використовуємо системну директорію storage/cache/hook
        if (defined('DIR_STORAGE')) {
            self::$cache_dir = DIR_STORAGE . 'cache/hook/';
        } else {
            // Fallback для старих версій
            self::$cache_dir = DIR_SYSTEM . 'storage/cache/hook/';
        }
        
        // Створюємо директорію якщо не існує
        if (!is_dir(self::$cache_dir)) {
            @mkdir(self::$cache_dir, 0755, true);
        }
    }
    
    /**
     * Отримує ім'я файлу кешу залежно від середовища
     * 
     * @return string Ім'я файлу кешу
     */
    private static function getCacheFileName() {
        if (self::$cache_file === null) {
            $is_admin = defined('DIR_CATALOG');
            self::$cache_file = $is_admin ? 'hook_boot_cache_admin.php' : 'hook_boot_cache_catalog.php';
        }
        return self::$cache_file;
    }
    
    /**
     * Отримує дані з кешу
     * 
     * @return array|null Масив з hook_boot функціями або null якщо кеш застарів
     */
    public static function get() {
        // Якщо є в пам'яті - повертаємо
        if (self::$memory_cache !== null) {
            return self::$memory_cache;
        }
        
        self::initCacheDir();
        
        $cache_path = self::$cache_dir . self::getCacheFileName();
        
        // Перевіряємо чи існує файл кешу
        if (!is_file($cache_path)) {
            return null;
        }
        
        // Читаємо кеш
        $cache_data = @include($cache_path);
        
        if (!is_array($cache_data) || !isset($cache_data['timestamp']) || !isset($cache_data['hooks'])) {
            return null;
        }
        
        // Перевіряємо чи не застарів кеш (якщо минуло більше години - перебудовуємо)
        // Також перевіряємо чи не змінилися файли розширень
        if (!self::isCacheValid($cache_data)) {
            return null;
        }
        
        // Зберігаємо в пам'яті
        self::$memory_cache = $cache_data['hooks'];
        
        return $cache_data['hooks'];
    }
    
    /**
     * Зберігає дані в кеш
     * 
     * @param array $hooks Масив з hook_boot функціями
     * @return bool Успішність збереження
     */
    public static function set($hooks) {
        self::initCacheDir();
        
        $cache_path = self::$cache_dir . self::getCacheFileName();
        
        $cache_data = [
            'timestamp' => time(),
            'hooks' => $hooks,
            'files_hash' => self::getExtensionsHash()
        ];
        
        // Формуємо PHP файл з даними
        $content = '<?php' . PHP_EOL;
        $content .= '// Hook boot cache generated at ' . date('Y-m-d H:i:s') . PHP_EOL;
        $content .= 'return ' . var_export($cache_data, true) . ';' . PHP_EOL;
        
        // Зберігаємо
        $result = @file_put_contents($cache_path, $content, LOCK_EX);
        
        if ($result !== false) {
            // Зберігаємо в пам'яті
            self::$memory_cache = $hooks;
            return true;
        }
        
        return false;
    }
    
    /**
     * Очищує кеш
     * 
     * @return bool Успішність очищення
     */
    public static function clear() {
        self::initCacheDir();
        
        $cache_path = self::$cache_dir . self::getCacheFileName();
        
        self::$memory_cache = null;
        
        if (is_file($cache_path)) {
            return @unlink($cache_path);
        }
        
        return true;
    }
    
    /**
     * Перевіряє чи актуальний кеш
     * 
     * @param array $cache_data Дані кешу
     * @return bool true якщо кеш актуальний
     */
    private static function isCacheValid($cache_data) {
        // Кеш дійсний 1 годину
        $max_age = 3600;
        
        if (time() - $cache_data['timestamp'] > $max_age) {
            return false;
        }
        
        // Перевіряємо чи не змінилися файли розширень
        if (isset($cache_data['files_hash'])) {
            $current_hash = self::getExtensionsHash();
            if ($current_hash !== $cache_data['files_hash']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Обчислює хеш всіх файлів розширень для перевірки змін
     * 
     * @return string MD5 хеш
     */
    private static function getExtensionsHash() {
        $hash_data = '';
        
        try {
            // Отримуємо реєстр для доступу до БД
            $registry = \GbitStudio\GDT\Engine\GDT::registry();
            
            if (!$registry->has('db')) {
                return md5('no_db');
            }
            
            $db = $registry->get('db');
            
            // Отримуємо всі розширення з БД
            $query = $db->query("SELECT `type`, `code` FROM `" . DB_PREFIX . "extension` ORDER BY `type`, `code`");
            
            $is_admin = defined('DIR_CATALOG');
            $base_dir = $is_admin ? constant('DIR_APPLICATION') : constant('DIR_CATALOG');
            
            foreach ($query->rows as $extension) {
                $type = $extension['type'];
                $code = $extension['code'];
                
                // Шлях до контролера
                $controller_path = $base_dir . 'controller/extension/' . $type . '/' . $code . '.php';
                
                if (is_file($controller_path)) {
                    // Додаємо час модифікації файлу до хешу
                    $hash_data .= $controller_path . ':' . filemtime($controller_path) . ';';
                }
            }
            
        } catch (\Exception $e) {
            return md5('error_' . $e->getMessage());
        }
        
        return md5($hash_data);
    }
    
    /**
     * Отримує інформацію про кеш
     * 
     * @return array Інформація про кеш
     */
    public static function getInfo() {
        self::initCacheDir();
        
        $cache_path = self::$cache_dir . self::getCacheFileName();
        
        $info = [
            'exists' => false,
            'path' => $cache_path,
            'size' => 0,
            'created' => null,
            'hooks_count' => 0,
            'valid' => false
        ];
        
        if (is_file($cache_path)) {
            $info['exists'] = true;
            $info['size'] = filesize($cache_path);
            $info['created'] = date('Y-m-d H:i:s', filemtime($cache_path));
            
            $cache_data = @include($cache_path);
            
            if (is_array($cache_data)) {
                $info['hooks_count'] = isset($cache_data['hooks']) ? count($cache_data['hooks']) : 0;
                $info['valid'] = self::isCacheValid($cache_data);
            }
        }
        
        return $info;
    }
}
