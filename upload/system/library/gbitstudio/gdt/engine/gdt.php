<?php
namespace GbitStudio\GDT\Engine;

/**
 * Клас для доступу до глобального реєстру OpenCart
 * Забезпечує статичний доступ до основних об'єктів системи
 */
final class GDT {
    
    /** @var object Глобальний реєстр OpenCart */
    private static $registry = null;
    
    /**
     * Встановлює глобальний реєстр
     * 
     * @param object $registry Екземпляр Registry
     */
    public static function setRegistry($registry) {
        self::$registry = $registry;
    }
    
    /**
     * Отримує глобальний реєстр
     * 
     * @return object Registry екземпляр
     * @throws \Exception якщо реєстр не ініціалізовано
     */
    public static function registry() {
        if (self::$registry === null) {
            throw new \Exception('GDT Registry not initialized');
        }
        
        return self::$registry;
    }
    
    /**
     * Отримує об'єкт config з реєстру
     * 
     * @return object Config екземпляр
     */
    public static function config() {
        return self::registry()->get('config');
    }
    
    /**
     * Отримує об'єкт db з реєстру
     * 
     * @return object DB екземпляр
     */
    public static function db() {
        return self::registry()->get('db');
    }
    
    /**
     * Отримує об'єкт log з реєстру
     * 
     * @return object Log екземпляр
     */
    public static function log() {
        if (self::registry()->has('log')) {
            return self::registry()->get('log');
        }
        
        return null;
    }
    
    /**
     * Записує повідомлення в лог
     * 
     * @param string $message Повідомлення для запису
     */
    public static function logWrite($message) {
        try {
            $log = self::log();
            
            if ($log !== null) {
                $log->write('[GDT Hook] ' . $message);
            }
        } catch (\Exception $e) {
            // Якщо не можемо записати в лог - ігноруємо
            error_log('[GDT Hook] ' . $message);
        }
    }
    
    /**
     * Отримує об'єкт request з реєстру
     * 
     * @return object Request екземпляр
     */
    public static function request() {
        return self::registry()->get('request');
    }
    
    /**
     * Отримує об'єкт response з реєстру
     * 
     * @return object Response екземпляр
     */
    public static function response() {
        return self::registry()->get('response');
    }
    
    /**
     * Отримує об'єкт session з реєстру
     * 
     * @return object Session екземпляр
     */
    public static function session() {
        return self::registry()->get('session');
    }
    
    /**
     * Отримує об'єкт url з реєстру
     * 
     * @return object Url екземпляр
     */
    public static function url() {
        return self::registry()->get('url');
    }
    
    /**
     * Отримує об'єкт load з реєстру
     * 
     * @return object Loader екземпляр
     */
    public static function load() {
        return self::registry()->get('load');
    }
    
    /**
     * Перевіряє чи ініціалізовано реєстр
     * 
     * @return bool true якщо реєстр ініціалізовано
     */
    public static function isInitialized() {
        return self::$registry !== null;
    }
}
