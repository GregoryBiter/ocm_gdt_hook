<?php
use GbitStudio\GDT\Engine\ControllerApp;
class ControllerExtensionModuleTest extends ControllerApp
{
    public function index()
    {
        //   response()->json([
        //     'success' => true,
        //     'message' => 'Hello world'
        //   ]);
        response(view('extension/module/test'));
    }


}