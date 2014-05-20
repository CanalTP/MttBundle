<?php

namespace CanalTP\MttBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SeasonLockedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        // Dit au dispatcher que vous voulez �couter l'�v�nement
        // form.pre_set_data et que la m�thode preSetData doit �tre appel�e
        return array(FormEvents::SUBMIT => 'submit');
    }

    public function submit(FormEvent $event)
    {
        $entity = $event->getData();
        $form = $event->getForm();

        var_dump(get_class($entity));
        die;
    }
}