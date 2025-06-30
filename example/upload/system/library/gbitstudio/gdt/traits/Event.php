<?php 
namespace GbitStudio\GDT\Traits;
trait Event {

    public function initEventTrait()
    {
        if($this->oc_events instanceof \GbitStudio\GDT\Events){
            return;
        }
        $this->oc_events = new \GbitStudio\GDT\Events($this->registry);
        $this->registry->get('load')->model('setting/event');
    }

    public function installEvents($events){
        $this->initEventTrait();
        $this->oc_events->installEvents($events);
    }

    public function removeEvents($events){
        $this->initEventTrait();
        $this->oc_events->uninstallEvents($events);
    }

    public function refrashEvents($events){
        $this->initEventTrait();
        $this->oc_events->refrashEvents($events);
        $this->responseJson(['success' => 'Events refrashed']);

    }
}