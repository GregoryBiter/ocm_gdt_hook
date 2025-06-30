<?php
namespace GbitStudio\GDT\Engine;
abstract class HookController {
    protected $registry;

    public function __construct($registry) {
        $this->registry = $registry;
    }

    abstract public function boot();

    public function index() {
        //защита від прямого доступу
        
    }
}