<?php

namespace GbitStudio\GDT\Engine;

class HookMetaParser {
    
    /**
     * Парсит метаданные из файла хука
     *
     * @param string $file_path Путь к файлу хука
     * @return array Массив с метаданными
     */
    public static function parseHookMeta($file_path) {
        if (!is_file($file_path)) {
            return [];
        }
        
        $content = file_get_contents($file_path);
        $meta = [];
        
        // Ищем блок комментариев в начале файла
        if (preg_match('/\/\*(.*?)\*\//s', $content, $matches)) {
            $comment_block = $matches[1];
            
            // Парсим каждую строку метаданных
            $lines = explode("\n", $comment_block);
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Убираем звездочки из комментариев
                $line = ltrim($line, ' *');
                
                // Ищем пары ключ: значение
                if (preg_match('/^([a-zA-Z_]+):\s*(.+)$/', $line, $line_matches)) {
                    $key = trim($line_matches[1]);
                    $value = trim($line_matches[2]);
                    
                    $meta[$key] = $value;
                }
            }
        }
        
        // Устанавливаем значения по умолчанию
        $defaults = [
            'name' => basename($file_path, '.php'),
            'description' => '',
            'version' => '1.0.0',
            'author' => '',
            'controller' => '',
            'dependencies' => [],
            'priority' => 10,
            'status' => 'active',
            'hidden' => false
        ];
        
        $meta = array_merge($defaults, $meta);
        
        // Обработка массивов (например, dependencies)
        if (isset($meta['dependencies']) && is_string($meta['dependencies'])) {
            $meta['dependencies'] = array_map('trim', explode(',', $meta['dependencies']));
        }
        
        // Обработка boolean полей
        if (isset($meta['hidden']) && is_string($meta['hidden'])) {
            $meta['hidden'] = in_array(strtolower($meta['hidden']), ['true', '1', 'yes', 'on']);
        }
        
        return $meta;
    }
    
    /**
     * Получает все метаданные хуков из директории
     *
     * @param string $hook_dir Директория с хуками
     * @return array Массив метаданных всех хуков
     */
    public static function getAllHooksMeta($hook_dir = null) {
        $hooks_meta = [];
        
        // Если директория не указана, сканируем обе директории
        if ($hook_dir === null) {
            $directories = [];
            
            // Проверяем admin хуки
            if (defined('DIR_APPLICATION')) {
                $admin_dir = constant('DIR_APPLICATION') . 'controller/hook/';
                if (is_dir($admin_dir)) {
                    $directories['admin'] = $admin_dir;
                }
            }
            
            // Проверяем catalog хуки
            // if (defined('DIR_CATALOG')) {
            //     $catalog_dir = constant('DIR_CATALOG') . 'controller/hook/';
            //     if (is_dir($catalog_dir)) {
            //         $directories['catalog'] = $catalog_dir;
            //     }
            // }
            
            foreach ($directories as $context => $dir) {
                $files = glob($dir . '*.php');
                
                foreach ($files as $file) {
                    $hook_name = basename($file, '.php');
                    $hooks_meta[$context . '_' . $hook_name] = self::parseHookMeta($file);
                    $hooks_meta[$context . '_' . $hook_name]['file_path'] = $file;
                    $hooks_meta[$context . '_' . $hook_name]['context'] = $context;
                }
            }
        } else {
            // Сканируем указанную директорию
            if (is_dir($hook_dir)) {
                $files = glob($hook_dir . '*.php');
                
                foreach ($files as $file) {
                    $hook_name = basename($file, '.php');
                    $hooks_meta[$hook_name] = self::parseHookMeta($file);
                    $hooks_meta[$hook_name]['file_path'] = $file;
                }
            }
        }
        
        return $hooks_meta;
    }
    
    /**
     * Проверяет, активен ли хук
     *
     * @param string $hook_name Имя хука
     * @param object $registry Реестр OpenCart
     * @return boolean
     */
    public static function isHookActive($hook_name, $registry) {
        // Проверяем настройки модуля
        $status_key = 'module_' . $hook_name . '_status';
        return $registry->get('config')->get($status_key);
    }
    
    /**
     * Валидирует метаданные хука
     *
     * @param array $meta Метаданные хука
     * @return array Массив ошибок валидации
     */
    public static function validateHookMeta($meta) {
        $errors = [];
        
        // Обязательные поля
        $required_fields = ['name', 'controller'];
        
        foreach ($required_fields as $field) {
            if (empty($meta[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }
        
        // Проверка версии
        if (!empty($meta['version']) && !preg_match('/^\d+\.\d+\.\d+$/', $meta['version'])) {
            $errors[] = "Invalid version format. Use x.x.x format";
        }
        
        // Проверка приоритета
        if (!empty($meta['priority']) && (!is_numeric($meta['priority']) || $meta['priority'] < 0)) {
            $errors[] = "Priority must be a positive number";
        }
        
        return $errors;
    }
}