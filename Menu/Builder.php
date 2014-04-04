<?php

namespace CanalTP\MttBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends ContainerAware
{
    public function mttMenu(FactoryInterface $factory, array $options)
    {
        $translator = $this->container->get('translator');
        $userManager = $this->container->get('canal_tp_mtt.user');
        $menu = $factory->createItem('root');

        $user = $this->container->get('security.context')->getToken()->getUser();
        if ($user != 'anon.') {
            $menu->addChild(
                "network",
                array('route' => 'canal_tp_mtt_homepage')
            );

            $networks = $userManager->getNetworks($user);
            foreach ($networks as $network) {
                $menu['network']->addChild(
                    $network['external_id'],
                    array('route' => 'canal_tp_mtt_homepage')
                );
            }

            // TODO: Remove this and display menu buttons group in network page
            $menu->addChild(
                "Gestion des saisons",
                array(
                    'route' => 'canal_tp_mtt_season_list',
                    'routeParameters' => array(
                        'network_id' => $networks[0]['external_id']
                    )
                )
            );

            $menu->addChild(
                "Gestion des réseaux",
                array(
                    'route' => 'canal_tp_mtt_network_list'
                )
            );
        }

        return $menu;
    }
}
